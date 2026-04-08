<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"/><meta name="viewport" content="width=device-width, initial-scale=1.0"/>
<title>Full PHI — RAB CONSULTING</title>
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
      <div class="panel" style="padding:24px;"><div class="badge">Full PHI</div><h2>Preview of the Full Programme Health Index assessment detail page.</h2></div>
      
<div class="assessment-layout">
  <div class="content-stack">
    <div class="panel" style="padding:24px;">
      <div class="toolbar"><h3>Full PHI assessment</h3><div class="actions"><span class="badge">Assessment in progress</span><button class="btn secondary">Generate AI draft</button></div></div>
      <p>Programme-led assessment workspace with scoring, evidence notes, source type, confidence and assessor comments.</p>
    </div>
    <div class="question-list">
      <div class="question-item"><div class="question-head"><div><div class="badge">P1 — Governance & Decision-Making</div><h3>Decision rights are clearly defined and understood.</h3></div><div class="score-chips"><span class="score-chip">1</span><span class="score-chip">2</span><span class="score-chip active">3</span><span class="score-chip">4</span><span class="score-chip">5</span></div></div><div class="form-grid"><div class="full"><label>Evidence note</label><textarea>SteerCo cadence exists but escalation routing is still inconsistent across workstreams.</textarea></div><div><label>Source type</label><select><option>interview</option><option>document</option></select></div><div><label>Confidence</label><select><option>medium</option><option>high</option></select></div><div class="full"><label>Assessor comment</label><textarea>Governance intent is visible, but operating discipline is not yet fully embedded.</textarea></div></div></div>
      <div class="question-item"><div class="question-head"><div><div class="badge">P2 — Planning & Delivery Control</div><h3>There is a credible integrated plan.</h3></div><div class="score-chips"><span class="score-chip">1</span><span class="score-chip">2</span><span class="score-chip">3</span><span class="score-chip active">4</span><span class="score-chip">5</span></div></div><div class="form-grid"><div class="full"><label>Evidence note</label><textarea>Integrated plan exists and shows milestones, but critical path ownership still needs reinforcement.</textarea></div><div><label>Source type</label><select><option>document</option><option>workshop</option></select></div><div><label>Confidence</label><select><option>high</option><option>medium</option></select></div><div class="full"><label>Assessor comment</label><textarea>Plan is credible overall, with some dependency control gaps.</textarea></div></div></div>
      <div class="question-item"><div class="question-head"><div><div class="badge">P7 — Cutover & Go-Live Readiness</div><h3>A detailed cutover runbook exists.</h3></div><div class="score-chips"><span class="score-chip">1</span><span class="score-chip active">2</span><span class="score-chip">3</span><span class="score-chip">4</span><span class="score-chip">5</span></div></div><div class="form-grid"><div class="full"><label>Evidence note</label><textarea>Cutover sequencing is incomplete and rehearsal evidence is not yet available.</textarea></div><div><label>Source type</label><select><option>document</option><option>interview</option></select></div><div><label>Confidence</label><select><option>high</option><option>medium</option></select></div><div class="full"><label>Assessor comment</label><textarea>Go-live planning remains the most material risk area.</textarea></div></div></div>
    </div>
  </div>
  <aside class="content-stack">
    <div class="score-box"><h3>Overall score</h3><div class="result-score rag-amber">3.1</div><p>RAG: Amber</p><p>BRI: 2.9 · VRI: 3.3</p></div>
    <div class="panel" style="padding:24px;"><h3>Critical flags</h3><ul><li>Cutover readiness trending below threshold</li><li>Governance inconsistent across decisions</li><li>Data and rehearsal evidence incomplete</li></ul></div>
    <div class="roadmap">
      <div class="stage"><strong>0–30 days</strong><p>Stabilise governance, define escalation and complete cutover ownership map.</p></div>
      <div class="stage"><strong>31–60 days</strong><p>Run rehearsals, complete evidence pack and validate Day 1 operating model.</p></div>
      <div class="stage"><strong>61–90 days</strong><p>Close residual data and hypercare readiness gaps before go-live commitment.</p></div>
    </div>
  </aside>
</div>

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
