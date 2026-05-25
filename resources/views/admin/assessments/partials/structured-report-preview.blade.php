@php
    $report = is_array($fullReportDraft ?? null) ? $fullReportDraft : [];
    $dashboard = is_array($report['intelligence_dashboard'] ?? null) ? $report['intelligence_dashboard'] : [];
    $overall = is_array($dashboard['overall'] ?? null) ? $dashboard['overall'] : [];
    $indices = is_array($dashboard['indices'] ?? null) ? $dashboard['indices'] : [];
    $profile = is_array($report['intelligence_profile'] ?? null) ? $report['intelligence_profile'] : [];
    $riskRegister = is_array($report['risk_register'] ?? null) ? $report['risk_register'] : [];
    $raid = is_array($report['raid_summary'] ?? null) ? $report['raid_summary'] : [];
    $rootCause = is_array($report['root_cause_analysis'] ?? null) ? $report['root_cause_analysis'] : [];
    $priorityPlan = is_array($report['priority_plan'] ?? null) ? $report['priority_plan'] : [];
    $rawJson = json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

    $asText = function ($value) use (&$asText): string {
        if (is_array($value)) {
            return trim(implode(' ', array_filter(array_map($asText, $value))));
        }

        return trim((string) $value);
    };

    $asList = function ($value) use ($asText): array {
        if (is_array($value)) {
            return array_values(array_filter(array_map($asText, $value), fn ($item) => $item !== ''));
        }

        $text = $asText($value);

        return $text === '' ? [] : [$text];
    };

    $toneClass = function ($value): string {
        $normalised = strtolower((string) $value);

        return match (true) {
            in_array($normalised, ['green', 'controlled', 'low', 'l'], true) => 'bg-green-100 text-green-800 border-green-200',
            in_array($normalised, ['red', 'critical', 'high', 'h'], true) => 'bg-red-100 text-red-800 border-red-200',
            default => 'bg-amber-100 text-amber-800 border-amber-200',
        };
    };

    $ratingLabel = function ($value) use ($asText): string {
        $normalised = strtoupper($asText($value));

        return match ($normalised) {
            'H' => 'High',
            'M' => 'Medium',
            'L' => 'Low',
            default => $asText($value),
        };
    };

    $badge = fn ($value, ?string $label = null) => '<span class="inline-flex items-center px-2.5 py-1 rounded-full border text-[11px] font-bold ' . e($toneClass($value)) . '">' . e(($label ? "{$label}: " : '') . ($ratingLabel($value) ?: '-')) . '</span>';
    $scoreValue = function ($value) {
        return is_numeric($value) ? number_format((float) $value, 2) . ' / 5' : 'Not available';
    };
    $normalisedIndex = function (array $indices, string $key): ?array {
        $raw = $indices[$key] ?? $indices[strtolower($key)] ?? null;

        if ($raw === null) {
            return null;
        }

        if (is_numeric($raw)) {
            return [
                'value' => (float) $raw,
                'interpretation' => null,
            ];
        }

        if (! is_array($raw)) {
            return null;
        }

        return [
            'value' => $raw['value'] ?? $raw['score'] ?? null,
            'interpretation' => $raw['interpretation'] ?? null,
        ];
    };
    $confidenceLegend = function ($legend) use ($asText): array {
        if (! is_array($legend)) {
            return [];
        }

        $normalised = [];

        foreach (['high' => 'High', 'medium' => 'Medium', 'low' => 'Low'] as $key => $label) {
            if (isset($legend[$key])) {
                $normalised[] = ['label' => $label, 'definition' => $asText($legend[$key])];
            }
        }

        if ($normalised !== []) {
            return $normalised;
        }

        foreach ($legend as $item) {
            if (! is_array($item)) {
                continue;
            }

            $label = $item['label'] ?? null;
            $definition = $item['definition'] ?? null;

            if ($asText($label) !== '' || $asText($definition) !== '') {
                $normalised[] = [
                    'label' => $asText($label) ?: 'Confidence',
                    'definition' => $asText($definition),
                ];
            }
        }

        return $normalised;
    };
@endphp

<div class="space-y-6">
    @if(!empty($report['cover_letter']))
        @php
            $letterParagraphs = collect(preg_split('/\n\s*\n/', trim((string) $report['cover_letter']), -1, PREG_SPLIT_NO_EMPTY))
                ->map(fn ($paragraph) => trim($paragraph))
                ->filter()
                ->values();
            $signature = $letterParagraphs->count() > 1 ? $letterParagraphs->pop() : null;
        @endphp
        <section class="bg-white border border-slate-200 shadow-sm p-6">
            <h4 class="text-xs font-black text-slate-500 uppercase tracking-widest mb-4">Cover Letter</h4>
            <div class="max-w-4xl space-y-4 text-sm text-slate-700 leading-7">
                @foreach($letterParagraphs as $paragraph)
                    <p class="whitespace-pre-wrap">{{ $paragraph }}</p>
                @endforeach
                @if($signature)
                    <div class="pt-4 mt-4 border-t border-slate-100 text-slate-900 font-semibold whitespace-pre-wrap">{{ $signature }}</div>
                @endif
            </div>
        </section>
    @endif

    @if(!empty($report['executive_position']))
        <section class="bg-white border border-slate-200 shadow-sm p-6">
            <div class="flex flex-col lg:flex-row lg:items-start lg:justify-between gap-4 mb-4">
                <div>
                    <h4 class="text-xs font-black text-slate-500 uppercase tracking-widest mb-2">Executive Position</h4>
                    <div class="flex flex-wrap gap-2">
                        <span class="inline-flex items-center px-3 py-1 rounded-full border border-slate-200 bg-slate-50 text-xs font-bold text-slate-700">Score {{ $scoreValue($overall['score'] ?? $assessment->overall_score) }}</span>
                        {!! $badge($overall['rag'] ?? $assessment->rag_status, 'RAG') !!}
                        <span class="inline-flex items-center px-3 py-1 rounded-full border border-blue-100 bg-blue-50 text-xs font-bold text-blue-800">Stage: {{ $overall['stage'] ?? $assessment->delivery_stage ?? 'Not recorded' }}</span>
                    </div>
                </div>
                @if(!empty($report['final_position']))
                    <div class="bg-amber-50 border border-amber-200 p-4 max-w-md">
                        <p class="text-[10px] font-black uppercase tracking-widest text-amber-700 mb-1">Decision Required</p>
                        <p class="text-sm font-bold text-amber-950">{{ $report['final_position'] }}</p>
                    </div>
                @endif
            </div>
            <p class="text-sm text-slate-700 leading-7 whitespace-pre-wrap">{{ $report['executive_position'] }}</p>
        </section>
    @endif

    @if($dashboard !== [])
        <section class="bg-white border border-slate-200 shadow-sm p-6">
            <h4 class="text-xs font-black text-slate-500 uppercase tracking-widest mb-4">Intelligence Dashboard</h4>
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 mb-4">
                <div class="bg-slate-50 border border-slate-100 p-4">
                    <p class="text-[10px] font-black uppercase tracking-widest text-slate-400">Overall Score</p>
                    <p class="text-2xl font-black text-slate-900 mt-1">{{ $scoreValue($overall['score'] ?? $assessment->overall_score) }}</p>
                </div>
                <div class="bg-slate-50 border border-slate-100 p-4">
                    <p class="text-[10px] font-black uppercase tracking-widest text-slate-400">RAG Status</p>
                    <div class="mt-2">{!! $badge($overall['rag'] ?? $assessment->rag_status, 'RAG') !!}</div>
                </div>
                <div class="bg-slate-50 border border-slate-100 p-4">
                    <p class="text-[10px] font-black uppercase tracking-widest text-slate-400">Delivery Stage</p>
                    <p class="text-sm font-bold text-slate-900 mt-2">Stage: {{ $overall['stage'] ?? $assessment->delivery_stage ?? 'Not recorded' }}</p>
                </div>
            </div>

            <p class="text-xs text-slate-500 mb-3">Indices are scored from 1 to 5.</p>
            <div class="grid grid-cols-1 md:grid-cols-5 gap-3 mb-4">
                @foreach(['BRI' => 'Business Readiness Index', 'VRI' => 'Value Realisation Index', 'DMI' => 'Digital Maturity Index', 'RII' => 'Risk Intelligence Index', 'CHI' => 'Compliance Health Index'] as $indexKey => $indexName)
                    @php $index = $normalisedIndex($indices, $indexKey); @endphp
                    @if($index)
                        <div class="border border-slate-200 p-4">
                            <p class="text-[10px] font-black uppercase tracking-widest text-slate-400">{{ $indexName }}</p>
                            <p class="text-xl font-black text-slate-900 mt-1">{{ $indexKey }} · {{ $scoreValue($index['value']) }}</p>
                            @if(filled($index['interpretation']))
                                <p class="text-xs text-slate-600 leading-5 mt-2">{{ $index['interpretation'] }}</p>
                            @endif
                        </div>
                    @endif
                @endforeach
            </div>
            @if(collect(['BRI', 'VRI', 'DMI', 'RII', 'CHI'])->every(fn ($indexKey) => $normalisedIndex($indices, $indexKey) === null))
                <p class="text-sm text-slate-500 mb-4">Not available from generated report.</p>
            @endif

            <div class="space-y-3">
                <div>
                    <p class="text-[10px] font-black uppercase tracking-widest text-slate-400 mb-2">Alert Flags</p>
                    @forelse($asList($dashboard['alert_flags'] ?? []) as $alert)
                        <span class="inline-flex items-center px-2.5 py-1 rounded-full bg-red-50 border border-red-100 text-[11px] font-bold text-red-700 mr-2 mb-2">{{ $alert }}</span>
                    @empty
                        <span class="text-sm text-slate-500">No alert flags triggered.</span>
                    @endforelse
                </div>
                <div>
                    <p class="text-[10px] font-black uppercase tracking-widest text-slate-400 mb-2">Confidence Legend</p>
                    <div class="flex flex-wrap gap-2">
                        @forelse($confidenceLegend($dashboard['confidence_legend'] ?? []) as $item)
                            <span class="inline-flex items-center px-2.5 py-1 rounded-full border text-[11px] font-bold {{ $toneClass($item['label'] ?? '') }}">{{ $item['label'] }} — {{ $item['definition'] }}</span>
                        @empty
                            @foreach(['High', 'Medium', 'Low'] as $label)
                                <span class="inline-flex items-center px-2.5 py-1 rounded-full border text-[11px] font-bold {{ $toneClass($label) }}">{{ $label }}</span>
                            @endforeach
                        @endforelse
                    </div>
                </div>
            </div>
        </section>
    @endif

    @if($profile !== [])
        <section class="bg-white border border-slate-200 shadow-sm p-6">
            <h4 class="text-xs font-black text-slate-500 uppercase tracking-widest mb-4">Intelligence Profile</h4>
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
                @foreach($profile as $finding)
                    @php $finding = is_array($finding) ? $finding : ['headline' => $asText($finding)]; @endphp
                    <article class="border border-slate-200 p-4">
                        <div class="flex flex-wrap items-center gap-2 mb-3">
                            <span class="text-[11px] font-black text-slate-500 uppercase tracking-widest">{{ $finding['pillar_code'] ?? 'Finding' }}</span>
                            {!! $badge($finding['rag'] ?? '-', 'RAG') !!}
                            <span class="inline-flex items-center px-2.5 py-1 rounded-full border text-[11px] font-bold {{ $toneClass($finding['confidence'] ?? 'Medium') }}">{{ $finding['confidence'] ?? 'Medium' }}</span>
                        </div>
                        <h5 class="text-sm font-black text-slate-900 mb-1">{{ $finding['headline'] ?? $finding['pillar_name'] ?? $finding['domain_name'] ?? 'Finding' }}</h5>
                        @if(!empty($finding['pillar_name']))
                            <p class="text-xs font-semibold text-slate-500 mb-2">{{ $finding['pillar_name'] }}</p>
                        @endif
                        <p class="text-xs text-slate-500 mb-3">Score: {{ $finding['score'] ?? '-' }}</p>
                        @if($asList($finding['evidence'] ?? []) !== [])
                            <ul class="list-disc pl-5 text-sm text-slate-700 leading-6 mb-3">
                                @foreach($asList($finding['evidence'] ?? []) as $evidence)
                                    <li>{{ $evidence }}</li>
                                @endforeach
                            </ul>
                        @endif
                        @if(!empty($finding['business_impact']))
                            <p class="text-sm text-slate-700 leading-6 mb-2"><strong>Business impact:</strong> {{ $asText($finding['business_impact']) }}</p>
                        @endif
                        @if(!empty($finding['compliance_dimension']))
                            <p class="text-sm text-amber-800 leading-6 mb-2"><strong>Compliance dimension:</strong> {{ $asText($finding['compliance_dimension']) }}</p>
                        @endif
                        @if(!empty($finding['action']))
                            <p class="text-sm text-slate-900 leading-6"><strong>Action:</strong> {{ $asText($finding['action']) }}</p>
                        @endif
                    </article>
                @endforeach
            </div>
        </section>
    @endif

    @if(array_key_exists('reporting_accuracy_risk_finding', $report))
        <section class="border shadow-sm p-6 {{ empty($report['reporting_accuracy_risk_finding']) ? 'bg-white border-slate-200' : 'bg-amber-50 border-amber-200' }}">
            <h4 class="text-xs font-black text-slate-500 uppercase tracking-widest mb-4">Reporting Accuracy Risk Finding</h4>
            <p class="text-sm text-slate-700 leading-7 whitespace-pre-wrap">{{ $asText($report['reporting_accuracy_risk_finding']) ?: 'No reporting accuracy risk finding recorded.' }}</p>
        </section>
    @endif

    @if($riskRegister !== [])
        <section class="bg-white border border-slate-200 shadow-sm p-6">
            <h4 class="text-xs font-black text-slate-500 uppercase tracking-widest mb-4">Risk Register</h4>
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="text-left text-[10px] font-black uppercase tracking-widest text-slate-400 border-b border-slate-100">
                        <tr>
                            <th class="py-2 pr-4">Risk</th>
                            <th class="py-2 pr-4">Probability</th>
                            <th class="py-2 pr-4">Impact</th>
                            <th class="py-2 pr-4">Owner</th>
                            <th class="py-2 pr-4">Current Control</th>
                            <th class="py-2">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach($riskRegister as $risk)
                            @php $risk = is_array($risk) ? $risk : ['risk_title' => $asText($risk)]; @endphp
                            <tr class="align-top">
                                <td class="py-3 pr-4 font-bold text-slate-900">{{ $risk['risk_title'] ?? 'Risk' }}</td>
                                <td class="py-3 pr-4">{!! $badge($risk['probability'] ?? '-') !!}</td>
                                <td class="py-3 pr-4">{!! $badge($risk['impact'] ?? '-') !!}</td>
                                <td class="py-3 pr-4 text-slate-700">{{ $risk['owner'] ?? '-' }}</td>
                                <td class="py-3 pr-4 text-slate-700">{{ $risk['current_control'] ?? '-' }}</td>
                                <td class="py-3 text-slate-700">{{ $risk['action'] ?? '-' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <p class="text-xs text-slate-500 mt-3">Probability and Impact use High / Medium / Low ratings.</p>
        </section>
    @endif

    @if($raid !== [])
        <section class="bg-white border border-slate-200 shadow-sm p-6">
            <h4 class="text-xs font-black text-slate-500 uppercase tracking-widest mb-4">RAID Summary</h4>
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 mb-4">
                @foreach(['total_risks' => 'Total Risks', 'critical_risks' => 'Critical Risks', 'issues_without_owner' => 'Issues Without Owner', 'overdue_actions' => 'Overdue Actions'] as $key => $label)
                    <div class="bg-slate-50 border border-slate-100 p-4">
                        <p class="text-[10px] font-black uppercase tracking-widest text-slate-400">{{ $label }}</p>
                        <p class="text-2xl font-black text-slate-900 mt-1">{{ $raid[$key] ?? 0 }}</p>
                    </div>
                @endforeach
            </div>
            <p class="text-sm text-slate-700 leading-7 whitespace-pre-wrap">{{ $raid['assessment'] ?? 'No RAID assessment returned.' }}</p>
        </section>
    @endif

    @if($rootCause !== [])
        <section class="bg-white border border-slate-200 shadow-sm p-6">
            <h4 class="text-xs font-black text-slate-500 uppercase tracking-widest mb-4">Root Cause Analysis</h4>
            <p class="text-sm text-slate-700 leading-7 whitespace-pre-wrap mb-4">{{ $rootCause['narrative'] ?? 'No root cause narrative returned.' }}</p>
            <div class="bg-red-50 border border-red-100 p-4 mb-4">
                <p class="text-[10px] font-black uppercase tracking-widest text-red-700 mb-1">Primary Cause</p>
                <p class="text-sm font-bold text-red-950">{{ $rootCause['primary_cause'] ?? '-' }}</p>
            </div>
            @if($asList($rootCause['causal_chain'] ?? []) !== [])
                <ol class="space-y-3">
                    @foreach($asList($rootCause['causal_chain'] ?? []) as $step)
                        <li class="flex gap-3">
                            <span class="w-6 h-6 shrink-0 rounded-full bg-slate-900 text-white text-xs font-bold flex items-center justify-center">{{ $loop->iteration }}</span>
                            <span class="text-sm text-slate-700 leading-6">{{ $step }}</span>
                        </li>
                    @endforeach
                </ol>
            @endif
        </section>
    @endif

    @if($priorityPlan !== [])
        <section class="bg-white border border-slate-200 shadow-sm p-6">
            <h4 class="text-xs font-black text-slate-500 uppercase tracking-widest mb-4">Priority Plan</h4>
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
                @foreach(['30_days' => '30 Days', '60_days' => '60 Days', '90_days' => '90 Days'] as $horizon => $label)
                    <div class="border border-slate-200 p-4">
                        <p class="text-[10px] font-black uppercase tracking-widest text-slate-400 mb-3">{{ $label }}</p>
                        <div class="space-y-3">
                            @forelse(($priorityPlan[$horizon] ?? []) as $item)
                                @php $item = is_array($item) ? $item : ['action_title' => $asText($item)]; @endphp
                                <div class="border-b border-slate-100 pb-3 last:border-b-0 last:pb-0">
                                    <p class="text-sm font-bold text-slate-900">{{ $item['action_title'] ?? $item['action'] ?? 'Action' }}</p>
                                    <p class="text-xs text-slate-500 mt-1">Owner: {{ $item['owner'] ?? '-' }}</p>
                                    <p class="text-xs text-slate-500">Deadline: {{ $item['deadline'] ?? '-' }}</p>
                                    <p class="text-xs text-slate-700 mt-2"><strong>Done condition:</strong> {{ $item['done_condition'] ?? '-' }}</p>
                                </div>
                            @empty
                                <p class="text-sm text-slate-500">No action returned.</p>
                            @endforelse
                        </div>
                    </div>
                @endforeach
            </div>
        </section>
    @endif

    @if(!empty($report['final_position']))
        <section class="bg-slate-900 text-white shadow-sm p-6">
            <h4 class="text-xs font-black text-slate-300 uppercase tracking-widest mb-3">Final Position</h4>
            <p class="text-xl font-black leading-8">{{ $report['final_position'] }}</p>
        </section>
    @endif

    @if(!empty($report['tier1_bridge']))
        <section class="bg-white border border-slate-200 shadow-sm p-6">
            <h4 class="text-xs font-black text-slate-500 uppercase tracking-widest mb-4">Evidence Gaps / Recommended Deep-Dive</h4>
            <p class="text-sm text-slate-700 leading-7 whitespace-pre-wrap">{{ $asText($report['tier1_bridge']) }}</p>
        </section>
    @endif

    @if(array_key_exists('compliance_risk_signals', $report))
        <section class="bg-amber-50 border border-amber-200 shadow-sm p-6">
            <h4 class="text-xs font-black text-amber-700 uppercase tracking-widest mb-4">Compliance Risk Signals</h4>
            <p class="text-sm text-amber-950 leading-7 whitespace-pre-wrap">{{ $asText($report['compliance_risk_signals']) ?: 'No compliance risk signals triggered.' }}</p>
        </section>
    @endif

    <details class="bg-slate-50 border border-slate-200 p-4">
        <summary class="cursor-pointer text-xs font-black text-slate-500 uppercase tracking-widest">View raw JSON</summary>
        <pre class="mt-4 text-xs text-slate-700 leading-6 whitespace-pre-wrap overflow-x-auto">{{ $rawJson }}</pre>
    </details>
</div>
