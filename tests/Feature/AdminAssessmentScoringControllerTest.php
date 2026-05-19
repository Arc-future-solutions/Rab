<?php

namespace Tests\Feature;

use App\Models\Assessment;
use App\Models\AssessmentFramework;
use App\Models\AssessmentPillar;
use App\Models\Client;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminAssessmentScoringControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_scoring_groups_similar_pillar_codes_exactly(): void
    {
        $admin = User::create([
            'name' => 'Admin',
            'email' => 'admin@example.com',
            'password' => 'password',
            'role' => 'admin',
        ]);

        $assessment = $this->assessment('SIR');
        $this->seedSirPillars();

        foreach ([
            'D1' => 3,
            'D10' => 2,
            'D11' => 4,
            'D12' => 5,
        ] as $code => $score) {
            $this->actingAs($admin)
                ->putJson(route('admin.assessments.autosave', $assessment), [
                    'question_code' => "{$code}.F1",
                    'pillar_name' => "{$code} — Test Pillar {$code}",
                    'question' => "Question {$code}",
                    'score' => $score,
                    'evidence_note' => "Evidence {$code}",
                    'confidence' => 'high',
                ])
                ->assertOk();
        }

        $scores = $assessment->pillarScores()
            ->pluck('score', 'name')
            ->mapWithKeys(fn ($score, $name) => [explode(' — ', $name)[0] => (float) $score])
            ->all();

        $this->assertSame(3.0, $scores['D1']);
        $this->assertSame(2.0, $scores['D10']);
        $this->assertSame(4.0, $scores['D11']);
        $this->assertSame(5.0, $scores['D12']);
    }

    private function assessment(string $type): Assessment
    {
        $client = Client::create([
            'company_name' => 'Acme Ltd',
            'primary_contact' => 'Alex Sponsor',
        ]);

        return Assessment::create([
            'client_id' => $client->id,
            'name' => "{$type} Controlled Assessment",
            'type' => $type,
            'report_tier' => 'Tier 2 Full',
            'overall_score' => 0,
            'rag_status' => 'Red',
            'status' => 'draft',
        ]);
    }

    private function seedSirPillars(): void
    {
        $framework = AssessmentFramework::create([
            'code' => 'SIR',
            'name' => 'Service Implementation Review',
        ]);

        foreach ([
            'D1' => 1.4,
            'D2' => 1.4,
            'D3' => 1.0,
            'D4' => 1.2,
            'D5' => 1.3,
            'D6' => 1.2,
            'D7' => 1.2,
            'D8' => 1.2,
            'D9' => 1.0,
            'D10' => 1.3,
            'D11' => 1.2,
            'D12' => 1.1,
        ] as $code => $weight) {
            AssessmentPillar::create([
                'framework_id' => $framework->id,
                'code' => $code,
                'name' => "Test Pillar {$code}",
                'weight' => $weight,
                'display_order' => (int) filter_var($code, FILTER_SANITIZE_NUMBER_INT),
            ]);
        }
    }
}
