<?php

namespace App\Services;

class AssessmentIndexCalculator
{
    private const PIR_WEIGHTS = [
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

    private const SIR_WEIGHTS = [
        'D1' => 1.4,
        'D2' => 1.4,
        'D3' => 1.0,
        'D4' => 1.2,
        'D5' => 1.3,
        'D6' => 1.2,
        'D7' => 1.2,
        'D8' => 1.2,
        'D9' => 1.0,
        'D10' => 1.3,
        'D11' => 1.2,
        'D12' => 1.1,
    ];

    public const PIR_DENOMINATOR = 12.1;
    public const SIR_DENOMINATOR = 14.5;

    private const RII_QUESTIONS = [
        'P1.F1',
        'P1.F2',
        'P1.F4',
        'P1.F5',
        'P2.F4',
        'P2.F5',
        'P2.F6',
    ];

    public static function calculatePirOverall(array $pillarScores): float
    {
        return self::weightedAverage($pillarScores, self::PIR_WEIGHTS, self::PIR_DENOMINATOR);
    }

    public static function calculatePir(array $pillarScores, array $answers = []): array
    {
        $p3 = self::score($pillarScores, 'P3');
        $p4 = self::score($pillarScores, 'P4');
        $p5 = self::score($pillarScores, 'P5');
        $p7 = self::score($pillarScores, 'P7');
        $p9 = self::score($pillarScores, 'P9');
        $p10 = self::score($pillarScores, 'P10');

        $dmi = round(($p9 * 1.0 + $p10 * 1.1) / 2.1, 2);
        if ($p10 < 2.5) {
            $dmi = min($dmi, 3.0);
        }

        return [
            'BRI' => round(($p3 * 1.3 + $p4 * 1.2 + $p5 * 1.2 + $p7 * 1.2) / 4.9, 2),
            'VRI' => round(($p3 * 1.3 + $p9 * 1.0) / 2.3, 2),
            'DMI' => $dmi,
            'RII' => self::averageAnswers($answers, self::RII_QUESTIONS),
        ];
    }

    public static function calculateSirOverall(array $pillarScores): float
    {
        return self::weightedAverage($pillarScores, self::SIR_WEIGHTS, self::SIR_DENOMINATOR);
    }

    public static function calculateSir(array $pillarScores): array
    {
        $d2 = self::score($pillarScores, 'D2');
        $d4 = self::score($pillarScores, 'D4');
        $d5 = self::score($pillarScores, 'D5');
        $d6 = self::score($pillarScores, 'D6');
        $d7 = self::score($pillarScores, 'D7');
        $d8 = self::score($pillarScores, 'D8');
        $d11 = self::score($pillarScores, 'D11');
        $d12 = self::score($pillarScores, 'D12');

        $smi = round(($d4 * 1.2 + $d6 * 1.2 + $d11 * 1.2) / 3.6, 2);
        $simi = round(($d4 * 1.2 + $d11 * 1.2 + $d12 * 1.1) / 3.5, 2);

        return [
            'SSI' => round(($d2 * 1.4 + $d5 * 1.3) / 2.7, 2),
            'SMI' => $smi,
            'SIMI' => $simi,
            'BAURI' => round(($d7 + $d8) / 2, 2),
            'smi_simi_delta' => round($smi - $simi, 2),
        ];
    }

    public static function normalizeQuestionAnswers(array $answers): array
    {
        $normalized = [];

        foreach ($answers as $question => $score) {
            $code = trim(explode(':', (string) $question, 2)[0]);
            $normalized[$code] = $score;
        }

        return $normalized;
    }

    private static function score(array $scores, string $code): float
    {
        $value = $scores[$code] ?? 0;

        if (is_array($value)) {
            $value = $value['score'] ?? 0;
        }

        if (is_object($value) && isset($value->score)) {
            $value = $value->score;
        }

        return (float) $value;
    }

    private static function averageAnswers(array $answers, array $questionCodes): ?float
    {
        $scores = [];

        foreach ($questionCodes as $questionCode) {
            if (isset($answers[$questionCode]) && is_numeric($answers[$questionCode])) {
                $scores[] = (float) $answers[$questionCode];
            }
        }

        return $scores === [] ? null : round(array_sum($scores) / count($scores), 2);
    }

    private static function weightedAverage(array $scores, array $weights, float $denominator): float
    {
        $weightedScoreSum = 0.0;

        foreach ($weights as $code => $weight) {
            $weightedScoreSum += self::score($scores, $code) * $weight;
        }

        return round($weightedScoreSum / $denominator, 2);
    }
}
