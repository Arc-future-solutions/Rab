@extends('admin.layouts.app')

@section('header', 'Leads')

@section('content')
<div class="bg-white shadow-xl border border-gray-200 rounded-2xl overflow-hidden" x-data="leadsTable()">
    <!-- Filters -->
    <div class="px-4 md:px-8 py-6 border-b border-gray-100 bg-gray-50/50">
        <form class="flex flex-col lg:flex-row gap-4 lg:items-center w-full" method="GET" action="{{ route('admin.leads.index') }}">
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:flex lg:gap-4 gap-4 flex-1">
                <select name="type" class="w-full lg:w-auto rounded-xl border-gray-200 shadow-sm px-4 py-2.5 border focus:ring-blue-500/20 focus:border-blue-500 text-sm appearance-none bg-white min-w-[120px]" onchange="this.form.submit()">
                    <option value="">All Frameworks</option>
                    <option value="PHI" {{ request('type') == 'PHI' ? 'selected' : '' }}>PHI</option>
                    <option value="ITSM" {{ request('type') == 'ITSM' ? 'selected' : '' }}>ITSM</option>
                    <option value="PIR" {{ request('type') == 'PIR' ? 'selected' : '' }}>PIR</option>
                    <option value="SIR" {{ request('type') == 'SIR' ? 'selected' : '' }}>SIR</option>
                </select>
                
                <select name="priority" class="w-full lg:w-auto rounded-xl border-gray-200 shadow-sm px-4 py-2.5 border focus:ring-blue-500/20 focus:border-blue-500 text-sm appearance-none bg-white min-w-[120px]" onchange="this.form.submit()">
                    <option value="">All Priorities</option>
                    <option value="High" {{ request('priority') == 'High' ? 'selected' : '' }}>High</option>
                    <option value="Normal" {{ request('priority') == 'Normal' ? 'selected' : '' }}>Normal</option>
                </select>

                <select name="lead_status" class="w-full lg:w-auto rounded-xl border-gray-200 shadow-sm px-4 py-2.5 border focus:ring-blue-500/20 focus:border-blue-500 text-sm appearance-none bg-white min-w-[150px]" onchange="this.form.submit()">
                    <option value="">All Lead Statuses</option>
                    <option value="Cold" {{ request('lead_status') == 'Cold' ? 'selected' : '' }}>Cold </option>
                    <option value="Warm" {{ request('lead_status') == 'Warm' ? 'selected' : '' }}>Warm </option>
                    <option value="Hot" {{ request('lead_status') == 'Hot' ? 'selected' : '' }}>Hot (Converted)</option>
                </select>

                <select name="source" class="w-full lg:w-auto rounded-xl border-gray-200 shadow-sm px-4 py-2.5 border focus:ring-blue-500/20 focus:border-blue-500 text-sm appearance-none bg-white min-w-[150px]" onchange="this.form.submit()">
                    <option value="">All Sources</option>
                    <option value="Assessment" {{ request('source') == 'Assessment' ? 'selected' : '' }}>Assessment Tool</option>
                    <option value="Dev" {{ request('source') == 'Dev' ? 'selected' : '' }}>Dev Service</option>
                    <option value="Consulting" {{ request('source') == 'Consulting' ? 'selected' : '' }}>Consulting</option>
                </select>
            </div>
            
            @if(request()->anyFilled(['type', 'priority', 'converted', 'lead_status', 'source']))
                <div class="flex lg:items-center">
                    <a href="{{ route('admin.leads.index') }}" class="text-[10px] font-black uppercase tracking-widest text-blue-600 hover:text-blue-800">Clear Filters</a>
                </div>
            @endif
        </form>
    </div>

    <!-- Table -->
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-100">
            <thead class="bg-gray-50/30">
                <tr>
                    <th @click="sortBy('name')" class="cursor-pointer px-8 py-4 text-left text-[10px] font-black text-slate-400 uppercase tracking-widest hover:text-slate-600 transition-colors text-nowrap">Lead Name <span x-show="sortCol === 'name'" x-text="sortAsc ? '↑' : '↓'"></span></th>
                    <th @click="sortBy('company')" class="cursor-pointer px-8 py-4 text-left text-[10px] font-black text-slate-400 uppercase tracking-widest hover:text-slate-600 transition-colors">Company <span x-show="sortCol === 'company'" x-text="sortAsc ? '↑' : '↓'"></span></th>
                    <th class="px-8 py-4 text-left text-[10px] font-black text-slate-400 uppercase tracking-widest">Type / Score</th>
                    <th class="px-8 py-4 text-left text-[10px] font-black text-slate-400 uppercase tracking-widest text-center">Source</th>
                    <th class="px-8 py-4 text-left text-[10px] font-black text-slate-400 uppercase tracking-widest text-center">Risk Level</th>
                    <th class="px-8 py-4 text-left text-[10px] font-black text-slate-400 uppercase tracking-widest">AI Recommendation</th>
                    <th class="px-8 py-4 text-left text-[10px] font-black text-slate-400 uppercase tracking-widest">Status</th>
                    <th @click="sortBy('created_at')" class="cursor-pointer px-8 py-4 text-left text-[10px] font-black text-slate-400 uppercase tracking-widest hover:text-slate-600 transition-colors">Received <span x-show="sortCol === 'created_at'" x-text="sortAsc ? '↑' : '↓'"></span></th>
                    <th class="px-8 py-4 text-right text-[10px] font-black text-slate-400 uppercase tracking-widest">Actions</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-50">
                <template x-for="lead in sortedData" :key="lead.id">
                    <tr class="hover:bg-blue-50/50 transition-colors group cursor-pointer" 
                        @click="openLeadModal(lead)"
                        :class="expandedId === lead.id ? 'bg-blue-50/30' : ''">
                        <td class="px-8 py-5">
                            <span class="font-black text-slate-800 text-sm group-hover:text-blue-600 transition-colors" x-text="lead.name"></span>
                        </td>
                        <td class="px-8 py-5 text-sm text-slate-500 font-medium" x-text="lead.company"></td>
                        <td class="px-8 py-5 text-nowrap">
                            <div class="flex items-center gap-3">
                                <span class="text-[10px] font-black uppercase tracking-widest text-slate-400" x-text="lead.type"></span>
                                <span :class="{
                                    'bg-red-500/10 text-red-600 border-red-200': lead.rag_status === 'Red',
                                    'bg-amber-500/10 text-amber-600 border-amber-200': lead.rag_status === 'Amber',
                                    'bg-green-500/10 text-green-600 border-green-200': lead.rag_status === 'Green',
                                }" class="px-2 py-0.5 inline-flex text-[10px] font-black rounded border shadow-sm" x-text="(lead.overall_score * 1).toFixed(1)"></span>
                            </div>
                        </td>
                        <td class="px-8 py-5 text-center">
                            <span :class="{
                                'text-blue-600 bg-blue-50 border-blue-100': lead.source === 'Assessment',
                                'text-purple-600 bg-purple-50 border-purple-100': lead.source === 'Dev',
                                'text-indigo-600 bg-indigo-50 border-indigo-100': lead.source === 'Consulting'
                            }" class="px-2 py-1 rounded-md text-[9px] font-black uppercase tracking-tighter border" x-text="lead.source || 'Direct Entry'"></span>
                        </td>
                        <td class="px-8 py-5 text-center">
                            <template x-if="lead.priority === 'High' || lead.rag_status === 'Red'">
                                <span class="bg-red-500 text-white px-2 py-0.5 rounded text-[10px] font-black uppercase shadow-lg shadow-red-500/20 tracking-tighter">High Risk</span>
                            </template>
                            <template x-if="(lead.priority === 'Normal' || !lead.priority) && lead.rag_status !== 'Red'">
                                <span class="text-slate-300 text-[10px] font-black uppercase tracking-widest">Normal</span>
                            </template>
                        </td>
                        <td class="px-8 py-5">
                            <div class="flex items-center gap-2">
                                <template x-if="lead.overall_score < 2.5">
                                    <span class="text-[11px] font-bold text-red-600 italic">Critical Recovery Action</span>
                                </template>
                                <template x-if="lead.overall_score >= 2.5 && lead.overall_score < 3.8">
                                    <span class="text-[11px] font-bold text-amber-600 italic">Strategic Discovery Workshop</span>
                                </template>
                                <template x-if="lead.overall_score >= 3.8">
                                    <span class="text-[11px] font-bold text-emerald-600 italic">Optimization & Growth</span>
                                </template>
                            </div>
                        </td>
                        <td class="px-8 py-5">
                            <template x-if="lead.lead_status === 'Hot'">
                                <span class="bg-blue-600 text-white px-2 py-1 rounded-lg text-[10px] font-black uppercase tracking-widest shadow-lg shadow-blue-600/20">Hot Client</span>
                            </template>
                            <template x-if="lead.lead_status === 'Warm'">
                                <span class="bg-amber-500/10 text-amber-600 px-2 py-1 rounded-lg text-[10px] font-black uppercase tracking-widest border border-amber-200">Warm Lead</span>
                            </template>
                            <template x-if="lead.lead_status === 'Cold' || !lead.lead_status">
                                <span class="text-slate-400 text-[10px] font-black uppercase tracking-widest">Cold Prospect</span>
                            </template>
                        </td>
                        <td class="px-8 py-5 text-[10px] font-black text-slate-400 uppercase tracking-tighter text-nowrap" x-text="new Date(lead.created_at).toLocaleDateString(undefined, {day: 'numeric', month: 'short'})"></td>
                        <td class="px-8 py-5 text-right">
                            <div class="flex items-center justify-end gap-3" @click.stop>
                                <button @click="expandedId = expandedId === lead.id ? null : lead.id"
                                        class="px-3 py-1.5 text-[10px] font-black uppercase tracking-widest rounded-xl transition-all"
                                        :class="expandedId === lead.id ? 'bg-slate-900 text-white' : 'bg-slate-100 text-slate-600 hover:bg-slate-200'">
                                    <span x-text="expandedId === lead.id ? 'Close' : 'Snapshot'"></span>
                                </button>
                                <template x-if="!lead.converted_to_client">
                                    <form :action="`/admin/leads/${lead.id}/convert`" method="POST" class="inline">
                                        @csrf
                                        <button type="submit" class="bg-blue-600 text-white hover:bg-blue-500 px-4 py-2 text-[10px] font-black uppercase tracking-widest rounded-xl shadow-lg shadow-blue-600/20 transition-all">
                                            Convert
                                        </button>
                                    </form>
                                </template>
                            </div>
                        </td>
                    </tr>
                    <!-- Expanded Answers Row -->
                    <tr x-show="expandedId === lead.id" x-cloak class="bg-gray-50/50">
                        <td colspan="7" class="px-8 py-8">
                            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden anim-fade-in">
                                <div class="px-6 py-4 border-b border-gray-100 bg-gray-50/30">
                                    <h4 class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Detailed Assessment Answers & Evidence</h4>
                                </div>
                                <div class="p-0">
                                    <template x-if="lead.answers_json">
                                        <div class="divide-y divide-gray-50">
                                            <template x-for="(pillar, pCode) in frameworks[lead.type]" :key="pCode">
                                                <div class="p-6">
                                                    <div class="flex items-center gap-3 mb-4">
                                                        <span class="w-6 h-6 flex items-center justify-center bg-slate-100 text-slate-500 rounded text-[10px] font-black" x-text="pCode"></span>
                                                        <h5 class="text-xs font-black text-slate-700 uppercase tracking-widest" x-text="pillar.name"></h5>
                                                    </div>
                                                    <div class="grid grid-cols-1 gap-6">
                                                        <template x-for="(qText, qCode) in pillar.questions" :key="qCode">
                                                            <div class="pl-9 border-l border-slate-100">
                                                                <div class="flex items-start justify-between gap-4">
                                                                    <p class="text-xs font-medium text-slate-600 leading-relaxed" x-text="qText"></p>
                                                                    <span :class="{
                                                                        'text-red-600': lead.answers_json[qCode] <= 2,
                                                                        'text-amber-500': lead.answers_json[qCode] > 2 && lead.answers_json[qCode] <= 4,
                                                                        'text-green-600': lead.answers_json[qCode] > 4
                                                                    }" class="font-black text-sm" x-text="lead.answers_json[qCode]"></span>
                                                                </div>
                                                                <template x-if="lead.answers_json['note_' + qCode]">
                                                                    <div class="mt-2 p-3 bg-blue-50/50 rounded-xl border border-blue-100/50">
                                                                        <p class="text-[10px] font-black text-blue-400 uppercase tracking-widest mb-1 italic">Evidence/Note:</p>
                                                                        <p class="text-[11px] text-slate-700 leading-relaxed" x-text="lead.answers_json['note_' + qCode]"></p>
                                                                    </div>
                                                                </template>
                                                            </div>
                                                        </template>
                                                    </div>
                                                </div>
                                            </template>
                                        </div>
                                    </template>
                                </div>
                            </div>
                        </td>
                    </tr>
                </template>
            </tbody>
        </table>
    </div>

    <!-- Lead Modal -->
    <div x-show="modalOpen" 
         class="fixed inset-0 z-50 overflow-y-auto" 
         x-cloak
         @keydown.escape.window="modalOpen = false">
        <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
            <div x-show="modalOpen" 
                 x-transition:enter="ease-out duration-300"
                 x-transition:enter-start="opacity-0"
                 x-transition:enter-end="opacity-100"
                 x-transition:leave="ease-in duration-200"
                 x-transition:leave-start="opacity-100"
                 x-transition:leave-end="opacity-0"
                 class="fixed inset-0 transition-opacity" 
                 @click="modalOpen = false">
                <div class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm"></div>
            </div>

            <span class="hidden sm:inline-block sm:align-middle sm:h-screen"></span>&#8203;
            
            <div x-show="modalOpen"
                 x-transition:enter="ease-out duration-300"
                 x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                 x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                 x-transition:leave="ease-in duration-200"
                 x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                 x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                 class="inline-block align-bottom bg-white rounded-3xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-xl sm:w-full border border-gray-100">
                
                <div class="bg-white px-8 pt-8 pb-8">
                    <div class="flex justify-between items-start mb-6">
                        <div>
                            <div class="badge mb-2">Lead Information</div>
                            <h3 class="text-2xl font-black text-slate-800 tracking-tight" x-text="selectedLead?.name"></h3>
                            <p class="text-slate-500 font-medium" x-text="selectedLead?.company"></p>
                        </div>
                        <button @click="modalOpen = false" class="text-slate-400 hover:text-slate-600 transition-colors">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                        </button>
                    </div>

                    <div class="grid grid-cols-2 gap-6 mb-8">
                        <div class="p-4 bg-gray-50 rounded-2xl border border-gray-100">
                            <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">Email Address</p>
                            <p class="text-sm font-bold text-slate-700" x-text="selectedLead?.email || 'N/A'"></p>
                        </div>
                        <div class="p-4 bg-gray-50 rounded-2xl border border-gray-100">
                            <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">Phone Number</p>
                            <p class="text-sm font-bold text-slate-700" x-text="selectedLead?.phone || 'Not provided'"></p>
                        </div>
                    </div>

                    <div class="mb-8">
                        <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-3">Technical Inquiry Details</p>
                        
                        <!-- Assessment Specific View -->
                        <template x-if="selectedLead?.source === 'Assessment' || !selectedLead?.source">
                            <a :href="'/admin/assessments/' + selectedLead?.id" 
                               class="flex items-center justify-between p-4 bg-blue-50/50 rounded-2xl border border-blue-100 hover:bg-blue-100/50 transition-all group">
                                <div class="flex items-center gap-4">
                                    <div class="w-10 h-10 bg-white rounded-xl shadow-sm flex items-center justify-center">
                                        <span class="text-xs font-black text-blue-600" x-text="selectedLead?.type"></span>
                                    </div>
                                    <div>
                                        <p class="text-sm font-black text-slate-800 group-hover:text-blue-600" x-text="['PHI', 'PIR'].includes(selectedLead?.type) ? 'Programme Health Snapshot' : 'Service Maturity Snapshot'"></p>
                                        <p class="text-[10px] font-bold text-slate-500">Score: <span class="text-blue-600" x-text="(selectedLead?.overall_score * 1).toFixed(1)"></span> / 5.0</p>
                                    </div>
                                </div>
                                <svg class="h-5 w-5 text-blue-400 transition-transform group-hover:translate-x-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
                            </a>
                        </template>

                        <!-- Service/Inquiry Specific View (Dev, Consulting, AI, Project) -->
                        <template x-if="selectedLead?.source && selectedLead?.source !== 'Assessment'">
                            <div class="space-y-3 max-h-[300px] overflow-y-auto pr-2 custom-scrollbar">
                                <template x-for="(value, key) in selectedLead?.answers_json" :key="key">
                                    <div class="p-4 bg-slate-50 rounded-2xl border border-slate-100 hover:bg-white hover:border-blue-100 transition-all">
                                        <p class="text-[9px] font-black text-blue-400 uppercase tracking-[0.2em] mb-1" x-text="key.charAt(0).toUpperCase() + key.slice(1).replace('_', ' ')"></p>
                                        <p class="text-xs font-bold text-slate-700 leading-relaxed" x-text="value"></p>
                                    </div>
                                </template>
                            </div>
                        </template>
                    </div>
                    
                    <div class="flex flex-col gap-3">
                        <template x-if="selectedLead && selectedLead.source === 'Assessment'">
                            <a :href="'/admin/assessments/create?lead_id=' + selectedLead.id" 
                               class="w-full bg-blue-600 text-white font-black py-4 rounded-2xl text-sm uppercase tracking-widest shadow-xl shadow-blue-600/20 hover:bg-blue-700 transition-all text-center">
                                Start New Full Assessment
                            </a>
                        </template>
                        <template x-if="selectedLead && !selectedLead.converted_to_client">
                            <form :action="`/admin/leads/${selectedLead.id}/convert`" method="POST">
                                @csrf
                                <button type="submit" class="w-full bg-slate-900 text-white font-black py-4 rounded-2xl text-sm uppercase tracking-widest hover:bg-slate-800 transition-all">
                                    Convert to Client
                                </button>
                            </form>
                        </template>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('leadsTable', () => ({
        data: @json($leads),
        frameworks: @json($questions),
        sortCol: 'created_at',
        sortAsc: false,
        expandedId: null,
        modalOpen: false,
        selectedLead: null,

        openLeadModal(lead) {
            this.selectedLead = lead;
            this.modalOpen = true;
        },
        
        sortBy(col) {
            if (this.sortCol === col) {
                this.sortAsc = !this.sortAsc;
            } else {
                this.sortCol = col;
                this.sortAsc = true;
            }
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
