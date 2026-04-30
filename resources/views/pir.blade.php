@extends('layouts.public')

@section('title', 'PIR: Programme Intelligence Review — Programme & Service Intelligence (PIR / SIR)')

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
<!-- PIR Hero -->
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
                <span class="hero-badge inline-flex items-center gap-2 px-5 py-2 rounded-full mb-6" style="background: rgba(37, 99, 235, 0.15); color: #60a5fa; border: 1px solid rgba(37, 99, 235, 0.2);">
                    <span class="inline-block w-1.5 h-1.5 rounded-full" style="background-color: #60a5fa;"></span>
                    Programme Intelligence Review
                </span>

                <h1 class="hero-title font-serif text-white text-4xl sm:text-5xl md:text-6xl font-bold leading-tight mb-6">
                    Independent insight for <br><span class="hero-word-highlight">stronger programme control</span>
                </h1>

                <p class="hero-subtitle text-lg md:text-xl leading-relaxed mb-10 max-w-2xl" style="color: #e2e8f0 !important;">
                    An objective external view of programme health, risks, and required next actions. PIR provides sponsors and directors with the delivery confidence needed for complex transformation.
                </p>

                <div class="hero-ctas flex flex-col sm:flex-row gap-4">
                    <x-button variant="primary" href="/rapid-consulting?type=phi" class="text-base px-8 py-4 group shadow-lg hover:shadow-xl">
                        Start PIR Diagnostic
                        <svg class="ml-2 w-5 h-5 group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"></path>
                        </svg>
                    </x-button>
                    <x-button variant="secondary" href="/contact" class="text-base px-8 py-4" style="border-color: #475569; color: #e2e8f0;">
                        Book PIR Consultation
                    </x-button>
                </div>
                <p class="text-xs mt-4 italic" style="color: #94a3b8;">Estimated completion time: 15 minutes.</p>
            </div>

            <!-- Hero Visual -->
            <div class="relative flex justify-center items-center p-6 lg:p-10 rounded-3xl overflow-hidden shadow-2xl" style="background: linear-gradient(145deg, #1A294C 0%, #0F172A 100%); border: 1px solid rgba(255,255,255,0.1);">
                <div class="absolute inset-0 opacity-5" style="background-image: radial-gradient(circle at 2px 2px, white 1px, transparent 0); background-size: 30px 30px;"></div>
                <div class="relative w-full max-w-sm p-6 bg-white rounded-xl shadow-lg" x-data="{ deliveryHealth: 78, leadIndicator: 12, governance: 65, stakeholder: 85, risk: 40 }">
                    <div class="flex flex-col gap-6">
                        <div class="flex justify-between items-end mb-4">
                            <div>
                                <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Delivery Health</span>
                                <h5 class="text-lg font-semibold text-ink">Programme Pillar Analysis</h5>
                            </div>
                            <div class="text-right">
                                <span class="block text-2xl font-extrabold text-primary" x-text="`${deliveryHealth}%`"></span>
                                <span class="status-indicator" style="--indicator-color: #10B981;">+<span x-text="leadIndicator"></span>% Lead Indicator</span>
                            </div>
                        </div>

                        <div class="space-y-6">
                            <div x-data="{ progress: governance, color: '#D97706', label: 'Amber' }">
                                <div class="flex justify-between text-sm font-semibold mb-2">
                                    <span class="text-slate-700">Governance & Control</span>
                                    <span :style="`color: ${color};`" x-text="label"></span>
                                </div>
                                <div class="status-bar">
                                    <div class="status-bar-fill" :style="`--progress-width: ${progress}%; --progress-color: ${color};`"></div>
                                </div>
                            </div>

                            <div x-data="{ progress: stakeholder, color: '#059669', label: 'Green' }">
                                <div class="flex justify-between text-sm font-semibold mb-2">
                                    <span class="text-slate-700">Stakeholder Alignment</span>
                                    <span :style="`color: ${color};`" x-text="label"></span>
                                </div>
                                <div class="status-bar">
                                    <div class="status-bar-fill" :style="`--progress-width: ${progress}%; --progress-color: ${color};`"></div>
                                </div>
                            </div>

                            <div x-data="{ progress: risk, color: '#DC2626', label: 'Red' }">
                                <div class="flex justify-between text-sm font-semibold mb-2">
                                    <span class="text-slate-700">Risk Remediation</span>
                                    <span :style="`color: ${color};`" x-text="label"></span>
                                </div>
                                <div class="status-bar">
                                    <div class="status-bar-fill" :style="`--progress-width: ${progress}%; --progress-color: ${color};`"></div>
                                </div>
                            </div>
                        </div>

                        <div class="pt-6 border-t border-slate-100 flex items-center gap-3">
                            <div class="w-8 h-8 rounded-full bg-blue-100 flex items-center justify-center">
                                <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                                </svg>
                            </div>
                            <span class="text-xs font-semibold text-slate-500">PIR Intelligence Verified</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- What is PIR -->
<section class="py-24 md:py-32 bg-gradient-to-b from-white to-cloud">
    <div class="container">
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-12 items-center">
            <div>
                <h2 class="font-manrope text-4xl md:text-5xl font-bold text-ink mb-6">
                    What is a Programme Intelligence Review?
                </h2>
                <p class="text-slate text-lg leading-relaxed mb-6">
                    The Programme Intelligence Review (PIR) is a structured, independent assessment of your transformation or ERP programme. It goes beyond simple status reporting to provide a deep, objective analysis of the true state of delivery.
                </p>
                <p class="text-slate text-lg leading-relaxed">
                    Designed for high-intensity environments, PIR identifies the "silent risks" and governance gaps that often lead to delays and cost overruns.
                </p>
            </div>
            <div class="bg-white p-8 md:p-12 rounded-2xl shadow-lg border border-line">
                <h3 class="font-manrope text-2xl font-bold text-ink mb-6">Who it is for</h3>
                <ul class="space-y-4">
                    <li class="flex items-center gap-4 text-slate-700 text-lg">
                        <svg class="w-6 h-6 text-primary flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M20 6 9 17l-5-5" />
                        </svg>
                        <span>Programme Directors & Leads</span>
                    </li>
                    <li class="flex items-center gap-4 text-slate-700 text-lg">
                        <svg class="w-6 h-6 text-primary flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M20 6 9 17l-5-5" />
                        </svg>
                        <span>Transformation Leaders</span>
                    </li>
                    <li class="flex items-center gap-4 text-slate-700 text-lg">
                        <svg class="w-6 h-6 text-primary flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M20 6 9 17l-5-5" />
                        </svg>
                        <span>Executive Sponsors</span>
                    </li>
                    <li class="flex items-center gap-4 text-slate-700 text-lg">
                        <svg class="w-6 h-6 text-primary flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M20 6 9 17l-5-5" />
                        </svg>
                        <span>CIO & CTO Stakeholders</span>
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
            <span class="inline-block px-4 py-1.5 rounded-full bg-primary/10 text-primary text-xs font-bold uppercase tracking-widest mb-4">
                The PIR Engagement
            </span>
            <h2 class="font-manrope text-4xl md:text-5xl font-bold text-ink mb-6">
                From Diagnostic to Deep Dive
            </h2>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
            <!-- The Diagnostic -->
            <div class="relative bg-white rounded-2xl border border-line p-8 transition-all duration-300 hover:shadow-lg hover:-translate-y-1">
                <div class="absolute top-0 left-0 w-full h-1 bg-gradient-to-r from-primary to-action"></div>
                <span class="inline-block px-4 py-1.5 rounded-full bg-primary/10 text-primary text-xs font-bold uppercase tracking-wider mb-6">Step 01</span>
                <h3 class="font-manrope text-2xl font-bold text-ink mb-4">The 15-Minute Diagnostic</h3>
                <p class="text-slate leading-relaxed mb-6">Gain immediate initial insight through our structured online assessment. It helps identify high-level risks and determines if a full consultant-led review is necessary.</p>
                <x-button variant="secondary" href="/rapid-consulting?type=phi" class="mt-4">Start PIR Diagnostic</x-button>
            </div>

            <!-- Full Review -->
            <div class="relative bg-white rounded-2xl border border-line p-8 transition-all duration-300 hover:shadow-lg hover:-translate-y-1">
                <div class="absolute top-0 left-0 w-full h-1 bg-gradient-to-r from-action to-primary"></div>
                <span class="inline-block px-4 py-1.5 rounded-full bg-primary/10 text-primary text-xs font-bold uppercase tracking-wider mb-6">Step 02</span>
                <h3 class="font-manrope text-2xl font-bold text-ink mb-4">The Full PIR Engagement</h3>
                <p class="text-slate leading-relaxed mb-4">A comprehensive, senior-led investigation including:</p>
                <ul class="space-y-3 mb-6 text-slate">
                    <li class="flex items-center gap-3">
                        <svg class="w-5 h-5 text-primary flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                        <span>Stakeholder & Team Interviews</span>
                    </li>
                    <li class="flex items-center gap-3">
                        <svg class="w-5 h-5 text-primary flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                        <span>Deep Document & Artefact Review</span>
                    </li>
                    <li class="flex items-center gap-3">
                        <svg class="w-5 h-5 text-primary flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                        <span>Technical & Governance Analysis</span>
                    </li>
                    <li class="flex items-center gap-3">
                        <svg class="w-5 h-5 text-primary flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                        <span>Findings workshop & Presentation</span>
                    </li>
                </ul>
                <x-button variant="primary" href="/contact">Book PIR Consultation</x-button>
            </div>
        </div>
    </div>
</section>

<!-- Outcomes -->
<section class="py-24 md:py-32 bg-white">
    <div class="container">
        <div class="text-center max-w-3xl mx-auto mb-16">
            <span class="inline-block px-4 py-1.5 rounded-full bg-primary/10 text-primary text-xs font-bold uppercase tracking-widest mb-4">
                Key Outcomes
            </span>
            <h2 class="font-manrope text-4xl md:text-5xl font-bold text-ink mb-6">
                Tangible Outcomes
            </h2>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div class="relative bg-white rounded-xl border border-line p-7 transition-all duration-300 hover:shadow-authority hover:-translate-y-1 hover:border-action/30">
                <div class="absolute top-0 left-0 w-1 h-full bg-gradient-to-b from-action to-primary rounded-l-xl opacity-0 group-hover:opacity-100 transition-opacity"></div>
                <h3 class="font-manrope font-bold text-xl text-ink mb-3">Delivery Clarity</h3>
                <p class="text-slate text-sm leading-relaxed">A clear, objective view of the programme's actual status versus stated progress.</p>
            </div>
            <div class="relative bg-white rounded-xl border border-line p-7 transition-all duration-300 hover:shadow-authority hover:-translate-y-1 hover:border-action/30">
                <div class="absolute top-0 left-0 w-1 h-full bg-gradient-to-b from-action to-primary rounded-l-xl opacity-0 group-hover:opacity-100 transition-opacity"></div>
                <h3 class="font-manrope font-bold text-xl text-ink mb-3">Priority Actions</h3>
                <p class="text-slate text-sm leading-relaxed">A structured roadmap of immediate and long-term actions to mitigate critical risks.</p>
            </div>
            <div class="relative bg-white rounded-xl border border-line p-7 transition-all duration-300 hover:shadow-authority hover:-translate-y-1 hover:border-action/30">
                <div class="absolute top-0 left-0 w-1 h-full bg-gradient-to-b from-action to-primary rounded-l-xl opacity-0 group-hover:opacity-100 transition-opacity"></div>
                <h3 class="font-manrope font-bold text-xl text-ink mb-3">Optional Recovery</h3>
                <p class="text-slate text-sm leading-relaxed">If required, RAB can transition from review into practical recovery and remediation support.</p>
            </div>
        </div>
    </div>
</section>

<!-- Final CTA Band -->
<section class="py-20 md:py-28 bg-gradient-to-r from-primary to-action relative overflow-hidden">
    <!-- Decorative background pattern -->
    <div class="absolute inset-0 opacity-10" style="background-image: radial-gradient(circle at 2px 2px, white 1px, transparent 0); background-size: 40px 40px;"></div>

    <div class="container relative z-10 text-center">
        <h2 class="text-3xl md:text-5xl font-bold text-white mb-6 max-w-4xl mx-auto">
            Ready for unbiased programme clarity?
        </h2>
        <p class="text-white/90 text-lg md:text-xl mb-10 max-w-2xl mx-auto leading-relaxed">
            Gain the confidence to navigate complex transformations with an objective Programme Intelligence Review.
        </p>
        <div class="flex justify-center gap-4 flex-wrap">
            <a href="/rapid-consulting?type=phi" class="inline-flex items-center justify-center px-8 py-4 bg-white text-primary font-bold rounded-xl hover:bg-cloud transition-colors shadow-lg">
                Start PIR Diagnostic
                <svg class="ml-2 w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"></path>
                </svg>
            </a>
            <a href="/contact" class="inline-flex items-center justify-center px-8 py-4 bg-transparent text-white font-bold rounded-xl border-2 border-white/50 hover:bg-white/10 transition-all">
                Book PIR Consultation
            </a>
        </div>
    </div>
</section>
@endsection