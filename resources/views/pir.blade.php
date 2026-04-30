@extends('layouts.public')

@section('title', 'PIR: Programme Intelligence Review — Programme & Service Intelligence (PIR / SIR)')

@section('content')
<!-- PIR Hero -->
<section class="section" style="padding-top: 100px; border-bottom: 1px solid var(--slate-200);">
    <div class="container">
        <div class="hero-section" style="grid-template-columns: 1.1fr 0.9fr;">
            <div>
                <h4 class="mb-4">Programme Intelligence Review (PIR)</h4>
                <h1 class="mb-6">Independent insight for stronger programme control</h1>
                <p class="hero-text">
                    An objective external view of programme health, risks, and required next actions. PIR provides sponsors and directors with the delivery confidence needed for complex transformation.
                </p>
                <div class="hero-actions">
                    <a href="/rapid-consulting?type=phi" class="btn-primary">Start PIR Diagnostic</a>
                    <a href="/contact" class="btn-secondary">Book PIR Consultation</a>
                </div>
                <span class="microcopy">Estimated completion time: 15 minutes.</span>
            </div>
            <div class="hero-visual">
                <div style="padding: 40px; background: white; border-radius: 12px; box-shadow: var(--shadow-lg); width: 100%; max-width: 400px;">
                    <div style="display: flex; flex-direction: column; gap: 32px;">
                        <div style="display: flex; justify-content: space-between; align-items: flex-end;">
                            <div>
                                <span style="font-size: 0.7rem; font-weight: 700; color: var(--slate-400); text-transform: uppercase; letter-spacing: 0.1em;">Delivery Health</span>
                                <h5 style="margin: 0; font-size: 1.25rem; text-transform: none; letter-spacing: normal;">Programme Pillar Analysis</h5>
                            </div>
                            <div style="text-align: right;">
                                <span style="display: block; font-size: 1.5rem; font-weight: 800; color: var(--primary);">78%</span>
                                <span style="font-size: 0.6rem; color: #10B981; font-weight: 700;">+12% Lead Indicator</span>
                            </div>
                        </div>
                        
                        <div style="display: flex; flex-direction: column; gap: 24px;">
                            <div style="display: flex; flex-direction: column; gap: 8px;">
                                <div style="display: flex; justify-content: space-between; font-size: 0.75rem; font-weight: 600;">
                                    <span>Governance & Control</span>
                                    <span style="color: #D97706;">Amber</span>
                                </div>
                                <div style="height: 8px; width: 100%; background: var(--slate-100); border-radius: 4px; overflow: hidden;">
                                    <div style="height: 100%; width: 65%; background: #D97706;"></div>
                                </div>
                            </div>
                            
                            <div style="display: flex; flex-direction: column; gap: 8px;">
                                <div style="display: flex; justify-content: space-between; font-size: 0.75rem; font-weight: 600;">
                                    <span>Stakeholder Alignment</span>
                                    <span style="color: #059669;">Green</span>
                                </div>
                                <div style="height: 8px; width: 100%; background: var(--slate-100); border-radius: 4px; overflow: hidden;">
                                    <div style="height: 100%; width: 85%; background: #059669;"></div>
                                </div>
                            </div>
                            
                            <div style="display: flex; flex-direction: column; gap: 8px;">
                                <div style="display: flex; justify-content: space-between; font-size: 0.75rem; font-weight: 600;">
                                    <span>Risk Remediation</span>
                                    <span style="color: #DC2626;">Red</span>
                                </div>
                                <div style="height: 8px; width: 100%; background: var(--slate-100); border-radius: 4px; overflow: hidden;">
                                    <div style="height: 100%; width: 40%; background: #DC2626;"></div>
                                </div>
                            </div>
                        </div>
                        
                        <div style="padding-top: 20px; border-top: 1px solid var(--slate-100); display: flex; align-items: center; gap: 12px;">
                            <div style="width: 32px; height: 32px; background: var(--bg-highlight); border-radius: 50%; display: flex; align-items: center; justify-content: center;">
                                <svg style="width: 16px; height: 16px; color: var(--primary);" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                            </div>
                            <span style="font-size: 0.7rem; font-weight: 600; color: var(--slate-500);">PIR / SIR Intelligence Verified</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- What is PIR -->
<section class="section">
    <div class="container">
        <div class="grid" style="display: grid; grid-template-columns: 1fr 1fr; gap: 60px;">
            <div>
                <h2 class="mb-6">What is a Programme Intelligence Review?</h2>
                <p>The Programme Intelligence Review (PIR) is a structured, independent assessment of your transformation or ERP programme. It goes beyond simple status reporting to provide a deep, objective analysis of the true state of delivery.</p>
                <p>Designed for high-intensity environments, PIR identifies the "silent risks" and governance gaps that often lead to delays and cost overruns.</p>
            </div>
            <div class="bg-soft" style="padding: 40px; border-radius: var(--radius-lg);">
                <h4 class="mb-4">Who it is for</h4>
                <ul class="footer-links" style="color: var(--slate-700);">
                    <li style="margin-bottom: 12px; display: flex; gap: 10px;"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="var(--primary)" stroke-width="3"><path d="M20 6 9 17l-5-5"/></svg> Programme Directors & Leads</li>
                    <li style="margin-bottom: 12px; display: flex; gap: 10px;"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="var(--primary)" stroke-width="3"><path d="M20 6 9 17l-5-5"/></svg> Transformation Leaders</li>
                    <li style="margin-bottom: 12px; display: flex; gap: 10px;"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="var(--primary)" stroke-width="3"><path d="M20 6 9 17l-5-5"/></svg> Executive Sponsors</li>
                    <li style="margin-bottom: 12px; display: flex; gap: 10px;"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="var(--primary)" stroke-width="3"><path d="M20 6 9 17l-5-5"/></svg> CIO & CTO Stakeholders</li>
                </ul>
            </div>
        </div>
    </div>
</section>

<!-- Process Section -->
<section class="section bg-soft">
    <div class="container">
        <div class="section-header text-center mb-16">
            <h4 class="mb-4 text-center">The PIR Engagement</h4>
            <h2 class="text-center">From Diagnostic to Deep Dive</h2>
        </div>
        
        <div class="grid-cards" style="grid-template-columns: 1fr 1fr;">
            <!-- The Diagnostic -->
            <div class="card-service" style="background: white;">
                <div class="badge mb-4">Step 01</div>
                <h3>The 15-Minute Diagnostic</h3>
                <p>Gain immediate initial insight through our structured online assessment. It helps identify high-level risks and determines if a full consultant-led review is necessary.</p>
                <a href="/rapid-consulting?type=phi" class="btn-secondary mt-4">Start PIR Diagnostic</a>
            </div>
            
            <!-- Full Review -->
            <div class="card-service" style="background: white; border-left: 4px solid var(--primary);">
                <div class="badge mb-4">Step 02</div>
                <h3>The Full PIR Engagement</h3>
                <p>A comprehensive, senior-led investigation including:</p>
                <ul class="footer-links" style="margin-top: 20px; color: var(--slate-600); margin-bottom: 24px;">
                    <li style="margin-bottom: 8px;">• Stakeholder & Team Interviews</li>
                    <li style="margin-bottom: 8px;">• Deep Document & Artefact Review</li>
                    <li style="margin-bottom: 8px;">• Technical & Governance Analysis</li>
                    <li style="margin-bottom: 8px;">• Findings workshop & Presentation</li>
                </ul>
                <a href="/contact" class="btn-primary">Book PIR Consultation</a>
            </div>
        </div>
    </div>
</section>

<!-- Outcomes -->
<section class="section">
    <div class="container">
        <div class="section-header mb-12">
            <h2>Tangible Outcomes</h2>
        </div>
        <div class="grid-cards">
            <div class="card-service">
                <h3>Delivery Clarity</h3>
                <p>A clear, objective view of the programme's actual status versus stated progress.</p>
            </div>
            <div class="card-service">
                <h3>Priority Actions</h3>
                <p>A structured roadmap of immediate and long-term actions to mitigate critical risks.</p>
            </div>
            <div class="card-service">
                <h3>Optional Recovery</h3>
                <p>If required, RAB can transition from review into practical recovery and remediation support.</p>
            </div>
        </div>
    </div>
</section>
@endsection
