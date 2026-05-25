<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Assessment;
use App\Models\Lead;
use App\Services\ReportService;
use App\Services\SnapshotAiPayloadBuilder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Services\AssessmentIndexCalculator;

use App\Traits\HasAssessmentQuestions;
use Throwable;

class AssessmentController extends Controller
{
    use HasAssessmentQuestions;

    public function index(Request $request)
    {
        $query = Assessment::with(['client', 'assessor']);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhereHas('client', function($cq) use ($search) {
                      $cq->where('company_name', 'like', "%{$search}%");
                  });
            });
        }

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        if ($request->filled('rag_status')) {
            $query->where('rag_status', $request->rag_status);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
       
        $assessments = $query->orderBy('created_at', 'desc')->get();

        // Public website snapshot submissions should appear regardless of CRM sales status.
        $publicAssessments = \App\Models\Lead::whereIn('assessment_type', [
                'PHI_SNAPSHOT',
                'ITSM_SNAPSHOT',
                'PIR_SNAPSHOT',
                'SIR_SNAPSHOT',
            ])
            ->whereIn('type', ['PHI', 'ITSM', 'PIR', 'SIR'])
            ->orderBy('created_at', 'desc')
            ->get();

        return view('admin.assessments.index', compact('assessments', 'publicAssessments'));
    }

    public function show($id)
    { 
        $assessment = Assessment::with(['client', 'assessor', 'pillarScores', 'questionResponses'])->find($id);
        $lead = $assessment && $assessment->snapshot_submission_id
            ? Lead::find($assessment->snapshot_submission_id)
            : Lead::find($id);
   
        if (!$assessment) {

            // Fallback: Check if it's a public website snapshot submission.
            if ($lead && in_array($lead->assessment_type, ['PHI_SNAPSHOT', 'ITSM_SNAPSHOT', 'PIR_SNAPSHOT', 'SIR_SNAPSHOT'], true)) {
                //  dd($lead->ai_recommendation);
                // Map Lead to an Assessment-like object for the view
                $assessment = new Assessment([
                    'id' => $lead->id,
                    'name' => 'Public Website Health-Check',
                    'type' => $lead->type,
                    'overall_score' => $lead->overall_score,
                    'rag_status' => $lead->rag_status,
                    'created_at' => $lead->created_at,
                    'status' => 'approved',
                    'report_tier' => 'Snapshot',
                ]);

                // Map specific index scores if they exist in lead
                $indexScores = $lead->index_scores_json ?? [];
                $assessment->bri = is_array($indexScores['BRI'] ?? null) ? ($indexScores['BRI']['score'] ?? null) : ($indexScores['BRI'] ?? null);
                $assessment->vri = is_array($indexScores['VRI'] ?? null) ? ($indexScores['VRI']['score'] ?? null) : ($indexScores['VRI'] ?? null);
                $assessment->ssi = is_array($indexScores['SSI'] ?? null) ? ($indexScores['SSI']['score'] ?? null) : ($indexScores['SSI'] ?? null);
                $assessment->smi = is_array($indexScores['SMI'] ?? null) ? ($indexScores['SMI']['score'] ?? null) : ($indexScores['SMI'] ?? null);
                $assessment->simi = is_array($indexScores['SIMI'] ?? null) ? ($indexScores['SIMI']['score'] ?? null) : ($indexScores['SIMI'] ?? null);
                $assessment->dmi = is_array($indexScores['DMI'] ?? null) ? ($indexScores['DMI']['score'] ?? null) : ($indexScores['DMI'] ?? null);
                $assessment->rii = is_array($indexScores['RII'] ?? null) ? ($indexScores['RII']['score'] ?? null) : ($indexScores['RII'] ?? null);
                $assessment->chi = is_array($indexScores['CHI'] ?? null) ? ($indexScores['CHI']['score'] ?? null) : ($indexScores['CHI'] ?? null);
                
                $bauVal = $indexScores['BAURI'] ?? $indexScores['BAU Readiness'] ?? null;
                $assessment->bau_readiness = is_array($bauVal) ? ($bauVal['score'] ?? null) : $bauVal;

                // Fallback for legacy leads (PHI/PIR)
                if (in_array($assessment->type, ['PHI', 'PIR'])) {
                    $pScores = [];
                    foreach ($indexScores as $k => $v) {
                        $pScores[$k] = is_array($v) ? ($v['score'] ?? 0) : $v;
                    }
                    if ($assessment->bri === null) {
                        if ($assessment->type === 'PIR') {
                            $assessment->bri = AssessmentIndexCalculator::calculatePir($pScores, $lead->answers_json ?? [])['BRI'];
                        } else {
                            // Legacy PHI
                            $briSum = ($pScores['P1'] ?? 0) + ($pScores['P2'] ?? 0) + ($pScores['P4'] ?? 0) + ($pScores['P6'] ?? 0) + ($pScores['P7'] ?? 0);
                            $assessment->bri = round($briSum / 5, 2);
                        }
                    }
                    if ($assessment->vri === null) {
                        $assessment->vri = AssessmentIndexCalculator::calculatePir($pScores, $lead->answers_json ?? [])['VRI'];
                    }
                    if ($assessment->dmi === null && $assessment->type === 'PIR') {
                        $assessment->dmi = AssessmentIndexCalculator::calculatePir($pScores, $lead->answers_json ?? [])['DMI'];
                    }
                    if ($assessment->rii === null && $assessment->type === 'PIR') {
                        $assessment->rii = AssessmentIndexCalculator::calculatePir($pScores, $lead->answers_json ?? [])['RII'];
                    }
                    if ($assessment->chi === null && $assessment->type === 'PIR') {
                        $complianceQuestions = ['P1.F8', 'P3.F11', 'P5.F8', 'P5.F9', 'P8.F8', 'P8.F9'];
                        $chi_qs = $assessment->questionResponses->filter(function($r) use ($complianceQuestions) {
                            foreach ($complianceQuestions as $code) {
                                if (str_starts_with($r->question, $code . ':')) return true;
                            }
                            return false;
                        });
                        if ($chi_qs->isNotEmpty()) {
                            $assessment->chi = round($chi_qs->avg('score'), 2);
                        }
                    }
                }

                // Fallback for SIR indices
                if ($assessment->type === 'SIR') {
                    $pScores = [];
                    foreach ($indexScores as $k => $v) {
                        $pScores[$k] = is_array($v) ? ($v['score'] ?? 0) : $v;
                    }

                    if ($assessment->ssi === null) {
                        $assessment->ssi = $pScores['SSI'] ?? AssessmentIndexCalculator::calculateSir($pScores)['SSI'];
                    }
                    if ($assessment->smi === null) {
                        $assessment->smi = $pScores['SMI'] ?? AssessmentIndexCalculator::calculateSir($pScores)['SMI'];
                    }
                    if ($assessment->simi === null) {
                        $assessment->simi = $pScores['SIMI'] ?? AssessmentIndexCalculator::calculateSir($pScores)['SIMI'];
                    }
                    if ($assessment->bau_readiness === null) {
                        $assessment->bau_readiness = $pScores['BAURI'] ?? $pScores['BAU Readiness'] ?? AssessmentIndexCalculator::calculateSir($pScores)['BAURI'];
                    }
                    if ($assessment->chi === null) {
                        $assessment->chi = $pScores['CHI'] ?? $pScores['D10'] ?? $pScores['Resilience'] ?? null;
                    }
                }
                
                // Mock dependencies
                $assessment->setRelation('client', new \App\Models\Client([
                    'company_name' => $lead->company,
                    'primary_contact' => $lead->name,
                ]));
                
                // Create virtual pillar scores from index_scores_json
                $pillarScores = [];
                $indexScores = $lead->index_scores_json ?? [];
                
                // List of indices to exclude from the pillar scores breakdown
                $excludedIndices = ['BRI', 'VRI', 'DMI', 'RII', 'CHI', 'SSI', 'SMI', 'SIMI', 'BAURI', 'smi_simi_delta'];

                foreach ($indexScores as $name => $data) {
                    // Skip if this is an index, not a pillar
                    if (in_array($name, $excludedIndices)) {
                        continue;
                    }

                    // PHI/PIR stores objects with 'score', ITSM/SIR stores raw scores
                    $scoreValue = is_array($data) ? ($data['score'] ?? 0) : $data;
                    $pillarName = is_array($data) ? ($data['name'] ?? $name) : $name;

                    $pillarScores[] = new \App\Models\AssessmentPillarScore([
                        'name' => $pillarName,
                        'score' => $scoreValue,
                        'rag_status' => $this->calculateRag($scoreValue),
                    ]);
                }
                $assessment->setRelation('pillarScores', collect($pillarScores));
                
                // Create virtual question responses from answers_json
                $responses = [];
                $answers = $lead->answers_json ?? [];
                foreach ($answers as $qKey => $ans) {
                    $responses[] = new \App\Models\AssessmentQuestionResponse([
                        'pillar_name' => 'General',
                        'question' => str_replace('_', ' ', $qKey),
                        'score' => is_numeric($ans) ? $ans : 0,
                        'evidence_note' => is_numeric($ans) ? 'Self-Assessment Score: ' . $ans : $ans,
                        'confidence' => 'N/A'
                    ]);
                }
                $assessment->setRelation('questionResponses', collect($responses));
                $assessment->is_public_lead = true;
            } else {
                abort(404);
            }
        }
        
        // Fetch all questions for both frameworks from DB for the view metadata
        $frameworkQuestions = [];
        $fws = \App\Models\AssessmentFramework::with(['pillars.questions' => function($q) {
            $q->where('is_active', true);
        }])->get();

        $type3File = base_path('rab_type3_questions_anchor_responses.json');
        $type3Map = [];
        if (file_exists($type3File)) {
            $type3Data = json_decode(file_get_contents($type3File), true);
            if (isset($type3Data['sections'])) {
                foreach ($type3Data['sections'] as $section) {
                    if (isset($section['questions'])) {
                        foreach ($section['questions'] as $t3q) {
                            $type3Map[$t3q['id']] = $t3q['anchor_responses'] ?? [];
                        }
                    }
                }
            }
        }

        foreach ($fws as $fw) {
            $fwData = [];
            foreach ($fw->pillars as $pillar) {
                $qs = [];
                foreach ($pillar->questions as $q) {
                    $anchors = $q->score_anchors;
                    if (is_string($anchors)) {
                        $anchors = json_decode($anchors, true) ?? [];
                    }
                    if (!is_array($anchors)) {
                        $anchors = [];
                    }

                    $cards = $type3Map[$q->question_code] ?? null;
                    if ($q->question_type == 3 && empty($cards)) {
                        $cards = [];
                        foreach ($anchors as $score => $resp) {
                            $cards[] = ['score' => $score, 'response' => $resp];
                        }
                    }

                    $qs[$q->question_code] = [
                        'text' => $q->question_text,
                        'anchors' => $anchors,
                        'type' => $q->question_type,
                        'type3_cards' => $cards,
                    ];
                }
                $fwData[$pillar->code] = [
                    'name' => $pillar->name,
                    'questions' => $qs
                ];
            }
            $frameworkQuestions[$fw->code] = $fwData;
        }
            // dd($lead);
        
        return view('admin.assessments.show', compact('assessment', 'lead', 'frameworkQuestions'));
    }

    public function regenerateSnapshotAi(
        Request $request,
        int $id,
        ReportService $reportService,
        SnapshotAiPayloadBuilder $snapshotAiPayloadBuilder
    ) {
        $lead = Lead::whereKey($id)
            ->whereIn('assessment_type', ['PIR_SNAPSHOT', 'SIR_SNAPSHOT'])
            ->first();

        if (! $lead) {
            return redirect()->back()->with('error', 'AI insights can only be regenerated for PIR or SIR snapshot leads.');
        }

        $promptKey = $snapshotAiPayloadBuilder->promptKey($lead->type);

        try {
            $payload = $snapshotAiPayloadBuilder->buildFromLead($lead);
            $structuredReport = $reportService->generate($promptKey, $payload);

            $lead->update([
                'ai_recommendation' => json_encode($structuredReport, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                'snapshot_report_json' => $structuredReport,
            ]);

            return redirect()->back()->with('success', 'AI insights regenerated successfully.');
        } catch (Throwable $e) {
            Log::error('Admin snapshot AI regeneration failed', [
                'lead_id' => $lead->id,
                'prompt_key' => $promptKey,
                'message' => $e->getMessage(),
            ]);

            return redirect()->back()->with('error', 'AI regeneration failed: ' . $e->getMessage());
        }
    }

    private function calculateRag($score)
    {
        if ($score < 2.5) return 'Red';
        if ($score < 3.8) return 'Amber';
        return 'Green';
    }

    public function updateStatus(Request $request, Assessment $assessment)
    {
        $request->validate([
            'status' => 'required|in:draft,in_progress,approved'
        ]);

        $assessment->update(['status' => $request->status]);

        return redirect()->back()->with('success', 'Assessment status updated successfully.');
    }
}
