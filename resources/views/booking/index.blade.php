@extends('layouts.public')

@section('title', 'Book a Rapid Consulting Call | RAB Consulting Services')

@section('content')
{{-- Calendly widget CSS --}}
<link href="https://assets.calendly.com/assets/external/widget.css" rel="stylesheet">

<style>
    .booking-page {
        background: linear-gradient(135deg, #f0f6ff 0%, #e8f0fe 100%);
        min-height: 80vh;
        padding-bottom: 60px;
    }
    .hero {
        text-align: center;
        padding: 52px 24px 32px;
    }
    .hero-badge {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        background: #dbeafe;
        color: #1d4ed8;
        border-radius: 999px;
        padding: 6px 16px;
        font-size: 13px;
        font-weight: 600;
        margin-bottom: 20px;
    }
    .hero h1 {
        font-size: clamp(28px, 5vw, 42px);
        font-weight: 800;
        color: #0f172a;
        line-height: 1.2;
        margin-bottom: 16px;
    }
    .hero h1 span { color: #2563eb; }
    .hero p {
        font-size: 17px;
        color: #475569;
        max-width: 600px;
        margin: 0 auto 12px;
        line-height: 1.7;
    }
    .trust-row {
        display: flex;
        gap: 24px;
        justify-content: center;
        flex-wrap: wrap;
        margin-bottom: 40px;
    }
    .trust-item {
        display: flex;
        align-items: center;
        gap: 8px;
        font-size: 13px;
        color: #64748b;
    }
    .trust-icon {
        width: 20px;
        height: 20px;
        color: #10b981;
    }
    .calendar-card {
        max-width: 900px;
        margin: 0 auto;
        background: #fff;
        border-radius: 20px;
        box-shadow: 0 4px 32px rgba(30,64,175,.10);
        overflow: hidden;
    }
    .calendar-card .card-header {
        background: linear-gradient(135deg, #1d4ed8 0%, #1e40af 100%);
        padding: 24px 32px;
        color: #fff;
    }
    .calendar-card .card-header h2 {
        font-size: 20px;
        font-weight: 700;
        margin: 0;
    }
    .calendar-card .card-header p {
        font-size: 14px;
        opacity: .85;
        margin-top: 4px;
    }
</style>

<div class="booking-page">
    {{-- Hero --}}
    <section class="hero">
        <div class="hero-badge">
            <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
            </svg>
            Schedule a Meeting
        </div>

        <h1>Book a <span>Rapid Consulting</span> Call</h1>

        <p>
            Get expert guidance on your IT service management or programme health in a focused 30-minute session.
            Choose a time slot below and receive an instant confirmation with a join link.
        </p>

        <div class="trust-row">
            <div class="trust-item">
                <svg class="trust-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                </svg>
                Instant confirmation
            </div>
            <div class="trust-item">
                <svg class="trust-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                </svg>
                Video join link included
            </div>
            <div class="trust-item">
                <svg class="trust-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                </svg>
                Free cancellation
            </div>
        </div>
    </section>

    {{-- Calendly embed --}}
    <div class="calendar-card">
        <div class="card-header">
            <h2>🗓 Select a Date &amp; Time</h2>
            <p>All times shown in your local timezone</p>
        </div>

        <div
            class="calendly-inline-widget"
            data-url="{{ config('services.calendly.url') }}?hide_landing_page_details=1&hide_gdpr_banner=1&name={{ urlencode($name ?? '') }}&email={{ urlencode($email ?? '') }}"
            style="min-width:320px;height:700px;">
        </div>
    </div>
</div>

{{-- Calendly embed script --}}
<script src="https://assets.calendly.com/assets/external/widget.js" async></script>
@endsection
