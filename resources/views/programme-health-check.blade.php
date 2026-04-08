<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"/><meta name="viewport" content="width=device-width, initial-scale=1.0"/>
<title>Programme Health Check — RAB CONSULTING</title>
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

<section class="page-hero"><div class="container hero-grid">
<div><div class="kicker"><span class="kicker-dot"></span> Public lead assessment</div><h1>Programme Health Check</h1><p>A short public diagnostic that provides an indicative programme health score and flags likely delivery risk without exposing the full paid framework.</p><div class="hero-actions"><a class="btn primary" href="#assessment-form">Start assessment</a><a class="btn secondary" href="/quote-request">Request executive review</a></div></div>
<div class="showcase"><video autoplay muted loop playsinline><source src="/assets/videos/consulting-flow.mp4" type="video/mp4"></video><div class="caption"><strong>Commercial-safe snapshot</strong><p class="subtle">Short, credible and positioned to convert into a deeper consulting review.</p></div></div>
</div></section>
<section class="section" id="assessment-form"><div class="container form-card">
<div class="section-head"><div><h2>Lead capture and snapshot scoring</h2><p>Indicative result only. The full framework remains inside the private consultant portal.</p></div></div>
<form data-assessment="phi">
  <div class="form-grid"><div><label>Full name</label><input type="text" required></div><div><label>Job title</label><input type="text" required></div><div><label>Company name</label><input type="text" required></div><div><label>Email</label><input type="email" required></div><div><label>Phone (optional)</label><input type="text" ></div><div><label>Programme name</label><input type="text" required></div><div><label>Programme type</label><input type="text" required></div><div><label>Delivery stage</label><input type="text" required></div><div><label>Industry</label><input type="text" required></div><div><label>Estimated budget (optional)</label><input type="text" ></div><div class="full"><label>Free-text concern</label><textarea required placeholder="Describe the current concern, risk or issue."></textarea></div><div><label>Confidence level (optional)</label><select><option value="">Select</option><option>Low</option><option>Medium</option><option>High</option></select></div></div>
  <div class="section" style="padding:26px 0 8px;"><h2>Diagnostic statements</h2><div class="question-list"><div class="question-item"><div class="question-head"><div><div class="badge">Governance & Control</div><h3>Decision-making and escalation are clear and effective.</h3></div><div class="score-chip active">1–5</div></div>
    <p class="subtle">Move the slider to rate the statement.</p><input type="range" min="1" max="5" value="3"></div><div class="question-item"><div class="question-head"><div><div class="badge">Governance & Control</div><h3>Programme reporting reflects the true status.</h3></div><div class="score-chip active">1–5</div></div>
    <p class="subtle">Move the slider to rate the statement.</p><input type="range" min="1" max="5" value="3"></div><div class="question-item"><div class="question-head"><div><div class="badge">Planning & Delivery</div><h3>The delivery plan is realistic and credible.</h3></div><div class="score-chip active">1–5</div></div>
    <p class="subtle">Move the slider to rate the statement.</p><input type="range" min="1" max="5" value="3"></div><div class="question-item"><div class="question-head"><div><div class="badge">Planning & Delivery</div><h3>Risks and dependencies are actively managed.</h3></div><div class="score-chip active">1–5</div></div>
    <p class="subtle">Move the slider to rate the statement.</p><input type="range" min="1" max="5" value="3"></div><div class="question-item"><div class="question-head"><div><div class="badge">Business Alignment</div><h3>Business objectives and KPIs are clearly defined.</h3></div><div class="score-chip active">1–5</div></div>
    <p class="subtle">Move the slider to rate the statement.</p><input type="range" min="1" max="5" value="3"></div><div class="question-item"><div class="question-head"><div><div class="badge">Business Alignment</div><h3>The business is actively engaged and accountable.</h3></div><div class="score-chip active">1–5</div></div>
    <p class="subtle">Move the slider to rate the statement.</p><input type="range" min="1" max="5" value="3"></div></div></div>
  <div class="hero-actions"><button class="btn primary" type="submit">Calculate indicative result</button><a class="btn secondary" href="/thank-you-phi">Preview thank you page</a></div>
  <div class="result-box"></div>
</form>
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
