<?php

namespace Tests\Feature;

use App\Models\Assessment;
use App\Models\AssessmentPillarScore;
use App\Models\AssessmentQuestionResponse;
use App\Models\Client;
use App\Models\User;
use App\Services\ReportPdfService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Tests\TestCase;

class AdminReportPdfExportTest extends TestCase
{
    use RefreshDatabase;

    public function test_export_requires_generated_ai_draft_json(): void
    {
        $assessment = $this->assessment();

        $this->actingAs($this->admin())
            ->post(route('admin.assessments.exportPdf', $assessment))
            ->assertRedirect(route('admin.assessments.show', $assessment))
            ->assertSessionHas('error', 'Generate AI report first before exporting the PDF.');
    }

    public function test_export_downloads_pdf_through_report_pdf_service(): void
    {
        $assessment = $this->assessment(['ai_draft_json' => $this->draft()]);
        $fakeService = new class extends ReportPdfService {
            public ?int $assessmentId = null;

            public function download(Assessment $assessment): BinaryFileResponse
            {
                $this->assessmentId = $assessment->id;

                $directory = storage_path('framework/testing');
                if (!is_dir($directory)) {
                    mkdir($directory, 0755, true);
                }

                $path = $directory . '/fake-rab-report.pdf';
                file_put_contents($path, '%PDF-1.4 fake');

                return response()->download($path, 'fake-rab-report.pdf');
            }
        };

        $this->app->instance(ReportPdfService::class, $fakeService);

        $this->actingAs($this->admin())
            ->post(route('admin.assessments.exportPdf', $assessment))
            ->assertOk()
            ->assertDownload('fake-rab-report.pdf');

        $this->assertSame($assessment->id, $fakeService->assessmentId);
    }

    public function test_assessment_page_uses_server_side_export_form_without_browser_pdf_libraries(): void
    {
        $assessment = $this->assessment(['ai_draft_json' => $this->draft()]);

        $response = $this->actingAs($this->admin())
            ->get(route('admin.assessments.show', $assessment))
            ->assertOk();

        $response->assertSee(route('admin.assessments.exportPdf', $assessment), false);
        $response->assertSee('Export Report');
        $response->assertDontSee('jspdf', false);
        $response->assertDontSee('html2canvas', false);
        $response->assertDontSee('pdf-header-template', false);
    }

    public function test_report_html_matches_required_step_11_structure(): void
    {
        $assessment = $this->assessment([
            'ai_draft_json' => $this->draft(),
            'report_tier' => 'Tier 2 Full',
            'type' => 'PIR',
        ]);

        AssessmentPillarScore::create([
            'assessment_id' => $assessment->id,
            'name' => 'P1 - Governance',
            'score' => 2.4,
            'rag_status' => 'Red',
            'critical_flag' => true,
        ]);

        AssessmentQuestionResponse::create([
            'assessment_id' => $assessment->id,
            'pillar_name' => 'P1 - Governance',
            'question' => 'P1.F1: Governance cadence',
            'score' => 2,
            'evidence_note' => 'Steering group evidence missing.',
            'confidence' => 'High',
            'confidence_level' => 'High',
        ]);

        $html = app(ReportPdfService::class)->renderHtml($assessment->fresh());

        $this->assertStringContainsString('RAB PROGRAMME INTELLIGENCE BRIEFING', $html);
        $this->assertStringContainsString('CONFIDENTIAL', $html);
        $this->assertStringContainsString('Transmittal Letter', $html);
        $this->assertStringContainsString('Executive Intelligence Position', $html);
        $this->assertStringContainsString('Intelligence Dashboard', $html);
        $this->assertStringContainsString('Stakeholder Intelligence', $html);
        $this->assertStringContainsString('RAID Summary', $html);
        $this->assertStringContainsString('Appendix — Evidence Base + Methodology Note', $html);
        $this->assertStringContainsString('This report is produced by RAB Consulting Services Ltd.', $html);
        $this->assertStringContainsString('RAB Consulting Services Ltd | rboukhiar@rabconsultingservices.com | +44 7717 544322 | rabconsultingservices.com', $html);
        $this->assertStringContainsString('RAB Proprietary Methodology™', $html);
        $this->assertStringContainsString('Page 1 of 12', $html);
        $this->assertStringContainsString('Appendix — Page 12 of 12', $html);
        $this->assertStringNotContainsString('jspdf', strtolower($html));
        $this->assertStringNotContainsString('html2canvas', strtolower($html));
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

    private function assessment(array $overrides = []): Assessment
    {
        $client = Client::create([
            'company_name' => 'Acme Ltd',
            'primary_contact' => 'Alex Sponsor',
        ]);

        return Assessment::create(array_merge([
            'client_id' => $client->id,
            'name' => 'Programme Recovery Assessment',
            'target_entity' => 'Atlas Programme',
            'type' => 'PIR',
            'report_tier' => 'Tier 2 Full',
            'delivery_stage' => 'Delivery Recovery',
            'overall_score' => 2.8,
            'rag_status' => 'Amber',
            'status' => 'completed',
            'critical_flag' => false,
            'bri' => 2.7,
            'vri' => 2.4,
            'dmi' => 3.1,
            'rii' => 2.2,
            'chi' => 2.6,
            'scoring_version' => '1.0',
        ], $overrides));
    }

    private function draft(): array
    {
        return [
            'cover_letter' => 'Dear Sponsor, this is the formal transmittal.',
            'executive_position' => 'The programme is recoverable with immediate governance intervention.',
            'intelligence_dashboard' => [
                'indices' => [
                    'BRI' => ['score' => 2.7, 'interpretation' => 'Readiness constrained'],
                    'VRI' => ['score' => 2.4, 'interpretation' => 'Value leakage'],
                    'DMI' => ['score' => 3.1, 'interpretation' => 'Partial maturity'],
                    'RII' => ['score' => 2.2, 'interpretation' => 'Risk exposure'],
                    'CHI' => ['score' => 2.6, 'interpretation' => 'Compliance pressure'],
                ],
                'alert_flags' => ['Critical governance exposure', 'Compliance risk signal'],
            ],
            'stakeholder_intelligence' => [
                'divergence_summary' => 'Sponsor and delivery team positions diverge on confidence.',
                'governance_implication' => 'Decision rights need to be reset.',
            ],
            'intelligence_profile' => [
                [
                    'headline' => 'Governance cadence failure',
                    'score' => 2.4,
                    'confidence' => 'High',
                    'evidence' => 'Steering minutes show unresolved actions.',
                    'business_impact' => 'Escalation lag is increasing delivery risk.',
                    'action' => 'Reset sponsor forum cadence.',
                ],
            ],
            'risk_register' => [
                [
                    'risk_title' => 'Benefits slippage',
                    'probability' => 'High',
                    'impact' => 'High',
                    'owner' => 'Sponsor',
                    'current_control' => 'Weekly reporting',
                    'action' => 'Rebaseline benefits.',
                ],
            ],
            'raid_summary' => [
                'total_risks' => 5,
                'critical_risks' => 2,
                'issues_without_owner' => 1,
                'overdue_actions' => 3,
                'assessment' => 'RAID hygiene requires immediate correction.',
            ],
            'root_cause_analysis' => [
                'narrative' => 'The root cause is weak escalation discipline.',
                'primary_cause' => 'Governance ambiguity',
                'causal_chain' => ['Ambiguous ownership', 'Late decisioning'],
            ],
            'priority_plan' => [
                '30_days' => [['action_title' => 'Reset governance', 'owner' => 'Sponsor', 'deadline' => '30 days', 'done_condition' => 'Forum active']],
                '60_days' => [['action_title' => 'Rebaseline plan', 'owner' => 'PMO', 'deadline' => '60 days', 'done_condition' => 'Baseline approved']],
                '90_days' => [['action_title' => 'Validate benefits', 'owner' => 'Finance', 'deadline' => '90 days', 'done_condition' => 'Benefits signed off']],
            ],
            'compliance_risk_signals' => 'Regulatory evidence trail is incomplete.',
            'final_position' => 'Proceed with controlled recovery.',
        ];
    }
}
