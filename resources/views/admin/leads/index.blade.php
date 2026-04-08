@extends('admin.layouts.app')

@section('header', 'Leads')

@section('content')
<div class="bg-white shadow-xl border border-gray-200 rounded-2xl overflow-hidden" x-data="leadsTable()">
    <!-- Filters -->
    <div class="px-8 py-6 border-b border-gray-100 flex justify-between items-center bg-gray-50/50">
        <form class="flex gap-4 items-center w-full" method="GET" action="{{ route('admin.leads.index') }}">
            <select name="type" class="rounded-xl border-gray-200 shadow-sm px-4 py-2.5 border focus:ring-blue-500/20 focus:border-blue-500 text-sm appearance-none bg-white min-w-[120px]" onchange="this.form.submit()">
                <option value="">All Frameworks</option>
                <option value="PHI" {{ request('type') == 'PHI' ? 'selected' : '' }}>PHI</option>
                <option value="ITSM" {{ request('type') == 'ITSM' ? 'selected' : '' }}>ITSM</option>
            </select>
            
            <select name="priority" class="rounded-xl border-gray-200 shadow-sm px-4 py-2.5 border focus:ring-blue-500/20 focus:border-blue-500 text-sm appearance-none bg-white min-w-[120px]" onchange="this.form.submit()">
                <option value="">All Priorities</option>
                <option value="High" {{ request('priority') == 'High' ? 'selected' : '' }}>High</option>
                <option value="Normal" {{ request('priority') == 'Normal' ? 'selected' : '' }}>Normal</option>
            </select>

            <select name="lead_status" class="rounded-xl border-gray-200 shadow-sm px-4 py-2.5 border focus:ring-blue-500/20 focus:border-blue-500 text-sm appearance-none bg-white min-w-[150px]" onchange="this.form.submit()">
                <option value="">All Lead Statuses</option>
                <option value="Cold" {{ request('lead_status') == 'Cold' ? 'selected' : '' }}>Cold Leads</option>
                <option value="Warm" {{ request('lead_status') == 'Warm' ? 'selected' : '' }}>Warm Leads</option>
                <option value="Hot" {{ request('lead_status') == 'Hot' ? 'selected' : '' }}>Hot (Converted)</option>
            </select>

            <select name="converted" class="rounded-xl border-gray-200 shadow-sm px-4 py-2.5 border focus:ring-blue-500/20 focus:border-blue-500 text-sm appearance-none bg-white min-w-[150px]" onchange="this.form.submit()">
                <option value="">Conversion Status</option>
                <option value="no" {{ request('converted') == 'no' ? 'selected' : '' }}>Active Leads</option>
                <option value="yes" {{ request('converted') == 'yes' ? 'selected' : '' }}>Archived (Converted)</option>
            </select>
            
            @if(request()->anyFilled(['type', 'priority', 'converted', 'lead_status']))
                <a href="{{ route('admin.leads.index') }}" class="text-[10px] font-black uppercase tracking-widest text-blue-600 hover:text-blue-800">Clear</a>
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
                    <th class="px-8 py-4 text-left text-[10px] font-black text-slate-400 uppercase tracking-widest">Risk Level</th>
                    <th class="px-8 py-4 text-left text-[10px] font-black text-slate-400 uppercase tracking-widest">Status</th>
                    <th @click="sortBy('created_at')" class="cursor-pointer px-8 py-4 text-left text-[10px] font-black text-slate-400 uppercase tracking-widest hover:text-slate-600 transition-colors">Received <span x-show="sortCol === 'created_at'" x-text="sortAsc ? '↑' : '↓'"></span></th>
                    <th class="px-8 py-4 text-right text-[10px] font-black text-slate-400 uppercase tracking-widest">Actions</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-50">
                <template x-for="lead in sortedData" :key="lead.id">
                    <tr class="hover:bg-blue-50/50 transition-colors group">
                        <td class="px-8 py-5">
                            <span class="font-black text-slate-800 text-sm group-hover:text-blue-600 transition-colors" x-text="lead.name"></span>
                        </td>
                        <td class="px-8 py-5 text-sm text-slate-500 font-medium" x-text="lead.company"></td>
                        <td class="px-8 py-5">
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
                            <template x-if="lead.priority === 'High'">
                                <span class="bg-red-500 text-white px-2 py-0.5 rounded text-[10px] font-black uppercase shadow-lg shadow-red-500/20 tracking-tighter">High Risk</span>
                            </template>
                            <template x-if="lead.priority === 'Normal' || !lead.priority">
                                <span class="text-slate-300 text-[10px] font-black uppercase tracking-widest">Normal</span>
                            </template>
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
                        <td class="px-8 py-5 text-[10px] font-black text-slate-400 uppercase tracking-tighter" x-text="new Date(lead.created_at).toLocaleDateString(undefined, {day: 'numeric', month: 'short'})"></td>
                        <td class="px-8 py-5 text-right">
                            <div class="flex items-center justify-end gap-3">
                                <a :href="`/booking?name=${encodeURIComponent(lead.name)}&email=${encodeURIComponent(lead.email ?? '')}`"
                                   target="_blank"
                                   class="bg-white text-slate-900 hover:bg-slate-900 hover:text-white border border-slate-200 p-2 rounded-xl shadow-sm transition-all">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                              d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                    </svg>
                                </a>
                                <template x-if="!lead.converted_to_client">
                                    <form :action="`/admin/leads/${lead.id}/convert`" method="POST" class="inline">
                                        @csrf
                                        <button type="submit" class="bg-blue-600 text-white hover:bg-blue-500 px-4 py-2 text-[10px] font-black uppercase tracking-widest rounded-xl shadow-lg shadow-blue-600/20 transition-all">
                                            Convert
                                        </button>
                                    </form>
                                </template>
                                <template x-if="lead.converted_to_client">
                                    <a :href="`/admin/clients/${lead.client_id}`" class="w-8 h-8 flex items-center justify-center bg-green-500 text-white rounded-full shadow-lg shadow-green-500/20">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                                    </a>
                                </template>
                            </div>
                        </td>
                    </tr>
                </template>
            </tbody>
        </table>
    </div>
</div>

<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('leadsTable', () => ({
        data: @json($leads),
        sortCol: 'created_at',
        sortAsc: false,
        
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
