@php
$phi_questions = [
    'P1 — Governance & Decision-Making' => [
        'P1_Q1' => 'Decision rights are clearly defined and understood.',
        'P1_Q2' => 'There is a functioning SteerCo with real authority.',
        'P1_Q3' => 'Escalations are timely and effective.',
        'P1_Q4' => 'Risks are openly discussed rather than filtered.',
        'P1_Q5' => 'Programme reporting is accurate and unmanipulated.',
        'P1_Q6' => 'Roles and responsibilities are clearly defined.',
        'P1_Q7' => 'Sponsorship is active and visible.'
    ],
    'P2 — Planning & Delivery Control' => [
        'P2_Q1' => 'There is a credible integrated plan.',
        'P2_Q2' => 'The critical path is clearly identified and tracked.',
        'P2_Q3' => 'Dependencies are actively managed.',
        'P2_Q4' => 'RAID management is effective and up to date.',
        'P2_Q5' => 'Milestones are meaningful rather than artificial.',
        'P2_Q6' => 'Delays are acknowledged early rather than hidden.',
        'P2_Q7' => 'Re-planning is structured rather than reactive.'
    ],
    'P3 — Business Alignment & Value' => [
        'P3_Q1' => 'Business KPIs are clearly defined.',
        'P3_Q2' => 'A measurable baseline exists.',
        'P3_Q3' => 'Benefits are measurable.',
        'P3_Q4' => 'KPIs are owned by the business rather than IT alone.',
        'P3_Q5' => 'Sponsors are accountable for outcomes.',
        'P3_Q6' => 'KPIs are tracked during delivery.',
        'P3_Q7' => 'Dashboards exist for value tracking.',
        'P3_Q8' => 'Budget tracking is accurate.',
        'P3_Q9' => 'Overruns are identified early.',
        'P3_Q10' => 'Change costs are controlled.'
    ],
    'P4 — Change, Training & Adoption' => [
        'P4_Q1' => 'There is a structured training plan.',
        'P4_Q2' => 'Training is role-based.',
        'P4_Q3' => 'Training is completed or actively controlled.',
        'P4_Q4' => 'Attendance is tracked.',
        'P4_Q5' => 'Business SMEs are actively involved.',
        'P4_Q6' => 'Users contribute to design decisions.',
        'P4_Q7' => 'Business ownership is clear.',
        'P4_Q8' => 'Resistance points are identified.',
        'P4_Q9' => 'Change champions are in place.',
        'P4_Q10' => 'Adoption is measured.'
    ],
    'P5 — Data Readiness & Migration' => [
        'P5_Q1' => 'There is a defined data migration strategy.',
        'P5_Q2' => 'Data owners are clearly identified.',
        'P5_Q3' => 'Data quality is understood and measured.',
        'P5_Q4' => 'Data cleansing is actively managed.',
        'P5_Q5' => 'Mock migrations have been completed.',
        'P5_Q6' => 'Reconciliation is defined and tested.',
        'P5_Q7' => 'The business is validating migrated data.'
    ],
    'P6 — Solution & Process Fit' => [
        'P6_Q1' => 'Processes are clearly defined.',
        'P6_Q2' => 'AS-IS and TO-BE are understood.',
        'P6_Q3' => 'The solution aligns with business needs.',
        'P6_Q4' => 'Over-customisation is controlled.',
        'P6_Q5' => 'Key design decisions are validated by business.',
        'P6_Q6' => 'Gaps are understood and documented.',
        'P6_Q7' => 'Workarounds are understood and acceptable where necessary.'
    ],
    'P7 — Cutover & Go-Live Readiness' => [
        'P7_Q1' => 'Data migration is tested end to end.',
        'P7_Q2' => 'Data reconciliation is validated.',
        'P7_Q3' => 'A data freeze plan exists.',
        'P7_Q4' => 'Data sign-offs are defined.',
        'P7_Q5' => 'Day 1 business processes are defined.',
        'P7_Q6' => 'Roles and responsibilities are clear.',
        'P7_Q7' => 'Manual processes are identified.',
        'P7_Q8' => 'Dependencies are mapped.',
        'P7_Q9' => 'A detailed cutover runbook exists.',
        'P7_Q10' => 'Timeline is defined at practical level.',
        'P7_Q11' => 'Rehearsals have been completed.',
        'P7_Q12' => 'The support model is defined.',
        'P7_Q13' => 'Escalation paths are clear.',
        'P7_Q14' => 'Hypercare is planned.',
        'P7_Q15' => 'SLAs and support expectations are defined.'
    ],
    'P8 — Delivery Capability & Resourcing' => [
        'P8_Q1' => 'The team structure is fit for purpose.',
        'P8_Q2' => 'Key roles are filled.',
        'P8_Q3' => 'Capability gaps are known and addressed.',
        'P8_Q4' => 'SI or vendor is managed effectively.',
        'P8_Q5' => 'PMO is functioning properly.',
        'P8_Q6' => 'Workload is realistic.',
        'P8_Q7' => 'Team stability is maintained.'
    ],
    'P9 — Operational & Automation Readiness' => [
        'P9_Q1' => 'Reporting is automated where appropriate.',
        'P9_Q2' => 'Data is structured for analytics.',
        'P9_Q3' => 'Automation opportunities are identified.',
        'P9_Q4' => 'Automation or AI is considered in the roadmap where relevant.',
        'P9_Q5' => 'Processes are standardised enough for automation.'
    ]
];

// Combine existing answers into an easy lookup map
$savedResponses = $assessment->questionResponses->keyBy(function($item) {
    return explode(':', $item->question)[0];
});
$savedPillars = $assessment->pillarScores->keyBy('name');
@endphp

@extends('admin.layouts.app')

@section('header')
<div class="flex justify-between items-center w-full" x-data="{
    answered: {{ $assessment->questionResponses->count() }},
    total: 75
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
    @foreach($phi_questions as $pillarName => $questions)
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
            <h3 class="font-bold">Global Assessment Summary</h3>
        </div>
        <div class="p-6 grid grid-cols-1 md:grid-cols-2 gap-4">
            <div class="md:col-span-2">
                <label class="block text-sm text-gray-600">Overall Assessor Comment</label>
                <textarea @change="saveContext" x-model="context.overall_assessor_comment" class="mt-1 block w-full rounded border-gray-300 p-2 border" rows="3"></textarea>
            </div>
            <div>
                <label class="block text-sm text-gray-600">Top 5 Risks</label>
                <textarea @change="saveContext" x-model="context.top_5_risks" class="mt-1 block w-full rounded border-gray-300 p-2 border" rows="4"></textarea>
            </div>
            <div>
                <label class="block text-sm text-gray-600">Executive Summary Override (Optional)</label>
                <textarea @change="saveContext" x-model="context.executive_summary_override" class="mt-1 block w-full rounded border-gray-300 p-2 border" rows="4"></textarea>
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
