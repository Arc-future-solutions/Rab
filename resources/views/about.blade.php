@extends('layouts.public')

@section('title', 'About Us — Programme & Service Intelligence (PIR / SIR) | RAB Consulting')

@section('content')
<!-- About Hero -->
<section class="section" style="padding-top: 100px; border-bottom: 1px solid var(--slate-200);">
    <div class="container">
        <div class="hero-section" style="grid-template-columns: 1.2fr 0.8fr;">
            <div>
                <div class="kicker"><span class="kicker-dot"></span> Programme & Service Intelligence (PIR / SIR)</div>
                <h1 class="mb-6">Independent intelligence, senior-led delivery</h1>
                <p class="hero-text">
                    RAB Consulting is an independent firm specialising in programme leadership and Programme & Service Intelligence (PIR / SIR). We provide organisations with the objective clarity and senior capability needed to navigate complex transformation.
                </p>
                <div class="hero-actions">
                    <a href="/booking" class="btn-primary">Book a Consultation</a>
                </div>
            </div>
            <div class="hero-visual" style="aspect-ratio: auto; min-height: 200px; background: var(--bg-highlight);">
                <div style="padding: 40px; text-align: center;">
                    <p style="font-size: 1.5rem; font-weight: 700; color: var(--primary);">Senior. Independent. Objective.</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Values / Approach -->
<section class="section">
    <div class="container">
        <div class="grid-cards" style="grid-template-columns: repeat(3, 1fr);">
            <div class="card-service">
                <h3>Independence</h3>
                <p>We provide a truly objective external view, free from internal bias or vendor influence. Our priority is the health and success of your delivery.</p>
            </div>
            <div class="card-service">
                <h3>Seniority</h3>
                <p>Our engagements are led by senior consultants with extensive experience in global enterprises and complex delivery environments.</p>
            </div>
            <div class="card-service">
                <h3>Practicality</h3>
                <p>We don't just provide theory. Our findings are grounded in practical delivery reality, with actionable next steps for recovery and improvement.</p>
            </div>
        </div>
    </div>
</section>

<!-- Credibility / Track Record -->
<section class="section bg-soft">
    <div class="container">
        <div class="section-header text-center mb-16">
            <h4 class="mb-4 text-center">Our Experience</h4>
            <h2 class="text-center">Credibility built through delivery</h2>
        </div>
        
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 80px; align-items: center;">
            <div>
                <p style="font-size: 1.1rem; color: var(--slate-700);">
                    RAB brings a wealth of experience across complex transformation environments, including work delivered within global enterprises and high-growth organisations.
                </p>
                <p>
                    We understand the challenges of programme management, ITSM maturity, and large-scale ERP implementations. Our approach combines structured diagnostic frameworks with senior-led expert interpretation.
                </p>
            </div>
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 24px;">
                <div class="bg-white" style="padding: 32px; border-radius: var(--radius); border: 1px solid var(--slate-200); text-align: center;">
                    <p style="font-weight: 700; color: var(--primary); margin-bottom: 0;">Global Enterprise Experience</p>
                </div>
                <div class="bg-white" style="padding: 32px; border-radius: var(--radius); border: 1px solid var(--slate-200); text-align: center;">
                    <p style="font-weight: 700; color: var(--primary); margin-bottom: 0;">Complex Transformation</p>
                </div>
                <div class="bg-white" style="padding: 32px; border-radius: var(--radius); border: 1px solid var(--slate-200); text-align: center;">
                    <p style="font-weight: 700; color: var(--primary); margin-bottom: 0;">Programme & Service Intelligence (PIR / SIR)</p>
                </div>
                <div class="bg-white" style="padding: 32px; border-radius: var(--radius); border: 1px solid var(--slate-200); text-align: center;">
                    <p style="font-weight: 700; color: var(--primary); margin-bottom: 0;">Recovery & Remediation</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Founder Section -->
<section class="section">
    <div class="container" style="max-width: 900px;">
        <div class="bg-highlight" style="padding: 60px; border-radius: var(--radius-lg);">
            <h2 class="mb-6">Our Philosophy</h2>
            <p style="font-size: 1.25rem; color: var(--primary); font-weight: 600; line-height: 1.5; margin-bottom: 24px;">
                "Controlled execution is the only way to deliver value. Most transformation challenges are not caused by technology alone, but by gaps in execution discipline."
            </p>
            <p>
                RAB exists to close those gaps — ensuring that technology and delivery investments result in measurable business outcomes. We provide the senior leadership and objective oversight needed to turn uncertainty into clarity.
            </p>
            <div class="mt-8">
                <a href="/booking" class="btn-primary">Book a Consulting Discussion</a>
            </div>
        </div>
    </div>
</section>
@endsection
