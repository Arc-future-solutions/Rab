@extends('layouts.public')

@section('title', 'Our Services — Programme & Service Intelligence (PIR / SIR) & Delivery Support')

@section('content')
<!-- Page Header -->
<!-- Page Header -->
<section class="section" style="padding-top: 120px; padding-bottom: 80px; background: linear-gradient(to right, #ffffff, var(--bg-soft)); border-bottom: 1px solid var(--slate-200);">
    <div class="container">
        <h4 class="mb-4 text-primary font-bold tracking-widest">Our Services</h4>
        <h1 class="mb-6 text-slate-900">Senior-led expertise for complex delivery</h1>
        <p class="hero-text text-slate-700" style="max-width: 800px; font-size: 1.25rem; line-height: 1.6;">
            RAB Consulting provides a comprehensive range of advisory and delivery support services designed to help organisations navigate the complexities of large-scale programmes and IT service environments.
        </p>
    </div>
</section>

<!-- Leadership & Delivery -->
<section class="section" id="leadership" style="background: white;">
    <div class="container">
        <div class="section-header mb-12">
            <h4 class="mb-2 text-primary font-bold">01. Leadership & Delivery</h4>
            <h2 class="text-slate-900">Senior direction and practical control</h2>
        </div>
        
        <div class="grid-cards">
            <div class="card-service" style="border-width: 2px;">
                <h3 class="text-primary">Programme Management</h3>
                <p class="text-slate-700">Expert direction for multi-stream transformation programmes, ensuring alignment with business objectives and robust risk management.</p>
            </div>
            <div class="card-service" style="border-width: 2px;">
                <h3 class="text-primary">Project Management</h3>
                <p class="text-slate-700">Focused delivery support for critical workstreams, ERP implementations, and high-stakes change initiatives.</p>
            </div>
            <div class="card-service" style="border-width: 2px;">
                <h3 class="text-primary">Programme & Service Intelligence (PIR / SIR)</h3>
                <p class="text-slate-700">Structured executive insight into programme health, delivery risk and operational readiness — enabling clear decisions and targeted intervention.</p>
            </div>
            <div class="card-service" id="recovery" style="border-width: 2px;">
                <h3 class="text-primary">Recovery & Remediation</h3>
                <p class="text-slate-700">Stabilising failing projects, identifying root causes, and executing remediation plans to get delivery back on track.</p>
            </div>
        </div>
    </div>
</section>

<!-- Review Products -->
<section class="section" id="reviews" style="background: var(--slate-200); color: var(--slate-700);">
    <div class="container">
        <div class="section-header mb-16">
            <h4 class="mb-2" style="color: var(--primary); font-weight: 800; filter: brightness(1.5);">02. Review Products</h4>
            <h2 style="color: var(--slate-900);">Programme & Service Intelligence Reviews</h2>
        </div>
        
        <div class="review-product-grid">
            <!-- PIR -->
            <div class="card-premium-review card-pir" style="background: rgba(255,255,255,0.7); border-color: rgba(255,255,255,0.3); backdrop-filter: blur(10px);">
                <h3 style="color: var(--slate-900); font-size: 2rem;">PIR</h3>
                <h4 style="text-transform: none; color: var(--slate-900); letter-spacing: normal; opacity: 0.9;">Programme Intelligence Review</h4>
                <ul class="footer-links" style="margin-top: 24px; color: var(--slate-300);">
                    <li style="margin-bottom: 12px; display: flex; align-items: flex-start; gap: 10px;">
                        <span style="color: var(--primary); filter: brightness(1.5); font-weight: bold;">•</span>
                        <span>Independent review of programme health</span>
                    </li>
                    <li style="margin-bottom: 12px; display: flex; align-items: flex-start; gap: 10px;">
                        <span style="color: var(--primary); filter: brightness(1.5); font-weight: bold;">•</span>
                        <span>ERP and transformation assessment</span>
                    </li>
                    <li style="margin-bottom: 12px; display: flex; align-items: flex-start; gap: 10px;">
                        <span style="color: var(--primary); filter: brightness(1.5); font-weight: bold;">•</span>
                        <span>Objective risk and delivery analysis</span>
                    </li>
                    <li style="margin-bottom: 12px; display: flex; align-items: flex-start; gap: 10px;">
                        <span style="color: var(--primary); filter: brightness(1.5); font-weight: bold;">•</span>
                        <span>Structured priority recommendations</span>
                    </li>
                </ul>
                <div class="mt-8">
                    <a href="/pir" class="btn-primary" style="background: white; color: var(--primary); border: none;">Learn more about PIR</a>
                </div>
            </div>
            
            <!-- SIR -->
            <div class="card-premium-review card-sir" style="background: rgba(255,255,255,0.7); border-color: rgba(255,255,255,0.5); backdrop-filter: blur(10px);">
                <h3 style="color: var(--slate-900); font-size: 2rem;">SIR</h3>
                <h4 style="text-transform: none; color: var(--slate-900); letter-spacing: normal; opacity: 0.9;">Service Intelligence Review</h4>
                <ul class="footer-links" style="margin-top: 24px; color: var(--slate-300);">
                    <li style="margin-bottom: 12px; display: flex; align-items: flex-start; gap: 10px;">
                        <span style="color: #6B7280; filter: brightness(2.0); font-weight: bold;">•</span>
                        <span>IT service and operational stability review</span>
                    </li>
                    <li style="margin-bottom: 12px; display: flex; align-items: flex-start; gap: 10px;">
                        <span style="color: #6B7280; filter: brightness(2.0); font-weight: bold;">•</span>
                        <span>ITSM and service governance assessment</span>
                    </li>
                    <li style="margin-bottom: 12px; display: flex; align-items: flex-start; gap: 10px;">
                        <span style="color: #6B7280; filter: brightness(2.0); font-weight: bold;">•</span>
                        <span>Incident and change process maturity</span>
                    </li>
                    <li style="margin-bottom: 12px; display: flex; align-items: flex-start; gap: 10px;">
                        <span style="color: #6B7280; filter: brightness(2.0); font-weight: bold;">•</span>
                        <span>Roadmap for service improvement</span>
                    </li>
                </ul>
                <div class="mt-8">
                    <a href="/sir" class="btn-primary" style="background: white; color: var(--primary); border: none;">Learn more about SIR</a>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Capability Support -->
<section class="section" id="augmentation" style="background: white;">
    <div class="container">
        <div class="section-header mb-12">
            <h4 class="mb-2 text-primary font-bold">03. Capability Support</h4>
            <h2 class="text-slate-900">Flexible reinforcing for your teams</h2>
        </div>
        
        <div class="grid-cards">
            <div class="card-service" style="border-width: 2px;">
                <h3 class="text-primary">Team Augmentation</h3>
                <p class="text-slate-700">High-calibre senior resource to reinforce your internal teams during peak delivery phases or specialist gaps.</p>
            </div>
            <div id="consulting" class="card-service" style="border-width: 2px;">
                <h3 class="text-primary">Business Consulting</h3>
                <p class="text-slate-700">Practical advisory to improve operational clarity, decision quality, and commercial outcomes across the business.</p>
            </div>
            <div id="digital" class="card-service" style="border-width: 2px;">
                <h3 class="text-primary">AI & Automation</h3>
                <p class="text-slate-700">Pragmatic digital enablement through AI and process automation focused on tangible business efficiency.</p>
            </div>
            <div class="card-service" style="border-width: 2px;">
                <h3 class="text-primary">Development</h3>
                <p class="text-slate-700">Targeted development capability to support custom digital requirements and integration projects.</p>
            </div>
        </div>
    </div>
</section>

<!-- Contact CTA -->
<section class="section" style="background: var(--bg-soft);">
    <div class="container">
        <div class="bg-primary shadow-2xl" style="padding: 80px; border-radius: var(--radius-lg); text-align: center; color: white;">
            <h2 class="mb-4" style="color: white; font-size: 2.5rem;">Discuss how we can support your business</h2>
            <p class="mb-10" style="max-width: 600px; margin-left: auto; margin-right: auto; font-size: 1.25rem; opacity: 0.9; color: white;">
                Whether you need a one-off independent review or long-term leadership support, our senior consultants are ready to help.
            </p>
            <a href="/contact" class="btn-primary" style="background: white; color: var(--primary); padding: 20px 48px; font-size: 1.1rem;">Book a Consulting Discussion</a>
        </div>
    </div>
</section>
@endsection
