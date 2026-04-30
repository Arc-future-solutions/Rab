@extends('layouts.public')

@section('title', 'Rapid Consulting — RAB CONSULTING')

@section('content')
<section class="page-hero">
  <div class="container hero-grid">
    <div>
      <div class="kicker"><span class="kicker-dot"></span> 30-minute expert consultation</div>
      <h1>Book a premium 30-minute consulting session in 5 clear steps.</h1>
      <p>Designed for founders, executives and project leaders who need fast decision-oriented support before launching a larger engagement.</p>
      <div class="cta-row"><a class="btn primary" href="#rapid-form">Start the booking</a><a class="btn secondary" href="/contact">Contact the team</a></div>
    </div>
    <div class="showcase">
      <img src="/assets/images/consulting-illustration.svg" alt="Rapid consulting">
      <div class="caption">
        <strong>Executive-ready experience</strong>
        <p class="subtle">Structured booking flow with personal information, project details, schedule, confirmation and payment step.</p>
      </div>
    </div>
  </div>
</section>

<section class="section" id="rapid-form">
  <div class="container form-card">
    <!-- Calendly inline widget begin -->
    <div class="calendly-inline-widget" data-url="{{ env('CALENDLY_EMBED_URL', 'https://calendly.com/your-calendly-id') }}" style="min-width:320px;height:700px;"></div>
    <script type="text/javascript" src="https://assets.calendly.com/assets/external/widget.js" async></script>
    <!-- Calendly inline widget end -->
  </div>
</section>
@endsection

