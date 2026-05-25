<?php

namespace App\Http\Controllers;

use App\Mail\HighPriorityDiagnosticAlert;
use App\Models\Lead;
use App\Services\ReportService;
use App\Services\DiagnosticOutcomeService;
use App\Services\InternalCrmService;
use App\Services\SnapshotAiPayloadBuilder;
use Illuminate\Support\Arr;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Spatie\Browsershot\Browsershot;
use setasign\Fpdi\Fpdi;
use Carbon\Carbon;
use App\Traits\HasAssessmentQuestions;
use App\Services\AssessmentIndexCalculator;
use App\Services\SnapshotReportService;

class RapidConsultingController extends Controller
{
    use HasAssessmentQuestions;

    public function __construct(
        private SnapshotReportService $snapshotReportService,
        private SnapshotAiPayloadBuilder $snapshotAiPayloadBuilder
    )
    {
    }

    private function getFrameworkDefinitions(string $type)
    {
        $type = strtolower($type);
        $framework = \App\Models\AssessmentFramework::where('code', strtoupper($type))->first();
        if (!$framework) {
            return $this->getFallbackFrameworkDefinitions($type);
        }

        return \App\Models\AssessmentPillar::where('framework_id', $framework->id)
            ->get()
            ->mapWithKeys(fn ($pillar) => [
                $pillar->code => [
                    'name' => $pillar->name,
                    'weight' => (float) $pillar->weight,
                    'critical' => (bool) $pillar->is_critical,
                ],
            ])
            ->toArray();
    }

    private function getFrameworkQuestions(string $type)
    {
        $type = strtolower($type);
        $framework = \App\Models\AssessmentFramework::where('code', strtoupper($type))->first();
        if (!$framework) {
            return $this->getFallbackFrameworkQuestions($type);
        }

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
        $hasQuestions = false;
        foreach ($pillars as $pillar) {
            $qs = [];
            foreach ($pillar->questions as $q) {
                $hasQuestions = true;
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
        return $hasQuestions ? $data : $this->getFallbackFrameworkQuestions($type);
    }

    private function getFallbackFrameworkDefinitions(string $type): array
    {
        return match ($type) {
            'pir' => $this->phiPillars,
            'sir' => $this->itsmDomains,
            default => [],
        };
    }

    private function getFallbackFrameworkQuestions(string $type): array
    {
        $type = strtolower($type);
        $jsonKey = strtoupper($type) . '_DIAGNOSTIC';
        $jsonPath = base_path('questions.json');

        if (File::exists($jsonPath)) {
            $decoded = json_decode(File::get($jsonPath), true);
            $questions = $decoded[$jsonKey] ?? [];

            if (!empty($questions)) {
                return $this->formatJsonQuestions($questions);
            }
        }

        return $this->formatLegacyQuestions(
            $type === 'sir' ? $this->itsmDomains : $this->phiPillars,
            $type === 'sir' ? $this->itsmSnapshotQuestions : $this->phiSnapshotQuestions
        );
    }

    private function formatJsonQuestions(array $questions): array
    {
        $data = [];

        foreach ($questions as $question) {
            $pillarCode = $question['pillar_code'] ?? null;
            if (!$pillarCode) {
                continue;
            }

            $anchors = $question['score_anchors'] ?? [];
            if (is_string($anchors)) {
                $anchors = json_decode($anchors, true) ?? [];
            }

            $cards = null;
            if (($question['question_type'] ?? null) == 3) {
                $cards = $this->scoreCardsFromOptions($question['select_options'] ?? null, $anchors);
            }

            $data[$pillarCode] ??= [
                'name' => $question['pillar_name'] ?? $pillarCode,
                'questions' => [],
            ];

            $data[$pillarCode]['questions'][$question['id']] = [
                'text' => $question['question_text'] ?? '',
                'type' => $question['question_type'] ?? null,
                'label' => $question['question_type_label'] ?? 'Question',
                'anchors' => $anchors,
                'type3_cards' => $cards,
            ];
        }

        return $data;
    }

    private function scoreCardsFromOptions($options, array $anchors): ?array
    {
        if (is_string($options)) {
            $options = json_decode($options, true) ?? [];
        }

        if (empty($options) && !empty($anchors)) {
            return collect($anchors)
                ->map(fn ($response, $score) => ['score' => (int) $score, 'response' => $response])
                ->values()
                ->toArray();
        }

        if (empty($options)) {
            return null;
        }

        return collect($options)
            ->values()
            ->map(fn ($response, $index) => ['score' => $index + 1, 'response' => $response])
            ->toArray();
    }

    private function formatLegacyQuestions(array $definitions, array $questions): array
    {
        $data = [];

        foreach ($questions as $pillarCode => $pillarQuestions) {
            $data[$pillarCode] = [
                'name' => $definitions[$pillarCode]['name'] ?? $pillarCode,
                'questions' => [],
            ];

            foreach ($pillarQuestions as $questionCode => $questionText) {
                $data[$pillarCode]['questions'][$questionCode] = [
                    'text' => $questionText,
                    'type' => null,
                    'label' => 'Question',
                    'anchors' => [],
                    'type3_cards' => null,
                ];
            }
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
            'lead_id' => $existingLead->id,
            'booking_token' => $existingLead->booking_token,
            'overall_score' => $overallScore,
            'rag_status' => $this->rag($overallScore),
            'pillar_scores' => $pillarScores, 
            'index_scores' => $indexScores,
            'type' => $type,
            'user' => $userData,
            'recommendation' => $this->generateRecommendation($overallScore, $pillarScores, $type)
        ];

        $results['snapshot_report'] = $this->snapshotReportService->build(
            $results,
            $existingLead->ai_recommendation,
            $existingLead->snapshot_report_json
        );
        
        Session::put('rc_results', $results);
        Session::put('rc_user', $userData);
        Session::put('rc_type', $type);
        Session::put('rc_answers', $answers);
    }


    public function start(Request $request)
    {
        // If type is pre-selected, go to the required context step before questions.
        if ($request->has('type') && in_array(strtolower($request->type), ['pir', 'sir'])) {
            Session::put('rc_type', strtolower($request->type));
            Session::forget(['rc_answers', 'rc_confidence', 'rc_results', 'rc_delivery_stage', 'rc_service_context', 'rc_regulatory_context']);
            return redirect()->route('rapid-consulting.context');
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
        Session::forget(['rc_answers', 'rc_confidence', 'rc_results', 'rc_delivery_stage', 'rc_service_context', 'rc_regulatory_context']);

        return redirect()->route('rapid-consulting.context');
    }

    public function context(Request $request)
    {
        if ($request->has('type') && in_array(strtolower($request->type), ['pir', 'sir'])) {
            Session::put('rc_type', strtolower($request->type));
            Session::forget(['rc_answers', 'rc_confidence', 'rc_results', 'rc_delivery_stage', 'rc_service_context', 'rc_regulatory_context']);
        }

        $type = Session::get('rc_type');
        if (!$type) {
            return redirect()->route('rapid-consulting.select-type');
        }

        return view('rapid-consulting.context', compact('type'));
    }

    public function storeContext(Request $request)
    {
        $type = Session::get('rc_type');
        if (!$type) {
            return redirect()->route('rapid-consulting.select-type');
        }

        if ($type === 'pir') {
            $validated = $request->validate([
                'delivery_stage' => 'required|in:Mobilisation,Design,Build,Test,Cutover,PostGoLive',
                'regulatory_context' => 'nullable|in:fca_uk,dora_eu,nhs_cqc,public_sector,gdpr_only',
            ]);

            Session::put('rc_delivery_stage', $validated['delivery_stage']);
        } else {
            $validated = $request->validate([
                'service_context' => 'required|in:NSI,Established,UnderPressure,Transformation,LegacyPreRetirement',
                'regulatory_context' => 'nullable|in:fca_uk,dora_eu,nhs_cqc,public_sector,gdpr_only',
            ]);

            Session::put('rc_service_context', $validated['service_context']);
        }

        Session::put('rc_regulatory_context', $validated['regulatory_context'] ?? null);

        return redirect()->route('rapid-consulting.assessment');
    }

    public function assessment(Request $request)
    {
        if ($request->has('type') && in_array(strtolower($request->type), ['pir', 'sir'])) {
            Session::put('rc_type', strtolower($request->type));
            Session::forget(['rc_answers', 'rc_results', 'rc_delivery_stage', 'rc_service_context', 'rc_regulatory_context']);
        }

        $type = Session::get('rc_type');
        if (!$type) {
            return redirect()->route('rapid-consulting.select-type');
        }

        if (
            ($type === 'pir' && !Session::has('rc_delivery_stage')) ||
            ($type === 'sir' && !Session::has('rc_service_context'))
        ) {
            return redirect()->route('rapid-consulting.context');
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

                if (filter_var($score, FILTER_VALIDATE_INT) === false || (int) $score < 1 || (int) $score > 5) {
                    return back()->withErrors("Invalid score for {$qCode}. Scores must be integers between 1 and 5.")->withInput();
                }

                if ($conf !== null && !in_array(strtolower((string) $conf), ['high', 'medium', 'low'], true)) {
                    return back()->withErrors("Invalid confidence level for {$qCode}.")->withInput();
                }

                $answers[$qCode] = $score;
                $confidence[$qCode] = strtolower((string) ($conf ?? 'medium'));

                $noteKey = 'note_' . $qCode;
                $note = $request->input($noteKey) ?? $request->input(str_replace('.', '_', $noteKey));
                if ($note) {
                    $answers[$noteKey] = $note;
                }
            }
        }

        Session::put('rc_answers', $answers);
        Session::put('rc_confidence', $confidence);

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
            'delivery_stage' => Session::get('rc_delivery_stage'),
            'service_context' => Session::get('rc_service_context'),
            'regulatory_context' => Session::get('rc_regulatory_context'),
            'recommendation' => $this->generateRecommendation($overallScore, $pillarScores, $type)
        ];

        $results['snapshot_report'] = $this->snapshotReportService->build($results);
        
        Session::put('rc_results', $results);

        return redirect()->route('rapid-consulting.personal-form');
    }

    private function calculateIndices(string $type, array $pillarScores, array $answers)
    {
        if (strtolower($type) === 'pir') {
            $complianceQuestions = ['P1.F8', 'P3.F11', 'P5.F8', 'P5.F9', 'P8.F8', 'P8.F9'];
            $cScores = [];
            foreach ($complianceQuestions as $qId) {
                if (isset($answers[$qId])) {
                    $cScores[] = (int)$answers[$qId];
                }
            }
            $chi = count($cScores) > 0 ? round(array_sum($cScores) / count($cScores), 2) : 0.0;

            return [
                ...AssessmentIndexCalculator::calculatePir($pillarScores, $answers),
                'CHI' => $chi,
            ];
        }

        return [
            ...AssessmentIndexCalculator::calculateSir($pillarScores),
            'CHI' => $pillarScores['D10']['score'] ?? 0,
        ];
    }

    public function personalForm()
    {
        if (!Session::has('rc_answers')) {
            return redirect()->route('rapid-consulting.index');
        }
        return view('rapid-consulting.personal-form');
    }

    public function processPersonalForm(
        Request $request,
        DiagnosticOutcomeService $outcomeService,
        InternalCrmService $internalCrm,
        ReportService $reportService
    )
    {
        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
            'job_title' => 'required|string|max:255',
            'company' => 'required|string|max:255',
            'industry' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'phone' => 'nullable|string|max:20',
            'consent_given' => 'accepted',
        ]);

        $type = Session::get('rc_type');
        $answers = Session::get('rc_answers');
        $confidence = Session::get('rc_confidence', []);
        $results = Session::get('rc_results');

        if (!$type || !$answers || !$results) {
            return redirect()->route('rapid-consulting.index');
        }

        $results['user'] = Arr::only($validatedData, [
            'name',
            'job_title',
            'company',
            'industry',
            'email',
            'phone',
        ]);
        Session::put('rc_user', $validatedData);
        Session::put('rc_results', $results);

        $leadPriority = $outcomeService->leadPriority(
            (float) $results['overall_score'],
            $results['pillar_scores'] ?? []
        );
        $consentTimestamp = now();

        $context = [
            'framework' => strtoupper($type),
            'delivery_stage' => Session::get('rc_delivery_stage'),
            'service_context' => Session::get('rc_service_context'),
            'regulatory_context' => Session::get('rc_regulatory_context'),
        ];

        $lead = $internalCrm->createSnapshotLead(
            $validatedData,
            $type,
            $results,
            $answers,
            $confidence,
            $context,
            $consentTimestamp,
            null
        );

        $promptKey = $this->snapshotAiPayloadBuilder->promptKey($context['framework']);

        try {
            Log::info('Generating snapshot report synchronously at assessment completion', [
                'lead_id' => $lead->id,
                'prompt_key' => $promptKey,
            ]);

            $aiPayload = $this->snapshotAiPayloadBuilder->buildFromLead($lead);
            $structuredReport = $reportService->generate($promptKey, $aiPayload);

            $lead->update([
                'ai_recommendation' => json_encode($structuredReport, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                'snapshot_report_json' => $structuredReport,
            ]);
        } catch (\Throwable $e) {
            Log::error('Snapshot AI generation failed at assessment completion', [
                'lead_id' => $lead->id,
                'prompt_key' => $promptKey,
                'message' => $e->getMessage(),
            ]);
        }

        $lead->refresh();

        $results['lead_id'] = $lead->id;
        $results['booking_token'] = $lead->booking_token;
        $results['snapshot_report'] = $this->snapshotReportService->build(
            $results,
            $lead->ai_recommendation,
            $lead->snapshot_report_json
        );
        Session::put('rc_results', $results);

        if ($leadPriority === 'High') {
            Mail::to(config('services.crm.high_priority_email'))
                ->send(new HighPriorityDiagnosticAlert(
                    $validatedData,
                    $results,
                    $context,
                    $outcomeService->topThreeInsightAreas($results['pillar_scores'] ?? [])
                ));
        }

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
                    $results['lead_id'] = $latestLead->id;
                    $results['booking_token'] = $latestLead->booking_token;
                    $results['ai_recommendation'] = $latestLead->ai_recommendation;
                    $results['snapshot_report'] = $this->snapshotReportService->build(
                        $results,
                        $latestLead->ai_recommendation,
                        $latestLead->snapshot_report_json
                    );
                    Session::put('rc_results', $results);
                }
            }
        }

        if (!$results) {
            return redirect()->route('rapid-consulting.index');
        }

        $results['snapshot_report'] = $this->snapshotReportService->build(
            $results,
            $results['ai_recommendation'] ?? null,
            $results['snapshot_report_json'] ?? null
        );

        return view('rapid-consulting.dashboard', compact('results'));
    }

    public function downloadSnapshotPdf(Request $request, Lead $lead)
    {
        $this->authorizeSnapshotPdfDownload($request, $lead);

        $results = $this->snapshotResultsFromLead($lead);
        $pdfMode = true;
        $hide_nav = true;
        $logoDataUri = $this->logoDataUri();
        $coverPart = 'cover';
        $bodyPart = 'body';
        $coverHtml = view('rapid-consulting.dashboard', [
            'results' => $results,
            'pdfMode' => $pdfMode,
            'pdfPart' => $coverPart,
            'hide_nav' => $hide_nav,
            'logoDataUri' => $logoDataUri,
        ])->render();
        $bodyHtml = view('rapid-consulting.dashboard', [
            'results' => $results,
            'pdfMode' => $pdfMode,
            'pdfPart' => $bodyPart,
            'hide_nav' => $hide_nav,
            'logoDataUri' => $logoDataUri,
        ])->render();

        $coverPdf = Browsershot::html($coverHtml)
            ->setNodeBinary($this->browsershotNodeBinary())
            ->setNpmBinary($this->browsershotNpmBinary())
            ->setChromePath($this->browsershotChromePath())
            ->setNodeModulePath(base_path('node_modules'))
            ->format('A4')
            ->margins(0, 0, 0, 0)
            ->setOption('preferCSSPageSize', true)
            ->showBackground()
            ->emulateMedia('print')
            ->waitUntilNetworkIdle()
            ->setDelay(500)
            ->noSandbox()
            ->addChromiumArguments(['disable-setuid-sandbox'])
            ->pdf();

        $bodyPdf = Browsershot::html($bodyHtml)
            ->setNodeBinary($this->browsershotNodeBinary())
            ->setNpmBinary($this->browsershotNpmBinary())
            ->setChromePath($this->browsershotChromePath())
            ->setNodeModulePath(base_path('node_modules'))
            ->format('A4')
            ->margins(0, 0, 0, 0)
            ->setOption('preferCSSPageSize', true)
            ->showBackground()
            ->showBrowserHeaderAndFooter()
            ->headerHtml($this->snapshotPdfHeaderTemplate($results, $logoDataUri))
            ->footerHtml($this->snapshotPdfFooterTemplate($results))
            ->initialPageNumber(2)
            ->emulateMedia('print')
            ->waitUntilNetworkIdle()
            ->setDelay(500)
            ->noSandbox()
            ->addChromiumArguments(['disable-setuid-sandbox'])
            ->pdf();

        $pdf = $this->mergePdfParts([$coverPdf, $bodyPdf]);

        $filename = sprintf(
            '%s-snapshot-report-%s-%s.pdf',
            strtolower((string) $lead->type),
            $lead->id,
            now()->toDateString()
        );

        return response($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }

    private function mergePdfParts(array $pdfParts): string
    {
        $temporaryFiles = [];
        $merged = new Fpdi();

        try {
            foreach ($pdfParts as $pdfPart) {
                $path = tempnam(sys_get_temp_dir(), 'rab-pdf-part-');
                file_put_contents($path, $pdfPart);
                $temporaryFiles[] = $path;

                $pageCount = $merged->setSourceFile($path);

                for ($pageNumber = 1; $pageNumber <= $pageCount; $pageNumber++) {
                    $templateId = $merged->importPage($pageNumber);
                    $size = $merged->getTemplateSize($templateId);
                    $orientation = ($size['width'] ?? 0) > ($size['height'] ?? 0) ? 'L' : 'P';

                    $merged->AddPage($orientation, [$size['width'], $size['height']]);
                    $merged->useTemplate($templateId);
                }
            }

            return $merged->Output('S');
        } finally {
            foreach ($temporaryFiles as $temporaryFile) {
                if (is_file($temporaryFile)) {
                    @unlink($temporaryFile);
                }
            }
        }
    }

    private function browsershotNodeBinary(): string
    {
        return $this->executablePath(env('BROWSERSHOT_NODE_BINARY'), 'node', '/usr/bin/node');
    }

    private function browsershotNpmBinary(): string
    {
        return $this->executablePath(env('BROWSERSHOT_NPM_BINARY'), 'npm', '/usr/bin/npm');
    }

    private function browsershotChromePath(): string
    {
        return $this->executablePath(env('BROWSERSHOT_CHROME_PATH'), 'google-chrome', '/usr/bin/google-chrome');
    }

    private function executablePath(?string $configuredPath, string $binary, string $fallback): string
    {
        if ($configuredPath && is_executable($configuredPath)) {
            return $configuredPath;
        }

        if (is_executable($fallback)) {
            return $fallback;
        }

        $resolved = trim((string) shell_exec('command -v ' . escapeshellarg($binary)));

        return $resolved !== '' ? $resolved : $binary;
    }

    private function snapshotPdfHeaderTemplate(array $results, string $logoDataUri): string
    {
        $title = $this->snapshotReportTitle($results);

        return '<div style="width:100%;height:18mm;padding:0 12mm;font-family:Inter,Arial,Helvetica,sans-serif;color:#0f172a;display:flex;align-items:center;justify-content:space-between;border-bottom:1px solid #cbd5e1;box-sizing:border-box;">'
            . '<div style="display:flex;align-items:center;gap:8px;font-weight:800;font-size:9px;letter-spacing:.08em;text-transform:uppercase;">'
            . '<img src="' . e($logoDataUri) . '" style="height:20px;width:auto;display:block;" />'
            . '<span>RAB Consulting Services</span>'
            . '</div>'
            . '<div style="font-size:9px;font-weight:800;letter-spacing:.08em;text-transform:uppercase;text-align:right;">' . e($title) . '</div>'
            . '</div>';
    }

    private function snapshotPdfFooterTemplate(array $results): string
    {
        $client = $results['user']['company'] ?? 'Client organisation';
        $date = isset($results['assessment_date'])
            ? Carbon::parse($results['assessment_date'])->format('d F Y')
            : now()->format('d F Y');

        return '<div style="width:100%;height:16mm;padding:0 12mm;font-family:Inter,Arial,Helvetica,sans-serif;color:#475569;display:flex;align-items:center;justify-content:space-between;border-top:1px solid #cbd5e1;box-sizing:border-box;font-size:8px;">'
            . '<div style="width:32%;font-weight:700;">Confidential &mdash; RAB Consulting Services</div>'
            . '<div style="width:36%;text-align:center;">' . e($client) . ' &middot; ' . e($date) . '</div>'
            . '<div style="width:32%;text-align:right;font-weight:700;">Page <span class="pageNumber"></span> of <span class="totalPages"></span></div>'
            . '</div>';
    }

    private function snapshotReportTitle(array $results): string
    {
        return strtolower((string) ($results['type'] ?? 'pir')) === 'sir'
            ? 'Service Intelligence Snapshot Report'
            : 'Programme Intelligence Snapshot Report';
    }

    private function logoDataUri(): string
    {
        $path = public_path('assets/images/logo-rab.png');

        if (! is_file($path)) {
            return '';
        }

        $mime = mime_content_type($path) ?: 'image/png';

        return 'data:' . $mime . ';base64,' . base64_encode((string) file_get_contents($path));
    }

    private function authorizeSnapshotPdfDownload(Request $request, Lead $lead): void
    {
        $sessionResults = Session::get('rc_results', []);
        $sessionOwnsLead = (int) ($sessionResults['lead_id'] ?? 0) === (int) $lead->id
            && hash_equals((string) ($sessionResults['booking_token'] ?? ''), (string) $lead->booking_token);
        $tokenMatches = is_string($request->query('token'))
            && hash_equals((string) $lead->booking_token, (string) $request->query('token'));
        $authOwnsLead = auth()->check()
            && hash_equals(strtolower((string) auth()->user()->email), strtolower((string) $lead->email));

        abort_unless($sessionOwnsLead || $tokenMatches || $authOwnsLead, 403);
        abort_unless(in_array($lead->assessment_type, ['PIR_SNAPSHOT', 'SIR_SNAPSHOT'], true), 404);
    }

    private function snapshotResultsFromLead(Lead $lead): array
    {
        $payload = $this->snapshotAiPayloadBuilder->buildFromLead($lead);
        $isSir = strtoupper((string) $lead->type) === 'SIR';
        $scoreKey = $isSir ? 'domain_scores' : 'pillar_scores';
        $nameKey = $isSir ? 'domain_names' : 'pillar_names';

        $areaScores = collect($payload[$scoreKey] ?? [])
            ->mapWithKeys(fn ($score, $code) => [$code => [
                'name' => $payload[$nameKey][$code] ?? $code,
                'score' => (float) $score,
                'rag' => $this->rag((float) $score),
            ]])
            ->all();

        $indexScores = $isSir
            ? [
                'SSI' => $payload['ssi'] ?? null,
                'SMI' => $payload['smi'] ?? null,
                'SIMI' => $payload['simi'] ?? null,
                'BAURI' => $payload['bau_ri'] ?? null,
                'CHI' => $payload['chi'] ?? null,
                'smi_simi_delta' => $payload['smi_simi_delta'] ?? null,
            ]
            : [
                'BRI' => $payload['bri'] ?? null,
                'VRI' => $payload['vri'] ?? null,
                'DMI' => $payload['dmi'] ?? null,
                'RII' => $payload['rii'] ?? null,
                'CHI' => $payload['chi'] ?? null,
            ];

        $results = [
            'lead_id' => $lead->id,
            'booking_token' => $lead->booking_token,
            'type' => strtolower((string) $lead->type),
            'subject_name' => $lead->company,
            'user' => [
                'name' => $lead->name,
                'email' => $lead->email,
                'company' => $lead->company,
                'job_title' => $lead->role_title,
                'industry' => $lead->industry,
            ],
            'overall_score' => (float) $lead->overall_score,
            'rag_status' => $lead->rag_status,
            'pillar_scores' => $areaScores,
            'index_scores' => array_filter($indexScores, fn ($value) => $value !== null),
            'delivery_stage' => $lead->delivery_stage,
            'service_context' => $lead->service_context,
            'regulatory_context' => $lead->regulatory_context,
            'assessment_date' => $lead->created_at?->toDateString(),
        ];

        $results['snapshot_report'] = $this->snapshotReportService->build(
            $results,
            $lead->ai_recommendation,
            $lead->snapshot_report_json
        );

        return $results;
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
