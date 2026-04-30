<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Intelligence Report — RAB Consulting</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        @page { size: A4; margin: 0; }
        body { -webkit-print-color-adjust: exact; background: white; }
        .chart-container { position: relative; height: 250px; width: 100%; }
        /* Force no page breaks inside cards */
        .card { page-break-inside: avoid; }
    </style>
</head>
<body class="bg-white font-sans text-slate-900 antialiased p-12">

    <!-- 1. HEADER -->
    <header class="flex justify-between items-end border-b-2 border-slate-100 pb-8 mb-12">
        <div class="shrink-0">
            <img src="/assets/images/logo-rab.png" alt="RAB Consulting" class="h-12">
        </div>
        <div class="text-right">
            <h1 class="text-4xl font-black text-slate-900 uppercase tracking-tighter leading-none mb-2">
                Intelligence Report
            </h1>
            <p class="text-xs font-black text-slate-400 uppercase tracking-[0.2em]">
                {{ now()->format('d M Y') }} | {{ strtoupper($results['type']) }} Snapshot | {{ $results['user']['company'] ?? 'Organisation' }}
            </p>
        </div>
    </header>

    <!-- 2. SUMMARY OVERVIEW -->
    <div class="grid grid-cols-12 gap-10 mb-12 card">
        <div class="col-span-4 flex flex-col items-center justify-center">
            <div class="relative w-48 h-48">
                <canvas id="scoreGauge"></canvas>
                <div class="absolute inset-0 flex flex-col items-center justify-center">
                    <span class="text-5xl font-black text-slate-900 tracking-tighter">{{ number_format($results['overall_score'], 1) }}</span>
                    <span class="text-[10px] font-black text-slate-400 uppercase tracking-widest text-center">Score / 5.0</span>
                </div>
            </div>
            <div class="mt-4 px-4 py-1.5 rounded-full text-[10px] font-black uppercase tracking-widest
                @if($results['rag_status'] === 'Green') bg-green-100 text-green-700
                @elseif($results['rag_status'] === 'Amber') bg-amber-100 text-amber-700
                @else bg-red-100 text-red-700 @endif">
                {{ $results['rag_status'] }} Status
            </div>
        </div>
        
        <div class="col-span-8">
            <h2 class="text-[11px] font-black text-blue-600 uppercase tracking-[0.2em] mb-4">Executive Intelligence Summary</h2>
            <div class="text-slate-700 text-sm leading-relaxed space-y-4">
                @php 
                    $paragraphs = explode("\n\n", $results['recommendation']);
                @endphp
                @foreach($paragraphs as $p)
                    <p>{{ $p }}</p>
                @endforeach
            </div>
        </div>
    </div>

    <!-- 3. INDICES & METRICS -->
    <div class="grid grid-cols-4 gap-6 mb-12 card">
        @foreach($results['index_scores'] as $index => $score)
        <div class="bg-slate-50 rounded-3xl p-6 border border-slate-100">
            <div class="flex items-center justify-between mb-2">
                <span class="text-[10px] font-black text-slate-400 uppercase tracking-widest">{{ $index }}</span>
                <div class="w-2 h-2 rounded-full @if($score >= 3.8) bg-green-500 @elseif($score >= 2.5) bg-amber-500 @else bg-red-500 @endif"></div>
            </div>
            <div class="flex items-baseline gap-1">
                @if($index === 'CHI')
                    <span class="text-2xl font-black text-slate-300 tracking-tighter">&mdash;</span>
                @else
                    <span class="text-3xl font-black text-slate-900 tracking-tighter">{{ number_format($score, 1) }}</span>
                    <span class="text-[10px] font-bold text-slate-400">/ 5</span>
                @endif
            </div>
        </div>
        @endforeach
    </div>

    <!-- 4. VISUAL ANALYSIS -->
    <div class="grid grid-cols-2 gap-10 mb-12 card">
        <div class="bg-white rounded-[2.5rem] p-8 border border-slate-100 shadow-sm">
            <h3 class="text-[11px] font-black text-slate-900 uppercase tracking-[0.15em] mb-8 text-center">Domain Intensity Radar</h3>
            <div class="chart-container">
                <canvas id="radarChart"></canvas>
            </div>
        </div>
        <div class="bg-white rounded-[2.5rem] p-8 border border-slate-100 shadow-sm">
            <h3 class="text-[11px] font-black text-slate-900 uppercase tracking-[0.15em] mb-8 text-center">Performance Distribution</h3>
            <div class="chart-container">
                <canvas id="barChart"></canvas>
            </div>
        </div>
    </div>

    <!-- 5. TOP WEAKEST INSIGHTS -->
    <div class="mb-12 card">
        <h3 class="text-[11px] font-black text-slate-900 uppercase tracking-[0.15em] mb-6">Priority Remediation Areas</h3>
        <div class="space-y-4">
            @php
                $weakest = collect($results['pillar_scores'])->sortBy('score')->take(3);
            @endphp
            @foreach($weakest as $code => $data)
            <div class="bg-white border border-slate-100 rounded-3xl p-6 flex items-start gap-6">
                <div class="shrink-0 w-12 h-12 @if($data['score'] < 2.5) bg-red-50 @else bg-amber-50 @endif rounded-2xl flex items-center justify-center font-black text-xl @if($data['score'] < 2.5) text-red-600 @else text-amber-600 @endif">
                    {{ number_format($data['score'], 1) }}
                </div>
                <div>
                    <h4 class="font-black text-slate-900 uppercase text-xs mb-2 tracking-tight">{{ $data['name'] }}</h4>
                    <p class="text-slate-600 text-xs leading-relaxed">
                        Performance in this domain indicates significant exposure. We recommend immediate review of operational controls and stakeholder alignment to address identified gaps.
                    </p>
                </div>
            </div>
            @endforeach
        </div>
    </div>

    <!-- 6. FOOTER (Branding only, not the website footer) -->
    <footer class="mt-20 pt-8 border-t border-slate-100 text-center">
        <p class="text-[10px] font-black text-slate-400 uppercase tracking-[0.2em]">
            Confidential Intelligence Report &copy; {{ date('Y') }} RAB Consulting Services Ltd
        </p>
        <p class="text-[9px] text-slate-300 mt-2">
            This document is a snapshot generated via the Programme & Service Intelligence Framework. For full details, contact your account lead.
        </p>
    </footer>

    <script>
        // Data Preparation
        const labels = {!! json_encode(collect($results['pillar_scores'])->pluck('name')) !!};
        const scores = {!! json_encode(collect($results['pillar_scores'])->pluck('score')) !!};
        const colors = {!! json_encode(collect($results['pillar_scores'])->map(fn($p) => $p['score'] < 2.5 ? '#ef4444' : ($p['score'] < 3.8 ? '#f59e0b' : '#22c55e'))) !!};

        window.onload = function() {
            // Gauge
            new Chart(document.getElementById('scoreGauge'), {
                type: 'doughnut',
                data: {
                    datasets: [{
                        data: [{{ $results['overall_score'] }}, {{ 5 - $results['overall_score'] }}],
                        backgroundColor: ['#1e3a8a', '#f1f5f9'],
                        borderWidth: 0,
                        circumference: 240,
                        rotation: 240,
                    }]
                },
                options: { cutout: '85%', plugins: { legend: { display: false }, tooltip: { enabled: false } } }
            });

            // Radar
            new Chart(document.getElementById('radarChart'), {
                type: 'radar',
                data: {
                    labels: labels.map(l => l.split(' ').slice(0,2).join(' ')),
                    datasets: [{
                        data: scores,
                        fill: true,
                        backgroundColor: 'rgba(30, 58, 138, 0.1)',
                        borderColor: '#1e3a8a',
                        borderWidth: 2,
                        pointRadius: 2
                    }]
                },
                options: {
                    scales: { r: { min: 0, max: 5, ticks: { display: false }, grid: { color: '#f1f5f9' } } },
                    plugins: { legend: { display: false } }
                }
            });

            // Bar
            new Chart(document.getElementById('barChart'), {
                type: 'bar',
                data: {
                    labels: labels,
                    datasets: [{ data: scores, backgroundColor: colors, borderRadius: 4, barThickness: 15 }]
                },
                options: {
                    scales: {
                        y: { min: 0, max: 5, grid: { display: false }, ticks: { font: { size: 8 } } },
                        x: { grid: { display: false }, ticks: { display: false } }
                    },
                    plugins: { legend: { display: false } }
                }
            });
            
            // If automated, we might want a small delay then trigger something
            // window.status = 'ready';
        };
    </script>
</body>
</html>
