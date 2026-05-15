@extends('layouts.public')
@section('title', 'Intelligence Snapshot — RAB Consulting')

@section('content')
<section class="min-h-screen bg-white py-12 md:py-20">
    <div class="container max-w-4xl mx-auto px-6">
        
        <!-- 1. HEADER -->
        <header class="flex flex-col md:flex-row justify-between items-start gap-6 mb-8">
            <div class="shrink-0">
                <img src="/assets/images/logo-rab.png" alt="RAB Consulting" class="h-12 md:h-14">
            </div>
            <div class="md:text-right">
                <h1 class="text-2xl md:text-3xl font-black text-slate-900 uppercase tracking-tighter leading-none mb-2">
                    {{ $lead->type === 'SIR' ? 'Service' : 'Programme' }} Intelligence Snapshot
                </h1>
                <p class="text-[10px] md:text-xs font-black text-slate-400 uppercase tracking-widest">
                    {{ $lead->created_at->format('d M Y') }} | {{ $lead->company }}
                </p>
            </div>
        </header>

        <!-- 2. SINGLE-LINE DISCLAIMER -->
        <div class="border-y border-slate-100 py-4 mb-12">
            <p class="text-[11px] md:text-xs font-bold text-slate-500 italic leading-relaxed text-center md:text-left">
                “Based on your responses, this snapshot reflects RAB Consulting’s proprietary {{ $lead->type === 'SIR' ? 'Service' : 'Programme' }} Intelligence methodology and provides an initial view of delivery risk and control.”
            </p>
        </div>

        @php
            // Robust AI Parsing
            $aiText = $lead->ai_recommendation ?? '';
            $parts = preg_split('/\*\*(.*?)\*\*/', $aiText, -1, PREG_SPLIT_DELIM_CAPTURE);
            $segments = [];
            for ($i = 1; $i < count($parts); $i += 2) {
                $segments[strtolower(trim($parts[$i]))] = trim($parts[$i+1] ?? '');
            }

            // Map segments to the required sections
            $execSummary = $segments['executive summary'] ?? $segments['programme health overview'] ?? null;
            $insights = $segments['key insights'] ?? $segments['what this indicates'] ?? null;
            $risks = $segments['top risks'] ?? $segments['emerging risk signals'] ?? null;
            $whatItMeans = $segments['what this means'] ?? $segments['what this means for your programme'] ?? null;
            
            // Map pillar scores from lead
            $pillarScores = [];
            $indexScores = $lead->index_scores_json ?? [];
            foreach($indexScores as $code => $data) {
                if(str_starts_with($code, 'P') || str_starts_with($code, 'D')) {
                    $pillarScores[] = [
                        'code' => $code,
                        'name' => is_array($data) ? ($data['name'] ?? $code) : $code,
                        'score' => is_array($data) ? ($data['score'] ?? 0) : $data,
                        'rag' => is_array($data) ? ($data['rag'] ?? 'N/A') : ($data < 2.5 ? 'Red' : ($data < 3.8 ? 'Amber' : 'Green'))
                    ];
                }
            }

            // Generate fallback summary if AI is missing
            if (!$execSummary) {
                $exposedPillars = collect($pillarScores)->filter(fn($p) => $p['rag'] !== 'Green')->pluck('name')->toArray();
                $exposedText = !empty($exposedPillars) ? " but exposed risk in: " . implode(', ', array_slice($exposedPillars, 0, 4)) : ".";
                $statusMsg = $lead->overall_score >= 3.8 ? "shows strong delivery control" : ($lead->overall_score >= 3.2 ? "shows partial control" : "shows significant delivery exposure");
                $execSummary = "Your overall score of " . number_format($lead->overall_score, 1) . " " . $statusMsg . $exposedText . " We recommend a focused consultant review to address these gaps and prevent escalation. Your critical control domains are broadly stable. Focus on continuous improvement and addressing the specific gaps identified above.";
            }
        @endphp

        <!-- 3. EXECUTIVE SNAPSHOT -->
        <div class="mb-16">
            <h2 class="text-[10px] font-black text-blue-600 uppercase tracking-[0.3em] mb-6">{{ $lead->type === 'SIR' ? 'Service' : 'Programme' }} Health Overview</h2>
            <div class="text-base md:text-lg text-slate-600 leading-[1.8] font-medium italic">
                {{ $execSummary }}
            </div>
        </div>

        <!-- 4. OVERALL SCORE (VISUAL BLOCK) -->
        <div class="mb-20">
            <h2 class="text-[10px] font-black text-slate-400 uppercase tracking-[0.3em] mb-6">Overall {{ $lead->type === 'SIR' ? 'Service' : 'Programme' }} Health</h2>
            <div class="bg-slate-50 rounded-[2.5rem] p-10 md:p-12 border border-slate-100 flex flex-col md:flex-row items-center gap-10 md:gap-20">
                <div class="text-center md:text-left">
                    <div class="text-7xl md:text-8xl font-black tracking-tighter @if($lead->rag_status === 'Red') text-red-600 @elseif($lead->rag_status === 'Amber') text-amber-500 @else text-green-600 @endif">
                        {{ number_format($lead->overall_score, 1) }}<span class="text-2xl md:text-3xl text-slate-300 ml-1">/ 5</span>
                    </div>
                </div>
                <div class="flex-1 text-center md:text-left">
                    <div class="text-2xl md:text-4xl font-black text-slate-900 uppercase tracking-tighter mb-2">
                        @php
                            $statusLabel = 'Controlled';
                            if($lead->overall_score < 2.5) $statusLabel = 'Critical';
                            elseif($lead->overall_score < 3.2) $statusLabel = 'At Risk';
                            elseif($lead->overall_score < 3.8) $statusLabel = 'Stable but Exposed';
                        @endphp
                        {{ $statusLabel }}
                    </div>
                    <p class="text-xs md:text-sm font-bold text-slate-500 leading-relaxed max-w-sm">
                        “This score reflects aggregated performance across governance, delivery, planning, and operational readiness.”
                    </p>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-16 mb-20">
            <!-- 5. DOMAIN BREAKDOWN -->
            <div>
                <h2 class="text-[10px] font-black text-slate-400 uppercase tracking-[0.3em] mb-8">Key Areas of Exposure</h2>
                <div class="space-y-6">
                    @foreach(collect($pillarScores)->take(7) as $pillar)
                        <div class="group">
                            <div class="flex justify-between items-end mb-2">
                                <span class="text-[11px] font-black text-slate-900 uppercase tracking-widest">{{ $pillar['name'] }}</span>
                                <span class="text-[9px] font-black uppercase @if($pillar['rag'] === 'Red') text-red-600 @elseif($pillar['rag'] === 'Amber') text-amber-500 @else text-green-600 @endif tracking-widest">
                                    @if($pillar['score'] >= 3.8) Strong @elseif($pillar['score'] >= 3.2) Stable @elseif($pillar['score'] >= 2.5) Exposed @else Critical @endif
                                </span>
                            </div>
                            <div class="h-1.5 w-full bg-slate-100 rounded-full overflow-hidden">
                                <div class="h-full @if($pillar['rag'] === 'Red') bg-red-600 @elseif($pillar['rag'] === 'Amber') bg-amber-500 @else bg-green-600 @endif transition-all duration-1000" style="width: {{ ($pillar['score'] / 5) * 100 }}%"></div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            <!-- 6. INTELLIGENCE INDICES -->
            <div>
                <h2 class="text-[10px] font-black text-slate-400 uppercase tracking-[0.3em] mb-8">Intelligence Indices</h2>
                <div class="grid grid-cols-2 gap-4">
                    @php
                        $indices = $lead->index_scores_json ?? [];
                    @endphp
                    @foreach(['BRI', 'VRI', 'DMI', 'RII', 'CHI', 'SSI', 'SMI', 'SIMI', 'BAURI'] as $idx)
                        @if(isset($indices[$idx]))
                            <div class="bg-slate-50/50 border border-slate-100 rounded-2xl p-4">
                                <span class="block text-[9px] font-black text-slate-400 uppercase tracking-widest mb-1">{{ $idx }}</span>
                                <span class="text-xl font-black text-slate-900">{{ number_format($indices[$idx], 1) }}</span>
                            </div>
                        @endif
                    @endforeach
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-16 mb-20">
            <!-- 7. KEY INSIGHTS -->
                <h2 class="text-[10px] font-black text-blue-600 uppercase tracking-[0.3em] mb-8">What This Indicates</h2>
                
                <!-- Part 1: Strategic Interpretation (General) -->
                <div class="mb-12 pb-12 border-b border-slate-100">
                    <h3 class="text-[9px] font-black text-slate-400 uppercase tracking-widest mb-6">Strategic Interpretation</h3>
                    <div class="space-y-6">
                        @if($insights)
                            @php
                                $insightList = preg_split('/^- /m', $insights, -1, PREG_SPLIT_NO_EMPTY);
                            @endphp
                            @foreach(collect($insightList)->take(3) as $item)
                                <div class="flex gap-4">
                                    <div class="mt-1.5 w-1.5 h-1.5 rounded-full bg-blue-600 shrink-0"></div>
                                    <p class="text-[13px] font-medium text-slate-600 leading-relaxed">{{ trim($item) }}</p>
                                </div>
                            @endforeach
                        @else
                            <p class="text-[13px] text-slate-400 italic">Evaluating cross-domain patterns and delivery control...</p>
                        @endif
                    </div>
                </div>

                <!-- Part 2: Domain-Specific Indicators (Per Pillar) -->
                <div>
                    <h3 class="text-[9px] font-black text-slate-400 uppercase tracking-widest mb-6">Domain-Specific Indicators</h3>
                    <div class="space-y-3">
                        @foreach($pillarScores as $pillar)
                            <div class="flex items-center justify-between gap-4 p-3 rounded-lg bg-white border border-slate-100 shadow-sm">
                                <div class="flex gap-3 items-center">
                                    <div class="w-3 h-3 rounded @if($pillar['rag'] === 'Red') bg-red-500 @elseif($pillar['rag'] === 'Amber') bg-amber-400 @else bg-green-600 @endif shrink-0"></div>
                                    <span class="text-[10px] font-black text-slate-900 uppercase tracking-tight">{{ $pillar['name'] }}</span>
                                </div>
                                <span class="text-[9px] font-bold text-slate-400 italic shrink-0">
                                    @if($pillar['score'] >= 3.8) optimal control @elseif($pillar['score'] >= 3.2) acceptable @elseif($pillar['score'] >= 2.5) vulnerable @else critical gap @endif
                                </span>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>

        <!-- 7. RISK SIGNALS -->
        <div class="mb-20">
            <h2 class="text-[10px] font-black text-red-600 uppercase tracking-[0.3em] mb-8">Emerging Risk Signals</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                @if($risks)
                    @php
                        $riskList = preg_split('/^- /m', $risks, -1, PREG_SPLIT_NO_EMPTY);
                    @endphp
                    @foreach(collect($riskList)->take(5) as $item)
                        <div class="bg-red-50/30 p-6 rounded-2xl border border-red-100/50 flex gap-4">
                            <svg class="w-5 h-5 text-red-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                            <p class="text-[13px] font-bold text-red-900 leading-relaxed">{{ trim($item) }}</p>
                        </div>
                    @endforeach
                @else
                    <p class="text-[13px] text-slate-400 italic">Identifying potential late-stage rework drivers...</p>
                @endif
            </div>
        </div>

        <!-- 8. WHAT THIS MEANS -->
        <div class="mb-20">
            <h2 class="text-[10px] font-black text-slate-900 uppercase tracking-[0.3em] mb-8">What This Means for Your {{ $lead->type === 'SIR' ? 'Service' : 'Programme' }}</h2>
            <div class="space-y-8">
                @if($whatItMeans)
                    @php
                        $meansList = preg_split('/^- /m', $whatItMeans, -1, PREG_SPLIT_NO_EMPTY);
                        if(count($meansList) < 2) $meansList = explode("\n", $whatItMeans);
                    @endphp
                    @foreach(collect($meansList)->take(3) as $item)
                        <div class="border-l-4 border-slate-900 pl-8">
                            <p class="text-base md:text-lg font-black text-slate-800 leading-relaxed">{{ trim($item) }}</p>
                        </div>
                    @endforeach
                @else
                    <div class="border-l-4 border-slate-100 pl-8">
                        <p class="text-base text-slate-400 italic">Evaluating delivery assumptions and recovery complexity...</p>
                    </div>
                @endif
            </div>
        </div>

        <!-- 9. NEXT STEP (CTA) - Hidden in Print -->
        <div class="print:hidden bg-blue-600 rounded-[3rem] p-10 md:p-16 text-center shadow-2xl shadow-blue-500/20 relative overflow-hidden group">
            <div class="absolute inset-0 bg-gradient-to-br from-white/10 to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-700"></div>
            <h2 class="text-[10px] font-black text-blue-100 uppercase tracking-[0.4em] mb-6 relative">Recommended Next Step</h2>
            <p class="text-xl md:text-2xl font-black text-white leading-tight max-w-2xl mx-auto mb-10 relative">
                “A full Programme Insight Review provides a structured, independent validation of these findings, identifies root causes, and defines clear recovery actions.”
            </p>
            <div class="flex flex-col sm:flex-row justify-center gap-4 relative">
                <a href="{{ route('booking.index', ['name' => $lead->name, 'email' => $lead->email]) }}" 
                   class="bg-white text-blue-600 px-10 py-5 rounded-full font-black uppercase tracking-tighter hover:scale-105 transition shadow-xl active:scale-95">
                    Book a Consultation
                </a>
                <button onclick="window.print()" class="bg-blue-700/50 text-white border border-blue-500/50 px-10 py-5 rounded-full font-black uppercase tracking-tighter hover:bg-blue-700 transition">
                    Download PDF Report
                </button>
            </div>
        </div>

        <div class="mt-20 pt-10 border-t border-slate-100 text-center">
             <p class="text-[9px] font-black text-slate-300 uppercase tracking-[0.3em]">RAB Consulting © {{ date('Y') }} — Confidential Intelligence Snapshot</p>
        </div>

    </div>
</section>

<style type="text/css" media="print">
    @page { size: auto; margin: 0mm; }
    body { background: white; }
    .print\:hidden { display: none !important; }
    section { padding: 20mm !important; }
</style>
@endsection
