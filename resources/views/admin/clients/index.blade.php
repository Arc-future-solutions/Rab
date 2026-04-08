@extends('admin.layouts.app')

@section('header', 'Clients')

@section('content')
<div class="bg-white shadow-xl border border-gray-200 rounded-2xl overflow-hidden" x-data="clientTable()">
    {{-- Search and Filters --}}
    <div class="px-8 py-6 border-b border-gray-100 flex justify-between items-center bg-gray-50/50">
        <form class="flex gap-4 items-center w-full" method="GET" action="{{ route('admin.clients.index') }}">
            <div class="relative w-72">
                <input type="text" name="search" placeholder="Filter clients..." value="{{ request('search') }}" 
                       class="w-full rounded-xl border-gray-200 shadow-sm pl-4 pr-10 py-2.5 border focus:ring-blue-500/20 focus:border-blue-500 transition-all text-sm">
            </div>
            
            <select name="industry" class="rounded-xl border-gray-200 shadow-sm px-4 py-2.5 border focus:ring-blue-500/20 focus:border-blue-500 transition-all text-sm appearance-none bg-white min-w-[180px]" onchange="this.form.submit()">
                <option value="">All Industries</option>
                @foreach($industries as $ind)
                    <option value="{{ $ind }}" {{ request('industry') == $ind ? 'selected' : '' }}>{{ $ind }}</option>
                @endforeach
            </select>
            
            @if(request('search') || request('industry'))
                <a href="{{ route('admin.clients.index') }}" class="text-xs font-black uppercase tracking-widest text-blue-600 hover:text-blue-800">Clear</a>
            @endif
        </form>
    </div>

    {{-- Clients Table --}}
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-100">
            <thead>
                <tr class="bg-gray-50/30">
                    <th @click="sortBy('company_name')" class="cursor-pointer px-8 py-4 text-left text-[10px] font-black text-slate-400 uppercase tracking-widest transition-colors hover:text-slate-600">Company <span x-show="sortCol === 'company_name'" x-text="sortAsc ? '↑' : '↓'"></span></th>
                    <th class="px-8 py-4 text-left text-[10px] font-black text-slate-400 uppercase tracking-widest">Primary Contact</th>
                    <th class="px-8 py-4 text-left text-[10px] font-black text-slate-400 uppercase tracking-widest">Industry</th>
                    <th @click="sortBy('assessment_count')" class="cursor-pointer px-8 py-4 text-left text-[10px] font-black text-slate-400 uppercase tracking-widest hover:text-slate-600">Assessments <span x-show="sortCol === 'assessment_count'" x-text="sortAsc ? '↑' : '↓'"></span></th>
                    <th @click="sortBy('latest_assessment_date')" class="cursor-pointer px-8 py-4 text-left text-[10px] font-black text-slate-400 uppercase tracking-widest hover:text-slate-600">Last Assessment <span x-show="sortCol === 'latest_assessment_date'" x-text="sortAsc ? '↑' : '↓'"></span></th>
                    <th class="px-8 py-4 text-left text-[10px] font-black text-slate-400 uppercase tracking-widest text-right">RAG Status</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-50">
                <template x-for="client in sortedClients" :key="client.id">
                    <tr @click="openClient(client)" class="hover:bg-blue-50/50 cursor-pointer transition-colors group">
                        <td class="px-8 py-5">
                            <span class="font-black text-slate-800 text-sm group-hover:text-blue-600 transition-colors" x-text="client.company_name"></span>
                        </td>
                        <td class="px-8 py-5 text-sm text-slate-500 font-medium" x-text="client.primary_contact"></td>
                        <td class="px-8 py-5">
                            <span class="text-[10px] font-black uppercase tracking-widest text-slate-400" x-text="client.industry || 'General'"></span>
                        </td>
                        <td class="px-8 py-5">
                            <span class="inline-flex items-center justify-center w-6 h-6 rounded-full bg-slate-100 text-slate-600 text-[10px] font-black" x-text="client.assessment_count"></span>
                        </td>
                        <td class="px-8 py-5 text-xs text-slate-400 font-bold" x-text="client.formatted_date || 'N/A'"></td>
                        <td class="px-8 py-5 text-right">
                            <template x-if="client.latest_rag_status">
                                <span :class="{
                                    'bg-red-500/10 text-red-600 border-red-200': client.latest_rag_status === 'Red',
                                    'bg-amber-500/10 text-amber-600 border-amber-200': client.latest_rag_status === 'Amber',
                                    'bg-green-500/10 text-green-600 border-green-200': client.latest_rag_status === 'Green',
                                }" class="px-3 py-1 inline-flex text-[10px] font-black rounded-full border shadow-sm uppercase tracking-widest" x-text="client.latest_rag_status"></span>
                            </template>
                            <template x-if="!client.latest_rag_status"><span class="text-slate-200 italic text-xs">None</span></template>
                        </td>
                    </tr>
                </template>
            </tbody>
        </table>
    </div>

    {{-- Client Data Modal --}}
    <div x-show="showModal" 
         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0 scale-95"
         x-transition:enter-end="opacity-100 scale-100"
         x-cloak>
        <div class="bg-white rounded-3xl shadow-2xl w-full max-w-2xl overflow-hidden border border-white/20" @click.away="showModal = false">
            <div class="p-8 border-b border-gray-100 bg-gray-50/50 flex justify-between items-start">
                <div>
                    <h2 class="text-2xl font-black text-slate-900 tracking-tight" x-text="selectedClient?.company_name"></h2>
                    <p class="text-sm text-slate-500 font-medium mt-1 uppercase tracking-widest flex items-center gap-2">
                        <span class="w-1.5 h-1.5 rounded-full bg-blue-500"></span>
                        Client Profile
                    </p>
                </div>
                <button @click="showModal = false" class="p-2 hover:bg-white rounded-xl transition-colors shadow-sm">
                    <svg class="w-6 h-6 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                </button>
            </div>
            
            <div class="p-8 grid grid-cols-2 gap-8">
                <div>
                    <h4 class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-3">Primary Contact</h4>
                    <p class="text-lg font-bold text-slate-800 leading-none" x-text="selectedClient?.primary_contact"></p>
                    <p class="text-sm text-slate-400 mt-2 font-medium italic" x-text="selectedClient?.email || 'No email saved'"></p>
                </div>
                <div>
                    <h4 class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-3">Industry</h4>
                    <p class="text-lg font-bold text-slate-800" x-text="selectedClient?.industry || 'Not set'"></p>
                </div>
                <div>
                    <h4 class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-3">Total Assessments</h4>
                    <p class="text-3xl font-black text-blue-600" x-text="selectedClient?.assessment_count"></p>
                </div>
                <div>
                    <h4 class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-3">Health Status</h4>
                    <template x-if="selectedClient?.latest_rag_status">
                        <div class="flex items-center gap-2">
                            <span :class="{
                                'bg-red-500': selectedClient.latest_rag_status === 'Red',
                                'bg-amber-500': selectedClient.latest_rag_status === 'Amber',
                                'bg-green-500': selectedClient.latest_rag_status === 'Green',
                            }" class="w-3 h-3 rounded-full animate-pulse"></span>
                            <span class="font-black text-slate-800 uppercase tracking-widest text-sm" x-text="selectedClient.latest_rag_status"></span>
                        </div>
                    </template>
                </div>
            </div>

            <div class="px-8 py-6 bg-slate-900 flex justify-between items-center">
                <span class="text-slate-400 text-xs font-bold">View full history, files and assessments.</span>
                <a :href="`/admin/clients/${selectedClient?.id}`" class="bg-blue-600 text-white px-6 py-2.5 rounded-xl font-black uppercase tracking-tighter hover:bg-blue-500 transition-all shadow-lg shadow-blue-500/20">
                    Open Full Record &rarr;
                </a>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('alpine:init', () => {
    let clientsData = @json($clients);
    clientsData = clientsData.map(c => {
        c.formatted_date = c.latest_assessment_date ? new Date(c.latest_assessment_date).toLocaleDateString() : '';
        return c;
    });

    Alpine.data('clientTable', () => ({
        clients: clientsData,
        sortCol: 'company_name',
        sortAsc: true,
        showModal: false,
        selectedClient: null,
        
        openClient(client) {
            this.selectedClient = client;
            this.showModal = true;
        },

        sortBy(col) {
            if (this.sortCol === col) this.sortAsc = !this.sortAsc;
            else { this.sortCol = col; this.sortAsc = true; }
        },
        
        get sortedClients() {
            return this.clients.sort((a, b) => {
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
