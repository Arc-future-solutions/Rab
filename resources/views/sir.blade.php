@extends('layouts.public')

@section('title', 'SIR: Service Intelligence Review — Programme & Service Intelligence (PIR / SIR)')

@section('content')
<!-- SIR Hero -->
<section class="section" style="padding-top: 100px; border-bottom: 1px solid var(--slate-200);">
    <div class="container">
        <div class="hero-section" style="grid-template-columns: 1.1fr 0.9fr;">
            <div>
                <h4 class="mb-4">Service Intelligence Review (SIR)</h4>
                <h1 class="mb-6">Independent insight for operational stability</h1>
                <p class="hero-text">
                    An objective assessment of IT service, ITSM and operational stability. Understand the true state of your service environment to drive better performance and resilience.
                </p>
                <div class="hero-actions">
                    <a href="/rapid-consulting?type=itsm" class="btn-primary">Start SIR Diagnostic</a>
                    <a href="/contact" class="btn-secondary">Book SIR Consultation</a>
                </div>
                <span class="microcopy">Estimated completion time: 15 minutes.</span>
            </div>
            <div class="hero-visual">
                <div style="padding: 40px; background: white; border-radius: 12px; box-shadow: var(--shadow-lg); width: 100%; max-width: 400px;">
                    <div style="display: flex; flex-direction: column; gap: 32px;">
                        <div style="display: flex; justify-content: space-between; align-items: flex-end;">
                            <div>
                                <span style="font-size: 0.7rem; font-weight: 700; color: var(--slate-400); text-transform: uppercase; letter-spacing: 0.1em;">Service Maturity</span>
                                <h5 style="margin: 0; font-size: 1.25rem; text-transform: none; letter-spacing: normal;">Operational Stability Index</h5>
                            </div>
                            <div style="text-align: right;">
                                <span style="display: block; font-size: 1.5rem; font-weight: 800; color: var(--secondary);">62%</span>
                                <span style="font-size: 0.6rem; color: #DC2626; font-weight: 700;">Critical Stability Risk</span>
                            </div>
                        </div>
                        
                        <div style="display: flex; flex-direction: column; gap: 24px;">
                            <div style="display: flex; flex-direction: column; gap: 8px;">
                                <div style="display: flex; justify-content: space-between; font-size: 0.75rem; font-weight: 600;">
                                    <span>Incident Management</span>
                                    <span style="color: #DC2626;">Low</span>
                                </div>
                                <div style="height: 8px; width: 100%; background: var(--slate-100); border-radius: 4px; overflow: hidden;">
                                    <div style="height: 100%; width: 35%; background: #DC2626;"></div>
                                </div>
                            </div>
                            
                            <div style="display: flex; flex-direction: column; gap: 8px;">
                                <div style="display: flex; justify-content: space-between; font-size: 0.75rem; font-weight: 600;">
                                    <span>Service Continuity</span>
                                    <span style="color: #D97706;">Moderate</span>
                                </div>
                                <div style="height: 8px; width: 100%; background: var(--slate-100); border-radius: 4px; overflow: hidden;">
                                    <div style="height: 100%; width: 55%; background: #D97706;"></div>
                                </div>
                            </div>
                            
                            <div style="display: flex; flex-direction: column; gap: 8px;">
                                <div style="display: flex; justify-content: space-between; font-size: 0.75rem; font-weight: 600;">
                                    <span>Release Governance</span>
                                    <span style="color: #059669;">High</span>
                                </div>
                                <div style="height: 8px; width: 100%; background: var(--slate-100); border-radius: 4px; overflow: hidden;">
                                    <div style="height: 100%; width: 80%; background: #059669;"></div>
                                </div>
                            </div>
                        </div>
                        
                        <div style="padding-top: 20px; border-top: 1px solid var(--slate-100); display: flex; align-items: center; gap: 12px;">
                            <div style="width: 32px; height: 32px; background: var(--brand-highlight); border-radius: 50%; display: flex; align-items: center; justify-content: center;">
                                <svg style="width: 16px; height: 16px; color: var(--secondary);" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                            </div>
                            <span style="font-size: 0.7rem; font-weight: 600; color: var(--slate-500);">ITIL Framework Aligned Review</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- What is SIR -->
<section class="section">
    <div class="container">
        <div class="grid" style="display: grid; grid-template-columns: 1fr 1fr; gap: 60px;">
            <div>
                <h2 class="mb-6">What is a Service Intelligence Review?</h2>
                <p>The Service Intelligence Review (SIR) provides an independent evaluation of your IT service environment. We look beyond basic SLAs to assess the actual stability, governance, and maturity of your operational processes.</p>
                <p>SIR is critical for organisations undergoing significant change or those experiencing persistent service instability. We provide the objective evidence needed to justify investment and drive improvement.</p>
            </div>
            <div class="bg-soft" style="padding: 40px; border-radius: var(--radius-lg);">
                <h4 class="mb-4">Who it is for</h4>
                <ul class="footer-links" style="color: var(--slate-700);">
                    <li style="margin-bottom: 12px; display: flex; gap: 10px;"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="var(--secondary)" stroke-width="3"><path d="M20 6 9 17l-5-5"/></svg> IT Directors & Leaders</li>
                    <li style="margin-bottom: 12px; display: flex; gap: 10px;"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="var(--secondary)" stroke-width="3"><path d="M20 6 9 17l-5-5"/></svg> Service Owners</li>
                    <li style="margin-bottom: 12px; display: flex; gap: 10px;"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="var(--secondary)" stroke-width="3"><path d="M20 6 9 17l-5-5"/></svg> Heads of IT Operations</li>
                    <li style="margin-bottom: 12px; display: flex; gap: 10px;"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="var(--secondary)" stroke-width="3"><path d="M20 6 9 17l-5-5"/></svg> ITSM Managers</li>
                </ul>
            </div>
        </div>
    </div>
</section>

<!-- Process Section -->
<section class="section bg-soft">
    <div class="container">
        <div class="section-header text-center mb-16">
            <h4 class="mb-4 text-center">The SIR Engagement</h4>
            <h2 class="text-center">From Diagnostic to Full Review</h2>
        </div>
        
        <div class="grid-cards" style="grid-template-columns: 1fr 1fr;">
            <!-- The Diagnostic -->
            <div class="card-service" style="background: white;">
                <div class="badge mb-4">Step 01</div>
                <h3>The 15-Minute Diagnostic</h3>
                <p>Get an initial snapshot of your service stability. Identify priority areas for investigation and understand how your operations compare to industry benchmarks.</p>
                <a href="/rapid-consulting?type=itsm" class="btn-secondary mt-4">Start SIR Diagnostic</a>
            </div>
            
            <!-- Full Review -->
            <div class="card-service" style="background: white; border-left: 4px solid var(--secondary);">
                <div class="badge mb-4">Step 02</div>
                <h3>The Full SIR Engagement</h3>
                <p>A deep-dive investigation into your operational environment:</p>
                <ul class="footer-links" style="margin-top: 20px; color: var(--slate-600); margin-bottom: 24px;">
                    <li style="margin-bottom: 8px;">• ITSM Process Review (Incident, Change, Problem)</li>
                    <li style="margin-bottom: 8px;">• Governance & Vendor Performance Review</li>
                    <li style="margin-bottom: 8px;">• Service Artefact & Reporting Analysis</li>
                    <li style="margin-bottom: 8px;">• Detailed Remediation Roadmap</li>
                </ul>
                <a href="/contact" class="btn-primary" style="background-color: var(--secondary);">Book SIR Consultation</a>
            </div>
        </div>
    </div>
</section>

<!-- Outcomes -->
<section class="section">
    <div class="container">
        <div class="section-header mb-12">
            <h2>Tangible Service Outcomes</h2>
        </div>
        <div class="grid-cards">
            <div class="card-service">
                <h3>Stability Assessment</h3>
                <p>A quantitative and qualitative view of service stability and operational risk.</p>
            </div>
            <div class="card-service">
                <h3>Maturity Roadmap</h3>
                <p>A prioritised plan to improve ITSM maturity and operational efficiency.</p>
            </div>
            <div class="card-service">
                <h3>Governance Insight</h3>
                <p>Practical recommendations to strengthen service governance and vendor oversight.</p>
            </div>
        </div>
    </div>
</section>
@endsection
