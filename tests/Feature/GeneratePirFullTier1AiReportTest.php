<?php

namespace Tests\Feature;

use App\Jobs\GeneratePirFullTier1AiReport;
use App\Models\Assessment;
use App\Models\Client;
use App\Services\FullReportAiPayloadBuilder;
use App\Services\PirFullTier1AiReportGenerationService;
use Database\Seeders\PirFullTier1PayloadFixtureSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Mockery;
use RuntimeException;
use Tests\TestCase;

class GeneratePirFullTier1AiReportTest extends TestCase
{
    use RefreshDatabase;

    public function test_valid_fixture_can_prepare_ai_generation_successfully(): void
    {
        Http::fake();
        $this->seed(PirFullTier1PayloadFixtureSeeder::class);

        $assessment = Assessment::where('name', 'PIR Full Tier 1 Payload Fixture')->firstOrFail();

        app(GeneratePirFullTier1AiReport::class, ['assessmentId' => $assessment->id])
            ->handle(app(PirFullTier1AiReportGenerationService::class));

        $state = $assessment->fresh()->ai_draft_json['pir_full_tier1_generation'];

        $this->assertSame('generation_prepared', $state['status']);
        $this->assertSame('pir_full_tier1', $state['prompt_key']);
        $this->assertSame(13, $state['evidence_notes_count']);
        $this->assertSame(13, $state['question_response_count']);
        $this->assertTrue($state['p1_p10_mapping_complete']);
        $this->assertSame([
            'bri' => true,
            'vri' => true,
            'dmi' => true,
            'rii' => true,
            'chi' => true,
        ], $state['indices_present']);
        Http::assertNothingSent();
    }

    public function test_invalid_assessment_fails_cleanly_and_does_not_call_ai(): void
    {
        Http::fake();

        $client = Client::create([
            'company_name' => 'Invalid Co',
            'primary_contact' => 'Pat Sponsor',
        ]);

        $assessment = Assessment::create([
            'client_id' => $client->id,
            'name' => 'Invalid PIR Full Tier 1 Fixture',
            'type' => 'PIR',
            'overall_score' => 2.5,
            'rag_status' => 'Amber',
            'status' => 'draft',
        ]);

        try {
            app(GeneratePirFullTier1AiReport::class, ['assessmentId' => $assessment->id])
                ->handle(app(PirFullTier1AiReportGenerationService::class));
            $this->fail('Expected validation failure was not thrown.');
        } catch (RuntimeException $exception) {
            $this->assertStringContainsString('PIR Full Tier 1 AI generation validation failed', $exception->getMessage());
            $this->assertStringContainsString('evidence_notes must contain at least 10 entries', $exception->getMessage());
            $this->assertStringContainsString('bri is required', $exception->getMessage());
        }

        $state = $assessment->fresh()->ai_draft_json['pir_full_tier1_generation'];

        $this->assertSame('validation_failed', $state['status']);
        $this->assertNotEmpty($state['errors']);
        Http::assertNothingSent();
    }

    public function test_service_reuses_payload_builder_boundary(): void
    {
        $client = Client::create([
            'company_name' => 'Builder Boundary Co',
            'primary_contact' => 'Sam Sponsor',
        ]);

        $assessment = Assessment::create([
            'client_id' => $client->id,
            'name' => 'Builder Boundary Assessment',
            'type' => 'PIR',
            'overall_score' => 3.1,
            'rag_status' => 'Amber',
            'status' => 'draft',
        ]);

        $builder = Mockery::mock(FullReportAiPayloadBuilder::class);
        $builder->shouldReceive('buildFromAssessment')
            ->once()
            ->with(Mockery::on(fn ($value) => $value instanceof Assessment && $value->id === $assessment->id), 'Review')
            ->andReturn($this->validPayload($assessment->id));

        $state = (new PirFullTier1AiReportGenerationService($builder))->prepare($assessment->id);

        $this->assertSame('generation_prepared', $state['status']);
        $this->assertSame(10, $state['evidence_notes_count']);
        $this->assertSame(10, $state['question_response_count']);
    }

    public function test_job_does_not_invent_missing_values(): void
    {
        $client = Client::create([
            'company_name' => 'Missing Values Co',
            'primary_contact' => 'Morgan Sponsor',
        ]);

        $assessment = Assessment::create([
            'client_id' => $client->id,
            'name' => 'Missing Values Assessment',
            'type' => 'PIR',
            'overall_score' => 2.5,
            'rag_status' => 'Amber',
            'status' => 'draft',
        ]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('bri is required');

        app(GeneratePirFullTier1AiReport::class, ['assessmentId' => $assessment->id])
            ->handle(app(PirFullTier1AiReportGenerationService::class));
    }

    private function validPayload(int $assessmentId): array
    {
        $pillarScores = [];
        foreach (range(1, 10) as $number) {
            $pillarScores["P{$number}"] = 3.0;
        }

        $questionResponses = [];
        $evidenceNotes = [];
        foreach (range(1, 10) as $number) {
            $questionId = "P{$number}.F1";
            $questionResponses[] = [
                'id' => $questionId,
                'pillar_code' => "P{$number}",
                'score' => 3,
                'is_compliance' => $number === 1,
            ];
            $evidenceNotes[$questionId] = [
                'note' => "Evidence note {$number}",
                'source' => "Source {$number}",
                'confidence' => 'High',
                'respondent' => 'Programme Sponsor',
            ];
        }

        return [
            'framework' => 'PIR',
            'assessment_id' => $assessmentId,
            'overall_score' => 3.1,
            'rag_status' => 'Amber',
            'pillar_scores' => $pillarScores,
            'bri' => 3.0,
            'vri' => 3.0,
            'dmi' => 3.0,
            'rii' => 3.0,
            'chi' => 3.0,
            'question_responses' => $questionResponses,
            'compliance_question_scores' => ['P1.F1' => 3],
            'tier' => 'Review',
            'evidence_notes' => $evidenceNotes,
            'stakeholder_notes' => null,
        ];
    }
}
