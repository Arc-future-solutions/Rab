@extends('admin.layouts.app')

@section('header', 'Assessments')

@section('content')
<div class="bg-gray-50 p-6 rounded-lg shadow-inner min-h-screen" x-data="{ tab: 'internal' }">
    
    <!-- Tabs Header -->
    <div class="flex items-center gap-6 mb-8 border-b border-gray-200">
        <button @click="tab = 'internal'" 
                :class="tab === 'internal' ? 'border-blue-600 text-blue-600' : 'border-transparent text-gray-400 hover:text-gray-600'"
                class="pb-4 px-2 font-black uppercase tracking-widest text-sm border-b-4 transition-all">
            Formal Assessments (Clients)
        </button>
        <button @click="tab = 'external'" 
                :class="tab === 'external' ? 'border-amber-500 text-amber-600' : 'border-transparent text-gray-400 hover:text-gray-600'"
                class="pb-4 px-2 font-black uppercase tracking-widest text-sm border-b-4 transition-all flex items-center gap-2">
            Public Website Assessments
            <span class="bg-amber-100 text-amber-700 px-2 py-0.5 rounded-full text-[10px]">{{ count($publicAssessments) }}</span>
        </button>
    </div>

    <!-- INTERNAL ASSESSMENTS TAB -->
    <div x-show="tab === 'internal'" class="bg-white shadow rounded-lg" x-data="assessmentTable()">
        <div class="px-6 py-4 border-b flex justify-between items-center bg-gray-50 rounded-t-lg">
            <form class="flex gap-4 items-center w-full" method="GET" action="{{ route('admin.assessments.index') }}">
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Search Client or Assessment..." class="rounded-md border-gray-300 shadow-sm px-4 py-2 border focus:ring-blue-500 focus:border-blue-500 min-w-[250px]">
                
                <select name="type" class="rounded-md border-gray-300 shadow-sm px-4 py-2 border focus:ring-blue-500 focus:border-blue-500" onchange="this.form.submit()">
                    <option value="">All Types</option>
                    <option value="PHI" {{ request('type') == 'PHI' ? 'selected' : '' }}>PHI</option>
                    <option value="ITSM" {{ request('type') == 'ITSM' ? 'selected' : '' }}>ITSM</option>
                    <option value="PIR" {{ request('type') == 'PIR' ? 'selected' : '' }}>PIR</option>
                    <option value="SIR" {{ request('type') == 'SIR' ? 'selected' : '' }}>SIR</option>
                </select>
                
                <select name="rag_status" class="rounded-md border-gray-300 shadow-sm px-4 py-2 border focus:ring-blue-500 focus:border-blue-500" onchange="this.form.submit()">
                    <option value="">All RAG</option>
                    <option value="Red" {{ request('rag_status') == 'Red' ? 'selected' : '' }}>Red</option>
                    <option value="Amber" {{ request('rag_status') == 'Amber' ? 'selected' : '' }}>Amber</option>
                    <option value="Green" {{ request('rag_status') == 'Green' ? 'selected' : '' }}>Green</option>
                </select>

                <select name="status" class="rounded-md border-gray-300 shadow-sm px-4 py-2 border focus:ring-blue-500 focus:border-blue-500" onchange="this.form.submit()">
                    <option value="">All Statuses</option>
                    <option value="draft" {{ request('status') == 'draft' ? 'selected' : '' }}>Draft</option>
                    <option value="in_progress" {{ request('status') == 'in_progress' ? 'selected' : '' }}>In Progress</option>
                    <option value="approved" {{ request('status') == 'approved' ? 'selected' : '' }}>Approved</option>
                </select>
                
                <div class="ml-auto flex gap-2">
                    <a href="{{ route('admin.assessments.create') }}" class="bg-blue-600 text-white px-4 py-2 rounded shadow hover:bg-blue-700">Create New Assessment</a>
                </div>
            </form>
        </div>

        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-white">
                <tr>
                    <th @click="sortBy('client_name')" class="cursor-pointer px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider text-[10px]">Client</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider text-[10px]">Assessment Name</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider text-[10px]">Type</th>
                    <th @click="sortBy('overall_score')" class="cursor-pointer px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider text-[10px]">Score</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider text-[10px]">Status</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider text-[10px]">Note</th>
                    <th @click="sortBy('created_at')" class="cursor-pointer px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider text-[10px]">Date</th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider text-[10px]">Actions</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                <template x-for="assessment in sortedData" :key="assessment.id">
                    <tr @click="window.location = `/admin/assessments/${assessment.id}`" class="hover:bg-gray-50 cursor-pointer">
                        <td class="px-6 py-4 font-black text-slate-800" x-text="assessment.client_name"></td>
                        <td class="px-6 py-4 text-blue-600 font-bold" x-text="assessment.name"></td>
                        <td class="px-6 py-4">
                            <span class="text-xs font-black uppercase tracking-widest text-slate-400" x-text="assessment.type"></span>
                        </td>
                        <td class="px-6 py-4">
                            <span :class="{
                                'bg-red-100 text-red-800': assessment.rag_status === 'Red',
                                'bg-amber-100 text-amber-800': assessment.rag_status === 'Amber',
                                'bg-green-100 text-green-800': assessment.rag_status === 'Green',
                            }" class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full" x-text="(assessment.overall_score * 1).toFixed(1)"></span>
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-500 capitalize" x-text="assessment.status.replace('_', ' ')"></td>
                        <td class="px-6 py-4 text-[10px] text-gray-400 font-bold italic truncate max-w-[150px]" x-text="assessment.note || '—'"></td>
                        <td class="px-6 py-4 text-sm text-gray-500" x-text="new Date(assessment.created_at).toLocaleDateString()"></td>
                        <td class="px-6 py-4 text-right">
                            <template x-if="assessment.status !== 'approved'">
                                <a :href="assessment.type === 'PHI' ? `/admin/assessments/phi/${assessment.id}/score` : `/admin/assessments/itsm/${assessment.id}/score`" 
                                   @click.stop
                                   class="bg-blue-600 text-white px-3 py-1 rounded text-[10px] font-black uppercase tracking-widest hover:bg-blue-700 transition shadow-sm inline-flex items-center gap-2">
                                    Continue
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"></path></svg>
                                </a>
                            </template>
                        </td>
                    </tr>
                </template>
            </tbody>
        </table>
    </div>

    <!-- EXTERNAL (PUBLIC) ASSESSMENTS TAB -->
    <div x-show="tab === 'external'" class="bg-white shadow-xl border border-gray-200 rounded-2xl overflow-hidden" x-data="{ showLeadModal: false, selectedLead: null }">
        <div class="px-8 py-6 border-b bg-amber-50/50 flex justify-between items-center">
            <div>
                <h2 class="text-sm font-black text-amber-900 uppercase tracking-widest">Incoming Website Health-Checks</h2>
                <p class="text-xs text-amber-700 font-bold mt-1 tracking-tighter italic">These are snapshot submissions from the public health checks.</p>
            </div>
            <div class="bg-amber-100 text-amber-700 px-3 py-1 rounded-full text-[10px] font-black uppercase tracking-widest border border-amber-200">
                {{ count($publicAssessments) }} Total
            </div>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-100">
                <thead class="bg-gray-50/30">
                    <tr>
                        <th class="px-8 py-4 text-left text-[10px] font-black text-slate-400 uppercase tracking-widest">Lead / Company</th>
                        <th class="px-8 py-4 text-left text-[10px] font-black text-slate-400 uppercase tracking-widest text-center">Framework</th>
                        <th class="px-8 py-4 text-left text-[10px] font-black text-slate-400 uppercase tracking-widest text-center">Health Score</th>
                        <th class="px-8 py-4 text-left text-[10px] font-black text-slate-400 uppercase tracking-widest text-center">Priority</th>
                        <th class="px-8 py-4 text-left text-[10px] font-black text-slate-400 uppercase tracking-widest">Note</th>
                        <th class="px-8 py-4 text-left text-[10px] font-black text-slate-400 uppercase tracking-widest text-right">Date Received</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-50">
                    @foreach($publicAssessments as $lead)
                        <tr @click="window.location.href = '{{ route('admin.assessments.show', $lead->id) }}'" class="hover:bg-amber-50/50 cursor-pointer group transition-colors">
                            <td class="px-8 py-5">
                                <div class="text-sm font-black text-slate-900 uppercase tracking-tight group-hover:text-amber-600 transition-colors">{{ $lead->name }}</div>
                                <div class="text-[10px] text-slate-400 font-bold uppercase tracking-widest mt-0.5">{{ $lead->company }}</div>
                            </td>
                            <td class="px-8 py-5 text-center">
                                <span class="bg-slate-100 text-slate-500 px-2 py-1 rounded text-[10px] font-black uppercase tracking-widest">{{ $lead->type }}</span>
                            </td>
                            <td class="px-8 py-5 text-center">
                                <span class="px-3 py-1.5 inline-flex text-xs font-black rounded-lg border shadow-sm
                                    @if($lead->rag_status === 'Red') bg-red-500/10 text-red-600 border-red-200 @elseif($lead->rag_status === 'Amber') bg-amber-500/10 text-amber-600 border-amber-200 @else bg-green-500/10 text-green-600 border-green-200 @endif">
                                    {{ number_format($lead->overall_score, 1) }}
                                </span>
                            </td>
                            <td class="px-8 py-5 text-center">
                                @if($lead->priority === 'High')
                                    <span class="bg-red-500 text-white px-2 py-0.5 rounded text-[10px] font-black uppercase animate-pulse shadow-lg shadow-red-500/20 tracking-tighter">High Risk</span>
                                @elseif($lead->priority === 'Medium')
                                    <span class="bg-amber-100 text-amber-700 px-2 py-0.5 rounded text-[10px] font-black uppercase tracking-tighter">Medium Risk</span>
                                @else
                                    <span class="text-slate-300 text-[10px] font-black uppercase tracking-widest">{{ $lead->priority ?: 'Normal' }}</span>
                                @endif
                            </td>
                            <td class="px-8 py-5">
                                @php
                                    $questionNotes = collect($lead->answers_json ?? [])
                                        ->filter(fn($v, $k) => str_starts_with($k, 'note_') && !empty($v))
                                        ->values();
                                @endphp
                                <div class="text-[10px] text-slate-400 font-bold italic truncate max-w-[150px]" title="{{ $questionNotes->implode("\n") }}">
                                    @if($questionNotes->isNotEmpty())
                                        <span class="text-blue-600 font-black">●</span> 
                                        {{ Str::limit($questionNotes->first(), 25) }}
                                        @if($questionNotes->count() > 1)
                                            <span class="text-slate-300 ml-1">+{{ $questionNotes->count() - 1 }} more</span>
                                        @endif
                                    @else
                                        <span class="text-slate-200">—</span>
                                    @endif
                                </div>
                            </td>
                            <td class="px-8 py-5 text-right text-[10px] text-slate-400 font-black uppercase">
                                {{ $lead->created_at->format('d M — H:i') }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        {{-- Lead Assessment Detail Modal --}}
        <div x-show="showLeadModal" 
             class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm"
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="opacity-0 scale-95"
             x-transition:enter-end="opacity-100 scale-100"
             x-cloak>
            <div class="bg-white rounded-3xl shadow-2xl w-full max-w-2xl overflow-hidden border border-white/20" @click.away="showLeadModal = false">
                <div class="p-8 border-b border-gray-100 bg-amber-50/50 flex justify-between items-start">
                    <div>
                        <h2 class="text-2xl font-black text-slate-900 tracking-tight" x-text="selectedLead?.name"></h2>
                        <p class="text-sm text-amber-600 font-bold mt-1 uppercase tracking-widest flex items-center gap-2" x-text="selectedLead?.company"></p>
                    </div>
                    <button @click="showLeadModal = false" class="p-2 hover:bg-white rounded-xl transition-colors shadow-sm">
                        <svg class="w-6 h-6 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                    </button>
                </div>
                
                <div class="p-8 h-[400px] overflow-y-auto">
                    <div class="grid grid-cols-2 gap-8 mb-8 pb-8 border-b border-gray-100">
                        <div>
                            <h4 class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">Framework Type</h4>
                            <p class="text-lg font-black text-slate-800" x-text="selectedLead?.type + ' Health-Check'"></p>
                        </div>
                        <div>
                            <h4 class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">Overall Score</h4>
                            <div class="flex items-center gap-2">
                                <span class="text-3xl font-black text-slate-800" x-text="(selectedLead?.overall_score * 1).toFixed(1)"></span>
                                <span :class="{
                                    'bg-red-500': selectedLead?.rag_status === 'Red',
                                    'bg-amber-500': selectedLead?.rag_status === 'Amber',
                                    'bg-green-500': selectedLead?.rag_status === 'Green',
                                }" class="w-3 h-3 rounded-full"></span>
                            </div>
                        </div>
                    </div>

                    <h4 class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-4">Assessment Responses</h4>
                    <div class="space-y-4">
                        <template x-if="selectedLead?.answers_json">
                            <template x-for="(val, key) in selectedLead.answers_json" :key="key">
                                <div class="bg-gray-50 rounded-xl p-4 border border-gray-100">
                                    <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1" x-text="key.replace(/_/g, ' ')"></p>
                                    <p class="text-sm font-bold text-slate-700" x-text="val"></p>
                                </div>
                            </template>
                        </template>
                    </div>
                </div>

                <div class="px-8 py-6 bg-slate-900 flex justify-between items-center">
                    <span class="text-slate-400 text-xs font-bold italic">Lead received via public diagnostic tool.</span>
                    <div class="flex gap-4">
                        <a :href="`/admin/leads`" class="text-white text-xs font-black uppercase tracking-widest border border-white/20 px-6 py-2.5 rounded-xl hover:bg-white/10 transition-all">
                            View All Leads
                        </a>
                        <form :action="selectedLead ? `/admin/leads/${selectedLead.id}/convert` : '#'" method="POST">
                            @csrf
                            <button type="submit" class="bg-blue-600 text-white px-6 py-2.5 rounded-xl font-black uppercase tracking-tighter hover:bg-blue-500 transition-all">
                                Convert to Client &rarr;
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('alpine:init', () => {
    let sourceData = @json($assessments);
    sourceData = sourceData.map(a => {
        a.client_name = a.client ? a.client.company_name : 'Unknown';
        return a;
    });

    Alpine.data('assessmentTable', () => ({
        data: sourceData,
        sortCol: 'created_at',
        sortAsc: false,
        
        sortBy(col) {
            if (this.sortCol === col) this.sortAsc = !this.sortAsc;
            else { this.sortCol = col; this.sortAsc = true; }
        },
        get sortedData() {
            return this.data.sort((a, b) => {
                let valA = a[this.sortCol] || '';
                let valB = b[this.sortCol] || '';
                if (valA < valB) return this.sortAsc ? -1 : 1;
                if (valA > valB) return this.sortAsc ? 1 : -1;
                return 0;
            });
        }
    }));
});
</script>
@endsection
