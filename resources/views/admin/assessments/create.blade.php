@extends('admin.layouts.app')

@section('header', 'Create Assessment')

@section('content')
<div class="max-w-2xl mx-auto bg-white shadow rounded-lg p-6">
    <form action="{{ route('admin.assessments.store') }}" method="POST">
        @csrf
        
        <div class="mb-4">
            <label class="block text-sm font-medium text-gray-700">Assessment Type</label>
            <select name="type" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500" required x-model="type">
                <option value="">Select Type...</option>
                <option value="PHI">PHI (Project Health Index)</option>
                <option value="ITSM">ITSM (IT Service Management)</option>
            </select>
        </div>

        <div class="mb-4">
            <label class="block text-sm font-medium text-gray-700">Client</label>
            <select name="client_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500" required>
                <option value="">Select Client...</option>
                @foreach($clients as $c)
                    <option value="{{ $c->id }}">{{ $c->company_name }}</option>
                @endforeach
            </select>
        </div>

        <div class="mb-4">
            <label class="block text-sm font-medium text-gray-700">Target Entity (Programme / Service Name)</label>
            <input type="text" name="target_entity" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500" placeholder="e.g. ERP Migration or IT Helpdesk">
        </div>

        <div class="mb-4">
            <label class="block text-sm font-medium text-gray-700">Assessment Name</label>
            <input type="text" name="name" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500" placeholder="e.g. Q3 2026 ERP Assessment" required>
        </div>

        <div class="mb-6">
            <label class="block text-sm font-medium text-gray-700">Start from existing Lead / Snapshot (Optional)</label>
            <select name="snapshot_submission_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                <option value="">None</option>
                @foreach($leads as $lead)
                    <option value="{{ $lead->id }}">{{ $lead->company }} - {{ $lead->name }} (Score: {{ $lead->overall_score }})</option>
                @endforeach
            </select>
        </div>

        <div class="flex justify-end">
            <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded shadow hover:bg-blue-700">
                Create & Start Scoring &rarr;
            </button>
        </div>
    </form>
</div>
@endsection
