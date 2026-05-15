@php
$itsm_questions = $questions ?? [];
$total_q = 0;
foreach($itsm_questions as $p => $qs) $total_q += count($qs);

$savedResponses = $assessment->questionResponses->keyBy(function($item) {
    return explode(':', $item->question)[0];
});
$savedPillars = $assessment->pillarScores->keyBy('name');
@endphp

@extends('admin.layouts.app')

@section('header')
<div class="flex justify-between items-center w-full" x-data="{
    answered: {{ $assessment->questionResponses->count() }},
    total: {{ $total_q }},
    submitting: false
}">
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
        <h2 class="text-3xl font-black text-white tracking-tight mb-3">AI Agent Analysis in Progress</h2>
        <p class="text-slate-400 font-bold uppercase tracking-[0.2em] text-[10px]">Benchmarking responses against industry frameworks...</p>
    </div>
    <div class="flex items-center gap-4">
        <span>{{ $assessment->client->company_name }} &rsaquo; {{ $assessment->name }}</span>
    </div>
    <div class="flex gap-4 items-center">
        <span class="text-sm text-gray-600 bg-yellow-100 px-3 py-1 rounded border border-yellow-200">
            <span x-text="answered"></span> / <span x-text="total"></span> Scored
        </span>
        <div class="flex items-center gap-2">
            <span class="text-sm font-semibold">Live Score:</span>
            <span id="live_overall_score" class="px-2 py-1 rounded text-white font-bold
                @if($assessment->rag_status === 'Green') bg-green-500
                @elseif($assessment->rag_status === 'Amber') bg-yellow-500
                @else bg-red-500 @endif
            ">
                {{ number_format($assessment->overall_score, 1) }}
            </span>
        </div>
        <form id="exitForm" action="{{ route('admin.assessments.generateReport', $assessment->id) }}" method="POST" class="inline m-0" @submit="submitting = true">
            @csrf
            <button type="submit" class="bg-gray-800 text-white px-4 py-2 rounded text-sm font-bold shadow hover:bg-gray-700">Done / Exit & Generate Report</button>
        </form>
    </div>
</div>
@endsection

@section('content')
<div x-data="scorerComponent()">
    <!-- Consultant Disclaimer Banner -->
    <div class="bg-amber-50 border-l-4 border-amber-400 p-4 mb-6 shadow-sm rounded-r-lg">
        <div class="flex items-center gap-3">
            <svg class="h-5 w-5 text-amber-400" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" /></svg>
            <p class="text-[11px] font-black text-amber-900 uppercase tracking-tight">
                Consultant Note: All scores represent self-reported data verified by evidentiary submission. Findings are operational and advisory only.
            </p>
        </div>
    </div>

    <!-- Context Card -->
    <div class="bg-white rounded-lg shadow mb-6 overflow-hidden">
        <div class="bg-indigo-50 border-b border-indigo-100 px-6 py-4">
            <h3 class="font-bold text-indigo-900">Call / Meeting Context</h3>
        </div>
        <div class="p-6 grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm text-gray-600">Date of Call</label>
                <input type="date" @change="saveContext" x-model="context.call_date" class="mt-1 block w-full rounded border-gray-300 p-2 border">
            </div>
            <div>
                <label class="block text-sm text-gray-600">Call Type</label>
                <select @change="saveContext" x-model="context.call_type" class="mt-1 block w-full rounded border-gray-300 p-2 border">
                    <option value="">Select...</option>
                    <option>Discovery Call</option>
                    <option>Follow-up Call</option>
                    <option>Deep Dive Workshop</option>
                    <option>Document Review</option>
                    <option>On-site Visit</option>
                    <option>Other</option>
                </select>
            </div>
            <div class="md:col-span-2">
                <label class="block text-sm text-gray-600">Attendees</label>
                <input type="text" @change="saveContext" x-model="context.call_attendees" placeholder="CEO, IT Director, PMO Lead" class="mt-1 block w-full rounded border-gray-300 p-2 border">
            </div>
            <div class="md:col-span-2">
                <label class="block text-sm text-gray-600">Summary</label>
                <textarea @change="saveContext" x-model="context.call_summary" class="mt-1 block w-full rounded border-gray-300 p-2 border" rows="3" placeholder="Brief summary of what was discussed..."></textarea>
            </div>
            <div class="md:col-span-2">
                <label class="block text-sm text-gray-600">Client Concerns</label>
                <textarea @change="saveContext" x-model="context.client_concerns" class="mt-1 block w-full rounded border-gray-300 p-2 border" rows="2"></textarea>
            </div>
            <div class="md:col-span-2">
                <label class="block text-sm text-gray-600">Next Action</label>
                <input type="text" @change="saveContext" x-model="context.next_agreed_action" class="mt-1 block w-full rounded border-gray-300 p-2 border">
            </div>
            <div>
                <label class="block text-sm text-gray-600">Service Context</label>
                <select @change="saveContext" x-model="context.service_context" class="mt-1 block w-full rounded border-gray-300 p-2 border">
                    <option value="">Select...</option>
                    <option value="NSI">New Service Introduction</option>
                    <option value="Established">Established</option>
                    <option value="UnderPressure">Under Pressure</option>
                    <option value="Transformation">Transformation</option>
                    <option value="LegacyPreRetirement">Legacy Pre-Retirement</option>
                </select>
            </div>
            <div>
                <label class="block text-sm text-gray-600">Regulatory Context</label>
                <select @change="saveContext" x-model="context.regulatory_context" class="mt-1 block w-full rounded border-gray-300 p-2 border">
                    <option value="">None / not applicable</option>
                    <option value="fca_uk">FCA UK</option>
                    <option value="dora_eu">DORA EU</option>
                    <option value="nhs_cqc">NHS / CQC</option>
                    <option value="public_sector">Public sector</option>
                    <option value="gdpr_only">GDPR only</option>
                </select>
            </div>
            <div>
                <label class="block text-sm text-gray-600">Sponsor / CIO</label>
                <input type="text" @change="saveContext" x-model="context.sponsor_name" class="mt-1 block w-full rounded border-gray-300 p-2 border">
            </div>
            <div>
                <label class="block text-sm text-gray-600">Interview Count</label>
                <input type="number" min="0" @change="saveContext" x-model="context.interview_count" class="mt-1 block w-full rounded border-gray-300 p-2 border">
            </div>
            <div class="md:col-span-2">
                <label class="block text-sm text-gray-600">Documents Reviewed</label>
                <textarea @change="saveContext" x-model="context.documents_reviewed" class="mt-1 block w-full rounded border-gray-300 p-2 border" rows="2" placeholder="One document per line"></textarea>
            </div>
        </div>
    </div>

    <!-- Pillars List -->
    @foreach($itsm_questions as $pillarName => $questions)
    <div class="bg-white rounded-lg shadow mb-6 overflow-hidden" x-data="{ expanded: true }">
        <div class="bg-gray-100 flex justify-between items-center px-6 py-4 cursor-pointer" @click="expanded = !expanded">
            <h3 class="font-bold text-gray-800">{{ $pillarName }}</h3>
            <div class="flex items-center gap-4">
                <span class="text-sm font-semibold" id="avg_{{ Str::slug($pillarName) }}">Avg: {{ number_format($savedPillars[$pillarName]->score ?? 0, 1) }}</span>
                <svg x-show="expanded" class="w-5 h-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"></path></svg>
                <svg x-show="!expanded" class="w-5 h-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="display: none;"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
            </div>
        </div>
        
        <div x-show="expanded" class="divide-y divide-gray-200">
            @foreach($questions as $code => $text)
                @php $ans = $savedResponses[$code] ?? null; @endphp
                <div class="p-6 {{ $ans ? '' : 'border-l-4 border-yellow-400' }}" id="row_{{ $code }}">
                    <div class="mb-2">
                        <span class="font-bold text-gray-900">{{ $code }}:</span> 
                        <span class="text-gray-700">{{ $text }}</span>
                        <span class="ml-2 text-xs italic text-green-600" id="saved_{{ $code }}" style="display: none;">Saved</span>
                    </div>
                    
                    <div class="grid grid-cols-1 lg:grid-cols-12 gap-4 mt-4">
                        <div class="lg:col-span-3">
                            <label class="block text-xs font-medium text-gray-500 mb-1">Score</label>
                            <div class="flex gap-1" x-data="{ score: {{ $ans->score ?? 'null' }} }">
                                @foreach([1 => 'bg-red-500', 2 => 'bg-orange-500', 3 => 'bg-yellow-400', 4 => 'bg-lime-500', 5 => 'bg-green-600'] as $val => $color)
                                    <label class="cursor-pointer">
                                        <input type="radio" name="score_{{ $code }}" value="{{ $val }}" class="hidden" x-model="score" @change="saveQ('{{ $code }}', '{{ addslashes($pillarName) }}', '{{ addslashes($text) }}')">
                                        <div :class="score == {{ $val }} ? '{{ $color }} text-white font-bold ring-2 ring-offset-1 ring-blue-500' : 'bg-gray-100 hover:bg-gray-200 text-gray-600'"
                                             class="w-8 h-8 flex items-center justify-center rounded text-sm transition-all shadow-sm">
                                            {{ $val }}
                                        </div>
                                    </label>
                                @endforeach
                            </div>
                        </div>

                        <div class="lg:col-span-5">
                            <label class="block text-xs font-medium text-gray-500 mb-1">Evidence Note</label>
                            <textarea id="ev_{{ $code }}" class="w-full text-sm rounded border-gray-300 px-2 py-1 border" rows="2" placeholder="What did the client say or show?" @change="saveQ('{{ $code }}', '{{ addslashes($pillarName) }}', '{{ addslashes($text) }}')">{{ $ans->evidence_note ?? '' }}</textarea>
                        </div>

                        <div class="lg:col-span-2">
                            <label class="block text-xs font-medium text-gray-500 mb-1">Source Type</label>
                            <select id="src_{{ $code }}" class="w-full text-sm rounded border-gray-300 px-2 py-1 border" @change="saveQ('{{ $code }}', '{{ addslashes($pillarName) }}', '{{ addslashes($text) }}')">
                                <option value="interview" {{ ($ans->source_type ?? '') == 'interview' ? 'selected' : '' }}>Interview</option>
                                <option value="document" {{ ($ans->source_type ?? '') == 'document' ? 'selected' : '' }}>Document</option>
                                <option value="observation" {{ ($ans->source_type ?? '') == 'observation' ? 'selected' : '' }}>Observation</option>
                                <option value="workshop" {{ ($ans->source_type ?? '') == 'workshop' ? 'selected' : '' }}>Workshop</option>
                                <option value="system_data" {{ ($ans->source_type ?? '') == 'system_data' ? 'selected' : '' }}>System Data</option>
                            </select>
                        </div>

                        <div class="lg:col-span-2">
                            <label class="block text-xs font-medium text-gray-500 mb-1">Confidence</label>
                            <select id="conf_{{ $code }}" class="w-full text-sm rounded border-gray-300 px-2 py-1 border" @change="saveQ('{{ $code }}', '{{ addslashes($pillarName) }}', '{{ addslashes($text) }}')">
                                <option value="low" {{ ($ans->confidence ?? '') == 'low' ? 'selected' : '' }}>Low</option>
                                <option value="medium" {{ ($ans->confidence ?? 'medium') == 'medium' ? 'selected' : '' }}>Medium</option>
                                <option value="high" {{ ($ans->confidence ?? '') == 'high' ? 'selected' : '' }}>High</option>
                            </select>
                        </div>
                        <div class="lg:col-span-3">
                            <label class="block text-xs font-medium text-gray-500 mb-1">Respondent Role</label>
                            <input id="role_{{ $code }}" type="text" value="{{ $ans->respondent_role ?? '' }}" class="w-full text-sm rounded border-gray-300 px-2 py-1 border" placeholder="Service Manager" @change="saveQ('{{ $code }}', '{{ addslashes($pillarName) }}', '{{ addslashes($text) }}')">
                        </div>
                        <div class="lg:col-span-4">
                            <label class="block text-xs font-medium text-gray-500 mb-1">Document Source</label>
                            <input id="doc_{{ $code }}" type="text" value="{{ $ans->document_source ?? '' }}" class="w-full text-sm rounded border-gray-300 px-2 py-1 border" placeholder="Incident Report Q1" @change="saveQ('{{ $code }}', '{{ addslashes($pillarName) }}', '{{ addslashes($text) }}')">
                        </div>
                        @if($assessment->report_tier === 'Tier 2 Full')
                            <div class="lg:col-span-5">
                                <label class="block text-xs font-medium text-gray-500 mb-1">Stakeholder Divergence Note</label>
                                <textarea id="div_{{ $code }}" class="w-full text-sm rounded border-gray-300 px-2 py-1 border" rows="2" @change="saveQ('{{ $code }}', '{{ addslashes($pillarName) }}', '{{ addslashes($text) }}')">{{ $ans->stakeholder_divergence_note ?? '' }}</textarea>
                            </div>
                        @endif
                    </div>
                </div>
            @endforeach

            <!-- Pillar wrap-up -->
            <div class="bg-gray-50 p-6 border-t border-gray-200">
                <h4 class="font-bold text-gray-700 mb-3 text-sm uppercase">Pillar Wrap-up</h4>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-xs text-gray-500 mb-1">Pillar Commentary</label>
                        <textarea class="w-full text-sm rounded border-gray-300 border p-2" rows="3" @change="savePillarContext('{{ addslashes($pillarName) }}', this.value, '', '')">{{ $savedPillars[$pillarName]->commentary ?? '' }}</textarea>
                    </div>
                    <div>
                        <label class="block text-xs text-gray-500 mb-1">Key Risks</label>
                        <textarea class="w-full text-sm rounded border-gray-300 border p-2" rows="3" @change="savePillarContext('{{ addslashes($pillarName) }}', '', this.value, '')">{{ $savedPillars[$pillarName]->key_risks ?? '' }}</textarea>
                    </div>
                    <div>
                        <label class="block text-xs text-gray-500 mb-1">Immediate Actions</label>
                        <textarea class="w-full text-sm rounded border-gray-300 border p-2" rows="3" @change="savePillarContext('{{ addslashes($pillarName) }}', '', '', this.value)">{{ $savedPillars[$pillarName]->immediate_actions ?? '' }}</textarea>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endforeach

    <!-- Global Fields -->
    <div class="bg-white rounded-lg shadow mb-10 overflow-hidden">
        <div class="bg-gray-800 text-white px-6 py-4">
            <h3 class="font-bold">Global Assessment Summary & ITSM Details</h3>
        </div>
        <div class="p-6 grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm text-gray-600">Service Criticality</label>
                <select @change="saveContext" x-model="context.service_criticality" class="mt-1 block w-full rounded border-gray-300 p-2 border">
                    <option value="">Select...</option>
                    <option>Low</option>
                    <option>Medium</option>
                    <option>High</option>
                    <option>Critical</option>
                </select>
            </div>
            <div>
                <label class="block text-sm text-gray-600">Annual Service Cost</label>
                <input type="number" min="0" step="0.01" @change="saveContext" x-model="context.annual_service_cost" class="mt-1 block w-full rounded border-gray-300 p-2 border">
            </div>
            <div>
                <label class="block text-sm text-gray-600">Service Hours</label>
                <input type="text" @change="saveContext" x-model="context.service_hours" class="mt-1 block w-full rounded border-gray-300 p-2 border">
            </div>
            <div>
                <label class="block text-sm text-gray-600">Primary Support Model</label>
                <input type="text" @change="saveContext" x-model="context.primary_support_model" class="mt-1 block w-full rounded border-gray-300 p-2 border">
            </div>
            <div class="md:col-span-2">
                <label class="block text-sm text-gray-600">Vendor Landscape Summary</label>
                <textarea @change="saveContext" x-model="context.vendor_landscape_summary" class="mt-1 block w-full rounded border-gray-300 p-2 border" rows="3"></textarea>
            </div>
            <div class="md:col-span-2 mt-4 pt-4 border-t"></div>
            <div class="md:col-span-2">
                <label class="block text-sm text-gray-600">Overall Assessor Comment</label>
                <textarea @change="saveContext" x-model="context.overall_assessor_comment" class="mt-1 block w-full rounded border-gray-300 p-2 border" rows="3"></textarea>
            </div>
            <div>
                <label class="block text-sm text-gray-600">Top Incident Themes</label>
                <textarea @change="saveContext" x-model="context.top_incident_themes" class="mt-1 block w-full rounded border-gray-300 p-2 border" rows="3"></textarea>
            </div>
            <div>
                <label class="block text-sm text-gray-600">Top Problem Themes</label>
                <textarea @change="saveContext" x-model="context.top_problem_themes" class="mt-1 block w-full rounded border-gray-300 p-2 border" rows="3"></textarea>
            </div>
            <div>
                <label class="block text-sm text-gray-600">Service Debt Notes</label>
                <textarea @change="saveContext" x-model="context.service_debt_notes" class="mt-1 block w-full rounded border-gray-300 p-2 border" rows="3"></textarea>
            </div>
            <div>
                <label class="block text-sm text-gray-600">Top 5 Risks</label>
                <textarea @change="saveContext" x-model="context.top_5_risks" class="mt-1 block w-full rounded border-gray-300 p-2 border" rows="3"></textarea>
            </div>
            <div class="md:col-span-2">
                <label class="block text-sm text-gray-600">Executive Summary Override (Optional)</label>
                <textarea @change="saveContext" x-model="context.executive_summary_override" class="mt-1 block w-full rounded border-gray-300 p-2 border" rows="3"></textarea>
            </div>
            <div class="md:col-span-2">
                <label class="block text-sm text-gray-600">Recommended Next Step</label>
                <input type="text" @change="saveContext" x-model="context.recommended_next_step" class="mt-1 block w-full rounded border-gray-300 p-2 border">
            </div>
            @if($assessment->report_tier === 'Tier 2 Full')
                <div class="md:col-span-2 mt-4 pt-4 border-t">
                    <h4 class="font-bold text-gray-700">Stakeholder Notes</h4>
                </div>
                <div>
                    <label class="block text-sm text-gray-600">Sponsor / CIO Position</label>
                    <textarea @change="saveContext" x-model="context.sponsor_position" class="mt-1 block w-full rounded border-gray-300 p-2 border" rows="3"></textarea>
                </div>
                <div>
                    <label class="block text-sm text-gray-600">Operational Position</label>
                    <textarea @change="saveContext" x-model="context.operational_position" class="mt-1 block w-full rounded border-gray-300 p-2 border" rows="3"></textarea>
                </div>
                <div class="md:col-span-2">
                    <label class="block text-sm text-gray-600">Divergence Areas</label>
                    <textarea @change="saveContext" x-model="context.divergence_areas" class="mt-1 block w-full rounded border-gray-300 p-2 border" rows="2" placeholder="One divergence area per line"></textarea>
                </div>
            @endif
        </div>
    </div>

    <!-- AI Agent Recommendations -->
    <div class="mt-8 mb-12">
        <div class="flex items-center gap-3 mb-6">
            <div class="w-10 h-10 bg-gradient-to-br from-blue-600 to-indigo-700 rounded-xl shadow-lg flex items-center justify-center">
                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>
            </div>
            <div>
                <h3 class="text-xl font-black text-slate-800 tracking-tight">AI Agent Recommendations</h3>
                <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Real-time Service Insights</p>
            </div>
        </div>

        @if($assessment->ai_draft_json)
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <div class="bg-white rounded-3xl p-8 border border-slate-100 shadow-sm">
                    <h4 class="text-[10px] font-black text-blue-600 uppercase tracking-[0.2em] mb-4">Executive Summary</h4>
                    <p class="text-slate-700 font-medium leading-relaxed">
                        {{ $assessment->ai_draft_json['executive_summary'] }}
                    </p>
                </div>
                <div class="bg-slate-900 rounded-3xl p-8 text-white shadow-2xl">
                    <h4 class="text-[10px] font-black text-blue-400 uppercase tracking-[0.2em] mb-4">Strategic Recommendations</h4>
                    <ul class="space-y-3">
                        @foreach($assessment->ai_draft_json['recommendations'] ?? [] as $rec)
                            <li class="flex items-start gap-3">
                                <svg class="w-4 h-4 text-blue-400 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"></path></svg>
                                <span class="text-sm font-bold text-slate-300">{{ $rec }}</span>
                            </li>
                        @endforeach
                    </ul>
                </div>
            </div>
        @else
            <div class="bg-slate-50 border-2 border-dashed border-slate-200 rounded-3xl p-12 text-center">
                <div class="w-16 h-16 bg-white rounded-2xl shadow-sm flex items-center justify-center mx-auto mb-4">
                    <svg class="w-8 h-8 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>
                </div>
                <h4 class="text-sm font-bold text-slate-400">No agent analysis available yet.</h4>
                <p class="text-xs text-slate-400 mt-1">Generate an AI draft to see strategic recommendations for this ITSM service.</p>
            </div>
        @endif
    </div>
</div>

<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('scorerComponent', () => ({
        context: {
            call_date: @json($assessment->call_date),
            call_type: @json($assessment->call_type),
            call_attendees: @json($assessment->call_attendees),
            call_summary: @json($assessment->call_summary),
            client_concerns: @json($assessment->client_concerns),
            next_agreed_action: @json($assessment->next_agreed_action),
            service_context: @json($assessment->service_context),
            regulatory_context: @json($assessment->regulatory_context),
            sponsor_name: @json($assessment->sponsor_name),
            interview_count: @json($assessment->interview_count),
            documents_reviewed: @json(implode("\n", $assessment->documents_reviewed ?? [])),
            
            // ITSM specific
            service_criticality: @json($assessment->service_criticality),
            annual_service_cost: @json($assessment->annual_service_cost),
            service_hours: @json($assessment->service_hours),
            primary_support_model: @json($assessment->primary_support_model),
            vendor_landscape_summary: @json($assessment->vendor_landscape_summary),
            top_incident_themes: @json($assessment->top_incident_themes),
            top_problem_themes: @json($assessment->top_problem_themes),
            service_debt_notes: @json($assessment->service_debt_notes),
            
            // Shared globals
            sponsor_position: @json($assessment->sponsor_position),
            operational_position: @json($assessment->operational_position),
            divergence_areas: @json(implode("\n", $assessment->divergence_areas ?? [])),
            overall_assessor_comment: @json($assessment->overall_assessor_comment),
            top_5_risks: @json($assessment->top_5_risks),
            executive_summary_override: @json($assessment->executive_summary_override),
            recommended_next_step: @json($assessment->recommended_next_step)
        },
        
        saveContext() {
            fetch("{{ route('admin.assessments.autosave', $assessment) }}", {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({ fields: this.context })
            });
        },
        
        saveQ(code, pillar, question) {
            const scoreInp = document.querySelector(`input[name="score_${code}"]:checked`);
            if (!scoreInp) return;
            
            const payload = {
                question_code: code,
                pillar_name: pillar,
                question: question,
                score: scoreInp.value,
                evidence_note: document.getElementById(`ev_${code}`).value,
                source_type: document.getElementById(`src_${code}`).value,
                respondent_role: document.getElementById(`role_${code}`).value,
                document_source: document.getElementById(`doc_${code}`).value,
                stakeholder_divergence_note: document.getElementById(`div_${code}`)?.value || null,
                confidence: document.getElementById(`conf_${code}`).value
            };
            
            fetch("{{ route('admin.assessments.autosave', $assessment) }}", {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify(payload)
            }).then(r => r.json()).then(res => {
                let savedLabel = document.getElementById(`saved_${code}`);
                savedLabel.style.display = 'inline';
                setTimeout(() => savedLabel.style.display = 'none', 3000);
                
                document.getElementById(`row_${code}`).classList.remove('border-l-4', 'border-yellow-400');
                
                // Update live score
                if(res.calculated) {
                    let d = document.getElementById('live_overall_score');
                    d.innerText = parseFloat(res.calculated.overall_score).toFixed(1);
                    d.className = `px-2 py-1 rounded text-white font-bold bg-${res.calculated.rag_status === 'Green' ? 'green' : (res.calculated.rag_status === 'Amber' ? 'yellow' : 'red')}-500`;
                }
            });
        },
        
        savePillarContext(pillar, comm, risk, action) {
            let activeEl = document.activeElement;
            let val = activeEl.value;
            let field = '';
            if (activeEl.previousElementSibling.innerText.includes('Commentary')) field = 'commentary';
            if (activeEl.previousElementSibling.innerText.includes('Risks')) field = 'key_risks';
            if (activeEl.previousElementSibling.innerText.includes('Actions')) field = 'immediate_actions';
            
            if(!field) return;

            let payload = {
                pillar_level_update: true,
                pillar_name: pillar
            };
            payload[field] = val;

            fetch("{{ route('admin.assessments.autosave', $assessment) }}", {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify(payload)
            });
        }
    }));
});
</script>
@endsection
