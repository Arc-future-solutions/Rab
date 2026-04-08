<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Assessment;
use App\Models\Client;
use App\Models\AssessmentQuestionResponse;
use App\Models\AssessmentPillarScore;
use Illuminate\Http\Request;

class AssessmentScoringController extends Controller
{
    public function create()
    {
        $clients = Client::orderBy('company_name')->get();
        // In reality, link to snapshot submissions if available
        $leads = \App\Models\Lead::where('converted_to_client', false)->get();
        
        return view('admin.assessments.create', compact('clients', 'leads'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'client_id' => 'required|exists:clients,id',
            'type' => 'required|in:PHI,ITSM',
            'name' => 'required|string',
            'target_entity' => 'nullable|string',
            'snapshot_submission_id' => 'nullable|exists:leads,id',
        ]);

        $assessment = Assessment::create([
            'client_id' => $data['client_id'],
            'type' => $data['type'],
            'name' => $data['name'],
            'target_entity' => $data['target_entity'],
            'snapshot_submission_id' => $data['snapshot_submission_id'] ?? null,
            'status' => 'draft',
            'overall_score' => 0,
            'rag_status' => 'Red',
            'assessor_id' => auth()->id() ?? 1,
            'critical_flag' => false,
        ]);

        if ($data['type'] === 'PHI') {
            return redirect()->route('admin.assessments.score.phi', $assessment->id);
        } else {
            return redirect()->route('admin.assessments.score.itsm', $assessment->id);
        }
    }

    public function scorePhi(Assessment $assessment)
    {
        $assessment->load(['questionResponses', 'pillarScores']);
        
        return view('admin.assessments.score-phi', compact('assessment'));
    }

    public function scoreItsm(Assessment $assessment)
    {
        $assessment->load(['questionResponses', 'pillarScores']);
        
        return view('admin.assessments.score-itsm', compact('assessment'));
    }

    public function autoSave(Request $request, Assessment $assessment)
    {
        // Accept either a single question response update, or context/global fields update
        if ($request->has('question_code')) {
            $data = $request->only(['question_code', 'pillar_name', 'question', 'score', 'evidence_note', 'source_type', 'confidence', 'assessor_comment']);
            
            AssessmentQuestionResponse::updateOrCreate(
                [
                    'assessment_id' => $assessment->id,
                    'pillar_name' => $data['pillar_name'],
                    'question' => $data['question_code'] . ': ' . $data['question'],
                ],
                [
                    'score' => $data['score'] ?? 0,
                    'evidence_note' => $data['evidence_note'],
                    'confidence' => $data['confidence'] ?? 'medium',
                ]
            );

            // Re-calculate all scores!
            $this->recalculateScores($assessment);

            return response()->json(['status' => 'saved', 'calculated' => $assessment->fresh()]);
        }
        
        // If it's a context / global fields save
        if ($request->has('fields')) {
            $assessment->update($request->input('fields'));
            return response()->json(['status' => 'saved context']);
        }

        // If it's a pillar level commentary save
        if ($request->has('pillar_level_update')) {
            AssessmentPillarScore::updateOrCreate(
                [
                    'assessment_id' => $assessment->id,
                    'name' => $request->input('pillar_name'),
                ],
                $request->only(['commentary', 'key_risks', 'immediate_actions'])
            );
            return response()->json(['status' => 'saved pillar extra']);
        }

        return response()->json(['status' => 'ignored']);
    }

    public function generateReport(Assessment $assessment)
    {
        // Fake generation for now
        $assessment->update([
            'ai_draft_json' => [
                'executive_summary' => 'Automatically generated summary for ' . $assessment->name,
                'key_findings' => ['Need attention on ' . $assessment->rag_status . ' areas'],
                'recommendations' => ['Fix the red pillars immediately']
            ]
        ]);

        return redirect()->route('admin.assessments.show', $assessment)->with('success', 'AI Report Draft generated successfully.');
    }

    private function recalculateScores(Assessment $assessment)
    {
        $responses = AssessmentQuestionResponse::where('assessment_id', $assessment->id)->get();
        if ($responses->isEmpty()) {
            return;
        }

        // Calculate Pillar Averages
        $pillars = $responses->groupBy('pillar_name');
        
        $totalScoreSum = 0;
        $totalQuestions = 0;
        
        $pillarAverages = [];

        foreach ($pillars as $pillarName => $qs) {
            $avg = $qs->avg('score');
            
            $rag = 'Red';
            if ($avg >= 3.8) {
                $rag = 'Green';
            } elseif ($avg >= 2.5) {
                $rag = 'Amber';
            }

            AssessmentPillarScore::updateOrCreate(
                [
                    'assessment_id' => $assessment->id,
                    'name' => $pillarName
                ],
                [
                    'score' => $avg,
                    'rag_status' => $rag,
                    'critical_flag' => $avg < 2.5
                ]
            );

            $pillarAverages[$pillarName] = $avg;
            
            $totalScoreSum += $qs->sum('score');
            $totalQuestions += $qs->count();
        }

        $overallScore = $totalScoreSum / $totalQuestions;
        $overallRag = 'Red';
        if ($overallScore >= 3.8) {
            $overallRag = 'Green';
        } elseif ($overallScore >= 2.5) {
            $overallRag = 'Amber';
        }

        // Specific Indices logic
        $updates = [
            'overall_score' => $overallScore,
            'rag_status' => $overallRag,
        ];

        if ($assessment->type === 'PHI') {
            // VRI = average of P3 questions
            $p3_qs = $responses->filter(fn($r) => str_starts_with($r->question, 'P3_'));
            if ($p3_qs->isNotEmpty()) {
                $updates['vri'] = $p3_qs->avg('score');
            }

            // BRI = average(all P4 + all P6 + specific P7 questions)
            $bri_qs = $responses->filter(function($r) {
                return str_starts_with($r->question, 'P4_') || 
                       str_starts_with($r->question, 'P6_') || 
                       in_array(explode(':', $r->question)[0], ['P7_Q5', 'P7_Q6', 'P7_Q7', 'P7_Q12', 'P7_Q13', 'P7_Q14', 'P7_Q15']);
            });
            if ($bri_qs->isNotEmpty()) {
                $updates['bri'] = $bri_qs->avg('score');
            }

            // Critical Flag: P1 < 2.5 OR P2 < 2.5 OR P5 < 2.5 OR P7 < 2.5 OR overall < 2.8
            $p1_avg = $pillarAverages['P1 — Governance & Decision-Making'] ?? 5;
            $p2_avg = $pillarAverages['P2 — Planning & Delivery Control'] ?? 5;
            $p5_avg = $pillarAverages['P5 — Data Readiness & Migration'] ?? 5;
            $p7_avg = $pillarAverages['P7 — Cutover & Go-Live Readiness'] ?? 5;

            $updates['critical_flag'] = ($p1_avg < 2.5 || $p2_avg < 2.5 || $p5_avg < 2.5 || $p7_avg < 2.5 || $overallScore < 2.8);
        }

        if ($assessment->type === 'ITSM') {
            // SSI = P2 + P4 + P5
            $ssi_qs = $responses->filter(fn($r) => str_starts_with($r->question, 'P2_') || str_starts_with($r->question, 'P4_') || str_starts_with($r->question, 'P5_'));
            if ($ssi_qs->isNotEmpty()) $updates['ssi'] = $ssi_qs->avg('score');

            // SMI = P1 + P6 + P11
            $smi_qs = $responses->filter(fn($r) => str_starts_with($r->question, 'P1_') || str_starts_with($r->question, 'P6_') || str_starts_with($r->question, 'P11_'));
            if ($smi_qs->isNotEmpty()) $updates['smi'] = $smi_qs->avg('score');

            // BAU = P7 + P8
            $bau_qs = $responses->filter(fn($r) => str_starts_with($r->question, 'P7_') || str_starts_with($r->question, 'P8_'));
            if ($bau_qs->isNotEmpty()) $updates['bau_readiness'] = $bau_qs->avg('score');

            $p2_avg = $pillarAverages['P2 — Incident & Major Incident Management'] ?? 5;
            $p5_avg = $pillarAverages['P5 — Change & Release Management'] ?? 5;
            $p7_avg = $pillarAverages['P7 — Service Transition & BAU Readiness'] ?? 5;
            $p10_avg = $pillarAverages['P10 — Operational Resilience & Continuity'] ?? 5;
            
            $updates['critical_flag'] = ($p2_avg < 2.5 || $p5_avg < 2.5 || $p7_avg < 2.5 || $p10_avg < 2.5 || $overallScore < 2.8);
        }

        $assessment->update($updates);
    }
}
