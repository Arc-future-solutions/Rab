<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"/><meta name="viewport" content="width=device-width, initial-scale=1.0"/>
<title>Rapid Consulting — RAB CONSULTING</title>
<meta name="description" content="RAB Assessment Platform front-end package"/>
<link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="/assets/css/styles.css"/>
</head><body>
<header class="topbar"><div class="container nav">
  <a class="brand" href="/"><img class="brand-logo" src="/assets/images/logo-rab.png" alt="RAB Consulting logo">
  <span class="brand-text"><strong>RAB CONSULTING</strong><span>ASSESSMENT PLATFORM</span></span></a>
  <nav class="nav-links"><a data-nav href="/about">About</a><a data-nav href="/services">Services</a><a data-nav href="/programme-health-check">Programme Health Check</a><a data-nav href="/service-health-check">Service Health Check</a><a data-nav href="/pricing">Pricing</a><a data-nav href="/rapid-consulting">Rapid Consulting</a><a data-nav href="/contact">Contact</a></nav>
  <div style="display:flex;gap:12px;align-items:center;">
    <button class="btn ghost menu-toggle" data-menu-toggle>Menu</button>
    <a class="nav-cta" href="/admin-dashboard">Open portal preview</a>
  </div>
</div></header>

<section class="page-hero">
  <div class="container hero-grid">
    <div>
      <div class="kicker"><span class="kicker-dot"></span> 30-minute expert consultation</div>
      <h1>Book a premium 30-minute consulting session in 5 clear steps.</h1>
      <p>Designed for founders, executives and project leaders who need fast decision-oriented support before launching a larger engagement.</p>
      <div class="cta-row"><a class="btn primary" href="#rapid-form">Start the booking</a><a class="btn secondary" href="/contact">Contact the team</a></div>
    </div>
    <div class="showcase"><img src="/assets/images/consulting-illustration.svg" alt="Rapid consulting"><div class="caption"><strong>Executive-ready experience</strong><p class="subtle">Structured booking flow with personal information, project details, schedule, confirmation and payment step.</p></div></div>
  </div>
</section>
<section class="section" id="rapid-form"><div class="container form-card">
  <!-- Calendly inline widget begin -->
  <div class="calendly-inline-widget" data-url="{{ env('CALENDLY_EMBED_URL', 'https://calendly.com/your-calendly-id') }}" style="min-width:320px;height:700px;"></div>
  <script type="text/javascript" src="https://assets.calendly.com/assets/external/widget.js" async></script>
  <!-- Calendly inline widget end -->
</div></section>

<footer class="footer"><div class="container">
  <div class="footer-grid">
    <div>
      <div class="brand" style="margin-bottom:16px;"><img class="brand-logo" src="/assets/images/logo-rab.png" alt="RAB Consulting logo">
      <span class="brand-text"><strong>RAB CONSULTING</strong><span>ASSESSMENT PLATFORM</span></span></div>
      <p>Unified front-end for Programme Health Check, Service Health Check, full consultant-led assessments, reporting workflows and internal review operations.</p>
    </div>
    <div><h4>Public Pages</h4><div class="footer-links">
      <a href="/programme-health-check">Programme Health Check</a>
      <a href="/service-health-check">Service Health Check</a>
      <a href="/pricing">Pricing</a>
      <a href="/rapid-consulting">Rapid Consulting</a>
    </div></div>
    <div><h4>Portal</h4><div class="footer-links">
      <a href="/admin-dashboard">Dashboard</a>
      <a href="/admin-leads">Leads</a>
      <a href="/admin-assessments">Assessments</a>
      <a href="/admin-reports">Reports</a>
    </div></div>
    <div><h4>Legal</h4><div class="footer-links">
      <a href="/privacy-policy">Privacy Policy</a>
      <a href="/terms">Terms</a>
    </div></div>
  </div>
  <div class="footer-bottom"><span>© 2026 RAB Consulting Services</span><span>Front-end build aligned to PHI and ITSM-HI platform structure.</span></div>
</div></footer>
<script src="/assets/js/main.js"></script>
</body></html>
