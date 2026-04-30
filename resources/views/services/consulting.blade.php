@extends('layouts.public')

@section('title', 'Consulting Services — RAB CONSULTING')

@section('content')
<section class="page-hero">
    <div class="container">
        <div class="kicker"><span class="kicker-dot"></span> Strategic Advisory</div>
        <h1>Strategic <span class="gradient-text">consulting</span> for complex enterprise transformations.</h1>
        <p>Expert advisory designed to turn your vision into a reality. Complete the inquiry below and one of our senior partners will contact you to discuss your idea.</p>
    </div>
</section>

<section class="section">
    <div class="container">
        <div class="form-card card shadow">
            <div class="section-head">
                <div>
                    <div class="badge">Consultation Request</div>
                    <h2>Share your vision</h2>
                    <p>Provide your details and describe the idea or project you need expert guidance on.</p>
                </div>
            </div>
            
            <form action="{{ route('services.consulting.submit') }}" method="POST" class="form-grid">
                @csrf
                <!-- User Info -->
                <div class="full">
                    <label for="name">Full Name</label>
                    <input type="text" id="name" name="name" placeholder="Michael Chen" required>
                </div>
                <div>
                    <label for="email">Work Email</label>
                    <input type="email" id="email" name="email" placeholder="m.chen@enterprise.com" required>
                </div>
                <div>
                    <label for="org">Organization</label>
                    <input type="text" id="org" name="organization" placeholder="e.g. Enterprise Global" required>
                </div>
                <div>
                    <label for="phone">Phone Number</label>
                    <input type="tel" id="phone" name="phone" placeholder="+1 (555) 000-0000">
                </div>
                <div>
                    <label for="position">Job Title</label>
                    <input type="text" id="position" name="position" placeholder="e.g. Strategic Partner">
                </div>
                <div>
                    <label for="service_type">Context of Idea</label>
                    <select id="service_type" name="service_type">
                        <option value="transformation">Digital Transformation</option>
                        <option value="product">New Product Launch</option>
                        <option value="operational">Operational Efficiency</option>
                        <option value="strategic">Strategic Growth</option>
                    </select>
                </div>

                <!-- Idea Content -->
                <div class="full">
                    <label for="idea">Your Idea</label>
                    <textarea id="idea" name="idea" placeholder="Describe the idea or challenge you need consulting for in detail..." required></textarea>
                </div>
                
                <div class="full">
                    <button type="submit" class="btn primary">Send Consulting Request</button>
                </div>
            </form>
        </div>
    </div>
</section>
@endsection
