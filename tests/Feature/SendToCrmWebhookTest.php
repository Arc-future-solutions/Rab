<?php

namespace Tests\Feature;

use App\Jobs\SendToCrmWebhook;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class SendToCrmWebhookTest extends TestCase
{
    use RefreshDatabase;

    public function test_crm_webhook_is_signed_with_hmac_sha256(): void
    {
        Config::set('services.crm.webhook_url', 'https://crm.example.test/api/webhooks/crm');
        Config::set('services.crm.webhook_secret', 'top-secret');

        Http::fake([
            'crm.example.test/*' => Http::response(['status' => 'ok']),
        ]);

        $payload = [
            'assessmentId' => 42,
            'assessmentType' => 'PIR',
            'leadPriority' => 'High',
        ];

        (new SendToCrmWebhook($payload))->handle();

        Http::assertSent(function ($request) use ($payload) {
            $body = json_encode($payload, JSON_THROW_ON_ERROR);
            $signature = hash_hmac('sha256', $body, 'top-secret');

            return $request->url() === 'https://crm.example.test/api/webhooks/crm'
                && $request->body() === $body
                && (($request->header('X-RAB-Signature')[0] ?? null) === $signature);
        });
    }
}
