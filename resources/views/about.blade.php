@extends('layouts.public')

@section('title', 'About Us — Programme & Service Intelligence (PIR / SIR) | RAB Consulting')

@push('head')
<style>
    /* Hero Animations */
    @keyframes fadeUp {
        from { opacity: 0; transform: translateY(30px); }
        to { opacity: 1; transform: translateY(0); }
    }

    @keyframes float {
        0%, 100% { transform: translateY(0px); }
        50% { transform: translateY(-20px); }
    }

    @keyframes glow {
        0%, 100% { opacity: 0.2; }
        50% { opacity: 0.4; }
    }

    .hero-badge { animation: fadeUp 0.6s ease-out 0ms both; }
    .hero-title { animation: fadeUp 0.6s ease-out 100ms both; }
    .hero-subtitle { animation: fadeUp 0.6s ease-out 200ms both; }
    .hero-ctas { animation: fadeUp 0.6s ease-out 300ms both; }

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
        0% { background-position: 0 0; }
        100% { background-position: 50px 50px; }
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
    <div class="absolute inset-0" style="background: rgba(15, 23, 42, 0.95);"></div>
    <div class="absolute inset-0 animated-grid"></div>
    <div class="floating-particle glow" style="width: 400px; height: 400px; top: -10%; right: -5%;"></div>
    <div class="floating-particle" style="width: 200px; height: 200px; bottom: 10%; left: -5%; animation-delay: 2s;"></div>

    <div class="container relative z-10">
        <div class="max-w-4xl">
            <span class="hero-badge inline-flex items-center gap-2 px-5 py-2 rounded-full mb-6" style="background: rgba(37, 99, 235, 0.15); color: #60a5fa; border: 1px solid rgba(37, 99, 235, 0.2);">
                <span class="inline-block w-1.5 h-1.5 rounded-full" style="background-color: #60a5fa;"></span>
                About RAB Consulting
            </span>

            <h1 class="hero-title font-serif text-white text-4xl sm:text-5xl md:text-6xl font-bold leading-tight mb-6">
                Independent intelligence, <br><span class="hero-word-highlight">senior-led delivery</span>
            </h1>

            <p class="hero-subtitle text-lg md:text-xl leading-relaxed mb-10 max-w-2xl" style="color: #e2e8f0 !important;">
                RAB Consulting is an independent firm specialising in programme leadership and Programme & Service Intelligence (PIR / SIR). We provide organisations with the objective clarity and senior capability needed to navigate complex transformation.
            </p>

            <div class="hero-ctas flex flex-col sm:flex-row gap-4">
                <x-button variant="primary" href="/booking" class="text-base px-8 py-4 group shadow-lg hover:shadow-xl">
                    Book a Consultation
                    <svg class="ml-2 w-5 h-5 group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"></path>
                    </svg>
                </x-button>
            </div>
        </div>
    </div>
</section>

<!-- Values / Approach -->
<section class="py-24 md:py-32 bg-white" id="values">
    <div class="container">
        <div class="max-w-3xl mb-16">
            <span class="inline-block px-4 py-1.5 rounded-full bg-primary/10 text-primary text-xs font-bold uppercase tracking-widest mb-4">
                Our Core Principles
            </span>
            <h2 class="font-manrope text-4xl md:text-5xl font-bold text-ink mb-6">
                Grounded in objectivity and seniority
            </h2>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <!-- Independence -->
            <div class="group relative bg-white rounded-xl border border-line p-7 transition-all duration-300 hover:shadow-authority hover:-translate-y-1 hover:border-action/30">
                <div class="absolute top-0 left-0 w-1 h-full bg-gradient-to-b from-action to-primary rounded-l-xl opacity-0 group-hover:opacity-100 transition-opacity"></div>
                <div class="w-12 h-12 rounded-lg bg-primary/10 flex items-center justify-center mb-5 group-hover:scale-110 transition-transform">
                    <svg class="w-6 h-6 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.03a6.274 6.274 0 01-3.52 5.222l-1.113 1.113a2 2 0 01-2.828 0l-1.113-1.113a6.274 6.274 0 01-3.52-5.222"></path>
                    </svg>
                </div>
                <h3 class="font-manrope font-bold text-xl text-ink mb-3">Independence</h3>
                <p class="text-slate text-sm leading-relaxed">We provide a truly objective external view, free from internal bias or vendor influence. Our priority is the health and success of your delivery.</p>
            </div>

            <!-- Seniority -->
            <div class="group relative bg-white rounded-xl border border-line p-7 transition-all duration-300 hover:shadow-authority hover:-translate-y-1 hover:border-action/30">
                <div class="absolute top-0 left-0 w-1 h-full bg-gradient-to-b from-action to-primary rounded-l-xl opacity-0 group-hover:opacity-100 transition-opacity"></div>
                <div class="w-12 h-12 rounded-lg bg-primary/10 flex items-center justify-center mb-5 group-hover:scale-110 transition-transform">
                    <svg class="w-6 h-6 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
                <h3 class="font-manrope font-bold text-xl text-ink mb-3">Seniority</h3>
                <p class="text-slate text-sm leading-relaxed">Our engagements are led by senior consultants with extensive experience in global enterprises and complex delivery environments.</p>
            </div>

            <!-- Practicality -->
            <div class="group relative bg-white rounded-xl border border-line p-7 transition-all duration-300 hover:shadow-authority hover:-translate-y-1 hover:border-action/30">
                <div class="absolute top-0 left-0 w-1 h-full bg-gradient-to-b from-action to-primary rounded-l-xl opacity-0 group-hover:opacity-100 transition-opacity"></div>
                <div class="w-12 h-12 rounded-lg bg-primary/10 flex items-center justify-center mb-5 group-hover:scale-110 transition-transform">
                    <svg class="w-6 h-6 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                    </svg>
                </div>
                <h3 class="font-manrope font-bold text-xl text-ink mb-3">Practicality</h3>
                <p class="text-slate text-sm leading-relaxed">We don't just provide theory. Our findings are grounded in practical delivery reality, with actionable next steps for recovery and improvement.</p>
            </div>
        </div>
    </div>
</section>

<!-- Experience Section -->
<section class="py-24 md:py-32 bg-cloud">
    <div class="container">
        <div class="max-w-3xl mb-16">
            <span class="inline-block px-4 py-1.5 rounded-full bg-primary/10 text-primary text-xs font-bold uppercase tracking-widest mb-4">
                Our Experience
            </span>
            <h2 class="font-manrope text-4xl md:text-5xl font-bold text-ink mb-6">
                Credibility built through delivery
            </h2>
            <p class="text-slate text-lg leading-relaxed">
                RAB brings a wealth of experience across complex transformation environments, including work delivered within global enterprises and high-growth organisations. We understand the challenges of programme management, ITSM maturity, and large-scale ERP implementations.
            </p>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
            <div class="bg-white p-8 rounded-xl border border-line text-center transition-all hover:shadow-md">
                <p class="font-bold text-primary">Global Enterprise Experience</p>
            </div>
            <div class="bg-white p-8 rounded-xl border border-line text-center transition-all hover:shadow-md">
                <p class="font-bold text-primary">Complex Transformation</p>
            </div>
            <div class="bg-white p-8 rounded-xl border border-line text-center transition-all hover:shadow-md">
                <p class="font-bold text-primary">PIR / SIR Frameworks</p>
            </div>
            <div class="bg-white p-8 rounded-xl border border-line text-center transition-all hover:shadow-md">
                <p class="font-bold text-primary">Recovery & Remediation</p>
            </div>
        </div>
    </div>
</section>

<!-- Philosophy Section -->
<section class="py-24 md:py-32 bg-white">
    <div class="container">
        <div class="max-w-4xl mx-auto relative bg-highlight p-8 md:p-16 rounded-3xl border border-primary/10 shadow-inner">
            <div class="relative z-10">
                <h2 class="font-manrope text-3xl md:text-4xl font-bold text-ink mb-8">Our Philosophy</h2>
                <p class="text-xl md:text-2xl text-primary font-semibold leading-relaxed mb-6" style="font-style: italic;">
                    "Controlled execution is the only way to deliver value. Most transformation challenges are not caused by technology alone, but by gaps in execution discipline."
                </p>
                <p class="text-slate text-lg leading-relaxed mb-10">
                    RAB exists to close those gaps — ensuring that technology and delivery investments result in measurable business outcomes. We provide the senior leadership and objective oversight needed to turn uncertainty into clarity.
                </p>
                <a href="/booking" class="inline-flex items-center justify-center px-8 py-4 bg-primary text-white font-bold rounded-xl hover:bg-action transition-all shadow-lg group">
                    Book a Consulting Discussion
                    <svg class="ml-2 w-5 h-5 group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"></path>
                    </svg>
                </a>
            </div>
        </div>
    </div>
</section>

<!-- Final CTA Band -->
<section class="py-20 md:py-28 bg-gradient-to-r from-primary to-action relative overflow-hidden">
    <div class="absolute inset-0 opacity-10" style="background-image: radial-gradient(circle at 2px 2px, white 1px, transparent 0); background-size: 40px 40px;"></div>
    <div class="container relative z-10 text-center">
        <h2 class="text-3xl md:text-5xl font-bold text-white mb-6 max-w-4xl mx-auto">
            Ready for objective clarity?
        </h2>
        <p class="text-white/90 text-lg md:text-xl mb-10 max-w-2xl mx-auto leading-relaxed">
            Whether you need a strategic review or immediate delivery support, our senior consultants are ready to help you regain control.
        </p>
        <div class="flex justify-center gap-4 flex-wrap">
            <a href="/booking" class="inline-flex items-center justify-center px-8 py-4 bg-white text-primary font-bold rounded-xl hover:bg-cloud transition-colors shadow-lg">
                Book Consultation
                <svg class="ml-2 w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 2 la-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"></path>
                </svg>
            </a>
            <a href="/rapid-consulting" class="inline-flex items-center justify-center px-8 py-4 bg-transparent text-white font-bold rounded-xl border-2 border-white/50 hover:bg-white/10 transition-all">
                Start Diagnostic
            </a>
        </div>
    </div>
</section>
@endsection
