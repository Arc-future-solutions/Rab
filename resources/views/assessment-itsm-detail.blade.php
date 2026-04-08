<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"/><meta name="viewport" content="width=device-width, initial-scale=1.0"/>
<title>Full ITSM-HI — RAB CONSULTING</title>
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
      <div class="panel" style="padding:24px;"><div class="badge">Full ITSM-HI</div><h2>Preview of the Full ITSM Health Index assessment detail page.</h2></div>
      
<div class="assessment-layout">
  <div class="content-stack">
    <div class="panel" style="padding:24px;">
      <div class="toolbar"><h3>Full ITSM-HI assessment</h3><div class="actions"><span class="badge">Under review</span><button class="btn secondary">Generate AI draft</button></div></div>
      <p>Service maturity workspace with incident, change, transition, resilience and automation scoring.</p>
    </div>
    <div class="question-list">
      <div class="question-item"><div class="question-head"><div><div class="badge">P2 — Incident & Major Incident Management</div><h3>Major incident handling is structured and controlled.</h3></div><div class="score-chips"><span class="score-chip">1</span><span class="score-chip active">2</span><span class="score-chip">3</span><span class="score-chip">4</span><span class="score-chip">5</span></div></div><div class="form-grid"><div class="full"><label>Evidence note</label><textarea>Escalation routes exist but communications vary between teams and incident leadership is inconsistent.</textarea></div><div><label>Source type</label><select><option>interview</option><option>system_data</option></select></div><div><label>Confidence</label><select><option>high</option><option>medium</option></select></div><div class="full"><label>Assessor comment</label><textarea>Major incident structure exists on paper, but execution discipline is variable.</textarea></div></div></div>
      <div class="question-item"><div class="question-head"><div><div class="badge">P5 — Change & Release Management</div><h3>The change process is controlled and enforced.</h3></div><div class="score-chips"><span class="score-chip">1</span><span class="score-chip">2</span><span class="score-chip active">3</span><span class="score-chip">4</span><span class="score-chip">5</span></div></div><div class="form-grid"><div class="full"><label>Evidence note</label><textarea>CAB process exists but emergency change discipline is inconsistent and change failure analysis is weak.</textarea></div><div><label>Source type</label><select><option>document</option><option>workshop</option></select></div><div><label>Confidence</label><select><option>medium</option><option>high</option></select></div><div class="full"><label>Assessor comment</label><textarea>Control is present, but release assurance needs more operational rigour.</textarea></div></div></div>
      <div class="question-item"><div class="question-head"><div><div class="badge">P10 — Operational Resilience & Continuity</div><h3>DR and continuity testing is performed.</h3></div><div class="score-chips"><span class="score-chip active">1</span><span class="score-chip">2</span><span class="score-chip">3</span><span class="score-chip">4</span><span class="score-chip">5</span></div></div><div class="form-grid"><div class="full"><label>Evidence note</label><textarea>Plans exist but testing cadence is irregular and restoration evidence is limited.</textarea></div><div><label>Source type</label><select><option>document</option><option>observation</option></select></div><div><label>Confidence</label><select><option>high</option><option>medium</option></select></div><div class="full"><label>Assessor comment</label><textarea>Operational resilience is the most material service risk.</textarea></div></div></div>
    </div>
  </div>
  <aside class="content-stack">
    <div class="score-box"><h3>Overall score</h3><div class="result-score rag-amber">2.8</div><p>RAG: Amber</p><p>SSI: 2.5 · SMI: 3.0 · BRI-S: 2.7</p></div>
    <div class="panel" style="padding:24px;"><h3>Top service risks</h3><ul><li>Major incident discipline inconsistent</li><li>Change assurance not fully mature</li><li>DR and continuity testing below target</li></ul></div>
    <div class="roadmap">
      <div class="stage"><strong>0–30 days</strong><p>Reinforce incident command model and define change failure governance.</p></div>
      <div class="stage"><strong>31–60 days</strong><p>Refresh transition and BAU handover controls with support ownership.</p></div>
      <div class="stage"><strong>61–90 days</strong><p>Rehearse resilience scenarios and close continuity evidence gaps.</p></div>
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
