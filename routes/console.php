<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('ai:smoke-test-reports {--source=both : synthetic, database, or both} {--prompt=* : Optional prompt key(s) to run}', function () {
    return app(\App\Services\AiSmokeTestReportRunner::class)->run(
        $this,
        (string) $this->option('source'),
        (array) $this->option('prompt')
    );
})->purpose('Run real Claude smoke tests for all active RAB AI report prompts');

Artisan::command('rab:poc-pir-snapshot-payload {leadId?}', function (?int $leadId = null) {
    $query = \App\Models\Lead::query()
        ->where('type', 'PIR')
        ->where('assessment_type', 'PIR_SNAPSHOT')
        ->whereNotNull('answers_json')
        ->whereNotNull('index_scores_json');

    $lead = $leadId
        ? (clone $query)->whereKey($leadId)->first()
        : (clone $query)->latest()->first();

    if (! $lead) {
        throw new RuntimeException('No usable PIR snapshot lead found.');
    }

    $payload = app(\App\Services\SnapshotAiPayloadBuilder::class)->buildFromLead($lead);

    $directory = storage_path('app/rab');
    \Illuminate\Support\Facades\File::ensureDirectoryExists($directory);
    \Illuminate\Support\Facades\File::put(
        $directory . '/pir_snapshot_payload.json',
        json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)
    );

    $this->info("Wrote storage/app/rab/pir_snapshot_payload.json from lead {$lead->id}.");
})->purpose('Build the PIR snapshot POC payload JSON file');

Artisan::command('rab:poc-pir-snapshot-generate', function () {
    $payloadPath = storage_path('app/rab/pir_snapshot_payload.json');
    $responsePath = storage_path('app/rab/pir_snapshot_response.json');

    if (! \Illuminate\Support\Facades\File::exists($payloadPath)) {
        throw new RuntimeException('Missing storage/app/rab/pir_snapshot_payload.json.');
    }

    $payload = json_decode(\Illuminate\Support\Facades\File::get($payloadPath), true);
    if (! is_array($payload)) {
        throw new RuntimeException('PIR snapshot payload file is not valid JSON.');
    }

    $response = app(\App\Services\ReportService::class)->generate('pir_snapshot', $payload);

    \Illuminate\Support\Facades\File::put(
        $responsePath,
        json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)
    );

    $this->info('Wrote storage/app/rab/pir_snapshot_response.json.');
})->purpose('Generate the PIR snapshot POC AI response JSON file');

Artisan::command('rab:poc-pir-full-tier1-payload {assessmentId}', function (int $assessmentId) {
    $assessment = \App\Models\Assessment::query()
        ->with(['client', 'assessor', 'pillarScores', 'questionResponses'])
        ->whereKey($assessmentId)
        ->where('type', 'PIR')
        ->first();

    if (! $assessment) {
        throw new RuntimeException("No PIR assessment found for ID {$assessmentId}.");
    }

    $payload = app(\App\Services\FullReportAiPayloadBuilder::class)
        ->buildFromAssessment($assessment, 'Review');

    $directory = storage_path('app/rab');
    \Illuminate\Support\Facades\File::ensureDirectoryExists($directory);
    \Illuminate\Support\Facades\File::put(
        $directory . '/pir_full_tier1_payload.json',
        json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)
    );

    $questionResponseKeys = ['id', 'pillar_code', 'score', 'is_compliance'];
    $questionResponsesMinimal = collect($payload['question_responses'])
        ->every(fn (array $response) => array_keys($response) === $questionResponseKeys);

    $checklist = [
        'framework' => ($payload['framework'] ?? null) === 'PIR',
        'tier_review' => ($payload['tier'] ?? null) === 'Review',
        'overall_score' => array_key_exists('overall_score', $payload),
        'pillar_scores' => array_key_exists('pillar_scores', $payload),
        'pillar_names' => array_key_exists('pillar_names', $payload),
        'bri' => array_key_exists('bri', $payload),
        'vri' => array_key_exists('vri', $payload),
        'dmi' => array_key_exists('dmi', $payload),
        'rii' => array_key_exists('rii', $payload),
        'chi' => array_key_exists('chi', $payload),
        'question_responses_minimal' => $questionResponsesMinimal,
        'compliance_question_scores' => array_key_exists('compliance_question_scores', $payload),
        'consultant_name' => array_key_exists('consultant_name', $payload),
        'interview_count' => array_key_exists('interview_count', $payload),
        'documents_reviewed' => array_key_exists('documents_reviewed', $payload),
        'evidence_notes_present' => count($payload['evidence_notes']) > 0,
        'evidence_notes_count' => count($payload['evidence_notes']),
        'reporting_accuracy_fields' => array_key_exists('reporting_accuracy_risk', $payload)
            && array_key_exists('reporting_accuracy_evidence', $payload),
        'stakeholder_notes_null' => array_key_exists('stakeholder_notes', $payload)
            && $payload['stakeholder_notes'] === null,
        'payload_saved' => 'storage/app/rab/pir_full_tier1_payload.json',
    ];

    $this->line(json_encode($checklist, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
})->purpose('Build the PIR full Tier 1 POC payload JSON file');

Artisan::command('rab:poc-pir-full-tier1-prepare {assessmentId=15} {--dispatch : Dispatch the queue job instead of running it synchronously}', function (int $assessmentId) {
    if ($this->option('dispatch')) {
        \App\Jobs\GeneratePirFullTier1AiReport::dispatch($assessmentId);

        $this->line(json_encode([
            'assessment_id' => $assessmentId,
            'job' => 'GeneratePirFullTier1AiReport',
            'dispatch' => true,
            'status' => 'queued',
            'ai_called' => false,
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));

        return;
    }

    try {
        app(\App\Jobs\GeneratePirFullTier1AiReport::class, ['assessmentId' => $assessmentId])
            ->handle(app(\App\Services\PirFullTier1AiReportGenerationService::class));
    } catch (\Throwable $exception) {
        $assessment = \App\Models\Assessment::find($assessmentId);
        $state = $assessment?->ai_draft_json['pir_full_tier1_generation'] ?? [];

        $this->line(json_encode([
            'assessment_id' => $assessmentId,
            'job' => 'GeneratePirFullTier1AiReport',
            'dispatch' => false,
            'status' => $state['status'] ?? 'failed',
            'errors' => $state['errors'] ?? [$exception->getMessage()],
            'ai_called' => false,
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));

        return self::FAILURE;
    }

    $assessment = \App\Models\Assessment::find($assessmentId);
    $state = $assessment->ai_draft_json['pir_full_tier1_generation'] ?? [];

    $this->line(json_encode([
        'assessment_id' => $assessmentId,
        'job' => 'GeneratePirFullTier1AiReport',
        'dispatch' => false,
        'status' => $state['status'] ?? null,
        'prompt_key' => $state['prompt_key'] ?? null,
        'evidence_notes_count' => $state['evidence_notes_count'] ?? null,
        'question_response_count' => $state['question_response_count'] ?? null,
        'p1_p10_mapping_complete' => $state['p1_p10_mapping_complete'] ?? null,
        'indices_present' => $state['indices_present'] ?? null,
        'stakeholder_notes_null' => true,
        'ai_called' => false,
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
})->purpose('Prepare PIR full Tier 1 AI generation without calling AI');

Artisan::command('rab:poc-pir-full-tier1-ai-job {assessmentId=15} {--dispatch : Dispatch the queue job instead of running it synchronously}', function (int $assessmentId) {
    if ($this->option('dispatch')) {
        \App\Jobs\GeneratePirFullTier1AiReport::dispatch($assessmentId);

        $this->line(json_encode([
            'assessment_id' => $assessmentId,
            'job' => 'GeneratePirFullTier1AiReport',
            'dispatch' => true,
            'status' => 'queued',
            'ai_called' => false,
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));

        return;
    }

    try {
        app(\App\Jobs\GeneratePirFullTier1AiReport::class, ['assessmentId' => $assessmentId])
            ->handle(app(\App\Services\PirFullTier1AiReportGenerationService::class));
    } catch (\Throwable $exception) {
        $assessment = \App\Models\Assessment::find($assessmentId);
        $state = $assessment?->ai_draft_json['pir_full_tier1_generation'] ?? [];

        $this->line(json_encode([
            'assessment_id' => $assessmentId,
            'job' => 'GeneratePirFullTier1AiReport',
            'dispatch' => false,
            'status' => $state['status'] ?? 'failed',
            'errors' => $state['errors'] ?? [$exception->getMessage()],
            'ai_called' => false,
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));

        return self::FAILURE;
    }

    $assessment = \App\Models\Assessment::find($assessmentId);
    $state = $assessment->ai_draft_json['pir_full_tier1_generation'] ?? [];

    $this->line(json_encode([
        'assessment_id' => $assessmentId,
        'job' => 'GeneratePirFullTier1AiReport',
        'dispatch' => false,
        'status' => $state['status'] ?? null,
        'prompt_key' => $state['prompt_key'] ?? null,
        'evidence_notes_count' => $state['evidence_notes_count'] ?? null,
        'question_response_count' => $state['question_response_count'] ?? null,
        'p1_p10_mapping_complete' => $state['p1_p10_mapping_complete'] ?? null,
        'indices_present' => $state['indices_present'] ?? null,
        'stakeholder_notes_null' => true,
        'ai_called' => false,
        'ai_generation_orchestration_ready' => ($state['status'] ?? null) === 'generation_prepared',
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
})->purpose('Run the PIR full Tier 1 AI generation job validation without calling AI');

Artisan::command('rab:poc-sir-snapshot-payload {leadId?}', function (?int $leadId = null) {
    $query = \App\Models\Lead::query()
        ->where('type', 'SIR')
        ->where('assessment_type', 'SIR_SNAPSHOT')
        ->whereNotNull('answers_json')
        ->whereNotNull('index_scores_json');

    $lead = $leadId
        ? (clone $query)->whereKey($leadId)->first()
        : (clone $query)->latest()->first();

    if (! $lead) {
        throw new RuntimeException('No usable SIR snapshot lead found.');
    }

    $payload = app(\App\Services\SnapshotAiPayloadBuilder::class)->buildFromLead($lead);

    $directory = storage_path('app/rab');
    \Illuminate\Support\Facades\File::ensureDirectoryExists($directory);
    \Illuminate\Support\Facades\File::put(
        $directory . '/sir_snapshot_payload.json',
        json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)
    );

    $this->info("Wrote storage/app/rab/sir_snapshot_payload.json from lead {$lead->id}.");
})->purpose('Build the SIR snapshot POC payload JSON file');

Artisan::command('rab:poc-sir-snapshot-generate', function () {
    $payloadPath = storage_path('app/rab/sir_snapshot_payload.json');
    $responsePath = storage_path('app/rab/sir_snapshot_response.json');

    if (! \Illuminate\Support\Facades\File::exists($payloadPath)) {
        throw new RuntimeException('Missing storage/app/rab/sir_snapshot_payload.json.');
    }

    $payload = json_decode(\Illuminate\Support\Facades\File::get($payloadPath), true);
    if (! is_array($payload)) {
        throw new RuntimeException('SIR snapshot payload file is not valid JSON.');
    }

    $response = app(\App\Services\ReportService::class)->generate('sir_snapshot', $payload);

    \Illuminate\Support\Facades\File::put(
        $responsePath,
        json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)
    );

    $this->info('Wrote storage/app/rab/sir_snapshot_response.json.');
})->purpose('Generate the SIR snapshot POC AI response JSON file');
