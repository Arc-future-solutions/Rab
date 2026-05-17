<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>High-Priority Diagnostic Alert</title>
    <style>
        body { font-family: Arial, sans-serif; background: #f4f7fb; margin: 0; padding: 30px; }
        .card { background: #fff; border-radius: 8px; max-width: 620px; margin: 0 auto; padding: 32px; box-shadow: 0 2px 8px rgba(0,0,0,.08); }
        h1 { color: #991b1b; font-size: 24px; margin: 0 0 8px; }
        p { color: #374151; line-height: 1.5; }
        .label { font-size: 12px; color: #6b7280; text-transform: uppercase; letter-spacing: .05em; margin-top: 18px; }
        .value { font-size: 16px; color: #111827; margin-top: 4px; }
        .badge { display: inline-block; padding: 4px 12px; border-radius: 999px; background: #fee2e2; color: #991b1b; font-weight: 700; }
        ul { margin: 10px 0 0 18px; color: #111827; }
        .footer { margin-top: 28px; font-size: 12px; color: #9ca3af; text-align: center; }
    </style>
</head>
<body>
<div class="card">
    <h1>High-Priority Diagnostic Alert</h1>
    <p>A public diagnostic has been classified as high priority and requires review.</p>

    <div class="label">Lead</div>
    <div class="value">{{ $lead['name'] ?? '—' }} | {{ $lead['company'] ?? '—' }}</div>

    <div class="label">Contact</div>
    <div class="value">{{ $lead['email'] ?? '—' }} @if(!empty($lead['phone'])) | {{ $lead['phone'] }} @endif</div>

    <div class="label">Assessment</div>
    <div class="value">{{ strtoupper($results['type'] ?? 'PIR') }} | Score {{ number_format((float) ($results['overall_score'] ?? 0), 2) }} | <span class="badge">{{ $results['rag_status'] ?? 'Amber' }}</span></div>

    <div class="label">Context</div>
    <div class="value">
        {{ $context['delivery_stage'] ?? $context['service_context'] ?? '—' }}
        @if(!empty($context['regulatory_context']))
            | {{ $context['regulatory_context'] }}
        @endif
    </div>

    <div class="label">Top Three Insight Areas</div>
    <ul>
        @foreach($insightAreas as $area)
            <li>{{ $area['pillarOrDomain'] }} — {{ number_format((float) $area['score'], 2) }} — {{ $area['insightLabel'] }}</li>
        @endforeach
    </ul>

    <div class="footer">
        RAB Consulting Services — diagnostic escalation
    </div>
</div>
</body>
</html>
