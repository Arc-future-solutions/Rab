@extends('layouts.public')

@section('title', 'Book a Consultation — RAB Consulting')

@section('content')
<!-- Booking Hero -->
<section class="section bg-soft" style="padding-top: 100px; padding-bottom: 60px; border-bottom: 1px solid var(--slate-200);">
    <div class="container" style="text-align: center;">
        <h4 class="mb-4">Schedule a Consultation</h4>
        <h1 class="mb-6">Book a 30-minute expert session</h1>
        <p class="hero-text" style="max-width: 700px; margin-left: auto; margin-right: auto;">
            Choose a time slot below for a focused discussion on your programme or service environment. You will receive an instant confirmation and a video meeting link.
        </p>
    </div>
</section>

<!-- Calendly Section -->
<section class="section">
    <div class="container">
        <div class="calendar-wrapper" style="background: white; border-radius: var(--radius-lg); border: 1px solid var(--slate-200); box-shadow: var(--shadow-lg); overflow: hidden;">
            <div style="padding: 24px 40px; border-bottom: 1px solid var(--slate-100); background: var(--bg-soft); display: flex; justify-content: space-between; align-items: center;">
                <div>
                    <h3 style="margin-bottom: 4px; font-size: 1.1rem;">Select Date & Time</h3>
                    <p style="font-size: 0.85rem; color: var(--slate-500); margin-bottom: 0;">Displayed in your local timezone</p>
                </div>
                <div style="display: flex; gap: 16px;">
                    <span style="font-size: 0.8rem; font-weight: 600; color: var(--primary); background: var(--bg-highlight); padding: 4px 12px; border-radius: 999px;">Video Consultation</span>
                </div>
            </div>
            
            <!-- Calendly Inline Widget -->
            <div 
                class="calendly-inline-widget" 
                data-url="{{ config('services.calendly.url') }}?hide_landing_page_details=1&hide_gdpr_banner=1&name={{ urlencode($name ?? '') }}&email={{ urlencode($email ?? '') }}&utm_content={{ urlencode($bookingToken ?? '') }}" 
                style="min-width:320px;height:700px;">
            </div>
        </div>
        
        <div class="text-center mt-12">
            <p class="text-slate-500" style="font-size: 0.9rem;">
                Having trouble with the calendar? <a href="/contact" class="underline" style="color: var(--primary);">Use our contact form</a> instead.
            </p>
        </div>
    </div>
</section>

<!-- Calendly JS -->
<script type="text/javascript" src="https://assets.calendly.com/assets/external/widget.js" async></script>
<script>
    window.addEventListener('message', function (event) {
        if (!event.data || event.data.event !== 'calendly.event_scheduled') {
            return;
        }

        const payload = event.data.payload || {};
        const eventUri = payload.event && payload.event.uri;
        const inviteeUri = payload.invitee && payload.invitee.uri;

        if (!eventUri || !inviteeUri) {
            return;
        }

        fetch(@json(route('booking.calendly-scheduled')), {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': @json(csrf_token()),
            },
            body: JSON.stringify({
                event_uri: eventUri,
                invitee_uri: inviteeUri,
                booking_token: @json($bookingToken ?? ''),
                name: @json($name ?? ''),
                email: @json($email ?? ''),
            }),
            keepalive: true,
        }).catch(function () {
            // Calendly webhook remains the primary fallback if this browser call fails.
        });
    });
</script>
@endsection
