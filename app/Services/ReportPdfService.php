<?php

namespace App\Services;

use App\Models\Assessment;
use Illuminate\Support\Str;
use Spatie\Browsershot\Browsershot;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ReportPdfService
{
    public function renderHtml(Assessment $assessment): string
    {
        $assessment->loadMissing(['client', 'assessor', 'pillarScores', 'questionResponses']);

        return view('admin.assessments.pdf-report', [
            'assessment' => $assessment,
            'report' => $assessment->ai_draft_json ?? [],
            'meta' => $this->metadata($assessment),
        ])->render();
    }

    public function download(Assessment $assessment): BinaryFileResponse
    {
        $directory = storage_path('app/private/reports/tmp');
        if (!is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        $filename = $this->filename($assessment);
        $path = $directory . '/' . $filename;

        Browsershot::html($this->renderHtml($assessment))
            ->noSandbox()
            ->addChromiumArguments(['disable-setuid-sandbox'])
            ->waitUntilNetworkIdle()
            ->format('A4')
            ->margins(10, 10, 10, 10)
            ->save($path);

        return response()->download($path, $filename)->deleteFileAfterSend(true);
    }

    private function metadata(Assessment $assessment): array
    {
        $isPir = $assessment->type === 'PIR';
        $isBriefing = $assessment->report_tier === 'Tier 2 Full';
        $subject = $assessment->target_entity ?: $assessment->name;

        return [
            'is_pir' => $isPir,
            'is_briefing' => $isBriefing,
            'client' => $assessment->client->company_name ?? 'Client',
            'subject' => $subject,
            'context' => $isPir ? $assessment->delivery_stage : $assessment->service_context,
            'report_type' => $this->reportType($assessment, true),
            'report_label' => $this->reportType($assessment, false),
            'date' => now()->format('F Y'),
            'scoring_version' => $assessment->scoring_version ?? '1.0',
        ];
    }

    private function reportType(Assessment $assessment, bool $caps): string
    {
        $framework = $assessment->type === 'PIR' ? 'Programme' : 'Service';
        $product = $assessment->report_tier === 'Tier 2 Full' ? 'Intelligence Briefing' : 'Intelligence Review';
        $label = "{$framework} {$product}";

        return $caps ? strtoupper("RAB {$label}") : $label;
    }

    private function filename(Assessment $assessment): string
    {
        $client = Str::slug($assessment->client->company_name ?? 'client');
        $subject = Str::slug($assessment->target_entity ?: $assessment->name ?: 'assessment');

        return "rab-{$client}-{$subject}-report.pdf";
    }
}
