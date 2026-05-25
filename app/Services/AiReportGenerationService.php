<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use RuntimeException;

class AiReportGenerationService
{
    public function generate(string $promptKey, string $systemPrompt, array $aiPayload, array $metadata = []): array
    {
        $apiKey = config('services.anthropic.api_key');

        if (! $apiKey) {
            throw new RuntimeException('Anthropic API key is not configured.');
        }

        $response = Http::withHeaders([
                'x-api-key' => $apiKey,
                'anthropic-version' => config('services.anthropic.version', '2023-06-01'),
            ])
            ->acceptJson()
            ->asJson()
            ->timeout((int) config('services.anthropic.timeout', 120))
            ->post($this->messagesUrl(), $this->requestPayload($promptKey, $systemPrompt, $aiPayload, $metadata));

        if ($response->failed()) {
            throw new RuntimeException(sprintf(
                'Anthropic returned HTTP %s: %s',
                $response->status(),
                Str::limit(trim($response->body()), 500)
            ));
        }

        $responseData = $response->json();
        if (! is_array($responseData)) {
            throw new RuntimeException('Anthropic returned a non-JSON response.');
        }

        $output = $this->extractOutputText($responseData);
        if (trim($output) === '') {
            throw new RuntimeException('Anthropic response did not include output text.');
        }

        $decoded = $this->decodeJsonCandidate($output);
        if (is_array($decoded)) {
            $output = json_encode($decoded, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }

        $payload = [
            'output' => $output,
            'provider_response_id' => $responseData['id'] ?? null,
            'prompt_key' => $promptKey,
        ];

        if (is_array($decoded)) {
            $payload['report'] = $decoded;
            $payload['snapshot_report_json'] = $decoded;
        }

        return $payload;
    }

    public function generateStreamed(string $promptKey, string $systemPrompt, array $aiPayload, array $metadata = []): array
    {
        $apiKey = config('services.anthropic.api_key');

        if (! $apiKey) {
            throw new RuntimeException('Anthropic API key is not configured.');
        }

        $requestPayload = $this->requestPayload($promptKey, $systemPrompt, $aiPayload, $metadata) + [
            'stream' => true,
        ];

        Log::info('Anthropic streamed report request started', [
            'prompt_key' => $promptKey,
            'model' => $requestPayload['model'],
            'max_tokens' => $requestPayload['max_tokens'],
            'stream' => true,
        ]);

        $response = Http::withHeaders([
                'x-api-key' => $apiKey,
                'anthropic-version' => config('services.anthropic.version', '2023-06-01'),
                'Accept' => 'text/event-stream',
            ])
            ->asJson()
            ->timeout((int) config('services.anthropic.timeout', 120))
            ->withOptions([
                'stream' => true,
                'read_timeout' => (int) config('services.anthropic.timeout', 120),
            ])
            ->post($this->messagesUrl(), $requestPayload);

        if ($response->failed()) {
            throw new RuntimeException(sprintf(
                'Anthropic returned HTTP %s: %s',
                $response->status(),
                Str::limit(trim($response->body()), 500)
            ));
        }

        $stream = $response->toPsrResponse()->getBody();
        $parser = new AnthropicMessagesStreamParser();
        $chunkCount = 0;

        while (! $stream->eof()) {
            $chunk = $stream->read(8192);

            if ($chunk === '') {
                break;
            }

            $chunkCount++;
            $parser->push($chunk);
        }

        $streamResult = $parser->finish();
        $output = $streamResult['text'];

        Log::info('Anthropic streamed report request completed', [
            'prompt_key' => $promptKey,
            'model' => $requestPayload['model'],
            'max_tokens' => $requestPayload['max_tokens'],
            'stream' => true,
            'received_chunk_count' => $chunkCount,
            'stream_event_count' => $streamResult['event_count'],
            'text_delta_count' => $streamResult['text_delta_count'],
            'final_text_length' => strlen($output),
        ]);

        if (trim($output) === '') {
            throw new RuntimeException('Anthropic response did not include output text.');
        }

        $decoded = $this->decodeJsonCandidate($output);
        if (is_array($decoded)) {
            $output = json_encode($decoded, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }

        $payload = [
            'output' => $output,
            'provider_response_id' => $streamResult['provider_response_id'],
            'prompt_key' => $promptKey,
            'stream' => true,
            'stream_metadata' => [
                'model' => $requestPayload['model'],
                'max_tokens' => $requestPayload['max_tokens'],
                'stream' => true,
                'received_chunk_count' => $chunkCount,
                'stream_event_count' => $streamResult['event_count'],
                'text_delta_count' => $streamResult['text_delta_count'],
                'final_text_length' => strlen($output),
            ],
        ];

        if (is_array($decoded)) {
            $payload['report'] = $decoded;
            $payload['snapshot_report_json'] = $decoded;
        }

        return $payload;
    }

    private function requestPayload(string $promptKey, string $systemPrompt, array $aiPayload, array $metadata): array
    {
        return [
            'model' => config('services.anthropic.model', 'claude-sonnet-4-5'),
            'max_tokens' => (int) config('services.anthropic.max_tokens', 8192),
            'system' => $systemPrompt,
            'messages' => [[
                'role' => 'user',
                'content' => [[
                    'type' => 'text',
                    'text' => json_encode([
                        'response_instruction' => 'Return one valid JSON object only. Do not wrap it in markdown fences. Do not include a preamble, commentary, or keys outside the requested schema.',
                        'prompt_key' => $promptKey,
                        'ai_payload' => $aiPayload,
                        'metadata' => $metadata,
                    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                ]],
            ]],
        ];
    }

    private function messagesUrl(): string
    {
        return rtrim((string) config('services.anthropic.base_url', 'https://api.anthropic.com/v1'), '/') . '/messages';
    }

    private function extractOutputText(array $responseData): string
    {
        if (isset($responseData['output_text']) && is_string($responseData['output_text'])) {
            return $responseData['output_text'];
        }

        $chunks = [];

        foreach ($responseData['content'] ?? [] as $content) {
            if (isset($content['text']) && is_string($content['text'])) {
                $chunks[] = $content['text'];
            }
        }

        foreach ($responseData['output'] ?? [] as $item) {
            foreach ($item['content'] ?? [] as $content) {
                foreach (['text', 'output_text'] as $key) {
                    if (isset($content[$key]) && is_string($content[$key])) {
                        $chunks[] = $content[$key];
                    }
                }
            }
        }

        return trim(implode("\n", $chunks));
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
}
