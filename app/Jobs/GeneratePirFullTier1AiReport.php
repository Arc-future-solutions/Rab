<?php

namespace App\Jobs;

use App\Services\PirFullTier1AiReportGenerationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class GeneratePirFullTier1AiReport implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public int $assessmentId
    ) {
    }

    public function handle(PirFullTier1AiReportGenerationService $service): void
    {
        $service->prepare($this->assessmentId);
    }
}
