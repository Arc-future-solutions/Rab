<?php

namespace Tests\Feature;

use App\Models\Assessment;
use App\Models\Client;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
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
                && $input['metadata']['assessment_id'] === 1;
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
    }

    private function configureAnthropic(): void
    {
        Config::set('services.anthropic.api_key', 'test-anthropic-key');
        Config::set('services.anthropic.base_url', 'https://example.test/v1');
        Config::set('services.anthropic.model', 'claude-sonnet-4-5');
        Config::set('services.anthropic.version', '2023-06-01');
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
