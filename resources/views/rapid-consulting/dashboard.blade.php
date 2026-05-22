@extends('layouts.public')
@section('title', 'Intelligence Snapshot — RAB Consulting')

@php
    $pdfMode = $pdfMode ?? false;
    $pdfPart = $pdfPart ?? 'full';
    $snapshotReport = $results['snapshot_report'] ?? [];
    $aiBrief = trim((string) ($snapshotReport['intelligence_brief'] ?? ''));
    $aiBriefParagraphs = preg_split('/\n\s*\n/', $aiBrief, -1, PREG_SPLIT_NO_EMPTY) ?: [];
    $aiInsightCards = collect($snapshotReport['insight_cards'] ?? [])->take(3);
    $areaLabel = strtolower((string) ($results['type'] ?? 'pir')) === 'sir' ? 'Domain' : 'Pillar';
    $areaLabelPlural = $areaLabel . 's';
    $scoreAreaTitle = $areaLabelPlural . ' Scores';
    $chartLabel = $areaLabel . ' Distribution';
    $performanceLabel = $areaLabel . ' Performance';
    $clientCompany = $results['user']['company'] ?? 'Client organisation';
    $subjectName = $results['subject_name'] ?? $results['programme_name'] ?? $results['service_name'] ?? $clientCompany;
    $assessmentDate = isset($results['assessment_date']) ? \Carbon\Carbon::parse($results['assessment_date'])->format('d F Y') : now()->format('d F Y');
    $reportTitle = strtolower((string) ($results['type'] ?? 'pir')) === 'sir'
        ? 'Service Intelligence Snapshot Report'
        : 'Programme Intelligence Snapshot Report';
    $aiBadgeClasses = function (?string $rag): string {
        return match (strtolower((string) $rag)) {
            'green' => 'bg-green-100 text-green-700 border-green-200',
            'red' => 'bg-red-100 text-red-700 border-red-200',
            default => 'bg-amber-100 text-amber-700 border-amber-200',
        };
    };
@endphp

@push('head')
<style>
    @media print {
        @page {
            size: A4;
            margin: {{ $pdfMode && $pdfPart === 'cover' ? '0' : '25mm 12mm 23mm' }};
        }
        @if($pdfMode && $pdfPart === 'full')
            @page:first { margin: 0; }
        @endif
        body { background: #fff !important; }
        .screen-only { display: none !important; }
        .pdf-cover-page {
            display: flex !important;
            height: 297mm;
            min-height: 297mm;
            max-height: 297mm;
            padding: 26mm 18mm 18mm !important;
            position: relative;
            z-index: 2147483647;
            overflow: hidden;
        }
        .print-break-avoid { break-inside: avoid; page-break-inside: avoid; }
        .print-break-before { break-before: page; page-break-before: always; }
        .print-compact { box-shadow: none !important; border-radius: 14px !important; }
        a[href] { color: inherit; text-decoration: none; }
    }
    .pdf-cover-page { display: none; }
</style>
@endpush

@section('content')
<section class="{{ $pdfMode ? 'bg-white py-0' : 'min-h-screen bg-slate-50 py-12 md:py-20' }}">
    @if($pdfMode && in_array($pdfPart, ['full', 'cover'], true))
        <div class="pdf-cover-page bg-white w-full flex-col justify-between page-break-after-always" style="page-break-after: always; break-after: page;">
            <div>
                <img src="{{ $logoDataUri ?? asset('assets/images/logo-rab.png') }}" alt="RAB Consulting Services" class="h-14 w-auto mb-16">
                <div class="border-t-4 border-slate-900 pt-10">
                    <p class="text-[11px] font-black uppercase tracking-[0.35em] text-blue-700 mb-6">RAB Consulting Services</p>
                    <h1 class="text-5xl font-black leading-tight tracking-tight text-slate-950 max-w-4xl">{{ $reportTitle }}</h1>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-10 border-y border-slate-200 py-10">
                <div>
                    <p class="text-[10px] font-black uppercase tracking-[0.25em] text-slate-400 mb-3">Client company</p>
                    <p class="text-2xl font-black text-slate-950">{{ $clientCompany }}</p>
                </div>
                <div>
                    <p class="text-[10px] font-black uppercase tracking-[0.25em] text-slate-400 mb-3">{{ strtolower((string) ($results['type'] ?? 'pir')) === 'sir' ? 'Service name' : 'Programme name' }}</p>
                    <p class="text-2xl font-black text-slate-950">{{ $subjectName }}</p>
                </div>
                <div>
                    <p class="text-[10px] font-black uppercase tracking-[0.25em] text-slate-400 mb-3">Assessment date</p>
                    <p class="text-xl font-bold text-slate-800">{{ $assessmentDate }}</p>
                </div>
                <div>
                    <p class="text-[10px] font-black uppercase tracking-[0.25em] text-slate-400 mb-3">Prepared by</p>
                    <p class="text-xl font-bold text-slate-800">Reda Boukhiar, Director, RAB Consulting Services</p>
                </div>
            </div>

            <p class="text-sm font-bold text-slate-600">
                This report is confidential and prepared exclusively for the named client organisation.
            </p>
        </div>
    @endif

    @if(! $pdfMode || in_array($pdfPart, ['full', 'body'], true))
    <div class="container max-w-5xl mx-auto px-6">
        <div id="download-action" class="screen-only flex justify-end mb-6">
            @if(! empty($results['lead_id']) && ! empty($results['booking_token']))
                <a href="{{ route('rapid-consulting.snapshot-report.pdf', ['lead' => $results['lead_id'], 'token' => $results['booking_token']]) }}" class="group flex items-center gap-3 bg-slate-900 text-white px-6 py-3 rounded-2xl font-black text-[11px] uppercase tracking-widest hover:bg-blue-600 transition-all duration-300 shadow-xl shadow-slate-200">
                    <svg class="w-4 h-4 text-blue-400 group-hover:text-white transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                    Download Report as PDF
                </a>
            @else
                <button type="button" disabled class="group flex items-center gap-3 bg-slate-300 text-white px-6 py-3 rounded-2xl font-black text-[11px] uppercase tracking-widest shadow-xl shadow-slate-200 cursor-not-allowed">
                    <svg class="w-4 h-4 text-white/70" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                    Download Report as PDF
                </button>
            @endif
        </div>

        <div class="bg-white rounded-[2.5rem] p-10 mb-12 border border-slate-200 shadow-xl shadow-slate-200/50 print-compact print-break-avoid">
            <div class="flex flex-col md:flex-row items-center gap-12">
                <div class="flex flex-col items-center">
                    <div class="relative w-56 h-56 flex items-center justify-center mb-4">
                        <canvas id="screenScoreGauge"></canvas>
                        <div class="absolute inset-0 flex flex-col items-center justify-center">
                            <span class="text-6xl font-black text-slate-900 tracking-tighter">{{ number_format((float) $results['overall_score'], 1) }}</span>
                            <span class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Score / 5.0</span>
                        </div>
                    </div>
                    <p class="max-w-xs text-[10px] text-slate-400 font-medium leading-relaxed text-center">
                        This diagnostic is based on self-reported responses and does not constitute a formal audit, independent review, regulatory assessment, or compliance certification. Scores reflect the information provided at the time of completion. Results may not reflect conditions that have changed since completion. RAB Consulting Services Ltd accepts no liability for decisions made solely on the basis of these results. A consultant-led engagement provides independently verified findings.
                    </p>
                </div>

                <div class="flex-1 text-center md:text-left">
                    <div class="flex items-center justify-center md:justify-start gap-4 mb-6">
                        <span class="px-6 py-2 rounded-full text-[10px] font-black uppercase tracking-widest @if($results['rag_status'] === 'Red') bg-red-100 text-red-600 @elseif($results['rag_status'] === 'Amber') bg-amber-100 text-amber-500 @else bg-green-100 text-green-600 @endif">
                             {{ $results['rag_status'] }} Status
                        </span>
                    </div>
                    <h1 class="text-3xl md:text-4xl font-black text-slate-900 tracking-tighter mb-6 italic leading-tight">
                        "Your {{ strtolower((string) ($results['type'] ?? 'pir')) === 'sir' ? 'service' : 'programme' }} shows @if($results['overall_score'] < 2.5) critical exposure @elseif($results['overall_score'] < 3.8) partial control with vulnerability @else strong control @endif."
                    </h1>
                    <p class="text-lg text-slate-500 leading-relaxed max-w-2xl font-medium">
                        Based on RAB Consulting's proprietary Intelligence methodology, the stored diagnostic score indicates
                        <span class="font-bold text-slate-800">@if($results['overall_score'] < 2.5) immediate intervention is required @elseif($results['overall_score'] < 3.8) targeted improvement is needed @else controls are currently performing above threshold @endif</span>.
                    </p>
                </div>
            </div>
        </div>

        <div class="mb-20 bg-white rounded-[3rem] p-12 border border-slate-100 shadow-xl shadow-slate-200/40 print-compact print-break-avoid">
            <h2 class="text-[11px] font-black text-slate-400 uppercase tracking-widest mb-12 text-center">{{ $areaLabel }} Score Heat Map</h2>
            <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6">
                @foreach($results['pillar_scores'] as $code => $pillar)
                    <div class="p-8 rounded-[2rem] flex flex-col items-center justify-center text-center transition-all hover:scale-105 hover:shadow-lg @if($pillar['score'] < 2.5) bg-red-50 text-red-700 border border-red-100 @elseif($pillar['score'] < 3.8) bg-amber-50 text-amber-700 border border-amber-100 @else bg-green-50 text-green-700 border border-green-100 @endif">
                        <span class="text-[10px] font-black uppercase tracking-widest mb-4 opacity-70">{{ $pillar['name'] }}</span>
                        <span class="text-4xl font-black leading-none">{{ number_format((float) $pillar['score'], 1) }}</span>
                    </div>
                @endforeach
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-12 mb-20">
            <div class="bg-blue-900 rounded-[3rem] p-10 text-white shadow-2xl shadow-blue-900/30 print-compact print-break-avoid">
                <h2 class="text-[11px] font-black text-blue-300 uppercase tracking-[0.2em] mb-8 text-center">Indices & Calculations</h2>
                <div class="space-y-8">
                    @foreach($results['index_scores'] as $name => $score)
                        <div class="border-b border-white/10 pb-6 last:border-0">
                            <div class="flex justify-between items-end mb-2">
                                <span class="text-[10px] font-black text-blue-200 uppercase tracking-widest">{{ $name === 'CHI' ? 'Compliance Health Index' : $name }}</span>
                                <span class="text-[9px] font-bold text-blue-300 italic">
                                    @if(is_numeric($score)) @if($score >= 4.0) High @elseif($score >= 3.0) Medium @else Low @endif @else Data Pending @endif
                                </span>
                            </div>
                            <span class="text-4xl font-black">{{ is_numeric($score) ? number_format((float) $score, 1) : '—' }}</span>
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="lg:col-span-2 bg-white rounded-[3rem] p-10 border border-slate-100 shadow-xl shadow-slate-200/40 print-compact print-break-avoid">
                <h2 class="text-[11px] font-black text-slate-400 uppercase tracking-[0.3em] mb-10 text-center">{{ $chartLabel }}</h2>
                <div class="aspect-square max-w-md mx-auto">
                    <canvas id="screenSpiderChart"></canvas>
                </div>
            </div>
        </div>

        <div class="mb-20">
            <h2 class="text-[11px] font-black text-slate-400 uppercase tracking-[0.3em] mb-12 text-center">Intelligence Visualisations</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-12 mb-12">
                <div class="bg-white rounded-[3rem] p-10 border border-slate-100 shadow-xl shadow-slate-200/40 print-compact print-break-avoid">
                    <h3 class="text-[11px] font-black text-slate-400 uppercase tracking-widest mb-10 text-center">{{ $performanceLabel }}</h3>
                    <div class="aspect-video">
                        <canvas id="screenBarChart"></canvas>
                    </div>
                </div>
                <div class="bg-white rounded-[3rem] p-10 border border-slate-100 shadow-xl shadow-slate-200/40 print-compact print-break-avoid">
                    <h3 class="text-[11px] font-black text-slate-400 uppercase tracking-widest mb-10 text-center">RAG Status</h3>
                    <div class="grid grid-cols-1 gap-4">
                        <div class="rounded-2xl bg-red-50 border border-red-100 px-6 py-4 text-red-700 font-black text-sm">Red: Below 2.5</div>
                        <div class="rounded-2xl bg-amber-50 border border-amber-100 px-6 py-4 text-amber-700 font-black text-sm">Amber: 2.5 to 3.7</div>
                        <div class="rounded-2xl bg-green-50 border border-green-100 px-6 py-4 text-green-700 font-black text-sm">Green: 3.8 and above</div>
                    </div>
                </div>
            </div>
        </div>

        <div id="ai-snapshot-insights" class="mb-20 print-break-before">
            <div class="bg-white rounded-[2.5rem] p-8 md:p-10 border border-slate-200 shadow-xl shadow-slate-200/40 mb-10 print-compact print-break-avoid">
                <h2 class="text-[11px] font-black text-blue-600 uppercase tracking-[0.3em] mb-8">Intelligence Brief</h2>
                <div class="space-y-6 text-lg text-slate-700 leading-relaxed font-medium">
                    @forelse($aiBriefParagraphs as $paragraph)
                        <p>{{ $paragraph }}</p>
                    @empty
                        <p>Snapshot intelligence is not available for this assessment yet.</p>
                    @endforelse
                </div>
            </div>

            <div>
                <h2 class="text-[11px] font-black text-slate-400 uppercase tracking-[0.3em] mb-8">Insight Cards</h2>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                    @foreach($aiInsightCards as $card)
                        @php
                            $isCompliance = ($card['pillar_code'] ?? $card['code'] ?? null) === 'COMPLIANCE';
                            $rag = $card['rag'] ?? 'Amber';
                            $score = $card['score'] ?? null;
                            $name = $card['pillar_name'] ?? $card['domain_name'] ?? $card['name'] ?? 'Priority area';
                        @endphp
                        <article class="bg-white rounded-[2rem] p-8 border shadow-xl shadow-slate-200/30 print-compact print-break-avoid {{ $isCompliance ? 'border-blue-500 ring-1 ring-blue-200' : 'border-slate-200' }}">
                            <div class="mb-5 flex min-h-12 items-start justify-between gap-4">
                                <h3 class="text-xl font-black leading-tight text-slate-900">{{ $name }}</h3>
                                <span class="shrink-0 rounded-full border px-3 py-1.5 text-[10px] font-black uppercase tracking-widest {{ $isCompliance ? 'border-blue-200 bg-blue-100 text-blue-700' : $aiBadgeClasses($rag) }}">
                                    {{ $isCompliance ? 'Compliance' : $rag }}
                                </span>
                            </div>

                            <div class="mb-6 flex items-end gap-2">
                                <span class="text-4xl font-black tracking-tight text-slate-900">{{ is_numeric($score) ? number_format((float) $score, 1) : '—' }}</span>
                                <span class="pb-1 text-[10px] font-black uppercase tracking-widest text-slate-400">Score</span>
                            </div>

                            <div class="space-y-5 text-sm leading-6 text-slate-700">
                                <p>{{ $card['finding'] ?? '' }}</p>
                                <p class="border-t border-slate-100 pt-5 font-bold text-slate-900">{{ $card['action'] ?? '' }}</p>
                            </div>
                        </article>
                    @endforeach
                </div>
            </div>
        </div>

        <div id="cta-section" class="bg-blue-900 rounded-[4rem] p-16 md:p-24 text-center shadow-2xl shadow-blue-900/40 relative overflow-hidden group print-compact print-break-avoid">
            <div class="absolute inset-0 bg-[radial-gradient(circle_at_top_right,_var(--tw-gradient-stops))] from-blue-500/20 via-transparent to-transparent"></div>
            <h2 class="text-[12px] font-black text-blue-300 uppercase tracking-[0.5em] mb-8 relative">Unlock Full Intelligence</h2>
            <p class="text-3xl md:text-5xl font-black text-white leading-tight max-w-4xl mx-auto mb-12 relative">
                Gain deep-dive root cause analysis and a structured recovery plan.
            </p>
            <div class="flex justify-center relative">
                <a href="{{ route('booking.index', ['name' => $results['user']['name'] ?? '', 'email' => $results['user']['email'] ?? '', 'booking_token' => $results['booking_token'] ?? '']) }}"
                   class="bg-white text-blue-900 px-12 py-6 rounded-full text-lg font-black uppercase tracking-tighter hover:scale-105 transition shadow-2xl active:scale-95">
                    Book a Full Consultant-Led Review
                </a>
            </div>
        </div>
    </div>
    @endif
</section>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const pillarData = @json(collect($results['pillar_scores'])->values());
    const labels = pillarData.map(p => p.name);
    const scores = pillarData.map(p => p.score);
    const colors = pillarData.map(p => p.rag === 'Red' ? '#ef4444' : (p.rag === 'Amber' ? '#f59e0b' : '#10b981'));
    const scoreColor = '{{ $results['rag_status'] === 'Red' ? '#ef4444' : ($results['rag_status'] === 'Amber' ? '#f59e0b' : '#10b981') }}';

    const scoreGauge = document.getElementById('screenScoreGauge');
    const spiderChart = document.getElementById('screenSpiderChart');
    const barChart = document.getElementById('screenBarChart');

    if (scoreGauge) {
        new Chart(scoreGauge, {
            type: 'doughnut',
            data: {
                datasets: [{
                    data: [{{ $results['overall_score'] }}, {{ 5 - $results['overall_score'] }}],
                    backgroundColor: [scoreColor, '#f1f5f9'],
                    borderWidth: 0,
                    circumference: 270,
                    rotation: 225,
                    cutout: '85%',
                    borderRadius: 20
                }]
            },
            options: {
                animation: false,
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false }, tooltip: { enabled: false } }
            }
        });
    }

    if (spiderChart) {
        new Chart(spiderChart, {
            type: 'radar',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Score',
                    data: scores,
                    backgroundColor: 'rgba(30, 58, 138, 0.1)',
                    borderColor: '#1e3a8a',
                    borderWidth: 3,
                    pointBackgroundColor: '#1e3a8a'
                }]
            },
            options: {
                animation: false,
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    r: { min: 0, max: 5, ticks: { display: false }, pointLabels: { font: { size: 10, weight: '700' } } }
                },
                plugins: { legend: { display: false } }
            }
        });
    }

    if (barChart) {
        new Chart(barChart, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [{ data: scores, backgroundColor: colors, borderRadius: 8 }]
            },
            options: {
                animation: false,
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: { min: 0, max: 5, grid: { display: false } },
                    x: { grid: { display: false }, ticks: { display: false } }
                },
                plugins: { legend: { display: false } }
            }
        });
    }
});
</script>
@endpush
