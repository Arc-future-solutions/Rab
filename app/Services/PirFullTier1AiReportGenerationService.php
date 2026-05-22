<?php

namespace App\Services;

use App\Models\Assessment;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class PirFullTier1AiReportGenerationService
{
    private const PROMPT_KEY = 'pir_full_tier1';

    private const PILLAR_CODES = [
        'P1',
        'P2',
        'P3',
        'P4',
        'P5',
        'P6',
        'P7',
        'P8',
        'P9',
        'P10',
    ];

    public function __construct(
        private readonly FullReportAiPayloadBuilder $payloadBuilder
    ) {
    }

    public function prepare(int $assessmentId): array
    {
        $assessment = Assessment::query()
            ->with(['client', 'assessor', 'pillarScores', 'questionResponses'])
            ->find($assessmentId);

        if (! $assessment) {
            throw new RuntimeException("Assessment {$assessmentId} was not found.");
        }

        $payload = $this->payloadBuilder->buildFromAssessment($assessment, 'Review');
        $errors = $this->validatePayload($payload);

        if ($errors !== []) {
            $this->storeGenerationState($assessment, 'validation_failed', $payload, $errors);

            throw new RuntimeException(
                'PIR Full Tier 1 AI generation validation failed: ' . implode('; ', $errors)
            );
        }

        $state = $this->storeGenerationState($assessment, 'generation_prepared', $payload);

        Log::info('PIR Full Tier 1 AI generation prepared.', [
            'assessment_id' => $assessment->id,
            'prompt_key' => self::PROMPT_KEY,
            'evidence_notes_count' => $state['evidence_notes_count'],
            'question_response_count' => $state['question_response_count'],
        ]);

        return $state;
    }

    public function validatePayload(array $payload): array
    {
        $errors = [];

        if (($payload['framework'] ?? null) !== 'PIR') {
            $errors[] = 'framework must be PIR';
        }

        if (($payload['tier'] ?? null) !== 'Review') {
            $errors[] = 'tier must be Review';
        }

        if (! array_key_exists('overall_score', $payload) || $payload['overall_score'] === null) {
            $errors[] = 'overall_score is required';
        }

        if (trim((string) ($payload['rag_status'] ?? '')) === '') {
            $errors[] = 'rag_status is required';
        }

        foreach (self::PILLAR_CODES as $code) {
            if (! array_key_exists($code, $payload['pillar_scores'] ?? []) || $payload['pillar_scores'][$code] === null) {
                $errors[] = "pillar_scores.{$code} is required";
            }
        }

        foreach (['bri', 'vri', 'dmi', 'rii', 'chi'] as $index) {
            if (! array_key_exists($index, $payload) || $payload[$index] === null) {
                $errors[] = "{$index} is required";
            }
        }

        if (count($payload['evidence_notes'] ?? []) < 10) {
            $errors[] = 'evidence_notes must contain at least 10 entries';
        }

        if (count($payload['question_responses'] ?? []) === 0) {
            $errors[] = 'question_responses are required';
        }

        foreach ($payload['question_responses'] ?? [] as $index => $response) {
            if (! is_array($response) || array_keys($response) !== ['id', 'pillar_code', 'score', 'is_compliance']) {
                $errors[] = "question_responses.{$index} must be minimal";
                break;
            }
        }

        if (! array_key_exists('stakeholder_notes', $payload) || $payload['stakeholder_notes'] !== null) {
            $errors[] = 'stakeholder_notes must be null for Tier 1';
        }

        return $errors;
    }

    private function storeGenerationState(
        Assessment $assessment,
        string $status,
        array $payload,
        array $errors = []
    ): array {
        $state = [
            'status' => $status,
            'prompt_key' => self::PROMPT_KEY,
            'assessment_id' => $assessment->id,
            'framework' => $payload['framework'] ?? null,
            'tier' => $payload['tier'] ?? null,
            'evidence_notes_count' => count($payload['evidence_notes'] ?? []),
            'question_response_count' => count($payload['question_responses'] ?? []),
            'p1_p10_mapping_complete' => $this->p1ToP10MappingComplete($payload),
            'indices_present' => $this->indicesPresent($payload),
            'prepared_at' => now()->toIso8601String(),
            'errors' => $errors,
        ];

        $draft = $assessment->ai_draft_json ?? [];
        $draft['pir_full_tier1_generation'] = $state;
        $assessment->forceFill(['ai_draft_json' => $draft])->save();

        return $state;
    }

    private function p1ToP10MappingComplete(array $payload): bool
    {
        foreach (self::PILLAR_CODES as $code) {
            if (! array_key_exists($code, $payload['pillar_scores'] ?? []) || $payload['pillar_scores'][$code] === null) {
                return false;
            }
        }

        return true;
    }

    private function indicesPresent(array $payload): array
    {
        $indices = [];

        foreach (['bri', 'vri', 'dmi', 'rii', 'chi'] as $index) {
            $indices[$index] = array_key_exists($index, $payload) && $payload[$index] !== null;
        }

        return $indices;
    }
}
