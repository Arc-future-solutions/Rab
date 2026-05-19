@extends('layouts.public')
@section('title', 'Intelligence Snapshot — RAB Consulting')

@section('content')
@php
    $weakest = collect($results['pillar_scores'])->sortBy('score')->first();
    $strongest = collect($results['pillar_scores'])->sortByDesc('score')->first();
    $criticalPillars = collect($results['pillar_scores'])->filter(fn($p) => $p['score'] < 3.0);
    
    $programmeState = "Trajectory shows a " . strtolower($results['rag_status']) . " status, driven by volatility in " . $weakest['name'] . " (" . number_format($weakest['score'], 1) . "). While " . $strongest['name'] . " is strong, the " . number_format($results['overall_score'], 1) . " score indicates delivery lifecycle exposure.";
    $directionalFocus = "Immediate focus required for " . $criticalPillars->pluck('name')->implode(', ') . ". Specifically, " . $weakest['name'] . " requires an urgent audit to stabilise performance before milestones.";

    $weakestPillars = collect($results['pillar_scores'])->sortBy('score')->take(3);
    $actionMapping = [
        'Governance' => 'Establish a centralized PMO to enforce reporting protocols.',
        'Financial' => 'Conduct forensic review of budget and resource efficiency.',
        'Planning' => 'Re-baseline schedule using realistic resource velocity.',
        'Delivery' => 'Deploy audit team to verify standards and QA coverage.',
        'Resource' => 'Initiate capability assessment to identify skill gaps.',
        'Data' => 'Implement rigorous data quality framework and validation.',
        'Change' => 'Develop engagement plan and training adoption roadmap.',
        'Solution' => 'Re-verify solution fit with core business stakeholders.',
        'Default' => 'Engage leadership to define recovery roadmap.'
    ];
@endphp
<section class="min-h-screen bg-slate-50 py-12 md:py-20" x-data="{ submitting: false }">
    {{-- Full-Page Loading Overlay --}}
    <div x-show="submitting" 
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         class="fixed inset-0 z-[9999] bg-slate-900/90 backdrop-blur-xl flex flex-col items-center justify-center text-center p-6" 
         x-cloak>
        <div class="relative w-24 h-24 mb-8">
            <div class="absolute inset-0 border-4 border-blue-500/20 rounded-full"></div>
            <div class="absolute inset-0 border-4 border-blue-500 border-t-transparent rounded-full animate-spin"></div>
            <div class="absolute inset-4 bg-gradient-to-br from-blue-600 to-indigo-700 rounded-2xl flex items-center justify-center shadow-lg">
                <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>
            </div>
        </div>
        <h2 class="text-3xl font-black text-white tracking-tight mb-3">Compiling Intelligence Report</h2>
        <p class="text-slate-400 font-bold uppercase tracking-[0.2em] text-[10px]">Generating high-fidelity PDF with AI insights...</p>
    </div>

    <!-- 1. INTERACTIVE DASHBOARD (Screen Only) -->
    <div class="screen-only container max-w-5xl mx-auto px-6">
        
        <!-- DOWNLOAD ACTION -->
        <div id="download-action" class="flex justify-end mb-6">
            <button @click="submitting = true; exportReport();" class="group flex items-center gap-3 bg-slate-900 text-white px-6 py-3 rounded-2xl font-black text-[11px] uppercase tracking-widest hover:bg-blue-600 transition-all duration-300 shadow-xl shadow-slate-200">
                <svg class="w-4 h-4 text-blue-400 group-hover:text-white transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                Download PDF Report
            </button>
        </div>

        <!-- 2. SUMMARY DISCLAIMER -->
        <div class="bg-white rounded-[2.5rem] p-10 mb-12 border border-slate-200 shadow-xl shadow-slate-200/50">
            <div class="flex flex-col md:flex-row items-center gap-12">
                <div class="flex flex-col items-center">
                    <div class="relative w-56 h-56 flex items-center justify-center mb-4">
                        <canvas id="screenScoreGauge"></canvas>
                        <div class="absolute inset-0 flex flex-col items-center justify-center">
                            <span class="text-6xl font-black text-slate-900 tracking-tighter">{{ number_format($results['overall_score'], 1) }}</span>
                            <span class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Score / 5.0</span>
                        </div>
                    </div>
                    <!-- DISCLAIMER 3.3 -->
                    <p class="max-w-xs text-[10px] text-slate-400 font-medium leading-relaxed text-center">
                        This diagnostic is based on self-reported responses and does not constitute a formal audit, independent review, regulatory assessment, or compliance certification. Scores reflect the information provided at the time of completion. Results may not reflect conditions that have changed since completion. RAB Consulting Services Ltd accepts no liability for decisions made solely on the basis of these results. A consultant-led engagement provides independently verified findings.
                    </p>
                </div>
                
                <div class="flex-1 text-center md:text-left">
                    <div class="flex items-center justify-center md:justify-start gap-4 mb-6">
                        <span class="px-6 py-2 rounded-full text-[10px] font-black uppercase tracking-widest @if($results['rag_status'] === 'Red') bg-red-100 text-red-600 @elseif($results['rag_status'] === 'Amber') bg-amber-100 text-amber-500 @else bg-green-100 text-green-600 @endif">
                             {{ $results['rag_status'] }} Status
                        </span>
                    </div>
                    <h2 class="text-3xl md:text-4xl font-black text-slate-900 tracking-tighter mb-6 italic leading-tight">
                        "Your {{ $results['type'] === 'itsm' ? 'service' : 'programme' }} shows @if($results['overall_score'] < 2.5) critical delivery exposure @elseif($results['overall_score'] < 3.8) partial control with vulnerability @else strong delivery control @endif."
                    </h2>
                    <p class="text-lg text-slate-500 leading-relaxed max-w-2xl font-medium">
                        Based on RAB Consulting’s proprietary Intelligence methodology, your current trajectory indicates 
                        <span class="font-bold text-slate-800">@if($results['overall_score'] < 2.5) immediate intervention is required @elseif($results['overall_score'] < 3.8) targeted improvement is needed @else your delivery controls are robust @endif</span>.
                    </p>
                </div>
            </div>
        </div>

        <!-- 3. AI SUMMARY & INDICES -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-12 mb-20">
            <div class="lg:col-span-2">
                <h2 class="text-[11px] font-black text-blue-600 uppercase tracking-[0.3em] mb-8">Executive Intelligence Summary</h2>
                <div class="space-y-8 text-xl text-slate-700 leading-relaxed font-medium">
                    <p>{{ $programmeState }}</p>
                    <p>{{ $directionalFocus }}</p>
                </div>
            </div>
            <div class="bg-blue-900 rounded-[3rem] p-10 text-white shadow-2xl shadow-blue-900/30">
                <h3 class="text-[11px] font-black text-blue-300 uppercase tracking-[0.2em] mb-8 text-center">Indices Snapshot</h3>
                <div class="space-y-8">
                    @foreach($results['index_scores'] as $name => $score)
                        <div class="border-b border-white/10 pb-6 last:border-0">
                            <div class="flex justify-between items-end mb-2">
                                <span class="text-[10px] font-black text-blue-200 uppercase tracking-widest">{{ $name === 'CHI' ? 'Compliance Health Index' : $name }}</span>
                                <span class="text-[9px] font-bold text-blue-300 italic">
                                    @if($name === 'CHI') Data Pending @else @if($score >= 4.0) High @elseif($score >= 3.0) Medium @else Low @endif @endif
                                </span>
                            </div>
                            <span class="text-4xl font-black">{{ $name === 'CHI' ? '—' : number_format($score, 1) }}</span>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        <!-- 4. VISUAL ANALYTICS -->
        <div class="mb-20">
            <h2 class="text-[11px] font-black text-slate-400 uppercase tracking-[0.3em] mb-12 text-center">Intelligence Visualisations</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-12 mb-12">
                <div class="bg-white rounded-[3rem] p-10 border border-slate-100 shadow-xl shadow-slate-200/40">
                    <h3 class="text-[11px] font-black text-slate-400 uppercase tracking-widest mb-10 text-center">Pillar Distribution</h3>
                    <div class="aspect-square max-w-sm mx-auto">
                        <canvas id="screenSpiderChart"></canvas>
                    </div>
                </div>
                <div class="bg-white rounded-[3rem] p-10 border border-slate-100 shadow-xl shadow-slate-200/40">
                    <h3 class="text-[11px] font-black text-slate-400 uppercase tracking-widest mb-10 text-center">Domain Performance</h3>
                    <div class="aspect-video">
                        <canvas id="screenBarChart"></canvas>
                    </div>
                </div>
            </div>
            
            <div class="bg-white rounded-[3rem] p-12 border border-slate-100 shadow-xl shadow-slate-200/40">
                <h3 class="text-[11px] font-black text-slate-400 uppercase tracking-widest mb-12 text-center">Intelligence Heat Map</h3>
                <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6">
                    @foreach($results['pillar_scores'] as $code => $pillar)
                        <div class="p-8 rounded-[2rem] flex flex-col items-center justify-center text-center transition-all hover:scale-105 hover:shadow-lg @if($pillar['score'] < 2.5) bg-red-50 text-red-700 border border-red-100 @elseif($pillar['score'] < 3.8) bg-amber-50 text-amber-700 border border-amber-100 @else bg-green-50 text-green-700 border border-green-100 @endif">
                            <span class="text-[10px] font-black uppercase tracking-widest mb-4 opacity-70">{{ $pillar['name'] }}</span>
                            <span class="text-4xl font-black leading-none">{{ number_format($pillar['score'], 1) }}</span>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        <!-- 5. PRIORITY INSIGHTS -->
        <div class="mb-20">
            <h2 class="text-[11px] font-black text-red-600 uppercase tracking-[0.3em] mb-12">Priority Insights & Actions</h2>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-10">
                @foreach($weakestPillars as $code => $pillar)
                    <div class="bg-white rounded-[2.5rem] p-10 border border-red-100 shadow-xl shadow-red-500/5 relative overflow-hidden group hover:-translate-y-2 transition-all duration-500">
                        <div class="absolute top-0 right-0 p-6">
                             <span class="text-[12px] font-black text-red-600 uppercase tracking-widest opacity-30">{{ $code }}</span>
                        </div>
                        <h4 class="text-2xl font-black text-slate-900 mb-6 group-hover:text-red-600 transition-colors">{{ $pillar['name'] }}</h4>
                        <div class="mb-8">
                            <span class="text-[10px] font-black text-slate-400 uppercase tracking-widest block mb-3">Critical Insight</span>
                            <p class="text-lg text-slate-600 leading-relaxed italic">
                                Your score of {{ number_format($pillar['score'], 1) }} indicates that the {{ strtolower($pillar['name']) }} controls are @if($pillar['score'] < 2.5) failing to protect the programme @else performing below threshold @endif.
                            </p>
                        </div>
                        <div class="pt-8 border-t border-slate-100">
                            <span class="text-[10px] font-black text-blue-600 uppercase tracking-widest block mb-3">Recommended Action</span>
                            <p class="text-lg font-bold text-slate-800 leading-relaxed">
                                {{ $actionMapping[$pillar['name']] ?? $actionMapping['Default'] }}
                            </p>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        <!-- 6. NEXT STEP (CTA) -->
        <div id="cta-section" class="bg-blue-900 rounded-[4rem] p-16 md:p-24 text-center shadow-2xl shadow-blue-900/40 relative overflow-hidden group mb-20">
            <div class="absolute inset-0 bg-[radial-gradient(circle_at_top_right,_var(--tw-gradient-stops))] from-blue-500/20 via-transparent to-transparent"></div>
            <h2 class="text-[12px] font-black text-blue-300 uppercase tracking-[0.5em] mb-8 relative">Unlock Full Intelligence</h2>
            <p class="text-3xl md:text-5xl font-black text-white leading-tight max-w-4xl mx-auto mb-12 relative">
                Gain deep-dive root cause analysis and a structured recovery plan.
            </p>
            <div class="flex justify-center relative">
                <a href="{{ route('booking.index', ['name' => $results['user']['name'] ?? '', 'email' => $results['user']['email'] ?? '', 'booking_token' => $results['booking_token'] ?? '']) }}" 
                   class="bg-white text-blue-900 px-12 py-6 rounded-full text-lg font-black uppercase tracking-tighter hover:scale-105 transition shadow-2xl active:scale-95">
                    Book a Full Consultant-Led Review
                </a>
            </div>
        </div>
    </div>

    <!-- 2. PDF REPORT CONTAINER (Hidden from Screen, used for Export) -->
    <div style="position: absolute; left: -9999px; top: 0; width: 210mm;">
        <!-- PAGE 1 -->
        <div id="report-page-1" class="w-[210mm] h-[297mm] bg-white p-10 font-sans flex flex-col relative overflow-hidden">
            <div class="flex-1">
                <!-- COMPACT TOP STRIP -->
                <header class="flex items-center justify-between border-b-2 border-slate-900 pb-4 mb-6">
                    <div class="flex items-center gap-6">
                        <img src="/assets/images/logo-rab.png" alt="RAB" class="h-10">
                        <div class="h-10 w-px bg-slate-200"></div>
                        <div>
                            <h1 class="text-2xl font-black text-slate-900 uppercase tracking-tighter leading-none mb-1">Intelligence Snapshot</h1>
                            <div class="flex items-center gap-2">
                                 <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">{{ $results['user']['company'] ?? 'Organisation' }}</p>
                                 <span class="text-slate-300">•</span>
                                 <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">{{ now()->format('d M Y') }}</p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="flex items-center gap-6">
                        <div class="flex items-center gap-4">
                            <div class="relative w-14 h-14">
                                <canvas id="pdfScoreGauge"></canvas>
                                <div class="absolute inset-0 flex items-center justify-center">
                                    <span class="text-xl font-black text-slate-900">{{ number_format($results['overall_score'], 1) }}</span>
                                </div>
                            </div>
                            <div class="flex flex-col justify-center">
                                <div class="flex items-center gap-2 mb-1">
                                    <span class="text-[14px] font-black text-slate-900">{{ number_format($results['overall_score'], 1) }} / 5.0</span>
                                    <span class="px-2 py-0.5 rounded-full text-[9px] font-black uppercase tracking-widest @if($results['rag_status'] === 'Red') bg-red-600 text-white @elseif($results['rag_status'] === 'Amber') bg-amber-500 text-white @else bg-green-600 text-white @endif">
                                        {{ strtoupper($results['rag_status']) }} STATUS
                                    </span>
                                </div>
                                <span class="text-[9px] font-black text-slate-400 uppercase tracking-[0.1em]">
                                    @if($results['rag_status'] === 'Red') Critical Intervention Required @elseif($results['rag_status'] === 'Amber') Targeted Improvement Needed @else Continuous Optimization @endif
                                </span>
                            </div>
                        </div>
                    </div>
                </header>

                <!-- ROW 1: AI SUMMARY (80%) | INDICES (20%) -->
                <div class="grid grid-cols-10 gap-6 mb-6">
                    <div class="col-span-8 bg-slate-50 rounded-3xl p-6 border border-slate-200">
                        <h2 class="text-[10px] font-black text-blue-600 uppercase tracking-widest mb-4">AI Intelligence Summary</h2>
                        <div class="space-y-3 text-[11px] text-slate-700 leading-relaxed">
                            <p>{{ $programmeState }}</p>
                            <p>{{ $directionalFocus }}</p>
                        </div>
                    </div>
                    
                    <div class="col-span-2 bg-white rounded-3xl p-6 border border-slate-100 shadow-sm">
                        <h2 class="text-[9px] font-black text-slate-400 uppercase tracking-widest mb-4 border-b border-slate-100 pb-2">Indices</h2>
                        <div class="space-y-4">
                            @foreach($results['index_scores'] as $name => $score)
                                <div class="last:border-0">
                                    <span class="block text-[9px] font-black text-slate-500 uppercase tracking-widest mb-1">{{ $name }}</span>
                                    <div class="flex justify-between items-baseline">
                                        <span class="text-xl font-black text-slate-900 leading-none">{{ $name === 'CHI' ? '—' : number_format($score, 1) }}</span>
                                        <span class="text-[7px] font-black text-blue-600 italic uppercase">
                                            @if($name === 'CHI') PENDING @else @if($score >= 4.0) HIGH @elseif($score >= 3.0) MED @else LOW @endif @endif
                                        </span>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>

                <!-- ROW 2: VISUALS -->
                <div class="grid grid-cols-3 gap-6 mb-6">
                    <div class="bg-white rounded-3xl p-6 border border-slate-100 shadow-sm flex flex-col items-center">
                        <h3 class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-4">Pillar Distribution</h3>
                        <div class="w-full aspect-square">
                            <canvas id="pdfSpiderChart"></canvas>
                        </div>
                    </div>
                    <div class="bg-white rounded-3xl p-6 border border-slate-100 shadow-sm flex flex-col items-center">
                        <h3 class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-4">Domain Performance</h3>
                        <div class="w-full aspect-square">
                            <canvas id="pdfBarChart"></canvas>
                        </div>
                    </div>
                    <div class="bg-white rounded-3xl p-6 border border-slate-100 shadow-sm flex flex-col">
                        <h3 class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-4 text-center">Heat Map</h3>
                        <div class="grid grid-cols-4 gap-2 flex-1 items-center">
                            @foreach($results['pillar_scores'] as $code => $pillar)
                                <div class="p-2 rounded-xl text-center border @if($pillar['score'] < 2.5) bg-red-50 text-red-700 border-red-100 @elseif($pillar['score'] < 3.8) bg-amber-50 text-amber-700 border-amber-100 @else bg-green-50 text-green-700 border-green-100 @endif">
                                    <span class="block text-[8px] font-black uppercase tracking-widest leading-tight mb-1">{{ $code }}</span>
                                    <span class="text-[11px] font-black leading-none">{{ number_format($pillar['score'], 1) }}</span>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>

            <!-- FOOTER 3.1 -->
            <div class="mt-auto pt-4 border-t border-slate-100 text-center">
                <p class="text-[7px] text-slate-400 leading-tight mb-1">
                    This report is produced by RAB Consulting Services Ltd. All findings are based on information provided during the diagnostic or consultant-led engagement. This report constitutes operational intelligence and professional advisory guidance only. It does not constitute legal, regulatory, financial, or compliance advice. Clients should engage their own legal, compliance, and regulatory advisers to confirm any regulatory position. RAB Consulting Services Ltd accepts no liability for decisions made solely on the basis of this report without independent legal or professional verification.
                </p>
                <p class="text-[7px] text-slate-400 font-bold uppercase tracking-widest mb-1">
                    ©2026 RAB Consulting Services Ltd. All methodology, indices, scoring frameworks, and report content are proprietary intellectual property. Reproduction or distribution without written consent is prohibited.
                </p>
                <p class="text-[7px] font-black text-slate-500 uppercase tracking-widest">
                    RAB Consulting Services Ltd | rboukhiar@rabconsultingservices.com | +447717540322 | rabconsulting.uk
                </p>
            </div>
        </div>

        <!-- PAGE 2 -->
        <div id="report-page-2" class="w-[210mm] h-[297mm] bg-white p-10 font-sans flex flex-col relative overflow-hidden">
            <div class="flex-1">
                <header class="flex items-center justify-between border-b border-slate-200 pb-4 mb-6">
                    <div class="flex items-center gap-4">
                        <img src="/assets/images/logo-rab.png" alt="RAB" class="h-8">
                        <div class="h-8 w-px bg-slate-200"></div>
                        <span class="text-sm font-black text-slate-900 uppercase tracking-tighter">Detailed Intelligence & Actions</span>
                    </div>
                    <span class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">{{ $results['user']['company'] ?? 'Organisation' }}</span>
                </header>

                <h2 class="text-[11px] font-black text-red-600 uppercase tracking-[0.3em] mb-6">Priority Insights & Actions</h2>
                
                @if(isset($results['regulatory_context']) && $results['regulatory_context'])
                    <!-- DISCLAIMER 3.2 — Regulatory Risk Exposure Section -->
                    <div class="mb-8 bg-blue-50/50 rounded-3xl p-6 border border-blue-100">
                        <h3 class="text-[10px] font-black text-blue-800 uppercase tracking-widest mb-3">Regulatory Risk Exposure</h3>
                        <div class="text-[10px] text-blue-900 leading-relaxed mb-4">
                            {{ $results['regulatory_context'] }}
                        </div>
                        <div class="pt-4 border-t border-blue-200">
                            <p class="text-[8px] font-bold text-blue-900 italic leading-relaxed">
                                "The regulatory risk exposure analysis in this section provides an operational intelligence interpretation of how this organisation's current service or programme health maps to known regulatory requirements. It is not legal advice. It is not a regulatory compliance certification or assurance. The organisation's legal, compliance, and regulatory affairs teams must be engaged to confirm the actual regulatory position before any regulatory submission or board declaration. RAB Consulting Services Ltd is not a regulated professional adviser under the FCA, SRA, or any other professional regulatory framework. Rules referenced are accurate as of the date of this report and are subject to change."
                            </p>
                        </div>
                    </div>
                @endif
                
                <!-- DETAILED INSIGHTS -->
                <div class="space-y-4">
                    @foreach($weakestPillars as $code => $pillar)
                        <div class="bg-white rounded-3xl p-6 border border-red-100 relative overflow-hidden">
                            <div class="absolute top-0 right-0 p-4 opacity-10">
                                 <span class="text-5xl font-black uppercase text-red-600">{{ $code }}</span>
                            </div>
                            <div class="relative z-10">
                                <div class="flex items-center gap-4 mb-3">
                                    <h4 class="text-lg font-black text-slate-900 uppercase tracking-tight">{{ $pillar['name'] }} Exposure</h4>
                                    <span class="px-3 py-1 rounded-full bg-red-50 text-red-600 text-[10px] font-black">SCORE: {{ number_format($pillar['score'], 1) }}</span>
                                </div>
                                
                                <div class="grid grid-cols-2 gap-6">
                                    <div>
                                        <span class="text-[9px] font-black text-slate-400 uppercase tracking-widest block mb-1">Critical Exposure Analysis</span>
                                        <p class="text-[11px] text-slate-700 leading-relaxed italic">
                                            Your score of {{ number_format($pillar['score'], 1) }} indicates that the {{ strtolower($pillar['name']) }} controls are @if($pillar['score'] < 2.5) failing to protect the programme @else performing below the minimum stability threshold @endif.
                                        </p>
                                    </div>
                                    <div>
                                        <span class="text-[9px] font-black text-blue-600 uppercase tracking-widest block mb-1">Recommended Recovery Action</span>
                                        <p class="text-[12px] font-bold text-slate-900 leading-relaxed">
                                            {{ $actionMapping[$pillar['name']] ?? $actionMapping['Default'] }}
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>

                <!-- ADDITIONAL NOTES/DISCLAIMER -->
                <div class="mt-6 bg-slate-50 rounded-2xl p-6 border border-slate-200">
                    <h3 class="text-[9px] font-black text-slate-900 uppercase tracking-widest mb-3">Intelligence Methodology Disclaimer</h3>
                    <p class="text-[8px] text-slate-500 leading-relaxed">
                        This Intelligence Snapshot is generated based on self-reported data and proprietary RAB Consulting assessment frameworks. It provides a strategic-level view of programme/service health and is intended to highlight areas requiring deeper investigation. For a definitive root-cause analysis and structured recovery plan, a full consultant-led review is recommended.
                    </p>
                </div>
            </div>

            <!-- FOOTER 3.1 -->
            <div class="mt-auto pt-4 border-t border-slate-100 text-center">
                <p class="text-[7px] text-slate-400 leading-tight mb-1">
                    This report is produced by RAB Consulting Services Ltd. All findings are based on information provided during the diagnostic or consultant-led engagement. This report constitutes operational intelligence and professional advisory guidance only. It does not constitute legal, regulatory, financial, or compliance advice. Clients should engage their own legal, compliance, and regulatory advisers to confirm any regulatory position. RAB Consulting Services Ltd accepts no liability for decisions made solely on the basis of this report without independent legal or professional verification.
                </p>
                <p class="text-[7px] text-slate-400 font-bold uppercase tracking-widest mb-1">
                    ©2026 RAB Consulting Services Ltd. All methodology, indices, scoring frameworks, and report content are proprietary intellectual property. Reproduction or distribution without written consent is prohibited.
                </p>
                <p class="text-[7px] font-black text-slate-500 uppercase tracking-widest">
                    RAB Consulting Services Ltd | rboukhiar@rabconsultingservices.com | +447717540322 | rboukhiar@rabconsultingservices.com
                </p>
            </div>
        </div>
    </div>
</section>

@endsection

@push('scripts')
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script>
async function exportReport() {
    const { jsPDF } = window.jspdf;
    const page1 = document.getElementById('report-page-1');
    const page2 = document.getElementById('report-page-2');
    const action = document.getElementById('download-action');

    try {
        // 1. Prepare UI
        if(action) action.classList.add('hidden');
        
        // Wait for any animations to settle
        await new Promise(resolve => setTimeout(resolve, 1000));

        const pdf = new jsPDF({
            orientation: 'p',
            unit: 'mm',
            format: 'a4',
            compress: true
        });

        // 2. Capture Page 1
        const canvas1 = await html2canvas(page1, {
            scale: 2,
            useCORS: true,
            logging: false,
            backgroundColor: '#ffffff',
            windowWidth: 1000
        });
        const imgData1 = canvas1.toDataURL('image/png', 1.0);
        pdf.addImage(imgData1, 'PNG', 0, 0, 210, 297, undefined, 'FAST');

        // 3. Capture Page 2
        pdf.addPage();
        const canvas2 = await html2canvas(page2, {
            scale: 2,
            useCORS: true,
            logging: false,
            backgroundColor: '#ffffff',
            windowWidth: 1000
        });
        const imgData2 = canvas2.toDataURL('image/png', 1.0);
        pdf.addImage(imgData2, 'PNG', 0, 0, 210, 297, undefined, 'FAST');

        // 4. Save
        pdf.save(`RAB-Intelligence-Report-${new Date().getTime()}.pdf`);

    } catch (e) {
        console.error('PDF Export Error:', e);
        alert("Failed to generate PDF. Please try again.");
    } finally {
        // 5. Restore UI
        if(action) action.classList.remove('hidden');
        
        const section = document.querySelector('section');
        if (section) {
            const data = Alpine.closestDataStack(section)[0];
            if(data) data.submitting = false;
        }
    }
}

document.addEventListener('DOMContentLoaded', function() {
    const pillarData = @json(collect($results['pillar_scores'])->values());
    const labels = pillarData.map(p => p.name);
    const scores = pillarData.map(p => p.score);
    const colors = pillarData.map(p => p.rag === 'Red' ? '#ef4444' : (p.rag === 'Amber' ? '#f59e0b' : '#10b981'));

    // --- SCREEN CHARTS ---
    new Chart(document.getElementById('screenScoreGauge'), {
        type: 'doughnut',
        data: {
            datasets: [{
                data: [{{ $results['overall_score'] }}, {{ 5 - $results['overall_score'] }}],
                backgroundColor: ['{{ $results['rag_status'] === 'Red' ? '#ef4444' : ($results['rag_status'] === 'Amber' ? '#f59e0b' : '#10b981') }}', '#f1f5f9'],
                borderWidth: 0,
                circumference: 270,
                rotation: 225,
                cutout: '85%',
                borderRadius: 20
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false }, tooltip: { enabled: false } }
        }
    });

    new Chart(document.getElementById('screenSpiderChart'), {
        type: 'radar',
        data: {
            labels: labels,
            datasets: [{
                label: 'Score',
                data: scores,
                backgroundColor: 'rgba(30, 58, 138, 0.1)',
                borderColor: '#1e3a8a',
                borderWidth: 3,
                pointBackgroundColor: '#1e3a8a'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                r: { min: 0, max: 5, ticks: { display: false }, pointLabels: { font: { size: 10, weight: '700' } } }
            },
            plugins: { legend: { display: false } }
        }
    });

    new Chart(document.getElementById('screenBarChart'), {
        type: 'bar',
        data: {
            labels: labels,
            datasets: [{ data: scores, backgroundColor: colors, borderRadius: 8 }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: { min: 0, max: 5, grid: { display: false } },
                x: { grid: { display: false }, ticks: { display: false } }
            },
            plugins: { legend: { display: false } }
        }
    });

    // --- PDF CHARTS (Compact) ---
    new Chart(document.getElementById('pdfScoreGauge'), {
        type: 'doughnut',
        data: {
            datasets: [{
                data: [{{ $results['overall_score'] }}, {{ 5 - $results['overall_score'] }}],
                backgroundColor: ['{{ $results['rag_status'] === 'Red' ? '#ef4444' : ($results['rag_status'] === 'Amber' ? '#f59e0b' : '#10b981') }}', '#f1f5f9'],
                borderWidth: 0,
                circumference: 270,
                rotation: 225,
                cutout: '80%',
                borderRadius: 2
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false }, tooltip: { enabled: false } }
        }
    });

    new Chart(document.getElementById('pdfSpiderChart'), {
        type: 'radar',
        data: {
            labels: labels,
            datasets: [{
                data: scores,
                backgroundColor: 'rgba(30, 58, 138, 0.05)',
                borderColor: '#1e3a8a',
                borderWidth: 2,
                pointRadius: 2
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                r: { min: 0, max: 5, ticks: { display: false }, pointLabels: { font: { size: 6 } } }
            },
            plugins: { legend: { display: false } }
        }
    });

    new Chart(document.getElementById('pdfBarChart'), {
        type: 'bar',
        data: {
            labels: labels,
            datasets: [{ data: scores, backgroundColor: colors, borderRadius: 4 }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: { min: 0, max: 5, ticks: { font: { size: 8 } } },
                x: { ticks: { display: false } }
            },
            plugins: { legend: { display: false } }
        }
    });
});
</script>
@endpush
