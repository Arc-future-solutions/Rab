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
    $aiText = $lead->ai_recommendation ?? $assessment->ai_recommendation ?? null;
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
                <button id="exportPdf" class="bg-slate-800 hover:bg-slate-900 text-white px-4 py-2.5 rounded-lg shadow-lg text-sm flex items-center justify-center gap-2 transition-all active:scale-95 flex-1 sm:flex-none">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                    <span>Export Report</span>
                </button>
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
            </div>
        </div>
    </div>
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

@if($reportConfig['show_risk_matrix'])
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

@if($reportConfig['show_intelligence_profile'])
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

@if($reportConfig['show_action_register'])
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

@if($reportConfig['show_final_position'])
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

<!-- AI Agent Recommendations -->
<div class="mt-8">
    <div class="flex items-center justify-between mb-6">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 bg-gradient-to-br from-blue-600 to-indigo-700 rounded-xl shadow-lg flex items-center justify-center">
                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>
            </div>
            <div>
                <h3 class="text-xl font-black text-slate-800 tracking-tight">AI Consulting Insights</h3>
                <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Agentic Intelligence & Strategy Recommendations</p>
            </div>
        </div>
    </div>
     @if($aiText)
     @php
            $text = $aiText;
            
            // Split by **Title**
            $parts = preg_split('/\*\*(.*?)\*\*/', $text, -1, PREG_SPLIT_DELIM_CAPTURE);
            
            $intro = trim($parts[0] ?? '');
            $segments = [];
            
            for ($i = 1; $i < count($parts); $i += 2) {
                $title = trim($parts[$i]);
                $content = isset($parts[$i+1]) ? trim($parts[$i+1]) : '';
                
                // If this is the last segment and it's very long, check if there's a sign-off
                if ($i + 2 >= count($parts)) {
                    // Simple heuristic for sign-off: text after last double newline if it's small
                    // But for now, let's keep it simple as requested.
                }
                
                $segments[] = [
                    'title' => $title,
                    'content' => $content
                ];
            }
            
            $coreSegments = collect($segments);
            $outro = null; // AI responses usually don't have a clear sign-off in this format yet

            $icons = [
                '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>',
                '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>',
                '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"></path></svg>',
                '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>'
            ];
        @endphp

        <div class="space-y-8">
            @if($intro)
                <div class="bg-blue-50/50 rounded-3xl p-8 border border-blue-100/50 shadow-sm relative overflow-hidden">
                    <div class="absolute -right-4 -top-4 text-blue-100 opacity-20">
                        <svg class="w-24 h-24" fill="currentColor" viewBox="0 0 24 24"><path d="M14.017 21L14.017 18C14.017 16.8954 14.9124 16 16.017 16H19.017C20.1216 16 21.017 16.8954 21.017 18V21M14.017 21H21.017M14.017 21C12.9124 21 12.017 20.1046 12.017 19V11C12.017 9.89543 12.9124 9 14.017 9H21.017C22.1216 9 23.017 9.89543 23.017 11V19C23.017 20.1046 22.1216 21 21.017 21M3.017 21L3.017 18C3.017 16.8954 3.91243 16 5.017 16H8.017C9.12157 16 10.017 16.8954 10.017 18V21M3.017 21H10.017M3.017 21C1.91243 21 1.017 20.1046 1.017 19V11C1.017 9.89543 1.91243 9 3.017 9H10.017C11.1216 9 12.017 9.89543 12.017 11V19C12.017 20.1046 11.1216 21 10.017 21"></path></svg>
                    </div>
                    <div class="flex items-center gap-2 mb-4">
                        <span class="p-1 px-2 rounded-md bg-blue-600 text-white text-[9px] font-black uppercase tracking-widest">Consulting Context</span>
                    </div>
                    <div class="text-sm font-bold text-slate-700 leading-relaxed italic">
                        {{ $intro }}
                    </div>
                </div>
            @endif

            <div class="grid grid-cols-1 gap-10">
                @foreach($coreSegments as $index => $segment)
                    @php
                        // Special styling for different segment types
                        $title = strtolower($segment['title']);
                        $isRisks = Str::contains($title, 'risk');
                        $isRecs = Str::contains($title, 'recommendation');
                        $isStrengths = Str::contains($title, 'strength');
                        
                        $bgClass = 'bg-white';
                        $textClass = 'text-slate-600';
                        $titleColor = 'text-blue-600';
                        $accentBg = 'bg-blue-600';
                        $iconColor = 'text-white';
                        
                        if ($isRisks) {
                            $bgClass = 'bg-slate-900';
                            $textClass = 'text-slate-300';
                            $titleColor = 'text-red-400';
                            $accentBg = 'bg-red-500/20';
                            $iconColor = 'text-red-400';
                        } elseif ($isRecs) {
                            $bgClass = 'bg-blue-600';
                            $textClass = 'text-blue-50';
                            $titleColor = 'text-white';
                            $accentBg = 'bg-white/20';
                            $iconColor = 'text-white';
                        }
                    @endphp

                    <div class="{{ $bgClass }} rounded-[2.5rem] p-10 border border-slate-100 shadow-xl shadow-slate-200/50 relative overflow-hidden transition-all duration-500 hover:-translate-y-2 group">
                        <div class="absolute top-0 right-0 p-12 opacity-[0.03] group-hover:opacity-[0.07] transition-opacity">
                            {!! $icons[$index] ?? '' !!}
                        </div>
                        
                        <div class="flex items-center gap-5 mb-8">
                            <div class="w-14 h-14 flex items-center justify-center rounded-2xl {{ $accentBg }} {{ $iconColor }} shadow-xl">
                                {!! $icons[$index] ?? '' !!}
                            </div>
                            <h4 class="text-xs font-black {{ $titleColor }} uppercase tracking-[0.4em]">
                                {{ $segment['title'] }}
                            </h4>
                        </div>

                        <div class="text-[16px] md:text-[18px] leading-[1.8] whitespace-pre-wrap {{ $textClass }} font-medium 
                        [&_strong]:{{ $isRisks || $isRecs ? 'text-white' : 'text-slate-900' }} [&_strong]:font-black">
                            @php
                                // Enhance bullet points
                                $formattedContent = preg_replace('/^- (.*)/m', '<div class="flex gap-4 mb-4 items-start"><span class="mt-2.5 w-2 h-2 rounded-full bg-current opacity-50 shrink-0"></span><span>$1</span></div>', trim($segment['content']));
                            @endphp
                            {!! $formattedContent !!}
                        </div>
                    </div>
                @endforeach
            </div>

            @if($outro)
                <div class="bg-slate-50 rounded-3xl p-8 border border-slate-200 text-center relative group">
                    <div class="absolute inset-0 bg-gradient-to-r from-blue-600/5 to-transparent opacity-0 group-hover:opacity-100 transition-opacity"></div>
                    <h4 class="text-[9px] font-black text-slate-400 uppercase tracking-[0.3em] mb-3">Strategic Momentum Sign-off</h4>
                    <div class="text-xs font-bold text-slate-500 leading-relaxed max-w-2xl mx-auto italic">
                        "{{ $outro }}"
                    </div>
                </div>
            @endif
        </div>
        </div>
    @else
        <div class="bg-slate-50 border-2 border-dashed border-slate-200 rounded-3xl p-12 text-center">
            <div class="w-16 h-16 bg-slate-100 rounded-2xl flex items-center justify-center mx-auto mb-4">
                <svg class="w-8 h-8 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>
            </div>
            <h4 class="text-lg font-bold text-slate-800 mb-2">No AI Strategy Yet</h4>
            <p class="text-slate-500 text-sm max-w-xs mx-auto mb-6">Complete the assessment and click "Generate Report" to receive AI-powered strategic consulting insights.</p>
            @if($assessment->status !== 'completed' && $assessment->overall_score > 0)
                <form action="{{ route('admin.assessments.generateReport', $assessment) }}" method="POST">
                    @csrf
                    <button type="submit" class="inline-flex items-center gap-2 bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 px-6 rounded-xl shadow-lg transition-all active:scale-95">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>
                        <span>Generate AI Insights</span>
                    </button>
                </form>
            @endif
        </div>
    @endif
</div>

<!-- HIDDEN PDF HEADER TEMPLATE -->
<div style="position: absolute; left: -9999px; top: 0; width: 210mm;">
    <div id="pdf-header-template" class="w-[210mm] bg-white p-10 font-sans border-b-2 border-slate-900 mb-6">
        <header class="flex items-center justify-between pb-4">
            <div class="flex items-center gap-6">
                <img src="/assets/images/logo-rab.png" alt="RAB" class="h-10">
                <div class="h-10 w-px bg-slate-200"></div>
                <div>
                    <h1 class="text-2xl font-black text-slate-900 uppercase tracking-tighter leading-none mb-1">{{ $reportConfig['tier_label'] }}</h1>
                    <div class="flex items-center gap-2">
                        <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">{{ $assessment->client->company_name ?? $assessment->client_name }}</p>
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
                            <span class="text-xl font-black text-slate-900">{{ number_format($assessment->overall_score, 1) }}</span>
                        </div>
                    </div>
                    <div class="flex flex-col justify-center">
                        <div class="flex items-center gap-2 mb-1">
                            <span class="text-[14px] font-black text-slate-900">{{ number_format($assessment->overall_score, 1) }} / 5.0</span>
                            <span class="px-2 py-0.5 rounded-full text-[9px] font-black uppercase tracking-widest @if($assessment->rag_status === 'Red') bg-red-600 text-white @elseif($assessment->rag_status === 'Amber') bg-amber-500 text-white @else bg-green-600 text-white @endif">
                                {{ strtoupper($assessment->rag_status) }} STATUS
                            </span>
                        </div>
                        <span class="text-[9px] font-black text-slate-400 uppercase tracking-[0.1em]">
                            @if($assessment->rag_status === 'Red') Critical Intervention Required @elseif($assessment->rag_status === 'Amber') Targeted Improvement Needed @else Continuous Optimization @endif
                        </span>
                    </div>
                </div>
            </div>
        </header>
    </div>
</div>

@endsection

@push('scripts')
{{-- Ensure Chart.js is loaded before the plugin --}}
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.2/dist/chart.umd.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels@2.2.0/dist/chartjs-plugin-datalabels.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>

<script>
document.addEventListener("DOMContentLoaded", function() {
    // Register the datalabels plugin inside the listener to ensure Chart is defined
    if (typeof Chart !== 'undefined' && typeof ChartDataLabels !== 'undefined') {
        Chart.register(ChartDataLabels);
    }
    // PDF Export Logic
    const exportBtn = document.getElementById('exportPdf');
    if(exportBtn) {
        exportBtn.addEventListener('click', async function() {
            const { jsPDF } = window.jspdf;
            const doc = new jsPDF('p', 'mm', 'a4');
            const pageWidth = doc.internal.pageSize.getWidth();
            
            exportBtn.disabled = true;
            exportBtn.innerText = 'Generating...';

            try {
                await new Promise(r => setTimeout(r, 150));
                const pageHeight = doc.internal.pageSize.getHeight();
                const margin = 14;
                const usableWidth = pageWidth - margin * 2;
                let y = margin;

                // ─── PAGE HEADER (Captured from HTML Template) ────────────────
                const headerEl = document.getElementById('pdf-header-template');
                const headerCvs = await html2canvas(headerEl, {scale:3, backgroundColor:'#ffffff'});
                const headerImg = headerCvs.toDataURL('image/png');
                const headerH = (headerCvs.height * pageWidth) / headerCvs.width;
                doc.addImage(headerImg, 'PNG', 0, 0, pageWidth, headerH);

                y = headerH + 8;

                // ─── TWO-COLUMN SECTION: Chart | Pillar Scores ──────────────
                const colL = margin;
                const colR = pageWidth / 2 + 4;
                const colW = (pageWidth / 2) - margin - 4;

                // Radar
                const radarEl = document.getElementById('radarChartContainer');
                const radarCvs = await html2canvas(radarEl, {scale:3, backgroundColor:'#ffffff'});
                const radarImg = radarCvs.toDataURL('image/png');
                const radarH = (radarCvs.height * colW) / radarCvs.width;
                doc.addImage(radarImg, 'PNG', colL, y, colW, radarH);

                // Bar
                const barEl = document.getElementById('barChartContainer');
                const barCvs = await html2canvas(barEl, {scale:3, backgroundColor:'#ffffff'});
                const barImg = barCvs.toDataURL('image/png');
                const barH = (barCvs.height * colW) / barCvs.width;
                doc.addImage(barImg, 'PNG', colR, y, colW, barH);

                y += Math.max(radarH, barH) + 10;

                // ─── RISK MATRIX & ACTION REGISTER (TIER 1 & 2) ──────────────
                @if($reportConfig['show_risk_matrix'])
                    if (y > pageHeight - 60) { doc.addPage(); y = 20; }
                    doc.setFontSize(12);
                    doc.setFont(undefined, 'bold');
                    doc.text("Risk Matrix & Priorities", margin, y);
                    y += 8;

                    const matrixEl = document.getElementById('pdf-risk-matrix');
                    const matrixCvs = await html2canvas(matrixEl, {scale:2, backgroundColor:'#ffffff'});
                    const matrixImg = matrixCvs.toDataURL('image/png');
                    const matrixH = (matrixCvs.height * usableWidth) / matrixCvs.width;
                    doc.addImage(matrixImg, 'PNG', margin, y, usableWidth, matrixH);
                    y += matrixH + 10;
                @endif

                @if($reportConfig['show_intelligence_profile'])
                    if (y > pageHeight - 100) { doc.addPage(); y = 20; }
                    doc.setFontSize(12);
                    doc.setFont(undefined, 'bold');
                    doc.text("Intelligence Profile (Deep Dive)", margin, y);
                    y += 8;

                    const profileEl = document.getElementById('pdf-intelligence-profile');
                    const profileCvs = await html2canvas(profileEl, {scale:2, backgroundColor:'#ffffff'});
                    const profileImg = profileCvs.toDataURL('image/png');
                    const profileH = (profileCvs.height * usableWidth) / profileCvs.width;
                    doc.addImage(profileImg, 'PNG', margin, y, usableWidth, profileH);
                    y += profileH + 10;
                @endif

                @if($reportConfig['show_final_position'])
                    if (y > pageHeight - 40) { doc.addPage(); y = 20; }
                    const finalEl = document.getElementById('pdf-final-position');
                    const finalCvs = await html2canvas(finalEl, {scale:2, backgroundColor:'#ffffff'});
                    const finalImg = finalCvs.toDataURL('image/png');
                    const finalH = (finalCvs.height * usableWidth) / finalCvs.width;
                    doc.addImage(finalImg, 'PNG', margin, y, usableWidth, finalH);
                    y += finalH + 10;
                @endif

                // ─── DIVIDER ────────────────────────────────────────────────
                if (y < pageHeight - 20) {
                    doc.setDrawColor(226, 232, 240);
                    doc.line(margin, y, pageWidth - margin, y);
                    y += 10;
                } else {
                    doc.addPage();
                    y = 20;
                }

                // ─── QUESTIONS TABLE ─────────────────────────────────────────
                // Section title
                doc.setFontSize(11);
                doc.setFont(undefined, 'bold');
                doc.setTextColor(15, 23, 42);
                doc.text("Detailed Assessment Findings", margin, y);
                y += 6;

                // Table header row
                const col1W = usableWidth * 0.08;  // Pillar code
                const col2W = usableWidth * 0.55;  // Question
                const col3W = usableWidth * 0.30;  // Evidence note
                const col4W = usableWidth * 0.07;  // Score

                doc.setFillColor(15, 23, 42);
                doc.rect(margin, y, usableWidth, 7, 'F');
                doc.setFontSize(7);
                doc.setFont(undefined, 'bold');
                doc.setTextColor(255, 255, 255);
                doc.text("PILLAR", margin + 2, y + 5);
                doc.text("QUESTION", margin + col1W + 2, y + 5);
                doc.text("EVIDENCE NOTE", margin + col1W + col2W + 2, y + 5);
                doc.text("SCORE", pageWidth - margin - 2, y + 5, {align:'right'});
                y += 10;

                let rowBg = false;
                let splitQ, splitNote, rowLines, rowH, scoreNum, chipFill, displayPillarCode;

                @foreach($assessment->questionResponses as $resp)
                    @php
                        // Extract only the P1/P2 code from the pillar name
                        $pParts = explode(' ', $resp->pillar_name);
                        $pCode = $pParts[0] ?? '—';

                        $qLookup = strtoupper(str_replace(['_', ' '], '', $resp->question));
                        $displayQuestion = $resp->question;
                        $type = $assessment->type;
                        if(isset($frameworkQuestions[$type])) {
                            foreach($frameworkQuestions[$type] as $pill) {
                                foreach($pill['questions'] as $code => $qMeta) {
                                    if(strtoupper(str_replace(['_', ' '], '', $code)) === $qLookup) {
                                        $displayQuestion = $code . ": " . (is_array($qMeta) ? $qMeta['text'] : $qMeta);
                                        break 2;
                                    }
                                }
                            }
                        }
                        
                        // Decode any HTML entities and clean for JS
                        $cleanQ    = addslashes(str_replace(["\r","\n"],' ', html_entity_decode($displayQuestion, ENT_QUOTES)));
                        $cleanNote = addslashes(str_replace(["\r","\n"],' ', html_entity_decode($resp->evidence_note ?: '—', ENT_QUOTES)));
                    @endphp

                    splitQ    = doc.splitTextToSize({!! json_encode($cleanQ) !!}, col2W - 4);
                    splitNote = doc.splitTextToSize({!! json_encode($cleanNote) !!}, col3W - 4);
                    rowLines  = Math.max(splitQ.length, splitNote.length, 1);
                    rowH      = rowLines * 4.4 + 5;

                    if(y + rowH > pageHeight - 14) {
                        doc.addPage();
                        // Repeat header on continuation pages
                        doc.setFillColor(15, 23, 42);
                        doc.rect(0, 0, pageWidth, 12, 'F');
                        doc.setFontSize(8);
                        doc.setFont(undefined, 'bold');
                        doc.setTextColor(255,255,255);
                        doc.text("Detailed Assessment Findings (cont.)", margin, 8);
                        y = 20;
                        rowBg = false;

                        doc.setFillColor(15, 23, 42);
                        doc.rect(margin, y, usableWidth, 7, 'F');
                        doc.setFontSize(7);
                        doc.setFont(undefined, 'bold');
                        doc.setTextColor(255, 255, 255);
                        doc.text("PILLAR", margin + 2, y + 5);
                        doc.text("QUESTION", margin + col1W + 2, y + 5);
                        doc.text("EVIDENCE NOTE", margin + col1W + col2W + 2, y + 5);
                        doc.text("SCORE", pageWidth - margin - 2, y + 5, {align:'right'});
                        y += 10;
                    }

                    // Alternating row background
                    if(rowBg) {
                        doc.setFillColor(248, 250, 252);
                        doc.rect(margin, y - 2, usableWidth, rowH, 'F');
                    }
                    rowBg = !rowBg;

                    // Pillar code (small pill)
                    doc.setFillColor(226, 232, 240);
                    doc.roundedRect(margin, y, col1W - 2, 5.5, 1, 1, 'F');
                    doc.setFontSize(7);
                    doc.setFont(undefined, 'bold');
                    doc.setTextColor(71, 85, 105);
                    doc.text("{{ $pCode }}", margin + (col1W - 2)/2, y + 4, {align:'center'});

                    // Question text
                    doc.setFontSize(7.5);
                    doc.setFont(undefined, 'normal');
                    doc.setTextColor(30, 41, 59);
                    doc.text(splitQ, margin + col1W + 2, y + 4);

                    // Evidence note
                    doc.setFontSize(7);
                    doc.setTextColor(100, 116, 139);
                    doc.text(splitNote, margin + col1W + col2W + 2, y + 4);

                    // Score chip
                    scoreNum = parseFloat("{{ $resp->score }}");
                    chipFill = scoreNum < 2.5 ? [239,68,68] : (scoreNum < 3.8 ? [245,158,11] : [16,185,129]);
                    doc.setFillColor(chipFill[0], chipFill[1], chipFill[2]);
                    doc.roundedRect(pageWidth - margin - 10, y - 0.5, 10, 6, 1, 1, 'F');
                    doc.setFontSize(7);
                    doc.setFont(undefined, 'bold');
                    doc.setTextColor(255,255,255);
                    doc.text("{{ $resp->score }}", pageWidth - margin - 5, y + 4, {align:'center'});

                    y += rowH;

                    // Bottom border per row
                    doc.setDrawColor(226, 232, 240);
                    doc.setLineWidth(0.2);
                    doc.line(margin, y - 1, pageWidth - margin, y - 1);
                @endforeach

                // Pillar Legend / Key
                y += 6;
                if(y + 15 > pageHeight - 15) {
                    doc.addPage();
                    y = 20;
                }
                doc.setFontSize(7);
                doc.setFont(undefined, 'bold');
                doc.setTextColor(15, 23, 42);
                doc.text("Pillar Key:", margin, y);
                y += 4;
                doc.setFont(undefined, 'normal');
                doc.setTextColor(100, 116, 139);
                
                let legendFullText = "";
                @foreach($assessment->pillarScores as $pillar)
                    legendFullText += "{!! addslashes(html_entity_decode($pillar->name, ENT_QUOTES)) !!}    ·    ";
                @endforeach
                // Remove trailing separator
                legendFullText = legendFullText.replace(/    ·    $/, "");

                let splitLegendText = doc.splitTextToSize(legendFullText, usableWidth);
                doc.text(splitLegendText, margin, y);

                // Footer disclaimer (Mandatory for all pages)
                const addDisclaimerFooter = (docPage) => {
                    const disclaimer = "This report is produced by RAB Consulting Services Ltd. All findings are based on information provided during the diagnostic or consultant-led engagement. This report constitutes operational intelligence and professional advisory guidance only. It does not constitute legal, regulatory, financial, or compliance advice. Clients should engage their own legal, compliance, and regulatory advisers to confirm any regulatory position. RAB Consulting Services Ltd accepts no liability for decisions made solely on the basis of this report without independent legal or professional verification. ©2026 RAB Consulting Services Ltd.";
                    doc.setFontSize(5);
                    doc.setFont(undefined, 'normal');
                    doc.setTextColor(148, 163, 184);
                    const splitDisclaimer = doc.splitTextToSize(disclaimer, usableWidth);
                    
                    const pageCount = doc.internal.getNumberOfPages();
                    for(let i = 1; i <= pageCount; i++) {
                        doc.setPage(i);
                        doc.setDrawColor(226, 232, 240);
                        doc.line(margin, pageHeight - 14, pageWidth - margin, pageHeight - 14);
                        doc.text(splitDisclaimer, margin, pageHeight - 11);
                        doc.text("Page " + i + " of " + pageCount, pageWidth - margin, pageHeight - 6, {align:'right'});
                    }
                };

                addDisclaimerFooter(doc);

                doc.save(`Assessment_{{ Str::slug($assessment->client->company_name ?? 'Report') }}_{{ date('Ymd') }}.pdf`);

            } catch (error) {
                console.error("PDF generation failed", error);
                alert("Failed to generate PDF. Check console for details.");
            } finally {
                exportBtn.disabled = false;
                exportBtn.innerText = 'Export Report';
            }
        });
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

        // --- PDF SCORE GAUGE ---
        new Chart(document.getElementById('pdfScoreGauge'), {
            type: 'doughnut',
            data: {
                datasets: [{
                    data: [{{ $assessment->overall_score }}, {{ 5 - $assessment->overall_score }}],
                    backgroundColor: ['{{ $assessment->rag_status === 'Red' ? '#ef4444' : ($assessment->rag_status === 'Amber' ? '#f59e0b' : '#10b981') }}', '#f1f5f9'],
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
                animation: false,
                plugins: { legend: { display: false }, tooltip: { enabled: false }, datalabels: { display: false } }
            }
        });
    }
});
</script>
@endpush
