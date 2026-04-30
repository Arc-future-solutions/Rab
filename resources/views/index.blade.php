@extends('layouts.public')

@section('title', 'RAB Consulting — Programme & Service Intelligence (PIR / SIR), Programme Leadership & Recovery')

@section('content')
<!-- Hero Section -->
<section class="section">
    <div class="container">
        <div class="hero-section">
            <div class="hero-content">
                <span class="hero-eyebrow">Senior-led transformation, recovery and assurance</span>
                <h1 class="hero-title">We lead, assess, and deliver complex transformation programmes — ensuring control at every stage.</h1>
                <p class="hero-text">
                    Programme Director–level leadership across ERP, digital and IT service transformations, including Programme & Service Intelligence (PIR/SIR) and programme recovery where required.
                </p>
                <div class="hero-actions">
                    <a href="/booking" class="btn-primary">Speak to a Programme Director</a>
                    <a href="/rapid-consulting" class="btn-secondary">Start 15-minute Diagnostic</a>
                </div>
                <span class="microcopy">Experience includes global and enterprise delivery environments such as Unilever, IWG and complex UK transformation programmes.</span>
            </div>
            <div class="hero-visual">
                <div class="authority-block" style="background: white; padding: 32px; border-radius: 12px; border: 1px solid var(--color-line); box-shadow: 0 4px 6px -1px rgba(0,0,0,0.08); text-align: left; width: 100%;">
                    <h3 style="color: var(--color-primary); margin-bottom: 20px; font-size: 1.25rem; font-weight: 700;">When to call RAB</h3>
                    <ul style="list-style: none; padding: 0; margin-bottom: 24px; display: flex; flex-direction: column; gap: 12px;">
                        <li style="display: flex; align-items: center; gap: 10px; font-size: 0.95rem; color: var(--color-ink);">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="var(--color-action)" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg>
                            <span>Programme under pressure</span>
                        </li>
                        <li style="display: flex; align-items: center; gap: 10px; font-size: 0.95rem; color: var(--color-ink);">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="var(--color-action)" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg>
                            <span>Governance weak or unclear</span>
                        </li>
                        <li style="display: flex; align-items: center; gap: 10px; font-size: 0.95rem; color: var(--color-ink);">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="var(--color-action)" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg>
                            <span>Vendor or SI misalignment</span>
                        </li>
                        <li style="display: flex; align-items: center; gap: 10px; font-size: 0.95rem; color: var(--color-ink);">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="var(--color-action)" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg>
                            <span>Go-live or BAU readiness risk</span>
                        </li>
                        <li style="display: flex; align-items: center; gap: 10px; font-size: 0.95rem; color: var(--color-ink);">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="var(--color-action)" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg>
                            <span>Need a fast independent view</span>
                        </li>
                    </ul>
                    <div style="background: var(--color-cloud); padding: 20px; border-radius: 10px; font-size: 0.85rem; border: 1px solid var(--color-line);">
                        <p style="font-weight: 700; margin-bottom: 6px; color: var(--color-ink); font-size: 0.9rem;">20+ years | UK · EU · MENA</p>
                        <p style="margin-bottom: 0; color: var(--color-slate); line-height: 1.4;">Direct senior ownership | Contract / interim / advisory</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Authority Strip -->
<div class="authority-strip">
    <div class="container">
        <div class="authority-inner">
            <span class="trust-indicator">Global Delivery Experience</span>
            <span class="trust-indicator">Complex Transformation Support</span>
            <span class="trust-indicator">Programme & Service Intelligence</span>
            <span class="trust-indicator">Senior-Led Advisory</span>
        </div>
    </div>
</div>

<!-- What RAB Does -->
<section class="section bg-soft">
    <div class="container">
        <div class="section-header text-center mb-12">
            <h4 class="mb-4 text-center">Our Capabilities</h4>
            <h2 class="text-center">Specialist support for complex environments</h2>
        </div>
        
        <div class="grid-cards">
            <!-- Card 1 -->
            <div class="card-service">
                <h3>Programme & Project Leadership</h3>
                <p>Senior-led direction, control and delivery support across transformation, ERP and complex change initiatives.</p>
                <a href="/services#leadership" class="card-link">Learn more →</a>
            </div>
            <!-- Card 2 (was Independent Reviews) -->
            <div class="card-service">
                <h3>Programme & Service Intelligence (PIR / SIR)</h3>
                <p>Structured executive insight into programme health, delivery risk and operational readiness — enabling clear decisions and targeted intervention.</p>
                <a href="/services#reviews" class="card-link">Explore PIR / SIR →</a>
            </div>
            <!-- Card 3 -->
            <div class="card-service">
                <h3>Recovery & Remediation</h3>
                <p>Where issues exist, RAB can support stabilisation, recovery planning and remediation execution.</p>
                <a href="/contact" class="card-link">Discuss recovery →</a>
            </div>
            <!-- Card 4 -->
            <div class="card-service">
                <h3>Team Augmentation</h3>
                <p>Access to trusted senior capability when delivery requires reinforcement or specialist support.</p>
                <a href="/services#augmentation" class="card-link">Talk to us →</a>
            </div>
            <!-- Card 5 -->
            <div class="card-service">
                <h3>Business Consulting</h3>
                <p>Structured support to improve operational clarity, delivery confidence and decision quality.</p>
                <a href="/booking" class="card-link">Book a consultation →</a>
            </div>
            <!-- Card 6 -->
            <div class="card-service">
                <h3>AI, Automation & Development</h3>
                <p>Practical digital enablement through AI, automation and development capability aligned to business outcomes.</p>
                <a href="/services#digital" class="card-link">Explore solutions →</a>
            </div>
        </div>
    </div>
</section>

<!-- PIR / SIR Section (was Independent Reviews Section) -->
<section class="section">
    <div class="container">
        <div class="section-header text-center mb-12">
            <h4 class="mb-4 text-center">Intelligence Reviews</h4>
            <h2 class="text-center">Independent intelligence before you commit.</h2>
            <p class="text-center mt-6" style="max-width: 80%; margin-left: auto; margin-right: auto; color: var(--color-ink); line-height: 1.6;">
                When leadership needs a clear, factual view of delivery reality, RAB Consulting provides structured Programme and Service Intelligence Reviews. These go beyond traditional assurance by exposing real delivery risk and failure points, governance effectiveness and decision gaps, programme viability and trajectory, and operational readiness and service maturity. Each review provides executive-level clarity, enabling leadership to make informed decisions, regain control and define the right next move. Where required, this moves directly into programme stabilisation and delivery ownership.
            </p>
            <p class="text-center mt-4 font-bold" style="color: var(--color-primary);">These diagnostics are the starting point, not the solution.</p>
        </div>

        <div class="review-product-grid">
            <!-- PIR Card -->
            <div class="card-premium-review card-pir">
                <span class="badge mb-4" style="background: var(--bg-highlight); color: var(--primary); padding: 4px 12px; border-radius: 999px; font-size: 0.75rem; font-weight: 700;">PROGRAMME FOCUS</span>
                <h3>PIR</h3>
                <h4 class="text-slate-900 mb-4" style="text-transform: none; letter-spacing: normal; color: var(--slate-900);">Programme Intelligence Review</h4>
                <p>Independent review of programme, transformation, ERP/change delivery. Gain an objective view of health, risks, and required actions.</p>
                <div class="mt-6 flex gap-4">
                    <a href="/rapid-consulting?type=phi" class="btn-primary">Start PIR</a>
                </div>
            </div>
            
            <!-- SIR Card -->
            <div class="card-premium-review card-sir">
                <span class="badge mb-4" style="background: var(--bg-highlight); color: var(--secondary); padding: 4px 12px; border-radius: 999px; font-size: 0.75rem; font-weight: 700;">SERVICE FOCUS</span>
                <h3>SIR</h3>
                <h4 class="text-slate-900 mb-4" style="text-transform: none; letter-spacing: normal; color: var(--slate-900);">Service Intelligence Review</h4>
                <p>Independent review of IT service, ITSM and operational stability. Understand the true state of your service environment.</p>
                <div class="mt-6 flex gap-4">
                    <a href="/rapid-consulting?type=itsm" class="btn-primary">Start SIR</a>
                </div>
            </div>
        </div>
        
        <div class="review-process-line">
            <span>Diagnostic</span>
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
            <span>Consultant-led review</span>
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
            <span>Findings & next actions</span>
        </div>
    </div>
</section>

<!-- How the Journey Works -->
<section class="section bg-soft">
    <div class="container">
        <div class="section-header text-center mb-16">
            <h4 class="mb-4 text-center">Your Path to Clarity</h4>
            <h2 class="text-center">How the Journey Works</h2>
        </div>
        
        <div class="journey-grid">
            <div class="journey-step">
                <div class="step-number">1</div>
                <h3>Complete the 15-minute diagnostic</h3>
                <p>Quickly identify key areas of concern and potential risk through our structured online assessment.</p>
            </div>
            <div class="journey-step">
                <div class="step-number">2</div>
                <h3>Receive initial insight</h3>
                <p>Get immediate feedback based on your responses to understand the high-level state of your environment.</p>
            </div>
            <div class="journey-step">
                <div class="step-number">3</div>
                <h3>Review & Decide</h3>
                <p>Discuss the findings with a senior consultant and decide whether to proceed with a full review.</p>
            </div>
            <div class="journey-step">
                <div class="step-number">4</div>
                <h3>Consultant-Led Support</h3>
                <p>Move into recovery, remediation, or wider consulting support if required to resolve complex challenges.</p>
            </div>
        </div>
        
        <div class="text-center mt-12 flex justify-center gap-4">
            <a href="/rapid-consulting" class="btn-primary">Start Diagnostic</a>
            <a href="/contact" class="btn-secondary">Book Review Consultation</a>
        </div>
    </div>
</section>

<!-- Why RAB -->
<section class="section">
    <div class="container">
        <div class="grid" style="display: grid; grid-template-columns: 1fr 1.2fr; gap: 80px; align-items: center;">
            <div class="visual-placeholder" style="background: var(--bg-soft); border-radius: var(--radius-lg); height: 500px; border: 1px solid var(--slate-300); position: relative; overflow: hidden;">
                 <img src="/assets/images/logo-rab.png" alt="RAB Consulting" style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); opacity: 0.5; width: 60%;">
                 <div style="position: absolute; bottom: 40px; left: 40px; right: 40px; background: white; padding: 24px; border-radius: var(--radius); border: 1px solid var(--slate-200); box-shadow: var(--shadow);">
                    <p class="mb-0" style="font-weight: 600; color: var(--primary);">Senior-led experience you can trust.</p>
                    <p class="mb-0" style="font-size: 0.85rem; color: var(--slate-400);">Independent. Objective. Delivery Focused.</p>
                 </div>
            </div>
            <div>
                <h4 class="mb-4">Why RAB Consulting</h4>
                <h2 class="mb-8">Senior experience, practical delivery, and objective clarity</h2>
                <div style="display: flex; flex-direction: column; gap: 24px;">
                    <div style="display: flex; gap: 20px;">
                        <div style="color: var(--primary);"><svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M20 6 9 17l-5-5"/></svg></div>
                        <div>
                            <p style="font-weight: 700; color: var(--slate-900); margin-bottom: 4px;">Senior Independence</p>
                            <p style="font-size: 0.95rem;">Unbiased, expert-level reviews that don't pull punches and focus on the reality of your project health.</p>
                        </div>
                    </div>
                    <div style="display: flex; gap: 20px;">
                        <div style="color: var(--primary);"><svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M20 6 9 17l-5-5"/></svg></div>
                        <div>
                            <p style="font-weight: 700; color: var(--slate-900); margin-bottom: 4px;">Practical Support</p>
                            <p style="font-size: 0.95rem;">Beyond just identifying problems, we provide the leadership and hands-on capability to fix them.</p>
                        </div>
                    </div>
                    <div style="display: flex; gap: 20px;">
                        <div style="color: var(--primary);"><svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M20 6 9 17l-5-5"/></svg></div>
                        <div>
                            <p style="font-weight: 700; color: var(--slate-900); margin-bottom: 4px;">Value Beyond Theory</p>
                            <p style="font-size: 0.95rem;">Our advice is rooted in real-world delivery experience across global, complex organisations.</p>
                        </div>
                    </div>
                </div>
                <div class="mt-8">
                    <a href="/booking" class="btn-primary">Book a Consultation</a>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Final CTA Band -->
<section class="section bg-highlight" style="border-top: 1px solid var(--slate-200);">
    <div class="container text-center">
        <h2 class="mb-4">If delivery is under pressure, act before the cost increases.</h2>
        <p class="mb-8" style="font-size: 1.1rem; max-width: 00px; margin-left: auto; margin-right: auto; color: var(--color-ink);">
            Speak directly with a senior Programme Director or begin with a structured diagnostic to get a clear view of your risk, priorities and next move.
        </p>
        <div class="flex justify-center gap-4">
            <a href="/booking" class="btn-primary" style="padding: 16px 40px;">Book Consultation</a>
            <a href="/rapid-consulting" class="btn-secondary" style="padding: 16px 40px;">Start Diagnostic</a>
        </div>
    </div>
</section>
@endsection