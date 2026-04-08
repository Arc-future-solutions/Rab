<?php

namespace App\Http\Controllers;

use App\Models\Lead;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Carbon\Carbon;

class RapidConsultingController extends Controller
{
    private $phiQuestions = [
        'P1' => [
            'name' => 'Governance & Control',
            'questions' => [
                'P1_Q1' => 'Decision-making and escalation are clear and effective.',
                'P1_Q2' => 'Programme reporting reflects the true status.'
            ]
        ],
        'P2' => [
            'name' => 'Planning & Delivery',
            'questions' => [
                'P2_Q1' => 'The delivery plan is realistic and credible.',
                'P2_Q2' => 'Risks and dependencies are actively managed.'
            ]
        ],
        'P3' => [
            'name' => 'Business Alignment',
            'questions' => [
                'P3_Q1' => 'Business objectives and KPIs are clearly defined.',
                'P3_Q2' => 'The business is actively engaged and accountable.'
            ]
        ],
        'P4' => [
            'name' => 'Change & Adoption',
            'questions' => [
                'P4_Q1' => 'Users are prepared and involved in the programme.',
                'P4_Q2' => 'Training and adoption readiness are on track.'
            ]
        ],
        'P5' => [
            'name' => 'Data Readiness',
            'questions' => [
                'P5_Q1' => 'Data quality and migration risks are understood.',
                'P5_Q2' => 'The business is validating data readiness.'
            ]
        ],
        'P6' => [
            'name' => 'Solution & Process Fit',
            'questions' => [
                'P6_Q1' => 'The solution supports real business processes.',
                'P6_Q2' => 'Key design decisions are validated by the business.'
            ]
        ],
        'P7' => [
            'name' => 'Cutover & Go-Live',
            'questions' => [
                'P7_Q1' => 'Go-live planning is clear and realistic.',
                'P7_Q2' => 'Business and operational readiness for Day 1 is understood.'
            ]
        ],
        'P8' => [
            'name' => 'Delivery Capability',
            'questions' => [
                'P8_Q1' => 'The programme team has the required capability and capacity.',
                'P8_Q2' => 'Vendors and partners are effectively managed.'
            ]
        ],
        'P9' => [
            'name' => 'Operational Readiness',
            'questions' => [
                'P9_Q1' => 'The support model for go-live and BAU is defined.',
                'P9_Q2' => 'The organisation is prepared to operate the solution.'
            ]
        ]
    ];

    private $itsmQuestions = [
        'P1' => [
            'name' => 'Service Governance & Ownership',
            'questions' => [
                'P1_Q1' => 'Service ownership and accountability are clearly defined.',
                'P1_Q2' => 'Service governance and performance review are active and effective.'
            ]
        ],
        'P2' => [
            'name' => 'Incident & Major Incident Management',
            'questions' => [
                'P2_Q1' => 'Incidents are resolved in a controlled and timely manner.',
                'P2_Q2' => 'Major incidents are handled with clear escalation and communication.'
            ]
        ],
        'P3' => [
            'name' => 'Service Request Management',
            'questions' => [
                'P3_Q1' => 'Service requests are standardised and clearly separated from incidents.',
                'P3_Q2' => 'Requests are fulfilled efficiently and within expected timescales.'
            ]
        ],
        'P4' => [
            'name' => 'Problem Management',
            'questions' => [
                'P4_Q1' => 'Recurring issues are investigated and root causes addressed.',
                'P4_Q2' => 'Problem management reduces repeat incidents over time.'
            ]
        ],
        'P5' => [
            'name' => 'Change & Release Management',
            'questions' => [
                'P5_Q1' => 'Changes are controlled and do not regularly create instability.',
                'P5_Q2' => 'Releases are planned, tested, and deployed in a structured way.'
            ]
        ],
        'P6' => [
            'name' => 'Service Performance, SLA & Reporting',
            'questions' => [
                'P6_Q1' => 'SLAs and KPIs reflect real service performance.',
                'P6_Q2' => 'Reporting is trusted and used to drive improvement.'
            ]
        ],
        'P7' => [
            'name' => 'Service Transition & BAU Readiness',
            'questions' => [
                'P7_Q1' => 'New or changed services are handed over to BAU in a structured way.',
                'P7_Q2' => 'Documentation, training, and knowledge transfer support live operations.'
            ]
        ],
        'P8' => [
            'name' => 'Service Operations & Support Model',
            'questions' => [
                'P8_Q1' => 'The support model is clear and effective in practice.',
                'P8_Q2' => 'Ticket ownership, escalation, and backlog control are working.'
            ]
        ],
        'P9' => [
            'name' => 'Supplier & Vendor Service Management',
            'questions' => [
                'P9_Q1' => 'Vendors are accountable for service performance.',
                'P9_Q2' => 'Internal and external teams work in a coordinated way.'
            ]
        ],
        'P10' => [
            'name' => 'Operational Resilience & Continuity',
            'questions' => [
                'P10_Q1' => 'Disaster recovery, backup, and continuity controls are reliable.',
                'P10_Q2' => 'Critical service failure scenarios are understood and planned for.'
            ]
        ],
        'P11' => [
            'name' => 'Automation, Tooling & Service Optimisation',
            'questions' => [
                'P11_Q1' => 'Service processes are supported by effective tools and automation.',
                'P11_Q2' => 'Self-service, knowledge management, or workflow optimisation is in place.'
            ]
        ]
    ];

    public function index()
    {
        // 1. Check if logged in (Auth)
        if (auth()->check()) {
            $user = auth()->user();
            $existingLead = Lead::where('email', $user->email)
                ->where('lead_status', 'Warm')
                ->orderBy('created_at', 'desc')
                ->first();

            if ($existingLead) {
                return $this->loadExistingToSession($existingLead, [
                    'name' => $user->name,
                    'email' => $user->email,
                    'company' => $existingLead->company,
                    'job_title' => $existingLead->role_title ?? 'User',
                    'industry' => $existingLead->industry ?? 'Other',
                ]);
            }
        }

        // 2. If we already have results in session from this browser
        if (Session::has('rc_results')) {
            return view('rapid-consulting.index', ['has_previous' => true]);
        }

        return view('rapid-consulting.index');
    }

    private function loadExistingToSession($existingLead, $userData)
    {
        $results = [
            'overall_score' => $existingLead->overall_score,
            'rag_status' => $existingLead->rag_status,
            'priority' => $existingLead->priority,
            'pillar_scores' => [], 
            'index_scores' => $existingLead->index_scores_json,
            'type' => strtolower($existingLead->type),
            'user' => $userData,
            'recommendation' => $this->generateRecommendation($existingLead->overall_score, [], strtolower($existingLead->type))
        ];
        
        Session::put('rc_results', $results);
        Session::put('rc_user', $userData);
        
        return redirect()->route('rapid-consulting.dashboard');
    }

    public function start(Request $request)
    {
        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
            'job_title' => 'required|string|max:255',
            'company' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'phone' => 'nullable|string|max:20',
            'industry' => 'required|string',
            'free_text_concern' => 'required|string',
            'confidence_level' => 'nullable|string'
        ]);

        // Check if a result already exists for this Email + Company
        $existingLead = Lead::where('email', $validatedData['email'])
            ->where('company', $validatedData['company'])
            ->where('lead_status', 'Warm')
            ->first();

        if ($existingLead) {
            return $this->loadExistingToSession($existingLead, $validatedData);
        }

        Session::put('rc_user', $validatedData);
        Session::forget(['rc_type', 'rc_answers', 'rc_results']);

        return redirect()->route('rapid-consulting.select-type');
    }

    public function selectType()
    {
        if (!Session::has('rc_user')) {
            return redirect()->route('rapid-consulting.index');
        }
        return view('rapid-consulting.select-type');
    }

    public function storeType(Request $request)
    {
        $request->validate([
            'type' => 'required|in:phi,itsm'
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

        $questions = ($type === 'phi') ? $this->phiQuestions : $this->itsmQuestions;

        return view('rapid-consulting.assessment', compact('questions', 'type'));
    }

    public function submit(Request $request)
    {
        $type = Session::get('rc_type');
        $user = Session::get('rc_user');

        if (!$type || !$user) {
            return redirect()->route('rapid-consulting.index');
        }

        $allQuestions = ($type === 'phi') ? $this->phiQuestions : $this->itsmQuestions;
        $answers = $request->except('_token');

        // Validate all questions answered
        foreach ($allQuestions as $pillarCode => $pillar) {
            foreach ($pillar['questions'] as $qCode => $text) {
                if (!isset($answers[$qCode])) {
                    return back()->withErrors('Please answer all questions.')->withInput();
                }
            }
        }

        Session::put('rc_answers', $answers);

        // Score calculation
        $pillarScores = [];
        $totalSum = 0;
        $totalCount = 0;

        foreach ($allQuestions as $pillarCode => $pillar) {
            $pillarSum = 0;
            $pillarCount = 0;
            foreach ($pillar['questions'] as $qCode => $text) {
                $score = (int)$answers[$qCode];
                $pillarSum += $score;
                $pillarCount++;
                $totalSum += $score;
                $totalCount++;
            }
            $avgPillar = $pillarSum / $pillarCount;
            $pillarScores[$pillarCode] = [
                'name' => $pillar['name'],
                'score' => round($avgPillar, 2),
                'rag' => $this->rag($avgPillar)
            ];
        }

        $overallScore = round($totalSum / $totalCount, 2);
        $ragStatus = $this->rag($overallScore);
        $leadPriority = 'Normal';

        $indexScores = [];
        if ($type === 'phi') {
            $leadPriority = ($overallScore < 3.0 || $pillarScores['P1']['score'] < 3.0 || $pillarScores['P7']['score'] < 3.0) ? 'High' : 'Normal';
            $indexScores = $pillarScores;
        } else {
            // ITSM calculation
            $ssi = ( ($answers['P2_Q1'] ?? 0) + ($answers['P2_Q2'] ?? 0) + ($answers['P4_Q1'] ?? 0) + ($answers['P4_Q2'] ?? 0) + ($answers['P5_Q1'] ?? 0) + ($answers['P5_Q2'] ?? 0) ) / 6;
            $smi = ( ($answers['P1_Q1'] ?? 0) + ($answers['P1_Q2'] ?? 0) + ($answers['P6_Q1'] ?? 0) + ($answers['P6_Q2'] ?? 0) + ($answers['P11_Q1'] ?? 0) + ($answers['P11_Q2'] ?? 0) ) / 6;
            $bau = ( ($answers['P7_Q1'] ?? 0) + ($answers['P7_Q2'] ?? 0) + ($answers['P8_Q1'] ?? 0) + ($answers['P8_Q2'] ?? 0) ) / 4;

            $indexScores = [
                'SSI' => round($ssi, 2),
                'SMI' => round($smi, 2),
                'BAU Readiness' => round($bau, 2)
            ];
            
            $p2Avg = ($answers['P2_Q1'] + $answers['P2_Q2']) / 2;
            $p5Avg = ($answers['P5_Q1'] + $answers['P5_Q2']) / 2;
            $p7Avg = ($answers['P7_Q1'] + $answers['P7_Q2']) / 2;

            if ($overallScore < 3.0 || $p2Avg < 3.0 || $p5Avg < 3.0 || $p7Avg < 3.0) {
                $leadPriority = 'High';
            }
        }

        $results = [
            'overall_score' => $overallScore,
            'rag_status' => $ragStatus,
            'priority' => $leadPriority,
            'pillar_scores' => $pillarScores,
            'index_scores' => $indexScores,
            'type' => $type,
            'user' => $user,
            'recommendation' => $this->generateRecommendation($overallScore, $pillarScores, $type)
        ];

        Session::put('rc_results', $results);

        // Save lead
        Lead::create([
            'name' => $user['name'],
            'company' => $user['company'],
            'email' => $user['email'],
            'phone' => $user['phone'],
            'role_title' => $user['job_title'],
            'industry' => $user['industry'],
            'free_text_concern' => $user['free_text_concern'],
            'confidence_level' => $user['confidence_level'],
            'type' => strtoupper($type),
            'overall_score' => $overallScore,
            'rag_status' => $ragStatus,
            'priority' => $leadPriority,
            'lead_status' => 'Warm', // Mark as Warm Lead
            'index_scores_json' => $indexScores,
            'answers_json' => $answers,
            'converted_to_client' => false,
        ]);

        return redirect()->route('rapid-consulting.results');
    }

    public function results()
    {
        $results = Session::get('rc_results');
        if (!$results) {
            return redirect()->route('rapid-consulting.index');
        }
        return view('rapid-consulting.results', compact('results'));
    }

    public function dashboard()
    {
        $results = Session::get('rc_results');
        if (!$results) {
            return redirect()->route('rapid-consulting.index');
        }
        return view('rapid-consulting.dashboard', compact('results'));
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
            $recommendation = "Your overall score of {$score} reflects broadly strong control. To maintain this and identify any hidden risks we recommend a periodic assurance review. Your strongest areas are: " . implode(', ', $strongPillars) . ".";
        }

        if ($type === 'phi') {
            if ($rag === 'Red' || $rag === 'Amber') {
                $recommendation .= " Governance and cutover readiness are the highest-risk areas for programme failure — prioritise these first.";
            } else {
                $recommendation .= " Your programme delivery posture is strong. Ensure continued governance rigour as you approach go-live.";
            }
        } else {
            if ($rag === 'Red' || $rag === 'Amber') {
                $recommendation .= " Incident control, change stability, and BAU transition are the most common sources of service degradation — address these as a priority.";
            } else {
                $recommendation .= " Your service operations are well controlled. Focus on continuous improvement and automation to optimise further.";
            }
        }

        return $recommendation;
    }
}
