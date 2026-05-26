<?php

namespace Tests\Feature;

use App\Jobs\GenerateAdminFullReport;
use App\Models\Assessment;
use App\Models\AssessmentFramework;
use App\Models\AssessmentPillar;
use App\Models\AssessmentPillarScore;
use App\Models\AssessmentQuestionBank;
use App\Models\AssessmentQuestionResponse;
use App\Models\Client;
use App\Models\User;
use App\Services\AdminFullReportGenerationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class AdminSirTier2BriefingWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_sir_tier2_blocks_generation_before_claude_when_required_stakeholder_fields_are_missing(): void
    {
        $this->configureAnthropic();
        $this->seedSirFramework();
        Queue::fake();
        Http::fake();

        $cases = [
            'sponsor_position' => ['sponsor_position' => null],
            'operational_position' => ['operational_position' => null],
            'divergence_areas' => ['divergence_areas' => []],
        ];

        foreach ($cases as $expectedMissing => $overrides) {
            $withoutQuestionDivergence = (bool) ($overrides['without_question_divergence'] ?? false);
            unset($overrides['without_question_divergence']);

            $assessment = $this->sirTier2Assessment($overrides);
            $this->seedAssessmentEvidence($assessment, $withoutQuestionDivergence);

            $this->actingAs($this->admin())
                ->post(route('admin.assessments.generateReport', $assessment))
                ->assertRedirect(route('admin.assessments.show', $assessment))
                ->assertSessionHas('error', fn (string $message) => str_contains($message, $expectedMissing));
        }

        Queue::assertNothingPushed();
        Http::assertNothingSent();
    }

    public function test_sir_tier2_generate_endpoint_dispatches_queued_job_and_does_not_call_anthropic_inline(): void
    {
        $this->configureAnthropic();
        $this->seedSirFramework();
        Queue::fake();
        Http::fake();

        $assessment = $this->sirTier2Assessment();
        $this->seedAssessmentEvidence($assessment);

        $this->actingAs($this->admin())
            ->post(route('admin.assessments.generateReport', $assessment))
            ->assertRedirect(route('admin.assessments.show', $assessment))
            ->assertSessionHas('success', 'AI report generation started. Refresh this page in a moment.');

        $assessment->refresh();
        $this->assertSame('generating', $assessment->ai_generation_status);
        Queue::assertPushed(GenerateAdminFullReport::class, fn (GenerateAdminFullReport $job) => $job->assessmentId === $assessment->id);
        Http::assertNothingSent();
    }

    public function test_sir_tier2_allows_generation_when_assessment_level_divergence_is_clear_without_question_divergence_note(): void
    {
        $this->configureAnthropic();
        $this->seedSirFramework();
        Queue::fake();
        Http::fake();

        $assessment = $this->sirTier2Assessment();
        $this->seedAssessmentEvidence($assessment, withoutQuestionDivergence: true);

        $this->actingAs($this->admin())
            ->post(route('admin.assessments.generateReport', $assessment))
            ->assertRedirect(route('admin.assessments.show', $assessment))
            ->assertSessionHas('success', 'AI report generation started. Refresh this page in a moment.');

        Queue::assertPushed(GenerateAdminFullReport::class, fn (GenerateAdminFullReport $job) => $job->assessmentId === $assessment->id);
        Http::assertNothingSent();
    }

    public function test_sir_tier2_queued_job_streams_canonical_briefing_request_and_stores_stripped_draft(): void
    {
        $this->configureAnthropic();
        $this->seedSirFramework();

        $assessment = $this->sirTier2Assessment();
        $this->seedAssessmentEvidence($assessment);

        $draft = $this->sirTier2Draft();
        Http::fake([
            'example.test/*' => Http::response($this->streamedAnthropicResponse([
                'report' => $draft,
                'metadata' => ['harmless_wrapper' => true],
            ]), 200, ['Content-Type' => 'text/event-stream']),
        ]);

        (new GenerateAdminFullReport($assessment->id))->handle(app(AdminFullReportGenerationService::class));

        Http::assertSent(function ($request) use ($assessment) {
            $payload = $request->data();
            $input = json_decode($payload['messages'][0]['content'][0]['text'], true);
            $aiPayload = $input['ai_payload'];

            return $request->url() === 'https://example.test/v1/messages'
                && ($payload['stream'] ?? false) === true
                && $payload['system'] === config('ai.prompts.sir_full_tier2')
                && $input['prompt_key'] === 'sir_full_tier2'
                && $input['metadata']['type'] === 'SIR_FULL'
                && $input['metadata']['is_full'] === true
                && $input['metadata']['assessment_id'] === $assessment->id
                && $aiPayload['framework'] === 'SIR'
                && $aiPayload['tier'] === 'Briefing'
                && $aiPayload['service_name'] === 'Payments Platform'
                && $aiPayload['service_context'] === 'Transformation'
                && isset($aiPayload['domain_scores'], $aiPayload['domain_names'])
                && isset($aiPayload['ssi'], $aiPayload['smi'], $aiPayload['simi'], $aiPayload['bau_ri'], $aiPayload['chi'], $aiPayload['smi_simi_delta'])
                && ! array_key_exists('bri', $aiPayload)
                && ! array_key_exists('pillar_names', $aiPayload)
                && $aiPayload['stakeholder_notes']['sponsor_position'] === 'Sponsor sees service reporting as controlled.'
                && $aiPayload['stakeholder_notes']['operational_position'] === 'Service management reports unresolved incidents.'
                && $aiPayload['evidence_notes']['D1.F1']['stakeholder_divergence_note'] === 'Sponsor sees stability; service management reports recurring incidents.';
        });

        $assessment->refresh();
        $this->assertSame($draft, $assessment->ai_draft_json);
        $this->assertSame(array_keys($draft), array_keys($assessment->ai_draft_json));
        $this->assertSame('completed', $assessment->status);
        $this->assertSame('completed', $assessment->ai_generation_status);

        $this->actingAs($this->admin())
            ->get(route('admin.assessments.show', $assessment))
            ->assertOk()
            ->assertSee('Stakeholder Intelligence')
            ->assertSee('Sponsor / Executive Position')
            ->assertSee('Operational / Service Management Position')
            ->assertSee('Service reporting divergence')
            ->assertSee('Service Stability Index')
            ->assertSee('SMI/SIMI Delta')
            ->assertSee('D1 — Service Governance &amp; Ownership', false)
            ->assertDontSee('RAID Summary')
            ->assertDontSee('Tier 1 Bridge')
            ->assertDontSee('Reporting Accuracy Risk Finding')
            ->assertDontSee('Business Readiness Index')
            ->assertDontSee('Risk Intelligence Index');
    }

    public function test_sir_tier2_rejects_non_canonical_or_pir_shaped_streamed_response(): void
    {
        $this->configureAnthropic();
        $this->seedSirFramework();

        $cases = [
            'missing stakeholder_intelligence' => function (array $draft): array {
                unset($draft['stakeholder_intelligence']);
                return $draft;
            },
            'extra raid_summary' => function (array $draft): array {
                $draft['raid_summary'] = ['total_risks' => 1];
                return $draft;
            },
            'extra tier1_bridge' => function (array $draft): array {
                $draft['tier1_bridge'] = 'Tier 1 bridge must not appear.';
                return $draft;
            },
            'extra reporting_accuracy_risk_finding' => function (array $draft): array {
                $draft['reporting_accuracy_risk_finding'] = null;
                return $draft;
            },
            'pir indices' => function (array $draft): array {
                $draft['intelligence_dashboard']['indices']['bri'] = ['value' => 2.4, 'interpretation' => 'Wrong index.'];
                return $draft;
            },
            'pillar labels' => function (array $draft): array {
                $draft['intelligence_profile'][0]['pillar_code'] = 'P1';
                unset($draft['intelligence_profile'][0]['domain_code']);
                return $draft;
            },
        ];

        foreach ($cases as $caseName => $mutator) {
            $assessment = $this->sirTier2Assessment(['name' => "Bad {$caseName}"]);
            $this->seedAssessmentEvidence($assessment);

            Http::fake([
                'example.test/*' => Http::response($this->streamedAnthropicResponse($mutator($this->sirTier2Draft())), 200, [
                    'Content-Type' => 'text/event-stream',
                ]),
            ]);

            (new GenerateAdminFullReport($assessment->id))->handle(app(AdminFullReportGenerationService::class));

            $assessment->refresh();
            $this->assertSame('failed', $assessment->ai_generation_status, $caseName);
            $this->assertNull($assessment->ai_draft_json, $caseName);
        }
    }

    private function configureAnthropic(): void
    {
        Config::set('services.anthropic.api_key', 'test-anthropic-key');
        Config::set('services.anthropic.base_url', 'https://example.test/v1');
        Config::set('services.anthropic.model', 'claude-sonnet-4-5');
        Config::set('services.anthropic.version', '2023-06-01');
    }

    private function admin(): User
    {
        return User::create([
            'name' => 'Admin',
            'email' => 'admin-sir-tier2-' . uniqid() . '@example.com',
            'password' => 'password',
            'role' => 'admin',
        ]);
    }

    private function sirTier2Assessment(array $overrides = []): Assessment
    {
        return Assessment::create(array_merge([
            'client_id' => Client::create([
                'company_name' => 'SIR Tier 2 Co',
                'primary_contact' => 'Sam Sponsor',
            ])->id,
            'name' => 'Service Intelligence Briefing',
            'target_entity' => 'Payments Platform',
            'type' => 'SIR',
            'report_tier' => 'Tier 2 Full',
            'overall_score' => 2.8,
            'rag_status' => 'Amber',
            'status' => 'draft',
            'service_context' => 'Transformation',
            'client_concerns' => 'Recurring incidents are not visible in service reporting.',
            'regulatory_context' => 'fca_uk',
            'sponsor_name' => 'Sam Sponsor',
            'interview_count' => 4,
            'documents_reviewed' => ['SLA pack', 'Incident report'],
            'annual_service_cost' => 250000,
            'sponsor_position' => 'Sponsor sees service reporting as controlled.',
            'operational_position' => 'Service management reports unresolved incidents.',
            'divergence_areas' => ['service reporting', 'incident recurrence'],
            'chi' => 2.5,
        ], $overrides));
    }

    private function seedSirFramework(): void
    {
        $framework = AssessmentFramework::create([
            'code' => 'SIR',
            'name' => 'Service Intelligence Review',
        ]);

        foreach (['D1' => 'Service Governance & Ownership', 'D2' => 'Incident & Major Incident Management'] as $code => $name) {
            $pillar = AssessmentPillar::create([
                'framework_id' => $framework->id,
                'code' => $code,
                'name' => $name,
                'weight' => 1,
            ]);

            AssessmentQuestionBank::create([
                'framework_id' => $framework->id,
                'pillar_id' => $pillar->id,
                'level' => 'full',
                'question_code' => "{$code}.F1",
                'question_text' => "{$name} is evidenced.",
                'is_compliance' => $code === 'D1',
            ]);
        }
    }

    private function seedAssessmentEvidence(Assessment $assessment, bool $withoutQuestionDivergence = false): void
    {
        foreach (['D1' => 'Service Governance & Ownership', 'D2' => 'Incident & Major Incident Management'] as $code => $name) {
            AssessmentPillarScore::create([
                'assessment_id' => $assessment->id,
                'name' => "{$code} — {$name}",
                'score' => 2.5,
                'rag_status' => 'Amber',
            ]);

            AssessmentQuestionResponse::create([
                'assessment_id' => $assessment->id,
                'pillar_name' => "{$code} — {$name}",
                'question' => "{$code}.F1: {$name} is evidenced.",
                'score' => 2,
                'evidence_note' => "{$code} evidence note",
                'respondent_role' => 'Service Manager',
                'document_source' => 'Evidence pack',
                'confidence' => 'high',
                'confidence_level' => 'high',
                'stakeholder_divergence_note' => $withoutQuestionDivergence ? null : 'Sponsor sees stability; service management reports recurring incidents.',
            ]);
        }
    }

    private function sirTier2Draft(): array
    {
        return [
            'cover_letter' => 'Formal transmittal for SIR Tier 2.',
            'executive_position' => 'The service needs stabilisation before further transformation can be absorbed.',
            'intelligence_dashboard' => [
                'overall' => ['score' => 2.8, 'rag' => 'Amber', 'context' => 'Transformation'],
                'indices' => [
                    'ssi' => ['value' => 2.5, 'interpretation' => 'SSI shows service stability pressure.'],
                    'smi' => ['value' => 2.7, 'interpretation' => 'SMI shows partial maturity.'],
                    'simi' => ['value' => 2.2, 'interpretation' => 'SIMI shows improvement is not embedded.'],
                    'bau_ri' => ['value' => 2.4, 'interpretation' => 'BAU readiness remains constrained.'],
                    'chi' => ['value' => 2.5, 'interpretation' => 'Compliance health is pressured.'],
                    'smi_simi_delta' => ['value' => 0.5, 'interpretation' => 'Maturity is not translating into improvement.'],
                ],
                'alert_flags' => ['D1 below 3.0'],
                'confidence_legend' => [
                    'high' => 'Confirmed by documentary evidence and interview',
                    'medium' => 'Confirmed by interview - independent documentary evidence not available',
                    'low' => 'Single source, contradicted by other data, or evidence not available',
                ],
            ],
            'stakeholder_intelligence' => [
                'divergence_summary' => 'The sponsor sees service reporting as controlled. Service management reports recurring incidents missing from reporting.',
                'divergence_areas' => [[
                    'area' => 'Service reporting divergence',
                    'sponsor_view' => 'Sponsor sees service reporting as controlled.',
                    'operational_view' => 'Service management reports unresolved incidents.',
                    'finding' => 'Service reporting does not reflect operational instability.',
                ]],
                'governance_implication' => 'The CIO must reset service reporting evidence standards.',
            ],
            'intelligence_profile' => [[
                'domain_code' => 'D1',
                'domain_name' => 'D1 — Service Governance & Ownership',
                'score' => 2.5,
                'rag' => 'Amber',
                'confidence' => 'High',
                'headline' => 'Service ownership evidence is incomplete.',
                'evidence' => ['Ownership interview', 'Service governance pack'],
                'business_impact' => 'Incident ownership remains unclear for business users.',
                'compliance_dimension' => null,
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
                'causal_chain' => ['Unclear ownership', 'Weak incident learning', 'Incomplete service reporting'],
            ],
            'priority_plan' => [
                '30_days' => [['action_title' => 'Reset ownership', 'owner' => 'Service Owner', 'deadline' => '30 days', 'done_condition' => 'Owners named']],
                '60_days' => [['action_title' => 'Validate reporting', 'owner' => 'Service Reporting Lead', 'deadline' => '60 days', 'done_condition' => 'Report evidence reconciled']],
                '90_days' => [['action_title' => 'Evidence pack', 'owner' => 'CIO', 'deadline' => '90 days', 'done_condition' => 'Evidence pack complete']],
            ],
            'final_position' => 'Needs stabilisation before further transformation can be absorbed',
            'evidence_validated_statement' => 'Evidence validated on-site. Confidence stated per finding.',
            'compliance_risk_signals' => 'Operational intelligence only. Engage legal and compliance advisers.',
        ];
    }

    private function streamedAnthropicResponse(array $draft): string
    {
        $json = json_encode($draft, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        return implode("\n\n", [
            'event: message_start' . "\n" . 'data: {"type":"message_start","message":{"id":"msg_sir_tier2","type":"message","role":"assistant","content":[]}}',
            'event: content_block_start' . "\n" . 'data: {"type":"content_block_start","index":0,"content_block":{"type":"text","text":""}}',
            'event: content_block_delta' . "\n" . 'data: ' . json_encode([
                'type' => 'content_block_delta',
                'index' => 0,
                'delta' => ['type' => 'text_delta', 'text' => $json],
            ]),
            'event: content_block_stop' . "\n" . 'data: {"type":"content_block_stop","index":0}',
            'event: message_stop' . "\n" . 'data: {"type":"message_stop"}',
            '',
        ]);
    }
}
