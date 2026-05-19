@extends('admin.layouts.app')

@section('header', 'Create Assessment')

@section('content')
@php 
    $selectedLead = request('lead_id') ? $leads->find(request('lead_id')) : null;
@endphp

<div class="max-w-2xl mx-auto bg-white shadow rounded-lg p-6" x-data="createAssessment()">
    <form action="{{ route('admin.assessments.store') }}" method="POST">
        @csrf
        
        <div class="mb-4">
            <label class="block text-sm font-medium text-gray-700">Report Tier (Required)</label>
            <select name="report_tier" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 font-bold" required>
                <option value="">Select Tier...</option>
                <option value="Tier 1 Rapid">Tier 1 Rapid — £3,500</option>
                <option value="Tier 2 Full">Tier 2 Full — £7,000</option>
            </select>
        </div>

        <div class="mb-4">
            <label class="block text-sm font-medium text-gray-700">Assessment Type</label>
            <select name="type" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500" required x-model="type">
                <option value="">Select Type...</option>
                <option value="PIR">PIR (Programme Implementation Review)</option>
                <option value="SIR">SIR (Service Implementation Review)</option>
            </select>
        </div>

        <div class="mb-4">
            <label class="block text-sm font-medium text-gray-700">{{ $selectedLead ? 'Lead Name' : 'Client' }}</label>
            @if($selectedLead)
                <div class="mt-1 p-3 bg-blue-50 border border-blue-200 rounded-md text-sm font-bold text-blue-800 flex justify-between items-center">
                    <span>{{ $selectedLead->name }} ({{ $selectedLead->company }})</span>
                    <input type="hidden" name="snapshot_submission_id" value="{{ $selectedLead->id }}">
                    <span class="text-[10px] bg-blue-600 text-white px-2 py-0.5 rounded uppercase font-black">Linked Lead</span>
                </div>
            @else
                <select name="client_id" 
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500" 
                        required 
                        x-model="clientId"
                        @change="handleClientChange($event.target.value)">
                    <option value="">Select Client...</option>
                    <template x-for="client in clientsList" :key="client.id">
                        <option :value="client.id" x-text="client.company_name"></option>
                    </template>
                    <option value="create_new" class="font-bold text-blue-600">+ Create New Client</option>
                </select>
            @endif
        </div>

        <div class="mb-4">
            <label class="block text-sm font-medium text-gray-700">Target Entity (Programme / Service Name)</label>
            <input type="text" name="target_entity" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500" 
                   value="{{ $selectedLead ? ($selectedLead->type === 'PHI' ? 'Programme Assessment' : 'Service Maturity') : '' }}"
                   placeholder="e.g. ERP Migration or IT Helpdesk">
        </div>

        <div class="mb-4">
            <label class="block text-sm font-medium text-gray-700">Assessment Name</label>
            <input type="text" name="name" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500" 
                   value="{{ $selectedLead ? 'Full Assessment for ' . $selectedLead->name : '' }}"
                   placeholder="e.g. Q3 2026 ERP Assessment" required>
        </div>

        @unless($selectedLead)
        <div class="mb-6">
            <label class="block text-sm font-medium text-gray-700">Start from existing Lead / Snapshot (Optional)</label>
            <select name="snapshot_submission_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                <option value="">None</option>
                @foreach($leads as $l)
                    <option value="{{ $l->id }}" {{ ($selectedLead && $selectedLead->id == $l->id) ? 'selected' : '' }}>
                        {{ $l->company }} - {{ $l->name }} (Score: {{ $l->overall_score }})
                    </option>
                @endforeach
            </select>
        </div>
        @endunless

        <div class="flex justify-end">
            <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded shadow hover:bg-blue-700">
                Create & Start Scoring &rarr;
            </button>
        </div>
    </form>

    <!-- Create Client Modal -->
    <div x-show="showClientModal" 
         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0 scale-95"
         x-transition:enter-end="opacity-100 scale-100"
         x-cloak>
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md overflow-hidden border border-white/20" @click.away="showClientModal = false">
            <div class="p-6 border-b border-gray-100 bg-gray-50 flex justify-between items-center">
                <h3 class="text-xl font-bold text-slate-900">Create New Client</h3>
                <button @click="showClientModal = false" class="text-gray-400 hover:text-gray-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                </button>
            </div>
            
            <form @submit.prevent="submitClient()" class="p-6 space-y-4">
                <div>
                    <label class="block text-xs font-bold text-gray-500 uppercase tracking-widest mb-1">Company Name</label>
                    <input type="text" x-model="newClient.company_name" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500" required>
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-500 uppercase tracking-widest mb-1">Primary Contact</label>
                    <input type="text" x-model="newClient.primary_contact" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500" required>
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-500 uppercase tracking-widest mb-1">Email Address</label>
                    <input type="email" x-model="newClient.email" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500" required>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-gray-500 uppercase tracking-widest mb-1">Phone</label>
                        <input type="text" x-model="newClient.phone" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500" required>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-500 uppercase tracking-widest mb-1">Industry</label>
                        <input type="text" x-model="newClient.industry" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    </div>
                </div>
                
                <div class="pt-4 flex justify-end gap-3">
                    <button type="button" @click="showClientModal = false" class="px-4 py-2 text-gray-600 hover:text-gray-800 font-medium">Cancel</button>
                    <button type="submit" 
                            :disabled="savingClient"
                            class="bg-blue-600 text-white px-6 py-2 rounded-lg font-bold shadow hover:bg-blue-700 disabled:opacity-50 flex items-center gap-2">
                        <span x-show="savingClient" class="animate-spin text-lg">&#9696;</span>
                        <span x-text="savingClient ? 'Saving...' : 'Save Client'"></span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function createAssessment() {
    return {
        type: '',
        clientId: '',
        showClientModal: false,
        savingClient: false,
        clientsList: @json($clients),
        newClient: {
            company_name: '',
            primary_contact: '',
            email: '',
            phone: '',
            industry: ''
        },

        handleClientChange(val) {
            if (val === 'create_new') {
                this.clientId = ''; // Reset select
                this.showClientModal = true;
            }
        },

        async submitClient() {
            this.savingClient = true;
            try {
                // Use relative URL to avoid port/host mismatch issues
                const response = await fetch('{{ route("admin.clients.store", [], false) }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: JSON.stringify(this.newClient)
                });

                const result = await response.json();
                if (result.success) {
                    // Add new client to list and select it
                    this.clientsList.push(result.client);
                    this.clientId = result.client.id;
                    this.showClientModal = false;
                    
                    // Reset form
                    this.newClient = {
                        company_name: '',
                        primary_contact: '',
                        email: '',
                        phone: '',
                        industry: ''
                    };
                } else {
                    alert('Error: ' + (result.message || 'Could not save client.'));
                }
            } catch (error) {
                console.error('Client creation failed:', error);
                alert('Fatal Error: Failed to connect to server.');
            } finally {
                this.savingClient = false;
            }
        }
    }
}
</script>
@endsection
