<?php

namespace Tests\Unit;

use App\Services\AssessmentIndexCalculator;
use PHPUnit\Framework\TestCase;

class AssessmentIndexCalculatorTest extends TestCase
{
    public function test_pir_indices_match_build_guide_examples(): void
    {
        $bri = AssessmentIndexCalculator::calculatePir([
            'P3' => 3.0,
            'P4' => 4.0,
            'P5' => 2.5,
            'P7' => 3.5,
        ]);

        $vri = AssessmentIndexCalculator::calculatePir([
            'P3' => 4.5,
            'P9' => 2.5,
        ]);

        $rii = AssessmentIndexCalculator::calculatePir([], [
            'P1.F1' => 2.5,
            'P1.F2' => 2.5,
            'P1.F4' => 2.5,
            'P1.F5' => 2.5,
            'P2.F4' => 2.5,
            'P2.F5' => 2.5,
            'P2.F6' => 2.5,
            'P2.F9' => 5.0,
        ]);

        $this->assertSame(3.24, $bri['BRI']);
        $this->assertSame(3.63, $vri['VRI']);
        $this->assertSame(2.5, $rii['RII']);
    }

    public function test_pir_dmi_is_capped_when_p10_is_below_threshold(): void
    {
        $indices = AssessmentIndexCalculator::calculatePir([
            'P9' => 4.0,
            'P10' => 2.3,
        ]);

        $this->assertSame(3.0, $indices['DMI']);
    }

    public function test_sir_indices_match_build_guide_examples(): void
    {
        $indices = AssessmentIndexCalculator::calculateSir([
            'D2' => 3.5,
            'D4' => 3.0,
            'D5' => 2.8,
            'D6' => 3.5,
            'D7' => 2.8,
            'D8' => 3.4,
            'D11' => 3.2,
            'D12' => 2.5,
        ]);

        $this->assertSame(3.16, $indices['SSI']);
        $this->assertSame(3.23, $indices['SMI']);
        $this->assertSame(2.91, $indices['SIMI']);
        $this->assertSame(3.1, $indices['BAURI']);
        $this->assertSame(0.32, $indices['smi_simi_delta']);
    }
}
