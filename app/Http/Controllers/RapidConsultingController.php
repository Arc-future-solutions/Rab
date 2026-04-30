<?php

namespace App\Http\Controllers;

use App\Models\Lead;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Session;
use Carbon\Carbon;
use App\Traits\HasAssessmentQuestions;

class RapidConsultingController extends Controller
{
    use HasAssessmentQuestions;
    private function getFrameworkDefinitions(string $type)
    {
        $framework = \App\Models\AssessmentFramework::where('code', strtoupper($type))->first();
        if (!$framework) return [];

        return \App\Models\AssessmentPillar::where('framework_id', $framework->id)
            ->get()
            ->keyBy('code')
            ->toArray();
    }

    private function getFrameworkQuestions(string $type)
    {
        $framework = \App\Models\AssessmentFramework::where('code', strtoupper($type))->first();
        if (!$framework) return [];

        $pillars = \App\Models\AssessmentPillar::where('framework_id', $framework->id)
            ->with(['questions' => function($q) {
                $q->where('level', 'snapshot')->where('is_active', true);
            }])
            ->get();

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

        $data = [];
        foreach ($pillars as $pillar) {
            $qs = [];
            foreach ($pillar->questions as $q) {
                $anchors = $q->score_anchors;
                if (is_string($anchors)) {
                    $anchors = json_decode($anchors, true) ?? [];
                }
                
                $cards = $type3Map[$q->question_code] ?? null;
                if ($q->question_type == 3 && empty($cards)) {
                    foreach ($anchors as $score => $resp) {
                        $cards[] = ['score' => $score, 'response' => $resp];
                    }
                }

                $qs[$q->question_code] = [
                    'text' => $q->question_text,
                    'type' => $q->question_type,
                    'label' => $q->question_type_label,
                    'anchors' => $anchors,
                    'type3_cards' => $cards,
                ];
            }
            $data[$pillar->code] = [
                'name' => $pillar->name,
                'questions' => $qs
            ];
        }
        return $data;
    }



    public function index()
    {
        $hasPhi = false;
        $hasItsm = false;

        if (auth()->check()) {
            $user = auth()->user();
            $leads = Lead::where('email', $user->email)
                ->where('lead_status', 'Warm')
                ->get();

            $hasPhi = $leads->where('type', 'PIR')->isNotEmpty();
            $hasItsm = $leads->where('type', 'SIR')->isNotEmpty();
        }

        return view('rapid-consulting.index', [
            'hasPhi' => $hasPhi,
            'hasItsm' => $hasItsm,
        ]);
    }

    private function loadExistingToSession($existingLead, $userData)
    {
        $type = strtolower($existingLead->type);
        $allQuestions = $this->getFrameworkQuestions($type);
        $frameworkDef = $this->getFrameworkDefinitions($type);
        $answers = $existingLead->answers_json;

        $pillarScores = [];
        $weightedScoreSum = 0;
        $weightSum = 0;

        foreach ($allQuestions as $pillarCode => $pillar) {
            $pillarSum = 0;
            $pillarCount = 0;
            foreach ($pillar['questions'] as $qCode => $qData) {
                $score = (int)($answers[$qCode] ?? 0);
                $pillarSum += $score;
                $pillarCount++;
            }
            $avgPillar = $pillarCount > 0 ? round($pillarSum / $pillarCount, 2) : 0;
            $weight = $frameworkDef[$pillarCode]['weight'] ?? 1.0;
            $weightedPillarScore = round($avgPillar * $weight, 2);
            
            $pillarScores[$pillarCode] = [
                'name' => $pillar['name'],
                'score' => $avgPillar,
                'rag' => $this->rag($avgPillar),
                'is_critical' => $frameworkDef[$pillarCode]['critical'] ?? false
            ];

            $weightedScoreSum += $weightedPillarScore;
            $weightSum += $weight;
        }

        $overallScore = $weightSum > 0 ? round($weightedScoreSum / $weightSum, 2) : 0;
        $indexScores = $existingLead->index_scores_json;

        $results = [
            'overall_score' => $overallScore,
            'rag_status' => $this->rag($overallScore),
            'pillar_scores' => $pillarScores, 
            'index_scores' => $indexScores,
            'type' => $type,
            'user' => $userData,
            'recommendation' => $this->generateRecommendation($overallScore, $pillarScores, $type)
        ];
        
        Session::put('rc_results', $results);
        Session::put('rc_user', $userData);
        Session::put('rc_type', $type);
        Session::put('rc_answers', $answers);
    }


    public function start(Request $request)
    {
        // If type is pre-selected, go straight to assessment (session-based)
        if ($request->has('type') && in_array(strtolower($request->type), ['pir', 'sir'])) {
            Session::put('rc_type', $request->type);
            return redirect()->route('rapid-consulting.assessment');
        }

        return redirect()->route('rapid-consulting.select-type');
    }

    public function selectType()
    {
        $completedTypes = [];
        if (auth()->check()) {
            $completedTypes = \App\Models\Lead::where('email', auth()->user()->email)
                ->where('lead_status', 'Warm')
                ->pluck('type')
                ->map(fn($t) => strtolower($t))
                ->toArray();
        }

        return view('rapid-consulting.select-type', compact('completedTypes'));
    }


    public function storeType(Request $request)
    {
        $request->validate([
            'type' => 'required|in:pir,sir'
        ]);

        Session::put('rc_type', $request->type);
        Session::forget(['rc_answers', 'rc_results']);

        return redirect()->route('rapid-consulting.assessment');
    }

    public function assessment()
    {
        $type = Session::get('rc_type');
        if (!$type) {
            return redirect()->route('rapid-consulting.select-type');
        }

        $questions = $this->getFrameworkQuestions($type);

        return view('rapid-consulting.assessment', compact('questions', 'type'));
    }


    public function submit(Request $request)
    {
        $type = Session::get('rc_type');
        if (!$type) {
            return redirect()->route('rapid-consulting.index');
        }

        $allQuestions = $this->getFrameworkQuestions($type);
        $frameworkDef = $this->getFrameworkDefinitions($type);
        $answers = [];
        $confidence = [];

        foreach ($allQuestions as $pillarCode => $pillar) {
            foreach ($pillar['questions'] as $qCode => $qData) {
                $score = $request->input($qCode) ?? $request->input(str_replace('.', '_', $qCode));
                $conf = $request->input('conf_' . $qCode) ?? $request->input('conf_' . str_replace('.', '_', $qCode));

                if ($score === null) {
                    return back()->withErrors("Please answer all questions. Missing: {$qCode}")->withInput();
                }
                $answers[$qCode] = $score;
                $confidence[$qCode] = $conf ?? 'medium';

                $noteKey = 'note_' . $qCode;
                $note = $request->input($noteKey) ?? $request->input(str_replace('.', '_', $noteKey));
                if ($note) {
                    $answers[$noteKey] = $note;
                }
            }
        }

        Session::put('rc_answers', $answers);

        // Calculate scores temporarily for session
        $pillarScores = [];
        $weightedScoreSum = 0;
        $weightSum = 0;

        foreach ($allQuestions as $pillarCode => $pillar) {
            $pillarSum = 0;
            $pillarCount = 0;
            foreach ($pillar['questions'] as $qCode => $qData) {
                $score = (int)$answers[$qCode];
                $pillarSum += $score;
                $pillarCount++;
            }
            $avgPillar = round($pillarSum / $pillarCount, 2);
            $weight = $frameworkDef[$pillarCode]['weight'] ?? 1.0;
            $weightedPillarScore = round($avgPillar * $weight, 2);

            $pillarScores[$pillarCode] = [
                'name' => $pillar['name'],
                'score' => $avgPillar,
                'rag' => $this->rag($avgPillar),
                'is_critical' => $frameworkDef[$pillarCode]['critical'] ?? false
            ];

            $weightedScoreSum += $weightedPillarScore;
            $weightSum += $weight;
        }

        $overallScore = $weightSum > 0 ? round($weightedScoreSum / $weightSum, 2) : 0;
        $ragStatus = $this->rag($overallScore);

        $indexScores = $this->calculateIndices($type, $pillarScores, $answers);

        $results = [
            'overall_score' => $overallScore,
            'rag_status' => $ragStatus,
            'pillar_scores' => $pillarScores,
            'index_scores' => $indexScores,
            'type' => $type,
            'recommendation' => $this->generateRecommendation($overallScore, $pillarScores, $type)
        ];
        
        Session::put('rc_results', $results);

        return redirect()->route('rapid-consulting.personal-form');
    }

    private function calculateIndices(string $type, array $pillarScores, array $answers)
    {
        $indexScores = [];
        if (strtolower($type) === 'pir') {
            $p3 = $pillarScores['P3']['score'] ?? 0;
            $p4 = $pillarScores['P4']['score'] ?? 0;
            $p5 = $pillarScores['P5']['score'] ?? 0;
            $p7 = $pillarScores['P7']['score'] ?? 0;
            $p9 = $pillarScores['P9']['score'] ?? 0;
            $p10 = $pillarScores['P10']['score'] ?? 0;

            $bri = round(($p3 * 1.3 + $p4 * 1.2 + $p5 * 1.2 + $p7 * 1.2) / 4.9, 2);
            $dmi = round(($p9 * 1.0 + $p10 * 1.1) / 2.1, 2);

            $complianceQuestions = ['P1.F8', 'P3.F11', 'P5.F8', 'P5.F9', 'P8.F8', 'P8.F9'];
            $cScores = [];
            foreach ($complianceQuestions as $qId) {
                if (isset($answers[$qId])) {
                    $cScores[] = (int)$answers[$qId];
                }
            }
            $chi = count($cScores) > 0 ? round(array_sum($cScores) / count($cScores), 2) : 0.0;

            $indexScores = [
                'BRI' => $bri,
                'VRI' => $p3,
                'DMI' => $dmi,
                'CHI' => $chi
            ];
        } else {
            $d1 = $pillarScores['D1']['score'] ?? 0;
            $d2 = $pillarScores['D2']['score'] ?? 0;
            $d4 = $pillarScores['D4']['score'] ?? 0;
            $d6 = $pillarScores['D6']['score'] ?? 0;
            $d7 = $pillarScores['D7']['score'] ?? 0;
            $d8 = $pillarScores['D8']['score'] ?? 0;
            $d10 = $pillarScores['D10']['score'] ?? 0;
            $d11 = $pillarScores['D11']['score'] ?? 0;
            $d12 = $pillarScores['D12']['score'] ?? 0;

            $indexScores = [
                'SSI' => round(($d1 + $d2) / 2, 2),
                'SMI' => round(($d4 * 1.2 + $d6 * 1.2 + $d11 * 0.9) / 3.3, 2),
                'SIMI' => round(($d4 * 1.2 + $d11 * 0.9 + $d12 * 1.1) / 3.2, 2),
                'BAURI' => round(($d7 + $d8) / 2, 2),
                'CHI' => $d10
            ];
        }
        return $indexScores;
    }

    public function personalForm()
    {
        if (!Session::has('rc_answers')) {
            return redirect()->route('rapid-consulting.index');
        }
        return view('rapid-consulting.personal-form');
    }

    public function processPersonalForm(Request $request)
    {
        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
            'job_title' => 'required|string|max:255',
            'company' => 'required|string|max:255',
            'industry' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'phone' => 'nullable|string|max:20',
        ]);

        $type = Session::get('rc_type');
        $answers = Session::get('rc_answers');
        $results = Session::get('rc_results');

        if (!$type || !$answers || !$results) {
            return redirect()->route('rapid-consulting.index');
        }

        $results['user'] = $validatedData;
        Session::put('rc_user', $validatedData);
        Session::put('rc_results', $results);

        // Finalize calculation and save
        $leadPriority = 'Normal';
        $lowCriticalPillars = false;
        foreach ($results['pillar_scores'] as $code => $data) {
            if ($data['is_critical'] && $data['score'] < 3.0) {
                $lowCriticalPillars = true;
                break;
            }
        }
        if ($results['overall_score'] < 3.0 || $lowCriticalPillars) {
            $leadPriority = 'High';
        }

        $test = Http::withoutVerifying()->post('https://n8n.srv1139767.hstgr.cloud/webhook-test/91523c95-9254-40e5-847d-047ae99956bd',[
          'type' => strtoupper($type),
          'results' => $results,
          'answers' => $answers,
          'submitted_at' => now()->toDateTimeString(),
        ]);

        $lead = \App\Models\Lead::create([
            'name' => $validatedData['name'],
            'company' => $validatedData['company'],
            'email' => $validatedData['email'],
            'phone' => $validatedData['phone'] ?? null,
            'role_title' => $validatedData['job_title'],
            'industry' => $validatedData['industry'] ?? 'Other',
            'type' => strtoupper($type),
            'source' => 'Assessment',
            'overall_score' => $results['overall_score'],
            'rag_status' => $results['rag_status'],
            'priority' => $leadPriority,
            'lead_status' => 'Warm',
            'index_scores_json' => $results['index_scores'],
            'answers_json' => $answers,
            'converted_to_client' => false,
            'ai_recommendation' => $test->json()['output'] ?? null,
        ]);

        \App\Jobs\SendToN8nWebhook::dispatch([
            'lead_id' => $lead->id,
            'type' => strtoupper($type),
            'results' => $results,
            'answers' => $answers,
            'submitted_at' => now()->toDateTimeString(),
        ]);

        return redirect()->route('rapid-consulting.results');
    }

    public function results()
    {
        return redirect()->route('rapid-consulting.dashboard');
    }

    public function dashboard()
    {
        $results = Session::get('rc_results');
        
        // Sync with DB to get latest AI recommendation if user is authenticated
        if (auth()->check()) {
            $latestLead = \App\Models\Lead::where('email', auth()->user()->email)
                ->where('lead_status', 'Warm')
                ->orderBy('created_at', 'desc')
                ->first();

            if ($latestLead) {
                if (!$results) {
                    // Re-hydrate session if lost
                    $this->loadExistingToSession($latestLead, [
                        'name' => auth()->user()->name,
                        'email' => auth()->user()->email,
                        'company' => $latestLead->company,
                        'job_title' => $latestLead->role_title ?? 'User',
                        'industry' => $latestLead->industry ?? 'Other',
                    ]);
                    $results = Session::get('rc_results');
                }
                
                // Add AI recommendation to results for the view
                if ($results) {
                    $results['ai_recommendation'] = $latestLead->ai_recommendation;
                    Session::put('rc_results', $results);
                }
            }
        }

        if (!$results) {
            return redirect()->route('rapid-consulting.index');
        }

        return view('rapid-consulting.dashboard', compact('results'));
    }

    public function download()
    {
        $results = Session::get('rc_results');
        if (!$results) {
            return redirect()->route('rapid-consulting.index');
        }

        return view('rapid-consulting.pdf-report', compact('results'));
    }

    public function reportStatus()
    {
        $results = Session::get('rc_results');
        if (!$results) {
            return response()->json(['status' => 'not_found'], 404);
        }

        // Dummy status check, in a real app this would check DB/Redis for report state
        return response()->json([
            'status' => 'ready',
            'report_id' => 'REP-' . strtoupper(substr(md5(Session::getId()), 0, 8))
        ]);
    }

    private function rag(float $score): string {
        if ($score < 2.5) return 'Red';
        if ($score < 3.8) return 'Amber';
        return 'Green';
    }

    private function generateRecommendation(float $score, array $pillarScores, string $type): string {
        $rag = $this->rag($score);
        $recommendation = "";

        if ($rag === 'Red') {
            $weakPillars = [];
            foreach ($pillarScores as $pillar) {
                if ($pillar['score'] < 2.5) $weakPillars[] = $pillar['name'];
            }
            $recommendation = "Your overall score of {$score} indicates critical risk. Immediate intervention is recommended across your weakest areas: " . implode(', ', $weakPillars) . ". We strongly recommend booking an executive review session to define a recovery plan before proceeding.";
        } else if ($rag === 'Amber') {
            $weakPillars = [];
            foreach ($pillarScores as $pillar) {
                if ($pillar['score'] < 3.0) $weakPillars[] = $pillar['name'];
            }
            $recommendation = "Your overall score of {$score} shows partial control but exposed risk in: " . implode(', ', $weakPillars) . ". We recommend a focused consultant review to address these gaps and prevent escalation.";
        } else {
            $strongPillars = [];
            foreach ($pillarScores as $pillar) {
                if ($pillar['score'] >= 4.0) $strongPillars[] = $pillar['name'];
            }
            $recommendation = "Your overall score of {$score} reflects broadly strong control. To maintain this and identify any hidden risks we recommend a periodic Programme & Service Intelligence (PIR / SIR) review. Your strongest areas are: " . implode(', ', $strongPillars) . ".";
        }

        // Add criticality note
        $criticalRisks = [];
        foreach ($pillarScores as $pillar) {
            if ($pillar['is_critical'] && $pillar['score'] < 3.0) {
                 $criticalRisks[] = $pillar['name'];
            }
        }

        if (!empty($criticalRisks)) {
            $recommendation .= " The following critical areas are at risk and should be prioritised: " . implode(', ', $criticalRisks) . ". Failure in these domains significantly impacts overall programme health.";
        } else {
            $recommendation .= " Your critical control domains are broadly stable. Focus on continuous improvement and addressing the specific gaps identified above.";
        }

        return $recommendation;
    }

}
