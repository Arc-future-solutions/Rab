<?php

namespace Tests\Feature;

use App\Models\Assessment;
use App\Models\AssessmentFramework;
use App\Models\AssessmentPillar;
use App\Models\AssessmentQuestionBank;
use App\Models\Client;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminAssessmentShowTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_assessment_show_handles_type_three_questions_without_score_anchors(): void
    {
        $admin = User::create([
            'name' => 'Admin',
            'email' => 'admin@example.com',
            'password' => 'password',
            'role' => 'admin',
        ]);

        $client = Client::create([
            'company_name' => 'Acme Ltd',
            'primary_contact' => 'Alex Sponsor',
        ]);

        $assessment = Assessment::create([
            'client_id' => $client->id,
            'name' => 'ERP Programme',
            'type' => 'PIR',
            'report_tier' => 'Tier 1 Rapid',
            'overall_score' => 0,
            'rag_status' => 'Red',
            'status' => 'draft',
        ]);

        $framework = AssessmentFramework::create([
            'code' => 'PIR',
            'name' => 'Programme Integrity Review',
        ]);

        $pillar = AssessmentPillar::create([
            'framework_id' => $framework->id,
            'code' => 'P1',
            'name' => 'Governance & Decision-Making',
            'weight' => 1,
        ]);

        AssessmentQuestionBank::create([
            'framework_id' => $framework->id,
            'pillar_id' => $pillar->id,
            'level' => 'full',
            'question_code' => 'P1.F1',
            'question_text' => 'Is decision ownership clear?',
            'question_type' => 3,
            'score_anchors' => null,
        ]);

        $this->actingAs($admin)
            ->get(route('admin.assessments.show', $assessment))
            ->assertOk()
            ->assertSee('ERP Programme');
    }
}
