@extends('layouts.public')

@section('title', 'Results Dashboard — Rapid Consulting')

@section('content')
<section class="section bg-slate-50 min-h-screen">
    <div class="container" style="max-width: 1200px;">
        
        {{-- Header --}}
        <div class="flex flex-col md:flex-row md:items-end md:justify-between gap-6 pb-10 border-b-2 border-slate-200">
            <div>
                <div class="badge bg-slate-900 text-white rounded-md mb-3 px-3 py-1 font-bold tracking-widest text-[10px]">CLIENT INSIGHT DASHBOARD</div>
                <h1 class="text-5xl font-black text-slate-900 uppercase tracking-tighter">Welcome, {{ $results['user']['name'] }}</h1>
                <p class="text-slate-500 font-bold mt-2 uppercase tracking-widest text-sm italic">{{ $results['user']['company'] }} — {{ strtoupper($results['type']) }} ASSESSMENT</p>
            </div>
            <div class="text-right flex flex-col items-end">
                <div class="text-slate-400 font-black text-[10px] uppercase tracking-widest">Completed: {{ now()->format('D d M Y') }}</div>
                <div class="mt-2 inline-flex items-center gap-2 bg-white px-4 py-2 border rounded-full shadow-sm text-xs font-black text-slate-600 uppercase tracking-widest">
                    <span class="w-3 h-3 rounded-full @if($results['priority'] === 'High') bg-red-600 animate-pulse @else bg-slate-300 @endif"></span>
                    PRIORITY: {{ $results['priority'] }}
                </div>
            </div>
        </div>

        {{-- Score Summary Row Card --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 lg:grid-cols-5 gap-6 mt-12">
            {{-- Overall score card --}}
            <div class="bg-white p-8 rounded-3xl shadow-xl flex flex-col items-center justify-center border-l-8 @if($results['rag_status'] === 'Red') border-red-600 @elseif($results['rag_status'] === 'Amber') border-amber-500 @else border-green-600 @endif">
                <div class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">Overall Score</div>
                <div class="text-5xl font-black @if($results['rag_status'] === 'Red') text-red-600 @elseif($results['rag_status'] === 'Amber') text-amber-500 @else text-green-600 @endif">
                    {{ number_format($results['overall_score'], 1) }}
                </div>
            </div>

            {{-- Index Card Row for ITSM --}}
            @if($results['type'] === 'itsm')
                @foreach($results['index_scores'] as $name => $score)
                    <div class="bg-white p-8 rounded-3xl shadow-xl flex flex-col items-center justify-center">
                        <div class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2 text-center">{{ $name }}</div>
                        <div class="text-4xl font-black @if($score < 2.5) text-red-600 @elseif($score < 3.8) text-amber-500 @else text-green-600 @endif">
                            {{ number_format($score, 1) }}
                        </div>
                    </div>
                @endforeach
            @else
                {{-- PHI Alternative Cards --}}
                <div class="bg-white p-8 rounded-3xl shadow-xl flex flex-col items-center justify-center">
                    <div class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2 text-center italic">Governance</div>
                    <div class="text-4xl font-black @if($results['pillar_scores']['P1']['score'] < 2.5) text-red-600 @elseif($results['pillar_scores']['P1']['score'] < 3.8) text-amber-500 @else text-green-600 @endif">
                        {{ number_format($results['pillar_scores']['P1']['score'], 1) }}
                    </div>
                </div>
                <div class="bg-white p-8 rounded-3xl shadow-xl flex flex-col items-center justify-center">
                    <div class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2 text-center italic">Cutover</div>
                    <div class="text-4xl font-black @if($results['pillar_scores']['P7']['score'] < 2.5) text-red-600 @elseif($results['pillar_scores']['P7']['score'] < 3.8) text-amber-500 @else text-green-600 @endif">
                        {{ number_format($results['pillar_scores']['P7']['score'], 1) }}
                    </div>
                </div>
                <div class="bg-white p-8 rounded-3xl shadow-xl flex flex-col items-center justify-center">
                    <div class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2 text-center italic">Readiness</div>
                    <div class="text-4xl font-black @if($results['pillar_scores']['P9']['score'] < 2.5) text-red-600 @elseif($results['pillar_scores']['P9']['score'] < 3.8) text-amber-500 @else text-green-600 @endif">
                        {{ number_format($results['pillar_scores']['P9']['score'], 1) }}
                    </div>
                </div>
            @endif
        </div>

        {{-- Pillar Scores Section (Chart.js) --}}
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-12 mt-16">
            <div class="lg:col-span-2">
                <div class="bg-white p-10 rounded-3xl shadow-2xl border border-slate-100">
                    <h2 class="text-2xl font-black text-slate-900 uppercase tracking-tighter mb-8">Pillar Breakdown Analysis</h2>
                    <div class="h-[600px] w-full">
                        <canvas id="pillarChart"></canvas>
                    </div>
                </div>
            </div>

            <div class="flex flex-col gap-8">
                {{-- Legend Card --}}
                <div class="bg-white p-8 rounded-3xl shadow-xl border border-slate-100">
                    <h3 class="text-sm font-black text-slate-900 uppercase tracking-widest mb-4">RAG Definition</h3>
                    <div class="space-y-4">
                        <div class="flex items-center gap-3">
                            <span class="w-4 h-4 bg-red-600 rounded-full"></span>
                            <span class="text-xs font-bold text-slate-600">RED: Critical Risk (< 2.5)</span>
                        </div>
                        <div class="flex items-center gap-3">
                            <span class="w-4 h-4 bg-amber-500 rounded-full"></span>
                            <span class="text-xs font-bold text-slate-600">AMBER: Partial Control (2.5 - 3.8)</span>
                        </div>
                        <div class="flex items-center gap-3">
                            <span class="w-4 h-4 bg-green-600 rounded-full"></span>
                            <span class="text-xs font-bold text-slate-600">GREEN: Fully Controlled (> 3.8)</span>
                        </div>
                    </div>
                </div>

                {{-- Summary List Card --}}
                <div class="bg-white rounded-3xl shadow-xl border border-slate-100 overflow-hidden flex-1">
                    <div class="bg-slate-50 px-8 py-4 border-b">
                        <span class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Strength Ranking</span>
                    </div>
                    <div class="divide-y p-2">
                        @php 
                            $sortedPillars = collect($results['pillar_scores'])->sortByDesc('score'); 
                        @endphp
                        @foreach($sortedPillars as $pillar)
                        <div class="p-4 flex items-center justify-between hover:bg-slate-50 transition">
                            <span class="text-sm font-bold text-slate-800">{{ $pillar['name'] }}</span>
                            <span class="text-xs font-black @if($pillar['rag'] === 'Red') text-red-600 @elseif($pillar['rag'] === 'Amber') text-amber-500 @else text-green-600 @endif">{{ $pillar['score'] }}</span>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>

        {{-- Recommended Action section --}}
        <div class="mt-16 bg-blue-900 p-12 rounded-3xl shadow-2xl relative overflow-hidden text-center md:text-left group">
            <div class="absolute right-0 top-0 w-80 h-80 bg-blue-800 rounded-full -translate-y-1/2 translate-x-1/3 opacity-30 blur-3xl group-hover:scale-150 transition duration-1000"></div>
            <div class="relative z-10 flex flex-col md:flex-row items-center justify-between gap-12">
                <div class="max-w-3xl">
                    <div class="inline-flex items-center gap-2 bg-blue-600 px-4 py-1.5 rounded-full mb-6 font-black text-[10px] text-white tracking-[0.2em]">RECOMMENDED NEXT STEP</div>
                    <h2 class="text-4xl font-extrabold text-white mb-6 uppercase tracking-tighter leading-none italic">Assessor Recommendation</h2>
                    <p class="text-xl text-blue-100 leading-relaxed font-medium">
                        "{{ $results['recommendation'] }}"
                    </p>
                </div>
                <div class="shrink-0 flex flex-col items-center gap-4">
                    <a href="{{ route('booking.index', ['name' => $results['user']['name'], 'email' => $results['user']['email']]) }}" 
                       class="btn primary bg-white text-blue-900 border-0 hover:bg-blue-50 text-xl py-6 px-16 shadow-2xl uppercase tracking-tighter font-black transition-all hover:scale-105 active:scale-95">
                        Book Executive Review &rarr;
                    </a>
                    <span class="text-blue-400 text-[10px] font-black uppercase tracking-widest italic opacity-60">Verified assessment summary</span>
                </div>
            </div>
        </div>

        <div class="mt-20 border-t-2 border-slate-200 py-12 flex justify-center">
            <button onclick="window.print()" class="text-slate-400 hover:text-slate-900 flex items-center font-black uppercase tracking-widest text-xs transition gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path></svg>
                Print results report
            </button>
        </div>
    </div>
</section>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const ctx = document.getElementById('pillarChart').getContext('2d');
    
    const pillarData = @json(collect($results['pillar_scores'])->values());
    const labels = pillarData.map(p => p.name);
    const scores = pillarData.map(p => p.score);
    const colors = pillarData.map(p => {
        if(p.rag === 'Red') return 'rgba(220, 38, 38, 0.85)';
        if(p.rag === 'Amber') return 'rgba(245, 158, 11, 0.85)';
        return 'rgba(22, 163, 74, 0.85)';
    });
    const borderColors = pillarData.map(p => {
        if(p.rag === 'Red') return 'rgba(220, 38, 38, 1)';
        if(p.rag === 'Amber') return 'rgba(245, 158, 11, 1)';
        return 'rgba(22, 163, 74, 1)';
    });

    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: labels,
            datasets: [{
                label: 'Health Score',
                data: scores,
                backgroundColor: colors,
                borderColor: borderColors,
                borderWidth: 2,
                borderRadius: 12,
                barThickness: 28,
            }]
        },
        options: {
            indexAxis: 'y',
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                tooltip: {
                    backgroundColor: 'rgba(15, 23, 42, 0.95)',
                    titleFont: { family: 'DM Sans', size: 14, weight: '900' },
                    bodyFont: { family: 'DM Sans', size: 12, weight: 'bold' },
                    padding: 16,
                    cornerRadius: 12,
                    displayColors: false,
                }
            },
            scales: {
                x: {
                    min: 0,
                    max: 5,
                    ticks: {
                        stepSize: 1,
                        font: { family: 'DM Sans', weight: 'bold', size: 11 },
                        color: '#94a3b8'
                    },
                    grid: { color: 'rgba(241, 245, 249, 1)' }
                },
                y: {
                    ticks: {
                        font: { family: 'DM Sans', weight: 'black', size: 12 },
                        color: '#1e293b'
                    },
                    grid: { display: false }
                }
            },
            animation: {
                duration: 2000,
                easing: 'easeOutQuart'
            }
        }
    });
});
</script>
@endpush
@endsection
