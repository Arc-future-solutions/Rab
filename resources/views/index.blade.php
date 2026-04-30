@extends('layouts.public')

@section('title', 'RAB Consulting — Programme & Service Intelligence (PIR / SIR), Programme Leadership & Recovery')

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
.hero-trust { animation: fadeUp 0.6s ease-out 400ms both; }

.hero-word-highlight {
    background: linear-gradient(135deg, #60a5fa 0%, #3b82f6 100%);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
    position: relative;
    display: inline-block;
    font-weight: 800;
}

.hero-word-highlight::after {
    content: '';
    position: absolute;
    bottom: -8px;
    left: 0;
    width: 100%;
    height: 3px;
    background: linear-gradient(90deg, #3b82f6, #60a5fa);
    border-radius: 2px;
    opacity: 0.6;
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
<section class="relative min-h-screen flex items-center overflow-hidden" style="background-color: #0F172A;">
    <!-- Dark overlay to ensure text readability -->
    <div class="absolute inset-0" style="background: rgba(15, 23, 42, 0.95);"></div>

    <!-- Animated Geometric Grid Background -->
    <div class="absolute inset-0 animated-grid"></div>

    <!-- Floating accent particles -->
    <div class="floating-particle glow" style="width: 500px; height: 500px; top: -10%; right: -5%;"></div>
    <div class="floating-particle" style="width: 300px; height: 300px; bottom: 10%; left: -5%; animation-delay: 2s;"></div>

    <div class="container relative z-10">
        <div class="max-w-5xl mx-auto text-center md:text-left">
            <span class="hero-badge inline-flex items-center gap-2 px-5 py-2 rounded-full" style="background: rgba(37, 99, 235, 0.15); color: #60a5fa; border: 1px solid rgba(37, 99, 235, 0.2);">
                <span class="inline-block w-1.5 h-1.5 rounded-full" style="background-color: #60a5fa; animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;"></span>
                Senior-led transformation, recovery and assurance
            </span>

            <h1 class="hero-title font-serif text-white text-4xl sm:text-5xl md:text-6xl lg:text-7xl font-bold leading-tight mt-6 mb-6">
                We lead, assess, and <span class="hero-word-highlight">deliver</span><br>
                <span style="color: #ffffff;">complex transformation programmes.</span>
            </h1>

            <p class="hero-subtitle text-base sm:text-lg md:text-xl leading-relaxed mb-8 max-w-3xl mx-auto md:mx-0" style="color: #e2e8f0 !important;">
                Programme Director–level leadership across ERP, digital and IT service transformations, including Programme & Service Intelligence (PIR/SIR) and programme recovery.
            </p>

            <div class="hero-ctas flex flex-col sm:flex-row gap-4 justify-center md:justify-start mb-10">
                <x-button variant="primary" href="/booking" class="text-base px-8 py-4 group shadow-lg hover:shadow-xl">
                    Speak to a Programme Director
                    <svg class="ml-2 w-5 h-5 group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"></path></svg>
                </x-button>
                <x-button variant="secondary" href="/rapid-consulting" class="text-base px-8 py-4" style="border-color: #475569; color: #e2e8f0;">
                    Start 15-minute Diagnostic
                </x-button>
            </div>

            <div class="hero-trust pt-8 border-t" style="border-color: rgba(255, 255, 255, 0.1);">
                <p class="text-xs sm:text-sm italic max-w-xl mx-auto md:mx-0" style="color: #94a3b8;">
                    Experience includes global and enterprise delivery environments such as Unilever, IWG and complex UK transformation programmes.
                </p>
            </div>
        </div>
    </div>
</section>

<!-- Authority Strip -->
<div class="bg-white border-b border-line/50 py-6 overflow-hidden">
    <div class="container">
        <div class="flex flex-wrap items-center justify-center gap-x-8 gap-y-3">
            <span class="text-sm font-medium text-slate-500 uppercase tracking-wider">Trusted by leaders at:</span>
            <div class="flex flex-wrap items-center gap-8 opacity-60 grayscale">
                <span class="text-slate-400 font-semibold">Global Enterprises</span>
                <span class="text-slate-300">•</span>
                <span class="text-slate-400 font-semibold">FTSE Companies</span>
                <span class="text-slate-300">•</span>
                <span class="text-slate-400 font-semibold">UK Government</span>
                <span class="text-slate-300">•</span>
                <span class="text-slate-400 font-semibold">Private Equity</span>
            </div>
        </div>
    </div>
</div>

<!-- What RAB Does -->
<section class="py-20 md:py-28 bg-gradient-to-b from-cloud to-white">
    <div class="container">
        <div class="text-center max-w-3xl mx-auto mb-16">
            <span class="inline-block px-4 py-1.5 rounded-full bg-primary/10 text-primary text-xs font-bold uppercase tracking-widest mb-4">
                Our Capabilities
            </span>
            <h2 class="font-manrope text-4xl md:text-5xl font-bold text-ink mb-6">
                Specialist support for complex environments
            </h2>
            <p class="text-slate text-lg leading-relaxed">
                Senior-led expertise delivered through focused capabilities designed for organisations facing transformation, recovery, or the need for independent assurance.
            </p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <!-- Card 1 -->
            <div class="group relative bg-white rounded-xl border border-line p-7 transition-all duration-300 hover:shadow-authority hover:-translate-y-1 hover:border-action/30">
                <div class="absolute top-0 left-0 w-1 h-full bg-gradient-to-b from-action to-primary rounded-l-xl opacity-0 group-hover:opacity-100 transition-opacity"></div>
                <div class="w-12 h-12 rounded-lg bg-primary/10 flex items-center justify-center mb-5 group-hover:scale-110 transition-transform">
                    <svg class="w-6 h-6 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path></svg>
                </div>
                <h3 class="font-manrope font-bold text-xl text-ink mb-3">Programme & Project Leadership</h3>
                <p class="text-slate text-sm leading-relaxed mb-5">Senior-led direction, control and delivery support across transformation, ERP and complex change initiatives.</p>
                <a href="/services#leadership" class="inline-flex items-center text-sm font-medium text-action hover:text-primary transition-colors group/link">
                    Learn more
                    <svg class="ml-1.5 w-4 h-4 group-hover/link:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"></path></svg>
                </a>
            </div>

            <!-- Card 2 -->
            <div class="group relative bg-white rounded-xl border border-line p-7 transition-all duration-300 hover:shadow-authority hover:-translate-y-1 hover:border-action/30">
                <div class="absolute top-0 left-0 w-1 h-full bg-gradient-to-b from-action to-primary rounded-l-xl opacity-0 group-hover:opacity-100 transition-opacity"></div>
                <div class="w-12 h-12 rounded-lg bg-primary/10 flex items-center justify-center mb-5 group-hover:scale-110 transition-transform">
                    <svg class="w-6 h-6 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                </div>
                <h3 class="font-manrope font-bold text-xl text-ink mb-3">Programme & Service Intelligence (PIR / SIR)</h3>
                <p class="text-slate text-sm leading-relaxed mb-5">Structured executive insight into programme health, delivery risk and operational readiness — enabling clear decisions and targeted intervention.</p>
                <a href="/services#reviews" class="inline-flex items-center text-sm font-medium text-action hover:text-primary transition-colors group/link">
                    Explore PIR / SIR
                    <svg class="ml-1.5 w-4 h-4 group-hover/link:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"></path></svg>
                </a>
            </div>

            <!-- Card 3 -->
            <div class="group relative bg-white rounded-xl border border-line p-7 transition-all duration-300 hover:shadow-authority hover:-translate-y-1 hover:border-action/30">
                <div class="absolute top-0 left-0 w-1 h-full bg-gradient-to-b from-action to-primary rounded-l-xl opacity-0 group-hover:opacity-100 transition-opacity"></div>
                <div class="w-12 h-12 rounded-lg bg-primary/10 flex items-center justify-center mb-5 group-hover:scale-110 transition-transform">
                    <svg class="w-6 h-6 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>
                </div>
                <h3 class="font-manrope font-bold text-xl text-ink mb-3">Recovery & Remediation</h3>
                <p class="text-slate text-sm leading-relaxed mb-5">Where issues exist, RAB can support stabilisation, recovery planning and remediation execution.</p>
                <a href="/contact" class="inline-flex items-center text-sm font-medium text-action hover:text-primary transition-colors group/link">
                    Discuss recovery
                    <svg class="ml-1.5 w-4 h-4 group-hover/link:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"></path></svg>
                </a>
            </div>

            <!-- Card 4 -->
            <div class="group relative bg-white rounded-xl border border-line p-7 transition-all duration-300 hover:shadow-authority hover:-translate-y-1 hover:border-action/30">
                <div class="absolute top-0 left-0 w-1 h-full bg-gradient-to-b from-action to-primary rounded-l-xl opacity-0 group-hover:opacity-100 transition-opacity"></div>
                <div class="w-12 h-12 rounded-lg bg-primary/10 flex items-center justify-center mb-5 group-hover:scale-110 transition-transform">
                    <svg class="w-6 h-6 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                </div>
                <h3 class="font-manrope font-bold text-xl text-ink mb-3">Team Augmentation</h3>
                <p class="text-slate text-sm leading-relaxed mb-5">Access to trusted senior capability when delivery requires reinforcement or specialist support.</p>
                <a href="/services#augmentation" class="inline-flex items-center text-sm font-medium text-action hover:text-primary transition-colors group/link">
                    Talk to us
                    <svg class="ml-1.5 w-4 h-4 group-hover/link:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"></path></svg>
                </a>
            </div>

            <!-- Card 5 -->
            <div class="group relative bg-white rounded-xl border border-line p-7 transition-all duration-300 hover:shadow-authority hover:-translate-y-1 hover:border-action/30">
                <div class="absolute top-0 left-0 w-1 h-full bg-gradient-to-b from-action to-primary rounded-l-xl opacity-0 group-hover:opacity-100 transition-opacity"></div>
                <div class="w-12 h-12 rounded-lg bg-primary/10 flex items-center justify-center mb-5 group-hover:scale-110 transition-transform">
                    <svg class="w-6 h-6 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path></svg>
                </div>
                <h3 class="font-manrope font-bold text-xl text-ink mb-3">Business Consulting</h3>
                <p class="text-slate text-sm leading-relaxed mb-5">Structured support to improve operational clarity, delivery confidence and decision quality.</p>
                <a href="/booking" class="inline-flex items-center text-sm font-medium text-action hover:text-primary transition-colors group/link">
                    Book a consultation
                    <svg class="ml-1.5 w-4 h-4 group-hover/link:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"></path></svg>
                </a>
            </div>

            <!-- Card 6 -->
            <div class="group relative bg-white rounded-xl border border-line p-7 transition-all duration-300 hover:shadow-authority hover:-translate-y-1 hover:border-action/30">
                <div class="absolute top-0 left-0 w-1 h-full bg-gradient-to-b from-action to-primary rounded-l-xl opacity-0 group-hover:opacity-100 transition-opacity"></div>
                <div class="w-12 h-12 rounded-lg bg-primary/10 flex items-center justify-center mb-5 group-hover:scale-110 transition-transform">
                    <svg class="w-6 h-6 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path></svg>
                </div>
                <h3 class="font-manrope font-bold text-xl text-ink mb-3">AI, Automation & Development</h3>
                <p class="text-slate text-sm leading-relaxed mb-5">Practical digital enablement through AI, automation and development capability aligned to business outcomes.</p>
                <a href="/services#digital" class="inline-flex items-center text-sm font-medium text-action hover:text-primary transition-colors group/link">
                    Explore solutions
                    <svg class="ml-1.5 w-4 h-4 group-hover/link:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"></path></svg>
                </a>
            </div>
        </div>
    </div>
</section>

<!-- PIR / SIR Section -->
<section class="py-20 md:py-28 bg-white relative overflow-hidden">
    <!-- Decorative background element -->
    <div class="absolute top-0 right-0 w-1/3 h-full bg-gradient-to-l from-cloud to-transparent opacity-50"></div>

    <div class="container relative z-10">
        <div class="text-center max-w-4xl mx-auto mb-16">
            <span class="inline-block px-4 py-1.5 rounded-full bg-primary/10 text-primary text-xs font-bold uppercase tracking-widest mb-4">
                Intelligence Reviews
            </span>
            <h2 class="font-manrope text-4xl md:text-5xl font-bold text-ink mb-6">
                Independent intelligence before you commit.
            </h2>
            <p class="text-slate text-lg leading-relaxed mb-4">
                When leadership needs a clear, factual view of delivery reality, RAB Consulting provides structured Programme and Service Intelligence Reviews. These go beyond traditional assurance by exposing real delivery risk and failure points, governance effectiveness and decision gaps, and operational readiness and service maturity.
            </p>
            <p class="text-primary font-bold text-lg">These diagnostics are the starting point, not the solution.</p>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 max-w-5xl mx-auto">
            <!-- PIR Card -->
            <div class="group relative bg-white rounded-2xl border-2 border-primary/20 p-8 transition-all duration-300 hover:shadow-2xl hover:border-primary/40 hover:-translate-y-2 overflow-hidden">
                <div class="absolute top-0 left-0 w-full h-1 bg-gradient-to-r from-primary to-action"></div>
                <div class="absolute -right-10 -top-10 w-40 h-40 bg-primary/5 rounded-full group-hover:scale-150 transition-transform duration-500"></div>

                <div class="relative">
                    <span class="inline-flex items-center gap-2 px-4 py-1.5 rounded-full bg-primary/10 text-primary text-xs font-bold uppercase tracking-wider mb-6">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path></svg>
                        Programme Focus
                    </span>

                    <h3 class="font-manrope text-5xl font-bold text-primary mb-2">PIR</h3>
                    <h4 class="text-xl font-semibold text-ink mb-4">Programme Intelligence Review</h4>
                    <p class="text-slate leading-relaxed mb-6">Independent review of programme, transformation, ERP/change delivery. Gain an objective view of health, risks, and required actions.</p>

                    <ul class="space-y-2 mb-8">
                        <li class="flex items-start gap-3 text-sm text-slate">
                            <svg class="w-5 h-5 text-primary mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                            <span>Delivery risk and failure points</span>
                        </li>
                        <li class="flex items-start gap-3 text-sm text-slate">
                            <svg class="w-5 h-5 text-primary mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                            <span>Governance effectiveness assessment</span>
                        </li>
                        <li class="flex items-start gap-3 text-sm text-slate">
                            <svg class="w-5 h-5 text-primary mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                            <span>Programme viability and trajectory</span>
                        </li>
                    </ul>

                    <a href="/rapid-consulting?type=phi" class="inline-flex items-center justify-center w-full px-6 py-4 bg-primary text-white font-medium rounded-xl hover:bg-action transition-colors group/btn">
                        Start PIR Diagnostic
                        <svg class="ml-2 w-5 h-5 group-hover/btn:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"></path></svg>
                    </a>
                </div>
            </div>

            <!-- SIR Card -->
            <div class="group relative bg-white rounded-2xl border-2 border-teal-500/20 p-8 transition-all duration-300 hover:shadow-2xl hover:border-teal-500/40 hover:-translate-y-2 overflow-hidden">
                <div class="absolute top-0 left-0 w-full h-1 bg-gradient-to-r from-teal-600 to-emerald-500"></div>
                <div class="absolute -right-10 -top-10 w-40 h-40 bg-teal-500/5 rounded-full group-hover:scale-150 transition-transform duration-500"></div>

                <div class="relative">
                    <span class="inline-flex items-center gap-2 px-4 py-1.5 rounded-full bg-teal-100 text-teal-700 text-xs font-bold uppercase tracking-wider mb-6">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14M5 12l2 2m-2-2l2-2m10 4a9 9 0 11-18 0 9 9 0 0118 0"></path></svg>
                        Service Focus
                    </span>

                    <h3 class="font-manrope text-5xl font-bold text-teal-600 mb-2">SIR</h3>
                    <h4 class="text-xl font-semibold text-ink mb-4">Service Intelligence Review</h4>
                    <p class="text-slate leading-relaxed mb-6">Independent review of IT service, ITSM and operational stability. Understand the true state of your service environment.</p>

                    <ul class="space-y-2 mb-8">
                        <li class="flex items-start gap-3 text-sm text-slate">
                            <svg class="w-5 h-5 text-teal-600 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                            <span>Service maturity assessment</span>
                        </li>
                        <li class="flex items-start gap-3 text-sm text-slate">
                            <svg class="w-5 h-5 text-teal-600 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                            <span>Operational stability review</span>
                        </li>
                        <li class="flex items-start gap-3 text-sm text-slate">
                            <svg class="w-5 h-5 text-teal-600 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                            <span>ITSM process effectiveness</span>
                        </li>
                    </ul>

                    <a href="/rapid-consulting?type=itsm" class="inline-flex items-center justify-center w-full px-6 py-4 bg-teal-600 text-white font-medium rounded-xl hover:bg-teal-700 transition-colors group/btn">
                        Start SIR Diagnostic
                        <svg class="ml-2 w-5 h-5 group-hover/btn:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"></path></svg>
                    </a>
                </div>
            </div>
        </div>

        <!-- Process Line -->
        <div class="mt-16 flex items-center justify-center gap-4 flex-wrap">
            <div class="flex items-center gap-3 px-5 py-3 bg-cloud rounded-full">
                <div class="w-8 h-8 rounded-full bg-primary/20 flex items-center justify-center">
                    <span class="text-primary font-bold text-sm">1</span>
                </div>
                <span class="text-slate font-medium">Diagnostic</span>
            </div>
            <svg class="w-6 h-6 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"></path></svg>
            <div class="flex items-center gap-3 px-5 py-3 bg-cloud rounded-full">
                <div class="w-8 h-8 rounded-full bg-primary/20 flex items-center justify-center">
                    <span class="text-primary font-bold text-sm">2</span>
                </div>
                <span class="text-slate font-medium">Consultant-led Review</span>
            </div>
            <svg class="w-6 h-6 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"></path></svg>
            <div class="flex items-center gap-3 px-5 py-3 bg-cloud rounded-full">
                <div class="w-8 h-8 rounded-full bg-primary/20 flex items-center justify-center">
                    <span class="text-primary font-bold text-sm">3</span>
                </div>
                <span class="text-slate font-medium">Findings & Next Actions</span>
            </div>
        </div>
    </div>
</section>

<!-- How the Journey Works -->
<section class="py-20 md:py-28 bg-gradient-to-b from-white to-cloud">
    <div class="container">
        <div class="text-center max-w-3xl mx-auto mb-16">
            <span class="inline-block px-4 py-1.5 rounded-full bg-primary/10 text-primary text-xs font-bold uppercase tracking-widest mb-4">
                Your Path to Clarity
            </span>
            <h2 class="font-manrope text-4xl md:text-5xl font-bold text-ink mb-6">
                How the Journey Works
            </h2>
        </div>

        <!-- Journey Timeline -->
        <div class="relative max-w-4xl mx-auto">
            <!-- Connecting Line (desktop) -->
            <div class="hidden md:block absolute top-8 left-0 right-0 h-0.5 bg-gradient-to-r from-primary/20 via-action/30 to-primary/20"></div>

            <div class="grid grid-cols-1 md:grid-cols-4 gap-8">
                <!-- Step 1 -->
                <div class="relative text-center group">
                    <div class="relative z-10 w-16 h-16 mx-auto mb-6 rounded-full bg-white border-4 border-primary/20 group-hover:border-primary transition-colors flex items-center justify-center shadow-md">
                        <span class="text-2xl font-bold text-primary">1</span>
                    </div>
                    <h3 class="font-manrope font-bold text-lg text-ink mb-3">Complete the 15-minute Diagnostic</h3>
                    <p class="text-slate text-sm leading-relaxed">Quickly identify key areas of concern and potential risk through our structured online assessment.</p>
                </div>

                <!-- Step 2 -->
                <div class="relative text-center group">
                    <div class="relative z-10 w-16 h-16 mx-auto mb-6 rounded-full bg-white border-4 border-primary/20 group-hover:border-primary transition-colors flex items-center justify-center shadow-md">
                        <span class="text-2xl font-bold text-primary">2</span>
                    </div>
                    <h3 class="font-manrope font-bold text-lg text-ink mb-3">Receive Initial Insight</h3>
                    <p class="text-slate text-sm leading-relaxed">Get immediate feedback based on your responses to understand the high-level state of your environment.</p>
                </div>

                <!-- Step 3 -->
                <div class="relative text-center group">
                    <div class="relative z-10 w-16 h-16 mx-auto mb-6 rounded-full bg-white border-4 border-primary/20 group-hover:border-primary transition-colors flex items-center justify-center shadow-md">
                        <span class="text-2xl font-bold text-primary">3</span>
                    </div>
                    <h3 class="font-manrope font-bold text-lg text-ink mb-3">Review & Decide</h3>
                    <p class="text-slate text-sm leading-relaxed">Discuss the findings with a senior consultant and decide whether to proceed with a full review.</p>
                </div>

                <!-- Step 4 -->
                <div class="relative text-center group">
                    <div class="relative z-10 w-16 h-16 mx-auto mb-6 rounded-full bg-white border-4 border-primary/20 group-hover:border-primary transition-colors flex items-center justify-center shadow-md">
                        <span class="text-2xl font-bold text-primary">4</span>
                    </div>
                    <h3 class="font-manrope font-bold text-lg text-ink mb-3">Consultant-Led Support</h3>
                    <p class="text-slate text-sm leading-relaxed">Move into recovery, remediation, or wider consulting support if required to resolve complex challenges.</p>
                </div>
            </div>
        </div>

        <div class="text-center mt-12 flex justify-center gap-4 flex-wrap">
            <a href="/rapid-consulting" class="inline-flex items-center justify-center px-8 py-4 bg-primary text-white font-medium rounded-xl hover:bg-action transition-colors shadow-md hover:shadow-lg">
                Start Diagnostic
                <svg class="ml-2 w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"></path></svg>
            </a>
            <a href="/contact" class="inline-flex items-center justify-center px-8 py-4 bg-white text-ink font-medium rounded-xl border-2 border-line hover:border-primary hover:bg-cloud transition-all">
                Book Review Consultation
            </a>
        </div>
    </div>
</section>

<!-- Why RAB -->
<section class="py-20 md:py-28 bg-white overflow-hidden">
    <div class="container">
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-12 items-center">
            <!-- Visual Side -->
            <div class="relative">
                <div class="relative bg-gradient-to-br from-cloud to-white rounded-2xl border border-line p-12 h-96 lg:h-[500px] overflow-hidden">
                    <!-- Decorative elements -->
                    <div class="absolute top-0 right-0 w-64 h-64 bg-primary/5 rounded-full -translate-y-1/2 translate-x-1/2"></div>
                    <div class="absolute bottom-0 left-0 w-48 h-48 bg-action/5 rounded-full translate-y-1/3 -translate-x-1/3"></div>

                    <!-- Logo -->
                    <img src="/assets/images/logo-rab.png" alt="RAB Consulting" class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 opacity-30 w-48">

                    <!-- Floating card -->
                    <div class="absolute bottom-8 left-8 right-8 bg-white rounded-xl p-6 border border-line shadow-authority">
                        <p class="font-bold text-primary mb-1">Senior-led experience you can trust.</p>
                        <p class="text-xs text-slate">Independent. Objective. Delivery Focused.</p>
                    </div>
                </div>
            </div>

            <!-- Content Side -->
            <div>
                <span class="inline-block px-4 py-1.5 rounded-full bg-primary/10 text-primary text-xs font-bold uppercase tracking-widest mb-4">
                    Why RAB Consulting
                </span>
                <h2 class="font-manrope text-4xl md:text-5xl font-bold text-ink mb-8">
                    Senior experience, practical delivery, and objective clarity
                </h2>

                <div class="space-y-6">
                    <!-- Point 1 -->
                    <div class="flex gap-5 group">
                        <div class="flex-shrink-0 w-12 h-12 rounded-xl bg-primary/10 flex items-center justify-center group-hover:bg-primary/20 transition-colors">
                            <svg class="w-6 h-6 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M20 6 9 17l-5-5"/></svg>
                        </div>
                        <div>
                            <p class="font-bold text-lg text-ink mb-1">Senior Independence</p>
                            <p class="text-slate leading-relaxed">Unbiased, expert-level reviews that don't pull punches and focus on the reality of your project health.</p>
                        </div>
                    </div>

                    <!-- Point 2 -->
                    <div class="flex gap-5 group">
                        <div class="flex-shrink-0 w-12 h-12 rounded-xl bg-primary/10 flex items-center justify-center group-hover:bg-primary/20 transition-colors">
                            <svg class="w-6 h-6 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M20 6 9 17l-5-5"/></svg>
                        </div>
                        <div>
                            <p class="font-bold text-lg text-ink mb-1">Practical Support</p>
                            <p class="text-slate leading-relaxed">Beyond just identifying problems, we provide the leadership and hands-on capability to fix them.</p>
                        </div>
                    </div>

                    <!-- Point 3 -->
                    <div class="flex gap-5 group">
                        <div class="flex-shrink-0 w-12 h-12 rounded-xl bg-primary/10 flex items-center justify-center group-hover:bg-primary/20 transition-colors">
                            <svg class="w-6 h-6 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M20 6 9 17l-5-5"/></svg>
                        </div>
                        <div>
                            <p class="font-bold text-lg text-ink mb-1">Value Beyond Theory</p>
                            <p class="text-slate leading-relaxed">Our advice is rooted in real-world delivery experience across global, complex organisations.</p>
                        </div>
                    </div>
                </div>

                <div class="mt-10">
                    <a href="/booking" class="inline-flex items-center justify-center px-8 py-4 bg-primary text-white font-medium rounded-xl hover:bg-action transition-colors shadow-md hover:shadow-lg group">
                        Book a Consultation
                        <svg class="ml-2 w-5 h-5 group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"></path></svg>
                    </a>
                </div>
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
            If delivery is under pressure, act before the cost increases.
        </h2>
        <p class="text-white/90 text-lg md:text-xl mb-10 max-w-2xl mx-auto leading-relaxed">
            Speak directly with a senior Programme Director or begin with a structured diagnostic to get a clear view of your risk, priorities and next move.
        </p>
        <div class="flex justify-center gap-4 flex-wrap">
            <a href="/booking" class="inline-flex items-center justify-center px-8 py-4 bg-white text-primary font-bold rounded-xl hover:bg-cloud transition-colors shadow-lg">
                Book Consultation
                <svg class="ml-2 w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"></path></svg>
            </a>
            <a href="/rapid-consulting" class="inline-flex items-center justify-center px-8 py-4 bg-transparent text-white font-bold rounded-xl border-2 border-white/50 hover:bg-white/10 transition-all">
                Start Diagnostic
            </a>
        </div>
    </div>
</section>
@endsection