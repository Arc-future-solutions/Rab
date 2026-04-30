@extends('layouts.public')

@section('title', 'Programme Health Check — RAB CONSULTING')

@section('content')
<section class="page-hero">
  <div class="container hero-grid">
    <div>
      <div class="kicker"><span class="kicker-dot"></span> Public lead assessment</div>
      <h1>Programme Health Check</h1>
      <p>A short public diagnostic that provides an indicative programme health score and flags likely delivery risk without exposing the full paid framework.</p>
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
    <form data-assessment="phi">
      <div class="form-grid">
        <div><label>Full name</label><input type="text" required></div>
        <div><label>Job title</label><input type="text" required></div>
        <div><label>Company name</label><input type="text" required></div>
        <div><label>Email</label><input type="email" required></div>
        <div><label>Phone (optional)</label><input type="text" ></div>
        <div><label>Programme name</label><input type="text" required></div>
        <div><label>Programme type</label><input type="text" required></div>
        <div><label>Delivery stage</label><input type="text" required></div>
        <div><label>Industry</label><input type="text" required></div>
        <div><label>Estimated budget (optional)</label><input type="text" ></div>
        <div class="full"><label>Free-text concern</label><textarea required placeholder="Describe the current concern, risk or issue."></textarea></div>
        <div><label>Confidence level (optional)</label><select><option value="">Select</option><option>Low</option><option>Medium</option><option>High</option></select></div>
      </div>
      <div class="section" style="padding:26px 0 8px;">
        <h2>Diagnostic statements</h2>
        <div class="question-list">
          <div class="question-item">
            <div class="question-head">
              <div>
                <div class="badge">Governance & Control</div>
                <h3>Decision-making and escalation are clear and effective.</h3>
              </div>
              <div class="score-chip active">1–5</div>
            </div>
            <p class="subtle">Move the slider to rate the statement.</p>
            <input type="range" min="1" max="5" value="3">
          </div>
          <div class="question-item">
            <div class="question-head">
              <div>
                <div class="badge">Governance & Control</div>
                <h3>Programme reporting reflects the true status.</h3>
              </div>
              <div class="score-chip active">1–5</div>
            </div>
            <p class="subtle">Move the slider to rate the statement.</p>
            <input type="range" min="1" max="5" value="3">
          </div>
          <div class="question-item">
            <div class="question-head">
              <div>
                <div class="badge">Planning & Delivery</div>
                <h3>The delivery plan is realistic and credible.</h3>
              </div>
              <div class="score-chip active">1–5</div>
            </div>
            <p class="subtle">Move the slider to rate the statement.</p>
            <input type="range" min="1" max="5" value="3">
          </div>
          <div class="question-item">
            <div class="question-head">
              <div>
                <div class="badge">Planning & Delivery</div>
                <h3>Risks and dependencies are actively managed.</h3>
              </div>
              <div class="score-chip active">1–5</div>
            </div>
            <p class="subtle">Move the slider to rate the statement.</p>
            <input type="range" min="1" max="5" value="3">
          </div>
          <div class="question-item">
            <div class="question-head">
              <div>
                <div class="badge">Business Alignment</div>
                <h3>Business objectives and KPIs are clearly defined.</h3>
              </div>
              <div class="score-chip active">1–5</div>
            </div>
            <p class="subtle">Move the slider to rate the statement.</p>
            <input type="range" min="1" max="5" value="3">
          </div>
          <div class="question-item">
            <div class="question-head">
              <div>
                <div class="badge">Business Alignment</div>
                <h3>The business is actively engaged and accountable.</h3>
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
        <a class="btn secondary" href="/thank-you-phi">Preview thank you page</a>
      </div>
      <div class="result-box"></div>
    </form>
  </div>
</section>
@endsection

