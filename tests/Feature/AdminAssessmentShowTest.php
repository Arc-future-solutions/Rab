<?php

namespace Tests\Feature;

use App\Models\Assessment;
use App\Models\AssessmentFramework;
use App\Models\AssessmentPillar;
use App\Models\AssessmentQuestionBank;
use App\Models\Client;
use App\Models\Lead;
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

    public function test_admin_public_snapshot_assessment_show_renders_snapshot_ai_controls_and_cards(): void
    {
        $admin = User::create([
            'name' => 'Admin',
            'email' => 'admin-snapshot@example.com',
            'password' => 'password',
            'role' => 'admin',
        ]);

        $lead = Lead::create([
            'name' => 'Casey Sponsor',
            'company' => 'Snapshot Client Ltd',
            'email' => 'casey@example.com',
            'type' => 'PIR',
            'assessment_type' => 'PIR_SNAPSHOT',
            'lead_status' => 'Warm',
            'overall_score' => 3.0,
            'rag_status' => 'Amber',
            'priority' => 'Medium',
            'source' => 'Assessment',
            'booking_token' => 'snapshot-token',
            'index_scores_json' => ['BRI' => 3.0, 'VRI' => 3.0, 'DMI' => 3.0, 'CHI' => 3.0],
            'answers_json' => ['P1.S1' => 3, 'P1.S2' => 3],
            'snapshot_report_json' => [
                'intelligence_brief' => "First intelligence paragraph.\n\nSecond intelligence paragraph.",
                'insight_cards' => [
                    [
                        'pillar_code' => 'P5',
                        'pillar_name' => 'P5 - Data Readiness',
                        'score' => 3,
                        'rag' => 'Amber',
                        'finding' => 'Data finding prose.',
                        'action' => 'Data action prose.',
                    ],
                    [
                        'pillar_code' => 'P6',
                        'pillar_name' => 'P6 - Solution Fit',
                        'score' => 3,
                        'rag' => 'Amber',
                        'finding' => 'Solution finding prose.',
                        'action' => 'Solution action prose.',
                    ],
                    [
                        'pillar_code' => 'COMPLIANCE',
                        'pillar_name' => 'Compliance Risk Signal',
                        'score' => 3,
                        'rag' => 'Amber',
                        'finding' => 'Compliance finding prose.',
                        'action' => 'Compliance action prose.',
                    ],
                ],
            ],
        ]);

        $this->actingAs($admin)
            ->get(route('admin.assessments.show', $lead->id))
            ->assertOk()
            ->assertSee('Regenerate AI Insights')
            ->assertSee('Download PDF Report')
            ->assertSee(route('rapid-consulting.snapshot-report.pdf', ['lead' => $lead->id, 'token' => 'snapshot-token']), false)
            ->assertSee('AI Consulting Insights')
            ->assertSee('Intelligence Brief')
            ->assertSee('First intelligence paragraph.')
            ->assertSee('Second intelligence paragraph.')
            ->assertSee('Insight Cards')
            ->assertSee('P5 - Data Readiness')
            ->assertSee('3.0')
            ->assertSee('Data finding prose.')
            ->assertSee('Data action prose.')
            ->assertSee('Compliance Risk Signal')
            ->assertSee('border-blue-400')
            ->assertSee('AI insights last generated:');
    }
}
