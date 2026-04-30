@extends('layouts.public')

@section('title', 'Software Development — RAB CONSULTING')

@section('content')
<section class="page-hero">
    <div class="container">
        <div class="kicker"><span class="kicker-dot"></span> Digital Engineering</div>
        <h1>Modern software development and <span class="gradient-text">digital engineering</span> service.</h1>
        <p>Complete the forms below to start your engagement. The first section captures your contact details, and the second helps us gather the necessary business requirements to build your BRD.</p>
    </div>
</section>

<section class="section">
    <div class="container">
        <form action="{{ route('services.dev.submit') }}" method="POST">
            @csrf
            <!-- PART 1: USER FORM -->
            <div class="form-card card shadow mb-8">
                <div class="section-head">
                    <div>
                        <div class="badge">Part 1</div>
                        <h2>User Information</h2>
                        <p>Tell us who you are so we can coordinate the discovery process.</p>
                    </div>
                </div>
                <div class="form-grid">
                    <div>
                        <label for="name">Full Name</label>
                        <input type="text" id="name" name="name" placeholder="John Doe" required>
                    </div>
                    <div>
                        <label for="email">Work Email</label>
                        <input type="email" id="email" name="email" placeholder="john@company.com" required>
                    </div>
                    <div>
                        <label for="phone">Phone Number</label>
                        <input type="tel" id="phone" name="phone" placeholder="+1 (555) 000-0000">
                    </div>
                    <div>
                        <label for="position">Job Title/Position</label>
                        <input type="text" id="position" name="position" placeholder="e.g. CTO, Product Manager">
                    </div>
                </div>
            </div>

            <!-- PART 2: BUSINESS FORM (BRD BUILDER) -->
            <div class="form-card card shadow">
                <div class="section-head">
                    <div>
                        <div class="badge">Part 2</div>
                        <h2>Business Requirements (BRD Builder)</h2>
                        <p>Provide the foundational details we need to construct your Business Requirements Document.</p>
                    </div>
                </div>
                <div class="form-grid">
                    <div class="full">
                        <label for="org_name">Organization Name</label>
                        <input type="text" id="org_name" name="organization" placeholder="e.g. Global Tech Solutions" required>
                    </div>
                    <div class="full">
                        <label for="problem">The Problem Statement</label>
                        <textarea id="problem" name="problem" placeholder="What specific business problem are you trying to solve?" required></textarea>
                    </div>
                    <div class="full">
                        <label for="objective">Core Objectives & Goals</label>
                        <textarea id="objective" name="objective" placeholder="What are the primary goals of this project? What does success look like?" required></textarea>
                    </div>
                    <div>
                        <label for="target_audience">Target Audience</label>
                        <input type="text" id="target_audience" name="target_audience" placeholder="e.g. Internal employees, B2B customers">
                    </div>
                    <div>
                        <label for="timeline">Desired Timeline</label>
                        <input type="text" id="timeline" name="timeline" placeholder="e.g. 6 months, Q4 2026">
                    </div>
                    <div class="full">
                        <label for="features">Key Functional Requirements</label>
                        <textarea id="features" name="features" placeholder="List the critical features or capabilities required..." required></textarea>
                    </div>
                    <div class="full">
                        <label for="constraints">Constraints or Dependencies</label>
                        <textarea id="constraints" name="constraints" placeholder="Are there any budget, technology, or third-party constraints?"></textarea>
                    </div>
                    <div class="full">
                        <button type="submit" class="btn primary w-full">Submit BRD & Request Dev Engagement</button>
                    </div>
                </div>
            </div>
        </form>
    </div>
</section>
@endsection
