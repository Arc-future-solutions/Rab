<?php

namespace App\Services;

use App\Models\Assessment;
use App\Models\AssessmentFramework;
use App\Models\AssessmentQuestionBank;
use App\Models\Lead;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\File;
use InvalidArgumentException;
use Throwable;

class AiSmokeTestReportRunner
{
    private const PROMPT_CASES = [
        'pir_snapshot' => ['framework' => 'PIR', 'report_type' => 'snapshot', 'tier' => null],
        'sir_snapshot' => ['framework' => 'SIR', 'report_type' => 'snapshot', 'tier' => null],
        'pir_full_tier1' => ['framework' => 'PIR', 'report_type' => 'full', 'tier' => 'Tier 1 Rapid'],
        'pir_full_tier2' => ['framework' => 'PIR', 'report_type' => 'full', 'tier' => 'Tier 2 Full'],
        'sir_full_tier1' => ['framework' => 'SIR', 'report_type' => 'full', 'tier' => 'Tier 1 Rapid'],
        'sir_full_tier2' => ['framework' => 'SIR', 'report_type' => 'full', 'tier' => 'Tier 2 Full'],
    ];

    private const FULL_REQUIRED_SECTIONS = [
        'pir_full_tier1' => [
            'cover_letter',
            'executive_position',
            'intelligence_dashboard',
            'intelligence_profile',
            'reporting_accuracy_risk_finding',
            'risk_register',
            'raid_summary',
            'root_cause_analysis',
            'priority_plan',
            'final_position',
            'tier1_bridge',
            'compliance_risk_signals',
        ],
        'pir_full_tier2' => [
            'cover_letter',
            'executive_position',
            'intelligence_dashboard',
            'stakeholder_intelligence',
            'intelligence_profile',
            'reporting_accuracy_risk_finding',
            'risk_register',
            'raid_summary',
            'root_cause_analysis',
            'priority_plan',
            'final_position',
            'evidence_validated_statement',
            'compliance_risk_signals',
        ],
        'sir_full_tier1' => [
            'cover_letter',
            'executive_position',
            'intelligence_dashboard',
            'intelligence_profile',
            'risk_register',
            'root_cause_analysis',
            'priority_plan',
            'final_position',
            'tier1_bridge',
            'compliance_risk_signals',
        ],
        'sir_full_tier2' => [
            'cover_letter',
            'executive_position',
            'intelligence_dashboard',
            'stakeholder_intelligence',
            'intelligence_profile',
            'risk_register',
            'root_cause_analysis',
            'priority_plan',
            'final_position',
            'evidence_validated_statement',
            'compliance_risk_signals',
        ],
    ];

    private const FINAL_POSITION_VALUES = [
        'pir_full_tier1' => [
            'Viable trajectory with focused intervention',
            'Requires structured recovery before go-live can proceed',
            'Go-live should not proceed until identified conditions are resolved',
        ],
        'pir_full_tier2' => [
            'Viable trajectory with focused intervention',
            'Requires structured recovery before go-live can proceed',
            'Go-live should not proceed until identified conditions are resolved',
        ],
        'sir_full_tier1' => [
            'Stable and improving with focused action',
            'Stable but not resilient — improvement programme required',
            'Needs stabilisation before further transformation can be absorbed',
        ],
        'sir_full_tier2' => [
            'Stable and improving with focused action',
            'Stable but not resilient - service improvement plan required',
            'Needs stabilisation before further transformation can be absorbed',
        ],
    ];

    public function __construct(
        private AiReportGenerationService $aiReportGeneration,
        private AssessmentAiPayloadBuilder $assessmentPayloadBuilder,
        private SnapshotAiPayloadBuilder $snapshotPayloadBuilder
    ) {
    }

    public function run(Command $command, string $source = 'both', array $promptKeys = []): int
    {
        $source = strtolower($source);
        if (! in_array($source, ['synthetic', 'database', 'both'], true)) {
            throw new InvalidArgumentException('The --source option must be synthetic, database, or both.');
        }

        $promptKeys = array_values(array_filter(array_map('strval', $promptKeys)));
        $cases = self::PROMPT_CASES;

        if ($promptKeys !== []) {
            $unknownPromptKeys = array_values(array_diff($promptKeys, array_keys(self::PROMPT_CASES)));

            if ($unknownPromptKeys !== []) {
                throw new InvalidArgumentException('Unknown --prompt value(s): ' . implode(', ', $unknownPromptKeys));
            }

            $cases = array_intersect_key(self::PROMPT_CASES, array_flip($promptKeys));
        }

        $timestamp = now()->format('Ymd_His');
        $relativeDirectory = "ai-smoke-tests/{$timestamp}";
        $absoluteDirectory = storage_path("app/{$relativeDirectory}");
        File::ensureDirectoryExists($absoluteDirectory);

        $context = $this->environmentContext();

        $command->info('AI smoke-test output directory: ' . $absoluteDirectory);
        $command->line('Anthropic model: ' . ($context['model'] ?: '[missing]'));
        $command->line('Anthropic base URL host: ' . ($context['base_url_host'] ?: '[missing]'));
        $command->line('Anthropic API key: ' . ($context['api_key_configured'] ? '[configured]' : '[missing]'));
        $command->line('Prompt keys: ' . implode(', ', array_keys($cases)));

        $summary = [];

        foreach ($cases as $promptKey => $case) {
            $sourceResults = [
                'synthetic' => $source === 'database'
                    ? $this->skipped('skipped_source_not_requested')
                    : $this->runSynthetic($promptKey, $case, $context),
                'database' => $source === 'synthetic'
                    ? $this->skipped('skipped_source_not_requested')
                    : $this->runDatabase($promptKey, $case, $context),
            ];

            $document = [
                'prompt_key' => $promptKey,
                'framework' => $case['framework'],
                'report_type' => $case['report_type'],
                'tier' => $case['tier'],
                'model' => $context['model'],
                'base_url_host' => $context['base_url_host'],
                'timestamp' => Carbon::now()->toIso8601String(),
                'smoke_test' => [
                    'source' => $source,
                    'source_results' => $this->sourceResultRows($sourceResults),
                ],
            ];

            $filePath = "{$absoluteDirectory}/{$promptKey}.json";
            File::put($filePath, json_encode($document, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

            $summary[] = [
                'prompt_key' => $promptKey,
                'file' => $filePath,
                'synthetic' => $sourceResults['synthetic']['status'],
                'database' => $sourceResults['database']['status'],
            ];

            $command->line(sprintf(
                '%s: synthetic=%s database=%s',
                $promptKey,
                $sourceResults['synthetic']['status'],
                $sourceResults['database']['status']
            ));
        }

        $command->newLine();
        $command->info('Generated smoke-test files:');
        foreach ($summary as $item) {
            $command->line(sprintf(
                '- %s: %s (synthetic=%s, database=%s)',
                $item['prompt_key'],
                $item['file'],
                $item['synthetic'],
                $item['database']
            ));
        }

        return Command::SUCCESS;
    }

    private function runSynthetic(string $promptKey, array $case, array $context): array
    {
        $payload = $case['report_type'] === 'snapshot'
            ? $this->syntheticSnapshotPayload($case['framework'])
            : $this->syntheticFullPayload($case['framework'], $case['tier']);

        return $this->runGeneration($promptKey, $case, $context, $payload, [
            'source' => 'synthetic',
            'framework' => $case['framework'],
            'report_type' => $case['report_type'],
            'tier' => $case['tier'],
        ]);
    }

    private function sourceResultRows(array $sourceResults): array
    {
        return collect($sourceResults)
            ->map(fn (array $result, string $source) => ['source' => $source] + $result)
            ->values()
            ->all();
    }

    private function runDatabase(string $promptKey, array $case, array $context): array
    {
        if ($case['report_type'] === 'snapshot') {
            $lead = Lead::query()
                ->where('assessment_type', $case['framework'] . '_SNAPSHOT')
                ->latest('id')
                ->first();

            if (! $lead) {
                return $this->skipped('skipped_no_matching_record');
            }

            $payload = $this->payloadFromLead($lead, $case['framework']);

            return $this->runGeneration($promptKey, $case, $context, $payload, [
                'source' => 'database',
                'lead_id' => $lead->id,
                'framework' => $case['framework'],
                'report_type' => $case['report_type'],
            ]);
        }

        $assessment = Assessment::query()
            ->with(['client', 'assessor', 'pillarScores', 'questionResponses'])
            ->where('type', $case['framework'])
            ->where('report_tier', $case['tier'])
            ->latest('id')
            ->first();

        if (! $assessment) {
            return $this->skipped('skipped_no_matching_record');
        }

        $payload = $this->assessmentPayloadBuilder->buildFullPayload($assessment);

        return $this->runGeneration($promptKey, $case, $context, $payload, [
            'source' => 'database',
            'assessment_id' => $assessment->id,
            'framework' => $case['framework'],
            'report_type' => $case['report_type'],
            'tier' => $case['tier'],
        ]);
    }

    private function runGeneration(
        string $promptKey,
        array $case,
        array $context,
        array $payload,
        array $metadata
    ): array {
        $started = microtime(true);
        $systemPrompt = config("ai.prompts.{$promptKey}");

        try {
            if (! $context['api_key_configured']) {
                throw new InvalidArgumentException('Anthropic API key is not configured.');
            }

            if (! $context['model']) {
                throw new InvalidArgumentException('Anthropic model is not configured.');
            }

            if (! $context['base_url']) {
                throw new InvalidArgumentException('Anthropic base URL is not configured.');
            }

            if (! is_string($systemPrompt) || trim($systemPrompt) === '') {
                throw new InvalidArgumentException("Prompt not found: {$promptKey}");
            }

            $response = $this->aiReportGeneration->generate($promptKey, $systemPrompt, $payload, $metadata);
            $decoded = $this->decodedReport($response);
            $validationNotes = $this->validateDecodedReport(
                $promptKey,
                $case['report_type'],
                $decoded,
                $response['output'] ?? null
            );

            return [
                'status' => $validationNotes === [] ? 'passed' : 'passed_with_warnings',
                'provider_response_id' => $response['provider_response_id'] ?? null,
                'raw_output' => $response['output'] ?? null,
                'decoded_json' => $decoded,
                'validation_notes' => $validationNotes,
                'duration_ms' => $this->durationMs($started),
            ];
        } catch (Throwable $e) {
            return [
                'status' => $e instanceof InvalidArgumentException ? 'failed_configuration' : 'failed',
                'provider_response_id' => null,
                'raw_output' => null,
                'decoded_json' => null,
                'validation_notes' => [$e->getMessage()],
                'duration_ms' => $this->durationMs($started),
            ];
        }
    }

    private function decodedReport(array $response): ?array
    {
        if (isset($response['report']) && is_array($response['report'])) {
            return $response['report'];
        }

        if (isset($response['snapshot_report_json']) && is_array($response['snapshot_report_json'])) {
            return $response['snapshot_report_json'];
        }

        if (isset($response['output']) && is_string($response['output'])) {
            $decoded = json_decode($response['output'], true);

            return json_last_error() === JSON_ERROR_NONE && is_array($decoded)
                ? $decoded
                : null;
        }

        return null;
    }

    private function validateDecodedReport(string $promptKey, string $reportType, ?array $decoded, ?string $rawOutput = null): array
    {
        $notes = [];

        if (is_string($rawOutput) && str_contains($rawOutput, '```')) {
            $notes[] = 'Anthropic output must be valid JSON only; markdown fences are not allowed.';
        }

        if (! is_array($decoded)) {
            $notes[] = 'Anthropic output did not parse as a JSON object.';

            return $notes;
        }

        if ($this->containsForbiddenResponseKey($decoded)) {
            $notes[] = 'AI response must not expose hidden_risk or score_anchors.';
        }

        if ($reportType === 'snapshot') {
            return array_merge($notes, $this->validateSnapshotReport($promptKey, $decoded));
        }

        return array_merge($notes, $this->validateFullReport($promptKey, $decoded));
    }

    private function validateSnapshotReport(string $promptKey, array $decoded): array
    {
        $notes = [];
        $allowedSections = ['intelligence_brief', 'insight_cards'];

        foreach (array_diff(array_keys($decoded), $allowedSections) as $section) {
            $notes[] = "Unexpected snapshot section: {$section}.";
        }

        if (! array_key_exists('intelligence_brief', $decoded)) {
            $notes[] = 'Missing required snapshot section: intelligence_brief.';
        } elseif (! is_string($decoded['intelligence_brief'])) {
            $notes[] = 'Snapshot section intelligence_brief is not a string.';
        } else {
            $notes = array_merge($notes, $this->validateSnapshotBrief($promptKey, $decoded['intelligence_brief']));
        }

        if (! array_key_exists('insight_cards', $decoded)) {
            $notes[] = 'Missing required snapshot section: insight_cards.';
        } elseif (! is_array($decoded['insight_cards'])) {
            $notes[] = 'Snapshot section insight_cards is not an array.';
        } else {
            $notes = array_merge($notes, $this->validateInsightCards($promptKey, $decoded['insight_cards']));
        }

        return $notes;
    }

    private function validateSnapshotBrief(string $promptKey, string $brief): array
    {
        $notes = [];
        $paragraphs = preg_split('/\R\s*\R/', trim($brief)) ?: [];
        $paragraphs = array_values(array_filter(array_map('trim', $paragraphs), fn (string $paragraph) => $paragraph !== ''));

        if (count($paragraphs) !== 2) {
            $notes[] = 'Snapshot intelligence_brief must contain exactly two paragraphs.';
        }

        if (isset($paragraphs[0])) {
            $wordCount = $this->wordCount($paragraphs[0]);
            if ($wordCount < 70 || $wordCount > 100) {
                $notes[] = "Snapshot intelligence_brief paragraph 1 must be 70-100 words; received {$wordCount}.";
            }
        }

        if (isset($paragraphs[1])) {
            $wordCount = $this->wordCount($paragraphs[1]);
            if ($wordCount < 55 || $wordCount > 75) {
                $notes[] = "Snapshot intelligence_brief paragraph 2 must be 55-75 words; received {$wordCount}.";
            }

            $expectedClosing = $promptKey === 'sir_snapshot'
                ? 'A full Service Intelligence Review would establish the root causes with evidence and produce a prioritised service improvement plan.'
                : 'A full Programme Intelligence Review would establish the root causes with evidence and produce a prioritised action plan.';

            if (! str_ends_with($paragraphs[1], $expectedClosing)) {
                $notes[] = 'Snapshot intelligence_brief paragraph 2 does not end with the required closing sentence.';
            }
        }

        return $notes;
    }

    private function validateInsightCards(string $promptKey, array $cards): array
    {
        $notes = [];
        $requiredKeys = ['score', 'rag', 'finding', 'action'];

        if (count($cards) !== 3) {
            $notes[] = 'Snapshot insight_cards must contain exactly 3 cards.';
        }

        foreach ($cards as $index => $card) {
            if (! is_array($card)) {
                $notes[] = "Snapshot insight_cards item {$index} is not an object.";
                continue;
            }

            foreach ($requiredKeys as $key) {
                if (! array_key_exists($key, $card)) {
                    $notes[] = "Snapshot insight_cards item {$index} is missing {$key}.";
                }
            }

            if ($promptKey === 'sir_snapshot') {
                if (! array_key_exists('domain_code', $card) && ! array_key_exists('pillar_code', $card)) {
                    $notes[] = "Snapshot insight_cards item {$index} is missing domain_code.";
                }

                if (! array_key_exists('domain_name', $card) && ! array_key_exists('pillar_name', $card)) {
                    $notes[] = "Snapshot insight_cards item {$index} is missing domain_name.";
                }
            } else {
                if (! array_key_exists('pillar_code', $card)) {
                    $notes[] = "Snapshot insight_cards item {$index} is missing pillar_code.";
                }

                if (! array_key_exists('pillar_name', $card)) {
                    $notes[] = "Snapshot insight_cards item {$index} is missing pillar_name.";
                }
            }

            if (isset($card['finding']) && is_string($card['finding']) && mb_strlen($card['finding']) > 220) {
                $notes[] = "Snapshot insight_cards item {$index} finding exceeds 220 characters.";
            }

            if (isset($card['action']) && is_string($card['action']) && mb_strlen($card['action']) > 160) {
                $notes[] = "Snapshot insight_cards item {$index} action exceeds 160 characters.";
            }
        }

        return $notes;
    }

    private function validateFullReport(string $promptKey, array $decoded): array
    {
        $notes = [];
        $allowedSections = self::FULL_REQUIRED_SECTIONS[$promptKey] ?? [];

        foreach (array_diff(array_keys($decoded), $allowedSections) as $section) {
            $notes[] = "Unexpected full-report section: {$section}.";
        }

        $notes = collect($allowedSections)
            ->reject(fn (string $section) => array_key_exists($section, $decoded))
            ->map(fn (string $section) => "Missing required full-report section: {$section}.")
            ->merge($notes)
            ->values()
            ->all();

        if (isset($decoded['cover_letter']) && is_string($decoded['cover_letter'])) {
            if (! str_contains($decoded['cover_letter'], '[CONSULTANT TO COMPLETE: specific milestone or date]')) {
                $notes[] = 'cover_letter must contain the required [CONSULTANT TO COMPLETE: specific milestone or date] placeholder.';
            }

            if (! str_ends_with(trim($decoded['cover_letter']), 'Reda Boukhiar, Director, RAB Consulting Services.')) {
                $notes[] = 'cover_letter must end with the required Reda Boukhiar signature.';
            }
        }

        if (isset($decoded['final_position']) && is_string($decoded['final_position'])) {
            $allowedFinalPositions = self::FINAL_POSITION_VALUES[$promptKey] ?? [];
            if ($allowedFinalPositions !== [] && ! in_array($decoded['final_position'], $allowedFinalPositions, true)) {
                $notes[] = 'final_position is not one of the permitted values.';
            }
        }

        if (isset($decoded['priority_plan']) && is_array($decoded['priority_plan'])) {
            $min = str_contains($promptKey, 'tier2') ? 4 : 2;
            $max = str_contains($promptKey, 'tier2') ? 5 : 4;

            foreach (['30_days', '60_days', '90_days'] as $horizon) {
                if (! isset($decoded['priority_plan'][$horizon]) || ! is_array($decoded['priority_plan'][$horizon])) {
                    $notes[] = "priority_plan.{$horizon} must be an array.";
                    continue;
                }

                $count = count($decoded['priority_plan'][$horizon]);
                if ($count < $min || $count > $max) {
                    $notes[] = "priority_plan.{$horizon} must contain {$min}-{$max} actions; received {$count}.";
                }
            }
        }

        if (($decoded['compliance_risk_signals'] ?? null) !== null) {
            if (! is_string($decoded['compliance_risk_signals'])) {
                $notes[] = 'compliance_risk_signals must be a string or null.';
            } elseif (! str_ends_with(trim($decoded['compliance_risk_signals']), 'Operational intelligence only. Engage legal and compliance advisers.')) {
                $notes[] = 'compliance_risk_signals must end with the required legal and compliance adviser sentence.';
            }
        }

        return $notes;
    }

    private function containsForbiddenResponseKey(array $value): bool
    {
        foreach ($value as $key => $item) {
            if (in_array($key, ['hidden_risk', 'score_anchors'], true)) {
                return true;
            }

            if (is_array($item) && $this->containsForbiddenResponseKey($item)) {
                return true;
            }
        }

        return false;
    }

    private function wordCount(string $value): int
    {
        return str_word_count(str_replace(['—', '–'], ' ', $value));
    }

    private function payloadFromLead(Lead $lead, string $framework): array
    {
        $answers = $lead->answers_json ?? [];
        $pillarScores = $this->pillarScoresFromAnswers($framework, $answers);

        if ($pillarScores === []) {
            $pillarScores = $this->pillarScoresFromLeadFallback($framework, $lead);
        }

        $results = [
            'overall_score' => (float) $lead->overall_score,
            'rag_status' => $lead->rag_status,
            'pillar_scores' => $pillarScores,
            'index_scores' => $lead->index_scores_json ?? [],
            'type' => strtolower($framework),
            'delivery_stage' => $framework === 'PIR' ? 'Build' : null,
            'service_context' => $framework === 'SIR' ? 'Transformation' : null,
            'regulatory_context' => $lead->regulatory_context,
            'user' => [
                'name' => $lead->name,
                'company' => $lead->company,
                'email' => $lead->email,
                'industry' => $lead->industry,
                'job_title' => $lead->role_title,
                'phone' => $lead->phone,
            ],
        ];

        return $this->snapshotPayloadBuilder->build($framework, $results, $answers);
    }

    private function pillarScoresFromAnswers(string $framework, array $answers): array
    {
        $frameworkModel = AssessmentFramework::where('code', $framework)->first();
        if (! $frameworkModel) {
            return [];
        }

        $questions = AssessmentQuestionBank::query()
            ->where('framework_id', $frameworkModel->id)
            ->where('level', 'snapshot')
            ->with('pillar')
            ->get();

        if ($questions->isEmpty()) {
            return [];
        }

        return $questions
            ->groupBy(fn (AssessmentQuestionBank $question) => $question->pillar?->code ?? $this->pillarCodeFromQuestion($question->question_code))
            ->map(function ($pillarQuestions, string $pillarCode) use ($answers, $framework) {
                $scores = $pillarQuestions
                    ->map(fn (AssessmentQuestionBank $question) => $answers[$question->question_code] ?? $answers[str_replace('.', '_', $question->question_code)] ?? null)
                    ->filter(fn ($score) => is_numeric($score))
                    ->map(fn ($score) => (float) $score)
                    ->values();

                if ($scores->isEmpty()) {
                    return null;
                }

                $firstQuestion = $pillarQuestions->first();
                $score = round($scores->avg(), 2);

                return [
                    'name' => $this->areaName($framework, $pillarCode, $firstQuestion->pillar?->name),
                    'score' => $score,
                    'rag' => $this->rag($score),
                    'is_critical' => false,
                ];
            })
            ->filter()
            ->all();
    }

    private function pillarScoresFromLeadFallback(string $framework, Lead $lead): array
    {
        $topAreas = $lead->top_three_insight_areas_json ?? [];

        if ($topAreas !== []) {
            return collect($topAreas)
                ->mapWithKeys(function (array $area, int $index) use ($framework) {
                    $code = $framework === 'PIR' ? 'P' . ($index + 1) : 'D' . ($index + 1);
                    $score = (float) ($area['score'] ?? 0);

                    return [$code => [
                        'name' => $area['pillarOrDomain'] ?? $this->areaName($framework, $code),
                        'score' => $score,
                        'rag' => $this->rag($score),
                        'is_critical' => false,
                    ]];
                })
                ->all();
        }

        return $framework === 'PIR'
            ? $this->scoreRows($framework, ['P1' => 2.4, 'P2' => 3.1, 'P3' => 2.8])
            : $this->scoreRows($framework, ['D1' => 2.5, 'D2' => 2.7, 'D11' => 2.6]);
    }

    private function syntheticSnapshotPayload(string $framework): array
    {
        $scores = $framework === 'PIR'
            ? $this->scoreRows('PIR', [
                'P1' => 2.2,
                'P2' => 3.1,
                'P3' => 2.7,
                'P4' => 3.4,
                'P5' => 2.4,
                'P6' => 3.2,
                'P7' => 2.5,
                'P8' => 3.6,
                'P9' => 2.6,
                'P10' => 3.0,
            ])
            : $this->scoreRows('SIR', [
                'D1' => 2.5,
                'D2' => 2.4,
                'D3' => 3.2,
                'D4' => 2.8,
                'D5' => 3.1,
                'D6' => 3.0,
                'D7' => 2.6,
                'D8' => 3.3,
                'D9' => 2.9,
                'D10' => 2.7,
                'D11' => 2.3,
                'D12' => 3.4,
            ]);

        $results = [
            'overall_score' => $framework === 'PIR' ? 2.87 : 2.85,
            'rag_status' => 'Amber',
            'pillar_scores' => $scores,
            'index_scores' => $framework === 'PIR'
                ? ['BRI' => 2.74, 'VRI' => 2.68, 'DMI' => 2.86, 'RII' => 2.44, 'CHI' => 2.2]
                : ['SSI' => 2.58, 'SMI' => 3.01, 'SIMI' => 2.55, 'BAURI' => 2.63, 'CHI' => 2.7, 'smi_simi_delta' => 0.46],
            'type' => strtolower($framework),
            'delivery_stage' => $framework === 'PIR' ? 'Build' : null,
            'service_context' => $framework === 'SIR' ? 'Transformation' : null,
            'regulatory_context' => $framework === 'PIR' ? 'fca_uk' : 'dora_eu',
            'user' => [
                'company' => $framework === 'PIR' ? 'RAB Synthetic Programme Co' : 'RAB Synthetic Service Co',
                'industry' => $framework === 'PIR' ? 'Financial services transformation' : 'Managed technology services',
            ],
        ];

        return $this->snapshotPayloadBuilder->build($framework, $results, $this->syntheticAnswers($framework));
    }

    private function syntheticFullPayload(string $framework, ?string $tier): array
    {
        $isPir = $framework === 'PIR';
        $scoreMap = $isPir
            ? ['P1' => 2.2, 'P2' => 3.1, 'P3' => 2.7, 'P4' => 3.4, 'P5' => 2.4, 'P6' => 3.2, 'P7' => 2.5, 'P8' => 3.6, 'P9' => 2.6, 'P10' => 3.0]
            : ['D1' => 2.5, 'D2' => 2.4, 'D3' => 3.2, 'D4' => 2.8, 'D5' => 3.1, 'D6' => 3.0, 'D7' => 2.6, 'D8' => 3.3, 'D9' => 2.9, 'D10' => 2.7, 'D11' => 2.3, 'D12' => 3.4];

        $payload = [
            'framework' => $framework,
            'assessment_id' => 'synthetic-' . strtolower($framework) . '-' . str_replace(' ', '-', strtolower((string) $tier)),
            'client_company' => $isPir ? 'RAB Synthetic Programme Co' : 'RAB Synthetic Service Co',
            'assessment_date' => now()->toDateString(),
            'primary_concern' => $isPir
                ? 'The sponsor is concerned that reported delivery status is more optimistic than delivery evidence supports.'
                : 'The IT Director is concerned that recurring incidents are not visible in management reporting.',
            'overall_score' => $isPir ? 2.87 : 2.85,
            'rag_status' => 'Amber',
            'regulatory_context' => $isPir ? 'fca_uk' : 'dora_eu',
            'alert_flags' => collect($scoreMap)->filter(fn ($score) => $score < 3.0)->keys()->map(fn ($code) => "{$code} below 3.0")->values()->all(),
            'question_responses' => $this->syntheticQuestionResponses($framework),
            'compliance_question_scores' => $isPir ? ['P1.F8' => 2, 'P5.F8' => 2] : ['D10.F2' => 2, 'D9.F3' => 2],
            'tier' => $tier === 'Tier 2 Full' ? 'Briefing' : 'Review',
            'consultant_name' => 'Reda Boukhiar',
            'sponsor_name' => $isPir ? 'Alex Sponsor' : 'Nadia CIO',
            'interview_count' => $tier === 'Tier 2 Full' ? 6 : 3,
            'documents_reviewed' => $isPir
                ? ['RAID log', 'Programme plan', 'Steering committee pack']
                : ['SLA pack', 'Incident trend report', 'Change log'],
            'confidence_level' => 'Medium',
            'evidence_notes' => $this->syntheticEvidenceNotes($framework),
            'emerging_issues' => $isPir
                ? ['Escalations are not closed in governance forums', 'Data migration defects are increasing', 'BAU ownership is not confirmed']
                : ['Major incident learning is not retained', 'CMDB accuracy is disputed', 'Problem management has no owner'],
            'stakeholder_notes' => $tier === 'Tier 2 Full' ? [
                'sponsor_position' => $isPir ? 'Sponsor expects the next milestone to remain green.' : 'IT Director believes service stability is improving.',
                'operational_position' => $isPir ? 'Delivery leads report unresolved risks that are absent from the board pack.' : 'Service manager reports recurring incidents outside SLA reporting.',
                'divergence_areas' => $isPir ? ['status reporting', 'cutover readiness'] : ['incident visibility', 'CMDB accuracy'],
            ] : null,
        ];

        if ($isPir) {
            return [
                ...$payload,
                'programme_name' => 'Finance Transformation Release 2',
                'programme_type' => 'ERP transformation',
                'delivery_stage' => 'Build',
                'pillar_scores' => $scoreMap,
                'pillar_names' => $this->areaNames('PIR'),
                'bri' => 2.74,
                'vri' => 2.68,
                'dmi' => 2.86,
                'rii' => 2.44,
                'chi' => 2.2,
                'programme_value' => 1850000.0,
                'reporting_accuracy_risk' => true,
                'reporting_accuracy_evidence' => 'Steering pack reports amber-green while RAID evidence shows three unresolved critical risks.',
            ];
        }

        return [
            ...$payload,
            'service_name' => 'Customer Operations Platform',
            'service_context' => 'Transformation',
            'domain_scores' => $scoreMap,
            'domain_names' => $this->areaNames('SIR'),
            'ssi' => 2.58,
            'smi' => 3.01,
            'simi' => 2.55,
            'bau_ri' => 2.63,
            'chi' => 2.7,
            'smi_simi_delta' => 0.46,
            'annual_service_cost' => 620000.0,
        ];
    }

    private function syntheticAnswers(string $framework): array
    {
        return $framework === 'PIR'
            ? ['P1.F1' => 2, 'P1.F8' => 2, 'P3.F1' => 3, 'P5.F8' => 2, 'P7.F1' => 2, 'P9.F1' => 3]
            : ['D1.F1' => 2, 'D2.F1' => 2, 'D4.F1' => 3, 'D9.F3' => 2, 'D10.F2' => 2, 'D11.F1' => 2];
    }

    private function syntheticQuestionResponses(string $framework): array
    {
        return collect($this->syntheticAnswers($framework))
            ->reject(fn ($score, string $code) => str_starts_with($code, 'note_'))
            ->map(fn ($score, string $code) => [
                'id' => $code,
                'pillar_code' => $this->pillarCodeFromQuestion($code),
                'score' => (int) $score,
                'is_compliance' => in_array($code, $framework === 'PIR' ? ['P1.F8', 'P5.F8'] : ['D9.F3', 'D10.F2'], true),
            ])
            ->values()
            ->all();
    }

    private function syntheticEvidenceNotes(string $framework): array
    {
        return collect($this->syntheticQuestionResponses($framework))
            ->mapWithKeys(fn (array $response) => [$response['id'] => [
                'note' => $framework === 'PIR'
                    ? 'Interview evidence and document review show the control is partly defined but not operating consistently.'
                    : 'Service records and interview evidence show the process exists but is not consistently followed.',
                'respondent_role' => $framework === 'PIR' ? 'Programme Director' : 'Service Manager',
                'document_source' => $framework === 'PIR' ? 'RAID log and board pack' : 'SLA pack and incident log',
                'confidence' => 'Medium',
                'stakeholder_divergence_note' => $response['score'] <= 2 ? 'Sponsor and operational views differ on the severity.' : null,
            ]])
            ->all();
    }

    private function scoreRows(string $framework, array $scores): array
    {
        return collect($scores)
            ->map(fn (float $score, string $code) => [
                'name' => $this->areaName($framework, $code),
                'score' => $score,
                'rag' => $this->rag($score),
                'is_critical' => in_array($code, $framework === 'PIR' ? ['P1', 'P5', 'P7'] : ['D1', 'D2', 'D10'], true),
            ])
            ->all();
    }

    private function areaNames(string $framework): array
    {
        if ($framework === 'PIR') {
            return [
                'P1' => 'P1 - Governance and Decision-Making',
                'P2' => 'P2 - Planning, Stage Gates and Delivery Control',
                'P3' => 'P3 - Business Alignment, Value and Financial Control',
                'P4' => 'P4 - Change Management, Training and Adoption',
                'P5' => 'P5 - Data Readiness, Migration and GDPR',
                'P6' => 'P6 - Solution, Process Fit and UAT',
                'P7' => 'P7 - Cutover, Go-Live, Decommissioning and Archiving',
                'P8' => 'P8 - Delivery Capability, Security and RACI',
                'P9' => 'P9 - Operational, Automation Readiness and Data Archiving',
                'P10' => 'P10 - Digital and Transformation Maturity',
            ];
        }

        return [
            'D1' => 'D1 - Service Governance and Ownership',
            'D2' => 'D2 - Incident and Major Incident Management',
            'D3' => 'D3 - Service Request Management',
            'D4' => 'D4 - Problem Management',
            'D5' => 'D5 - Change and Release Management',
            'D6' => 'D6 - Service Performance, SLA and Reporting',
            'D7' => 'D7 - Service Transition and BAU Readiness',
            'D8' => 'D8 - Service Operations and Support Model',
            'D9' => 'D9 - Supplier and Vendor Management',
            'D10' => 'D10 - Operational Resilience and Continuity',
            'D11' => 'D11 - Service Tooling, CMDB and Knowledge Management',
            'D12' => 'D12 - Service Intelligence and Continuous Value',
        ];
    }

    private function areaName(string $framework, string $code, ?string $databaseName = null): string
    {
        if ($databaseName) {
            return "{$code} - {$databaseName}";
        }

        return $this->areaNames($framework)[$code] ?? $code;
    }

    private function pillarCodeFromQuestion(string $questionCode): string
    {
        if (preg_match('/^([PD]\d+)/', $questionCode, $matches)) {
            return $matches[1];
        }

        return $questionCode;
    }

    private function rag(float $score): string
    {
        if ($score >= 3.8) {
            return 'Green';
        }

        return $score >= 2.5 ? 'Amber' : 'Red';
    }

    private function environmentContext(): array
    {
        $baseUrl = (string) config('services.anthropic.base_url');

        return [
            'api_key_configured' => filled(config('services.anthropic.api_key')),
            'model' => (string) config('services.anthropic.model'),
            'base_url' => $baseUrl,
            'base_url_host' => parse_url($baseUrl, PHP_URL_HOST) ?: null,
        ];
    }

    private function skipped(string $status): array
    {
        return [
            'status' => $status,
            'provider_response_id' => null,
            'raw_output' => null,
            'decoded_json' => null,
            'validation_notes' => [],
            'duration_ms' => 0,
        ];
    }

    private function durationMs(float $started): int
    {
        return (int) round((microtime(true) - $started) * 1000);
    }
}
