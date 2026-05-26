<?php

namespace Tests\Feature;

use App\Jobs\GenerateAdminFullReport;
use App\Models\Assessment;
use App\Models\AssessmentFramework;
use App\Models\AssessmentPillar;
use App\Models\AssessmentQuestionBank;
use App\Models\Client;
use App\Models\User;
use App\Services\AdminFullReportGenerationService;
use App\Services\ReportPdfService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Tests\TestCase;

class AdminSirTier1ReviewWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_sir_tier1_generate_endpoint_dispatches_job_and_does_not_call_anthropic(): void
    {
        $this->configureAnthropic();
        Queue::fake();
        Http::fake();

        $admin = $this->admin();
        $assessment = Assessment::create([
            'client_id' => Client::create([
                'company_name' => 'Queued SIR Co',
                'primary_contact' => 'Sam Sponsor',
            ])->id,
            'assessor_id' => $admin->id,
            'name' => 'Queued SIR Tier 1',
            'type' => 'SIR',
            'report_tier' => 'Tier 1 Rapid',
            'overall_score' => 2.8,
            'rag_status' => 'Amber',
            'status' => 'draft',
        ]);

        $this->actingAs($admin)
            ->post(route('admin.assessments.generateReport', $assessment))
            ->assertRedirect(route('admin.assessments.show', $assessment))
            ->assertSessionHas('success', 'AI report generation started. Refresh this page in a moment.');

        $assessment->refresh();

        $this->assertSame('generating', $assessment->ai_generation_status);
        $this->assertNull($assessment->ai_generation_error);
        Queue::assertPushed(GenerateAdminFullReport::class, fn (GenerateAdminFullReport $job) => $job->assessmentId === $assessment->id);
        Http::assertNothingSent();
    }

    public function test_admin_can_create_score_generate_render_and_export_sir_tier1_review(): void
    {
        $this->configureAnthropic();
        $this->seedSirFramework();
        Queue::fake();

        $admin = $this->admin();
        $client = Client::create([
            'company_name' => 'SIR Tier 1 Co',
            'primary_contact' => 'Sam Sponsor',
            'email' => 'sam@example.com',
        ]);

        $this->actingAs($admin)
            ->post(route('admin.assessments.store'), [
                'client_id' => $client->id,
                'type' => 'SIR',
                'report_tier' => 'Tier 1 Rapid',
                'name' => 'Service Stability Review',
                'target_entity' => 'Payments Platform',
            ])
            ->assertRedirect();

        $assessment = Assessment::firstOrFail();
        $this->assertSame('SIR', $assessment->type);
        $this->assertSame('Tier 1 Rapid', $assessment->report_tier);

        $this->actingAs($admin)
            ->putJson(route('admin.assessments.autosave', $assessment), [
                'fields' => [
                    'service_context' => 'Transformation',
                    'client_concerns' => 'Recurring incidents are not visible in service reporting.',
                    'regulatory_context' => 'fca_uk',
                    'sponsor_name' => 'Sam Sponsor',
                    'interview_count' => 4,
                    'documents_reviewed' => "SLA pack\nIncident report\nCMDB extract",
                    'annual_service_cost' => '250000',
                    'top_5_risks' => "Major incident recurrence\nCMDB accuracy gap",
                ],
            ])
            ->assertOk();

        foreach ($this->questionScores() as $row) {
            $this->actingAs($admin)
                ->putJson(route('admin.assessments.autosave', $assessment), [
                    'question_code' => $row['code'],
                    'pillar_name' => $row['domain'] . ' — ' . $this->domainNames()[$row['domain']],
                    'question' => $row['text'],
                    'score' => $row['score'],
                    'evidence_note' => $row['evidence'],
                    'source_type' => $row['source_type'],
                    'respondent_role' => $row['respondent_role'],
                    'document_source' => $row['document_source'],
                    'confidence' => $row['confidence'],
                ])
                ->assertOk();
        }

        $assessment->refresh();
        $this->assertNotNull($assessment->ssi);
        $this->assertNotNull($assessment->smi);
        $this->assertNotNull($assessment->simi);
        $this->assertNotNull($assessment->bau_readiness);
        $this->assertNotNull($assessment->chi);

        $draft = $this->structuredDraft();
        Http::fake([
            'example.test/*' => Http::response($this->streamedAnthropicResponse($draft), 200, [
                'Content-Type' => 'text/event-stream',
            ]),
        ]);
        $capturedAnthropicRequest = null;

        $this->actingAs($admin)
            ->post(route('admin.assessments.generateReport', $assessment))
            ->assertRedirect(route('admin.assessments.show', $assessment))
            ->assertSessionHas('success', 'AI report generation started. Refresh this page in a moment.');

        Queue::assertPushed(GenerateAdminFullReport::class, fn (GenerateAdminFullReport $job) => $job->assessmentId === $assessment->id);

        (new GenerateAdminFullReport($assessment->id))->handle(app(AdminFullReportGenerationService::class));

        Http::assertSent(function ($request) use (&$capturedAnthropicRequest) {
            $payload = $request->data();
            $capturedAnthropicRequest = $payload;

            return $request->url() === 'https://example.test/v1/messages';
        });

        $this->assertNotNull($capturedAnthropicRequest);
        $this->assertSirTier1ClaudeRequestIsSufficient($capturedAnthropicRequest, $assessment);

        $assessment->refresh();
        $this->assertEquals($draft, $assessment->ai_draft_json);
        $this->assertSame('completed', $assessment->status);
        $this->assertSame('completed', $assessment->ai_generation_status);
        $this->assertNull($assessment->ai_generation_error);

        $this->actingAs($admin)
            ->get(route('admin.assessments.show', $assessment))
            ->assertOk()
            ->assertSee('Cover Letter')
            ->assertSee('Executive Position')
            ->assertSee('Intelligence Dashboard')
            ->assertSee('Intelligence Profile')
            ->assertSee('Risk Register')
            ->assertSee('Root Cause Analysis')
            ->assertSee('Priority Plan')
            ->assertSee('Final Position')
            ->assertSee('Tier 1 Bridge')
            ->assertSee('Evidence gaps and recommended deep-dive areas')
            ->assertDontSee('Evidence Gaps / Recommended Deep-Dive')
            ->assertSee('Service Stability Index')
            ->assertSee('SSI · 2.41 / 5')
            ->assertSee('Service Maturity Index')
            ->assertSee('SMI/SIMI Delta')
            ->assertSee('D1 — Service Governance &amp; Ownership', false)
            ->assertSee('Formal transmittal for SIR Tier 1')
            ->assertSee('Executive position for SIR Tier 1')
            ->assertDontSee('Business Readiness Index')
            ->assertDontSee('Value Realisation Index')
            ->assertDontSee('Digital Maturity Index')
            ->assertDontSee('Risk Intelligence Index')
            ->assertDontSee('RAID Summary')
            ->assertDontSee('Stakeholder Intelligence')
            ->assertDontSee('Reporting Accuracy Risk Finding');

        $fakeService = new class extends ReportPdfService {
            public ?int $assessmentId = null;

            public function download(Assessment $assessment): BinaryFileResponse
            {
                $this->assessmentId = $assessment->id;

                $directory = storage_path('framework/testing');
                if (! is_dir($directory)) {
                    mkdir($directory, 0755, true);
                }

                $path = $directory . '/sir-tier1-review.pdf';
                file_put_contents($path, '%PDF-1.4 fake');

                return response()->download($path, 'sir-tier1-review.pdf');
            }
        };
        $this->app->instance(ReportPdfService::class, $fakeService);

        $this->actingAs($admin)
            ->post(route('admin.assessments.exportPdf', $assessment))
            ->assertOk()
            ->assertDownload('sir-tier1-review.pdf');

        $this->assertSame($assessment->id, $fakeService->assessmentId);
    }

    public function test_sir_tier1_rejects_pir_shaped_streamed_response(): void
    {
        $this->configureAnthropic();
        $this->seedSirFramework();

        $assessment = Assessment::create([
            'client_id' => Client::create([
                'company_name' => 'Bad SIR Co',
                'primary_contact' => 'Sam Sponsor',
            ])->id,
            'name' => 'Bad SIR',
            'target_entity' => 'Bad Service',
            'type' => 'SIR',
            'report_tier' => 'Tier 1 Rapid',
            'overall_score' => 2.8,
            'rag_status' => 'Amber',
            'status' => 'draft',
            'service_context' => 'Transformation',
            'chi' => 2.5,
        ]);

        foreach ($this->questionScores() as $row) {
            $this->actingAs($this->admin())
                ->putJson(route('admin.assessments.autosave', $assessment), [
                    'question_code' => $row['code'],
                    'pillar_name' => $row['domain'] . ' — ' . $this->domainNames()[$row['domain']],
                    'question' => $row['text'],
                    'score' => $row['score'],
                    'evidence_note' => $row['evidence'],
                    'source_type' => $row['source_type'],
                    'respondent_role' => $row['respondent_role'],
                    'document_source' => $row['document_source'],
                    'confidence' => $row['confidence'],
                ])
                ->assertOk();
        }

        $badDraft = $this->structuredDraft();
        $badDraft['raid_summary'] = ['total_risks' => 1];
        $badDraft['intelligence_dashboard']['indices']['bri'] = ['value' => 2.4, 'interpretation' => 'Wrong PIR index.'];
        $badDraft['intelligence_profile'][0]['pillar_code'] = 'P1';
        unset($badDraft['intelligence_profile'][0]['domain_code']);

        Http::fake([
            'example.test/*' => Http::response($this->streamedAnthropicResponse($badDraft), 200, [
                'Content-Type' => 'text/event-stream',
            ]),
        ]);

        (new GenerateAdminFullReport($assessment->id))->handle(app(AdminFullReportGenerationService::class));

        $assessment->refresh();
        $this->assertSame('failed', $assessment->ai_generation_status);
        $this->assertStringContainsString('non-canonical top-level keys', $assessment->ai_generation_error);
        $this->assertNull($assessment->ai_draft_json);
    }

    public function test_sir_tier1_metadata_only_export_guard_still_blocks_pdf(): void
    {
        $assessment = Assessment::create([
            'client_id' => Client::create([
                'company_name' => 'Guard SIR Co',
                'primary_contact' => 'Sam Sponsor',
            ])->id,
            'name' => 'Guard SIR',
            'type' => 'SIR',
            'report_tier' => 'Tier 1 Rapid',
            'overall_score' => 2.8,
            'rag_status' => 'Amber',
            'status' => 'completed',
            'ai_draft_json' => [
                'sir_full_tier1_generation' => [
                    'status' => 'generation_prepared',
                    'prompt_key' => 'sir_full_tier1',
                ],
            ],
        ]);

        $this->actingAs($this->admin())
            ->post(route('admin.assessments.exportPdf', $assessment))
            ->assertRedirect(route('admin.assessments.show', $assessment))
            ->assertSessionHas('error', 'Generate AI report first before exporting the PDF.');
    }

    private function configureAnthropic(): void
    {
        Config::set('services.anthropic.api_key', 'test-anthropic-key');
        Config::set('services.anthropic.base_url', 'https://example.test/v1');
        Config::set('services.anthropic.model', 'claude-sonnet-4-5');
        Config::set('services.anthropic.version', '2023-06-01');
    }

    private function assertSirTier1ClaudeRequestIsSufficient(array $requestBody, Assessment $assessment): void
    {
        $input = json_decode($requestBody['messages'][0]['content'][0]['text'], true);
        $aiPayload = $input['ai_payload'];

        $this->assertTrue($requestBody['stream']);
        $this->assertSame(config('ai.prompts.sir_full_tier1'), $requestBody['system']);
        $this->assertSame('sir_full_tier1', $input['prompt_key']);
        $this->assertSame('SIR_FULL', $input['metadata']['type']);
        $this->assertTrue($input['metadata']['is_full']);
        $this->assertSame($assessment->id, $input['metadata']['assessment_id']);
        $this->assertSame('SIR', $aiPayload['framework']);
        $this->assertSame('Review', $aiPayload['tier']);
        $this->assertSame('Payments Platform', $aiPayload['service_name']);
        $this->assertSame('Transformation', $aiPayload['service_context']);
        $this->assertArrayHasKey('domain_scores', $aiPayload);
        $this->assertArrayHasKey('domain_names', $aiPayload);

        foreach (['ssi', 'smi', 'simi', 'bau_ri', 'chi', 'smi_simi_delta'] as $indexKey) {
            $this->assertArrayHasKey($indexKey, $aiPayload);
            $this->assertIsNumeric($aiPayload[$indexKey]);
        }

        $this->assertNull($aiPayload['stakeholder_notes']);
        $this->assertEquals(250000.0, $aiPayload['annual_service_cost']);
        $this->assertArrayHasKey('D1.F1', $aiPayload['evidence_notes']);
        $this->assertArrayHasKey('D10.F1', $aiPayload['evidence_notes']);
        $this->assertArrayHasKey('D1.F1', $aiPayload['compliance_question_scores']);
        $this->assertArrayHasKey('D10.F1', $aiPayload['compliance_question_scores']);
        $this->assertNotEmpty($aiPayload['question_responses']);

        foreach ($aiPayload['question_responses'] as $response) {
            $this->assertSame(['id', 'domain_code', 'score', 'is_compliance'], array_keys($response));
            $this->assertArrayNotHasKey('pillar_code', $response);
        }
    }

    private function admin(): User
    {
        return User::create([
            'name' => 'Admin',
            'email' => 'admin-sir-tier1-' . uniqid() . '@example.com',
            'password' => 'password',
            'role' => 'admin',
        ]);
    }

    private function seedSirFramework(): void
    {
        $framework = AssessmentFramework::create([
            'code' => 'SIR',
            'name' => 'Service Intelligence Review',
        ]);

        foreach ($this->domainNames() as $code => $name) {
            AssessmentPillar::create([
                'framework_id' => $framework->id,
                'code' => $code,
                'name' => $name,
                'weight' => $this->domainWeights()[$code],
                'is_critical' => in_array($code, ['D2', 'D7', 'D10'], true),
                'display_order' => (int) substr($code, 1),
            ]);
        }

        foreach ($this->questionScores() as $index => $row) {
            AssessmentQuestionBank::create([
                'framework_id' => $framework->id,
                'pillar_id' => AssessmentPillar::where('framework_id', $framework->id)->where('code', $row['domain'])->firstOrFail()->id,
                'level' => 'full',
                'question_code' => $row['code'],
                'question_text' => $row['text'],
                'is_compliance' => $row['is_compliance'],
                'is_active' => true,
                'display_order' => $index + 1,
            ]);
        }
    }

    private function domainNames(): array
    {
        return [
            'D1' => 'Service Governance & Ownership',
            'D2' => 'Incident & Major Incident Management',
            'D4' => 'Problem Management',
            'D5' => 'Change & Release Management',
            'D6' => 'Service Performance, SLA & Reporting',
            'D7' => 'Service Transition & BAU Readiness',
            'D8' => 'Service Operations & Support Model',
            'D10' => 'Operational Resilience & Continuity',
            'D11' => 'Service Tooling, CMDB & Knowledge Management',
            'D12' => 'Service Intelligence & Continuous Value',
        ];
    }

    private function domainWeights(): array
    {
        return [
            'D1' => 1.2,
            'D2' => 1.4,
            'D4' => 1.2,
            'D5' => 1.3,
            'D6' => 1.2,
            'D7' => 1.3,
            'D8' => 1.1,
            'D10' => 1.3,
            'D11' => 1.2,
            'D12' => 1.1,
        ];
    }

    private function questionScores(): array
    {
        return [
            ['code' => 'D1.F1', 'domain' => 'D1', 'score' => 2, 'is_compliance' => true, 'text' => 'Service ownership is clear.', 'evidence' => 'Ownership model has gaps.', 'source_type' => 'interview', 'respondent_role' => 'Service Owner', 'document_source' => 'Ownership interview', 'confidence' => 'high'],
            ['code' => 'D2.F1', 'domain' => 'D2', 'score' => 2, 'is_compliance' => false, 'text' => 'Major incidents are reviewed.', 'evidence' => 'Three P1 incidents had no post-incident review.', 'source_type' => 'document', 'respondent_role' => 'Major Incident Manager', 'document_source' => 'Incident report', 'confidence' => 'high'],
            ['code' => 'D4.F1', 'domain' => 'D4', 'score' => 2, 'is_compliance' => false, 'text' => 'Known errors are maintained.', 'evidence' => 'KEDB is not maintained.', 'source_type' => 'document', 'respondent_role' => 'Problem Manager', 'document_source' => 'KEDB extract', 'confidence' => 'medium'],
            ['code' => 'D5.F1', 'domain' => 'D5', 'score' => 2, 'is_compliance' => false, 'text' => 'Change failure is measured.', 'evidence' => 'Change failure rate conflicts with SLA packs.', 'source_type' => 'document', 'respondent_role' => 'Change Manager', 'document_source' => 'Change log', 'confidence' => 'high'],
            ['code' => 'D6.F1', 'domain' => 'D6', 'score' => 3, 'is_compliance' => false, 'text' => 'SLA reporting is reliable.', 'evidence' => 'SLA packs omit repeat incidents.', 'source_type' => 'document', 'respondent_role' => 'Service Reporting Lead', 'document_source' => 'SLA pack', 'confidence' => 'medium'],
            ['code' => 'D7.F1', 'domain' => 'D7', 'score' => 2, 'is_compliance' => false, 'text' => 'BAU readiness is evidenced.', 'evidence' => 'Transition acceptance remains informal.', 'source_type' => 'workshop', 'respondent_role' => 'Transition Lead', 'document_source' => 'Transition workshop', 'confidence' => 'high'],
            ['code' => 'D8.F1', 'domain' => 'D8', 'score' => 3, 'is_compliance' => false, 'text' => 'Support model is defined.', 'evidence' => 'Resolver model exists but handoffs are unclear.', 'source_type' => 'document', 'respondent_role' => 'Support Manager', 'document_source' => 'Support model', 'confidence' => 'medium'],
            ['code' => 'D10.F1', 'domain' => 'D10', 'score' => 2, 'is_compliance' => true, 'text' => 'Continuity controls are tested.', 'evidence' => 'DR test evidence is incomplete.', 'source_type' => 'document', 'respondent_role' => 'Continuity Lead', 'document_source' => 'DR pack', 'confidence' => 'high'],
            ['code' => 'D11.F1', 'domain' => 'D11', 'score' => 2, 'is_compliance' => false, 'text' => 'CMDB governance is accurate.', 'evidence' => 'CMDB ownership is not evidenced.', 'source_type' => 'document', 'respondent_role' => 'Tooling Lead', 'document_source' => 'CMDB extract', 'confidence' => 'high'],
            ['code' => 'D12.F1', 'domain' => 'D12', 'score' => 2, 'is_compliance' => false, 'text' => 'Improvement learning is embedded.', 'evidence' => 'Improvement backlog is not linked to incidents.', 'source_type' => 'interview', 'respondent_role' => 'Service Improvement Lead', 'document_source' => 'Improvement interview', 'confidence' => 'medium'],
        ];
    }

    private function structuredDraft(): array
    {
        return [
            'cover_letter' => 'Formal transmittal for SIR Tier 1',
            'executive_position' => 'Executive position for SIR Tier 1',
            'intelligence_dashboard' => [
                'overall' => ['score' => 2.8, 'rag' => 'Amber', 'context' => 'Transformation'],
                'indices' => [
                    'ssi' => ['value' => 2.41, 'interpretation' => 'SSI shows fundamental service stability pressure.'],
                    'smi' => ['value' => 2.87, 'interpretation' => 'SMI shows partial maturity.'],
                    'simi' => ['value' => 2.42, 'interpretation' => 'SIMI shows improvement is not embedded.'],
                    'bau_ri' => ['value' => 2.5, 'interpretation' => 'BAU readiness remains constrained.'],
                    'chi' => ['value' => 2.0, 'interpretation' => 'Compliance health is pressured.'],
                    'smi_simi_delta' => ['value' => 0.45, 'interpretation' => 'Maturity is not translating into improvement.'],
                ],
                'alert_flags' => ['D1 below 3.0'],
                'confidence_legend' => [
                    'high' => 'Confirmed by documentary evidence and interview',
                    'medium' => 'Supported by interview or partial evidence',
                    'low' => 'Single source or contradicted evidence',
                ],
            ],
            'intelligence_profile' => [[
                'domain_code' => 'D1',
                'domain_name' => 'D1 — Service Governance & Ownership',
                'score' => 2,
                'rag' => 'Red',
                'confidence' => 'High',
                'headline' => 'Service ownership gap',
                'evidence' => ['Ownership interview', 'Service governance pack'],
                'business_impact' => 'Incident ownership remains unclear for business users.',
                'compliance_dimension' => 'SMCR accountability evidence is weak.',
                'action' => 'Name service owners and decision rights.',
            ]],
            'risk_register' => [[
                'risk_title' => 'Major incident recurrence',
                'probability' => 'High',
                'impact' => 'High',
                'owner' => 'Service Owner',
                'current_control' => 'Incident review forum',
                'action' => 'Reinstate post-incident review discipline.',
            ]],
            'root_cause_analysis' => [
                'narrative' => 'Root cause narrative.',
                'primary_cause' => 'Weak service ownership.',
                'causal_chain' => ['Unclear ownership', 'Weak incident learning'],
            ],
            'priority_plan' => [
                '30_days' => [['action_title' => 'Reset ownership', 'owner' => 'Service Owner', 'deadline' => '30 days', 'done_condition' => 'Owners named']],
                '60_days' => [],
                '90_days' => [],
            ],
            'final_position' => 'Needs stabilisation before further transformation can be absorbed',
            'tier1_bridge' => 'Further document validation is required.',
            'compliance_risk_signals' => 'Operational intelligence only. Engage legal and compliance advisers.',
        ];
    }

    private function streamedAnthropicResponse(array $draft): string
    {
        $json = json_encode($draft, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $midpoint = intdiv(strlen($json), 2);

        return implode("\n\n", [
            'event: message_start' . "\n" . 'data: {"type":"message_start","message":{"id":"msg_sir_tier1","type":"message","role":"assistant","content":[]}}',
            'event: content_block_start' . "\n" . 'data: {"type":"content_block_start","index":0,"content_block":{"type":"text","text":""}}',
            'event: content_block_delta' . "\n" . 'data: ' . json_encode([
                'type' => 'content_block_delta',
                'index' => 0,
                'delta' => ['type' => 'text_delta', 'text' => substr($json, 0, $midpoint)],
            ]),
            'event: content_block_delta' . "\n" . 'data: ' . json_encode([
                'type' => 'content_block_delta',
                'index' => 0,
                'delta' => ['type' => 'text_delta', 'text' => substr($json, $midpoint)],
            ]),
            'event: content_block_stop' . "\n" . 'data: {"type":"content_block_stop","index":0}',
            'event: message_stop' . "\n" . 'data: {"type":"message_stop"}',
            '',
        ]);
    }
}
