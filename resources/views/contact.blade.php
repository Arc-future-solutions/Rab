@extends('layouts.public')

@section('title', 'Contact Us — Book a Consultation | RAB Consulting')

@push('head')
<style>
    /* Hero Animations */
    @keyframes fadeUp {
        from { opacity: 0; transform: translateY(30px); }
        to { opacity: 1; transform: translateY(0); }
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

    .floating-particle {
        position: absolute;
        background: radial-gradient(circle, rgba(59, 130, 246, 0.2) 0%, transparent 70%);
        border-radius: 50%;
        animation: float 10s ease-in-out infinite;
        pointer-events: none;
    }

    @keyframes float {
        0%, 100% { transform: translateY(0px); }
        50% { transform: translateY(-20px); }
    }
</style>
@endpush

@section('content')
<!-- Hero Section -->
<section class="relative py-24 md:py-32 flex items-center overflow-hidden" style="background-color: #0F172A;">
    <div class="absolute inset-0" style="background: rgba(15, 23, 42, 0.95);"></div>
    <div class="absolute inset-0 animated-grid"></div>
    <div class="floating-particle" style="width: 400px; height: 400px; top: -10%; right: -5%; animation-delay: 0s;"></div>
    <div class="floating-particle" style="width: 200px; height: 200px; bottom: 10%; left: -5%; animation-delay: 2s;"></div>

    <div class="container relative z-10">
        <div class="max-w-3xl">
            <span class="hero-badge inline-flex items-center gap-2 px-5 py-2 rounded-full mb-6" style="background: rgba(37, 99, 235, 0.15); color: #60a5fa; border: 1px solid rgba(37, 99, 235, 0.2);">
                <span class="inline-block w-1.5 h-1.5 rounded-full" style="background-color: #60a5fa;"></span>
                Get In Touch
            </span>

            <h1 class="hero-title font-serif text-white text-4xl sm:text-5xl md:text-6xl font-bold leading-tight mb-6">
                Book a <span class="hero-word-highlight">consulting discussion</span>
            </h1>

            <p class="hero-subtitle text-lg md:text-xl leading-relaxed mb-10 max-w-2xl" style="color: #e2e8f0 !important;">
                Whether you are looking for an independent PIR/SIR review, programme leadership support, or wider consulting advice, we are ready to discuss your requirements.
            </p>
        </div>
    </div>
</section>

<!-- Contact Section -->
<section class="py-24 md:py-32 bg-white">
    <div class="container">
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-16">

            <!-- Left Column: Contact Info -->
            <div class="lg:col-span-4">
                <div class="sticky top-24">
                    <h3 class="font-manrope text-3xl font-bold text-ink mb-6">Get in touch</h3>
                    <p class="text-slate text-lg mb-10 leading-relaxed">
                        Speak directly with our senior consultants about your programme or service environment.
                    </p>

                    <div class="space-y-8 mb-12">
                        <div class="group">
                            <h4 class="text-xs font-bold uppercase tracking-widest text-slate-400 mb-2">General Inquiries</h4>
                            <p class="text-xl font-bold text-primary transition-colors group-hover:text-action">contact@rabconsulting.co.uk</p>
                        </div>
                        <div class="group">
                            <h4 class="text-xs font-bold uppercase tracking-widest text-slate-400 mb-2">UK Office</h4>
                            <p class="text-slate leading-relaxed">
                                United Kingdom<br>
                                Senior-led delivery support nationwide.
                            </p>
                        </div>
                    </div>

                    <div class="p-8 rounded-2xl bg-highlight border border-primary/10 relative overflow-hidden group transition-all hover:shadow-md">
                        <div class="absolute top-0 right-0 w-24 h-24 bg-primary/5 rounded-full -mr-12 -mt-12 transition-transform group-hover:scale-150"></div>
                        <h4 class="font-bold text-primary mb-3 relative z-10">Need a quick assessment?</h4>
                        <p class="text-slate text-sm leading-relaxed mb-6 relative z-10">
                            Gain initial insight through our 15-minute diagnostic before booking a full consultation.
                        </p>
                        <a href="/rapid-consulting" class="inline-flex items-center justify-center px-6 py-3 bg-white text-primary font-bold rounded-xl border border-primary/20 hover:bg-cloud transition-all w-full text-center shadow-sm">
                            Start Diagnostic
                            <svg class="ml-2 w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"></path></svg>
                        </a>
                    </div>
                </div>
            </div>

            <!-- Right Column: Form -->
            <div class="lg:col-span-8">
                <div class="bg-white rounded-3xl border border-line p-8 md:p-12 shadow-authority relative overflow-hidden">
                    <div class="absolute top-0 left-0 w-full h-1.5 bg-gradient-to-r from-primary to-action"></div>

                    <form action="{{ route('contact.submit') }}" method="POST" class="space-y-8">
                        @csrf

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div class="space-y-2">
                                <label for="name" class="text-sm font-semibold text-ink">Full Name</label>
                                <input type="text" id="name" name="name" placeholder="E.g. John Smith" required
                                    class="w-full px-4 py-3 rounded-xl border border-line bg-cloud/30 focus:ring-2 focus:ring-primary/20 focus:border-primary outline-none transition-all placeholder:text-slate-400">
                            </div>
                            <div class="space-y-2">
                                <label for="email" class="text-sm font-semibold text-ink">Work Email</label>
                                <input type="email" id="email" name="email" placeholder="john@company.com" required
                                    class="w-full px-4 py-3 rounded-xl border border-line bg-cloud/30 focus:ring-2 focus:ring-primary/20 focus:border-primary outline-none transition-all placeholder:text-slate-400">
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div class="space-y-2">
                                <label for="company" class="text-sm font-semibold text-ink">Company</label>
                                <input type="text" id="company" name="company" placeholder="Company Name" required
                                    class="w-full px-4 py-3 rounded-xl border border-line bg-cloud/30 focus:ring-2 focus:ring-primary/20 focus:border-primary outline-none transition-all placeholder:text-slate-400">
                            </div>
                            <div class="space-y-2">
                                <label for="role" class="text-sm font-semibold text-ink">Job Role</label>
                                <input type="text" id="role" name="role" placeholder="E.g. Programme Director" required
                                    class="w-full px-4 py-3 rounded-xl border border-line bg-cloud/30 focus:ring-2 focus:ring-primary/20 focus:border-primary outline-none transition-all placeholder:text-slate-400">
                            </div>
                        </div>

                        <div class="space-y-2">
                            <label for="phone" class="text-sm font-semibold text-ink">Phone Number (Optional)</label>
                            <input type="tel" id="phone" name="phone" placeholder="+44 000 000 000"
                                class="w-full px-4 py-3 rounded-xl border border-line bg-cloud/30 focus:ring-2 focus:ring-primary/20 focus:border-primary outline-none transition-all placeholder:text-slate-400">
                        </div>

                        <div class="space-y-2">
                            <label for="interest" class="text-sm font-semibold text-ink">Area of Interest</label>
                            <select id="interest" name="interest"
                                class="w-full px-4 py-3 rounded-xl border border-line bg-cloud/30 focus:ring-2 focus:ring-primary/20 focus:border-primary outline-none transition-all appearance-none">
                                <option value="pir">Programme Intelligence Review (PIR)</option>
                                <option value="sir">Service Intelligence Review (SIR)</option>
                                <option value="leadership">Programme/Project Leadership</option>
                                <option value="recovery">Recovery & Remediation</option>
                                <option value="augmentation">Team Augmentation</option>
                                <option value="other">Other Consulting Support</option>
                            </select>
                        </div>

                        <div class="space-y-2">
                            <label for="message" class="text-sm font-semibold text-ink">How can we help?</label>
                            <textarea id="message" name="message" rows="5" placeholder="Tell us about your requirements..."
                                class="w-full px-4 py-3 rounded-xl border border-line bg-cloud/30 focus:ring-2 focus:ring-primary/20 focus:border-primary outline-none transition-all placeholder:text-slate-400 resize-none"></textarea>
                        </div>

                        <div class="pt-4">
                            <button type="submit" class="w-full py-4 bg-primary text-white font-bold rounded-xl hover:bg-action transition-all shadow-lg hover:shadow-xl group flex items-center justify-center">
                                Request Consultation
                                <svg class="ml-2 w-5 h-5 group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"></path>
                                </svg>
                            </button>
                        </div>

                        <p class="text-center text-xs text-slate-400">
                            By submitting this form, you agree to our <a href="/privacy-policy" class="underline hover:text-primary transition-colors">Privacy Policy</a> and <a href="/terms" class="underline hover:text-primary transition-colors">Terms of Service</a>.
                        </p>
                    </form>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Final CTA Band -->
<section class="py-20 md:py-28 bg-gradient-to-r from-primary to-action relative overflow-hidden">
    <div class="absolute inset-0 opacity-10" style="background-image: radial-gradient(circle at 2px 2px, white 1px, transparent 0); background-size: 40px 40px;"></div>
    <div class="container relative z-10 text-center">
        <h2 class="text-3xl md:text-5xl font-bold text-white mb-6 max-w-4xl mx-auto">
            Let's define your path to clarity.
        </h2>
        <p class="text-white/90 text-lg md:text-xl mb-10 max-w-2xl mx-auto leading-relaxed">
            Our senior consultants provide the objective oversight and execution discipline needed to turn uncertainty into measurable results.
        </p>
        <div class="flex justify-center gap-4 flex-wrap">
            <a href="/booking" class="inline-flex items-center justify-center px-8 py-4 bg-white text-primary font-bold rounded-xl hover:bg-cloud transition-colors shadow-lg">
                Book Consultation
                <svg class="ml-2 w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
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
