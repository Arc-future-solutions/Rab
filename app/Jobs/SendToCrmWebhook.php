<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SendToCrmWebhook implements ShouldQueue
{
    use Queueable;

    public int $tries = 4;

    public function __construct(public array $payload)
    {
    }

    public function backoff(): array
    {
        return [1, 3, 9];
    }

    public function handle(): void
    {
        $url = config('services.crm.webhook_url');
        $secret = config('services.crm.webhook_secret');

        if (!$url) {
            Log::warning('CRM Webhook URL not configured. Skipping webhook delivery.');
            return;
        }

        if (!$secret) {
            Log::warning('CRM Webhook secret not configured. Skipping webhook delivery.');
            return;
        }

        $body = json_encode($this->payload, JSON_THROW_ON_ERROR);
        $signature = hash_hmac('sha256', $body, $secret);

        $response = Http::withHeaders([
            'X-RAB-Signature' => $signature,
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
        ])->withBody($body, 'application/json')->post($url);

        if ($response->failed()) {
            Log::error('CRM webhook delivery failed', [
                'status' => $response->status(),
                'body' => $response->body(),
                'assessment_id' => $this->payload['assessmentId'] ?? null,
            ]);

            $response->throw();
        }
    }
}
