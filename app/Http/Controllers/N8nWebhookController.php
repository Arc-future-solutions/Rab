<?php

namespace App\Http\Controllers;

use App\Models\Assessment;
use App\Services\SnapshotReportService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class N8nWebhookController extends Controller
{
    public function __construct(private SnapshotReportService $snapshotReportService)
    {
    }

    public function receive(Request $request)
    {
        Log::info('N8n Webhook Received', ['payload' => $request->all()]);

        $request->validate([
            'lead_id' => 'required_without:assessment_id',
            'assessment_id' => 'required_without:lead_id',
        ]);

        if ($request->filled('assessment_id')) {
            return $this->receiveAssessmentReport($request);
        }

        $lead = \App\Models\Lead::find($request->lead_id);

        if (!$lead) {
            Log::error('N8n Webhook Error: Lead not found', ['lead_id' => $request->lead_id]);
            return response()->json(['error' => 'Lead not found'], 404);
        }

        $payload = $request->all();
        $structuredReport = $this->snapshotReportService->structuredReportFromPayload($payload);
        $rawRecommendation = $this->snapshotReportService->rawRecommendationFromPayload($payload);

        if ($rawRecommendation === null && $structuredReport === null) {
            Log::warning('N8n Webhook Warning: No snapshot content found in callback payload', [
                'lead_id' => $request->lead_id,
                'payload_keys' => array_keys($payload),
            ]);

            return response()->json(['error' => 'No snapshot content found'], 422);
        }

        $lead->update([
            'ai_recommendation' => $rawRecommendation,
            'snapshot_report_json' => $structuredReport,
        ]);

        Log::info('N8n Webhook Stored Snapshot Content', [
            'lead_id' => $lead->id,
            'stored_structured_report' => $structuredReport !== null,
            'stored_raw_recommendation' => $rawRecommendation !== null,
        ]);

        return response()->json(['status' => 'success']);
    }

    private function receiveAssessmentReport(Request $request)
    {
        $assessment = Assessment::find($request->assessment_id);

        if (!$assessment) {
            Log::error('N8n Webhook Error: Assessment not found', [
                'assessment_id' => $request->assessment_id,
            ]);

            return response()->json(['error' => 'Assessment not found'], 404);
        }

        $payload = $request->all();
        $draft = $this->extractStructuredAssessmentDraft($payload);
        $rawRecommendation = $this->rawAssessmentRecommendation($payload, $draft);

        if ($draft === null && $rawRecommendation === null) {
            Log::warning('N8n Webhook Warning: No assessment report content found in callback payload', [
                'assessment_id' => $assessment->id,
                'payload_keys' => array_keys($payload),
            ]);

            return response()->json(['error' => 'No assessment report content found'], 422);
        }

        $topRisks = $payload['top_5_risks'] ?? $payload['top_risks'] ?? null;
        if (is_array($topRisks)) {
            $topRisks = json_encode($topRisks);
        }

        $updates = [
            'ai_recommendation' => $rawRecommendation,
            'ai_draft_json' => $draft,
            'status' => 'completed',
        ];

        if ($topRisks !== null) {
            $updates['top_5_risks'] = $topRisks;
        }

        $assessment->update($updates);

        Log::info('N8n Webhook Stored Assessment Report Content', [
            'assessment_id' => $assessment->id,
            'stored_structured_report' => $draft !== null,
            'stored_raw_recommendation' => $rawRecommendation !== null,
        ]);

        return response()->json(['status' => 'success']);
    }

    private function rawAssessmentRecommendation(array $payload, ?array $draft): ?string
    {
        $raw = $payload['output'] ?? $payload['recommendation'] ?? null;

        if (is_string($raw) && trim($raw) !== '') {
            return $raw;
        }

        if (is_array($raw)) {
            return json_encode($raw, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }

        return $draft !== null
            ? json_encode($draft, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
            : null;
    }

    private function extractStructuredAssessmentDraft(mixed $value): ?array
    {
        $structuredKeys = [
            'cover_letter',
            'executive_position',
            'intelligence_dashboard',
            'stakeholder_intelligence',
            'intelligence_profile',
            'risk_register',
            'raid_summary',
            'root_cause_analysis',
            'priority_plan',
            'compliance_risk_signals',
            'final_position',
            'tier1_bridge',
        ];

        return $this->extractStructuredDraftFromValue($value, $structuredKeys);
    }

    private function extractStructuredDraftFromValue(mixed $value, array $structuredKeys): ?array
    {
        if (is_string($value)) {
            $decoded = $this->decodeJsonCandidate($value);

            return is_array($decoded)
                ? $this->extractStructuredDraftFromValue($decoded, $structuredKeys)
                : null;
        }

        if (!is_array($value)) {
            return null;
        }

        $draft = array_intersect_key($value, array_flip($structuredKeys));
        if ($draft !== []) {
            return $draft;
        }

        if (array_is_list($value)) {
            foreach ($value as $item) {
                $draft = $this->extractStructuredDraftFromValue($item, $structuredKeys);
                if ($draft !== null) {
                    return $draft;
                }
            }

            return null;
        }

        foreach (['report', 'assessment_report_json', 'output', 'recommendation', 'content', 'message', 'text', 'data', 'body', 'result', 'response', 'payload'] as $key) {
            if (!array_key_exists($key, $value)) {
                continue;
            }

            $draft = $this->extractStructuredDraftFromValue($value[$key], $structuredKeys);
            if ($draft !== null) {
                return $draft;
            }
        }

        return null;
    }

    private function decodeJsonCandidate(string $value): ?array
    {
        $candidate = trim($value);

        if ($candidate === '') {
            return null;
        }

        if (preg_match('/```(?:json)?\s*(\{.*\})\s*```/s', $candidate, $matches)) {
            $candidate = $matches[1];
        }

        $decoded = json_decode($candidate, true);

        if (json_last_error() === JSON_ERROR_NONE && is_string($decoded)) {
            $decoded = json_decode($decoded, true);
        }

        return json_last_error() === JSON_ERROR_NONE && is_array($decoded)
            ? $decoded
            : null;
    }
}
