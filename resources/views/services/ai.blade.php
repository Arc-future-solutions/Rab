@extends('layouts.public')

@section('title', 'AI Solutions — RAB CONSULTING')

@section('content')
<section class="page-hero">
    <div class="container">
        <div class="kicker"><span class="kicker-dot"></span> Intelligent Automation</div>
        <h1>Applied <span class="gradient-text">artificial intelligence</span> and automation strategy.</h1>
        <p>Harness the power of Generative AI, machine learning, and intelligent automation to revolutionize your business processes and customer experiences.</p>
    </div>
</section>

<section class="section">
    <div class="container">
        <div class="grid-3" style="margin-bottom: 48px;">
            <div class="stat"><strong>GenAI Strategy</strong><span>Executive guidance on implementing Large Language Models.</span></div>
            <div class="stat"><strong>Automation</strong><span>Identifying and automating high-value business processes.</span></div>
            <div class="stat"><strong>Data Readiness</strong><span>Ensuring your data architecture is ready for AI scaling.</span></div>
        </div>

        <div class="form-card card shadow">
            <div class="section-head">
                <div>
                    <div class="badge">AI Inquiry</div>
                    <h2>Explore AI for your business</h2>
                    <p>Share your vision for AI and automation, and our specialists will help you identify the highest-impact use cases.</p>
                </div>
            </div>
            
            <form action="{{ route('services.ai.submit') }}" method="POST" class="form-grid">
                @csrf
                <div class="full">
                    <label for="org">Organization Name</label>
                    <input type="text" id="org" name="organization" placeholder="e.g. NextGen Corp" required>
                </div>
                <div>
                    <label for="name">Contact Name</label>
                    <input type="text" id="name" name="name" placeholder="Alice Wong" required>
                </div>
                <div>
                    <label for="email">Work Email</label>
                    <input type="email" id="email" name="email" placeholder="alice@nextgen.com" required>
                </div>
                <div>
                    <label for="phone">Phone Number</label>
                    <input type="tel" id="phone" name="phone" placeholder="+1 (555) 000-0000">
                </div>
                <div>
                    <label for="position">Job Title</label>
                    <input type="text" id="position" name="position" placeholder="e.g. AI Product Lead">
                </div>
                <div class="full">
                    <label for="ai_focus">Primary Area of Interest</label>
                    <select id="ai_focus" name="ai_focus">
                        <option value="genai">Generative AI / LLMs</option>
                        <option value="automation">Process Automation (RPA)</option>
                        <option value="analytics">Predictive Analytics</option>
                        <option value="data">Data Strategy for AI</option>
                    </select>
                </div>
                <div class="full">
                    <label for="scope">Use Case Description</label>
                    <textarea id="scope" name="scope" placeholder="What specific problem or opportunity are you looking to solve with AI?" required></textarea>
                </div>
                <div class="full">
                    <button type="submit" class="btn primary">Initiate AI Assessment</button>
                </div>
            </form>
        </div>
    </div>
</section>
@endsection
