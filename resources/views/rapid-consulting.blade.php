@extends('layouts.public')

@section('title', 'Rapid Consulting — RAB CONSULTING')

@push('head')
<style>
  /* Hero Animations */
  @keyframes fadeUp {
    from {
      opacity: 0;
      transform: translateY(30px);
    }

    to {
      opacity: 1;
      transform: translateY(0);
    }
  }

  @keyframes float {

    0%,
    100% {
      transform: translateY(0px);
    }

    50% {
      transform: translateY(-20px);
    }
  }

  @keyframes glow {

    0%,
    100% {
      opacity: 0.2;
    }

    50% {
      opacity: 0.4;
    }
  }

  .hero-badge {
    animation: fadeUp 0.6s ease-out 0ms both;
  }

  .hero-title {
    animation: fadeUp 0.6s ease-out 100ms both;
  }

  .hero-subtitle {
    animation: fadeUp 0.6s ease-out 200ms both;
  }

  .hero-ctas {
    animation: fadeUp 0.6s ease-out 300ms both;
  }

  .hero-word-highlight {
    background: linear-gradient(135deg, #60a5fa 0%, #3b82f6 100%);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
    position: relative;
    display: inline-block;
    font-weight: 800;
  }

  /* Animated grid background */
  .animated-grid {
    background-image:
      radial-gradient(circle at 2px 2px, rgba(255, 255, 255, 0.06) 1px, transparent 0);
    background-size: 50px 50px;
    animation: gridMove 25s linear infinite;
  }

  @keyframes gridMove {
    0% {
      background-position: 0 0;
    }

    100% {
      background-position: 50px 50px;
    }
  }

  /* Floating particles */
  .floating-particle {
    position: absolute;
    background: radial-gradient(circle, rgba(59, 130, 246, 0.2) 0%, transparent 70%);
    border-radius: 50%;
    animation: float 10s ease-in-out infinite;
    pointer-events: none;
  }

  .floating-particle.glow {
    animation: glow 5s ease-in-out infinite;
  }
</style>
@endpush

@section('content')
<!-- Rapid Consulting Hero -->
<section class="relative py-24 md:py-32 flex items-center overflow-hidden" style="background-color: #0F172A;">
  <!-- Dark overlay to ensure text readability -->
  <div class="absolute inset-0" style="background: rgba(15, 23, 42, 0.95);"></div>

  <!-- Animated Geometric Grid Background -->
  <div class="absolute inset-0 animated-grid"></div>

  <!-- Floating accent particles -->
  <div class="floating-particle glow" style="width: 400px; height: 400px; top: -10%; right: -5%;"></div>
  <div class="floating-particle" style="width: 200px; height: 200px; bottom: 10%; left: -5%; animation-delay: 2s;"></div>

  <div class="container relative z-10">
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-12 items-center">
      <div class="max-w-xl lg:max-w-none">
        <span class="hero-badge inline-flex items-center gap-2 px-5 py-2 rounded-full mb-6" style="background: rgba(37, 99, 235, 0.15); color: #60a5fa; border: 1px solid rgba(37, 99, 235, 0.2);">
          <span class="inline-block w-1.5 h-1.5 rounded-full" style="background-color: #60a5fa;"></span>
          Rapid Consulting
        </span>

        <h1 class="hero-title font-serif text-white text-4xl sm:text-5xl md:text-6xl font-bold leading-tight mb-6">
          Book a premium 30-minute <br><span class="hero-word-highlight">consulting session</span>
        </h1>

        <p class="hero-subtitle text-lg md:text-xl leading-relaxed mb-10 max-w-2xl" style="color: #e2e8f0 !important;">
          Designed for founders, executives and project leaders who need fast decision-oriented support before launching a larger engagement.
        </p>

        <div class="hero-ctas flex flex-col sm:flex-row gap-4">
          <x-button variant="primary" href="#rapid-form" class="text-base px-8 py-4 group shadow-lg hover:shadow-xl">
            Start the booking
            <svg class="ml-2 w-5 h-5 group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"></path>
            </svg>
          </x-button>
          <x-button variant="secondary" href="/contact" class="text-base px-8 py-4" style="border-color: #475569; color: #e2e8f0;">
            Contact the team
          </x-button>
        </div>
      </div>

      <!-- Hero Visual -->
      <div class="relative flex justify-center items-center">
        <img src="/assets/images/consulting-illustration.svg" alt="Rapid consulting" class="w-full max-w-md mx-auto relative z-10">
        <div class="absolute bottom-8 left-1/2 -translate-x-1/2 bg-white rounded-xl p-4 border border-line shadow-authority text-center max-w-xs w-full">
          <p class="font-bold text-primary mb-1">Executive-ready experience</p>
          <p class="text-xs text-slate">Structured booking flow with personal information, project details, schedule, confirmation and payment step.</p>
        </div>
      </div>
    </div>
  </div>
</section>

<section class="section bg-cloud" id="rapid-form">
  <div class="container bg-white p-8 md:p-12 rounded-2xl shadow-lg border border-line">
    <h2 class="font-manrope text-4xl md:text-5xl font-bold text-ink mb-10 text-center">Book Your Session</h2>
    <!-- Calendly inline widget begin -->
    <div class="calendly-inline-widget" data-url="{{ config('services.calendly.embed_url', 'https://calendly.com/your-calendly-id') }}" style="min-width:320px;height:700px;"></div>
    <script type="text/javascript" src="https://assets.calendly.com/assets/external/widget.js" async></script>
    <!-- Calendly inline widget end -->
  </div>
</section>
@endsection
