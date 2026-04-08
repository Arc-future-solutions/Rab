<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"/><meta name="viewport" content="width=device-width, initial-scale=1.0"/>
<title>RAB Assessment Platform — RAB CONSULTING</title>
<meta name="description" content="RAB Assessment Platform front-end package"/>
<link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=DM+Serif+Display:ital@0;1&family=DM+Sans:ital,opsz,wght@0,9..40,300;0,9..40,400;0,9..40,500;0,9..40,600;1,9..40,300&display=swap" rel="stylesheet">
<link rel="stylesheet" href="/assets/css/styles.css"/>
</head><body>
<header class="topbar"><div class="container nav">
  <a class="brand" href="/"><img class="brand-logo" src="/assets/images/logo-rab.png" alt="RAB Consulting logo">
  <span class="brand-text"><strong>RAB CONSULTING</strong><span>ASSESSMENT PLATFORM</span></span></a>
  <nav class="nav-links"><a data-nav href="/about">About</a><a data-nav href="/services">Services</a><a data-nav href="/pricing">Pricing</a><a data-nav href="/rapid-consulting">Rapid Consulting</a><a data-nav href="/contact">Contact</a></nav>
  <div style="display:flex;gap:12px;align-items:center;">
    <button class="btn ghost menu-toggle" data-menu-toggle>Menu</button>
    <a class="nav-cta" href="/admin">Open portal preview</a>
  </div>
</div></header>

<section class="hero">
  <div class="container hero-grid">
    <div>
      <div class="kicker"><span class="kicker-dot"></span> Unified PHI + ITSM-HI assessment platform</div>
      <h1>Enterprise <span class="gradient-text">assessment platform</span> for lead diagnostics, consultant delivery and AI-assisted reporting.</h1>
      <p>This front-end package presents the full RAB Assessment Platform as a premium consulting product: two public health checks, one secure internal portal, structured assessment workflows, report approval views and AI prompt governance.</p>
      <div class="hero-actions">
        <a class="btn primary" href="/programme-health-check">Start Programme Health Check</a>
        <a class="btn secondary" href="/admin-dashboard">View internal portal</a>
      </div>
      <div class="stats">
        <div class="stat"><strong>4 products</strong><span>Programme Health Check, Service Health Check, Full PHI and Full ITSM-HI.</span></div>
        <div class="stat"><strong>1 shared portal</strong><span>Unified login, dashboard, database views, reports and AI prompt control.</span></div>
        <div class="stat"><strong>Human-approved outputs</strong><span>AI drafts remain internal until reviewed and approved.</span></div>
      </div>
    </div>
    <div class="visual-card">
      <video autoplay muted loop playsinline poster="assets/images/hero-network.svg"><source src="/assets/videos/hero-motion.mp4" type="video/mp4"></video>
      <div class="visual-overlay"><h3>Built like a serious consulting product</h3><p>Strict structure, premium UI, commercial clarity and operational logic across public assessments, assessor workflows and final report issuance.</p></div>
    </div>
  </div>
</section>

<section class="section">
  <div class="container">
    <div class="section-head"><div><div class="badge">Platform structure</div><h2>Two public entry products, two full internal frameworks.</h2></div></div>
    <div class="grid-4">
      <div class="card"><h3>Programme Health Check</h3><p>Lead-generation assessment for programme, ERP and transformation delivery risk.</p><a class="btn secondary" href="/programme-health-check">Open page</a></div>
      <div class="card"><h3>Service Health Check</h3><p>Lead-generation assessment for service, ITSM, support and operational stability risk.</p><a class="btn secondary" href="/service-health-check">Open page</a></div>
      <div class="card"><h3>Full PHI</h3><p>Consultant-led programme assessment with evidence capture, scoring, commentary and AI draft report.</p><a class="btn secondary" href="/assessment-phi-detail">Preview assessment</a></div>
      <div class="card"><h3>Full ITSM-HI</h3><p>Consultant-led service maturity review with pillar scoring, risk capture and final report workflow.</p><a class="btn secondary" href="/assessment-itsm-detail">Preview assessment</a></div>
    </div>
  </div>
</section>

<section class="section">
 <div class="container feature-grid">
   <div class="panel" style="padding:28px;">
     <div class="badge">Shared functions</div>
     <h2>One codebase, one dashboard, one role model.</h2>
     <ul>
       <li>Shared login and internal portal for Admin, Assessor, Reviewer and optional Client Viewer</li>
       <li>Lead triage, conversion to client and creation of programme or service records</li>
       <li>Configuration-driven assessment engine for PHI and ITSM-HI</li>
       <li>Server-side AI draft generation with prompt versioning and structured JSON outputs</li>
       <li>Final report review, approval and PDF export journey</li>
     </ul>
     <div class="hero-actions">
       <a class="btn primary" href="/admin-dashboard">Portal preview</a>
       <a class="btn secondary" href="/admin-ai-prompts">AI prompts</a>
     </div>
   </div>
   <div class="showcase">
     <video autoplay muted loop playsinline><source src="/assets/videos/operations-dashboard.mp4" type="video/mp4"></video>
     <div class="caption"><strong>Consulting workflow visual</strong><p class="subtle">Dashboard, triage, scoring, AI drafts, reviewer approval and report exports.</p></div>
   </div>
 </div>
</section>

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