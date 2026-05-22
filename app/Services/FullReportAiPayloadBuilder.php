<?php

namespace App\Services;

use App\Models\Assessment;
use App\Models\AssessmentFramework;
use App\Models\AssessmentQuestionBank;
use InvalidArgumentException;

class FullReportAiPayloadBuilder
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

    public function buildFromAssessment($assessment, string $tier = 'Review'): array
    {
        if (! $assessment instanceof Assessment) {
            throw new InvalidArgumentException('Full report payload builder requires an Assessment model.');
        }

        $assessment->loadMissing(['client', 'assessor', 'pillarScores', 'questionResponses']);

        if (strtoupper((string) $assessment->type) !== 'PIR') {
            throw new InvalidArgumentException('Only PIR full Tier 1 payloads are supported.');
        }

        if ($tier !== 'Review') {
            throw new InvalidArgumentException('Only the Review tier is supported.');
        }

        return [
            'framework' => 'PIR',
            'assessment_id' => $assessment->id,
            'client_company' => $assessment->client->company_name ?? null,
            'programme_name' => $assessment->target_entity ?? $assessment->name,
            'programme_type' => $assessment->name,
            'delivery_stage' => $assessment->delivery_stage,
            'primary_concern' => $assessment->client_concerns,
            'regulatory_context' => $assessment->regulatory_context,
            'assessment_date' => optional($assessment->updated_at)->toDateString(),
            'overall_score' => $this->floatOrNull($assessment->overall_score),
            'rag_status' => $assessment->rag_status,
            'pillar_scores' => $this->pillarScores($assessment),
            'pillar_names' => self::PIR_PILLAR_NAMES,
            'bri' => $this->floatOrNull($assessment->bri),
            'vri' => $this->floatOrNull($assessment->vri),
            'dmi' => $this->floatOrNull($assessment->dmi),
            'rii' => $this->floatOrNull($assessment->rii),
            'chi' => $this->floatOrNull($assessment->chi),
            'alert_flags' => $this->alertFlags($assessment),
            'question_responses' => $this->questionResponses($assessment),
            'compliance_question_scores' => $this->complianceQuestionScores($assessment),
            'tier' => 'Review',
            'consultant_name' => $assessment->assessor->name ?? null,
            'sponsor_name' => $assessment->sponsor_name,
            'interview_count' => $assessment->interview_count !== null ? (int) $assessment->interview_count : null,
            'documents_reviewed' => $this->stringList($assessment->documents_reviewed),
            'programme_value' => $this->floatOrNull($assessment->programme_value),
            'evidence_notes' => $this->evidenceNotes($assessment),
            'reporting_accuracy_risk' => (bool) $assessment->reporting_accuracy_risk,
            'reporting_accuracy_evidence' => $assessment->reporting_accuracy_evidence,
            'stakeholder_notes' => null,
        ];
    }

    private function pillarScores(Assessment $assessment): array
    {
        $scores = array_fill_keys(array_keys(self::PIR_PILLAR_NAMES), null);

        foreach ($assessment->pillarScores as $score) {
            $code = $this->pillarCode((string) $score->name);
            if ($code !== null && array_key_exists($code, $scores)) {
                $scores[$code] = $this->floatOrNull($score->score);
            }
        }

        return $scores;
    }

    private function alertFlags(Assessment $assessment): array
    {
        $flags = [];

        if ((bool) $assessment->critical_flag) {
            $flags[] = 'Assessment marked critical';
        }

        foreach ($assessment->pillarScores as $score) {
            if (! (bool) $score->critical_flag) {
                continue;
            }

            $code = $this->pillarCode((string) $score->name);
            $flags[] = $code ? "{$code} critical flag" : "{$score->name} critical flag";
        }

        return array_values(array_unique($flags));
    }

    private function questionResponses(Assessment $assessment): array
    {
        $questionBank = $this->questionBank($assessment);

        return $assessment->questionResponses
            ->map(function ($response) use ($questionBank) {
                $code = $this->questionCode((string) $response->question);
                $bankQuestion = $questionBank[$code] ?? null;

                return [
                    'id' => $code,
                    'pillar_code' => $bankQuestion?->pillar?->code ?? $this->pillarCode((string) $response->pillar_name),
                    'score' => $response->score !== null ? (int) $response->score : null,
                    'is_compliance' => (bool) ($bankQuestion?->is_compliance ?? false),
                ];
            })
            ->values()
            ->toArray();
    }

    private function complianceQuestionScores(Assessment $assessment): array
    {
        $questionBank = $this->questionBank($assessment);
        $scores = [];

        foreach ($assessment->questionResponses as $response) {
            $code = $this->questionCode((string) $response->question);
            if ((bool) ($questionBank[$code]?->is_compliance ?? false)) {
                $scores[$code] = $response->score !== null ? (int) $response->score : null;
            }
        }

        return $scores;
    }

    private function evidenceNotes(Assessment $assessment): array
    {
        return $assessment->questionResponses
            ->filter(fn ($response) => trim((string) $response->evidence_note) !== '')
            ->mapWithKeys(function ($response) {
                $source = trim((string) ($response->document_source ?: $response->source_type));
                $respondent = trim((string) $response->respondent_role);

                return [
                    $this->questionCode((string) $response->question) => [
                        'note' => trim((string) $response->evidence_note),
                        'source' => $source !== '' ? $source : null,
                        'confidence' => $this->normaliseConfidence($this->firstFilled([
                            $response->confidence_level,
                            $response->confidence,
                        ])),
                        'respondent' => $respondent !== '' ? $respondent : null,
                    ],
                ];
            })
            ->toArray();
    }

    private function questionBank(Assessment $assessment): array
    {
        $framework = AssessmentFramework::where('code', $assessment->type)->first();
        if (! $framework) {
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

    private function pillarCode(string $value): ?string
    {
        if (preg_match('/\bP{1,2}(\d{1,2})\b/', $value, $matches) === 1) {
            return 'P' . $matches[1];
        }

        return null;
    }

    private function normaliseConfidence(?string $confidence): string
    {
        return match (strtolower((string) $confidence)) {
            'high' => 'High',
            'low' => 'Low',
            default => 'Medium',
        };
    }

    private function firstFilled(array $values): ?string
    {
        foreach ($values as $value) {
            if (trim((string) $value) !== '') {
                return (string) $value;
            }
        }

        return null;
    }

    private function floatOrNull($value): ?float
    {
        return $value !== null ? (float) $value : null;
    }

    private function stringList($value): array
    {
        if (is_array($value)) {
            return array_values(array_filter(array_map('trim', $value)));
        }

        if (! is_string($value) || trim($value) === '') {
            return [];
        }

        return array_values(array_filter(array_map('trim', preg_split('/\r\n|\r|\n|,/', $value))));
    }
}
