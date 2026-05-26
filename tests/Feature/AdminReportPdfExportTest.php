<?php

namespace Tests\Feature;

use App\Models\Assessment;
use App\Models\AssessmentFramework;
use App\Models\AssessmentPillar;
use App\Models\AssessmentPillarScore;
use App\Models\AssessmentQuestionBank;
use App\Models\AssessmentQuestionResponse;
use App\Models\Client;
use App\Models\User;
use App\Services\ReportPdfService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use ReflectionMethod;
use Spatie\Browsershot\Browsershot;
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

    public function test_export_error_message_is_visible_after_redirect(): void
    {
        $assessment = $this->assessment();

        $this->actingAs($this->admin())
            ->followingRedirects()
            ->post(route('admin.assessments.exportPdf', $assessment))
            ->assertSee('Generate AI report first before exporting the PDF.');
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

    public function test_metadata_only_ai_draft_json_does_not_enable_pdf_export(): void
    {
        $assessment = $this->assessment([
            'ai_draft_json' => [
                'pir_full_tier1_generation' => [
                    'status' => 'generation_prepared',
                    'prompt_key' => 'pir_full_tier1',
                ],
            ],
            'ai_recommendation' => null,
        ]);

        $this->actingAs($this->admin())
            ->post(route('admin.assessments.exportPdf', $assessment))
            ->assertRedirect(route('admin.assessments.show', $assessment))
            ->assertSessionHas('error', 'Generate AI report first before exporting the PDF.');
    }

    public function test_export_uses_legacy_ai_recommendation_when_structured_draft_is_missing(): void
    {
        $assessment = $this->assessment([
            'ai_draft_json' => null,
            'ai_recommendation' => 'Legacy AI summary',
            'status' => 'completed',
        ]);

        $fakeService = new class extends ReportPdfService {
            public ?int $assessmentId = null;

            public function download(Assessment $assessment): BinaryFileResponse
            {
                $this->assessmentId = $assessment->id;

                $directory = storage_path('framework/testing');
                if (!is_dir($directory)) {
                    mkdir($directory, 0755, true);
                }

                $path = $directory . '/legacy-rab-report.pdf';
                file_put_contents($path, '%PDF-1.4 fake');

                return response()->download($path, 'legacy-rab-report.pdf');
            }
        };

        $this->app->instance(ReportPdfService::class, $fakeService);

        $this->actingAs($this->admin())
            ->post(route('admin.assessments.exportPdf', $assessment))
            ->assertOk()
            ->assertDownload('legacy-rab-report.pdf');

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

    public function test_completed_assessment_without_structured_draft_shows_rebuild_action(): void
    {
        $assessment = $this->assessment([
            'ai_draft_json' => null,
            'ai_recommendation' => 'Legacy AI summary',
            'status' => 'completed',
        ]);

        $this->actingAs($this->admin())
            ->get(route('admin.assessments.show', $assessment))
            ->assertOk()
            ->assertSee('Rebuild AI Insights')
            ->assertSee(route('admin.assessments.generateReport', $assessment), false);
    }

    public function test_report_html_matches_required_step_11_structure(): void
    {
        $assessment = $this->assessment([
            'ai_draft_json' => $this->draft(),
            'report_tier' => 'Tier 2 Full',
            'type' => 'PIR',
        ]);
        $this->seedQuestionBank('PIR');

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
            'respondent_role' => 'Programme Sponsor',
            'document_source' => 'Steering minutes',
            'stakeholder_divergence_note' => 'Delivery lead reported a stronger control position.',
        ]);

        $html = app(ReportPdfService::class)->renderHtml($assessment->fresh());

        $this->assertStringContainsString('RAB PROGRAMME INTELLIGENCE BRIEFING', $html);
        $this->assertStringContainsString('CONFIDENTIAL', $html);
        $this->assertStringNotContainsString('file://', $html);
        $this->assertSame(5, substr_count($html, '<div class="index-card">'));
        $this->assertStringContainsString('score-dial', $html);
        $this->assertStringContainsString('Controlled', $html);
        $this->assertStringContainsString('At Risk', $html);
        $this->assertStringContainsString('confidence-legend', $html);
        $this->assertStringContainsString('Radar Chart', $html);
        $this->assertStringContainsString('Bar Chart', $html);
        $this->assertStringContainsString('pdfRadarChart', $html);
        $this->assertStringContainsString('pdfBarChart', $html);
        $this->assertStringContainsString('Transmittal Letter', $html);
        $this->assertStringContainsString('Executive Intelligence Position', $html);
        $this->assertStringContainsString('Intelligence Dashboard', $html);
        $this->assertStringContainsString('Stakeholder Intelligence', $html);
        $this->assertStringContainsString('Sponsor Position', $html);
        $this->assertStringContainsString('Operational Position', $html);
        $this->assertStringContainsString('Sponsor and delivery team positions diverge on confidence.', $html);
        $this->assertStringContainsString('Status reporting', $html);
        $this->assertStringContainsString('Sponsor sees status as controlled.', $html);
        $this->assertStringContainsString('Delivery team reports evidence gaps.', $html);
        $this->assertStringContainsString('Evidence Validated Statement', $html);
        $this->assertStringContainsString('Evidence has been validated through direct document review and interview.', $html);
        $this->assertStringContainsString('RAID Summary', $html);
        $this->assertStringContainsString('Risk Register + Risk Heat Map', $html);
        $this->assertStringContainsString('Risk Heat Map — Probability × Impact', $html);
        $this->assertStringContainsString('risk-matrix-grid', $html);
        $this->assertStringContainsString('Compliance Risk Signals', $html);
        $this->assertStringContainsString('Appendix — Database Extract', $html);
        $this->assertStringContainsString('Appendix — Methodology Note', $html);
        $this->assertStringContainsString('Question ID', $html);
        $this->assertStringContainsString('Question Text', $html);
        $this->assertStringContainsString('Pillar / Domain', $html);
        $this->assertStringContainsString('Evidence Note', $html);
        $this->assertStringContainsString('Confidence Level', $html);
        $this->assertStringContainsString('Respondent Role', $html);
        $this->assertStringContainsString('Document Source', $html);
        $this->assertStringContainsString('Stakeholder Divergence', $html);
        $this->assertStringContainsString('P1.F1', $html);
        $this->assertStringContainsString('Is decision ownership clear?', $html);
        $this->assertStringContainsString('ABOUT RAB INTELLIGENCE INDICES', $html);
        $this->assertStringContainsString('RAB Proprietary Methodology™', $html);
        $this->assertStringNotContainsString('jspdf', strtolower($html));
        $this->assertStringNotContainsString('html2canvas', strtolower($html));
    }

    public function test_pir_tier1_report_html_renders_reporting_accuracy_risk_finding_when_null(): void
    {
        $assessment = $this->assessment([
            'ai_draft_json' => array_merge($this->draft(), [
                'reporting_accuracy_risk_finding' => null,
                'intelligence_dashboard' => [
                    'indices' => [
                        'bri' => ['value' => 3.93, 'interpretation' => 'BRI of 3.93 confirms readiness evidence is usable.'],
                    ],
                    'confidence_legend' => [
                        'high' => 'Confirmed by documentary evidence and interview',
                        'medium' => 'Confirmed by interview only',
                        'low' => 'Single source or contradicted evidence',
                    ],
                ],
                'tier1_bridge' => 'Tier 1 bridge narrative.',
                'stakeholder_intelligence' => null,
            ]),
            'report_tier' => 'Tier 1 Rapid',
            'type' => 'PIR',
        ]);

        $html = app(ReportPdfService::class)->renderHtml($assessment->fresh());

        $this->assertStringContainsString('Reporting Accuracy Risk Finding', $html);
        $this->assertStringContainsString('No reporting accuracy risk finding recorded.', $html);
        $this->assertStringContainsString('BRI of 3.93 confirms readiness evidence is usable.', $html);
        $this->assertStringContainsString('Confirmed by documentary evidence and interview', $html);
    }

    public function test_pdf_risk_heat_map_renders_readable_axes_markers_and_legend(): void
    {
        $assessment = $this->assessment([
            'ai_draft_json' => array_merge($this->draft(), [
                'risk_register' => [
                    [
                        'risk_title' => 'Benefits slippage',
                        'probability' => 'H',
                        'impact' => 'H',
                        'owner' => 'Sponsor',
                        'current_control' => 'Weekly reporting',
                        'action' => 'Rebaseline benefits.',
                    ],
                    [
                        'risk_title' => 'Supplier dependency',
                        'probability' => 'M',
                        'impact' => 'L',
                        'owner' => 'Commercial Lead',
                        'current_control' => 'Supplier checkpoint',
                        'action' => 'Confirm dependency plan.',
                    ],
                    [
                        'risk_title' => 'Data migration readiness',
                        'probability' => 'High',
                        'impact' => 'High',
                        'owner' => 'Technology Lead',
                        'current_control' => 'Cutover review',
                        'action' => 'Validate migration rehearsal.',
                    ],
                ],
            ]),
        ]);

        $html = app(ReportPdfService::class)->renderHtml($assessment->fresh());

        $this->assertStringContainsString('Risk Heat Map — Probability × Impact', $html);
        $this->assertStringContainsString('Risks are positioned by probability and impact.', $html);
        $this->assertStringContainsString('Impact: Low', $html);
        $this->assertStringContainsString('Impact: Medium', $html);
        $this->assertStringContainsString('Impact: High', $html);
        $this->assertStringContainsString('Probability: Low', $html);
        $this->assertStringContainsString('Probability: Medium', $html);
        $this->assertStringContainsString('Probability: High', $html);
        $this->assertStringContainsString('<td>High</td><td>High</td>', $html);
        $this->assertStringContainsString('<td>Medium</td><td>Low</td>', $html);
        $this->assertStringContainsString('<span class="risk-cell-point">1</span>', $html);
        $this->assertStringContainsString('<span class="risk-cell-point">3</span>', $html);
        $this->assertStringContainsString('1 — Benefits slippage', $html);
        $this->assertStringContainsString('2 — Supplier dependency', $html);
        $this->assertStringContainsString('3 — Data migration readiness', $html);
        $this->assertStringNotContainsString('Impact H', $html);
        $this->assertStringNotContainsString('Probability H', $html);
    }

    public function test_pdf_risk_heat_map_shows_empty_message_without_grid_when_no_risks_exist(): void
    {
        $assessment = $this->assessment([
            'ai_draft_json' => array_merge($this->draft(), [
                'risk_register' => [],
            ]),
        ]);

        $html = app(ReportPdfService::class)->renderHtml($assessment->fresh());

        $this->assertStringContainsString('Risk Heat Map — Probability × Impact', $html);
        $this->assertStringContainsString('No risks available for heat map rendering.', $html);
        $this->assertStringNotContainsString('<div class="risk-matrix-grid">', $html);
    }

    public function test_report_pdf_service_uses_configured_browsershot_chrome_path_when_executable(): void
    {
        Config::set('services.browsershot.chrome_path', PHP_BINARY);

        $method = new ReflectionMethod(ReportPdfService::class, 'chromePath');
        $method->setAccessible(true);

        $this->assertSame(PHP_BINARY, $method->invoke(app(ReportPdfService::class)));
    }

    public function test_report_pdf_service_does_not_apply_missing_browsershot_chrome_path(): void
    {
        Config::set('services.browsershot.chrome_path', '/missing/google-chrome');

        $method = new ReflectionMethod(ReportPdfService::class, 'chromePath');
        $method->setAccessible(true);

        $this->assertNull($method->invoke(app(ReportPdfService::class)));
    }

    public function test_real_browsershot_pdf_export_smoke_when_environment_enabled(): void
    {
        if (! env('RUN_REAL_BROWSERHOT_PDF_TEST')) {
            $this->markTestSkipped('Set RUN_REAL_BROWSERHOT_PDF_TEST=1 and BROWSERSHOT_CHROME_PATH to run the real Browsershot smoke test.');
        }

        $chromePath = env('BROWSERSHOT_CHROME_PATH');
        if (! $chromePath || ! is_executable($chromePath)) {
            $this->markTestSkipped('BROWSERSHOT_CHROME_PATH is not configured to an executable Chrome/Chromium binary.');
        }

        $assessment = $this->assessment([
            'ai_draft_json' => array_merge($this->draft(), [
                'reporting_accuracy_risk_finding' => null,
                'tier1_bridge' => 'Tier 1 bridge narrative.',
                'stakeholder_intelligence' => null,
            ]),
            'report_tier' => 'Tier 1 Rapid',
            'type' => 'PIR',
        ]);

        $path = storage_path('framework/testing/real-browsershot-smoke.pdf');
        if (! is_dir(dirname($path))) {
            mkdir(dirname($path), 0755, true);
        }

        Browsershot::html(app(ReportPdfService::class)->renderHtml($assessment->fresh()))
            ->setChromePath($chromePath)
            ->setNodeModulePath(base_path('node_modules'))
            ->noSandbox()
            ->format('A4')
            ->save($path);

        $this->assertFileExists($path);
        $this->assertGreaterThan(0, filesize($path));
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
                'sponsor_position' => 'Sponsor sees status as controlled.',
                'operational_position' => 'Delivery team reports evidence gaps.',
                'divergence_areas' => [[
                    'area' => 'Status reporting',
                    'sponsor_view' => 'Sponsor sees status as controlled.',
                    'operational_view' => 'Delivery team reports evidence gaps.',
                    'finding' => 'Status is ahead of delivery evidence.',
                ]],
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
            'evidence_validated_statement' => 'Evidence has been validated through direct document review and interview.',
        ];
    }

    private function seedQuestionBank(string $frameworkCode): void
    {
        $framework = AssessmentFramework::create([
            'code' => $frameworkCode,
            'name' => $frameworkCode === 'PIR' ? 'Programme Intelligence Review' : 'Service Intelligence Review',
            'is_active' => true,
        ]);

        $pillar = AssessmentPillar::create([
            'framework_id' => $framework->id,
            'code' => $frameworkCode === 'PIR' ? 'P1' : 'D1',
            'name' => $frameworkCode === 'PIR' ? 'Governance & Decision-Making' : 'Service Governance & Ownership',
            'weight' => 1.0,
            'is_critical' => false,
            'display_order' => 1,
        ]);

        AssessmentQuestionBank::create([
            'framework_id' => $framework->id,
            'pillar_id' => $pillar->id,
            'level' => 'full',
            'question_code' => $frameworkCode === 'PIR' ? 'P1.F1' : 'D1.F1',
            'question_text' => 'Is decision ownership clear?',
            'is_active' => true,
            'display_order' => 1,
        ]);
    }
}
