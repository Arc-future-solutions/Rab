<?php

namespace Tests\Feature;

use App\Models\Assessment;
use App\Models\Client;
use App\Models\User;
use App\Jobs\GenerateAdminFullReport;
use App\Services\AdminFullReportGenerationService;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class AdminGenerateReportPromptTest extends TestCase
{
    use RefreshDatabase;

    public function test_generate_report_sends_active_prompt_key_and_system_prompt_to_anthropic(): void
    {
        $this->configureAnthropic();

        $draft = [
            'cover_letter' => 'Formal transmittal',
            'executive_position' => 'Recoverable with intervention',
            'final_position' => 'Proceed with controlled recovery',
        ];

        Http::fake([
            'example.test/*' => Http::response([
                'id' => 'msg_123',
                'content' => [[
                    'type' => 'text',
                    'text' => json_encode($draft),
                ]],
            ]),
        ]);

        $assessment = $this->assessment('PIR', 'Tier 2 Full');

        $this->actingAs($this->admin())
            ->post(route('admin.assessments.generateReport', $assessment))
            ->assertRedirect(route('admin.assessments.show', $assessment));

        Http::assertSent(function ($request) {
            $payload = $request->data();
            $input = json_decode($payload['messages'][0]['content'][0]['text'], true);

            return $request->url() === 'https://example.test/v1/messages'
                && $request->hasHeader('x-api-key', 'test-anthropic-key')
                && $request->hasHeader('anthropic-version', '2023-06-01')
                && $payload['model'] === 'claude-sonnet-4-5'
                && $payload['system'] === config('ai.prompts.pir_full_tier2')
                && ! isset($payload['output_config'])
                && $input['prompt_key'] === 'pir_full_tier2'
                && $input['ai_payload']['tier'] === 'Briefing'
                && $input['metadata']['assessment_id'] === 1
                && $input['metadata']['type'] === 'PIR_FULL'
                && $input['metadata']['is_full'] === true
                && isset($input['metadata']['submitted_at'])
                && ! array_key_exists('results', $input['metadata'])
                && ! array_key_exists('answers', $input['metadata']);
        });
    }

    public function test_generate_report_fails_fast_when_active_prompt_is_missing(): void
    {
        Config::set('ai.prompts.sir_full_tier1', null);

        $assessment = $this->assessment('SIR', 'Tier 1 Rapid');

        $this->withoutExceptionHandling();
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Prompt not found: sir_full_tier1');

        $this->actingAs($this->admin())
            ->post(route('admin.assessments.generateReport', $assessment));
    }

    public function test_generate_report_stores_structured_ai_output_as_draft_json(): void
    {
        $this->configureAnthropic();

        $draft = [
            'cover_letter' => 'Formal transmittal',
            'executive_position' => 'Recoverable with intervention',
            'final_position' => 'Proceed with controlled recovery',
        ];

        Http::fake([
            'example.test/*' => Http::response([
                'content' => [[
                    'type' => 'text',
                    'text' => json_encode($draft),
                ]],
            ]),
        ]);

        $assessment = $this->assessment('PIR', 'Tier 2 Full');

        $this->actingAs($this->admin())
            ->post(route('admin.assessments.generateReport', $assessment))
            ->assertRedirect(route('admin.assessments.show', $assessment))
            ->assertSessionHas('success', 'AI Report generated successfully.');

        $assessment->refresh();

        $this->assertSame($draft, $assessment->ai_draft_json);
        $this->assertSame(json_encode($draft), $assessment->ai_recommendation);
        $this->assertSame('completed', $assessment->status);
        $this->assertSame('completed', $assessment->ai_generation_status);
        $this->assertNull($assessment->ai_generation_error);
        $this->assertNotNull($assessment->ai_generation_started_at);
        $this->assertNotNull($assessment->ai_generation_completed_at);
    }

    public function test_generate_report_rejects_empty_anthropic_output(): void
    {
        $this->configureAnthropic();

        Http::fake([
            'example.test/*' => Http::response(['id' => 'msg_empty', 'content' => [['type' => 'text', 'text' => '']]]),
        ]);

        $assessment = $this->assessment('PIR', 'Tier 2 Full');

        $this->actingAs($this->admin())
            ->post(route('admin.assessments.generateReport', $assessment))
            ->assertRedirect(route('admin.assessments.show', $assessment))
            ->assertSessionHas('error', 'AI report generation failed: Anthropic response did not include output text.');

        $assessment->refresh();

        $this->assertNull($assessment->ai_draft_json);
        $this->assertNull($assessment->ai_recommendation);
        $this->assertSame('draft', $assessment->status);
        $this->assertSame('failed', $assessment->ai_generation_status);
        $this->assertSame('Anthropic response did not include output text.', $assessment->ai_generation_error);
    }

    public function test_generate_report_does_not_store_fallback_text_when_ai_response_is_invalid(): void
    {
        $this->configureAnthropic();

        Http::fake([
            'example.test/*' => Http::response(['message' => 'Upstream validation failed'], 500),
        ]);

        $assessment = $this->assessment('PIR', 'Tier 2 Full');

        $this->actingAs($this->admin())
            ->post(route('admin.assessments.generateReport', $assessment))
            ->assertRedirect(route('admin.assessments.show', $assessment))
            ->assertSessionHas('error', 'AI report generation failed: Anthropic returned HTTP 500: {"message":"Upstream validation failed"}');

        $assessment->refresh();

        $this->assertNull($assessment->ai_draft_json);
        $this->assertNull($assessment->ai_recommendation);
        $this->assertSame('draft', $assessment->status);
        $this->assertSame('failed', $assessment->ai_generation_status);
        $this->assertSame('Anthropic returned HTTP 500: {"message":"Upstream validation failed"}', $assessment->ai_generation_error);
    }

    public function test_generate_report_failure_is_persisted_and_retry_can_succeed(): void
    {
        $this->configureAnthropic();
        Log::spy();

        $draft = [
            'cover_letter' => 'Retry transmittal',
            'executive_position' => 'Retry succeeded',
            'final_position' => 'Proceed with controlled recovery',
        ];

        $calls = 0;
        Http::fake(function () use (&$calls, $draft) {
            $calls++;

            if ($calls === 1) {
                throw new ConnectionException('cURL error 28: Operation timed out after 120002 milliseconds with 0 bytes received');
            }

            return Http::response($this->streamedAnthropicResponse($draft), 200, [
                'Content-Type' => 'text/event-stream',
            ]);
        });

        $assessment = $this->assessment('PIR', 'Tier 1 Rapid');
        $job = new GenerateAdminFullReport($assessment->id);

        $assessment->forceFill([
            'ai_generation_status' => 'generating',
            'ai_generation_error' => null,
            'ai_generation_started_at' => now(),
            'ai_generation_completed_at' => null,
        ])->save();

        $job->handle(app(AdminFullReportGenerationService::class));

        $assessment->refresh();

        $this->assertSame('failed', $assessment->ai_generation_status);
        $this->assertSame('cURL error 28: Operation timed out after 120002 milliseconds with 0 bytes received', $assessment->ai_generation_error);
        $this->assertNull($assessment->ai_draft_json);
        $this->assertNull($assessment->ai_recommendation);
        $this->assertSame('draft', $assessment->status);

        $assessment->forceFill([
            'ai_generation_status' => 'generating',
            'ai_generation_error' => null,
            'ai_generation_started_at' => now(),
            'ai_generation_completed_at' => null,
        ])->save();

        $job->handle(app(AdminFullReportGenerationService::class));

        $assessment->refresh();

        $this->assertSame($draft, $assessment->ai_draft_json);
        $this->assertSame('completed', $assessment->ai_generation_status);
        $this->assertNull($assessment->ai_generation_error);
        $this->assertSame('completed', $assessment->status);

        Log::shouldHaveReceived('log')
            ->with('info', 'Queued streamed admin full-report generation completed', \Mockery::on(function (array $context) use ($assessment, $draft) {
                $json = json_encode($draft, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

                return $context['assessment_id'] === $assessment->id
                    && $context['framework'] === 'PIR'
                    && $context['type'] === 'PIR'
                    && $context['report_tier'] === 'Tier 1 Rapid'
                    && $context['model'] === 'claude-sonnet-4-5'
                    && $context['max_tokens'] === 8192
                    && $context['stream'] === true
                    && $context['received_chunk_count'] === 1
                    && $context['final_text_length'] === strlen($json)
                    && $context['ai_generation_status'] === 'completed'
                    && ! array_key_exists('prompt', $context)
                    && ! array_key_exists('payload', $context)
                    && ! array_key_exists('ai_payload', $context)
                    && ! array_key_exists('evidence_notes', $context)
                    && ! array_key_exists('response_text', $context);
            }))
            ->once();
    }

    public function test_full_report_job_failure_is_persisted_when_stream_returns_error_event(): void
    {
        $this->configureAnthropic();
        Log::spy();

        Http::fake([
            'example.test/*' => Http::response(implode("\n\n", [
                'event: message_start' . "\n" . 'data: {"type":"message_start","message":{"id":"msg_error"}}',
                'event: error' . "\n" . 'data: {"type":"error","error":{"type":"overloaded_error","message":"Upstream overloaded"}}',
                '',
            ]), 200, ['Content-Type' => 'text/event-stream']),
        ]);

        $assessment = $this->assessment('PIR', 'Tier 1 Rapid');
        $assessment->forceFill([
            'ai_generation_status' => 'generating',
            'ai_generation_error' => null,
            'ai_generation_started_at' => now(),
            'ai_generation_completed_at' => null,
        ])->save();

        (new GenerateAdminFullReport($assessment->id))->handle(app(AdminFullReportGenerationService::class));

        $assessment->refresh();

        $this->assertSame('failed', $assessment->ai_generation_status);
        $this->assertSame('Upstream overloaded', $assessment->ai_generation_error);
        $this->assertNull($assessment->ai_draft_json);
        $this->assertNull($assessment->ai_recommendation);

        Log::shouldHaveReceived('log')
            ->with('error', 'Queued streamed admin full-report generation failed', \Mockery::on(function (array $context) use ($assessment) {
                return $context['assessment_id'] === $assessment->id
                    && $context['framework'] === 'PIR'
                    && $context['type'] === 'PIR'
                    && $context['report_tier'] === 'Tier 1 Rapid'
                    && $context['model'] === 'claude-sonnet-4-5'
                    && $context['max_tokens'] === 8192
                    && $context['stream'] === true
                    && $context['received_chunk_count'] === null
                    && $context['final_text_length'] === null
                    && $context['ai_generation_status'] === 'failed'
                    && $context['failure_class'] === \RuntimeException::class
                    && $context['failure_message'] === 'Upstream overloaded'
                    && ! array_key_exists('prompt', $context)
                    && ! array_key_exists('payload', $context)
                    && ! array_key_exists('ai_payload', $context)
                    && ! array_key_exists('evidence_notes', $context)
                    && ! array_key_exists('response_text', $context);
            }))
            ->once();
    }

    public function test_full_report_job_failure_is_persisted_when_streamed_output_is_invalid_json(): void
    {
        $this->configureAnthropic();

        $existingDraft = [
            'cover_letter' => 'Existing transmittal',
            'executive_position' => 'Existing position',
            'final_position' => 'Existing final position',
        ];

        Http::fake([
            'example.test/*' => Http::response($this->streamedTextResponse('This is not JSON.'), 200, [
                'Content-Type' => 'text/event-stream',
            ]),
        ]);

        $assessment = $this->assessment('PIR', 'Tier 1 Rapid');
        $assessment->forceFill([
            'ai_draft_json' => $existingDraft,
            'ai_recommendation' => json_encode($existingDraft),
            'status' => 'completed',
            'ai_generation_status' => 'generating',
            'ai_generation_error' => null,
            'ai_generation_started_at' => now(),
            'ai_generation_completed_at' => null,
        ])->save();

        (new GenerateAdminFullReport($assessment->id))->handle(app(AdminFullReportGenerationService::class));

        $assessment->refresh();

        $this->assertSame('failed', $assessment->ai_generation_status);
        $this->assertSame('AI response was received but did not include a usable report payload.', $assessment->ai_generation_error);
        $this->assertSame($existingDraft, $assessment->ai_draft_json);
        $this->assertSame(json_encode($existingDraft), $assessment->ai_recommendation);
    }

    public function test_full_report_job_failure_does_not_overwrite_existing_successful_report_fields(): void
    {
        $this->configureAnthropic();

        $existingDraft = [
            'cover_letter' => 'Existing transmittal',
            'executive_position' => 'Existing position',
            'final_position' => 'Existing final position',
        ];

        Http::fake(fn () => throw new ConnectionException('cURL error 28: Operation timed out'));

        $assessment = $this->assessment('PIR', 'Tier 1 Rapid');
        $assessment->forceFill([
            'ai_draft_json' => $existingDraft,
            'ai_recommendation' => json_encode($existingDraft),
            'status' => 'completed',
            'ai_generation_status' => 'generating',
            'ai_generation_error' => null,
            'ai_generation_started_at' => now(),
            'ai_generation_completed_at' => null,
        ])->save();

        (new GenerateAdminFullReport($assessment->id))->handle(app(AdminFullReportGenerationService::class));

        $assessment->refresh();

        $this->assertSame('failed', $assessment->ai_generation_status);
        $this->assertSame('cURL error 28: Operation timed out', $assessment->ai_generation_error);
        $this->assertSame($existingDraft, $assessment->ai_draft_json);
        $this->assertSame(json_encode($existingDraft), $assessment->ai_recommendation);
        $this->assertSame('completed', $assessment->status);
    }

    private function configureAnthropic(): void
    {
        Config::set('services.anthropic.api_key', 'test-anthropic-key');
        Config::set('services.anthropic.base_url', 'https://example.test/v1');
        Config::set('services.anthropic.model', 'claude-sonnet-4-5');
        Config::set('services.anthropic.version', '2023-06-01');
    }

    private function streamedAnthropicResponse(array $draft): string
    {
        return $this->streamedTextResponse(json_encode($draft, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }

    private function streamedTextResponse(string $text): string
    {
        $midpoint = intdiv(strlen($text), 2);

        return implode("\n\n", [
            'event: message_start' . "\n" . 'data: {"type":"message_start","message":{"id":"msg_streamed","type":"message","role":"assistant","content":[]}}',
            'event: content_block_start' . "\n" . 'data: {"type":"content_block_start","index":0,"content_block":{"type":"text","text":""}}',
            'event: content_block_delta' . "\n" . 'data: ' . json_encode([
                'type' => 'content_block_delta',
                'index' => 0,
                'delta' => ['type' => 'text_delta', 'text' => substr($text, 0, $midpoint)],
            ]),
            'event: content_block_delta' . "\n" . 'data: ' . json_encode([
                'type' => 'content_block_delta',
                'index' => 0,
                'delta' => ['type' => 'text_delta', 'text' => substr($text, $midpoint)],
            ]),
            'event: content_block_stop' . "\n" . 'data: {"type":"content_block_stop","index":0}',
            'event: message_stop' . "\n" . 'data: {"type":"message_stop"}',
            '',
        ]);
    }

    private function admin(): User
    {
        return User::create([
            'name' => 'Admin',
            'email' => 'admin@example.com',
            'password' => 'password',
            'role' => 'admin',
        ]);
    }

    private function assessment(string $type, string $tier): Assessment
    {
        $client = Client::create([
            'company_name' => 'Acme Ltd',
            'primary_contact' => 'Alex Sponsor',
        ]);

        return Assessment::create([
            'client_id' => $client->id,
            'name' => "{$type} Assessment",
            'type' => $type,
            'report_tier' => $tier,
            'overall_score' => 3.5,
            'rag_status' => 'Amber',
            'status' => 'draft',
        ]);
    }
}
