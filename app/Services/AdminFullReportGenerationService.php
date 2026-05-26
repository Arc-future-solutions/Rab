<?php

namespace App\Services;

use App\Models\Assessment;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\File;
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

            $aiRecommendation = $data['output_raw'] ?? $data['output'] ?? $data['recommendation'] ?? null;
            $aiDraft = $this->normaliseAiDraft($aiRecommendation, $data);

            if ($aiDraft === null) {
                if ($usesStreaming) {
                    $this->logStreamedOutputDiagnostics($assessment, $data);
                }

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
            && in_array($assessment->report_tier, ['Tier 1 Rapid', 'Tier 2 Full'], true);
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
            $responseData['output_raw'] ?? null,
            $responseData['report'] ?? null,
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
            'evidence_validated_statement',
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

        foreach (['report', 'output_raw', 'output', 'recommendation', 'content', 'message', 'text'] as $key) {
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

        $decoded = $this->decodeJsonString($candidate);
        if (is_array($decoded)) {
            return $this->normaliseAiResponsePayload($decoded);
        }

        $firstDecodedPayload = null;

        foreach ($this->extractJsonObjectStrings($candidate) as $jsonObject) {
            $decoded = $this->decodeJsonString($jsonObject);
            if (! is_array($decoded)) {
                continue;
            }

            $payload = $this->normaliseAiResponsePayload($decoded);

            if ($this->structuredKeyMatches($payload) !== []) {
                return $payload;
            }

            $firstDecodedPayload ??= $payload;
        }

        return $firstDecodedPayload;
    }

    private function decodeJsonString(string $candidate): ?array
    {
        $decoded = json_decode($candidate, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            return null;
        }

        if (is_string($decoded)) {
            return $this->decodeJsonCandidate($decoded);
        }

        return is_array($decoded) ? $decoded : null;
    }

    /**
     * @return array<int, string>
     */
    private function extractJsonObjectStrings(string $value): array
    {
        $objects = [];
        $length = strlen($value);

        for ($start = 0; $start < $length; $start++) {
            if ($value[$start] !== '{') {
                continue;
            }

            $depth = 0;
            $inString = false;
            $escaped = false;

            for ($i = $start; $i < $length; $i++) {
                $char = $value[$i];

                if ($inString) {
                    if ($escaped) {
                        $escaped = false;
                        continue;
                    }

                    if ($char === '\\') {
                        $escaped = true;
                        continue;
                    }

                    if ($char === '"') {
                        $inString = false;
                    }

                    continue;
                }

                if ($char === '"') {
                    $inString = true;
                    continue;
                }

                if ($char === '{') {
                    $depth++;
                    continue;
                }

                if ($char !== '}') {
                    continue;
                }

                $depth--;

                if ($depth === 0) {
                    $objects[] = substr($value, $start, $i - $start + 1);
                    break;
                }
            }
        }

        return array_values(array_unique($objects));
    }

    private function logStreamedOutputDiagnostics(Assessment $assessment, array $responseData): void
    {
        [$source, $output] = $this->normalisationInputSource($responseData);
        if (! is_string($output)) {
            Log::warning('Streamed full-report output diagnostics', [
                'assessment_id' => $assessment->id,
                'prompt_key' => $responseData['prompt_key'] ?? null,
                'output_present' => false,
                'normalisation_input_source' => $source,
            ]);

            return;
        }

        if ((int) $assessment->id === 17 && $assessment->type === 'PIR' && $assessment->report_tier === 'Tier 2 Full') {
            $path = storage_path('app/private/debug/assessment-17-pir-tier2-stream-output.txt');
            File::ensureDirectoryExists(dirname($path));
            File::put($path, $output);
        }

        $directDecoded = $this->decodeJsonString(trim($output));
        $balancedDecoded = null;

        foreach ($this->extractJsonObjectStrings($output) as $jsonObject) {
            $decoded = $this->decodeJsonString($jsonObject);
            if (! is_array($decoded)) {
                continue;
            }

            $payload = $this->normaliseAiResponsePayload($decoded);
            if ($this->structuredKeyMatches($payload) !== []) {
                $balancedDecoded = $payload;
                break;
            }

            $balancedDecoded ??= $payload;
        }

        $parsed = is_array($directDecoded) ? $this->normaliseAiResponsePayload($directDecoded) : $balancedDecoded;
        $foundKeys = is_array($parsed) ? $this->structuredKeyMatches($parsed) : [];
        $requiredKeys = $this->requiredPirTier2ReportKeys();

        Log::warning('Streamed full-report output diagnostics', [
            'assessment_id' => $assessment->id,
            'prompt_key' => $responseData['prompt_key'] ?? null,
            'final_streamed_text_length' => $responseData['stream_metadata']['final_text_length'] ?? null,
            'normalisation_input_length' => strlen($output),
            'normalisation_input_source' => $source,
            'output_length' => strlen($output),
            'output_first_non_whitespace_char' => trim($output) !== '' ? trim($output)[0] : null,
            'output_contains_open_brace' => str_contains($output, '{'),
            'output_contains_fenced_json' => preg_match('/```(?:json)?\s*\{/i', $output) === 1,
            'json_decode_direct_success' => is_array($directDecoded),
            'balanced_json_extraction_success' => is_array($balancedDecoded),
            'parsed_top_level_keys' => is_array($parsed) ? array_keys($parsed) : [],
            'required_report_key_matches' => [
                'found' => array_values(array_intersect($requiredKeys, $foundKeys)),
                'missing' => array_values(array_diff($requiredKeys, $foundKeys)),
            ],
        ]);
    }

    /**
     * @return array{0: string|null, 1: mixed}
     */
    private function normalisationInputSource(array $responseData): array
    {
        foreach (['output_raw', 'output', 'report'] as $key) {
            if (array_key_exists($key, $responseData)) {
                return [$key, $responseData[$key]];
            }
        }

        return [null, null];
    }

    /**
     * @return array<int, string>
     */
    private function structuredKeyMatches(array $payload): array
    {
        return array_values(array_intersect(array_keys($payload), $this->structuredDraftKeys()));
    }

    /**
     * @return array<int, string>
     */
    private function requiredPirTier2ReportKeys(): array
    {
        return [
            'cover_letter',
            'executive_position',
            'intelligence_dashboard',
            'intelligence_profile',
            'stakeholder_intelligence',
            'evidence_validated_statement',
            'risk_register',
            'raid_summary',
            'root_cause_analysis',
            'priority_plan',
            'final_position',
        ];
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

        foreach (['report'] as $key) {
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
