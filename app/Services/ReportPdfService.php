<?php

namespace App\Services;

use App\Models\Assessment;
use App\Models\AssessmentFramework;
use App\Models\AssessmentQuestionBank;
use Illuminate\Support\Str;
use Spatie\Browsershot\Browsershot;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ReportPdfService
{
    public function renderHtml(Assessment $assessment): string
    {
        $assessment->loadMissing(['client', 'assessor', 'pillarScores', 'questionResponses']);

        return view('admin.assessments.pdf-report', [
            'assessment' => $assessment,
            'report' => $this->reportData($assessment),
            'meta' => $this->metadata($assessment),
            'appendix' => $this->appendixData($assessment),
            'logo' => $this->logoDataUri(),
        ])->render();
    }

    public function download(Assessment $assessment): BinaryFileResponse
    {
        $directory = storage_path('app/private/reports/tmp');
        if (!is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        $filename = $this->filename($assessment);
        $path = $directory . '/' . $filename;

        Browsershot::html($this->renderHtml($assessment))
            ->setNodeBinary($this->nodeBinary())
            ->setNpmBinary($this->npmBinary())
            ->setNodeModulePath(base_path('node_modules'))
            ->noSandbox()
            ->addChromiumArguments(['disable-setuid-sandbox'])
            ->waitUntilNetworkIdle()
            ->format('A4')
            ->margins(10, 10, 24, 10)
            ->showBrowserHeaderAndFooter()
            ->headerTemplate('<span></span>')
            ->footerTemplate($this->footerTemplate($assessment))
            ->save($path);

        return response()->download($path, $filename)->deleteFileAfterSend(true);
    }

    private function metadata(Assessment $assessment): array
    {
        $isPir = $assessment->type === 'PIR';
        $isBriefing = $assessment->report_tier === 'Tier 2 Full';
        $subject = $assessment->target_entity ?: $assessment->name;

        return [
            'is_pir' => $isPir,
            'is_briefing' => $isBriefing,
            'client' => $assessment->client->company_name ?? 'Client',
            'subject' => $subject,
            'context' => $isPir ? $assessment->delivery_stage : $assessment->service_context,
            'report_type' => $this->reportType($assessment, true),
            'report_label' => $this->reportType($assessment, false),
            'date' => now()->format('F Y'),
            'scoring_version' => $assessment->scoring_version ?? '1.0',
        ];
    }

    private function reportType(Assessment $assessment, bool $caps): string
    {
        $framework = $assessment->type === 'PIR' ? 'Programme' : 'Service';
        $product = $assessment->report_tier === 'Tier 2 Full' ? 'Intelligence Briefing' : 'Intelligence Review';
        $label = "{$framework} {$product}";

        return $caps ? strtoupper("RAB {$label}") : $label;
    }

    private function filename(Assessment $assessment): string
    {
        $client = Str::slug($assessment->client->company_name ?? 'client');
        $subject = Str::slug($assessment->target_entity ?: $assessment->name ?: 'assessment');

        return "rab-{$client}-{$subject}-report.pdf";
    }

    private function reportData(Assessment $assessment): array
    {
        $draft = $assessment->ai_draft_json;

        if (is_array($draft) && $draft !== []) {
            return $draft;
        }

        return $this->legacyDraft($assessment);
    }

    private function legacyDraft(Assessment $assessment): array
    {
        $assessment->loadMissing(['pillarScores', 'questionResponses']);

        $summary = trim((string) ($assessment->ai_recommendation ?? ''));
        if ($summary === '') {
            $summary = 'Legacy assessment export generated from stored assessment data.';
        }

        $indices = $this->legacyIndices($assessment);
        $pillarScores = $assessment->pillarScores->sortBy('score')->values();

        return [
            'cover_letter' => $summary,
            'executive_position' => sprintf(
                'Overall score %s (%s). This export was generated from the stored assessment record because no structured AI draft was saved.',
                number_format((float) $assessment->overall_score, 1),
                $assessment->rag_status
            ),
            'intelligence_dashboard' => [
                'indices' => $indices,
                'alert_flags' => $this->legacyAlertFlags($assessment),
                'confidence_legend' => $this->legacyConfidenceLegend(),
            ],
            'stakeholder_intelligence' => $assessment->report_tier === 'Tier 2 Full'
                ? [
                    'divergence_summary' => 'No structured stakeholder intelligence was stored for this legacy assessment.',
                    'governance_implication' => 'Re-run AI generation to capture the structured briefing fields.',
                ]
                : null,
            'intelligence_profile' => $pillarScores->map(function ($pillar) {
                return [
                    'headline' => $pillar->name,
                    'score' => (float) $pillar->score,
                    'confidence' => 'Medium',
                    'evidence' => 'Legacy export built from stored pillar scores.',
                    'business_impact' => 'Review supporting question responses in the assessment record.',
                    'action' => 'Re-run AI generation for a structured narrative.',
                ];
            })->all(),
            'risk_register' => $pillarScores->take(5)->map(function ($pillar, $index) {
                return [
                    'risk_title' => $pillar->name,
                    'probability' => $pillar->rag_status === 'Green' ? 'Low' : ($pillar->rag_status === 'Amber' ? 'Medium' : 'High'),
                    'impact' => $pillar->critical_flag ? 'High' : ($pillar->score < 3.2 ? 'Medium' : 'Low'),
                    'owner' => 'Assessment owner',
                    'current_control' => 'Legacy export summary',
                    'action' => 'Re-run AI generation to populate the risk register.',
                ];
            })->all(),
            'raid_summary' => $assessment->type === 'PIR'
                ? [
                    'total_risks' => max(1, $pillarScores->count()),
                    'critical_risks' => $pillarScores->filter(fn ($pillar) => $pillar->critical_flag || $pillar->score < 3)->count(),
                    'issues_without_owner' => 0,
                    'overdue_actions' => 0,
                    'assessment' => 'Legacy assessment export built from stored pillar scores.',
                ]
                : null,
            'root_cause_analysis' => [
                'narrative' => 'The structured root cause narrative was not stored for this assessment. Re-run AI generation to produce the full report narrative.',
                'primary_cause' => 'Legacy assessment record',
                'causal_chain' => [
                    'Assessment created before structured AI draft storage was introduced.',
                    'Existing recommendation text is preserved, but the structured JSON payload is missing.',
                ],
            ],
            'priority_plan' => $this->legacyPriorityPlan($assessment),
            'compliance_risk_signals' => $this->legacyComplianceSignals($assessment),
            'final_position' => $summary,
            'tier1_bridge' => $assessment->type === 'SIR' ? 'Re-run AI generation to capture the structured service briefing.' : null,
        ];
    }

    private function legacyIndices(Assessment $assessment): array
    {
        $indices = [];
        $map = [
            'BRI' => $assessment->bri,
            'VRI' => $assessment->vri,
            'DMI' => $assessment->dmi,
            'RII' => $assessment->rii,
            'CHI' => $assessment->chi,
            'SSI' => $assessment->ssi,
            'SMI' => $assessment->smi,
            'SIMI' => $assessment->simi,
            'BAURI' => $assessment->bau_readiness,
        ];

        foreach ($map as $name => $score) {
            if ($score === null) {
                continue;
            }

            $indices[$name] = [
                'score' => (float) $score,
                'interpretation' => $this->scoreInterpretation((float) $score),
            ];
        }

        return $indices;
    }

    private function legacyAlertFlags(Assessment $assessment): array
    {
        $flags = [];

        if ($assessment->critical_flag) {
            $flags[] = 'Critical flag triggered on the assessment record.';
        }

        if ($assessment->chi !== null && (float) $assessment->chi < 3) {
            $flags[] = 'Compliance health requires attention.';
        }

        return $flags;
    }

    private function legacyConfidenceLegend(): array
    {
        return [
            ['label' => 'High', 'color' => '#166534', 'definition' => 'Confirmed by documentary evidence and interview.'],
            ['label' => 'Medium', 'color' => '#B45309', 'definition' => 'Supported by stored assessment scoring data.'],
            ['label' => 'Low', 'color' => '#B91C1C', 'definition' => 'Limited or contradictory evidence.'],
        ];
    }

    private function legacyPriorityPlan(Assessment $assessment): array
    {
        $headline = $assessment->name ?: 'Assessment';

        return [
            '30_days' => [[
                'action_title' => 'Re-run AI generation for structured draft',
                'owner' => 'Assessment owner',
                'deadline' => '30 days',
                'done_condition' => 'Structured JSON report is stored',
            ]],
            '60_days' => [[
                'action_title' => 'Review top scoring gaps',
                'owner' => 'Assessment owner',
                'deadline' => '60 days',
                'done_condition' => 'Question evidence is reviewed against the report',
            ]],
            '90_days' => [[
                'action_title' => 'Refresh export pack for ' . $headline,
                'owner' => 'Assessment owner',
                'deadline' => '90 days',
                'done_condition' => 'Legacy export is replaced with structured PDF',
            ]],
        ];
    }

    private function legacyComplianceSignals(Assessment $assessment): array
    {
        if ($assessment->chi === null || (float) $assessment->chi >= 3) {
            return [];
        }

        return [[
            'finding' => 'Compliance health is below the preferred threshold.',
            'domain' => 'Compliance',
            'obligation' => 'Review controls and supporting evidence.',
            'action' => 'Re-run AI generation and confirm compliance detail.',
            'deadline' => '30 days',
        ]];
    }

    private function scoreInterpretation(float $score): string
    {
        if ($score >= 4) {
            return 'Controlled';
        }

        if ($score >= 3) {
            return 'At Risk';
        }

        if ($score >= 2) {
            return 'Weak';
        }

        return 'Critical Failure';
    }

    private function nodeBinary(): string
    {
        return env('BROWSERSHOT_NODE_BINARY', '/home/harakaty6/.nvm/versions/node/v22.22.2/bin/node');
    }

    private function npmBinary(): string
    {
        return env('BROWSERSHOT_NPM_BINARY', '/home/harakaty6/.nvm/versions/node/v22.22.2/bin/npm');
    }

    private function logoDataUri(): string
    {
        $path = public_path('assets/images/logo-rab.png');

        if (!is_file($path)) {
            return '';
        }

        $mime = mime_content_type($path) ?: 'image/png';
        return 'data:' . $mime . ';base64,' . base64_encode((string) file_get_contents($path));
    }

    private function footerTemplate(Assessment $assessment): string
    {
        $legal = 'This report is produced by RAB Consulting Services Ltd. All findings are based on information provided during the engagement. '
            . 'This constitutes operational intelligence and professional advisory guidance only. It does not constitute legal, regulatory, financial, or compliance advice. '
            . '&copy; 2026 RAB Consulting Services Ltd. All rights reserved.';
        $contact = 'RAB Consulting Services Ltd | rboukhiar@rabconsultingservices.com | +44 7717 544322 | rabconsultingservices.com';

        return '<div style="width:100%;padding:4px 24px 4px 24px;font-family:Inter,Arial,Helvetica,sans-serif;font-size:6.5pt;color:#64748B;border-top:1px solid #E5E7EB;display:flex;justify-content:space-between;align-items:flex-end;gap:12px;">'
            . '<div style="flex:1;line-height:1.3;">'
            . '<div>' . $legal . '</div>'
            . '<div style="margin-top:2px;font-size:7pt;">' . $contact . '</div>'
            . '</div>'
            . '<div style="white-space:nowrap;font-size:7.5pt;font-weight:700;color:#374151;">Page <span class="pageNumber"></span> of <span class="totalPages"></span></div>'
            . '</div>';
    }

    private function appendixData(Assessment $assessment): array
    {
        $assessment->loadMissing('questionResponses');
        $questionBank = $this->questionBank($assessment);

        $rows = $assessment->questionResponses
            ->map(function ($response) use ($questionBank) {
                $questionId = $this->questionCode((string) $response->question);
                $question = $questionBank[$questionId] ?? null;

                return [
                    'question_id' => $questionId,
                    'question_text' => $question?->question_text ?: $this->questionText((string) $response->question),
                    'pillar_code' => $question?->pillar?->code ?: $this->pillarCode((string) $response->pillar_name),
                    'score' => (float) $response->score,
                    'rag' => $this->scoreInterpretation((float) $response->score),
                    'evidence_note' => trim((string) ($response->evidence_note ?? '')),
                    'confidence_level' => $this->normaliseConfidence($response->confidence_level ?? $response->confidence),
                    'respondent_role' => trim((string) ($response->respondent_role ?? '')),
                    'document_source' => trim((string) ($response->document_source ?? '')),
                    'stakeholder_divergence_note' => trim((string) ($response->stakeholder_divergence_note ?? '')),
                    'display_order' => $question?->display_order ?? PHP_INT_MAX,
                ];
            })
            ->sort(function (array $left, array $right) {
                return [$left['display_order'], $left['question_id']] <=> [$right['display_order'], $right['question_id']];
            })
            ->values()
            ->map(function (array $row) {
                unset($row['display_order']);

                return $row;
            })
            ->all();

        return [
            'is_tier2' => $assessment->report_tier === 'Tier 2 Full',
            'rows' => $rows,
            'methodology_note' => 'ABOUT RAB INTELLIGENCE INDICES — The indices in this briefing are proprietary commercial intelligence signals developed by RAB Consulting Services. They are not standard industry metrics. They are weighted composites calculated by the RAB Platform scoring engine. The AI generation engine uses them — it does not compute them. © 2026 RAB Consulting Services Ltd. All rights reserved.',
        ];
    }

    private function questionBank(Assessment $assessment): array
    {
        $framework = AssessmentFramework::where('code', $assessment->type)->first();

        if (!$framework) {
            return [];
        }

        return AssessmentQuestionBank::where('framework_id', $framework->id)
            ->with('pillar')
            ->get()
            ->keyBy('question_code')
            ->all();
    }

    private function questionCode(string $question): string
    {
        return trim(explode(':', $question, 2)[0]);
    }

    private function questionText(string $question): string
    {
        $parts = explode(':', $question, 2);

        return trim($parts[1] ?? $parts[0]);
    }

    private function pillarCode(string $pillarName): string
    {
        return trim(explode(' — ', $pillarName, 2)[0]);
    }

    private function normaliseConfidence(?string $confidence): string
    {
        return match (strtolower((string) $confidence)) {
            'high' => 'High',
            'low' => 'Low',
            default => 'Medium',
        };
    }
}
