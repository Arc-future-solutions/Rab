<?php

namespace App\Jobs;

use App\Models\Assessment;
use App\Services\AdminFullReportGenerationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class GenerateAdminFullReport implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public int $assessmentId
    ) {
    }

    public function handle(AdminFullReportGenerationService $service): void
    {
        $assessment = Assessment::find($this->assessmentId);

        if (! $assessment) {
            return;
        }

        $service->generate($assessment, false);
    }
}
