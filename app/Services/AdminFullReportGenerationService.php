<?php

namespace App\Services;

use App\Models\Assessment;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class AdminFullReportGenerationService
{
    public function __construct(
        private readonly AssessmentAiPayloadBuilder $payloadBuilder,
        private readonly AiReportGenerationService $aiReportGeneration
    ) {
    }

    public function generate(Assessment $assessment, bool $markGenerating = true): bool
    {
        $assessment->load(['questionResponses', 'pillarScores', 'client', 'assessor']);

        $aiPayload = $this->payloadBuilder->buildFullPayload($assessment);
        $promptKey = $this->payloadBuilder->promptKey($assessment);
        $systemPrompt = config("ai.prompts.{$promptKey}");

        if (! $systemPrompt) {
            throw new RuntimeException("Prompt not found: {$promptKey}");
        }

        if ($markGenerating) {
            $this->markGenerating($assessment);
        }

        try {
            $metadata = [
                'type' => strtoupper($assessment->type) . '_FULL',
                'is_full' => true,
                'assessment_id' => $assessment->id,
                'submitted_at' => now()->toDateTimeString(),
            ];

            $usesStreaming = $this->shouldUseStreaming($assessment);

            $data = $usesStreaming
                ? $this->aiReportGeneration->generateStreamed($promptKey, $systemPrompt, $aiPayload, $metadata)
                : $this->aiReportGeneration->generate($promptKey, $systemPrompt, $aiPayload, $metadata);

            $aiRecommendation = $data['output'] ?? $data['recommendation'] ?? null;
            $aiDraft = $this->normaliseAiDraft($aiRecommendation, $data);

            if ($aiDraft === null) {
                Log::warning('Full assessment AI response missing usable content', [
                    'assessment_id' => $assessment->id,
                    'response_keys' => array_keys($data),
                ]);

                $this->markFailed($assessment, 'AI response was received but did not include a usable report payload.');

                if ($usesStreaming) {
                    $this->logStreamedGeneration(
                        $assessment,
                        'warning',
                        'Queued streamed admin full-report generation failed',
                        'failed',
                        $data,
                        RuntimeException::class,
                        'AI response was received but did not include a usable report payload.'
                    );
                }

                return false;
            }

            $topRisks = $data['top_5_risks'] ?? null;
            if ($topRisks && is_array($topRisks)) {
                $topRisks = json_encode($topRisks);
            }

            $assessment->forceFill([
                'ai_recommendation' => $aiRecommendation,
                'ai_draft_json' => $aiDraft,
                'top_5_risks' => $topRisks,
                'status' => 'completed',
                'ai_generation_status' => 'completed',
                'ai_generation_error' => null,
                'ai_generation_completed_at' => now(),
            ])->save();

            if ($usesStreaming) {
                $this->logStreamedGeneration(
                    $assessment,
                    'info',
                    'Queued streamed admin full-report generation completed',
                    'completed',
                    $data
                );
            }

            return true;
        } catch (\Throwable $e) {
            Log::error('Full assessment AI generation failed', [
                'assessment_id' => $assessment->id,
                'message' => $e->getMessage(),
            ]);

            $this->markFailed($assessment, $e->getMessage());

            if ($this->shouldUseStreaming($assessment)) {
                $this->logStreamedGeneration(
                    $assessment,
                    'error',
                    'Queued streamed admin full-report generation failed',
                    'failed',
                    [],
                    $e::class,
                    $e->getMessage()
                );
            }

            return false;
        }
    }

    public function markGenerating(Assessment $assessment): void
    {
        $assessment->forceFill([
            'ai_generation_status' => 'generating',
            'ai_generation_error' => null,
            'ai_generation_started_at' => now(),
            'ai_generation_completed_at' => null,
        ])->save();
    }

    private function markFailed(Assessment $assessment, string $message): void
    {
        $assessment->forceFill([
            'ai_generation_status' => 'failed',
            'ai_generation_error' => $message,
            'ai_generation_completed_at' => now(),
        ])->save();
    }

    private function shouldUseStreaming(Assessment $assessment): bool
    {
        return strtoupper((string) $assessment->type) === 'PIR'
            && $assessment->report_tier === 'Tier 1 Rapid';
    }

    private function logStreamedGeneration(
        Assessment $assessment,
        string $level,
        string $message,
        string $status,
        array $responseData = [],
        ?string $failureClass = null,
        ?string $failureMessage = null
    ): void {
        $streamMetadata = is_array($responseData['stream_metadata'] ?? null)
            ? $responseData['stream_metadata']
            : [];

        $context = [
            'assessment_id' => $assessment->id,
            'framework' => $assessment->type,
            'type' => $assessment->type,
            'report_tier' => $assessment->report_tier,
            'model' => $streamMetadata['model'] ?? config('services.anthropic.model'),
            'max_tokens' => $streamMetadata['max_tokens'] ?? (int) config('services.anthropic.max_tokens', 8192),
            'stream' => true,
            'received_chunk_count' => $streamMetadata['received_chunk_count'] ?? null,
            'final_text_length' => $streamMetadata['final_text_length'] ?? null,
            'ai_generation_status' => $status,
        ];

        if ($failureClass !== null) {
            $context['failure_class'] = $failureClass;
            $context['failure_message'] = $failureMessage;
        }

        Log::log($level, $message, $context);
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

    private function structuredDraftKeys(): array
    {
        return [
            'cover_letter',
            'executive_position',
            'intelligence_dashboard',
            'stakeholder_intelligence',
            'intelligence_profile',
            'reporting_accuracy_risk_finding',
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
            ? $this->normaliseAiResponsePayload($decoded)
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
