@extends('layouts.public')

@section('title', 'Project Management — RAB CONSULTING')

@section('content')
<section class="page-hero">
    <div class="container">
        <div class="kicker"><span class="kicker-dot"></span> Programme & Service Intelligence (PIR / SIR)</div>
        <h1>Precision <span class="gradient-text">project management</span> and delivery governance.</h1>
        <p>Ensure your most critical initiatives stay on track with our specialized PMO services, Programme & Service Intelligence (PIR / SIR), and outcome-focused project leadership.</p>
    </div>
</section>

<section class="section">
    <div class="container">
        <div class="grid-3" style="margin-bottom: 48px;">
            <div class="stat"><strong>PMO Setup</strong><span>Building robust project management offices from the ground up.</span></div>
            <div class="stat"><strong>Risk Intelligence</strong><span>Structured PIR / SIR to ensure delivery health and compliance.</span></div>
            <div class="stat"><strong>Specialist PMs</strong><span>Senior delivery experts for your most complex workstreams.</span></div>
        </div>

        <div class="form-card card shadow">
            <div class="section-head">
                <div>
                    <div class="badge">Project Inquiry</div>
                    <h2>Describe your delivery needs</h2>
                    <p>Tell us about the project or programme you need support with, and our delivery directors will contact you.</p>
                </div>
            </div>
            
            <form action="{{ route('services.project.submit') }}" method="POST" class="form-grid">
                @csrf
                <div class="full">
                    <label for="org">Organization Name</label>
                    <input type="text" id="org" name="organization" placeholder="e.g. Infrastructure United" required>
                </div>
                <div>
                    <label for="name">Contact Name</label>
                    <input type="text" id="name" name="name" placeholder="David Miller" required>
                </div>
                <div>
                    <label for="email">Work Email</label>
                    <input type="email" id="email" name="email" placeholder="d.miller@infra.com" required>
                </div>
                <div>
                    <label for="phone">Phone Number</label>
                    <input type="tel" id="phone" name="phone" placeholder="+1 (555) 000-0000">
                </div>
                <div>
                    <label for="position">Job Title</label>
                    <input type="text" id="position" name="position" placeholder="e.g. Operations Director">
                </div>
                <div class="full">
                    <label for="project_size">Estimated Project Budget/Scale</label>
                    <select id="project_size" name="project_size">
                        <option value="small">< $100k</option>
                        <option value="medium">$100k - $1M</option>
                        <option value="large">$1M - $10M</option>
                        <option value="enterprise">> $10M+</option>
                    </select>
                </div>
                <div class="full">
                    <label for="scope">Project Requirements</label>
                    <textarea id="scope" name="scope" placeholder="Briefly outline the project timeline, scale, and specific support needed (e.g. Planning, Risk Management, PMO setup)..." required></textarea>
                </div>
                <div class="full">
                    <button type="submit" class="btn primary">Inquire About Delivery</button>
                </div>
            </form>
        </div>
    </div>
</section>
@endsection
