@extends('layouts.public')

@section('title', 'SIR: Service Intelligence Review — Programme & Service Intelligence (PIR / SIR)')

@push('head')
<style>
    /* Hero Animations */
    @keyframes fadeUp {
        from {
            opacity: 0;
            transform: translateY(30px);
        }

        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    @keyframes float {

        0%,
        100% {
            transform: translateY(0px);
        }

        50% {
            transform: translateY(-20px);
        }
    }

    @keyframes glow {

        0%,
        100% {
            opacity: 0.2;
        }

        50% {
            opacity: 0.4;
        }
    }

    .hero-badge {
        animation: fadeUp 0.6s ease-out 0ms both;
    }

    .hero-title {
        animation: fadeUp 0.6s ease-out 100ms both;
    }

    .hero-subtitle {
        animation: fadeUp 0.6s ease-out 200ms both;
    }

    .hero-ctas {
        animation: fadeUp 0.6s ease-out 300ms both;
    }

    .hero-word-highlight {
        background: linear-gradient(135deg, #60a5fa 0%, #3b82f6 100%);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
        position: relative;
        display: inline-block;
        font-weight: 800;
    }

    /* Animated grid background */
    .animated-grid {
        background-image:
            radial-gradient(circle at 2px 2px, rgba(255, 255, 255, 0.06) 1px, transparent 0);
        background-size: 50px 50px;
        animation: gridMove 25s linear infinite;
    }

    @keyframes gridMove {
        0% {
            background-position: 0 0;
        }

        100% {
            background-position: 50px 50px;
        }
    }

    /* Floating particles */
    .floating-particle {
        position: absolute;
        background: radial-gradient(circle, rgba(59, 130, 246, 0.2) 0%, transparent 70%);
        border-radius: 50%;
        animation: float 10s ease-in-out infinite;
        pointer-events: none;
    }

    .floating-particle.glow {
        animation: glow 5s ease-in-out infinite;
    }

    .status-bar {
        height: 8px;
        width: 100%;
        background: #E2E8F0;
        border-radius: 4px;
        overflow: hidden;
    }

    .status-bar-fill {
        height: 100%;
        width: var(--progress-width, 0%);
        background: var(--progress-color, #94a3b8);
        transition: width 1s ease-out;
    }

    .status-indicator {
        display: block;
        font-size: 0.6rem;
        color: var(--indicator-color, #94a3b8);
        font-weight: 700;
        transition: color 1s ease-out;
    }
</style>
@endpush

@section('content')
<!-- SIR Hero -->
<section class="relative py-24 md:py-32 flex items-center overflow-hidden" style="background-color: #0F172A;">
    <!-- Dark overlay to ensure text readability -->
    <div class="absolute inset-0" style="background: rgba(15, 23, 42, 0.95);"></div>

    <!-- Animated Geometric Grid Background -->
    <div class="absolute inset-0 animated-grid"></div>

    <!-- Floating accent particles -->
    <div class="floating-particle glow" style="width: 400px; height: 400px; top: -10%; right: -5%;"></div>
    <div class="floating-particle" style="width: 200px; height: 200px; bottom: 10%; left: -5%; animation-delay: 2s;"></div>

    <div class="container relative z-10">
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-12 items-center">
            <div class="max-w-xl lg:max-w-none">
                <span class="hero-badge inline-flex items-center gap-2 px-5 py-2 rounded-full mb-6" style="background: rgba(13, 148, 136, 0.15); color: #2DD4BF; border: 1px solid rgba(13, 148, 136, 0.2);">
                    <span class="inline-block w-1.5 h-1.5 rounded-full" style="background-color: #2DD4BF;"></span>
                    Service Intelligence Review
                </span>

                <h1 class="hero-title font-serif text-white text-4xl sm:text-5xl md:text-6xl font-bold leading-tight mb-6">
                    Independent insight for <br><span class="hero-word-highlight" style="background: linear-gradient(135deg, #2DD4BF 0%, #0D9488 100%); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text;">operational stability</span>
                </h1>

                <p class="hero-subtitle text-lg md:text-xl leading-relaxed mb-10 max-w-2xl" style="color: #e2e8f0 !important;">
                    An objective assessment of IT service, ITSM and operational stability. Understand the true state of your service environment to drive better performance and resilience.
                </p>

                <div class="hero-ctas flex flex-col sm:flex-row gap-4">
                    <x-button variant="primary" href="/rapid-consulting?type=itsm" class="text-base px-8 py-4 group shadow-lg hover:shadow-xl" style="background-color: #0D9488; hover:background-color: #0F766E;">
                        Start SIR Diagnostic
                        <svg class="ml-2 w-5 h-5 group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"></path>
                        </svg>
                    </x-button>
                    <x-button variant="secondary" href="/contact" class="text-base px-8 py-4" style="border-color: #475569; color: #e2e8f0;">
                        Book SIR Consultation
                    </x-button>
                </div>
                <p class="text-xs mt-4 italic" style="color: #94a3b8;">Estimated completion time: 15 minutes.</p>
            </div>

            <!-- Hero Visual -->
            <div class="relative flex justify-center items-center p-6 lg:p-10 rounded-3xl overflow-hidden shadow-2xl" style="background: linear-gradient(145deg, #1A4C4A 0%, #0F172A 100%); border: 1px solid rgba(255,255,255,0.1);">
                <div class="absolute inset-0 opacity-5" style="background-image: radial-gradient(circle at 2px 2px, white 1px, transparent 0); background-size: 30px 30px;"></div>
                <div class="relative w-full max-w-sm p-6 bg-white rounded-xl shadow-lg" x-data="{ serviceMaturity: 62, stabilityRisk: 'Critical Stability Risk', incident: 35, continuity: 55, release: 80 }">
                    <div class="flex flex-col gap-6">
                        <div class="flex justify-between items-end mb-4">
                            <div>
                                <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Service Maturity</span>
                                <h5 class="text-lg font-semibold text-ink">Operational Stability Index</h5>
                            </div>
                            <div class="text-right">
                                <span class="block text-2xl font-extrabold" style="color: #0D9488;" x-text="`${serviceMaturity}%`"></span>
                                <span class="status-indicator" style="--indicator-color: #DC2626;" x-text="stabilityRisk"></span>
                            </div>
                        </div>

                        <div class="space-y-6">
                            <div x-data="{ progress: incident, color: '#DC2626', label: 'Low' }">
                                <div class="flex justify-between text-sm font-semibold mb-2">
                                    <span class="text-slate-700">Incident Management</span>
                                    <span :style="`color: ${color};`" x-text="label"></span>
                                </div>
                                <div class="status-bar">
                                    <div class="status-bar-fill" :style="`--progress-width: ${progress}%; --progress-color: ${color};`"></div>
                                </div>
                            </div>

                            <div x-data="{ progress: continuity, color: '#D97706', label: 'Moderate' }">
                                <div class="flex justify-between text-sm font-semibold mb-2">
                                    <span class="text-slate-700">Service Continuity</span>
                                    <span :style="`color: ${color};`" x-text="label"></span>
                                </div>
                                <div class="status-bar">
                                    <div class="status-bar-fill" :style="`--progress-width: ${progress}%; --progress-color: ${color};`"></div>
                                </div>
                            </div>

                            <div x-data="{ progress: release, color: '#059669', label: 'High' }">
                                <div class="flex justify-between text-sm font-semibold mb-2">
                                    <span class="text-slate-700">Release Governance</span>
                                    <span :style="`color: ${color};`" x-text="label"></span>
                                </div>
                                <div class="status-bar">
                                    <div class="status-bar-fill" :style="`--progress-width: ${progress}%; --progress-color: ${color};`"></div>
                                </div>
                            </div>
                        </div>

                        <div class="pt-6 border-t border-slate-100 flex items-center gap-3">
                            <div class="w-8 h-8 rounded-full bg-teal-100 flex items-center justify-center">
                                <svg class="w-4 h-4 text-teal-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path d="M13 10V3L4 14h7v7l9-11h-7z" />
                                </svg>
                            </div>
                            <span class="text-xs font-semibold text-slate-500">ITIL Framework Aligned Review</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- What is SIR -->
<section class="py-24 md:py-32 bg-gradient-to-b from-white to-cloud">
    <div class="container">
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-12 items-center">
            <div>
                <h2 class="font-manrope text-4xl md:text-5xl font-bold text-ink mb-6">
                    What is a Service Intelligence Review?
                </h2>
                <p class="text-slate text-lg leading-relaxed mb-6">
                    The Service Intelligence Review (SIR) provides an independent evaluation of your IT service environment. We look beyond basic SLAs to assess the actual stability, governance, and maturity of your operational processes.
                </p>
                <p class="text-slate text-lg leading-relaxed">
                    SIR is critical for organisations undergoing significant change or those experiencing persistent service instability. We provide the objective evidence needed to justify investment and drive improvement.
                </p>
            </div>
            <div class="bg-white p-8 md:p-12 rounded-2xl shadow-lg border border-line">
                <h3 class="font-manrope text-2xl font-bold text-ink mb-6">Who it is for</h3>
                <ul class="space-y-4">
                    <li class="flex items-center gap-4 text-slate-700 text-lg">
                        <svg class="w-6 h-6 text-teal-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M20 6 9 17l-5-5" />
                        </svg>
                        <span>IT Directors & Leaders</span>
                    </li>
                    <li class="flex items-center gap-4 text-slate-700 text-lg">
                        <svg class="w-6 h-6 text-teal-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M20 6 9 17l-5-5" />
                        </svg>
                        <span>Service Owners</span>
                    </li>
                    <li class="flex items-center gap-4 text-slate-700 text-lg">
                        <svg class="w-6 h-6 text-teal-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M20 6 9 17l-5-5" />
                        </svg>
                        <span>Heads of IT Operations</span>
                    </li>
                    <li class="flex items-center gap-4 text-slate-700 text-lg">
                        <svg class="w-6 h-6 text-teal-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M20 6 9 17l-5-5" />
                        </svg>
                        <span>ITSM Managers</span>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</section>

<!-- Process Section -->
<section class="py-24 md:py-32 bg-cloud">
    <div class="container">
        <div class="text-center max-w-3xl mx-auto mb-16">
            <span class="inline-block px-4 py-1.5 rounded-full bg-teal-100 text-teal-700 text-xs font-bold uppercase tracking-widest mb-4">
                The SIR Engagement
            </span>
            <h2 class="font-manrope text-4xl md:text-5xl font-bold text-ink mb-6">
                From Diagnostic to Full Review
            </h2>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
            <!-- The Diagnostic -->
            <div class="relative bg-white rounded-2xl border border-line p-8 transition-all duration-300 hover:shadow-lg hover:-translate-y-1">
                <div class="absolute top-0 left-0 w-full h-1 bg-gradient-to-r from-teal-600 to-emerald-500"></div>
                <span class="inline-block px-4 py-1.5 rounded-full bg-teal-100 text-teal-700 text-xs font-bold uppercase tracking-wider mb-6">Step 01</span>
                <h3 class="font-manrope text-2xl font-bold text-ink mb-4">The 15-Minute Diagnostic</h3>
                <p class="text-slate leading-relaxed mb-6">Get an initial snapshot of your service stability. Identify priority areas for investigation and understand how your operations compare to industry benchmarks.</p>
                <x-button variant="secondary" href="/rapid-consulting?type=itsm" class="mt-4" style="background-color: #0D9488; color: white;">Start SIR Diagnostic</x-button>
            </div>

            <!-- Full Review -->
            <div class="relative bg-white rounded-2xl border border-line p-8 transition-all duration-300 hover:shadow-lg hover:-translate-y-1">
                <div class="absolute top-0 left-0 w-full h-1 bg-gradient-to-r from-emerald-500 to-teal-600"></div>
                <span class="inline-block px-4 py-1.5 rounded-full bg-teal-100 text-teal-700 text-xs font-bold uppercase tracking-wider mb-6">Step 02</span>
                <h3 class="font-manrope text-2xl font-bold text-ink mb-4">The Full SIR Engagement</h3>
                <p class="text-slate leading-relaxed mb-4">A deep-dive investigation into your operational environment:</p>
                <ul class="space-y-3 mb-6 text-slate">
                    <li class="flex items-center gap-3">
                        <svg class="w-5 h-5 text-teal-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                        <span>ITSM Process Review (Incident, Change, Problem)</span>
                    </li>
                    <li class="flex items-center gap-3">
                        <svg class="w-5 h-5 text-teal-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                        <span>Governance & Vendor Performance Review</span>
                    </li>
                    <li class="flex items-center gap-3">
                        <svg class="w-5 h-5 text-teal-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                        <span>Service Artefact & Reporting Analysis</span>
                    </li>
                    <li class="flex items-center gap-3">
                        <svg class="w-5 h-5 text-teal-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                        <span>Detailed Remediation Roadmap</span>
                    </li>
                </ul>
                <x-button variant="primary" href="/contact" style="background-color: #0D9488; hover:background-color: #0F766E;">Book SIR Consultation</x-button>
            </div>
        </div>
    </div>
</section>

<!-- Outcomes -->
<section class="py-24 md:py-32 bg-white">
    <div class="container">
        <div class="text-center max-w-3xl mx-auto mb-16">
            <span class="inline-block px-4 py-1.5 rounded-full bg-teal-100 text-teal-700 text-xs font-bold uppercase tracking-widest mb-4">
                Key Outcomes
            </span>
            <h2 class="font-manrope text-4xl md:text-5xl font-bold text-ink mb-6">
                Tangible Service Outcomes
            </h2>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div class="relative bg-white rounded-xl border border-line p-7 transition-all duration-300 hover:shadow-authority hover:-translate-y-1 hover:border-action/30">
                <div class="absolute top-0 left-0 w-1 h-full bg-gradient-to-b from-teal-600 to-emerald-500 rounded-l-xl opacity-0 group-hover:opacity-100 transition-opacity"></div>
                <h3 class="font-manrope font-bold text-xl text-ink mb-3">Stability Assessment</h3>
                <p class="text-slate text-sm leading-relaxed">A quantitative and qualitative view of service stability and operational risk.</p>
            </div>
            <div class="relative bg-white rounded-xl border border-line p-7 transition-all duration-300 hover:shadow-authority hover:-translate-y-1 hover:border-action/30">
                <div class="absolute top-0 left-0 w-1 h-full bg-gradient-to-b from-teal-600 to-emerald-500 rounded-l-xl opacity-0 group-hover:opacity-100 transition-opacity"></div>
                <h3 class="font-manrope font-bold text-xl text-ink mb-3">Maturity Roadmap</h3>
                <p class="text-slate text-sm leading-relaxed">A prioritised plan to improve ITSM maturity and operational efficiency.</p>
            </div>
            <div class="relative bg-white rounded-xl border border-line p-7 transition-all duration-300 hover:shadow-authority hover:-translate-y-1 hover:border-action/30">
                <div class="absolute top-0 left-0 w-1 h-full bg-gradient-to-b from-teal-600 to-emerald-500 rounded-l-xl opacity-0 group-hover:opacity-100 transition-opacity"></div>
                <h3 class="font-manrope font-bold text-xl text-ink mb-3">Governance Insight</h3>
                <p class="text-slate text-sm leading-relaxed">Practical recommendations to strengthen service governance and vendor oversight.</p>
            </div>
        </div>
    </div>
</section>

<!-- Final CTA Band -->
<section class="py-20 md:py-28 bg-gradient-to-r from-teal-600 to-emerald-500 relative overflow-hidden">
    <!-- Decorative background pattern -->
    <div class="absolute inset-0 opacity-10" style="background-image: radial-gradient(circle at 2px 2px, white 1px, transparent 0); background-size: 40px 40px;"></div>

    <div class="container relative z-10 text-center">
        <h2 class="text-3xl md:text-5xl font-bold text-white mb-6 max-w-4xl mx-auto">
            Ready to improve your service stability?
        </h2>
        <p class="text-white/90 text-lg md:text-xl mb-10 max-w-2xl mx-auto leading-relaxed">
            Gain clear insight into your IT service environment with an objective Service Intelligence Review.
        </p>
        <div class="flex justify-center gap-4 flex-wrap">
            <a href="/rapid-consulting?type=itsm" class="inline-flex items-center justify-center px-8 py-4 bg-white text-teal-600 font-bold rounded-xl hover:bg-cloud transition-colors shadow-lg">
                Start SIR Diagnostic
                <svg class="ml-2 w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"></path>
                </svg>
            </a>
            <a href="/contact" class="inline-flex items-center justify-center px-8 py-4 bg-transparent text-white font-bold rounded-xl border-2 border-white/50 hover:bg-white/10 transition-all">
                Book SIR Consultation
            </a>
        </div>
    </div>
</section>
@endsection