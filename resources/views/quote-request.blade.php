@extends('layouts.public')

@section('title', 'Request a quote — RAB CONSULTING')

@section('content')
<section class="page-hero">
  <div class="container">
    <div class="kicker"><span class="kicker-dot"></span> Executive engagement request</div>
    <h1>Request a proposal for a full assessment or recovery review.</h1>
    <p>This page pre-fills the selected service when users arrive from a service CTA.</p>
  </div>
</section>

<section class="section">
  <div class="container form-card">
    <form data-demo-form>
      <div class="form-grid">
        <div><label>Selected service</label><input id="selectedService" value="Full Assessment / Executive Review" required></div>
        <div><label>Company name</label><input required></div>
        <div><label>Primary contact</label><input required></div>
        <div><label>Email</label><input type="email" required></div>
        <div><label>Phone</label><input></div>
        <div><label>Industry</label><input></div>
        <div class="full"><label>Project or service name</label><input></div>
        <div class="full"><label>Description of need</label><textarea required placeholder="Describe the context, current risk and expected output."></textarea></div>
      </div>
      <div class="hero-actions">
        <button class="btn primary" type="submit">Submit request</button>
      </div>
      <div class="notice">Quote request captured in this front-end demo.</div>
    </form>
  </div>
</section>
@endsection

