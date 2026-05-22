<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class ReportService
{
    private const MAX_TOKENS = [
        'pir_snapshot' => 2000,
        'sir_snapshot' => 2000,
        'pir_full_tier1' => 16000,
        'sir_full_tier1' => 16000,
        'pir_full_tier2' => 16000,
        'sir_full_tier2' => 16000,
    ];

    public function generate(string $promptKey, array $payload): array
    {
        $prompt = config('ai.' . $promptKey);
        $model = 'claude-sonnet-4-6';
        $headers = [
            'x-api-key' => config('services.anthropic.key'),
            'anthropic-version' => '2023-06-01',
            'content-type' => 'application/json',
        ];

        if (! is_string($prompt) || trim($prompt) === '') {
            throw new RuntimeException("Prompt not found: {$promptKey}");
        }

        Log::info('Anthropic report request', [
            'prompt_key' => $promptKey,
            'model' => $model,
            'anthropic_version' => $headers['anthropic-version'],
        ]);

        $response = Http::withHeaders($headers)->post('https://api.anthropic.com/v1/messages', [
            'model' => $model,
            'max_tokens' => self::MAX_TOKENS[$promptKey] ?? 16000,
            'system' => $prompt,
            'messages' => [
                [
                    'role' => 'user',
                    'content' => "Analyse the following payload and return the JSON output as instructed.\n\n" . json_encode($payload),
                ],
            ],
        ]);

        if ($response->failed()) {
            throw new RuntimeException("Anthropic request failed with HTTP {$response->status()}: {$response->body()}");
        }

        $raw = $this->extractText($response->json() ?? []);
        $clean = $this->stripJsonFences($raw);
        $decoded = json_decode($clean, true);

        if (! is_array($decoded)) {
            throw new RuntimeException('AI response was not valid JSON.');
        }

        return $decoded;
    }

    private function extractText(array $response): string
    {
        $chunks = [];

        foreach ($response['content'] ?? [] as $content) {
            if (is_array($content) && isset($content['text']) && is_string($content['text'])) {
                $chunks[] = $content['text'];
            }
        }

        return trim(implode("\n", $chunks));
    }

    private function stripJsonFences(string $value): string
    {
        $clean = trim($value);

        if (preg_match('/^```(?:json)?\s*(.*?)\s*```$/s', $clean, $matches)) {
            return trim($matches[1]);
        }

        return $clean;
    }
}
