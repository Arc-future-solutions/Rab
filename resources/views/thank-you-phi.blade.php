@extends('layouts.public')

@section('title', 'Thank you — RAB CONSULTING')

@section('content')
<section class="page-hero">
  <div class="container">
    <div class="badge">Submission complete</div>
    <h1>Thank you for completing the Programme Health Check.</h1>
    <p>Your indicative result has been captured. The next step is an executive PHI review or proposal discussion.</p>
    <div class="hero-actions">
      <a class="btn primary" href="/quote-request?service=Executive%20PHI%20Review">Request Executive PHI Review</a>
      <a class="btn secondary" href="/">Back to home</a>
    </div>
  </div>
</section>
@endsection

