<?php

namespace App\Jobs;

use App\Models\Lead;
use App\Services\AiReportGenerationService;
use App\Services\SnapshotReportService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class SendToN8nWebhook implements ShouldQueue
{
    use Queueable;

    public array $data;

    /**
     * Create a new job instance.
     */
    public function __construct(array $data)
    {
        $this->data = $data;
    }

    /**
     * Execute the job.
     */
    public function handle(
        AiReportGenerationService $aiReportGeneration,
        SnapshotReportService $snapshotReportService
    ): void
    {
        $lead = isset($this->data['lead_id']) ? Lead::find($this->data['lead_id']) : null;

        if (! $lead) {
            Log::warning('Snapshot AI generation skipped because lead was not found.', [
                'lead_id' => $this->data['lead_id'] ?? null,
            ]);
            return;
        }

        $promptKey = $this->data['prompt_key'] ?? null;
        $systemPrompt = $this->data['system_prompt'] ?? null;
        $aiPayload = $this->data['ai_payload'] ?? null;

        if (! $promptKey || ! $systemPrompt || ! is_array($aiPayload)) {
            Log::warning('Snapshot AI generation skipped because prompt payload is incomplete.', [
                'lead_id' => $lead->id,
                'prompt_key' => $promptKey,
            ]);
            return;
        }

        try {
            Log::info('Generating queued snapshot report with internal AI service', [
                'lead_id' => $lead->id,
                'prompt_key' => $promptKey,
            ]);

            $responseData = $aiReportGeneration->generate($promptKey, $systemPrompt, $aiPayload, [
                'lead_id' => $lead->id,
                'type' => $this->data['type'] ?? null,
                'is_full' => $this->data['is_full'] ?? false,
                'results' => $this->data['results'] ?? null,
                'answers' => $this->data['answers'] ?? null,
                'assessment_context' => $this->data['assessment_context'] ?? null,
                'submitted_at' => $this->data['submitted_at'] ?? null,
            ]);

            $structuredReport = $snapshotReportService->structuredReportFromPayload($responseData);
            $rawRecommendation = $snapshotReportService->rawRecommendationFromPayload($responseData);

            if ($structuredReport || $rawRecommendation) {
                $lead->update([
                    'ai_recommendation' => $rawRecommendation,
                    'snapshot_report_json' => $structuredReport,
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Queued snapshot AI generation exception', [
                'lead_id' => $lead->id,
                'message' => $e->getMessage()
            ]);
        }
    }
}
