<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Assessment;
use Illuminate\Http\Request;

class AssessmentController extends Controller
{
    public function index(Request $request)
    {
        $query = Assessment::with(['client', 'assessor']);

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

        // Also fetch Warm Leads (Public Assessments)
        $publicAssessments = \App\Models\Lead::where('lead_status', 'Warm')
            ->orderBy('created_at', 'desc')
            ->get();

        return view('admin.assessments.index', compact('assessments', 'publicAssessments'));
    }

    public function show($id)
    {
        // Try finding a formal assessment first
        $assessment = Assessment::with(['client', 'assessor', 'pillarScores', 'questionResponses'])->find($id);
        
        if (!$assessment) {
            // Fallback: Check if it's a Public Website Assessment (Warm Lead)
            $lead = \App\Models\Lead::find($id);
            if ($lead && $lead->lead_status === 'Warm') {
                // Map Lead to an Assessment-like object for the view
                $assessment = new Assessment([
                    'id' => $lead->id,
                    'name' => 'Public Website Health-Check',
                    'type' => $lead->type,
                    'overall_score' => $lead->overall_score,
                    'rag_status' => $lead->rag_status,
                    'created_at' => $lead->created_at,
                    'status' => 'approved',
                ]);
                
                // Mock dependencies
                $assessment->setRelation('client', new \App\Models\Client([
                    'company_name' => $lead->company,
                    'primary_contact' => $lead->name,
                ]));
                
                // Create virtual pillar scores from index_scores_json
                $pillarScores = [];
                $indexScores = $lead->index_scores_json ?? [];
                foreach ($indexScores as $name => $data) {
                    // PHI stores objects with 'score', ITSM stores raw scores
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
        
        return view('admin.assessments.show', compact('assessment'));
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
