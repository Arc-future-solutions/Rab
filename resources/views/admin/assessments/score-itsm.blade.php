@php
$itsm_questions = [
    'P1 — Service Governance & Ownership' => [
        'P1_Q1' => 'Service ownership is clearly defined across business and IT.',
        'P1_Q2' => 'The service catalogue exists and is maintained.',
        'P1_Q3' => 'Governance forums for service performance are active and effective.',
        'P1_Q4' => 'SLA and KPI ownership is clearly assigned.',
        'P1_Q5' => 'Decision-making and escalation paths are structured.'
    ],
    'P2 — Incident & Major Incident Management' => [
        'P2_Q1' => 'Incident management is defined and consistently followed.',
        'P2_Q2' => 'MTTR is tracked and actively improved.',
        'P2_Q3' => 'Major incident handling is structured and controlled.',
        'P2_Q4' => 'Escalation paths are clear and respected.',
        'P2_Q5' => 'Incident communication is timely and effective.',
        'P2_Q6' => 'Incident trends are analysed.'
    ],
    'P3 — Service Request Management' => [
        'P3_Q1' => 'The service request catalogue is defined and maintained.',
        'P3_Q2' => 'Requests are clearly separated from incidents.',
        'P3_Q3' => 'Standard requests are documented and repeatable.',
        'P3_Q4' => 'Request fulfilment times are defined and tracked.',
        'P3_Q5' => 'Self-service capability exists where appropriate.',
        'P3_Q6' => 'Request fulfilment is automated where possible.'
    ],
    'P4 — Problem Management' => [
        'P4_Q1' => 'Problems are formally logged and prioritised.',
        'P4_Q2' => 'Root Cause Analysis is performed effectively.',
        'P4_Q3' => 'A Known Error Database is maintained where appropriate.',
        'P4_Q4' => 'Recurring incidents reduce over time.',
        'P4_Q5' => 'Problem ownership is clearly assigned.',
        'P4_Q6' => 'Corrective fixes are implemented, not just analysed.'
    ],
    'P5 — Change & Release Management' => [
        'P5_Q1' => 'The change process is controlled and enforced.',
        'P5_Q2' => 'The Change Advisory Board is effective where required.',
        'P5_Q3' => 'Emergency changes are managed appropriately.',
        'P5_Q4' => 'Release planning is structured and controlled.',
        'P5_Q5' => 'Deployment success rate is tracked.',
        'P5_Q6' => 'Change failure rate is monitored and acted upon.'
    ],
    'P6 — Service Performance, SLA & Reporting' => [
        'P6_Q1' => 'SLAs are clearly defined and understood.',
        'P6_Q2' => 'KPIs reflect meaningful service outcomes.',
        'P6_Q3' => 'Service dashboards exist and are used.',
        'P6_Q4' => 'Service performance is reviewed regularly with stakeholders.',
        'P6_Q5' => 'SLA breaches are analysed and acted upon.',
        'P6_Q6' => 'Business satisfaction is measured.'
    ],
    'P7 — Service Transition & BAU Readiness' => [
        'P7_Q1' => 'Handover from project to BAU is structured and controlled.',
        'P7_Q2' => 'The support model is clearly defined.',
        'P7_Q3' => 'Operational documentation is complete and usable.',
        'P7_Q4' => 'Knowledge transfer has been completed effectively.',
        'P7_Q5' => 'Training has been delivered and validated.',
        'P7_Q6' => 'Early life support or hypercare is structured.'
    ],
    'P8 — Service Operations & Support Model' => [
        'P8_Q1' => 'Service Desk effectiveness is understood and managed.',
        'P8_Q2' => 'Ticket routing and ownership are clear.',
        'P8_Q3' => 'Escalation operates effectively in practice.',
        'P8_Q4' => 'On-call or out-of-hours support is defined where needed.',
        'P8_Q5' => 'Workload is manageable across support teams.',
        'P8_Q6' => 'Backlog is controlled and visible.'
    ],
    'P9 — Supplier & Vendor Service Management' => [
        'P9_Q1' => 'Vendor SLAs are clearly defined.',
        'P9_Q2' => 'Supplier performance is measured.',
        'P9_Q3' => 'Accountability between internal and vendor teams is clear.',
        'P9_Q4' => 'Internal and external service teams are integrated effectively.',
        'P9_Q5' => 'Contractual commitments reflect operational reality.',
        'P9_Q6' => 'Vendor dependency risks are understood and managed.'
    ],
    'P10 — Operational Resilience & Continuity' => [
        'P10_Q1' => 'Disaster recovery plans exist and are current.',
        'P10_Q2' => 'Business continuity plans exist and are current.',
        'P10_Q3' => 'DR and continuity testing is performed.',
        'P10_Q4' => 'Backup and restore processes are reliable.',
        'P10_Q5' => 'Critical services are identified and prioritised.',
        'P10_Q6' => 'Failure scenarios are understood and planned for.'
    ],
    'P11 — Automation, Tooling & Service Optimisation' => [
        'P11_Q1' => 'Service operations are supported by appropriate tooling.',
        'P11_Q2' => 'Manual processes are reduced where possible.',
        'P11_Q3' => 'Self-service and knowledge capability are effective.',
        'P11_Q4' => 'Request and workflow automation is in place where valuable.',
        'P11_Q5' => 'Continuous improvement activity is active.',
        'P11_Q6' => 'AI or predictive capability is considered where relevant.'
    ]
];

$savedResponses = $assessment->questionResponses->keyBy(function($item) {
    return explode(':', $item->question)[0];
});
$savedPillars = $assessment->pillarScores->keyBy('name');
@endphp

@extends('admin.layouts.app')

@section('header')
<div class="flex justify-between items-center w-full" x-data="{
    answered: {{ $assessment->questionResponses->count() }},
    total: 65
}">
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
        <template x-if="answered / total >= 0.8">
            <form action="{{ route('admin.assessments.generateReport', $assessment->id) }}" method="POST" class="inline m-0">
                @csrf
                <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded text-sm font-bold shadow hover:bg-blue-700 animate-pulse">Generate AI Draft</button>
            </form>
        </template>
        <button onclick="window.location='{{ route('admin.assessments.show', $assessment->id) }}'" class="bg-gray-800 text-white px-4 py-2 rounded text-sm hover:bg-gray-700">Done / Exit</button>
    </div>
</div>
@endsection

@section('content')
<div x-data="scorerComponent()">

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
        </div>
    </div>
</div>

<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('scorerComponent', () => ({
        context: {
            call_date: '{{ $assessment->call_date }}',
            call_type: '{{ $assessment->call_type }}',
            call_attendees: '{{ $assessment->call_attendees }}',
            call_summary: '{{ addslashes($assessment->call_summary) }}',
            client_concerns: '{{ addslashes($assessment->client_concerns) }}',
            next_agreed_action: '{{ addslashes($assessment->next_agreed_action) }}',
            
            // ITSM specific
            service_criticality: '{{ addslashes($assessment->service_criticality) }}',
            service_hours: '{{ addslashes($assessment->service_hours) }}',
            primary_support_model: '{{ addslashes($assessment->primary_support_model) }}',
            vendor_landscape_summary: '{{ addslashes($assessment->vendor_landscape_summary) }}',
            top_incident_themes: '{{ addslashes($assessment->top_incident_themes) }}',
            top_problem_themes: '{{ addslashes($assessment->top_problem_themes) }}',
            service_debt_notes: '{{ addslashes($assessment->service_debt_notes) }}',
            
            // Shared globals
            overall_assessor_comment: '{{ addslashes($assessment->overall_assessor_comment) }}',
            top_5_risks: '{{ addslashes($assessment->top_5_risks) }}',
            executive_summary_override: '{{ addslashes($assessment->executive_summary_override) }}',
            recommended_next_step: '{{ addslashes($assessment->recommended_next_step) }}'
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
