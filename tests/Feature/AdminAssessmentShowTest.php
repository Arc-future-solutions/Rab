<?php

namespace Tests\Feature;

use App\Models\Assessment;
use App\Models\AssessmentFramework;
use App\Models\AssessmentPillar;
use App\Models\AssessmentQuestionBank;
use App\Models\Client;
use App\Models\Lead;
use App\Models\User;
use App\Jobs\GenerateAdminFullReport;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
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

    public function test_formal_pir_tier1_show_renders_full_report_generate_action_not_snapshot_regenerate_action(): void
    {
        $admin = User::create([
            'name' => 'Admin',
            'email' => 'admin-formal-show@example.com',
            'password' => 'password',
            'role' => 'admin',
        ]);

        $lead = $this->snapshotLead();
        $assessment = $this->formalPirTier1Assessment($lead);

        $this->actingAs($admin)
            ->get(route('admin.assessments.show', $assessment))
            ->assertOk()
            ->assertSee('Generate AI Report')
            ->assertSee(route('admin.assessments.generateReport', $assessment), false)
            ->assertDontSee(route('admin.assessments.regenerateSnapshotAi', $assessment->id), false)
            ->assertDontSee(route('admin.assessments.regenerateSnapshotAi', $lead->id), false)
            ->assertDontSee('AI insights can only be regenerated for PIR or SIR snapshot leads.');
    }

    public function test_formal_completed_pir_tier1_with_ai_draft_shows_regenerate_ai_report_action(): void
    {
        $admin = User::create([
            'name' => 'Admin',
            'email' => 'admin-formal-tier1-regenerate@example.com',
            'password' => 'password',
            'role' => 'admin',
        ]);

        $lead = $this->snapshotLead(['email' => 'tier1-regenerate@example.com']);
        $assessment = $this->formalPirTier1Assessment($lead);
        $assessment->forceFill([
            'status' => 'completed',
            'ai_generation_status' => 'completed',
            'ai_draft_json' => $this->structuredPirTier1Draft(),
            'ai_recommendation' => json_encode($this->structuredPirTier1Draft()),
        ])->save();

        $this->actingAs($admin)
            ->get(route('admin.assessments.show', $assessment))
            ->assertOk()
            ->assertSee('Regenerate AI Report')
            ->assertSee(route('admin.assessments.generateReport', $assessment), false)
            ->assertDontSee(route('admin.assessments.regenerateSnapshotAi', $assessment->id), false)
            ->assertDontSee(route('admin.assessments.regenerateSnapshotAi', $lead->id), false);
    }

    public function test_formal_completed_pir_tier2_with_ai_draft_shows_regenerate_ai_report_action(): void
    {
        $admin = User::create([
            'name' => 'Admin',
            'email' => 'admin-formal-tier2-regenerate@example.com',
            'password' => 'password',
            'role' => 'admin',
        ]);

        $lead = $this->snapshotLead(['email' => 'tier2-regenerate@example.com']);
        $assessment = $this->formalPirTier1Assessment($lead);
        $assessment->forceFill([
            'report_tier' => 'Tier 2 Full',
            'status' => 'completed',
            'ai_generation_status' => 'completed',
            'ai_draft_json' => $this->structuredPirTier1Draft(),
            'ai_recommendation' => json_encode($this->structuredPirTier1Draft()),
        ])->save();

        $this->actingAs($admin)
            ->get(route('admin.assessments.show', $assessment))
            ->assertOk()
            ->assertSee('Regenerate AI Report')
            ->assertSee(route('admin.assessments.generateReport', $assessment), false)
            ->assertDontSee(route('admin.assessments.regenerateSnapshotAi', $assessment->id), false)
            ->assertDontSee(route('admin.assessments.regenerateSnapshotAi', $lead->id), false);
    }

    public function test_formal_failed_assessment_shows_retry_ai_report_generation_action(): void
    {
        $admin = User::create([
            'name' => 'Admin',
            'email' => 'admin-formal-retry@example.com',
            'password' => 'password',
            'role' => 'admin',
        ]);

        $lead = $this->snapshotLead(['email' => 'formal-retry@example.com']);
        $assessment = $this->formalPirTier1Assessment($lead);
        $assessment->forceFill([
            'ai_generation_status' => 'failed',
            'ai_generation_error' => 'Claude timed out.',
        ])->save();

        $this->actingAs($admin)
            ->get(route('admin.assessments.show', $assessment))
            ->assertOk()
            ->assertSee('Retry AI Report Generation')
            ->assertSee(route('admin.assessments.generateReport', $assessment), false)
            ->assertDontSee(route('admin.assessments.regenerateSnapshotAi', $assessment->id), false)
            ->assertDontSee(route('admin.assessments.regenerateSnapshotAi', $lead->id), false);
    }

    public function test_formal_generating_assessment_shows_disabled_generating_action(): void
    {
        $admin = User::create([
            'name' => 'Admin',
            'email' => 'admin-formal-generating@example.com',
            'password' => 'password',
            'role' => 'admin',
        ]);

        $lead = $this->snapshotLead(['email' => 'formal-generating@example.com']);
        $assessment = $this->formalPirTier1Assessment($lead);
        $assessment->forceFill([
            'ai_generation_status' => 'generating',
        ])->save();

        $response = $this->actingAs($admin)
            ->get(route('admin.assessments.show', $assessment))
            ->assertOk()
            ->assertSee('Generating...')
            ->assertSee(route('admin.assessments.generateReport', $assessment), false)
            ->assertDontSee(route('admin.assessments.regenerateSnapshotAi', $assessment->id), false)
            ->assertDontSee(route('admin.assessments.regenerateSnapshotAi', $lead->id), false);

        $this->assertStringContainsString('disabled', $response->getContent());
    }

    public function test_formal_pir_tier1_generate_action_uses_full_report_generation_route(): void
    {
        Config::set('services.anthropic.api_key', 'test-key');
        Config::set('services.anthropic.base_url', 'https://example.test/v1');
        Config::set('services.anthropic.model', 'claude-test');
        Config::set('services.anthropic.version', '2023-06-01');
        Config::set('ai.prompts.pir_full_tier1', 'PIR Tier 1 full report prompt');
        Queue::fake();
        Http::fake();

        $admin = User::create([
            'name' => 'Admin',
            'email' => 'admin-formal-generate@example.com',
            'password' => 'password',
            'role' => 'admin',
        ]);

        $lead = $this->snapshotLead();
        $assessment = $this->formalPirTier1Assessment($lead);
        $this->addMinimalPirScoringEvidence($assessment);

        $this->actingAs($admin)
            ->post(route('admin.assessments.generateReport', $assessment))
            ->assertRedirect(route('admin.assessments.show', $assessment))
            ->assertSessionHas('success', 'AI report generation started. Refresh this page in a moment.')
            ->assertSessionMissing('error');

        Queue::assertPushed(GenerateAdminFullReport::class, fn (GenerateAdminFullReport $job) => $job->assessmentId === $assessment->id);
        Http::assertNothingSent();

        $assessment->refresh();
        $this->assertSame('generating', $assessment->ai_generation_status);
        $this->assertNull($assessment->ai_generation_error);
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

    public function test_pir_tier1_structured_report_preview_renders_readable_sections_without_dashboard_json_wall(): void
    {
        $admin = User::create([
            'name' => 'Admin',
            'email' => 'admin-pir-preview@example.com',
            'password' => 'password',
            'role' => 'admin',
        ]);

        $lead = $this->snapshotLead(['email' => 'pir-preview@example.com']);
        $assessment = $this->formalPirTier1Assessment($lead);
        $assessment->forceFill([
            'status' => 'completed',
            'ai_generation_status' => 'completed',
            'ai_draft_json' => $this->structuredPirTier1Draft(),
            'ai_recommendation' => json_encode($this->structuredPirTier1Draft()),
        ])->save();

        $response = $this->actingAs($admin)
            ->get(route('admin.assessments.show', $assessment))
            ->assertOk()
            ->assertSee('Assessment Scores &amp; Evidence', false)
            ->assertSee('Generated Programme Intelligence Review Preview')
            ->assertSee('Regenerate AI Report')
            ->assertDontSee('Stored report JSON')
            ->assertSee('Cover Letter')
            ->assertSee('Executive Position')
            ->assertSee('Intelligence Dashboard')
            ->assertSee('Intelligence Profile')
            ->assertSee('Risk Register')
            ->assertSee('RAID Summary')
            ->assertSee('Root Cause Analysis')
            ->assertSee('Priority Plan')
            ->assertSee('Final Position')
            ->assertSee('Evidence Gaps / Recommended Deep-Dive')
            ->assertSee('Compliance Risk Signals')
            ->assertSee('Reporting Accuracy Risk Finding')
            ->assertSee('Decision Required')
            ->assertSee('Overall Score')
            ->assertSee('RAG: Amber')
            ->assertSee('Stage: Build')
            ->assertSee('Business Readiness Index')
            ->assertSee('BRI · 2.20 / 5')
            ->assertSee('Readiness constrained')
            ->assertSee('Value Realisation Index')
            ->assertSee('VRI · 2.40 / 5')
            ->assertSee('Value confidence weak')
            ->assertSee('Digital Maturity Index')
            ->assertSee('DMI · 3.50 / 5')
            ->assertSee('Partial maturity')
            ->assertSee('Risk Intelligence Index')
            ->assertSee('RII · 2.50 / 5')
            ->assertSee('Risk controls inconsistent')
            ->assertSee('Compliance Health Index')
            ->assertSee('CHI · 2.00 / 5')
            ->assertSee('Compliance pressure')
            ->assertSee('High')
            ->assertSee('High — Documented and interviewed')
            ->assertSee('Medium')
            ->assertSee('Medium — Partially evidenced')
            ->assertSee('Low — Single source')
            ->assertDontSee('No interpretation returned.')
            ->assertDontSee('Confidence:')
            ->assertSee('No reporting accuracy risk finding recorded.')
            ->assertSee('Operational intelligence only. Engage legal and compliance advisers.')
            ->assertSee('View raw JSON')
            ->assertDontSee('Risk Matrix (Top')
            ->assertDontSee('Intelligence Profile (Deep Dive)')
            ->assertDontSee('Priority Action Register')
            ->assertDontSee('Final Position Statement');

        $html = $response->getContent();
        preg_match('/<section[^>]*>\s*<h4[^>]*>Intelligence Dashboard<\/h4>.*?<\/section>/s', $html, $matches);
        $dashboardHtml = $matches[0] ?? '';

        $this->assertNotSame('', $dashboardHtml);
        $this->assertStringNotContainsString('"indices"', $dashboardHtml);
        $this->assertStringNotContainsString('<pre', $dashboardHtml);

        preg_match('/<section[^>]*>\s*<h4[^>]*>Risk Register<\/h4>.*?<\/section>/s', $html, $riskMatches);
        $riskHtml = $riskMatches[0] ?? '';

        $this->assertNotSame('', $riskHtml);
        $this->assertStringContainsString('High', $riskHtml);
        $this->assertStringContainsString('Medium', $riskHtml);
        $this->assertStringNotContainsString('>H<', $riskHtml);
        $this->assertStringNotContainsString('>M<', $riskHtml);

        $this->assertStringContainsString('<details', $html);
        $this->assertStringContainsString('intelligence_dashboard', $html);
    }

    public function test_pir_score_page_renders_full_report_schema_without_legacy_summary_key(): void
    {
        $admin = User::create([
            'name' => 'Admin',
            'email' => 'admin-pir-score-full-schema@example.com',
            'password' => 'password',
            'role' => 'admin',
        ]);

        $lead = $this->snapshotLead(['email' => 'pir-score-full-schema@example.com']);
        $assessment = $this->formalPirTier1Assessment($lead);
        $draft = $this->structuredPirTier1Draft();
        unset($draft['executive_summary'], $draft['recommendations']);

        $assessment->forceFill([
            'report_tier' => 'Tier 2 Full',
            'ai_generation_status' => 'completed',
            'ai_draft_json' => $draft,
            'ai_recommendation' => json_encode($draft),
        ])->save();

        $this->actingAs($admin)
            ->get(route('admin.assessments.score.phi', $assessment))
            ->assertOk()
            ->assertSee('AI Agent Recommendations')
            ->assertSee('The programme is recoverable if decision ownership is reset.')
            ->assertSee('Reset decisions');
    }

    public function test_snapshot_fallback_show_still_uses_snapshot_regeneration_route(): void
    {
        $admin = User::create([
            'name' => 'Admin',
            'email' => 'admin-snapshot-route@example.com',
            'password' => 'password',
            'role' => 'admin',
        ]);

        $lead = $this->snapshotLead([
            'email' => 'snapshot-route@example.com',
            'booking_token' => 'snapshot-route-token',
        ]);

        $this->actingAs($admin)
            ->get(route('admin.assessments.show', $lead->id))
            ->assertOk()
            ->assertSee(route('admin.assessments.regenerateSnapshotAi', $lead->id), false)
            ->assertDontSee(route('admin.assessments.generateReport', $lead->id), false);
    }

    public function test_admin_public_snapshot_assessment_show_supports_hot_snapshot_leads(): void
    {
        $admin = User::create([
            'name' => 'Admin',
            'email' => 'admin-hot-snapshot@example.com',
            'password' => 'password',
            'role' => 'admin',
        ]);

        $lead = Lead::create([
            'name' => 'High Risk Sponsor',
            'company' => 'Hot Snapshot Ltd',
            'email' => 'hot-show@example.com',
            'type' => 'PIR',
            'assessment_type' => 'PIR_SNAPSHOT',
            'lead_status' => 'Hot',
            'overall_score' => 2.1,
            'rag_status' => 'Red',
            'priority' => 'High',
            'source' => 'Assessment',
            'booking_token' => 'hot-snapshot-token',
            'index_scores_json' => ['BRI' => 2.1, 'VRI' => 2.1, 'DMI' => 2.1, 'CHI' => 2.1],
            'answers_json' => ['P1.S1' => 2, 'P1.S2' => 2],
        ]);

        $this->actingAs($admin)
            ->get(route('admin.assessments.show', $lead->id))
            ->assertOk()
            ->assertSee('Public Website Health-Check')
            ->assertSee('Hot Snapshot Ltd')
            ->assertSee('Download PDF Report');
    }

    public function test_admin_assessment_index_lists_all_public_snapshot_leads_regardless_of_sales_status(): void
    {
        $admin = User::create([
            'name' => 'Admin',
            'email' => 'admin-index@example.com',
            'password' => 'password',
            'role' => 'admin',
        ]);

        Lead::create([
            'name' => 'Hot Lead',
            'company' => 'Hot Snapshot Ltd',
            'email' => 'hot@example.com',
            'type' => 'PIR',
            'assessment_type' => 'PIR_SNAPSHOT',
            'lead_status' => 'Hot',
            'overall_score' => 2.1,
            'rag_status' => 'Red',
            'priority' => 'High',
            'source' => 'Assessment',
        ]);

        Lead::create([
            'name' => 'Cold Lead',
            'company' => 'Cold Snapshot Ltd',
            'email' => 'cold@example.com',
            'type' => 'SIR',
            'assessment_type' => 'SIR_SNAPSHOT',
            'lead_status' => 'Cold',
            'overall_score' => 4.1,
            'rag_status' => 'Green',
            'priority' => 'Low',
            'source' => 'Assessment',
        ]);

        Lead::create([
            'name' => 'Service Inquiry',
            'company' => 'Inquiry Ltd',
            'email' => 'inquiry@example.com',
            'type' => 'Consulting',
            'lead_status' => 'Warm',
            'overall_score' => 3.5,
            'rag_status' => 'Amber',
            'priority' => 'Medium',
            'source' => 'Contact',
        ]);

        $this->actingAs($admin)
            ->get(route('admin.assessments.index'))
            ->assertOk()
            ->assertSee('Hot Snapshot Ltd')
            ->assertSee('Cold Snapshot Ltd')
            ->assertDontSee('Inquiry Ltd');
    }

    private function snapshotLead(array $overrides = []): Lead
    {
        return Lead::create(array_merge([
            'name' => 'Casey Sponsor',
            'company' => 'Snapshot Client Ltd',
            'email' => 'casey-snapshot@example.com',
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
                'intelligence_brief' => 'Snapshot intelligence paragraph.',
                'insight_cards' => [],
            ],
        ], $overrides));
    }

    private function formalPirTier1Assessment(Lead $lead): Assessment
    {
        $client = Client::create([
            'company_name' => 'Formal Client Ltd',
            'primary_contact' => 'Alex Sponsor',
        ]);

        return Assessment::create([
            'client_id' => $client->id,
            'snapshot_submission_id' => $lead->id,
            'name' => 'ERP Programme',
            'type' => 'PIR',
            'report_tier' => 'Tier 1 Rapid',
            'target_entity' => 'ERP Replacement',
            'delivery_stage' => 'Build',
            'client_concerns' => 'Sponsor needs a full PIR review.',
            'overall_score' => 3.0,
            'rag_status' => 'Amber',
            'status' => 'in_progress',
            'sponsor_name' => 'Alex Sponsor',
            'interview_count' => 2,
            'documents_reviewed' => ['Steering pack'],
        ]);
    }

    private function addMinimalPirScoringEvidence(Assessment $assessment): void
    {
        foreach (['P1', 'P3', 'P4', 'P5', 'P7', 'P9', 'P10'] as $pillarCode) {
            $assessment->pillarScores()->create([
                'name' => "{$pillarCode} — Test Pillar",
                'score' => 3.0,
                'rag_status' => 'Amber',
            ]);
        }

        $assessment->questionResponses()->create([
            'pillar_name' => 'P1 — Governance & Decision-Making',
            'question' => 'P1.F1: Is decision ownership clear?',
            'score' => 3,
            'evidence_note' => 'Decision ownership is partly defined.',
            'confidence' => 'Medium',
        ]);
    }

    private function structuredPirTier1Draft(): array
    {
        return [
            'cover_letter' => "Dear Sponsor,\n\nThis is the formal review transmittal.\n\nReda Boukhiar",
            'executive_position' => 'The programme is recoverable if decision ownership is reset.',
            'intelligence_dashboard' => [
                'overall' => ['score' => 2.8, 'rag' => 'Amber', 'stage' => 'Build'],
                'indices' => [
                    'bri' => ['value' => 2.2, 'interpretation' => 'Readiness constrained'],
                    'vri' => ['value' => 2.4, 'interpretation' => 'Value confidence weak'],
                    'dmi' => ['value' => 3.5, 'interpretation' => 'Partial maturity'],
                    'rii' => ['value' => 2.5, 'interpretation' => 'Risk controls inconsistent'],
                    'chi' => ['value' => 2.0, 'interpretation' => 'Compliance pressure'],
                ],
                'alert_flags' => ['P1 below 3.0'],
                'confidence_legend' => [
                    'high' => 'Documented and interviewed',
                    'medium' => 'Partially evidenced',
                    'low' => 'Single source',
                ],
            ],
            'intelligence_profile' => [[
                'pillar_code' => 'P1',
                'pillar_name' => 'P1 - Governance',
                'score' => 2,
                'rag' => 'Red',
                'confidence' => 'High',
                'headline' => 'Governance decision delay',
                'evidence' => ['Decision log', 'Sponsor interview'],
                'business_impact' => 'Cutover readiness is affected.',
                'compliance_dimension' => 'Evidence trail is weak.',
                'action' => 'Reset decision ownership.',
            ]],
            'reporting_accuracy_risk_finding' => null,
            'risk_register' => [[
                'risk_title' => 'Governance decision delay',
                'probability' => 'H',
                'impact' => 'M',
                'owner' => 'Executive Sponsor',
                'current_control' => 'Steering forum',
                'action' => 'Reset decision ownership within 30 days.',
            ]],
            'raid_summary' => [
                'total_risks' => 5,
                'critical_risks' => 2,
                'issues_without_owner' => 1,
                'overdue_actions' => 2,
                'assessment' => 'RAID health is weak.',
            ],
            'root_cause_analysis' => [
                'narrative' => 'The root cause is weak escalation discipline.',
                'primary_cause' => 'Weak decision ownership',
                'causal_chain' => ['Unclear ownership', 'Delayed decisions'],
            ],
            'priority_plan' => [
                '30_days' => [['action_title' => 'Reset decisions', 'owner' => 'Sponsor', 'deadline' => '30 days', 'done_condition' => 'Owners named']],
                '60_days' => [['action_title' => 'Rebaseline plan', 'owner' => 'PMO', 'deadline' => '60 days', 'done_condition' => 'Baseline approved']],
                '90_days' => [['action_title' => 'Validate benefits', 'owner' => 'Finance', 'deadline' => '90 days', 'done_condition' => 'Benefits signed off']],
            ],
            'final_position' => 'Requires structured recovery before go-live can proceed',
            'tier1_bridge' => 'Further document validation is required.',
            'compliance_risk_signals' => 'Operational intelligence only. Engage legal and compliance advisers.',
        ];
    }
}
