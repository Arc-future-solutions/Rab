<?php

namespace App\Services;

use App\Models\Assessment;
use App\Models\AssessmentFramework;
use App\Models\AssessmentQuestionBank;

class AssessmentAiPayloadBuilder
{
    private const PIR_PILLAR_NAMES = [
        'P1' => 'P1 — Governance & Decision-Making',
        'P2' => 'P2 — Planning, Stage Gates & Delivery Control',
        'P3' => 'P3 — Business Alignment, Value & Financial Control',
        'P4' => 'P4 — Change Management, Training & Adoption',
        'P5' => 'P5 — Data Readiness, Migration & GDPR',
        'P6' => 'P6 — Solution, Process Fit & UAT',
        'P7' => 'P7 — Cutover, Go-Live, Decommissioning & Archiving',
        'P8' => 'P8 — Delivery Capability, Security & RACI',
        'P9' => 'P9 — Operational, Automation Readiness & Data Archiving',
        'P10' => 'P10 — Digital & Transformation Maturity',
    ];

    private const SIR_DOMAIN_NAMES = [
        'D1' => 'D1 — Service Governance & Ownership',
        'D2' => 'D2 — Incident & Major Incident Management',
        'D3' => 'D3 — Service Request Management',
        'D4' => 'D4 — Problem Management',
        'D5' => 'D5 — Change & Release Management',
        'D6' => 'D6 — Service Performance, SLA & Reporting',
        'D7' => 'D7 — Service Transition & BAU Readiness',
        'D8' => 'D8 — Service Operations & Support Model',
        'D9' => 'D9 — Supplier & Vendor Management',
        'D10' => 'D10 — Operational Resilience & Continuity',
        'D11' => 'D11 — Service Tooling, CMDB & Knowledge Management',
        'D12' => 'D12 — Service Intelligence & Continuous Value',
    ];

    public function buildFullPayload(Assessment $assessment): array
    {
        $assessment->loadMissing(['client', 'assessor', 'pillarScores', 'questionResponses']);

        $framework = strtoupper($assessment->type);
        $pillarScores = $this->scoreMap($assessment);
        $answers = AssessmentIndexCalculator::normalizeQuestionAnswers(
            $assessment->questionResponses->pluck('score', 'question')->toArray()
        );

        $indices = $framework === 'PIR'
            ? [
                ...AssessmentIndexCalculator::calculatePir($pillarScores, $answers),
                'CHI' => $assessment->chi,
            ]
            : [
                ...AssessmentIndexCalculator::calculateSir($pillarScores),
                'CHI' => $assessment->chi,
            ];

        $payload = [
            'framework' => $framework,
            'assessment_id' => $assessment->id,
            'client_company' => $assessment->client->company_name ?? null,
            'assessment_date' => optional($assessment->updated_at)->toDateString(),
            'tier' => $assessment->report_tier === 'Tier 2 Full' ? 'Briefing' : 'Review',
            'consultant_name' => $assessment->assessor->name ?? 'Reda Boukhiar',
            'sponsor_name' => $assessment->sponsor_name,
            'interview_count' => $assessment->interview_count,
            'documents_reviewed' => $this->stringList($assessment->documents_reviewed),
            'confidence_level' => $this->overallConfidence($assessment),
            'overall_score' => (float) $assessment->overall_score,
            'rag_status' => $assessment->rag_status,
            'regulatory_context' => $assessment->regulatory_context,
            'alert_flags' => $this->alertFlags($pillarScores),
            'question_responses' => $this->questionResponses($assessment),
            'compliance_question_scores' => $this->complianceQuestionScores($assessment),
            'evidence_notes' => $this->evidenceNotes($assessment),
            'emerging_issues' => $this->stringList($assessment->top_5_risks),
            'stakeholder_notes' => $assessment->report_tier === 'Tier 2 Full' ? [
                'sponsor_position' => $assessment->sponsor_position,
                'operational_position' => $assessment->operational_position,
                'divergence_areas' => $this->stringList($assessment->divergence_areas),
            ] : null,
        ];

        if ($framework === 'PIR') {
            return [
                ...$payload,
                'programme_name' => $assessment->target_entity ?? $assessment->name,
                'programme_type' => $assessment->name,
                'delivery_stage' => $assessment->delivery_stage,
                'pillar_scores' => $pillarScores,
                'pillar_names' => self::PIR_PILLAR_NAMES,
                'bri' => $indices['BRI'],
                'vri' => $indices['VRI'],
                'dmi' => $indices['DMI'],
                'rii' => $indices['RII'],
                'chi' => $indices['CHI'],
                'programme_value' => $assessment->programme_value !== null ? (float) $assessment->programme_value : null,
                'reporting_accuracy_risk' => (bool) $assessment->reporting_accuracy_risk,
                'reporting_accuracy_evidence' => $assessment->reporting_accuracy_evidence,
            ];
        }

        return [
            ...$payload,
            'service_name' => $assessment->target_entity ?? $assessment->name,
            'service_context' => $assessment->service_context,
            'domain_scores' => $pillarScores,
            'domain_names' => self::SIR_DOMAIN_NAMES,
            'ssi' => $indices['SSI'],
            'smi' => $indices['SMI'],
            'simi' => $indices['SIMI'],
            'bau_ri' => $indices['BAURI'],
            'chi' => $indices['CHI'],
            'smi_simi_delta' => $indices['smi_simi_delta'],
            'annual_service_cost' => $assessment->annual_service_cost !== null ? (float) $assessment->annual_service_cost : null,
        ];
    }

    public function promptKey(Assessment $assessment): string
    {
        $framework = str_starts_with(strtoupper($assessment->type), 'PIR') ? 'pir' : 'sir';
        $tier = $assessment->report_tier === 'Tier 2 Full' ? 'tier2' : 'tier1';

        return "{$framework}_full_{$tier}";
    }

    private function scoreMap(Assessment $assessment): array
    {
        return $assessment->pillarScores
            ->mapWithKeys(fn ($score) => [explode(' — ', $score->name)[0] => (float) $score->score])
            ->toArray();
    }

    private function questionResponses(Assessment $assessment): array
    {
        $questionBank = $this->questionBank($assessment);

        return $assessment->questionResponses->map(function ($response) use ($questionBank) {
            $code = $this->questionCode($response->question);
            $bankQuestion = $questionBank[$code] ?? null;

            return [
                'id' => $code,
                'pillar_code' => $bankQuestion?->pillar?->code ?? explode(' — ', (string) $response->pillar_name)[0],
                'score' => (int) $response->score,
                'is_compliance' => (bool) ($bankQuestion?->is_compliance ?? false),
            ];
        })->values()->toArray();
    }

    private function complianceQuestionScores(Assessment $assessment): array
    {
        $questionBank = $this->questionBank($assessment);
        $scores = [];

        foreach ($assessment->questionResponses as $response) {
            $code = $this->questionCode($response->question);
            if ((bool) ($questionBank[$code]?->is_compliance ?? false)) {
                $scores[$code] = (int) $response->score;
            }
        }

        return $scores;
    }

    private function evidenceNotes(Assessment $assessment): array
    {
        return $assessment->questionResponses
            ->mapWithKeys(fn ($response) => [
                $this->questionCode($response->question) => [
                    'note' => $response->evidence_note,
                    'respondent_role' => $response->respondent_role,
                    'document_source' => $response->document_source,
                    'confidence' => $this->normaliseConfidence($response->confidence),
                ],
            ])
            ->toArray();
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

    private function normaliseConfidence(?string $confidence): string
    {
        return match (strtolower((string) $confidence)) {
            'high' => 'High',
            'low' => 'Low',
            default => 'Medium',
        };
    }

    private function overallConfidence(Assessment $assessment): string
    {
        $responses = $assessment->questionResponses;
        if ($responses->isEmpty()) {
            return 'Medium';
        }

        $low = $responses->where('confidence', 'low')->count();
        $high = $responses->where('confidence', 'high')->count();

        if ($low > 0) {
            return 'Low';
        }

        return $high >= max(1, (int) ceil($responses->count() * 0.75)) ? 'High' : 'Medium';
    }

    private function alertFlags(array $scores): array
    {
        return collect($scores)
            ->filter(fn ($score) => $score < 3.0)
            ->map(fn ($score, $code) => "{$code} below 3.0")
            ->values()
            ->toArray();
    }

    private function stringList($value): array
    {
        if (is_array($value)) {
            return array_values(array_filter(array_map('trim', $value)));
        }

        if (!is_string($value) || trim($value) === '') {
            return [];
        }

        return array_values(array_filter(array_map('trim', preg_split('/\r\n|\r|\n|,/', $value))));
    }
}
