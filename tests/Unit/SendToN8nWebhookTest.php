<?php

namespace Tests\Unit;

use App\Jobs\SendToN8nWebhook;
use App\Models\Lead;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class SendToN8nWebhookTest extends TestCase
{
    use RefreshDatabase;

    public function test_legacy_snapshot_job_generates_and_stores_report_with_internal_ai_service(): void
    {
        Config::set('services.anthropic.api_key', 'test-anthropic-key');
        Config::set('services.anthropic.base_url', 'https://example.test/v1');

        $lead = Lead::create([
            'name' => 'Alex Sponsor',
            'company' => 'Acme Ltd',
            'email' => 'alex@example.com',
            'type' => 'PIR',
            'assessment_type' => 'PIR_SNAPSHOT',
            'overall_score' => 2.8,
            'rag_status' => 'Amber',
            'priority' => 'High',
            'lead_status' => 'Hot',
            'booking_status' => 'NotBooked',
            'consent_given' => true,
        ]);

        $report = [
            'intelligence_brief' => "Paragraph one.\n\nParagraph two.",
            'insight_cards' => [[
                'pillar_code' => 'P1',
                'pillar_name' => 'P1 - Governance',
                'score' => 2.2,
                'rag' => 'Red',
                'finding' => 'Finding',
                'action' => 'Action',
            ]],
        ];

        Http::fake([
            'example.test/*' => Http::response([
                'id' => 'msg_123',
                'content' => [[
                    'type' => 'text',
                    'text' => json_encode($report),
                ]],
            ]),
        ]);

        $job = new SendToN8nWebhook([
            'lead_id' => $lead->id,
            'prompt_key' => 'pir_snapshot',
            'system_prompt' => 'Return JSON.',
            'ai_payload' => ['framework' => 'PIR'],
            'type' => 'PIR_SNAPSHOT',
        ]);

        app()->call([$job, 'handle']);

        $lead->refresh();

        $this->assertSame($report, $lead->snapshot_report_json);
        $this->assertSame(json_encode($report), $lead->ai_recommendation);

        Http::assertSent(function ($request) {
            $payload = $request->data();
            $input = json_decode($payload['messages'][0]['content'][0]['text'], true);

            return $request->url() === 'https://example.test/v1/messages'
                && $request->hasHeader('x-api-key', 'test-anthropic-key')
                && $input['prompt_key'] === 'pir_snapshot'
                && $input['ai_payload']['framework'] === 'PIR';
        });
    }
}
