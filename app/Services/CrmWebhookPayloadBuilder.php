<?php

namespace App\Services;

use App\Models\Lead;
use Illuminate\Support\Carbon;

class CrmWebhookPayloadBuilder
{
    public function __construct(private DiagnosticOutcomeService $outcomes)
    {
    }

    public function build(Lead $lead, array $results, array $context, Carbon $consentTimestamp): array
    {
        $pillarScores = $results['pillar_scores'] ?? [];
        $overallScore = (float) ($results['overall_score'] ?? 0);
        $criticalFlag = $this->outcomes->criticalFlag($pillarScores);

        return [
            'assessmentId' => $lead->id,
            'assessmentType' => strtoupper((string) $lead->type),
            'tier' => 'Snapshot',
            'timestamp' => now()->toIso8601String(),
            'lead' => [
                'name' => $lead->name,
                'email' => $lead->email,
                'company' => $lead->company,
                'phone' => $lead->phone,
            ],
            'consentGiven' => true,
            'consentTimestamp' => $consentTimestamp->toIso8601String(),
            'overallScore' => round($overallScore, 2),
            'ragStatus' => $results['rag_status'] ?? 'Amber',
            'actionIndicator' => $this->outcomes->actionIndicator($overallScore),
            'criticalFlag' => $criticalFlag ?: null,
            'alerts' => $this->outcomes->alerts($pillarScores),
            'leadPriority' => $this->outcomes->leadPriority($overallScore, $pillarScores),
            'topThreeInsightAreas' => $this->outcomes->topThreeInsightAreas($pillarScores),
            'assessmentContext' => [
                'framework' => strtoupper((string) $lead->type),
                'deliveryStage' => $context['delivery_stage'] ?? null,
                'serviceContext' => $context['service_context'] ?? null,
                'regulatoryContext' => $context['regulatory_context'] ?? null,
            ],
            'scoring_version' => '1.0',
        ];
    }
}
