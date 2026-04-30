@extends('layouts.public')

@section('title', 'Service Health Check — RAB CONSULTING')

@section('content')
<section class="page-hero">
  <div class="container hero-grid">
    <div>
      <div class="kicker"><span class="kicker-dot"></span> Public lead assessment</div>
      <h1>Service Health Check</h1>
      <p>A short public diagnostic that provides an indicative service health view across operational control, resilience and ITSM maturity.</p>
      <div class="hero-actions">
        <a class="btn primary" href="#assessment-form">Start assessment</a>
        <a class="btn secondary" href="/quote-request">Request executive review</a>
      </div>
    </div>
    <div class="showcase">
      <video autoplay muted loop playsinline><source src="/assets/videos/consulting-flow.mp4" type="video/mp4"></video>
      <div class="caption">
        <strong>Commercial-safe snapshot</strong>
        <p class="subtle">Short, credible and positioned to convert into a deeper consulting review.</p>
      </div>
    </div>
  </div>
</section>

<section class="section" id="assessment-form">
  <div class="container form-card">
    <div class="section-head">
      <div>
        <h2>Lead capture and snapshot scoring</h2>
        <p>Indicative result only. The full framework remains inside the private consultant portal.</p>
      </div>
    </div>
    <form data-assessment="itsm">
      <div class="form-grid">
        <div><label>Full name</label><input type="text" required></div>
        <div><label>Job title</label><input type="text" required></div>
        <div><label>Company name</label><input type="text" required></div>
        <div><label>Email</label><input type="email" required></div>
        <div><label>Phone (optional)</label><input type="text" ></div>
        <div><label>Service name</label><input type="text" required></div>
        <div><label>Service scope</label><input type="text" required></div>
        <div><label>Industry</label><input type="text" required></div>
        <div><label>Support model type</label><input type="text" required></div>
        <div class="full"><label>Free-text concern</label><textarea required placeholder="Describe the current concern, risk or issue."></textarea></div>
        <div><label>Confidence level (optional)</label><select><option value="">Select</option><option>Low</option><option>Medium</option><option>High</option></select></div>
      </div>
      <div class="section" style="padding:26px 0 8px;">
        <h2>Diagnostic statements</h2>
        <div class="question-list">
          <div class="question-item">
            <div class="question-head">
              <div>
                <div class="badge">Service Governance & Ownership</div>
                <h3>Service ownership and accountability are clearly defined.</h3>
              </div>
              <div class="score-chip active">1–5</div>
            </div>
            <p class="subtle">Move the slider to rate the statement.</p>
            <input type="range" min="1" max="5" value="3">
          </div>
          <div class="question-item">
            <div class="question-head">
              <div>
                <div class="badge">Incident & Major Incident Management</div>
                <h3>Incidents are resolved in a controlled and timely manner.</h3>
              </div>
              <div class="score-chip active">1–5</div>
            </div>
            <p class="subtle">Move the slider to rate the statement.</p>
            <input type="range" min="1" max="5" value="3">
          </div>
          <div class="question-item">
            <div class="question-head">
              <div>
                <div class="badge">Change & Release Management</div>
                <h3>Changes are controlled and do not regularly create instability.</h3>
              </div>
              <div class="score-chip active">1–5</div>
            </div>
            <p class="subtle">Move the slider to rate the statement.</p>
            <input type="range" min="1" max="5" value="3">
          </div>
          <div class="question-item">
            <div class="question-head">
              <div>
                <div class="badge">Service Performance, SLA & Reporting</div>
                <h3>SLAs and KPIs reflect real service performance.</h3>
              </div>
              <div class="score-chip active">1–5</div>
            </div>
            <p class="subtle">Move the slider to rate the statement.</p>
            <input type="range" min="1" max="5" value="3">
          </div>
          <div class="question-item">
            <div class="question-head">
              <div>
                <div class="badge">Operational Resilience & Continuity</div>
                <h3>Disaster recovery, backup and continuity controls are reliable.</h3>
              </div>
              <div class="score-chip active">1–5</div>
            </div>
            <p class="subtle">Move the slider to rate the statement.</p>
            <input type="range" min="1" max="5" value="3">
          </div>
          <div class="question-item">
            <div class="question-head">
              <div>
                <div class="badge">Automation, Tooling & Service Optimisation</div>
                <h3>Service processes are supported by effective tools and automation.</h3>
              </div>
              <div class="score-chip active">1–5</div>
            </div>
            <p class="subtle">Move the slider to rate the statement.</p>
            <input type="range" min="1" max="5" value="3">
          </div>
        </div>
      </div>
      <div class="hero-actions">
        <button class="btn primary" type="submit">Calculate indicative result</button>
        <a class="btn secondary" href="/thank-you-itsm">Preview thank you page</a>
      </div>
      <div class="result-box"></div>
    </form>
  </div>
</section>
@endsection

