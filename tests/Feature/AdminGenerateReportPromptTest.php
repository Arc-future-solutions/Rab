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

    public function test_generate_report_sends_active_prompt_key_and_system_prompt(): void
    {
        Http::fake([
            'n8n.srv1139767.hstgr.cloud/*' => Http::response([
                'output' => 'Generated report text',
                'top_5_risks' => ['Risk one'],
            ]),
        ]);

        $assessment = $this->assessment('PIR', 'Tier 2 Full');

        $this->actingAs($this->admin())
            ->post(route('admin.assessments.generateReport', $assessment))
            ->assertRedirect(route('admin.assessments.show', $assessment));

        Http::assertSent(function ($request) {
            $payload = $request->data();

            return $payload['prompt_key'] === 'pir_full_tier2'
                && $payload['system_prompt'] === config('ai.prompts.pir_full_tier2')
                && $payload['ai_payload']['tier'] === 'Briefing'
                && $request->hasHeader('anthropic-beta', 'zdr-2024-10-23');
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
        $draft = [
            'cover_letter' => 'Formal transmittal',
            'executive_position' => 'Recoverable with intervention',
            'final_position' => 'Proceed with controlled recovery',
        ];

        Http::fake([
            'n8n.srv1139767.hstgr.cloud/*' => Http::response([
                'output' => json_encode($draft),
                'top_5_risks' => ['Risk one'],
            ]),
        ]);

        $assessment = $this->assessment('PIR', 'Tier 2 Full');

        $this->actingAs($this->admin())
            ->post(route('admin.assessments.generateReport', $assessment))
            ->assertRedirect(route('admin.assessments.show', $assessment));

        $assessment->refresh();

        $this->assertSame($draft, $assessment->ai_draft_json);
        $this->assertSame('["Risk one"]', $assessment->top_5_risks);
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
