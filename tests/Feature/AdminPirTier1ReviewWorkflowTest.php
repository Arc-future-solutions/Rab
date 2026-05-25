<?php

namespace Tests\Feature;

use App\Models\Assessment;
use App\Models\AssessmentFramework;
use App\Models\AssessmentPillar;
use App\Models\AssessmentQuestionBank;
use App\Models\Client;
use App\Models\User;
use App\Jobs\GenerateAdminFullReport;
use App\Services\ReportPdfService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Tests\TestCase;

class AdminPirTier1ReviewWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_pir_tier1_generate_endpoint_dispatches_job_and_does_not_call_anthropic(): void
    {
        $this->configureAnthropic();
        Queue::fake();
        Http::fake();

        $admin = $this->admin();
        $client = Client::create([
            'company_name' => 'Queued PIR Co',
            'primary_contact' => 'Alex Sponsor',
        ]);
        $assessment = Assessment::create([
            'client_id' => $client->id,
            'assessor_id' => $admin->id,
            'name' => 'Queued PIR Tier 1',
            'type' => 'PIR',
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
        $this->assertNotNull($assessment->ai_generation_started_at);
        $this->assertNull($assessment->ai_generation_completed_at);

        Queue::assertPushed(GenerateAdminFullReport::class, fn (GenerateAdminFullReport $job) => $job->assessmentId === $assessment->id);
        Http::assertNothingSent();
    }

    public function test_admin_can_create_score_generate_render_and_export_pir_tier1_review(): void
    {
        $this->configureAnthropic();
        $this->seedPirFramework();

        $admin = $this->admin();
        $client = Client::create([
            'company_name' => 'PIR Tier 1 Co',
            'primary_contact' => 'Alex Sponsor',
            'email' => 'alex@example.com',
        ]);

        $this->actingAs($admin)
            ->post(route('admin.assessments.store'), [
                'client_id' => $client->id,
                'type' => 'PIR',
                'report_tier' => 'Tier 1 Rapid',
                'name' => 'ERP Recovery Review',
                'target_entity' => 'ERP Replacement',
            ])
            ->assertRedirect();

        $assessment = Assessment::firstOrFail();
        $this->assertSame('PIR', $assessment->type);
        $this->assertSame('Tier 1 Rapid', $assessment->report_tier);

        $this->actingAs($admin)
            ->putJson(route('admin.assessments.autosave', $assessment), [
                'fields' => [
                    'delivery_stage' => 'Build',
                    'client_concerns' => 'Sponsor is concerned that governance and cutover evidence do not match status reporting.',
                    'regulatory_context' => 'fca_uk',
                    'sponsor_name' => 'Alex Sponsor',
                    'interview_count' => 3,
                    'documents_reviewed' => "RAID log\nSteering pack\nCutover draft",
                    'programme_value' => '1250000',
                    'reporting_accuracy_risk' => true,
                    'reporting_accuracy_evidence' => 'Steering pack is amber-green while RAID evidence shows unresolved critical risks.',
                    'top_5_risks' => "Governance decision delay\nCutover criteria gap",
                ],
            ])
            ->assertOk();

        foreach ($this->questionScores() as $row) {
            $this->actingAs($admin)
                ->putJson(route('admin.assessments.autosave', $assessment), [
                    'question_code' => $row['code'],
                    'pillar_name' => $row['pillar'] . ' — ' . $this->pillarNames()[$row['pillar']],
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

        $assessment->refresh()->load(['pillarScores', 'questionResponses']);

        $this->assertSame(11, $assessment->questionResponses->count());
        $this->assertSame('Amber', $assessment->rag_status);
        $this->assertGreaterThan(0, (float) $assessment->overall_score);
        $this->assertNotNull($assessment->bri);
        $this->assertNotNull($assessment->vri);
        $this->assertNotNull($assessment->dmi);
        $this->assertNotNull($assessment->rii);
        $this->assertNotNull($assessment->chi);
        $this->assertSame(2.0, (float) $assessment->chi);
        $this->assertArrayHasKey('P10', $assessment->pillarScores->mapWithKeys(
            fn ($score) => [explode(' — ', $score->name)[0] => (float) $score->score]
        )->all());

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

        Http::assertSent(function ($request) use ($assessment, &$capturedAnthropicRequest) {
            $payload = $request->data();
            $input = json_decode($payload['messages'][0]['content'][0]['text'], true);
            $aiPayload = $input['ai_payload'];
            $capturedAnthropicRequest = $payload;

            return $request->url() === 'https://example.test/v1/messages'
                && $payload['stream'] === true
                && $payload['system'] === config('ai.prompts.pir_full_tier1')
                && $input['prompt_key'] === 'pir_full_tier1'
                && $input['metadata']['assessment_id'] === $assessment->id
                && $input['metadata']['type'] === 'PIR_FULL'
                && $input['metadata']['is_full'] === true
                && isset($input['metadata']['submitted_at'])
                && ! array_key_exists('results', $input['metadata'])
                && ! array_key_exists('answers', $input['metadata'])
                && $aiPayload['framework'] === 'PIR'
                && $aiPayload['tier'] === 'Review'
                && is_float($aiPayload['overall_score'])
                && $aiPayload['rag_status'] === 'Amber'
                && isset($aiPayload['pillar_scores']['P1'], $aiPayload['pillar_scores']['P10'])
                && isset($aiPayload['bri'], $aiPayload['vri'], $aiPayload['dmi'], $aiPayload['rii'], $aiPayload['chi'])
                && isset($aiPayload['evidence_notes']['P1.F1'], $aiPayload['evidence_notes']['P1.F8'], $aiPayload['evidence_notes']['P5.F9'])
                && $aiPayload['stakeholder_notes'] === null
                && ! array_key_exists('sponsor_position', $aiPayload)
                && ! array_key_exists('operational_position', $aiPayload)
                && ! array_key_exists('divergence_areas', $aiPayload);
        });

        $this->assertPirTier1ClaudeRequestIsSufficient($capturedAnthropicRequest, $assessment);
        $debugPath = $this->writePirTier1ClaudeRequestDebugExport($capturedAnthropicRequest);
        $this->assertFileExists($debugPath);

        $assessment->refresh();
        $this->assertSame($draft, $assessment->ai_draft_json);
        $this->assertArrayHasKey('reporting_accuracy_risk_finding', $assessment->ai_draft_json);
        $this->assertNull($assessment->ai_draft_json['reporting_accuracy_risk_finding']);
        $this->assertSame(json_encode($draft, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), $assessment->ai_recommendation);
        $this->assertSame('completed', $assessment->status);
        $this->assertSame('completed', $assessment->ai_generation_status);
        $this->assertNull($assessment->ai_generation_error);
        $this->assertNotNull($assessment->ai_generation_started_at);
        $this->assertNotNull($assessment->ai_generation_completed_at);

        $this->actingAs($admin)
            ->get(route('admin.assessments.show', $assessment))
            ->assertOk()
            ->assertSee('Cover Letter')
            ->assertSee('Executive Position')
            ->assertSee('Intelligence Dashboard')
            ->assertSee('Intelligence Profile')
            ->assertSee('Reporting Accuracy Risk Finding')
            ->assertSee('Risk Register')
            ->assertSee('RAID Summary')
            ->assertSee('Root Cause Analysis')
            ->assertSee('Priority Plan')
            ->assertSee('Final Position')
            ->assertSee('Evidence Gaps / Recommended Deep-Dive')
            ->assertSee('Compliance Risk Signals')
            ->assertSee('Formal transmittal for PIR Tier 1')
            ->assertSee('Executive position for PIR Tier 1')
            ->assertSee('No reporting accuracy risk finding recorded.')
            ->assertSee('Governance decision delay')
            ->assertSee('Requires structured recovery before go-live can proceed');

        $fakeService = new class extends ReportPdfService {
            public ?int $assessmentId = null;

            public function download(Assessment $assessment): BinaryFileResponse
            {
                $this->assessmentId = $assessment->id;

                $directory = storage_path('framework/testing');
                if (! is_dir($directory)) {
                    mkdir($directory, 0755, true);
                }

                $path = $directory . '/pir-tier1-review.pdf';
                file_put_contents($path, '%PDF-1.4 fake');

                return response()->download($path, 'pir-tier1-review.pdf');
            }
        };
        $this->app->instance(ReportPdfService::class, $fakeService);

        $this->actingAs($admin)
            ->post(route('admin.assessments.exportPdf', $assessment))
            ->assertOk()
            ->assertDownload('pir-tier1-review.pdf');

        $this->assertSame($assessment->id, $fakeService->assessmentId);
    }

    private function assertPirTier1ClaudeRequestIsSufficient(?array $requestBody, Assessment $assessment): void
    {
        $this->assertIsArray($requestBody);
        $input = json_decode($requestBody['messages'][0]['content'][0]['text'], true);
        $aiPayload = $input['ai_payload'];
        $metadata = $input['metadata'];
        $userMessage = $requestBody['messages'][0]['content'][0]['text'];

        $this->assertSame('PIR', $aiPayload['framework']);
        $this->assertSame($assessment->id, $aiPayload['assessment_id']);
        $this->assertSame('PIR Tier 1 Co', $aiPayload['client_company']);
        $this->assertSame('ERP Replacement', $aiPayload['programme_name']);
        $this->assertSame('ERP Recovery Review', $aiPayload['programme_type']);
        $this->assertSame('Build', $aiPayload['delivery_stage']);
        $this->assertSame('Sponsor is concerned that governance and cutover evidence do not match status reporting.', $aiPayload['primary_concern']);
        $this->assertSame('fca_uk', $aiPayload['regulatory_context']);
        $this->assertSame($assessment->fresh()->updated_at->toDateString(), $aiPayload['assessment_date']);
        $this->assertIsFloat($aiPayload['overall_score']);
        $this->assertSame('Amber', $aiPayload['rag_status']);
        $this->assertSame('Review', $aiPayload['tier']);
        $this->assertSame('Admin', $aiPayload['consultant_name']);
        $this->assertSame('Alex Sponsor', $aiPayload['sponsor_name']);
        $this->assertSame(3, $aiPayload['interview_count']);
        $this->assertSame(['RAID log', 'Steering pack', 'Cutover draft'], $aiPayload['documents_reviewed']);
        $this->assertContains($aiPayload['confidence_level'], ['High', 'Medium', 'Low']);
        $this->assertSame(['Governance decision delay', 'Cutover criteria gap'], $aiPayload['emerging_issues']);
        $this->assertSame(1250000.0, (float) $aiPayload['programme_value']);
        $this->assertTrue($aiPayload['reporting_accuracy_risk']);
        $this->assertSame('Steering pack is amber-green while RAID evidence shows unresolved critical risks.', $aiPayload['reporting_accuracy_evidence']);
        $this->assertNull($aiPayload['stakeholder_notes']);

        foreach (['P1', 'P2', 'P3', 'P4', 'P5', 'P6', 'P7', 'P8', 'P9', 'P10'] as $pillarCode) {
            $this->assertArrayHasKey($pillarCode, $aiPayload['pillar_scores']);
            $this->assertArrayHasKey($pillarCode, $aiPayload['pillar_names']);
        }

        foreach (['bri', 'vri', 'dmi', 'rii', 'chi'] as $indexKey) {
            $this->assertArrayHasKey($indexKey, $aiPayload);
            $this->assertIsNumeric($aiPayload[$indexKey]);
        }

        $this->assertNotEmpty($aiPayload['evidence_notes']);
        $this->assertArrayHasKey('P1.F1', $aiPayload['evidence_notes']);
        $this->assertArrayHasKey('P1.F8', $aiPayload['evidence_notes']);
        $this->assertArrayHasKey('P5.F9', $aiPayload['evidence_notes']);

        $this->assertNotEmpty($aiPayload['question_responses']);
        foreach ($aiPayload['question_responses'] as $response) {
            $this->assertSame(['id', 'pillar_code', 'score', 'is_compliance'], array_keys($response));
        }

        $this->assertArrayHasKey('compliance_question_scores', $aiPayload);
        $this->assertArrayHasKey('P1.F8', $aiPayload['compliance_question_scores']);
        $this->assertArrayHasKey('P5.F9', $aiPayload['compliance_question_scores']);

        $this->assertSame('PIR_FULL', $metadata['type']);
        $this->assertTrue($metadata['is_full']);
        $this->assertSame($assessment->id, $metadata['assessment_id']);
        $this->assertArrayNotHasKey('results', $metadata);
        $this->assertArrayNotHasKey('answers', $metadata);
        $this->assertStringNotContainsString('detailed_responses', $userMessage);
        $this->assertStringNotContainsString('hidden_risk', $userMessage);
        $this->assertStringNotContainsString('score_anchors', $userMessage);
        $this->assertStringNotContainsString('question_text', $userMessage);
        $this->assertStringNotContainsString('Sponsor decision ownership is clear.', $userMessage);
        $this->assertStringNotContainsString('Cutover readiness criteria are defined.', $userMessage);
    }

    private function writePirTier1ClaudeRequestDebugExport(array $requestBody): string
    {
        $input = json_decode($requestBody['messages'][0]['content'][0]['text'], true);
        $path = storage_path('app/debug/pir-tier1-claude-request.json');

        File::ensureDirectoryExists(dirname($path));
        File::put($path, json_encode([
            'model' => $requestBody['model'],
            'max_tokens' => $requestBody['max_tokens'],
            'stream' => $requestBody['stream'] ?? false,
            'system_prompt_length' => strlen((string) $requestBody['system']),
            'user_payload_length' => strlen((string) $requestBody['messages'][0]['content'][0]['text']),
            'metadata' => $input['metadata'],
            'ai_payload' => $input['ai_payload'],
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

        return $path;
    }

    private function configureAnthropic(): void
    {
        Config::set('services.anthropic.api_key', 'test-anthropic-key');
        Config::set('services.anthropic.base_url', 'https://example.test/v1');
        Config::set('services.anthropic.model', 'claude-sonnet-4-5');
        Config::set('services.anthropic.version', '2023-06-01');
    }

    private function streamedAnthropicResponse(array $draft): string
    {
        $json = json_encode($draft, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $midpoint = intdiv(strlen($json), 2);

        return implode("\n\n", [
            'event: message_start' . "\n" . 'data: {"type":"message_start","message":{"id":"msg_pir_tier1","type":"message","role":"assistant","content":[]}}',
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
            'event: message_delta' . "\n" . 'data: {"type":"message_delta","delta":{"stop_reason":"end_turn","stop_sequence":null}}',
            'event: message_stop' . "\n" . 'data: {"type":"message_stop"}',
            '',
        ]);
    }

    private function admin(): User
    {
        return User::create([
            'name' => 'Admin',
            'email' => 'admin-pir-tier1@example.com',
            'password' => 'password',
            'role' => 'admin',
        ]);
    }

    private function seedPirFramework(): void
    {
        $framework = AssessmentFramework::create([
            'code' => 'PIR',
            'name' => 'Programme Intelligence Review',
        ]);

        foreach ($this->pillarNames() as $code => $name) {
            AssessmentPillar::create([
                'framework_id' => $framework->id,
                'code' => $code,
                'name' => $name,
                'weight' => $this->pillarWeights()[$code],
                'is_critical' => in_array($code, ['P1', 'P5', 'P7'], true),
                'display_order' => (int) substr($code, 1),
            ]);
        }

        foreach ($this->questionScores() as $index => $row) {
            AssessmentQuestionBank::create([
                'framework_id' => $framework->id,
                'pillar_id' => AssessmentPillar::where('framework_id', $framework->id)->where('code', $row['pillar'])->firstOrFail()->id,
                'level' => 'full',
                'question_code' => $row['code'],
                'question_text' => $row['text'],
                'is_compliance' => $row['is_compliance'],
                'is_active' => true,
                'display_order' => $index + 1,
            ]);
        }
    }

    private function pillarNames(): array
    {
        return [
            'P1' => 'Governance & Decision-Making',
            'P2' => 'Planning, Stage Gates & Delivery Control',
            'P3' => 'Business Alignment, Value & Financial Control',
            'P4' => 'Change Management, Training & Adoption',
            'P5' => 'Data Readiness, Migration & GDPR',
            'P6' => 'Solution, Process Fit & UAT',
            'P7' => 'Cutover, Go-Live, Decommissioning & Archiving',
            'P8' => 'Delivery Capability, Security & RACI',
            'P9' => 'Operational, Automation Readiness & Data Archiving',
            'P10' => 'Digital & Transformation Maturity',
        ];
    }

    private function pillarWeights(): array
    {
        return [
            'P1' => 1.5,
            'P2' => 1.4,
            'P3' => 1.3,
            'P4' => 1.2,
            'P5' => 1.2,
            'P6' => 1.1,
            'P7' => 1.2,
            'P8' => 1.1,
            'P9' => 1.0,
            'P10' => 1.1,
        ];
    }

    private function questionScores(): array
    {
        return [
            ['code' => 'P1.F1', 'pillar' => 'P1', 'score' => 2, 'is_compliance' => false, 'text' => 'Sponsor decision ownership is clear.', 'evidence' => 'Sponsor confirmed decisions are still escalated informally.', 'source_type' => 'interview', 'respondent_role' => 'Programme Sponsor', 'document_source' => 'Sponsor interview', 'confidence' => 'high'],
            ['code' => 'P1.F8', 'pillar' => 'P1', 'score' => 2, 'is_compliance' => true, 'text' => 'Decision logs and action ownership are maintained.', 'evidence' => 'Decision log has four overdue actions with no owner.', 'source_type' => 'document', 'respondent_role' => 'PMO Lead', 'document_source' => 'Decision log', 'confidence' => 'high'],
            ['code' => 'P2.F4', 'pillar' => 'P2', 'score' => 3, 'is_compliance' => false, 'text' => 'Critical path dependencies are visible.', 'evidence' => 'Integrated plan exists but data cleansing dates are not baselined.', 'source_type' => 'document', 'respondent_role' => 'Programme Planner', 'document_source' => 'Integrated plan', 'confidence' => 'medium'],
            ['code' => 'P3.F11', 'pillar' => 'P3', 'score' => 2, 'is_compliance' => true, 'text' => 'Financial controls support accurate reporting.', 'evidence' => 'Steering pack status conflicts with cost exposure in RAID.', 'source_type' => 'document', 'respondent_role' => 'Finance Lead', 'document_source' => 'Steering pack', 'confidence' => 'high'],
            ['code' => 'P4.F1', 'pillar' => 'P4', 'score' => 3, 'is_compliance' => false, 'text' => 'Change impacts are understood.', 'evidence' => 'Change plan exists but business ownership is partial.', 'source_type' => 'workshop', 'respondent_role' => 'Change Lead', 'document_source' => 'Change workshop', 'confidence' => 'medium'],
            ['code' => 'P5.F9', 'pillar' => 'P5', 'score' => 2, 'is_compliance' => true, 'text' => 'GDPR and data retention requirements are understood.', 'evidence' => 'Retention decisions remain pending legal confirmation.', 'source_type' => 'document', 'respondent_role' => 'Data Protection Officer', 'document_source' => 'GDPR checklist', 'confidence' => 'medium'],
            ['code' => 'P6.F1', 'pillar' => 'P6', 'score' => 3, 'is_compliance' => false, 'text' => 'Solution fit is understood.', 'evidence' => 'Fit gaps are logged but workarounds remain open.', 'source_type' => 'document', 'respondent_role' => 'Solution Architect', 'document_source' => 'Design log', 'confidence' => 'medium'],
            ['code' => 'P7.F1', 'pillar' => 'P7', 'score' => 2, 'is_compliance' => false, 'text' => 'Cutover readiness criteria are defined.', 'evidence' => 'Go/no-go criteria lack objective reconciliation thresholds.', 'source_type' => 'document', 'respondent_role' => 'Cutover Manager', 'document_source' => 'Cutover draft', 'confidence' => 'high'],
            ['code' => 'P8.F1', 'pillar' => 'P8', 'score' => 4, 'is_compliance' => false, 'text' => 'Delivery roles are assigned.', 'evidence' => 'RACI is approved for delivery teams.', 'source_type' => 'document', 'respondent_role' => 'Delivery Lead', 'document_source' => 'RACI', 'confidence' => 'high'],
            ['code' => 'P9.F1', 'pillar' => 'P9', 'score' => 3, 'is_compliance' => false, 'text' => 'Operational reporting requirements are clear.', 'evidence' => 'Reporting requirements are described but not in acceptance criteria.', 'source_type' => 'interview', 'respondent_role' => 'Service Transition Lead', 'document_source' => 'Transition interview', 'confidence' => 'low'],
            ['code' => 'P10.F1', 'pillar' => 'P10', 'score' => 4, 'is_compliance' => false, 'text' => 'Transformation maturity is actively improved.', 'evidence' => 'Adoption metrics have named owners.', 'source_type' => 'workshop', 'respondent_role' => 'Transformation Lead', 'document_source' => 'Maturity workshop', 'confidence' => 'medium'],
        ];
    }

    private function structuredDraft(): array
    {
        return [
            'cover_letter' => 'Formal transmittal for PIR Tier 1',
            'executive_position' => 'Executive position for PIR Tier 1',
            'intelligence_dashboard' => [
                'overall' => ['score' => 2.8, 'rag' => 'Amber', 'stage' => 'Build'],
                'indices' => [
                    'BRI' => ['score' => 2.2, 'interpretation' => 'Readiness is constrained.'],
                    'VRI' => ['score' => 2.4, 'interpretation' => 'Value confidence is weak.'],
                    'DMI' => ['score' => 3.5, 'interpretation' => 'Digital maturity is partial.'],
                    'RII' => ['score' => 2.5, 'interpretation' => 'Risk controls are inconsistent.'],
                    'CHI' => ['score' => 2, 'interpretation' => 'Compliance health is pressured.'],
                ],
                'alert_flags' => ['P1 below 3.0'],
                'confidence_legend' => [
                    ['label' => 'High', 'color' => '#166534', 'definition' => 'Confirmed by documentary evidence and interview'],
                ],
            ],
            'intelligence_profile' => [[
                'pillar_code' => 'P1',
                'pillar_name' => 'P1 — Governance & Decision-Making',
                'score' => 2,
                'rag' => 'Red',
                'confidence' => 'High',
                'headline' => 'Governance decision delay',
                'evidence' => ['Decision log', 'Sponsor interview'],
                'business_impact' => 'Decision delay is affecting cutover readiness.',
                'compliance_dimension' => null,
                'action' => 'Sponsor to reset decision ownership.',
            ]],
            'reporting_accuracy_risk_finding' => null,
            'risk_register' => [[
                'risk_title' => 'Governance decision delay',
                'probability' => 'High',
                'impact' => 'High',
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
                'narrative' => 'Root cause narrative.',
                'primary_cause' => 'Weak decision ownership.',
                'causal_chain' => ['Unclear ownership', 'Delayed decisions'],
            ],
            'priority_plan' => [
                '30_days' => [['action_title' => 'Reset decisions', 'owner' => 'Sponsor', 'deadline' => '30 days', 'done_condition' => 'Owners named']],
                '60_days' => [],
                '90_days' => [],
            ],
            'final_position' => 'Requires structured recovery before go-live can proceed',
            'tier1_bridge' => 'Further document validation is required.',
            'compliance_risk_signals' => null,
        ];
    }
}
