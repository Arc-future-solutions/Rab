<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"/><meta name="viewport" content="width=device-width, initial-scale=1.0"/>
<title>Settings — RAB CONSULTING</title>
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

<section class="section">
  <div class="container portal-shell">
    <aside class="sidebar">
      <div class="sidebar-card">
        <div class="badge">Internal Portal</div>
        <h3 style="margin-top:10px;">RAB Assessment Operations</h3>
        <p class="subtle">Shared workspace for Admin, Assessor, Reviewer and optional Client Viewer.</p>
      </div>
      <div class="sidebar-card"><div class="sidebar-nav"><a data-nav href="/admin-dashboard">Dashboard</a><a data-nav href="/admin-leads">Leads</a><a data-nav href="/admin-clients">Clients</a><a data-nav href="/admin-assessments">Assessments</a><a data-nav href="/admin-reports">Reports</a><a data-nav href="/admin-ai-prompts">AI Prompts</a><a data-nav href="/admin-settings">Settings</a></div></div>
    </aside>
    <main class="content-stack">
      <div class="panel" style="padding:24px;"><div class="badge">Settings</div><h2>Authentication, notifications, export rules and implementation controls.</h2></div>
      
<div class="grid-3">
  <div class="card"><h3>Authentication</h3><p>Single internal authentication model for Admin, Assessor, Reviewer and optional Client Viewer.</p></div>
  <div class="card"><h3>Email rules</h3><p>Internal alerts on snapshot submission. AI-generated client emails remain disabled until approved.</p></div>
  <div class="card"><h3>Exports and storage</h3><p>PDF export path registry, attachment storage and audit log visibility.</p></div>
</div>
<div class="panel" style="padding:24px;"><h3>Implementation notes</h3><ul><li>AI must never calculate scores.</li><li>Public site must not expose the private full frameworks.</li><li>Raw AI output remains separate from final approved report.</li></ul></div>

    </main>
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
