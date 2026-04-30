@extends('layouts.public')

@section('title', 'Pricing — RAB CONSULTING')

@section('content')
<section class="page-hero">
  <div class="container hero-grid">
    <div>
      <div class="kicker"><span class="kicker-dot"></span> Commercial presentation page</div>
      <h1>Pricing presentation for snapshots, executive reviews and full health assessments.</h1>
      <p>This page is structured as a premium commercial front-end. Pricing values are placeholders and can be adjusted later.</p>
    </div>
    <div class="showcase">
      <video autoplay muted loop playsinline><source src="/assets/videos/consulting-flow.mp4" type="video/mp4"></video>
      <div class="caption">
        <strong>Monetisation-ready front end</strong>
        <p class="subtle">Designed to support lead capture and paid assessment upsell journeys.</p>
      </div>
    </div>
  </div>
</section>

<section class="section">
  <div class="container grid-3">
    <div class="card"><h3>Programme Health Check</h3><p>Free or low-friction lead assessment for qualification.</p><p><strong>Indicative:</strong> Free</p><a class="btn secondary" href="/programme-health-check">Open</a></div>
    <div class="card"><h3>Service Health Check</h3><p>Commercial-safe service maturity snapshot for operational leads.</p><p><strong>Indicative:</strong> Free</p><a class="btn secondary" href="/service-health-check">Open</a></div>
    <div class="card"><h3>Executive Review / Full Assessment</h3><p>Consultant-led engagement with evidence, AI draft, reviewer approval and PDF report.</p><p><strong>Indicative:</strong> Custom proposal</p><a class="btn primary" href="/quote-request">Request a quote</a></div>
  </div>
</section>
@endsection

