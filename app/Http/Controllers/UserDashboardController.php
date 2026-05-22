<?php

namespace App\Http\Controllers;

use App\Models\Lead;
use App\Services\SnapshotReportService;
use Illuminate\Http\Request;

class UserDashboardController extends Controller
{
    public function __construct(private SnapshotReportService $snapshotReportService)
    {
    }

    public function index()
    {
        $leads = Lead::where('email', auth()->user()->email)
            ->orderBy('created_at', 'desc')
            ->get();
            
        // If there's only one assessment, redirect to it directly for a better experience
        if ($leads->count() === 1) {
            return redirect()->route('user.results', $leads->first()->id);
        }
            
        return view('user.dashboard', compact('leads'));
    }

    public function results(Lead $lead)
    {
        // Simple security check
        if ($lead->email !== auth()->user()->email && auth()->user()->role !== 'admin') {
            abort(403);
        }

        $results = [
            'type' => strtolower((string) $lead->type),
            'overall_score' => (float) $lead->overall_score,
            'rag_status' => (string) $lead->rag_status,
            'pillar_scores' => $this->pillarScoresFromLead($lead),
            'index_scores' => $lead->index_scores_json ?? [],
            'delivery_stage' => $lead->delivery_stage,
            'service_context' => $lead->service_context,
            'regulatory_context' => $lead->regulatory_context,
        ];

        $snapshotReport = $this->snapshotReportService->build(
            $results,
            $lead->ai_recommendation,
            $lead->snapshot_report_json
        );

        return view('user.results', compact('lead', 'snapshotReport'));
    }

    private function pillarScoresFromLead(Lead $lead): array
    {
        $scores = [];
        $topAreas = collect($lead->top_three_insight_areas_json ?? []);

        foreach ($topAreas as $area) {
            $name = $area['pillarOrDomain'] ?? null;

            if (! $name) {
                continue;
            }

            $code = strtok((string) $name, ' ');
            $score = (float) ($area['score'] ?? 0);

            $scores[$code ?: $name] = [
                'name' => $name,
                'score' => $score,
                'rag' => $score < 2.5 ? 'Red' : ($score < 3.8 ? 'Amber' : 'Green'),
            ];
        }

        return $scores;
    }
}
