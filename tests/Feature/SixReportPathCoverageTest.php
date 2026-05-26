<?php

namespace Tests\Feature;

use App\Http\Controllers\RapidConsultingController;
use App\Models\Assessment;
use App\Models\AssessmentFramework;
use App\Models\AssessmentPillar;
use App\Models\AssessmentPillarScore;
use App\Models\AssessmentQuestionBank;
use App\Models\AssessmentQuestionResponse;
use App\Models\Client;
use App\Models\Lead;
use App\Models\User;
use App\Services\ReportPdfService;
use App\Services\ReportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Route;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Tests\TestCase;

class SixReportPathCoverageTest extends TestCase
{
    use RefreshDatabase;

    #[DataProvider('snapshotPaths')]
    public function test_snapshot_report_generation_paths_build_required_payload_and_use_snapshot_service(
        string $framework,
        string $promptKey
    ): void {
        Mail::fake();

        $fakeReportService = new class extends ReportService {
            public array $calls = [];

            public function generate(string $promptKey, array $payload): array
            {
                $this->calls[] = compact('promptKey', 'payload');

                return [
                    'intelligence_brief' => "Snapshot paragraph one.\n\nSnapshot paragraph two.",
                    'insight_cards' => [],
                ];
            }
        };

        $this->app->instance(ReportService::class, $fakeReportService);

        $this->withSession($this->snapshotSession($framework))
            ->post(route('rapid-consulting.process-personal-form'), $this->personalFormData([
                'consent_given' => '1',
                'email' => strtolower($framework) . '-snapshot@example.com',
                'company' => "{$framework} Snapshot Co",
            ]))
            ->assertRedirect(route('rapid-consulting.results'));

        $lead = Lead::firstOrFail();
        $this->assertCount(1, $fakeReportService->calls);

        $call = $fakeReportService->calls[0];
        $payload = $call['payload'];

        $this->assertSame($promptKey, $call['promptKey']);
        $this->assertSame($framework, $payload['framework']);
        $this->assertSame($lead->id, $payload['assessment_id']);
        $this->assertSame("{$framework} Snapshot Co", $payload['client_company']);
        $this->assertSame($framework . '_SNAPSHOT', $lead->assessment_type);
        $this->assertSame($framework === 'PIR' ? 'Build' : 'Transformation', $payload[$framework === 'PIR' ? 'delivery_stage' : 'service_context']);
        $this->assertArrayHasKey('question_responses', $payload);
        $this->assertArrayHasKey('compliance_question_scores', $payload);
        $this->assertArrayHasKey('alert_flags', $payload);

        if ($framework === 'PIR') {
            $this->assertArrayHasKey('pillar_scores', $payload);
            $this->assertArrayHasKey('pillar_names', $payload);
            $this->assertArrayHasKey('bri', $payload);
            $this->assertArrayHasKey('vri', $payload);
            $this->assertArrayHasKey('dmi', $payload);
            $this->assertArrayHasKey('rii', $payload);
            $this->assertArrayHasKey('chi', $payload);
        } else {
            $this->assertArrayHasKey('domain_scores', $payload);
            $this->assertArrayHasKey('domain_names', $payload);
            $this->assertArrayHasKey('ssi', $payload);
            $this->assertArrayHasKey('smi', $payload);
            $this->assertArrayHasKey('simi', $payload);
            $this->assertArrayHasKey('bau_ri', $payload);
            $this->assertArrayHasKey('smi_simi_delta', $payload);
        }

        $route = Route::getRoutes()->getByName('rapid-consulting.snapshot-report.pdf');
        $this->assertSame(RapidConsultingController::class . '@downloadSnapshotPdf', $route->getActionName());
        $this->assertStringContainsString(
            "/rapid-consulting/leads/{$lead->id}/snapshot-report.pdf",
            route('rapid-consulting.snapshot-report.pdf', ['lead' => $lead->id, 'token' => $lead->booking_token])
        );
    }

    #[DataProvider('fullReportPaths')]
    public function test_full_report_generation_paths_send_correct_prompt_key_payload_and_metadata(
        string $framework,
        string $reportTier,
        string $expectedPromptKey,
        string $expectedPayloadTier
    ): void {
        $this->configureAnthropic();

        Http::fake(function ($request) {
            $payload = $request->data();

            if (($payload['stream'] ?? false) === true) {
                return Http::response($this->streamedAnthropicResponse($this->draft()), 200, [
                    'Content-Type' => 'text/event-stream',
                ]);
            }

            return Http::response([
                'id' => 'msg_123',
                'content' => [[
                    'type' => 'text',
                    'text' => json_encode($this->draft()),
                ]],
            ]);
        });

        $assessment = $this->assessment($framework, $reportTier);
        $this->seedAssessmentEvidence($assessment);

        $this->actingAs($this->admin())
            ->post(route('admin.assessments.generateReport', $assessment))
            ->assertRedirect(route('admin.assessments.show', $assessment))
            ->assertSessionHas('success', $framework === 'PIR'
                ? 'AI report generation started. Refresh this page in a moment.'
                : 'AI Report generated successfully.');

        Http::assertSent(function ($request) use ($framework, $expectedPromptKey, $expectedPayloadTier, $assessment) {
            $payload = $request->data();
            $input = json_decode($payload['messages'][0]['content'][0]['text'], true);
            $aiPayload = $input['ai_payload'];

            return $request->url() === 'https://example.test/v1/messages'
                && (($framework === 'PIR') === (($payload['stream'] ?? false) === true))
                && $payload['system'] === config("ai.prompts.{$expectedPromptKey}")
                && $input['prompt_key'] === $expectedPromptKey
                && $input['metadata']['type'] === "{$framework}_FULL"
                && $input['metadata']['is_full'] === true
                && $input['metadata']['assessment_id'] === $assessment->id
                && isset($input['metadata']['submitted_at'])
                && ! array_key_exists('results', $input['metadata'])
                && ! array_key_exists('answers', $input['metadata'])
                && $aiPayload['framework'] === $framework
                && $aiPayload['tier'] === $expectedPayloadTier
                && isset($aiPayload['assessment_id'], $aiPayload['overall_score'], $aiPayload['rag_status'])
                && isset($aiPayload['question_responses'], $aiPayload['evidence_notes'], $aiPayload['confidence_level'])
                && ($expectedPayloadTier === 'Briefing') === is_array($aiPayload['stakeholder_notes'])
                && ($framework === 'PIR'
                    ? isset($aiPayload['pillar_scores'], $aiPayload['pillar_names'], $aiPayload['bri'], $aiPayload['vri'], $aiPayload['dmi'], $aiPayload['rii'], $aiPayload['chi'], $aiPayload['programme_value'])
                    : isset($aiPayload['domain_scores'], $aiPayload['domain_names'], $aiPayload['ssi'], $aiPayload['smi'], $aiPayload['simi'], $aiPayload['bau_ri'], $aiPayload['smi_simi_delta'], $aiPayload['annual_service_cost']));
        });

        $assessment->refresh();

        $this->assertSame($this->draft(), $assessment->ai_draft_json);
        $this->assertSame('completed', $assessment->status);
    }

    #[DataProvider('fullReportPdfPaths')]
    public function test_full_report_pdf_export_paths_use_pdf_service(
        string $framework,
        string $reportTier
    ): void {
        $assessment = $this->assessment($framework, $reportTier, [
            'ai_draft_json' => $this->draft(),
            'status' => 'completed',
        ]);

        $fakeService = new class extends ReportPdfService {
            public ?int $assessmentId = null;
            public ?string $framework = null;
            public ?string $reportTier = null;

            public function download(Assessment $assessment): BinaryFileResponse
            {
                $this->assessmentId = $assessment->id;
                $this->framework = $assessment->type;
                $this->reportTier = $assessment->report_tier;

                $directory = storage_path('framework/testing');
                if (! is_dir($directory)) {
                    mkdir($directory, 0755, true);
                }

                $path = $directory . '/six-path-report.pdf';
                file_put_contents($path, '%PDF-1.4 fake');

                return response()->download($path, 'six-path-report.pdf');
            }
        };

        $this->app->instance(ReportPdfService::class, $fakeService);

        $this->actingAs($this->admin())
            ->post(route('admin.assessments.exportPdf', $assessment))
            ->assertOk()
            ->assertDownload('six-path-report.pdf');

        $this->assertSame($assessment->id, $fakeService->assessmentId);
        $this->assertSame($framework, $fakeService->framework);
        $this->assertSame($reportTier, $fakeService->reportTier);
    }

    public static function snapshotPaths(): array
    {
        return [
            'PIR Snapshot' => ['PIR', 'pir_snapshot'],
            'SIR Snapshot' => ['SIR', 'sir_snapshot'],
        ];
    }

    public static function fullReportPaths(): array
    {
        return [
            'PIR Tier 1 / Review' => ['PIR', 'Tier 1 Rapid', 'pir_full_tier1', 'Review'],
            'PIR Tier 2 / Briefing' => ['PIR', 'Tier 2 Full', 'pir_full_tier2', 'Briefing'],
            'SIR Tier 1 / Review' => ['SIR', 'Tier 1 Rapid', 'sir_full_tier1', 'Review'],
            'SIR Tier 2 / Briefing' => ['SIR', 'Tier 2 Full', 'sir_full_tier2', 'Briefing'],
        ];
    }

    public static function fullReportPdfPaths(): array
    {
        return [
            'PIR Tier 1 / Review' => ['PIR', 'Tier 1 Rapid'],
            'PIR Tier 2 / Briefing' => ['PIR', 'Tier 2 Full'],
            'SIR Tier 1 / Review' => ['SIR', 'Tier 1 Rapid'],
            'SIR Tier 2 / Briefing' => ['SIR', 'Tier 2 Full'],
        ];
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
            'event: message_start' . "\n" . 'data: {"type":"message_start","message":{"id":"msg_123","type":"message","role":"assistant","content":[]}}',
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

    private function admin(): User
    {
        return User::create([
            'name' => 'Admin',
            'email' => 'admin-' . uniqid() . '@example.com',
            'password' => 'password',
            'role' => 'admin',
        ]);
    }

    private function personalFormData(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Alex Sponsor',
            'job_title' => 'Sponsor',
            'company' => 'Snapshot Co',
            'industry' => 'Financial services',
            'email' => 'snapshot@example.com',
            'phone' => '02070000000',
        ], $overrides);
    }

    private function snapshotSession(string $framework): array
    {
        $isPir = $framework === 'PIR';
        $areaCodes = $isPir
            ? array_map(fn (int $number) => "P{$number}", range(1, 10))
            : array_map(fn (int $number) => "D{$number}", range(1, 12));
        $answers = [];
        $confidence = [];
        $areaScores = [];

        foreach ($areaCodes as $areaCode) {
            $questionCode = "{$areaCode}.F1";
            $answers[$questionCode] = 3;
            $confidence[$questionCode] = 'medium';
            $areaScores[$areaCode] = [
                'name' => $areaCode,
                'score' => 3.2,
                'rag' => 'Amber',
                'is_critical' => false,
            ];
        }

        $indexScores = $isPir
            ? ['BRI' => 3.2, 'VRI' => 3.1, 'DMI' => 3.0, 'RII' => 3.0, 'CHI' => 3.2]
            : ['SSI' => 3.2, 'SMI' => 3.1, 'SIMI' => 2.9, 'BAURI' => 3.0, 'CHI' => 3.2, 'smi_simi_delta' => 0.2];

        return [
            'rc_type' => strtolower($framework),
            'rc_answers' => $answers,
            'rc_confidence' => $confidence,
            'rc_delivery_stage' => $isPir ? 'Build' : null,
            'rc_service_context' => $isPir ? null : 'Transformation',
            'rc_regulatory_context' => 'fca_uk',
            'rc_results' => [
                'overall_score' => 3.2,
                'rag_status' => 'Amber',
                'pillar_scores' => $areaScores,
                'index_scores' => $indexScores,
                'type' => strtolower($framework),
                'delivery_stage' => $isPir ? 'Build' : null,
                'service_context' => $isPir ? null : 'Transformation',
                'regulatory_context' => 'fca_uk',
                'recommendation' => 'Proceed to review.',
            ],
        ];
    }

    private function assessment(string $framework, string $reportTier, array $overrides = []): Assessment
    {
        $client = Client::create([
            'company_name' => "{$framework} Client " . uniqid(),
            'primary_contact' => 'Alex Sponsor',
        ]);

        return Assessment::create(array_merge([
            'client_id' => $client->id,
            'name' => "{$framework} Assessment",
            'target_entity' => "{$framework} Target",
            'type' => $framework,
            'report_tier' => $reportTier,
            'overall_score' => 3.2,
            'rag_status' => 'Amber',
            'status' => 'draft',
            'delivery_stage' => $framework === 'PIR' ? 'Build' : null,
            'service_context' => $framework === 'SIR' ? 'Transformation' : null,
            'client_concerns' => 'Evidence quality needs review.',
            'regulatory_context' => 'fca_uk',
            'sponsor_name' => 'Alex Sponsor',
            'interview_count' => 3,
            'documents_reviewed' => ['RAID log', 'Service report'],
            'programme_value' => $framework === 'PIR' ? 1200000 : null,
            'annual_service_cost' => $framework === 'SIR' ? 250000 : null,
            'reporting_accuracy_risk' => $framework === 'PIR',
            'reporting_accuracy_evidence' => $framework === 'PIR' ? 'Status reporting conflicts with evidence.' : null,
            'sponsor_position' => 'Sponsor reports controlled delivery.',
            'operational_position' => 'Operations reports unresolved risk.',
            'divergence_areas' => ['ownership', 'evidence quality'],
            'chi' => 3.0,
        ], $overrides));
    }

    private function seedAssessmentEvidence(Assessment $assessment): void
    {
        $isPir = $assessment->type === 'PIR';
        $framework = AssessmentFramework::create([
            'code' => $assessment->type,
            'name' => $isPir ? 'Programme Intelligence Review' : 'Service Intelligence Review',
        ]);

        $max = $isPir ? 10 : 12;
        for ($number = 1; $number <= $max; $number++) {
            $code = ($isPir ? 'P' : 'D') . $number;
            $pillar = AssessmentPillar::create([
                'framework_id' => $framework->id,
                'code' => $code,
                'name' => "{$code} Area",
                'weight' => 1,
            ]);

            AssessmentQuestionBank::create([
                'framework_id' => $framework->id,
                'pillar_id' => $pillar->id,
                'level' => 'full',
                'question_code' => "{$code}.F1",
                'question_text' => "{$code} evidence question",
                'is_compliance' => $number === 1,
            ]);

            AssessmentPillarScore::create([
                'assessment_id' => $assessment->id,
                'name' => "{$code} — {$code} Area",
                'score' => $number === 1 ? 2.5 : 3.2,
                'rag_status' => 'Amber',
            ]);

            AssessmentQuestionResponse::create([
                'assessment_id' => $assessment->id,
                'pillar_name' => "{$code} — {$code} Area",
                'question' => "{$code}.F1: {$code} evidence question",
                'score' => $number === 1 ? 2 : 3,
                'evidence_note' => "{$code} evidence note",
                'respondent_role' => 'Programme Sponsor',
                'document_source' => 'Evidence pack',
                'confidence' => 'high',
                'confidence_level' => 'high',
                'stakeholder_divergence_note' => 'Sponsor and operations differ.',
            ]);
        }
    }

    private function draft(): array
    {
        return [
            'cover_letter' => 'Formal transmittal',
            'executive_position' => 'Recoverable with intervention',
            'final_position' => 'Proceed with controlled recovery',
        ];
    }
}
