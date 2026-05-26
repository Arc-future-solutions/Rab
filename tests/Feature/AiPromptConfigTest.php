<?php

namespace Tests\Feature;

use App\Models\Assessment;
use App\Models\Client;
use App\Services\AssessmentAiPayloadBuilder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AiPromptConfigTest extends TestCase
{
    use RefreshDatabase;

    public function test_active_ai_prompts_match_build_guide_verbatim(): void
    {
        $guide = file_get_contents(base_path('docs/RAB_Developer_Final_Build_Guide_v5.md'));
        $prompts = config('ai.prompts');

        foreach ($this->activePromptKeys() as $key) {
            $this->assertArrayHasKey($key, $prompts);
            $this->assertSame($this->guidePrompt($guide, $key), $prompts[$key]);
        }
    }

    public function test_deprecated_prompt_keys_are_not_available_for_new_generation(): void
    {
        $prompts = config('ai.prompts');

        foreach (['full_report', 'pir_full_report', 'sir_full_report', 'pir_regulatory', 'sir_regulatory'] as $key) {
            $this->assertArrayNotHasKey($key, $prompts);
        }
    }

    public function test_pir_full_tier2_prompt_defines_canonical_briefing_schema_contract(): void
    {
        $prompt = config('ai.prompts.pir_full_tier2');

        foreach ($this->canonicalPirTier2Keys() as $key) {
            $this->assertStringContainsString("\"{$key}\"", $prompt);
        }

        $this->assertStringContainsString('Do not use alternate top-level keys such as opening, overall_position, key_themes,', $prompt);

        foreach (['opening', 'overall_position', 'key_themes', 'instruction_to_sponsor'] as $alternateKey) {
            $this->assertStringContainsString($alternateKey, $prompt);
        }

        $this->assertStringContainsString('Do not include tier1_bridge for Tier 2.', $prompt);
        $this->assertStringContainsString('Return JSON only.', $prompt);
        $this->assertStringContainsString('Use exactly these top-level keys and no others', $prompt);
        $this->assertStringContainsString('Treat this report tier as Briefing.', $prompt);
        $this->assertStringContainsString('Include stakeholder_intelligence.', $prompt);
        $this->assertStringContainsString('Include evidence_validated_statement.', $prompt);
    }

    public function test_full_report_prompt_key_selection_uses_active_prompt_keys(): void
    {
        $client = Client::create([
            'company_name' => 'Prompt Co',
            'primary_contact' => 'Prompt Owner',
        ]);

        $builder = new AssessmentAiPayloadBuilder();

        $cases = [
            ['PIR', 'Tier 1 Rapid', 'pir_full_tier1'],
            ['PIR', 'Tier 2 Full', 'pir_full_tier2'],
            ['SIR', 'Tier 1 Rapid', 'sir_full_tier1'],
            ['SIR', 'Tier 2 Full', 'sir_full_tier2'],
        ];

        foreach ($cases as [$type, $tier, $expectedKey]) {
            $assessment = Assessment::create([
                'client_id' => $client->id,
                'name' => "{$type} {$tier}",
                'type' => $type,
                'report_tier' => $tier,
                'overall_score' => 3.5,
                'rag_status' => 'Amber',
                'status' => 'draft',
            ]);

            $this->assertSame($expectedKey, $builder->promptKey($assessment));
            $this->assertNotEmpty(config("ai.prompts.{$expectedKey}"));
        }
    }

    private function activePromptKeys(): array
    {
        return [
            'pir_snapshot',
            'sir_snapshot',
            'pir_full_tier1',
            'pir_full_tier2',
            'sir_full_tier1',
            'sir_full_tier2',
        ];
    }

    private function canonicalPirTier2Keys(): array
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
            'final_position',
            'evidence_validated_statement',
            'compliance_risk_signals',
        ];
    }

    private function guidePrompt(string $guide, string $key): string
    {
        $pattern = "/'" . preg_quote($key, '/') . "'\s*=>\s*<<<'PROMPT'\R(.*?)\RPROMPT,/s";

        $this->assertMatchesRegularExpression($pattern, $guide, "Prompt {$key} missing from build guide.");
        preg_match($pattern, $guide, $matches);

        return $matches[1];
    }
}
