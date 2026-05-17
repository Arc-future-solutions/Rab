<?php

namespace App\Services;

class DiagnosticOutcomeService
{
    public function criticalFlag(array $pillarScores): bool
    {
        foreach ($pillarScores as $score) {
            if (($score['is_critical'] ?? false) && (float) ($score['score'] ?? 0) < 3.0) {
                return true;
            }
        }

        return false;
    }

    public function leadPriority(float $overallScore, array $pillarScores): string
    {
        if ($overallScore < 3.0 || $this->criticalFlag($pillarScores)) {
            return 'High';
        }

        return $overallScore <= 3.5 ? 'Medium' : 'Low';
    }

    public function actionIndicator(float $overallScore): string
    {
        if ($overallScore < 3.0) {
            return 'Immediate Intervention Required';
        }

        if ($overallScore <= 3.5) {
            return 'Targeted Review Recommended';
        }

        return 'Maintain and Monitor';
    }

    public function alerts(array $pillarScores): array
    {
        $alerts = [];

        foreach ($pillarScores as $code => $score) {
            if ((float) ($score['score'] ?? 0) < 3.0) {
                $alerts[] = sprintf(
                    '%s below 3.0',
                    is_string($code) ? $code : ($score['name'] ?? 'Area')
                );
            }
        }

        return $alerts;
    }

    public function topThreeInsightAreas(array $pillarScores): array
    {
        return collect($pillarScores)
            ->map(function (array $score, $code) {
                $value = (float) ($score['score'] ?? 0);

                return [
                    'pillarOrDomain' => $score['name'] ?? (string) $code,
                    'score' => round($value, 2),
                    'insightLabel' => $this->insightLabel($value),
                ];
            })
            ->sortBy('score')
            ->take(3)
            ->values()
            ->all();
    }

    public function insightLabel(float $score): string
    {
        if ($score >= 4.0) {
            return 'Controlled';
        }

        if ($score >= 3.0) {
            return 'At Risk';
        }

        if ($score >= 2.0) {
            return 'Weak';
        }

        return 'Critical Failure';
    }
}
