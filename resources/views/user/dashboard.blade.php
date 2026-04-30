<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"/><meta name="viewport" content="width=device-width, initial-scale=1.0"/>
<title>Dashboard — RAB CONSULTING</title>
<link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,opsz,wght@0,9..40,300;0,9..40,400;0,9..40,500;0,9..40,600;1,9..40,300&family=DM+Serif+Display&display=swap" rel="stylesheet">
<link rel="stylesheet" href="/assets/css/styles.css"/>
</head><body>

<header class="topbar"><div class="container nav">
  <a class="brand" href="/"><img class="brand-logo" src="/assets/images/logo-rab.png" alt="RAB Consulting logo">
  <span class="brand-text"><strong>RAB CONSULTING</strong><span>USER PORTAL</span></span></a>
  <div style="display:flex;gap:12px;align-items:center;">
    <span class="subtle" style="margin-right: 12px;">Welcome, {{ auth()->user()->name }}</span>
    <form action="/logout" method="POST" style="display: inline;">
      @csrf
      <button class="btn ghost" type="submit">Logout</button>
    </form>
  </div>
</div></header>

<main class="section"><div class="container">
  <div class="section-head">
    <div>
      <div class="kicker">Overview</div>
      <h2>Your Assessment History</h2>
    </div>
    <a href="/rapid-consulting" class="btn primary">New Assessment</a>
  </div>

  @if($leads->isEmpty())
    <div class="empty-state">
      <h3>No assessments found</h3>
      <p class="subtle">You haven't completed any assessments yet.</p>
      <a href="/rapid-consulting" class="btn primary" style="margin-top: 24px;">Start Your First Assessment</a>
    </div>
  @else
    <div class="table-card">
      <table>
        <thead>
          <tr>
            <th>Type</th>
            <th>Overall Score</th>
            <th>RAG Status</th>
            <th>Priority</th>
            <th>Date</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          @foreach($leads as $lead)
            <tr>
              <td><strong>{{ strtoupper($lead->type) }}</strong></td>
              <td>{{ $lead->overall_score }}</td>
              <td>
                <span class="badge" style="background: var(--gold-dim); color: var(--gold-2); border-color: transparent;">
                  {{ $lead->rag_status }}
                </span>
              </td>
              <td>
                <span class="badge" style="background: {{ $lead->priority === 'High' ? 'var(--danger)' : 'var(--success)' }}20; color: {{ $lead->priority === 'High' ? 'var(--danger)' : 'var(--success)' }}; border-color: transparent;">
                  {{ $lead->priority }}
                </span>
              </td>
              <td>{{ $lead->created_at->format('M d, Y') }}</td>
              <td>
                <a href="{{ route('user.results', $lead) }}" class="btn ghost" style="padding: 5px 12px; font-size: 0.8rem;">View Results</a>
              </td>
            </tr>
          @endforeach
        </tbody>
      </table>
    </div>
  @endif
</div></main>

<footer class="footer"><div class="container">
  <div class="footer-bottom"><span>© 2026 RAB Consulting Services</span><span>Transforming execution through data and AI.</span></div>
</div></footer>

</body></html>
