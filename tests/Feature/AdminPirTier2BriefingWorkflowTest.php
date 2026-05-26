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
use App\Services\AiReportGenerationService;
use App\Services\ReportPdfService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Tests\TestCase;

class AdminPirTier2BriefingWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_pir_tier2_generate_endpoint_dispatches_shared_job_and_does_not_call_anthropic_inline(): void
    {
        $this->configureAnthropic();
        Queue::fake();
        Http::fake();

        $admin = $this->admin();
        $client = Client::create([
            'company_name' => 'Queued PIR Tier 2 Co',
            'primary_contact' => 'Alex Sponsor',
        ]);
        $assessment = Assessment::create([
            'client_id' => $client->id,
            'assessor_id' => $admin->id,
            'name' => 'Queued PIR Tier 2',
            'type' => 'PIR',
            'report_tier' => 'Tier 2 Full',
            'overall_score' => 3.2,
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

    public function test_generate_streamed_returns_raw_full_report_text_without_snapshot_normalisation(): void
    {
        $this->configureAnthropic();
        $draft = $this->structuredDraft();
        $streamedText = 'Preface before report '
            . json_encode(['opening' => 'Snapshot-style object should not replace output.'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
            . "\n"
            . json_encode($draft, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        Http::fake([
            'example.test/*' => Http::response($this->streamedTextResponse($streamedText), 200, [
                'Content-Type' => 'text/event-stream',
            ]),
        ]);

        $response = app(AiReportGenerationService::class)->generateStreamed(
            'pir_full_tier2',
            config('ai.prompts.pir_full_tier2'),
            ['framework' => 'PIR', 'tier' => 'Briefing'],
            ['type' => 'PIR_FULL', 'is_full' => true]
        );

        $this->assertSame($streamedText, $response['output']);
        $this->assertSame($streamedText, $response['output_raw']);
        $this->assertSame(strlen($streamedText), $response['stream_metadata']['final_text_length']);
        $this->assertArrayNotHasKey('report', $response);
        $this->assertArrayNotHasKey('snapshot_report_json', $response);
    }

    public function test_admin_full_report_does_not_use_snapshot_report_json_as_fallback(): void
    {
        $assessment = $this->pirTier2Assessment();
        $draft = $this->structuredDraft();

        $this->app->instance(AiReportGenerationService::class, $this->fakeAiService([
            'output' => 'not json',
            'provider_response_id' => 'msg_snapshot_leak',
            'prompt_key' => 'pir_full_tier2',
            'stream' => true,
            'stream_metadata' => ['stream' => true],
            'snapshot_report_json' => $draft,
        ]));

        (new GenerateAdminFullReport($assessment->id))->handle(app(AdminFullReportGenerationService::class));

        $assessment->refresh();

        $this->assertNull($assessment->ai_draft_json);
        $this->assertNull($assessment->ai_recommendation);
        $this->assertSame('failed', $assessment->ai_generation_status);
        $this->assertSame('AI response was received but did not include a usable report payload.', $assessment->ai_generation_error);
    }

    public function test_admin_can_create_score_generate_render_and_export_pir_tier2_briefing(): void
    {
        $this->configureAnthropic();
        $this->seedPirFramework();

        $admin = $this->admin();
        $client = Client::create([
            'company_name' => 'PIR Tier 2 Co',
            'primary_contact' => 'Alex Sponsor',
            'email' => 'alex@example.com',
        ]);

        $this->actingAs($admin)
            ->post(route('admin.assessments.store'), [
                'client_id' => $client->id,
                'type' => 'PIR',
                'report_tier' => 'Tier 2 Full',
                'name' => 'ERP Recovery Briefing',
                'target_entity' => 'ERP Replacement',
            ])
            ->assertRedirect();

        $assessment = Assessment::firstOrFail();
        $this->assertSame('PIR', $assessment->type);
        $this->assertSame('Tier 2 Full', $assessment->report_tier);

        $this->actingAs($admin)
            ->putJson(route('admin.assessments.autosave', $assessment), [
                'fields' => [
                    'delivery_stage' => 'Build',
                    'client_concerns' => 'Sponsor is concerned that delivery reporting and operational evidence diverge.',
                    'regulatory_context' => 'fca_uk',
                    'sponsor_name' => 'Alex Sponsor',
                    'interview_count' => 6,
                    'documents_reviewed' => "RAID log\nSteering pack\nCutover draft\nFinance tracker",
                    'programme_value' => '2500000',
                    'reporting_accuracy_risk' => true,
                    'reporting_accuracy_evidence' => 'Board pack remains amber-green while operational evidence shows unresolved blockers.',
                    'sponsor_position' => 'Sponsor expects the programme can remain amber-green if weekly actions continue.',
                    'operational_position' => 'Delivery leads report unresolved cutover and data risks that are absent from sponsor reporting.',
                    'divergence_areas' => "Status reporting\nCutover readiness\nData migration evidence",
                    'top_5_risks' => "Sponsor reporting divergence\nCutover decision delay\nData migration evidence gap",
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
                    'stakeholder_divergence_note' => $row['stakeholder_divergence_note'],
                    'confidence' => $row['confidence'],
                ])
                ->assertOk();
        }

        $assessment->refresh()->load(['pillarScores', 'questionResponses']);

        $this->assertSame(11, $assessment->questionResponses->count());
        $this->assertSame('Amber', $assessment->rag_status);
        $this->assertGreaterThan(0, (float) $assessment->overall_score);
        $this->assertSame([
            'Status reporting',
            'Cutover readiness',
            'Data migration evidence',
        ], $assessment->divergence_areas);

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
                && $payload['system'] === config('ai.prompts.pir_full_tier2')
                && $input['prompt_key'] === 'pir_full_tier2'
                && $input['metadata']['assessment_id'] === $assessment->id
                && $input['metadata']['type'] === 'PIR_FULL'
                && $input['metadata']['is_full'] === true
                && ! array_key_exists('results', $input['metadata'])
                && ! array_key_exists('answers', $input['metadata'])
                && $aiPayload['framework'] === 'PIR'
                && $aiPayload['tier'] === 'Briefing'
                && is_array($aiPayload['stakeholder_notes'])
                && $aiPayload['stakeholder_notes']['sponsor_position'] === 'Sponsor expects the programme can remain amber-green if weekly actions continue.'
                && $aiPayload['stakeholder_notes']['operational_position'] === 'Delivery leads report unresolved cutover and data risks that are absent from sponsor reporting.'
                && $aiPayload['stakeholder_notes']['divergence_areas'] === ['Status reporting', 'Cutover readiness', 'Data migration evidence']
                && isset($aiPayload['evidence_notes']['P1.F1']['stakeholder_divergence_note'])
                && $aiPayload['evidence_notes']['P1.F1']['stakeholder_divergence_note'] === 'Sponsor sees governance as controlled; delivery lead reports decisions are informal.'
                && isset($aiPayload['evidence_notes']['P7.F1']['stakeholder_divergence_note'])
                && $aiPayload['evidence_notes']['P7.F1']['stakeholder_divergence_note'] === 'Sponsor expects cutover to hold; cutover manager reports readiness criteria are unresolved.';
        });

        $this->assertPirTier2ClaudeRequestIsSufficient($capturedAnthropicRequest, $assessment);

        $assessment->refresh();
        $this->assertSame($draft, $assessment->ai_draft_json);
        $this->assertArrayHasKey('stakeholder_intelligence', $assessment->ai_draft_json);
        $this->assertArrayHasKey('evidence_validated_statement', $assessment->ai_draft_json);
        $this->assertSame(json_encode($draft, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), $assessment->ai_recommendation);
        $this->assertSame('completed', $assessment->status);
        $this->assertSame('completed', $assessment->ai_generation_status);
        $this->assertNull($assessment->ai_generation_error);
        $this->assertNotNull($assessment->ai_generation_started_at);
        $this->assertNotNull($assessment->ai_generation_completed_at);

        $this->actingAs($admin)
            ->get(route('admin.assessments.show', $assessment))
            ->assertOk()
            ->assertSee('Generated Programme Intelligence Briefing Preview')
            ->assertSee('Stakeholder Intelligence')
            ->assertSee('Sponsor Position')
            ->assertSee('Operational Position')
            ->assertSee('Governance Implication')
            ->assertSee('Evidence Validated Statement')
            ->assertSee('Sponsor confidence is ahead of operational evidence')
            ->assertSee('Status reporting')
            ->assertSee('Sponsor reports amber-green.')
            ->assertSee('Delivery leads report unresolved blockers.')
            ->assertSee('Sponsor must reset decision rights and evidence standards.')
            ->assertSee('The evidence has been validated through direct document review and interview.')
            ->assertDontSee('Tier 1 Bridge')
            ->assertDontSee('Evidence Gaps / Recommended Deep-Dive');

        $fakeService = new class extends ReportPdfService {
            public ?int $assessmentId = null;

            public function download(Assessment $assessment): BinaryFileResponse
            {
                $this->assessmentId = $assessment->id;

                $directory = storage_path('framework/testing');
                if (! is_dir($directory)) {
                    mkdir($directory, 0755, true);
                }

                $path = $directory . '/pir-tier2-briefing.pdf';
                file_put_contents($path, '%PDF-1.4 fake');

                return response()->download($path, 'pir-tier2-briefing.pdf');
            }
        };
        $this->app->instance(ReportPdfService::class, $fakeService);

        $this->actingAs($admin)
            ->post(route('admin.assessments.exportPdf', $assessment))
            ->assertOk()
            ->assertDownload('pir-tier2-briefing.pdf');

        $this->assertSame($assessment->id, $fakeService->assessmentId);
    }

    public function test_streaming_wrapper_with_output_json_for_pir_tier2_stores_structured_report(): void
    {
        $draft = $this->structuredDraft();
        $assessment = $this->pirTier2Assessment();

        $this->app->instance(AiReportGenerationService::class, $this->fakeAiService([
            'output' => json_encode($draft, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'provider_response_id' => 'msg_wrapper',
            'prompt_key' => 'pir_full_tier2',
            'stream' => true,
            'stream_metadata' => [
                'model' => 'claude-sonnet-4-6',
                'max_tokens' => 8192,
                'stream' => true,
                'received_chunk_count' => 12,
                'stream_event_count' => 357,
                'text_delta_count' => 347,
                'final_text_length' => 32503,
            ],
        ]));

        (new GenerateAdminFullReport($assessment->id))->handle(app(AdminFullReportGenerationService::class));

        $assessment->refresh();

        foreach ([
            'cover_letter',
            'executive_position',
            'intelligence_dashboard',
            'intelligence_profile',
            'stakeholder_intelligence',
            'evidence_validated_statement',
            'risk_register',
            'raid_summary',
            'root_cause_analysis',
            'priority_plan',
            'final_position',
        ] as $key) {
            $this->assertArrayHasKey($key, $assessment->ai_draft_json);
        }

        $this->assertSame($draft['stakeholder_intelligence'], $assessment->ai_draft_json['stakeholder_intelligence']);
        $this->assertArrayNotHasKey('output', $assessment->ai_draft_json);
        $this->assertArrayNotHasKey('provider_response_id', $assessment->ai_draft_json);
        $this->assertArrayNotHasKey('stream_metadata', $assessment->ai_draft_json);
        $this->assertSame('completed', $assessment->ai_generation_status);
        $this->assertNull($assessment->ai_generation_error);
        $this->assertNotNull($assessment->ai_generation_completed_at);
    }

    public function test_streaming_wrapper_output_can_contain_fenced_or_surrounding_json(): void
    {
        $draft = $this->structuredDraft();
        $assessment = $this->pirTier2Assessment();
        $output = "Here is the report JSON:\n\n```json\n"
            . json_encode($draft, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
            . "\n```\n";

        $this->app->instance(AiReportGenerationService::class, $this->fakeAiService([
            'output' => $output,
            'provider_response_id' => 'msg_fenced',
            'prompt_key' => 'pir_full_tier2',
            'stream' => true,
            'stream_metadata' => ['stream' => true],
        ]));

        (new GenerateAdminFullReport($assessment->id))->handle(app(AdminFullReportGenerationService::class));

        $assessment->refresh();

        $this->assertSame($draft, $assessment->ai_draft_json);
        $this->assertSame('completed', $assessment->ai_generation_status);
    }

    public function test_streaming_wrapper_output_with_preliminary_json_then_tier2_report_succeeds(): void
    {
        $draft = $this->structuredDraft();
        $assessment = $this->pirTier2Assessment();
        $output = 'Diagnostic: {"status":"prepared","prompt_key":"pir_full_tier2"}'
            . "\n\nActual report:\n"
            . json_encode($draft, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
            . "\nEnd.";

        $this->app->instance(AiReportGenerationService::class, $this->fakeAiService([
            'output' => $output,
            'provider_response_id' => 'msg_preliminary_json',
            'prompt_key' => 'pir_full_tier2',
            'stream' => true,
            'stream_metadata' => ['stream' => true],
        ]));

        (new GenerateAdminFullReport($assessment->id))->handle(app(AdminFullReportGenerationService::class));

        $assessment->refresh();

        $this->assertSame($draft, $assessment->ai_draft_json);
        $this->assertSame('completed', $assessment->ai_generation_status);
    }

    public function test_streaming_response_wrapper_keys_alone_are_not_treated_as_report(): void
    {
        $assessment = $this->pirTier2Assessment();

        $this->app->instance(AiReportGenerationService::class, $this->fakeAiService([
            'output' => '{"metadata_only":{"status":"prepared"}}',
            'provider_response_id' => 'msg_metadata_only',
            'prompt_key' => 'pir_full_tier2',
            'stream' => true,
            'stream_metadata' => ['stream' => true],
        ]));

        (new GenerateAdminFullReport($assessment->id))->handle(app(AdminFullReportGenerationService::class));

        $assessment->refresh();

        $this->assertNull($assessment->ai_draft_json);
        $this->assertNull($assessment->ai_recommendation);
        $this->assertSame('failed', $assessment->ai_generation_status);
        $this->assertSame('AI response was received but did not include a usable report payload.', $assessment->ai_generation_error);
    }

    public function test_pir_tier2_full_report_rejects_snapshot_style_keys(): void
    {
        $assessment = $this->pirTier2Assessment();

        $this->app->instance(AiReportGenerationService::class, $this->fakeAiService([
            'output_raw' => json_encode([
                'opening' => 'Snapshot opening',
                'overall_position' => 'Snapshot position',
                'key_themes' => ['Governance divergence', 'Evidence confidence'],
                'instruction_to_sponsor' => 'Use the wrong briefing schema',
                'primary_concern_statement' => 'Snapshot concern',
                'confidence_statement' => 'Snapshot confidence',
                'scope_statement' => 'Snapshot scope',
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'provider_response_id' => 'msg_snapshot_keys',
            'prompt_key' => 'pir_full_tier2',
            'stream' => true,
            'stream_metadata' => ['stream' => true],
        ]));

        (new GenerateAdminFullReport($assessment->id))->handle(app(AdminFullReportGenerationService::class));

        $assessment->refresh();

        $this->assertNull($assessment->ai_draft_json);
        $this->assertSame('failed', $assessment->ai_generation_status);
        $this->assertSame('AI response was received but did not include a usable report payload.', $assessment->ai_generation_error);
    }

    public function test_invalid_streaming_output_fails_without_overwriting_existing_report(): void
    {
        $existingDraft = $this->structuredDraft();
        $assessment = $this->pirTier2Assessment([
            'ai_draft_json' => $existingDraft,
            'ai_recommendation' => json_encode($existingDraft, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'status' => 'completed',
            'ai_generation_status' => 'generating',
            'ai_generation_error' => null,
        ]);

        $this->app->instance(AiReportGenerationService::class, $this->fakeAiService([
            'output' => 'This is not JSON and has no structured report object.',
            'provider_response_id' => 'msg_invalid',
            'prompt_key' => 'pir_full_tier2',
            'stream' => true,
            'stream_metadata' => ['stream' => true],
        ]));

        (new GenerateAdminFullReport($assessment->id))->handle(app(AdminFullReportGenerationService::class));

        $assessment->refresh();

        $this->assertSame($existingDraft, $assessment->ai_draft_json);
        $this->assertSame(json_encode($existingDraft, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), $assessment->ai_recommendation);
        $this->assertSame('completed', $assessment->status);
        $this->assertSame('failed', $assessment->ai_generation_status);
        $this->assertSame('AI response was received but did not include a usable report payload.', $assessment->ai_generation_error);
    }

    public function test_failed_assessment_17_streaming_output_writes_private_debug_file_and_safe_diagnostics(): void
    {
        Log::spy();

        $path = storage_path('app/private/debug/assessment-17-pir-tier2-stream-output.txt');
        if (file_exists($path)) {
            unlink($path);
        }

        $assessment = $this->pirTier2Assessment([
            'id' => 17,
            'name' => 'Assessment 17',
        ]);
        $output = 'Diagnostic: {"status":"prepared"} no report payload.';

        $this->app->instance(AiReportGenerationService::class, $this->fakeAiService([
            'output' => $output,
            'provider_response_id' => 'msg_invalid_17',
            'prompt_key' => 'pir_full_tier2',
            'stream' => true,
            'stream_metadata' => [
                'model' => 'claude-sonnet-4-6',
                'max_tokens' => 8192,
                'stream' => true,
                'received_chunk_count' => 10,
                'stream_event_count' => 356,
                'text_delta_count' => 346,
                'final_text_length' => strlen($output),
            ],
        ]));

        (new GenerateAdminFullReport($assessment->id))->handle(app(AdminFullReportGenerationService::class));

        $assessment->refresh();

        $this->assertSame('failed', $assessment->ai_generation_status);
        $this->assertFileExists($path);
        $this->assertSame($output, File::get($path));

        Log::shouldHaveReceived('warning')
            ->with('Streamed full-report output diagnostics', \Mockery::on(function (array $context) use ($assessment, $output) {
                return $context['assessment_id'] === $assessment->id
                    && $context['prompt_key'] === 'pir_full_tier2'
                    && $context['final_streamed_text_length'] === strlen($output)
                    && $context['normalisation_input_length'] === strlen($output)
                    && $context['normalisation_input_source'] === 'output'
                    && $context['output_length'] === strlen($output)
                    && $context['output_first_non_whitespace_char'] === 'D'
                    && $context['output_contains_open_brace'] === true
                    && $context['output_contains_fenced_json'] === false
                    && $context['json_decode_direct_success'] === false
                    && $context['balanced_json_extraction_success'] === true
                    && $context['parsed_top_level_keys'] === ['status']
                    && $context['required_report_key_matches']['found'] === []
                    && in_array('cover_letter', $context['required_report_key_matches']['missing'], true);
            }))
            ->once();
    }

    private function assertPirTier2ClaudeRequestIsSufficient(?array $requestBody, Assessment $assessment): void
    {
        $this->assertIsArray($requestBody);
        $input = json_decode($requestBody['messages'][0]['content'][0]['text'], true);
        $aiPayload = $input['ai_payload'];
        $metadata = $input['metadata'];
        $userMessage = $requestBody['messages'][0]['content'][0]['text'];

        $this->assertSame('PIR', $aiPayload['framework']);
        $this->assertSame($assessment->id, $aiPayload['assessment_id']);
        $this->assertSame('PIR Tier 2 Co', $aiPayload['client_company']);
        $this->assertSame('ERP Replacement', $aiPayload['programme_name']);
        $this->assertSame('ERP Recovery Briefing', $aiPayload['programme_type']);
        $this->assertSame('Build', $aiPayload['delivery_stage']);
        $this->assertSame('Briefing', $aiPayload['tier']);
        $this->assertSame(6, $aiPayload['interview_count']);
        $this->assertSame(['RAID log', 'Steering pack', 'Cutover draft', 'Finance tracker'], $aiPayload['documents_reviewed']);
        $this->assertSame(2500000.0, (float) $aiPayload['programme_value']);
        $this->assertTrue($aiPayload['reporting_accuracy_risk']);
        $this->assertIsArray($aiPayload['stakeholder_notes']);
        $this->assertSame('PIR_FULL', $metadata['type']);
        $this->assertTrue($metadata['is_full']);
        $this->assertSame($assessment->id, $metadata['assessment_id']);
        $this->assertArrayNotHasKey('results', $metadata);
        $this->assertArrayNotHasKey('answers', $metadata);
        $this->assertStringNotContainsString('detailed_responses', $userMessage);
        $this->assertStringNotContainsString('score_anchors', $userMessage);
        $this->assertStringNotContainsString('question_text', $userMessage);
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
        return $this->streamedTextResponse($json);
    }

    private function streamedTextResponse(string $text): string
    {
        $midpoint = intdiv(strlen($text), 2);

        return implode("\n\n", [
            'event: message_start' . "\n" . 'data: {"type":"message_start","message":{"id":"msg_pir_tier2","type":"message","role":"assistant","content":[]}}',
            'event: content_block_start' . "\n" . 'data: {"type":"content_block_start","index":0,"content_block":{"type":"text","text":""}}',
            'event: content_block_delta' . "\n" . 'data: ' . json_encode([
                'type' => 'content_block_delta',
                'index' => 0,
                'delta' => ['type' => 'text_delta', 'text' => substr($text, 0, $midpoint)],
            ]),
            'event: content_block_delta' . "\n" . 'data: ' . json_encode([
                'type' => 'content_block_delta',
                'index' => 0,
                'delta' => ['type' => 'text_delta', 'text' => substr($text, $midpoint)],
            ]),
            'event: content_block_stop' . "\n" . 'data: {"type":"content_block_stop","index":0}',
            'event: message_stop' . "\n" . 'data: {"type":"message_stop"}',
            '',
        ]);
    }

    private function admin(): User
    {
        return User::create([
            'name' => 'Admin',
            'email' => 'admin-pir-tier2@example.com',
            'password' => 'password',
            'role' => 'admin',
        ]);
    }

    private function pirTier2Assessment(array $overrides = []): Assessment
    {
        $client = Client::create([
            'company_name' => 'PIR Tier 2 Wrapper Co',
            'primary_contact' => 'Alex Sponsor',
        ]);

        return Assessment::create(array_merge([
            'client_id' => $client->id,
            'name' => 'Wrapper PIR Tier 2',
            'target_entity' => 'ERP Replacement',
            'type' => 'PIR',
            'report_tier' => 'Tier 2 Full',
            'overall_score' => 3.1,
            'rag_status' => 'Amber',
            'status' => 'draft',
            'sponsor_position' => 'Sponsor expects amber-green status.',
            'operational_position' => 'Delivery leads report unresolved blockers.',
            'divergence_areas' => ['Status reporting'],
        ], $overrides));
    }

    private function fakeAiService(array $response): AiReportGenerationService
    {
        return new class($response) extends AiReportGenerationService {
            public function __construct(private readonly array $response)
            {
            }

            public function generateStreamed(string $promptKey, string $systemPrompt, array $aiPayload, array $metadata = []): array
            {
                return $this->response;
            }

            public function generate(string $promptKey, string $systemPrompt, array $aiPayload, array $metadata = []): array
            {
                return $this->response;
            }
        };
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
            ['code' => 'P1.F1', 'pillar' => 'P1', 'score' => 2, 'is_compliance' => false, 'text' => 'Sponsor decision ownership is clear.', 'evidence' => 'Sponsor confirmed decisions are still escalated informally.', 'source_type' => 'interview', 'respondent_role' => 'Programme Sponsor', 'document_source' => 'Sponsor interview', 'confidence' => 'high', 'stakeholder_divergence_note' => 'Sponsor sees governance as controlled; delivery lead reports decisions are informal.'],
            ['code' => 'P1.F8', 'pillar' => 'P1', 'score' => 2, 'is_compliance' => true, 'text' => 'Decision logs and action ownership are maintained.', 'evidence' => 'Decision log has four overdue actions with no owner.', 'source_type' => 'document', 'respondent_role' => 'PMO Lead', 'document_source' => 'Decision log', 'confidence' => 'high', 'stakeholder_divergence_note' => 'Sponsor believes actions are owned; PMO evidence shows owner gaps.'],
            ['code' => 'P2.F4', 'pillar' => 'P2', 'score' => 3, 'is_compliance' => false, 'text' => 'Critical path dependencies are visible.', 'evidence' => 'Integrated plan exists but data cleansing dates are not baselined.', 'source_type' => 'document', 'respondent_role' => 'Programme Planner', 'document_source' => 'Integrated plan', 'confidence' => 'medium', 'stakeholder_divergence_note' => null],
            ['code' => 'P3.F11', 'pillar' => 'P3', 'score' => 2, 'is_compliance' => true, 'text' => 'Financial controls support accurate reporting.', 'evidence' => 'Steering pack status conflicts with cost exposure in RAID.', 'source_type' => 'document', 'respondent_role' => 'Finance Lead', 'document_source' => 'Steering pack', 'confidence' => 'high', 'stakeholder_divergence_note' => 'Sponsor reports budget control; finance evidence shows unresolved exposure.'],
            ['code' => 'P4.F1', 'pillar' => 'P4', 'score' => 3, 'is_compliance' => false, 'text' => 'Change impacts are understood.', 'evidence' => 'Change plan exists but business ownership is partial.', 'source_type' => 'workshop', 'respondent_role' => 'Change Lead', 'document_source' => 'Change workshop', 'confidence' => 'medium', 'stakeholder_divergence_note' => null],
            ['code' => 'P5.F9', 'pillar' => 'P5', 'score' => 2, 'is_compliance' => true, 'text' => 'GDPR and data retention requirements are understood.', 'evidence' => 'Retention decisions remain pending legal confirmation.', 'source_type' => 'document', 'respondent_role' => 'Data Protection Officer', 'document_source' => 'GDPR checklist', 'confidence' => 'medium', 'stakeholder_divergence_note' => 'Sponsor expects data signoff; DPO confirms retention decisions are open.'],
            ['code' => 'P6.F1', 'pillar' => 'P6', 'score' => 3, 'is_compliance' => false, 'text' => 'Solution fit is understood.', 'evidence' => 'Fit gaps are logged but workarounds remain open.', 'source_type' => 'document', 'respondent_role' => 'Solution Architect', 'document_source' => 'Design log', 'confidence' => 'medium', 'stakeholder_divergence_note' => null],
            ['code' => 'P7.F1', 'pillar' => 'P7', 'score' => 2, 'is_compliance' => false, 'text' => 'Cutover readiness criteria are defined.', 'evidence' => 'Go/no-go criteria lack objective reconciliation thresholds.', 'source_type' => 'document', 'respondent_role' => 'Cutover Manager', 'document_source' => 'Cutover draft', 'confidence' => 'high', 'stakeholder_divergence_note' => 'Sponsor expects cutover to hold; cutover manager reports readiness criteria are unresolved.'],
            ['code' => 'P8.F1', 'pillar' => 'P8', 'score' => 4, 'is_compliance' => false, 'text' => 'Delivery roles are assigned.', 'evidence' => 'RACI is approved for delivery teams.', 'source_type' => 'document', 'respondent_role' => 'Delivery Lead', 'document_source' => 'RACI', 'confidence' => 'high', 'stakeholder_divergence_note' => null],
            ['code' => 'P9.F1', 'pillar' => 'P9', 'score' => 3, 'is_compliance' => false, 'text' => 'Operational reporting requirements are clear.', 'evidence' => 'Reporting requirements are described but not in acceptance criteria.', 'source_type' => 'interview', 'respondent_role' => 'Service Transition Lead', 'document_source' => 'Transition interview', 'confidence' => 'low', 'stakeholder_divergence_note' => null],
            ['code' => 'P10.F1', 'pillar' => 'P10', 'score' => 4, 'is_compliance' => false, 'text' => 'Transformation maturity is actively improved.', 'evidence' => 'Adoption metrics have named owners.', 'source_type' => 'workshop', 'respondent_role' => 'Transformation Lead', 'document_source' => 'Maturity workshop', 'confidence' => 'medium', 'stakeholder_divergence_note' => null],
        ];
    }

    private function structuredDraft(): array
    {
        return [
            'cover_letter' => 'Formal transmittal for PIR Tier 2',
            'executive_position' => 'Executive position for PIR Tier 2',
            'intelligence_dashboard' => [
                'overall' => ['score' => 3.1, 'rag' => 'Amber', 'stage' => 'Build'],
                'indices' => [
                    'BRI' => ['score' => 2.4, 'interpretation' => 'Readiness is constrained.'],
                    'VRI' => ['score' => 2.5, 'interpretation' => 'Value confidence is weak.'],
                    'DMI' => ['score' => 3.4, 'interpretation' => 'Digital maturity is partial.'],
                    'RII' => ['score' => 2.6, 'interpretation' => 'Risk controls are inconsistent.'],
                    'CHI' => ['score' => 2.2, 'interpretation' => 'Compliance health is pressured.'],
                ],
                'alert_flags' => ['P1 below 4.0'],
                'confidence_legend' => [
                    'high' => 'Confirmed by documentary evidence and interview',
                    'medium' => 'Confirmed by interview only',
                    'low' => 'Single source or contradicted evidence',
                ],
            ],
            'stakeholder_intelligence' => [
                'divergence_summary' => 'Sponsor confidence is ahead of operational evidence.',
                'sponsor_position' => 'Sponsor expects the programme can remain amber-green if weekly actions continue.',
                'operational_position' => 'Delivery leads report unresolved cutover and data risks that are absent from sponsor reporting.',
                'divergence_areas' => [[
                    'area' => 'Status reporting',
                    'sponsor_view' => 'Sponsor reports amber-green.',
                    'operational_view' => 'Delivery leads report unresolved blockers.',
                    'finding' => 'Reported status is ahead of evidence.',
                ]],
                'governance_implication' => 'Sponsor must reset decision rights and evidence standards.',
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
            'reporting_accuracy_risk_finding' => 'Board reporting is ahead of operational evidence.',
            'risk_register' => [[
                'risk_title' => 'Sponsor reporting divergence',
                'probability' => 'High',
                'impact' => 'High',
                'owner' => 'Executive Sponsor',
                'current_control' => 'Steering forum',
                'action' => 'Reset evidence standards within 30 days.',
            ]],
            'raid_summary' => [
                'total_risks' => 6,
                'critical_risks' => 3,
                'issues_without_owner' => 1,
                'overdue_actions' => 2,
                'assessment' => 'RAID health is weak.',
            ],
            'root_cause_analysis' => [
                'narrative' => 'Root cause narrative.',
                'primary_cause' => 'Sponsor reporting is not evidence-led.',
                'causal_chain' => ['Optimistic reporting', 'Delayed decisions'],
            ],
            'priority_plan' => [
                '30_days' => [['action_title' => 'Reset decisions', 'owner' => 'Sponsor', 'deadline' => '30 days', 'done_condition' => 'Owners named']],
                '60_days' => [['action_title' => 'Validate evidence', 'owner' => 'PMO', 'deadline' => '60 days', 'done_condition' => 'Evidence pack signed off']],
                '90_days' => [['action_title' => 'Regulatory evidence pack', 'owner' => 'Sponsor', 'deadline' => '90 days', 'done_condition' => 'Evidence pack complete']],
            ],
            'final_position' => 'Requires executive briefing intervention before go-live can proceed',
            'evidence_validated_statement' => 'The evidence has been validated through direct document review and interview.',
            'tier1_bridge' => 'Tier 1 bridge should not render for Tier 2.',
            'compliance_risk_signals' => 'If actions complete, a regulatory reviewer will see signed decision logs and evidence-backed status reporting.',
        ];
    }
}
