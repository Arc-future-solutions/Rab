@extends('admin.layouts.app')

@section('header')
    <div class="flex items-center gap-4">
        <a href="{{ route('admin.assessments.index') }}" class="text-gray-500 hover:text-gray-700">&larr; Back</a>
        <span>Assessment: {{ $assessment->name }}</span>
    </div>
@endsection

@section('content')
<!-- Header Summary -->
<div class="bg-white shadow rounded-lg mb-6 p-6">
    <div class="flex justify-between items-start">
        <div>
            <h2 class="text-2xl font-bold text-gray-900">{{ $assessment->client->company_name ?? $assessment->client_name }}</h2>
            <p class="text-gray-500 mt-1">{{ $assessment->type }} Assessment &middot; {{ $assessment->created_at->format('M d, Y') }}</p>
            @if(isset($assessment->is_public_lead))
                <span class="mt-2 inline-flex items-center px-3 py-1 rounded-full text-[10px] font-black bg-amber-100 text-amber-700 border border-amber-200 uppercase tracking-widest">
                    Public Website Health-Check
                </span>
            @endif
            @if($assessment->critical_flag)
                <span class="mt-2 inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                    <svg class="mr-1.5 h-2 w-2 text-red-400" fill="currentColor" viewBox="0 0 8 8"><circle cx="4" cy="4" r="3" /></svg>
                    CRITICAL FLAG TRIGGERED
                </span>
            @endif
        </div>
        <div class="text-right flex flex-col items-end">
            <span class="text-sm text-gray-500 block mb-1">Overall Score</span>
            <span class="px-3 py-1 inline-flex text-lg font-bold rounded-full 
                @if($assessment->rag_status === 'Green') bg-green-100 text-green-800 
                @elseif($assessment->rag_status === 'Amber') bg-amber-100 text-amber-800 
                @else bg-red-100 text-red-800 @endif">
                {{ number_format($assessment->overall_score, 1) }} ({{ $assessment->rag_status }})
            </span>
            <div class="mt-4">
                @if($assessment->status === 'draft')
                    <form action="{{ route('admin.assessments.updateStatus', $assessment) }}" method="POST" class="inline">
                        @csrf @method('PUT')
                        <input type="hidden" name="status" value="in_progress">
                        <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded shadow text-sm">Mark In Review</button>
                    </form>
                @elseif($assessment->status === 'in_progress')
                    <form action="{{ route('admin.assessments.updateStatus', $assessment) }}" method="POST" class="inline">
                        @csrf @method('PUT')
                        <input type="hidden" name="status" value="approved">
                        <button type="submit" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded shadow text-sm">Approve Report</button>
                    </form>
                @else
                    <span class="bg-gray-100 text-gray-800 px-4 py-2 rounded font-medium border border-gray-200">Approved</span>
                @endif
            </div>
        </div>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
    <!-- Radar Chart -->
    <div class="bg-white shadow rounded-lg p-6 lg:col-span-1">
        <h3 class="text-lg font-medium mb-4">Pillar Distribution</h3>
        <canvas id="radarChart"></canvas>
    </div>

    <!-- Index Scores -->
    <div class="bg-white shadow rounded-lg p-6 lg:col-span-2">
        <h3 class="text-lg font-medium mb-4">Indices</h3>
        <div class="grid grid-cols-2 md:grid-cols-3 gap-6">
            @if($assessment->type === 'PHI')
                <div class="border rounded-lg p-4 text-center">
                    <span class="block text-sm text-gray-500">Business Readiness Index (BRI)</span>
                    <span class="block mt-2 text-2xl font-bold">{{ $assessment->bri ?: 'N/A' }}</span>
                </div>
                <div class="border rounded-lg p-4 text-center">
                    <span class="block text-sm text-gray-500">Value Realisation Index (VRI)</span>
                    <span class="block mt-2 text-2xl font-bold">{{ $assessment->vri ?: 'N/A' }}</span>
                </div>
            @endif

            @if($assessment->type === 'ITSM')
                <div class="border rounded-lg p-4 text-center">
                    <span class="block text-sm text-gray-500">Service Stability Index (SSI)</span>
                    <span class="block mt-2 text-2xl font-bold">{{ $assessment->ssi ?: 'N/A' }}</span>
                </div>
                <div class="border rounded-lg p-4 text-center">
                    <span class="block text-sm text-gray-500">Service Maturity Index (SMI)</span>
                    <span class="block mt-2 text-2xl font-bold">{{ $assessment->smi ?: 'N/A' }}</span>
                </div>
                <div class="border rounded-lg p-4 text-center">
                    <span class="block text-sm text-gray-500">BAU Readiness</span>
                    <span class="block mt-2 text-2xl font-bold">{{ $assessment->bau_readiness ?: 'N/A' }}</span>
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
                                @if($pillar->critical_flag)
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

<!-- Question Responses -->
<div class="bg-white shadow rounded-lg mb-6">
    <div class="px-6 py-4 border-b border-gray-200">
        <h3 class="text-lg font-medium">Question Responses</h3>
    </div>
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 w-1/4">Pillar</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 w-1/2">Question & Evidence</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500">Score</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500">Confidence</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                @foreach($assessment->questionResponses as $resp)
                    <tr class="align-top">
                        <td class="px-6 py-4 text-sm text-gray-900 font-medium">{{ $resp->pillar_name }}</td>
                        <td class="px-6 py-4 text-sm">
                            <div class="text-gray-900 font-medium mb-1">{{ $resp->question }}</div>
                            <div class="text-gray-500 text-xs mt-2 p-2 bg-gray-50 rounded italic whitespace-pre-wrap">{{ $resp->evidence_note ?: 'No evidence note provided.' }}</div>
                        </td>
                        <td class="px-6 py-4">
                            <span class="px-2 py-1 bg-gray-100 rounded text-sm font-bold border border-gray-200">{{ $resp->score }}</span>
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-500">{{ $resp->confidence }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

<!-- AI Draft JSON (Debug) -->
@if($assessment->ai_draft_json)
<div class="bg-white shadow rounded-lg p-6 mb-6">
    <h3 class="text-lg font-medium mb-4">AI Raw Draft (Read Only)</h3>
    <pre class="bg-gray-800 text-green-400 p-4 rounded text-xs overflow-x-auto">{{ json_encode($assessment->ai_draft_json, JSON_PRETTY_PRINT) }}</pre>
</div>
@endif

@endsection

@stack('scripts')
<script>
document.addEventListener("DOMContentLoaded", function() {
    const pillars = @json($assessment->pillarScores);
    
    if (pillars.length > 0) {
        const labels = pillars.map(p => p.name);
        const data = pillars.map(p => p.score);
        
        const ctx = document.getElementById('radarChart').getContext('2d');
        new Chart(ctx, {
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
                plugins: {
                    legend: { display: false }
                }
            }
        });
    }
});
</script>
