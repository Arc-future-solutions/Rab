@extends('layouts.public')

@section('title', 'Our Services — Programme & Service Intelligence (PIR / SIR) & Delivery Support')

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
</style>
@endpush

@section('content')
<!-- Hero Section -->
<section class="relative py-24 md:py-32 flex items-center overflow-hidden" style="background-color: #0F172A;">
    <!-- Dark overlay to ensure text readability -->
    <div class="absolute inset-0" style="background: rgba(15, 23, 42, 0.95);"></div>

    <!-- Animated Geometric Grid Background -->
    <div class="absolute inset-0 animated-grid"></div>

    <!-- Floating accent particles -->
    <div class="floating-particle glow" style="width: 400px; height: 400px; top: -10%; right: -5%;"></div>
    <div class="floating-particle" style="width: 200px; height: 200px; bottom: 10%; left: -5%; animation-delay: 2s;"></div>

    <div class="container relative z-10">
        <div class="max-w-4xl">
            <span class="hero-badge inline-flex items-center gap-2 px-5 py-2 rounded-full mb-6" style="background: rgba(37, 99, 235, 0.15); color: #60a5fa; border: 1px solid rgba(37, 99, 235, 0.2);">
                <span class="inline-block w-1.5 h-1.5 rounded-full" style="background-color: #60a5fa;"></span>
                Our Services
            </span>

            <h1 class="hero-title font-serif text-white text-4xl sm:text-5xl md:text-6xl font-bold leading-tight mb-6">
                Senior-led expertise for <br><span class="hero-word-highlight">complex delivery</span>
            </h1>

            <p class="hero-subtitle text-lg md:text-xl leading-relaxed mb-10 max-w-2xl" style="color: #e2e8f0 !important;">
                RAB Consulting provides a comprehensive range of advisory and delivery support services designed to help organisations navigate the complexities of large-scale programmes and IT service environments.
            </p>

            <div class="hero-ctas flex flex-col sm:flex-row gap-4">
                <x-button variant="primary" href="/booking" class="text-base px-8 py-4 group shadow-lg hover:shadow-xl">
                    Discuss your requirements
                    <svg class="ml-2 w-5 h-5 group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"></path>
                    </svg>
                </x-button>
            </div>
        </div>
    </div>
</section>

<!-- Leadership & Delivery -->
<section class="py-24 md:py-32 bg-white" id="leadership">
    <div class="container">
        <div class="max-w-3xl mb-16">
            <span class="inline-block px-4 py-1.5 rounded-full bg-primary/10 text-primary text-xs font-bold uppercase tracking-widest mb-4">
                01. Leadership & Delivery
            </span>
            <h2 class="font-manrope text-4xl md:text-5xl font-bold text-ink mb-6">
                Senior direction and practical control
            </h2>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
            <!-- Card 1 -->
            <div class="group relative bg-white rounded-xl border border-line p-7 transition-all duration-300 hover:shadow-authority hover:-translate-y-1 hover:border-action/30">
                <div class="absolute top-0 left-0 w-1 h-full bg-gradient-to-b from-action to-primary rounded-l-xl opacity-0 group-hover:opacity-100 transition-opacity"></div>
                <div class="w-12 h-12 rounded-lg bg-primary/10 flex items-center justify-center mb-5 group-hover:scale-110 transition-transform">
                    <svg class="w-6 h-6 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.03a6.274 6.274 0 01-3.52 5.222l-1.113 1.113a2 2 0 01-2.828 0l-1.113-1.113a6.274 6.274 0 01-3.52-5.222"></path>
                    </svg>
                </div>
                <h3 class="font-manrope font-bold text-xl text-ink mb-3">Programme Management</h3>
                <p class="text-slate text-sm leading-relaxed">Expert direction for multi-stream transformation programmes, ensuring alignment with business objectives and robust risk management.</p>
            </div>

            <!-- Card 2 -->
            <div class="group relative bg-white rounded-xl border border-line p-7 transition-all duration-300 hover:shadow-authority hover:-translate-y-1 hover:border-action/30">
                <div class="absolute top-0 left-0 w-1 h-full bg-gradient-to-b from-action to-primary rounded-l-xl opacity-0 group-hover:opacity-100 transition-opacity"></div>
                <div class="w-12 h-12 rounded-lg bg-primary/10 flex items-center justify-center mb-5 group-hover:scale-110 transition-transform">
                    <svg class="w-6 h-6 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
                <h3 class="font-manrope font-bold text-xl text-ink mb-3">Project Management</h3>
                <p class="text-slate text-sm leading-relaxed">Focused delivery support for critical workstreams, ERP implementations, and high-stakes change initiatives.</p>
            </div>

            <!-- Card 3 -->
            <div class="group relative bg-white rounded-xl border border-line p-7 transition-all duration-300 hover:shadow-authority hover:-translate-y-1 hover:border-action/30">
                <div class="absolute top-0 left-0 w-1 h-full bg-gradient-to-b from-action to-primary rounded-l-xl opacity-0 group-hover:opacity-100 transition-opacity"></div>
                <div class="w-12 h-12 rounded-lg bg-primary/10 flex items-center justify-center mb-5 group-hover:scale-110 transition-transform">
                    <svg class="w-6 h-6 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                    </svg>
                </div>
                <h3 class="font-manrope font-bold text-xl text-ink mb-3">PIR / SIR Support</h3>
                <p class="text-slate text-sm leading-relaxed">Structured executive insight into programme health, delivery risk and operational readiness through independent review.</p>
            </div>

            <!-- Card 4 -->
            <div class="group relative bg-white rounded-xl border border-line p-7 transition-all duration-300 hover:shadow-authority hover:-translate-y-1 hover:border-action/30">
                <div class="absolute top-0 left-0 w-1 h-full bg-gradient-to-b from-action to-primary rounded-l-xl opacity-0 group-hover:opacity-100 transition-opacity"></div>
                <div class="w-12 h-12 rounded-lg bg-primary/10 flex items-center justify-center mb-5 group-hover:scale-110 transition-transform">
                    <svg class="w-6 h-6 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                    </svg>
                </div>
                <h3 class="font-manrope font-bold text-xl text-ink mb-3">Recovery & Remediation</h3>
                <p class="text-slate text-sm leading-relaxed">Stabilising failing projects, identifying root causes, and executing remediation plans to get delivery back on track.</p>
            </div>
        </div>
    </div>
</section>

<!-- Review Products -->
<section class="py-24 md:py-32 bg-cloud" id="reviews">
    <div class="container">
        <div class="max-w-3xl mb-16">
            <span class="inline-block px-4 py-1.5 rounded-full bg-primary/10 text-primary text-xs font-bold uppercase tracking-widest mb-4">
                02. Intelligence Reviews
            </span>
            <h2 class="font-manrope text-4xl md:text-5xl font-bold text-ink mb-6">
                Objective insight for leadership
            </h2>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
            <!-- PIR Card -->
            <div class="group relative bg-white rounded-2xl border-2 border-primary/20 p-8 transition-all duration-300 hover:shadow-2xl hover:border-primary/40 hover:-translate-y-2 overflow-hidden">
                <div class="absolute top-0 left-0 w-full h-1 bg-gradient-to-r from-primary to-action"></div>

                <div class="relative">
                    <span class="inline-flex items-center gap-2 px-4 py-1.5 rounded-full bg-primary/10 text-primary text-xs font-bold uppercase tracking-wider mb-6">
                        Programme Focus
                    </span>

                    <h3 class="font-manrope text-5xl font-bold text-primary mb-2">PIR</h3>
                    <h4 class="text-xl font-semibold text-ink mb-4">Programme Intelligence Review</h4>
                    <p class="text-slate leading-relaxed mb-6">Independent review of programme health, transformation trajectory, and delivery risk.</p>

                    <ul class="space-y-3 mb-8">
                        <li class="flex items-start gap-3 text-sm text-slate">
                            <svg class="w-5 h-5 text-primary mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                            </svg>
                            <span>Independent health & risk assessment</span>
                        </li>
                        <li class="flex items-start gap-3 text-sm text-slate">
                            <svg class="w-5 h-5 text-primary mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                            </svg>
                            <span>ERP & transformation trajectory analysis</span>
                        </li>
                        <li class="flex items-start gap-3 text-sm text-slate">
                            <svg class="w-5 h-5 text-primary mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                            </svg>
                            <span>Actionable recovery & remediation paths</span>
                        </li>
                    </ul>

                    <x-button variant="primary" href="/pir" class="w-full justify-center py-4">Explore PIR</x-button>
                </div>
            </div>

            <!-- SIR Card -->
            <div class="group relative bg-white rounded-2xl border-2 border-teal-500/20 p-8 transition-all duration-300 hover:shadow-2xl hover:border-teal-500/40 hover:-translate-y-2 overflow-hidden">
                <div class="absolute top-0 left-0 w-full h-1 bg-gradient-to-r from-teal-600 to-emerald-500"></div>

                <div class="relative">
                    <span class="inline-flex items-center gap-2 px-4 py-1.5 rounded-full bg-teal-100 text-teal-700 text-xs font-bold uppercase tracking-wider mb-6">
                        Service Focus
                    </span>

                    <h3 class="font-manrope text-5xl font-bold text-teal-600 mb-2">SIR</h3>
                    <h4 class="text-xl font-semibold text-ink mb-4">Service Intelligence Review</h4>
                    <p class="text-slate leading-relaxed mb-6">Independent review of IT service stability, operational governance, and ITSM maturity.</p>

                    <ul class="space-y-3 mb-8">
                        <li class="flex items-start gap-3 text-sm text-slate">
                            <svg class="w-5 h-5 text-teal-600 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                            </svg>
                            <span>Service stability & operational risk review</span>
                        </li>
                        <li class="flex items-start gap-3 text-sm text-slate">
                            <svg class="w-5 h-5 text-teal-600 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                            </svg>
                            <span>ITSM process effectiveness assessment</span>
                        </li>
                        <li class="flex items-start gap-3 text-sm text-slate">
                            <svg class="w-5 h-5 text-teal-600 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                            </svg>
                            <span>Operational governance & maturity roadmap</span>
                        </li>
                    </ul>

                    <x-button variant="primary" href="/sir" class="w-full justify-center py-4 bg-teal-600 hover:bg-teal-700">Explore SIR</x-button>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Capability Support -->
<section class="py-24 md:py-32 bg-white" id="augmentation">
    <div class="container">
        <div class="max-w-3xl mb-16">
            <span class="inline-block px-4 py-1.5 rounded-full bg-primary/10 text-primary text-xs font-bold uppercase tracking-widest mb-4">
                03. Capability Support
            </span>
            <h2 class="font-manrope text-4xl md:text-5xl font-bold text-ink mb-6">
                Flexible reinforcing for your teams
            </h2>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
            <!-- Card 1 -->
            <div class="group relative bg-white rounded-xl border border-line p-7 transition-all duration-300 hover:shadow-authority hover:-translate-y-1 hover:border-action/30">
                <div class="absolute top-0 left-0 w-1 h-full bg-gradient-to-b from-action to-primary rounded-l-xl opacity-0 group-hover:opacity-100 transition-opacity"></div>
                <div class="w-12 h-12 rounded-lg bg-primary/10 flex items-center justify-center mb-5 group-hover:scale-110 transition-transform">
                    <svg class="w-6 h-6 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                    </svg>
                </div>
                <h3 class="font-manrope font-bold text-xl text-ink mb-3">Team Augmentation</h3>
                <p class="text-slate text-sm leading-relaxed">High-calibre senior resource to reinforce your internal teams during peak delivery phases or specialist gaps.</p>
            </div>

            <!-- Card 2 -->
            <div class="group relative bg-white rounded-xl border border-line p-7 transition-all duration-300 hover:shadow-authority hover:-translate-y-1 hover:border-action/30">
                <div class="absolute top-0 left-0 w-1 h-full bg-gradient-to-b from-action to-primary rounded-l-xl opacity-0 group-hover:opacity-100 transition-opacity"></div>
                <div class="w-12 h-12 rounded-lg bg-primary/10 flex items-center justify-center mb-5 group-hover:scale-110 transition-transform">
                    <svg class="w-6 h-6 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                    </svg>
                </div>
                <h3 class="font-manrope font-bold text-xl text-ink mb-3">Business Consulting</h3>
                <p class="text-slate text-sm leading-relaxed">Practical advisory to improve operational clarity, decision quality, and commercial outcomes across the business.</p>
            </div>

            <!-- Card 3 -->
            <div class="group relative bg-white rounded-xl border border-line p-7 transition-all duration-300 hover:shadow-authority hover:-translate-y-1 hover:border-action/30">
                <div class="absolute top-0 left-0 w-1 h-full bg-gradient-to-b from-action to-primary rounded-l-xl opacity-0 group-hover:opacity-100 transition-opacity"></div>
                <div class="w-12 h-12 rounded-lg bg-primary/10 flex items-center justify-center mb-5 group-hover:scale-110 transition-transform">
                    <svg class="w-6 h-6 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.548a3 3 0 00-3.536 0l-.548-.548z"></path>
                    </svg>
                </div>
                <h3 class="font-manrope font-bold text-xl text-ink mb-3">AI & Automation</h3>
                <p class="text-slate text-sm leading-relaxed">Pragmatic digital enablement through AI and process automation focused on tangible business efficiency.</p>
            </div>

            <!-- Card 4 -->
            <div class="group relative bg-white rounded-xl border border-line p-7 transition-all duration-300 hover:shadow-authority hover:-translate-y-1 hover:border-action/30">
                <div class="absolute top-0 left-0 w-1 h-full bg-gradient-to-b from-action to-primary rounded-l-xl opacity-0 group-hover:opacity-100 transition-opacity"></div>
                <div class="w-12 h-12 rounded-lg bg-primary/10 flex items-center justify-center mb-5 group-hover:scale-110 transition-transform">
                    <svg class="w-6 h-6 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4"></path>
                    </svg>
                </div>
                <h3 class="font-manrope font-bold text-xl text-ink mb-3">Development</h3>
                <p class="text-slate text-sm leading-relaxed">Targeted development capability to support custom digital requirements and integration projects.</p>
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
            Discuss how we can support your business
        </h2>
        <p class="text-white/90 text-lg md:text-xl mb-10 max-w-2xl mx-auto leading-relaxed">
            Whether you need a one-off independent review or long-term leadership support, our senior consultants are ready to help.
        </p>
        <div class="flex justify-center gap-4 flex-wrap">
            <a href="/contact" class="inline-flex items-center justify-center px-8 py-4 bg-white text-primary font-bold rounded-xl hover:bg-cloud transition-colors shadow-lg">
                Book a Consulting Discussion
                <svg class="ml-2 w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"></path>
                </svg>
            </a>
        </div>
    </div>
</section>
@endsection