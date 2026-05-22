<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Assessment;
use App\Models\Client;
use App\Models\Lead;
use App\Models\AssessmentQuestionResponse;
use App\Models\AssessmentPillarScore;
use App\Services\AiReportGenerationService;
use App\Services\AssessmentAiPayloadBuilder;
use App\Services\AssessmentIndexCalculator;
use App\Services\InternalCrmService;
use App\Services\ReportPdfService;
use Illuminate\Support\Arr;
use Illuminate\Http\Client\Response;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class AssessmentScoringController extends Controller
{
    public function create()
    {
        $clients = Client::orderBy('company_name')->get();
        // In reality, link to snapshot submissions if available
        $leads = \App\Models\Lead::where('converted_to_client', false)->get();
        
        return view('admin.assessments.create', compact('clients', 'leads'));
    }

    public function store(Request $request, InternalCrmService $internalCrm)
    {
        $data = $request->validate([
            'client_id' => 'nullable|exists:clients,id',
            'type' => 'required|in:PIR,SIR',
            'report_tier' => 'required|in:Tier 1 Rapid,Tier 2 Full',
            'name' => 'required|string',
            'target_entity' => 'nullable|string',
            'snapshot_submission_id' => 'nullable|exists:leads,id',
        ]);

        if (empty($data['client_id']) && empty($data['snapshot_submission_id'])) {
            return back()->withErrors(['client_id' => 'Select a client or start from an existing lead.'])->withInput();
        }

        if (! empty($data['snapshot_submission_id'])) {
            $lead = Lead::findOrFail($data['snapshot_submission_id']);
            $data['client_id'] = $internalCrm->convertLeadToClient($lead)->id;
        }

        $assessment = Assessment::create([
            'client_id' => $data['client_id'],
            'type' => $data['type'],
            'report_tier' => $data['report_tier'],
            'name' => $data['name'],
            'target_entity' => $data['target_entity'],
            'snapshot_submission_id' => $data['snapshot_submission_id'] ?? null,
            'status' => 'draft',
            'overall_score' => 0,
            'rag_status' => 'Red',
            'assessor_id' => auth()->id() ?? 1,
            'critical_flag' => false,
        ]);

        if ($data['type'] === 'PIR') {
            return redirect()->route('admin.assessments.score.phi', $assessment->id);
        } else {
            return redirect()->route('admin.assessments.score.itsm', $assessment->id);
        }
    }

    public function scorePhi(Assessment $assessment)
    {
        $assessment->load(['questionResponses', 'pillarScores']);
        
        $framework = \App\Models\AssessmentFramework::where('code', $assessment->type)->with('pillars.questions')->first();
        $questions = [];
        if ($framework) {
            foreach ($framework->pillars as $pillar) {
                $pillarName = $pillar->code . ' — ' . $pillar->name;
                $qs = [];
                foreach ($pillar->questions as $q) {
                    if ($q->level === 'full') {
                        $qs[$q->question_code] = $q->question_text;
                    }
                }
                if (!empty($qs)) {
                    $questions[$pillarName] = $qs;
                }
            }
        }
        
        return view('admin.assessments.score-phi', compact('assessment', 'questions'));
    }

    public function scoreItsm(Assessment $assessment)
    {
        $assessment->load(['questionResponses', 'pillarScores']);
        
        $framework = \App\Models\AssessmentFramework::where('code', $assessment->type)->with('pillars.questions')->first();
        $questions = [];
        if ($framework) {
            foreach ($framework->pillars as $pillar) {
                $pillarName = $pillar->code . ' — ' . $pillar->name;
                $qs = [];
                foreach ($pillar->questions as $q) {
                    if ($q->level === 'full') {
                        $qs[$q->question_code] = $q->question_text;
                    }
                }
                if (!empty($qs)) {
                    $questions[$pillarName] = $qs;
                }
            }
        }
        
        return view('admin.assessments.score-itsm', compact('assessment', 'questions'));
    }

    public function autoSave(Request $request, Assessment $assessment)
    {
        // Accept either a single question response update, or context/global fields update
        if ($request->has('question_code')) {
            $data = $request->only([
                'question_code',
                'pillar_name',
                'question',
                'score',
                'evidence_note',
                'source_type',
                'respondent_role',
                'document_source',
                'stakeholder_divergence_note',
                'confidence',
                'assessor_comment',
            ]);
            
            AssessmentQuestionResponse::updateOrCreate(
                [
                    'assessment_id' => $assessment->id,
                    'pillar_name' => $data['pillar_name'],
                    'question' => $data['question_code'] . ': ' . $data['question'],
                ],
                [
                    'score' => $data['score'] ?? 0,
                    'evidence_note' => $data['evidence_note'],
                    'source_type' => $data['source_type'] ?? null,
                    'respondent_role' => $data['respondent_role'] ?? null,
                    'document_source' => $data['document_source'] ?? null,
                    'stakeholder_divergence_note' => $data['stakeholder_divergence_note'] ?? null,
                    'confidence' => $data['confidence'] ?? 'medium',
                    'confidence_level' => $data['confidence'] ?? 'medium',
                ]
            );

            // Re-calculate all scores!
            $this->recalculateScores($assessment);

            return response()->json(['status' => 'saved', 'calculated' => $assessment->fresh()]);
        }
        
        // If it's a context / global fields save
        if ($request->has('fields')) {
            $assessment->update($this->normaliseContextFields($request->input('fields', [])));
            return response()->json(['status' => 'saved context']);
        }

        // If it's a pillar level commentary save
        if ($request->has('pillar_level_update')) {
            AssessmentPillarScore::updateOrCreate(
                [
                    'assessment_id' => $assessment->id,
                    'name' => $request->input('pillar_name'),
                ],
                $request->only(['commentary', 'key_risks', 'immediate_actions'])
            );
            return response()->json(['status' => 'saved pillar extra']);
        }

        return response()->json(['status' => 'ignored']);
    }

    public function generateReport(
        Assessment $assessment,
        AssessmentAiPayloadBuilder $payloadBuilder,
        AiReportGenerationService $aiReportGeneration
    )
    {
        $assessment->load(['questionResponses', 'pillarScores', 'client']);
        
        $framework = \App\Models\AssessmentFramework::where('code', $assessment->type)->with('pillars.questions')->first();
        $questionMap = [];
        if ($framework) {
            foreach ($framework->pillars as $pillar) {
                foreach ($pillar->questions as $q) {
                    $questionMap[$q->question_code] = $q->question_text;
                }
            }
        }

        $pillarScores = $assessment->pillarScores
            ->mapWithKeys(fn ($score) => [$score->name => (float) $score->score])
            ->toArray();
        $aiPayload = $payloadBuilder->buildFullPayload($assessment);
        $promptKey = $payloadBuilder->promptKey($assessment);
        $systemPrompt = config("ai.prompts.{$promptKey}");

        if (!$systemPrompt) {
            throw new \Exception("Prompt not found: {$promptKey}");
        }

        $results = [
            'overall_score' => $assessment->overall_score,
            'rag_status' => $assessment->rag_status,
            'pillar_scores' => $pillarScores,
            'index_scores' => [
                'BRI' => $assessment->bri,
                'VRI' => $assessment->vri,
                'DMI' => $assessment->dmi,
                'RII' => $assessment->rii,
                'CHI' => $assessment->chi,
                'SSI' => $assessment->ssi,
                'SMI' => $assessment->smi,
                'SIMI' => $assessment->simi,
                'BAURI' => $assessment->bau_readiness,
                'smi_simi_delta' => $assessment->smi !== null && $assessment->simi !== null
                    ? round($assessment->smi - $assessment->simi, 2)
                    : null,
            ],
            'type' => $assessment->type,
            'user' => [
                'name' => $assessment->client->company_name ?? 'Client',
                'company' => $assessment->client->company_name ?? 'Client',
            ],
            'detailed_responses' => $assessment->questionResponses->map(function($resp) use ($questionMap) {
                $qCode = explode(':', $resp->question)[0];
                return [
                    'pillar_name' => $resp->pillar_name,
                    'question_code' => $qCode,
                    'question_text' => $questionMap[$qCode] ?? $resp->question,
                    'score' => $resp->score,
                    'evidence_note' => $resp->evidence_note,
                    'respondent_role' => $resp->respondent_role,
                    'document_source' => $resp->document_source,
                    'stakeholder_divergence_note' => $resp->stakeholder_divergence_note,
                    'confidence' => $resp->confidence,
                ];
            })->toArray(),
        ];

        $answers = $assessment->questionResponses->pluck('score', 'question')->toArray();

        try {
            $data = $aiReportGeneration->generate($promptKey, $systemPrompt, $aiPayload, [
                'type' => strtoupper($assessment->type) . '_FULL',
                'is_full' => true,
                'results' => $results,
                'answers' => $answers,
                'assessment_id' => $assessment->id,
                'submitted_at' => now()->toDateTimeString(),
            ]);
            $aiRecommendation = $data['output'] ?? $data['recommendation'] ?? null;
            $aiDraft = $this->normaliseAiDraft($aiRecommendation, $data);

            if ($aiDraft === null && blank($aiRecommendation)) {
                Log::warning('Full assessment AI response missing usable content', [
                    'assessment_id' => $assessment->id,
                    'response_keys' => array_keys($data),
                ]);

                return redirect()
                    ->route('admin.assessments.show', $assessment)
                    ->with('error', 'AI response was received but did not include a usable report payload.');
            }

            $topRisks = $data['top_5_risks'] ?? null;
            
            if ($topRisks && is_array($topRisks)) {
                $topRisks = json_encode($topRisks);
            }
        } catch (\Exception $e) {
            Log::error('Full assessment AI generation failed', [
                'assessment_id' => $assessment->id,
                'message' => $e->getMessage(),
            ]);

            return redirect()
                ->route('admin.assessments.show', $assessment)
                ->with('error', 'AI report generation failed: ' . $e->getMessage());
        }

        $assessment->update([
            'ai_recommendation' => $aiRecommendation,
            'ai_draft_json' => $aiDraft,
            'top_5_risks' => $topRisks,
            'status' => 'completed'
        ]);

        return redirect()->route('admin.assessments.show', $assessment)->with('success', 'AI Report generated successfully.');
    }

    public function exportPdf(Assessment $assessment, ReportPdfService $reportPdfService)
    {
        if (!$assessment->ai_draft_json && blank($assessment->ai_recommendation)) {
            return redirect()
                ->route('admin.assessments.show', $assessment)
                ->with('error', 'Generate AI report first before exporting the PDF.');
        }

        return $reportPdfService->download($assessment);
    }

    private function recalculateScores(Assessment $assessment)
    {
        $responses = AssessmentQuestionResponse::where('assessment_id', $assessment->id)->get();
        if ($responses->isEmpty()) {
            return;
        }

        // Get framework definitions from DB
        $framework = \App\Models\AssessmentFramework::where('code', $assessment->type)->first();
        if (!$framework) return;

        $dbPillars = \App\Models\AssessmentPillar::where('framework_id', $framework->id)->get()->keyBy('code');

        // Calculate Pillar Averages
        // Responses store values like "P1 — Governance..."; extract the exact
        // code so P1 never accidentally absorbs P10, or D1 absorbs D10-D12.
        $groupedResponses = $responses->groupBy(function($r) use ($dbPillars) {
            $pillarName = (string) $r->pillar_name;
            $candidateCode = trim(explode(' — ', $pillarName, 2)[0]);

            if ($candidateCode !== '' && isset($dbPillars[$candidateCode])) {
                return $candidateCode;
            }

            return $r->pillar_name;
        });
        
        $weightedScoreSum = 0;
        $totalWeight = 0;
        $isCriticalFailure = false;

        foreach ($groupedResponses as $pCode => $qs) {
            $pillar = $dbPillars[$pCode] ?? null;
            
            // Formula 1: raw_score = ROUND( sum/count, 2 )
            $avg = round($qs->avg('score'), 2);
            
            $rag = 'Red';
            if ($avg >= 3.8) {
                $rag = 'Green';
            } elseif ($avg >= 2.5) {
                $rag = 'Amber';
            }

            AssessmentPillarScore::updateOrCreate(
                [
                    'assessment_id' => $assessment->id,
                    'name' => $pillar ? ($pillar->code . ' — ' . $pillar->name) : $pCode
                ],
                [
                    'score' => $avg,
                    'rag_status' => $rag,
                    'critical_flag' => ($pillar && $pillar->is_critical && $avg < 3.0)
                ]
            );

            if ($pillar) {
                // Formula 2: weighted_pillar_score = ROUND( raw_score * weight, 2 )
                $weightedPillarScore = round($avg * $pillar->weight, 2);
                
                $weightedScoreSum += $weightedPillarScore;
                $totalWeight += $pillar->weight;
                
                if ($pillar->is_critical && $avg < 3.0) {
                    $isCriticalFailure = true;
                }
            } else {
                // If pillar not found in DB bank, treat as weight 1.0 for safety
                $weightedScoreSum += $avg;
                $totalWeight += 1.0;
            }
        }

        // Formula 3: overall_score = ROUND( SUM(weighted_scores) / SUM(weights), 2 )
        $overallScore = $totalWeight > 0 ? round($weightedScoreSum / $totalWeight, 2) : 0;
        
        $overallRag = 'Red';
        if ($overallScore >= 3.8) {
            $overallRag = 'Green';
        } elseif ($overallScore >= 2.5) {
            $overallRag = 'Amber';
        }

        $updates = [
            'overall_score' => $overallScore,
            'rag_status' => $overallRag,
            'critical_flag' => $isCriticalFailure || ($overallScore < 3.0),
        ];


        // Specific Indices for PIR
        if ($assessment->type === 'PIR') {
            $pillarScores = AssessmentPillarScore::where('assessment_id', $assessment->id)->get()->keyBy(function($p) {
                return explode(' — ', $p->name)[0]; // Get the code like P1, P2...
            });

            $pirIndices = AssessmentIndexCalculator::calculatePir(
                $pillarScores->all(),
                AssessmentIndexCalculator::normalizeQuestionAnswers($responses->pluck('score', 'question')->toArray())
            );

            $updates['bri'] = $pirIndices['BRI'];
            $updates['vri'] = $pirIndices['VRI'];
            $updates['dmi'] = $pirIndices['DMI'];
            $updates['rii'] = $pirIndices['RII'];

            // CHI: specific compliance questions
            $complianceQuestions = ['P1.F8', 'P3.F11', 'P5.F8', 'P5.F9', 'P8.F8', 'P8.F9'];
            $chi_qs = $responses->filter(function($r) use ($complianceQuestions) {
                foreach ($complianceQuestions as $code) {
                    if (str_starts_with($r->question, $code . ':')) return true;
                }
                return false;
            });
            if ($chi_qs->isNotEmpty()) {
                $updates['chi'] = round($chi_qs->avg('score'), 2);
            }
        }

        // Specific Indices for SIR
        if ($assessment->type === 'SIR') {
            $pillarScores = AssessmentPillarScore::where('assessment_id', $assessment->id)->get()->keyBy(function($p) {
                return explode(' — ', $p->name)[0]; // Get the code like D1, D2...
            });

            $sirIndices = AssessmentIndexCalculator::calculateSir($pillarScores->all());

            $updates['ssi'] = $sirIndices['SSI'];
            $updates['smi'] = $sirIndices['SMI'];
            $updates['simi'] = $sirIndices['SIMI'];
            $updates['bau_readiness'] = $sirIndices['BAURI'];

            // CHI: average of D10 (Security & Compliance)
            if (isset($pillarScores['D10'])) {
                $updates['chi'] = $pillarScores['D10']->score;
            }
        }

        $assessment->update($updates);
    }

    private function normaliseContextFields(array $fields): array
    {
        $allowed = [
            'call_date',
            'call_type',
            'call_attendees',
            'call_summary',
            'client_concerns',
            'next_agreed_action',
            'delivery_stage',
            'service_context',
            'regulatory_context',
            'sponsor_name',
            'interview_count',
            'documents_reviewed',
            'programme_value',
            'annual_service_cost',
            'reporting_accuracy_risk',
            'reporting_accuracy_evidence',
            'sponsor_position',
            'operational_position',
            'divergence_areas',
            'overall_assessor_comment',
            'top_5_risks',
            'executive_summary_override',
            'recommended_next_step',
            'service_criticality',
            'service_hours',
            'primary_support_model',
            'vendor_landscape_summary',
            'top_incident_themes',
            'top_problem_themes',
            'service_debt_notes',
        ];

        $normalised = array_intersect_key($fields, array_flip($allowed));

        foreach (['documents_reviewed', 'divergence_areas'] as $listField) {
            if (array_key_exists($listField, $normalised) && is_string($normalised[$listField])) {
                $normalised[$listField] = array_values(array_filter(array_map('trim', preg_split('/\r\n|\r|\n|,/', $normalised[$listField]))));
            }
        }

        if (array_key_exists('reporting_accuracy_risk', $normalised)) {
            $normalised['reporting_accuracy_risk'] = filter_var($normalised['reporting_accuracy_risk'], FILTER_VALIDATE_BOOLEAN);
        }

        foreach (['interview_count', 'programme_value', 'annual_service_cost'] as $numericField) {
            if (array_key_exists($numericField, $normalised) && $normalised[$numericField] === '') {
                $normalised[$numericField] = null;
            }
        }

        return $normalised;
    }

    private function normaliseAiDraft($aiRecommendation, array $responseData): ?array
    {
        $structuredKeys = $this->structuredDraftKeys();

        foreach ([
            $aiRecommendation,
            $responseData['report'] ?? null,
            $responseData['snapshot_report_json'] ?? null,
            $responseData['output'] ?? null,
            $responseData['recommendation'] ?? null,
            $responseData['content'] ?? null,
            $responseData['message'] ?? null,
            $responseData,
        ] as $candidate) {
            $draft = $this->extractStructuredDraft($candidate, $structuredKeys);
            if ($draft !== null) {
                return $draft;
            }
        }

        foreach (['data', 'body', 'result', 'response', 'payload'] as $key) {
            if (! isset($responseData[$key])) {
                continue;
            }

            $draft = $this->extractStructuredDraft($responseData[$key], $structuredKeys);
            if ($draft !== null) {
                return $draft;
            }
        }

        return null;
    }

    private function normaliseAiHttpResponse(Response $response): array
    {
        $json = $response->json();
        if (is_array($json)) {
            return $this->normaliseAiResponsePayload($json);
        }

        $body = trim($response->body());
        if ($body === '') {
            return [];
        }

        $decoded = $this->decodeJsonCandidate($body);
        if (is_array($decoded)) {
            return $this->normaliseAiResponsePayload($decoded);
        }

        $structuredDraft = $this->extractStructuredDraft($body, $this->structuredDraftKeys());
        if ($structuredDraft !== null) {
            return $structuredDraft + ['output' => $body];
        }

        return $this->normaliseAiResponsePayload([
            'output' => $body,
        ]);
    }

    private function structuredDraftKeys(): array
    {
        return [
            'cover_letter',
            'executive_position',
            'intelligence_dashboard',
            'stakeholder_intelligence',
            'intelligence_profile',
            'risk_register',
            'raid_summary',
            'root_cause_analysis',
            'priority_plan',
            'compliance_risk_signals',
            'final_position',
            'tier1_bridge',
        ];
    }

    private function extractStructuredDraft(mixed $value, array $structuredKeys): ?array
    {
        if (is_string($value)) {
            $decoded = $this->decodeJsonCandidate($value);
            if (is_array($decoded)) {
                return $this->extractStructuredDraft($decoded, $structuredKeys);
            }

            return null;
        }

        if (! is_array($value)) {
            return null;
        }

        $draft = array_intersect_key($value, array_flip($structuredKeys));
        if ($draft !== []) {
            return $draft;
        }

        if (array_is_list($value)) {
            foreach ($value as $item) {
                $draft = $this->extractStructuredDraft($item, $structuredKeys);
                if ($draft !== null) {
                    return $draft;
                }
            }

            return null;
        }

        foreach (['report', 'snapshot_report_json', 'output', 'recommendation', 'content', 'message', 'text'] as $key) {
            if (! array_key_exists($key, $value)) {
                continue;
            }

            $draft = $this->extractStructuredDraft($value[$key], $structuredKeys);
            if ($draft !== null) {
                return $draft;
            }
        }

        return null;
    }

    private function decodeJsonCandidate(string $value): ?array
    {
        $candidate = trim($value);

        if ($candidate === '') {
            return null;
        }

        if (preg_match('/```(?:json)?\s*(\{.*\})\s*```/s', $candidate, $matches)) {
            $candidate = $matches[1];
        }

        $decoded = json_decode($candidate, true);

        if (json_last_error() === JSON_ERROR_NONE && is_string($decoded)) {
            $decoded = json_decode($decoded, true);
        }

        return json_last_error() === JSON_ERROR_NONE && is_array($decoded)
            ? $decoded
            : null;
    }

    private function normaliseAiResponsePayload($responseData): array
    {
        if (! is_array($responseData)) {
            return [];
        }

        $payload = $responseData;

        while (array_is_list($payload) && count($payload) === 1 && is_array($payload[0])) {
            $payload = $payload[0];
        }

        foreach (['data', 'body', 'result', 'response', 'payload'] as $key) {
            if (! isset($payload[$key])) {
                continue;
            }

            if (is_array($payload[$key])) {
                $payload = $payload[$key];

                while (array_is_list($payload) && count($payload) === 1 && is_array($payload[0])) {
                    $payload = $payload[0];
                }
            } elseif (is_string($payload[$key])) {
                $decoded = $this->decodeJsonCandidate($payload[$key]);
                if ($decoded !== null) {
                    $payload = $decoded;
                }
            }
        }

        if (! is_array($payload)) {
            return [];
        }

        foreach (['report', 'snapshot_report_json'] as $key) {
            if (! array_key_exists($key, $payload)) {
                continue;
            }

            $draft = $this->extractStructuredDraft($payload[$key], $this->structuredDraftKeys());
            if ($draft !== null) {
                $payload = Arr::except($payload, [$key]) + $draft;
            }
        }

        return is_array($payload) ? $payload : [];
    }

}
