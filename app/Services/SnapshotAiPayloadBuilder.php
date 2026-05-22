<?php

namespace App\Services;

use App\Models\AssessmentFramework;
use App\Models\AssessmentQuestionBank;
use App\Models\Lead;

class SnapshotAiPayloadBuilder
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

    public function __construct(private DiagnosticOutcomeService $outcomes)
    {
    }

    public function promptKey(string $framework): string
    {
        return strtolower($framework) . '_snapshot';
    }

    public function systemPrompt(string $framework): ?string
    {
        return config('ai.' . $this->promptKey($framework));
    }

    public function buildFromLead(Lead $lead): array
    {
        $framework = strtoupper((string) $lead->type);

        return match ($framework) {
            'PIR' => $this->buildPirFromLead($lead),
            'SIR' => $this->buildSirFromLead($lead),
            default => throw new \InvalidArgumentException("Unsupported snapshot framework: {$framework}"),
        };
    }

    private function buildPirFromLead(Lead $lead): array
    {
        $questionResponses = $this->leadQuestionResponses('PIR', $lead, 'pillar_code');
        $pillarScores = $this->areaScores(self::PIR_PILLAR_NAMES, $questionResponses, 'pillar_code');
        $indices = $lead->index_scores_json ?? [];

        return [
            'framework' => 'PIR',
            'assessment_id' => $lead->id,
            'client_company' => $lead->company,
            'programme_name' => $lead->company,
            'programme_type' => 'Snapshot',
            'delivery_stage' => $lead->delivery_stage,
            'primary_concern' => $lead->free_text_concern ?: $lead->industry,
            'regulatory_context' => $lead->regulatory_context,
            'assessment_date' => $lead->created_at?->toDateString(),
            'overall_score' => (float) $lead->overall_score,
            'rag_status' => $lead->rag_status,
            'pillar_scores' => $pillarScores,
            'pillar_names' => self::PIR_PILLAR_NAMES,
            'bri' => $this->indexValue($indices, 'BRI'),
            'vri' => $this->indexValue($indices, 'VRI'),
            'dmi' => $this->indexValue($indices, 'DMI'),
            'rii' => $this->indexValue($indices, 'RII'),
            'chi' => $this->indexValue($indices, 'CHI'),
            'alert_flags' => array_values($lead->alerts_json ?? []),
            'question_responses' => $questionResponses,
            'compliance_question_scores' => $this->complianceQuestionScores($questionResponses),
        ];
    }

    private function buildSirFromLead(Lead $lead): array
    {
        $questionResponses = $this->leadQuestionResponses('SIR', $lead, 'domain_code');
        $domainScores = $this->areaScores(self::SIR_DOMAIN_NAMES, $questionResponses, 'domain_code');
        $indices = $lead->index_scores_json ?? [];

        return [
            'framework' => 'SIR',
            'assessment_id' => $lead->id,
            'client_company' => $lead->company,
            'service_name' => $lead->company,
            'programme_type' => 'Snapshot',
            'service_context' => $lead->service_context,
            'primary_concern' => $lead->free_text_concern ?: $lead->industry,
            'regulatory_context' => $lead->regulatory_context,
            'assessment_date' => $lead->created_at?->toDateString(),
            'overall_score' => (float) $lead->overall_score,
            'rag_status' => $lead->rag_status,
            'domain_scores' => $domainScores,
            'domain_names' => self::SIR_DOMAIN_NAMES,
            'ssi' => $this->indexValue($indices, 'SSI'),
            'smi' => $this->indexValue($indices, 'SMI'),
            'simi' => $this->indexValue($indices, 'SIMI'),
            'bau_ri' => $this->indexValue($indices, 'BAURI'),
            'chi' => $this->indexValue($indices, 'CHI'),
            'smi_simi_delta' => $this->indexValue($indices, 'smi_simi_delta'),
            'alert_flags' => array_values($lead->alerts_json ?? []),
            'question_responses' => $questionResponses,
            'compliance_question_scores' => $this->complianceQuestionScores($questionResponses),
        ];
    }

    public function build(string $framework, array $results, array $answers): array
    {
        $framework = strtoupper($framework);
        $scoreMap = collect($results['pillar_scores'] ?? [])
            ->mapWithKeys(fn (array $score, string $code) => [$code => (float) ($score['score'] ?? 0)])
            ->toArray();

        $questionResponses = $this->questionResponses($framework, $results, $answers);
        $complianceScores = collect($questionResponses)
            ->filter(fn (array $response) => $response['is_compliance'])
            ->mapWithKeys(fn (array $response) => [$response['id'] => $response['score']])
            ->all();

        $basePayload = [
            'framework' => $framework,
            'client_company' => $results['user']['company'] ?? null,
            'assessment_date' => now()->toDateString(),
            'primary_concern' => $results['user']['industry'] ?? null,
            'overall_score' => (float) ($results['overall_score'] ?? 0),
            'rag_status' => $results['rag_status'] ?? 'Amber',
            'regulatory_context' => $results['regulatory_context'] ?? null,
            'alert_flags' => $this->outcomes->alerts($results['pillar_scores'] ?? []),
            'question_responses' => $questionResponses,
            'compliance_question_scores' => $complianceScores,
        ];

        if ($framework === 'PIR') {
            return [
                ...$basePayload,
                'programme_name' => $results['user']['company'] ?? 'Programme',
                'programme_type' => 'Snapshot',
                'delivery_stage' => $results['delivery_stage'] ?? null,
                'pillar_scores' => $scoreMap,
                'pillar_names' => self::PIR_PILLAR_NAMES,
                'bri' => $results['index_scores']['BRI'] ?? null,
                'vri' => $results['index_scores']['VRI'] ?? null,
                'dmi' => $results['index_scores']['DMI'] ?? null,
                'rii' => $results['index_scores']['RII'] ?? null,
                'chi' => $results['index_scores']['CHI'] ?? null,
            ];
        }

        return [
            ...$basePayload,
            'service_name' => $results['user']['company'] ?? 'Service',
            'service_context' => $results['service_context'] ?? null,
            'domain_scores' => $scoreMap,
            'domain_names' => self::SIR_DOMAIN_NAMES,
            'ssi' => $results['index_scores']['SSI'] ?? null,
            'smi' => $results['index_scores']['SMI'] ?? null,
            'simi' => $results['index_scores']['SIMI'] ?? null,
            'bau_ri' => $results['index_scores']['BAURI'] ?? null,
            'chi' => $results['index_scores']['CHI'] ?? null,
            'smi_simi_delta' => $results['index_scores']['smi_simi_delta'] ?? null,
        ];
    }

    private function questionResponses(string $framework, array $results, array $answers): array
    {
        $questionBank = $this->questionBank($framework);
        $responses = [];

        foreach ($answers as $questionCode => $score) {
            if (! is_numeric($score) || str_starts_with((string) $questionCode, 'note_')) {
                continue;
            }

            $normalisedCode = $this->normaliseQuestionCode((string) $questionCode);
            $bankQuestion = $questionBank[$normalisedCode] ?? null;
            $pillarCode = $bankQuestion['pillar_code'] ?? $this->pillarCodeFromQuestion($normalisedCode);

            $responses[] = [
                'id' => $normalisedCode,
                'pillar_code' => $pillarCode,
                'score' => (int) $score,
                'is_compliance' => (bool) ($bankQuestion['is_compliance'] ?? false),
            ];
        }

        if ($responses !== []) {
            return $responses;
        }

        return collect($results['pillar_scores'] ?? [])
            ->map(fn (array $score, string $code) => [
                'id' => $code,
                'pillar_code' => $code,
                'score' => (int) round((float) ($score['score'] ?? 0)),
                'is_compliance' => false,
            ])
            ->values()
            ->all();
    }

    private function questionBank(string $framework): array
    {
        $frameworkModel = AssessmentFramework::where('code', $framework)->first();
        if (! $frameworkModel) {
            return [];
        }

        return AssessmentQuestionBank::query()
            ->where('framework_id', $frameworkModel->id)
            ->where('level', 'snapshot')
            ->with('pillar')
            ->get()
            ->mapWithKeys(fn (AssessmentQuestionBank $question) => [
                $this->normaliseQuestionCode($question->question_code) => [
                    'pillar_code' => $question->pillar?->code ? preg_replace('/^P+/', 'P', preg_replace('/^D+/', 'D', $question->pillar->code)) : $this->pillarCodeFromQuestion($question->question_code),
                    'is_compliance' => (bool) $question->is_compliance,
                ],
            ])
            ->all();
    }

    private function normaliseQuestionCode(string $questionCode): string
    {
        return str_replace('_', '.', trim($questionCode));
    }

    private function leadQuestionResponses(string $framework, Lead $lead, string $areaKey): array
    {
        $questionBank = $this->questionBank($framework);

        return collect($lead->answers_json ?? [])
            ->filter(fn ($score, $id) => is_numeric($score) && ! str_starts_with((string) $id, 'note_'))
            ->map(function ($score, $id) use ($questionBank, $areaKey) {
                $normalisedCode = $this->normaliseQuestionCode((string) $id);
                $bankQuestion = $questionBank[$normalisedCode] ?? [];

                return [
                    'id' => $normalisedCode,
                    $areaKey => $bankQuestion['pillar_code'] ?? $this->pillarCodeFromQuestion($normalisedCode),
                    'score' => (int) $score,
                    'is_compliance' => (bool) ($bankQuestion['is_compliance'] ?? false),
                ];
            })
            ->values()
            ->all();
    }

    private function areaScores(array $areaNames, array $questionResponses, string $areaKey): array
    {
        return collect($areaNames)
            ->mapWithKeys(function (string $name, string $areaCode) use ($questionResponses, $areaKey) {
                $scores = collect($questionResponses)
                    ->where($areaKey, $areaCode)
                    ->pluck('score');

                if ($scores->isEmpty()) {
                    throw new \RuntimeException("Missing stored question scores for {$areaCode}.");
                }

                return [$areaCode => round($scores->avg(), 2)];
            })
            ->all();
    }

    private function complianceQuestionScores(array $questionResponses): array
    {
        return collect($questionResponses)
            ->filter(fn (array $response) => $response['is_compliance'])
            ->mapWithKeys(fn (array $response) => [$response['id'] => $response['score']])
            ->all();
    }

    private function indexValue(array $indices, string $key): ?float
    {
        return array_key_exists($key, $indices) && $indices[$key] !== null ? (float) $indices[$key] : null;
    }

    private function pillarCodeFromQuestion(string $questionCode): string
    {
        if (preg_match('/^([PD]\d+)/', $questionCode, $matches)) {
            return $matches[1];
        }

        return $questionCode;
    }
}
