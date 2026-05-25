@extends('admin.layouts.app')

@section('header')
    <div class="flex items-center gap-4">
        <a href="{{ route('admin.assessments.index') }}" class="text-gray-500 hover:text-gray-700">&larr; Back</a>
        <span>Assessment: {{ $assessment->name }}</span>
    </div>
@endsection

@section('content')
@php
    $reportConfig = $assessment->reportConfig();
    $isPublicSnapshotAssessment = (bool) ($assessment->is_public_lead ?? false);
    $snapshotLead = $isPublicSnapshotAssessment ? ($lead ?? null) : null;
    $snapshotReport = is_array($snapshotLead?->snapshot_report_json ?? null)
        ? $snapshotLead->snapshot_report_json
        : null;
    $briefParagraphs = collect(preg_split('/\n\s*\n/', trim((string) ($snapshotReport['intelligence_brief'] ?? '')), -1, PREG_SPLIT_NO_EMPTY))
        ->map(fn ($paragraph) => trim($paragraph))
        ->filter()
        ->values();
    $insightCards = collect($snapshotReport['insight_cards'] ?? [])->take(3)->values();
    $structuredReportDetector = app(\App\Services\StructuredReportDetector::class);
    $fullReportDraft = $structuredReportDetector->hasReportContent($assessment->ai_draft_json)
        ? $assessment->ai_draft_json
        : null;
    $hasStructuredDraft = $fullReportDraft !== null;
    $needsStructuredDraft = !$hasStructuredDraft;
    $hasConsultantNotes = $assessment->pillarScores->contains(function ($pillar) {
        return filled($pillar->commentary) || filled($pillar->key_risks) || filled($pillar->immediate_actions);
    });
@endphp
<!-- Header Summary -->
<div id="pdf-header" class="bg-white shadow rounded-lg mb-6 p-6">
    <div class="flex flex-col md:flex-row justify-between items-start gap-6">
        <div class="w-full md:w-auto">
            <h2 class="text-2xl font-bold text-gray-900">{{ $assessment->client->company_name ?? $assessment->client_name }}</h2>
            <p class="text-gray-500 mt-1">{{ $assessment->type }} Assessment &middot; {{ $assessment->created_at->format('M d, Y') }}</p>
            <div class="flex flex-wrap gap-2 mt-2">
                @if(isset($assessment->is_public_lead))
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-[10px] font-black bg-amber-100 text-amber-700 border border-amber-200 uppercase tracking-widest">
                        Public Website Health-Check
                    </span>
                @endif
                @if($assessment->critical_flag)
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                        <svg class="mr-1.5 h-2 w-2 text-red-400" fill="currentColor" viewBox="0 0 8 8"><circle cx="4" cy="4" r="3" /></svg>
                        CRITICAL FLAG TRIGGERED
                    </span>
                @endif
            </div>
        </div>
        <div class="w-full md:w-auto flex flex-col items-start md:items-end">
            <span class="text-sm text-gray-500 block mb-1">Overall Score</span>
            <span class="px-3 py-1 inline-flex text-lg font-bold rounded-full 
                @if($assessment->rag_status === 'Green') bg-green-100 text-green-800 
                @elseif($assessment->rag_status === 'Amber') bg-amber-100 text-amber-800 
                @else bg-red-100 text-red-800 @endif">
                {{ number_format($assessment->overall_score, 1) }} ({{ $assessment->rag_status }})
            </span>
            <div class="mt-4 flex flex-wrap gap-3">
                @if($snapshotLead)
                    <form action="{{ route('admin.assessments.regenerateSnapshotAi', $assessment->id) }}" method="POST" class="flex-1 sm:flex-none" x-data="{ loading: false }" @submit="loading = true">
                        @csrf
                        <button type="submit" :disabled="loading" class="w-full bg-blue-600 hover:bg-blue-700 disabled:bg-blue-400 text-white px-4 py-2.5 rounded-lg shadow-lg text-sm font-bold flex items-center justify-center gap-2 transition-all active:scale-95">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>
                            <span x-show="!loading">Regenerate AI Insights</span>
                            <span x-show="loading" x-cloak>Regenerating...</span>
                        </button>
                    </form>
                    <a href="{{ route('rapid-consulting.snapshot-report.pdf', ['lead' => $snapshotLead->id, 'token' => $snapshotLead->booking_token]) }}"
                       class="flex-1 sm:flex-none bg-slate-800 hover:bg-slate-900 text-white px-4 py-2.5 rounded-lg shadow-lg text-sm flex items-center justify-center gap-2 transition-all active:scale-95">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                        <span>Download PDF Report</span>
                    </a>
                @else
                    <form action="{{ route('admin.assessments.exportPdf', $assessment) }}" method="POST" class="flex-1 sm:flex-none">
                        @csrf
                        <button type="submit" class="w-full bg-slate-800 hover:bg-slate-900 text-white px-4 py-2.5 rounded-lg shadow-lg text-sm flex items-center justify-center gap-2 transition-all active:scale-95">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                            <span>Export Report</span>
                        </button>
                    </form>
                    @if($needsStructuredDraft)
                        <form action="{{ route('admin.assessments.generateReport', $assessment) }}" method="POST" class="flex-1 sm:flex-none">
                            @csrf
                            <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white px-4 py-2.5 rounded-lg shadow-lg text-sm font-bold flex items-center justify-center gap-2 transition-all active:scale-95">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>
                                <span>{{ $assessment->status === 'completed' ? 'Rebuild AI Insights' : 'Generate AI Insights' }}</span>
                            </button>
                        </form>
                    @endif
                @endif
                @if(! $snapshotLead)
                    @if($assessment->status !== 'approved')
                        <a href="{{ in_array($assessment->type, ['PHI', 'PIR']) ? route('admin.assessments.score.phi', $assessment->id) : route('admin.assessments.score.itsm', $assessment->id) }}" 
                           class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2.5 rounded-lg shadow-lg text-sm font-bold flex items-center justify-center gap-2 transition-all active:scale-95 flex-1 sm:flex-none">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                            <span>Continue Scoring</span>
                        </a>
                    @endif

                    @if($assessment->status === 'draft')
                        <form action="{{ route('admin.assessments.updateStatus', $assessment) }}" method="POST" class="flex-1 sm:flex-none">
                            @csrf @method('PUT')
                            <input type="hidden" name="status" value="in_progress">
                            <button type="submit" class="w-full bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold px-4 py-2.5 rounded shadow text-sm border border-slate-200">Mark In Review</button>
                        </form>
                    @elseif($assessment->status === 'in_progress')
                        <form action="{{ route('admin.assessments.updateStatus', $assessment) }}" method="POST" class="flex-1 sm:flex-none">
                            @csrf @method('PUT')
                            <input type="hidden" name="status" value="approved">
                            <button type="submit" class="w-full bg-green-600 hover:bg-green-700 text-white font-bold px-4 py-2.5 rounded shadow text-sm">Approve Report</button>
                        </form>
                    @else
                        <span class="bg-gray-100 text-gray-800 px-4 py-2.5 rounded font-medium border border-gray-200 flex items-center justify-center">Approved</span>
                    @endif
                @endif
            </div>
        </div>
    </div>
</div>

@if($assessment->ai_generation_status && $assessment->ai_generation_status !== 'idle')
    <div class="bg-white shadow rounded-lg mb-6 p-4 border-l-4 {{ $assessment->ai_generation_status === 'failed' ? 'border-red-500' : ($assessment->ai_generation_status === 'completed' ? 'border-green-500' : 'border-blue-500') }}">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-2">
            <div>
                <p class="text-xs font-black text-slate-400 uppercase tracking-widest">AI Generation Status</p>
                <p class="text-sm font-bold text-slate-800">{{ ucfirst($assessment->ai_generation_status) }}</p>
            </div>
            @if($assessment->ai_generation_started_at || $assessment->ai_generation_completed_at)
                <p class="text-xs text-slate-500">
                    @if($assessment->ai_generation_started_at)
                        Started {{ $assessment->ai_generation_started_at->format('d M Y H:i') }}
                    @endif
                    @if($assessment->ai_generation_completed_at)
                        &middot; Completed {{ $assessment->ai_generation_completed_at->format('d M Y H:i') }}
                    @endif
                </p>
            @endif
        </div>
        @if($assessment->ai_generation_error)
            <p class="mt-3 text-sm text-red-700 bg-red-50 border border-red-100 rounded p-3">{{ $assessment->ai_generation_error }}</p>
        @endif
    </div>
@endif

<section class="mt-8">
    <div class="mb-6">
        <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Source assessment data</p>
        <h2 class="text-2xl font-black text-slate-900 tracking-tight">Assessment Scores &amp; Evidence</h2>
    </div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
    <!-- Left Column: Charts -->
    <div class="lg:col-span-1 space-y-6">
        <div id="pdf-radar" class="bg-white shadow rounded-lg p-6">
            <h3 class="text-sm font-black text-slate-400 uppercase tracking-widest mb-4">Pillar Distribution (Spider)</h3>
            <div id="radarChartContainer" class="relative h-[300px]">
                <canvas id="radarChart"></canvas>
            </div>
        </div>

        <div id="pdf-bar" class="bg-white shadow rounded-lg p-6">
            <h3 class="text-sm font-black text-slate-400 uppercase tracking-widest mb-4">Domain Performance (Bar)</h3>
            <div id="barChartContainer" class="relative h-[300px]">
                <canvas id="barChart"></canvas>
            </div>
        </div>

        <div id="pdf-health" class="bg-white shadow rounded-lg p-6">
            <h3 class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-4">Pillar Health Overview</h3>
            
            <div id="healthPieContainer" class="relative h-[220px] mb-6">
                <canvas id="healthPieChart"></canvas>
                <div class="absolute inset-0 flex flex-col items-center justify-center pointer-events-none">
                    <span class="text-[10px] font-black text-slate-400 uppercase">Health</span>
                    <span class="text-xl font-black text-slate-800">{{ $assessment->rag_status }}</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Index Scores -->
    <div id="pdf-indices" class="bg-white shadow rounded-lg p-6 lg:col-span-2">
        <h3 class="text-lg font-medium mb-4">Indices</h3>
        <div class="grid grid-cols-2 md:grid-cols-3 gap-6">
            @if(in_array($assessment->type, ['PHI', 'PIR']))
                <div class="border rounded-lg p-4 text-center">
                    <span class="block text-sm text-gray-500">Business Readiness Index (BRI)</span>
                    <span class="block mt-2 text-2xl font-bold text-slate-800">
                        {{ $assessment->bri !== null ? number_format($assessment->bri, 1) : 'N/A' }}
                    </span>
                </div>
                <div class="border rounded-lg p-4 text-center">
                    <span class="block text-sm text-gray-500">Value Realisation Index (VRI)</span>
                    <span class="block mt-2 text-2xl font-bold text-slate-800">
                        {{ $assessment->vri !== null ? number_format($assessment->vri, 1) : 'N/A' }}
                    </span>
                </div>
                <div class="border rounded-lg p-4 text-center">
                    <span class="block text-sm text-gray-500">Digital Maturity Index (DMI)</span>
                    <span class="block mt-2 text-2xl font-bold text-slate-800">
                        {{ $assessment->dmi !== null ? number_format($assessment->dmi, 1) : 'N/A' }}
                    </span>
                </div>
                @if($assessment->type === 'PIR')
                    <div class="border rounded-lg p-4 text-center">
                        <span class="block text-sm text-gray-500">Risk Intelligence Index (RII)</span>
                        <span class="block mt-2 text-2xl font-bold text-slate-800">
                            {{ $assessment->rii !== null ? number_format($assessment->rii, 1) : 'N/A' }}
                        </span>
                    </div>
                @endif
                <div class="border rounded-lg p-4 text-center">
                    <span class="block text-sm text-gray-500">Compliance Health (CHI)</span>
                    <span class="block mt-2 text-2xl font-bold text-slate-800">
                        {{ $assessment->chi !== null ? number_format($assessment->chi, 1) : 'N/A' }}
                    </span>
                </div>
            @endif

            @if(in_array($assessment->type, ['ITSM', 'SIR']))
                <div class="border rounded-lg p-4 text-center">
                    <span class="block text-sm text-gray-500">Service Stability Index (SSI)</span>
                    <span class="block mt-2 text-2xl font-bold text-slate-800">
                        {{ $assessment->ssi !== null ? number_format($assessment->ssi, 1) : 'N/A' }}
                    </span>
                </div>
                <div class="border rounded-lg p-4 text-center">
                    <span class="block text-sm text-gray-500">Service Maturity Index (SMI)</span>
                    <span class="block mt-2 text-2xl font-bold text-slate-800">
                        {{ $assessment->smi !== null ? number_format($assessment->smi, 1) : 'N/A' }}
                    </span>
                </div>
                <div class="border rounded-lg p-4 text-center">
                    <span class="block text-sm text-gray-500">Service Improvement (SIMI)</span>
                    <span class="block mt-2 text-2xl font-bold text-slate-800">
                        {{ $assessment->simi !== null ? number_format($assessment->simi, 1) : 'N/A' }}
                    </span>
                </div>
                <div class="border rounded-lg p-4 text-center">
                    <span class="block text-sm text-gray-500">BAU Readiness</span>
                    <span class="block mt-2 text-2xl font-bold text-slate-800">
                        {{ $assessment->bau_readiness !== null ? number_format($assessment->bau_readiness, 1) : 'N/A' }}
                    </span>
                </div>
                <div class="border rounded-lg p-4 text-center">
                    <span class="block text-sm text-gray-500">Compliance Health (CHI)</span>
                    <span class="block mt-2 text-2xl font-bold text-slate-800">
                        {{ $assessment->chi !== null ? number_format($assessment->chi, 1) : 'N/A' }}
                    </span>
                </div>
            @endif
        </div>

        <h3 class="text-lg font-medium mt-8 mb-4 border-t pt-6">Pillar Scores</h3>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500">Pillar</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500">Score</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500">Critical</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($assessment->pillarScores as $pillar)
                        <tr class="border-b border-gray-100">
                            <td class="px-4 py-3">{{ $pillar->name }}</td>
                            <td class="px-4 py-3">
                                <span class="px-2 py-1 text-xs rounded-full 
                                    @if($pillar->rag_status === 'Green') bg-green-100 text-green-700
                                    @elseif($pillar->rag_status === 'Amber') bg-amber-100 text-amber-700
                                    @else bg-red-100 text-red-700 @endif">
                                    {{ $pillar->score }}
                                </span>
                            </td>
                            <td class="px-4 py-3">
                                @if($pillar->critical_flag || $pillar->rag_status === 'Red')
                                    <span class="text-red-600 font-bold">YES</span>
                                @else
                                    <span class="text-gray-400">No</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>

@if($reportConfig['show_risk_matrix'] && ! $hasStructuredDraft)
    <!-- Risk Matrix Section -->
    <div id="pdf-risk-matrix" class="bg-white shadow rounded-lg mb-6 p-6">
        <h3 class="text-lg font-bold text-slate-800 mb-4">Risk Matrix (Top {{ $reportConfig['risk_matrix_top_n'] }} Critical Exposures)</h3>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            @php
                $risks = $assessment->top_5_risks;
                if(is_string($risks)) $risks = json_decode($risks, true);
            @endphp
            @if(!empty($risks))
                @foreach(array_slice($risks, 0, $reportConfig['risk_matrix_top_n']) as $risk)
                    <div class="p-4 bg-slate-900 text-white rounded-2xl flex gap-4">
                        <div class="w-8 h-8 bg-red-500 text-white rounded-lg flex items-center justify-center font-black shrink-0">{{ $loop->iteration }}</div>
                        <p class="text-sm font-medium leading-relaxed">{{ $risk }}</p>
                    </div>
                @endforeach
            @else
                <div class="col-span-2 p-8 bg-slate-50 border border-dashed border-slate-200 rounded-2xl text-center">
                    <p class="text-slate-400 italic">No top risks categorized for this tier yet.</p>
                </div>
            @endif
        </div>
    </div>
@endif

@if($reportConfig['show_intelligence_profile'] && ! $hasStructuredDraft)
    <!-- Intelligence Profile Section -->
    <div id="pdf-intelligence-profile" class="bg-white shadow rounded-lg mb-6 p-6">
        <h3 class="text-lg font-bold text-slate-800 mb-4">Intelligence Profile (Deep Dive)</h3>
        <div class="space-y-6">
            @foreach($assessment->pillarScores as $pillar)
                @if($pillar->score < $reportConfig['intelligence_profile_threshold'])
                    <div class="border border-slate-100 rounded-3xl p-6 bg-slate-50/30">
                        <div class="flex justify-between items-start mb-4">
                            <h4 class="text-md font-black text-slate-900 uppercase tracking-tight">{{ $pillar->name }}</h4>
                            <span class="px-3 py-1 rounded-full bg-red-50 text-red-600 text-[10px] font-black tracking-widest">THRESHOLD TRIGGERED: {{ $pillar->score }}</span>
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                            <div>
                                <span class="text-[9px] font-black text-slate-400 uppercase tracking-widest block mb-2">Exposure Analysis</span>
                                <p class="text-[12px] text-slate-700 leading-relaxed italic">{{ $pillar->commentary ?: 'Consultant-led analysis pending.' }}</p>
                            </div>
                            <div>
                                <span class="text-[9px] font-black text-red-600 uppercase tracking-widest block mb-2">Key Risks Identified</span>
                                <p class="text-[13px] font-bold text-slate-900 leading-relaxed">{{ $pillar->key_risks ?: 'None listed.' }}</p>
                            </div>
                        </div>
                    </div>
                @endif
            @endforeach
        </div>
    </div>
@endif

@php
    $regContext = $assessment->regulatory_context ?? $lead->regulatory_context ?? null;
    $showReg = false;
    if ($regContext) {
        if ($reportConfig['tier'] === 'Snapshot') {
            if ($assessment->overall_score < 3.5) $showReg = true;
        } else {
            $showReg = true;
        }
    }
@endphp

@if($showReg)
    <!-- Regulatory Risk Exposure Section -->
    <div id="pdf-regulatory-risk" class="bg-white shadow rounded-lg mb-6 p-6 border-2 border-red-500 bg-red-50/50">
        <h3 class="text-lg font-bold text-red-700 mb-2 flex items-center gap-2">
            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path></svg>
            Regulatory Risk Exposure
        </h3>
        <p class="text-sm text-red-800 font-bold mb-4">CRITICAL: The following regulatory context identifies potential non-compliance or exposure based on the current scoring profile.</p>
        <div class="p-4 bg-white rounded-xl border border-red-200 text-sm text-slate-700 leading-relaxed">
            {{ $regContext }}
        </div>
    </div>
@endif

@if($reportConfig['show_action_register'] && ! $hasStructuredDraft)
    <!-- Priority Action Register -->
    <div id="pdf-action-register" class="bg-white shadow rounded-lg mb-6 p-6">
        <h3 class="text-lg font-bold text-slate-800 mb-4">Priority Action Register</h3>
        <div class="space-y-3">
            @php
                $actions = [];
                foreach($assessment->pillarScores as $ps) {
                    if($ps->immediate_actions) $actions[] = $ps->immediate_actions;
                }
            @endphp
            @if(!empty($actions))
                @foreach(array_slice($actions, 0, $reportConfig['action_register_top_n']) as $action)
                    <div class="flex items-start gap-4 p-4 bg-blue-50 border border-blue-100 rounded-2xl">
                        <div class="w-2 h-2 bg-blue-600 rounded-full mt-2 shrink-0"></div>
                        <p class="text-sm font-bold text-blue-900 leading-relaxed">{{ $action }}</p>
                    </div>
                @endforeach
            @else
                 <div class="p-8 bg-slate-50 border border-dashed border-slate-200 rounded-2xl text-center">
                    <p class="text-slate-400 italic">No priority actions assigned yet.</p>
                </div>
            @endif
        </div>
    </div>
@endif

@if($reportConfig['show_final_position'] && ! $hasStructuredDraft)
    <!-- Final Position Statement -->
    <div id="pdf-final-position" class="bg-white shadow rounded-lg mb-6 p-6 border-l-8 border-slate-900">
        <h3 class="text-lg font-bold text-slate-800 mb-3">Final Position Statement</h3>
        <p class="text-md text-slate-700 leading-relaxed italic">
            {{ $assessment->overall_assessor_comment ?: 'Final executive statement pending completion of review.' }}
        </p>
    </div>
@endif

@if($reportConfig['show_appendix_full_data'])
    <!-- Appendix: Full Data (Tier 2 only) -->
    <div id="pdf-appendix" class="bg-white shadow rounded-lg mb-6 p-6 page-break-before">
        <h3 class="text-lg font-bold text-slate-800 mb-4">Appendix: Full Response Data & Evidence</h3>
        <p class="text-sm text-slate-500 mb-6 italic">This section provides the exhaustive data set used for the Tier 2 Full Intelligence Report, including respondent-level scores and consultant evidence notes.</p>
        <!-- The main question responses table will be moved here or kept as is but styled as appendix -->
    </div>
@endif

@if($hasConsultantNotes)
    <div id="consultant-notes" class="bg-white shadow rounded-lg mb-6 p-6">
        <h3 class="text-lg font-bold text-slate-800 mb-4">Consultant Notes</h3>
        <div class="space-y-4">
            @foreach($assessment->pillarScores as $pillar)
                @if(filled($pillar->commentary) || filled($pillar->key_risks) || filled($pillar->immediate_actions))
                    <div class="border border-slate-100 rounded-lg p-4">
                        <h4 class="text-sm font-black text-slate-900 mb-3">{{ $pillar->name }}</h4>
                        @if(filled($pillar->commentary))
                            <p class="text-sm text-slate-700 leading-6 mb-2"><strong>Commentary:</strong> {{ $pillar->commentary }}</p>
                        @endif
                        @if(filled($pillar->key_risks))
                            <p class="text-sm text-slate-700 leading-6 mb-2"><strong>Key risks:</strong> {{ $pillar->key_risks }}</p>
                        @endif
                        @if(filled($pillar->immediate_actions))
                            <p class="text-sm text-slate-700 leading-6"><strong>Immediate actions:</strong> {{ $pillar->immediate_actions }}</p>
                        @endif
                    </div>
                @endif
            @endforeach
        </div>
    </div>
@endif

<!-- Question Responses -->
<div class="@if($reportConfig['show_appendix_full_data']) opacity-50 grayscale @endif bg-white shadow rounded-lg mb-6">
    <div class="px-6 py-4 border-b border-gray-200">
        <h3 class="text-lg font-medium">{{ $reportConfig['show_appendix_full_data'] ? 'Respondent Evidence (Appendix Reference)' : 'Question Responses' }}</h3>
    </div>
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 w-1/4 hidden sm:table-cell">Pillar</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 w-full sm:w-1/2">Question & Evidence</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500">Score</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500">Selected Anchor</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                @foreach($assessment->questionResponses as $resp)
                    <tr class="align-top">
                        <td class="px-6 py-4 text-sm text-gray-900 font-medium hidden sm:table-cell">{{ $resp->pillar_name }}</td>
                        <td class="px-6 py-4 text-sm">
                            <div class="text-gray-900 font-medium mb-1">
                                @php
                                    $qLookup = strtoupper(str_replace(['_', ' '], '', $resp->question));
                                    $displayQuestion = $resp->question;
                                    $selectedAnchor = $resp->confidence;
                                    $type = $assessment->type;
                                    if(isset($frameworkQuestions[$type])) {
                                        foreach($frameworkQuestions[$type] as $pillar) {
                                            foreach($pillar['questions'] as $code => $qMeta) {
                                                if(strtoupper(str_replace(['_', ' '], '', $code)) === $qLookup) {
                                                    $displayQuestion = $qMeta['text'];
                                                    
                                                    // Determine Selected Anchor
                                                    if (!empty($qMeta['type3_cards'])) {
                                                        foreach ($qMeta['type3_cards'] as $card) {
                                                            if ($card['score'] == $resp->score) {
                                                                $selectedAnchor = $card['response'];
                                                                break;
                                                            }
                                                        }
                                                    } elseif (!empty($qMeta['anchors'])) {
                                                        // Fallback to standard anchors (1, 3, 5)
                                                        $selectedAnchor = $qMeta['anchors'][$resp->score] ?? ($qMeta['anchors'][(string)$resp->score] ?? 'Score: ' . $resp->score);
                                                    }
                                                    
                                                    break 2;
                                                }
                                            }
                                        }
                                    }
                                @endphp
                                {{ $displayQuestion }}
                            </div>
                            <div class="text-gray-500 text-xs mt-2 p-2 bg-gray-50 rounded italic whitespace-pre-wrap">{{ $resp->evidence_note ?: 'No evidence note provided.' }}</div>
                        </td>
                        <td class="px-6 py-4">
                            <span class="px-2 py-1 bg-gray-100 rounded text-sm font-bold border border-gray-200">{{ $resp->score }}</span>
                        </td>
                        <td class="px-6 py-4 text-xs text-gray-500 italic leading-relaxed">{{ $selectedAnchor }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
</section>

<!-- AI Consulting Insights -->
<section class="mt-8">
    <div class="flex items-center justify-between mb-6">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 bg-blue-600 rounded-xl shadow-lg flex items-center justify-center">
                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>
            </div>
            <div>
                <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest">AI-generated client narrative</p>
                <h2 class="text-2xl font-black text-slate-900 tracking-tight">Generated PIR Tier 1 Report Preview</h2>
            </div>
        </div>
    </div>
    @if($fullReportDraft)
        @include('admin.assessments.partials.structured-report-preview', ['fullReportDraft' => $fullReportDraft, 'assessment' => $assessment])
    @elseif($snapshotReport)
        <div class="space-y-6">
            <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6">
                <h4 class="text-xs font-black text-slate-500 uppercase tracking-widest mb-4">Intelligence Brief</h4>
                <div class="space-y-4 text-sm text-slate-700 leading-7">
                    @foreach($briefParagraphs as $paragraph)
                        <p>{{ $paragraph }}</p>
                    @endforeach
                </div>
            </div>

            <div>
                <h4 class="text-xs font-black text-slate-500 uppercase tracking-widest mb-4">Insight Cards</h4>
                <div class="grid grid-cols-1 xl:grid-cols-3 gap-4">
                    @foreach($insightCards as $card)
                        @php
                            $cardCode = strtoupper((string) ($card['pillar_code'] ?? $card['domain_code'] ?? ''));
                            $cardName = $card['pillar_name'] ?? $card['domain_name'] ?? $card['name'] ?? 'Insight area';
                            $cardRag = $card['rag'] ?? 'Amber';
                            $isComplianceCard = $cardCode === 'COMPLIANCE' || \Illuminate\Support\Str::contains(strtolower($cardName), 'compliance');
                            $badgeClass = match($cardRag) {
                                'Green' => 'bg-green-100 text-green-800 border-green-200',
                                'Red' => 'bg-red-100 text-red-800 border-red-200',
                                default => 'bg-amber-100 text-amber-800 border-amber-200',
                            };
                        @endphp
                        <article class="bg-white rounded-xl border {{ $isComplianceCard ? 'border-blue-400 ring-1 ring-blue-100' : 'border-slate-200' }} shadow-sm p-5">
                            <div class="flex items-start justify-between gap-3 mb-4">
                                <h5 class="text-sm font-black text-slate-900 leading-snug">{{ $cardName }}</h5>
                                <span class="shrink-0 px-2 py-1 rounded-full border text-[10px] font-black uppercase tracking-widest {{ $badgeClass }}">
                                    {{ $cardRag }}
                                </span>
                            </div>
                            <div class="text-3xl font-black text-slate-900 mb-4">{{ number_format((float) ($card['score'] ?? 0), 1) }}</div>
                            <div class="space-y-4 text-sm leading-6 text-slate-700">
                                <div>
                                    <div class="text-[10px] font-black uppercase tracking-widest text-slate-400 mb-1">Finding</div>
                                    <p>{{ $card['finding'] ?? 'No finding provided.' }}</p>
                                </div>
                                <div>
                                    <div class="text-[10px] font-black uppercase tracking-widest text-slate-400 mb-1">Action</div>
                                    <p>{{ $card['action'] ?? 'No action provided.' }}</p>
                                </div>
                            </div>
                        </article>
                    @endforeach
                </div>
                <p class="mt-4 text-xs font-semibold text-slate-500">
                    AI insights last generated:
                    {{ $snapshotLead?->updated_at ? $snapshotLead->updated_at->format('d M Y H:i') : 'Not available' }}
                </p>
            </div>
        </div>
    @else
    <div class="bg-slate-50 border-2 border-dashed border-slate-200 rounded-3xl p-12 text-center">
        <div class="w-16 h-16 bg-slate-100 rounded-2xl flex items-center justify-center mx-auto mb-4">
            <svg class="w-8 h-8 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>
        </div>
        <h4 class="text-lg font-bold text-slate-800 mb-2">No AI Strategy Yet</h4>
        <p class="text-slate-500 text-sm max-w-xs mx-auto mb-6">
            @if($assessment->status === 'completed')
                This assessment was completed before structured AI drafts were stored. Rebuild the AI insights to enable PDF export.
            @else
                Complete the assessment and click "Generate AI Insights" to receive AI-powered strategic consulting insights.
            @endif
        </p>
        @if($snapshotLead && $assessment->overall_score > 0)
            <form action="{{ route('admin.assessments.regenerateSnapshotAi', $assessment->id) }}" method="POST">
                @csrf
                <button type="submit" class="inline-flex items-center gap-2 bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 px-6 rounded-xl shadow-lg transition-all active:scale-95">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>
                    <span>Regenerate AI Insights</span>
                </button>
            </form>
        @elseif($assessment->overall_score > 0 && ($assessment->status !== 'approved' || $needsStructuredDraft))
            <form action="{{ route('admin.assessments.generateReport', $assessment) }}" method="POST">
                @csrf
                <button type="submit" class="inline-flex items-center gap-2 bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 px-6 rounded-xl shadow-lg transition-all active:scale-95">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>
                    <span>{{ $assessment->status === 'completed' ? 'Rebuild AI Insights' : 'Generate AI Insights' }}</span>
                </button>
            </form>
        @endif
    </div>
    @endif
</section>

@endsection

@push('scripts')
{{-- Ensure Chart.js is loaded before the plugin --}}
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.2/dist/chart.umd.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels@2.2.0/dist/chartjs-plugin-datalabels.min.js"></script>

<script>
document.addEventListener("DOMContentLoaded", function() {
    // Register the datalabels plugin inside the listener to ensure Chart is defined
    if (typeof Chart !== 'undefined' && typeof ChartDataLabels !== 'undefined') {
        Chart.register(ChartDataLabels);
    }

    const pillars = @json($assessment->pillarScores);
    
    if (pillars.length > 0) {
        const labels = pillars.map(p => p.name);
        const data = pillars.map(p => p.score);
        
        // Radar Chart
        const radarCtx = document.getElementById('radarChart').getContext('2d');
        new Chart(radarCtx, {
            type: 'radar',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Score',
                    data: data,
                    backgroundColor: 'rgba(59, 130, 246, 0.2)',
                    borderColor: '#3b82f6',
                    pointBackgroundColor: '#2563eb',
                    borderWidth: 2
                }]
            },
            options: {
                scales: {
                    r: {
                        angleLines: { color: 'rgba(0, 0, 0, 0.1)' },
                        grid: { color: 'rgba(0, 0, 0, 0.1)' },
                        suggestedMin: 1,
                        suggestedMax: 5,
                        ticks: { stepSize: 1, backdropColor: 'transparent' }
                    }
                },
                responsive: true,
                maintainAspectRatio: false,
                animation: false,
                plugins: {
                    legend: { display: false },
                    datalabels: { display: false }
                }
            }
        });

        // Health Pie Chart
        const ctxPie = document.getElementById('healthPieChart').getContext('2d');
        const pillarData = {!! json_encode($assessment->pillarScores->map(function($p, $index) {
            $pParts = explode(' — ', $p->name);
            $pCode = count($pParts) > 1 ? $pParts[0] : ('P' . ($index + 1));
            return [
                'name' => $p->name,
                'code' => $pCode,
                'score' => (float)$p->score,
                'color' => $p->score >= 3.8 ? '#22c55e' : ($p->score >= 2.5 ? '#eab308' : '#ef4444')
            ];
        })) !!};

        new Chart(ctxPie, {
            type: 'doughnut',
            plugins: [ChartDataLabels],
            data: {
                labels: pillarData.map(p => p.name),
                datasets: [{
                    data: pillarData.map(p => 1), // Equal slices for pillars
                    backgroundColor: pillarData.map(p => p.color),
                    borderWidth: 4,
                    borderColor: '#ffffff',
                    hoverOffset: 10
                }]
            },
            options: {
                cutout: '65%',
                responsive: true,
                maintainAspectRatio: false,
                animation: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        backgroundColor: 'rgba(15, 23, 42, 0.9)',
                        padding: 12,
                        titleFont: { size: 10, weight: 'bold' },
                        bodyFont: { size: 12, weight: 'black' },
                        callbacks: {
                            label: function(context) {
                                const p = pillarData[context.dataIndex];
                                return ` ${p.name}: ${p.score}`;
                            }
                        }
                    },
                    datalabels: {
                        color: (context) => {
                            const p = pillarData[context.dataIndex];
                            return p.score >= 2.5 && p.score < 3.8 ? '#0f172a' : '#ffffff';
                        },
                        font: {
                            weight: 'bold',
                            size: 11
                        },
                        formatter: (value, context) => {
                            return pillarData[context.dataIndex].code;
                        },
                        textAlign: 'center',
                        display: true
                    }
                }
            }
        });

        // --- BAR CHART WITH CONFIDENCE ---
        const barCtx = document.getElementById('barChart').getContext('2d');
        const responses = @json($assessment->questionResponses);
        const confPillars = {};
        responses.forEach(r => {
            if(!confPillars[r.pillar_name]) confPillars[r.pillar_name] = [];
            const c = r.confidence ? r.confidence.toLowerCase() : 'medium';
            let val = 0.5; 
            if(c === 'high') val = 0.9;
            if(c === 'low') val = 0.2;
            confPillars[r.pillar_name].push(val);
        });
        
        const pillarConfidence = pillars.map(p => {
            const vals = confPillars[p.name] || [0.5];
            return vals.reduce((a, b) => a + b, 0) / vals.length;
        });

        new Chart(barCtx, {
            type: 'bar',
            data: {
                labels: pillarData.map(p => p.code),
                datasets: [
                    {
                        label: 'Score',
                        data: data,
                        backgroundColor: pillarData.map(p => p.color),
                        borderRadius: 4,
                        barThickness: 15
                    },
                    @if($reportConfig['show_confidence_bands'])
                    {
                        label: 'Confidence',
                        data: pillarConfidence.map((c, i) => data[i] * c),
                        backgroundColor: 'rgba(15, 23, 42, 0.1)',
                        borderColor: '#0f172a',
                        borderWidth: 1,
                        borderDash: [2, 2],
                        type: 'line',
                        fill: false,
                        pointRadius: 3,
                        pointStyle: 'rectRot',
                        showLine: false
                    }
                    @endif
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                animation: false,
                scales: {
                    y: { beginAtZero: true, max: 5, ticks: { stepSize: 1 } },
                    x: { grid: { display: false } }
                },
                plugins: {
                    legend: { display: false },
                    datalabels: {
                        anchor: 'end',
                        align: 'top',
                        font: { size: 9, weight: 'bold' },
                        formatter: (val, ctx) => {
                            if(ctx.datasetIndex === 1) return (pillarConfidence[ctx.dataIndex] * 100).toFixed(0) + '%';
                            return val;
                        },
                        display: (ctx) => ctx.datasetIndex === 0
                    }
                }
            }
        });
    }
});
</script>
@endpush
