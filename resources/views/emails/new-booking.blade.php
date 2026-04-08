<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>New Booking Notification</title>
    <style>
        body { font-family: Arial, sans-serif; background: #f4f7fb; margin: 0; padding: 30px; }
        .card { background: #fff; border-radius: 8px; max-width: 560px; margin: 0 auto; padding: 32px; box-shadow: 0 2px 8px rgba(0,0,0,.08); }
        h1 { color: #1e40af; font-size: 22px; margin-bottom: 4px; }
        .label { font-size: 12px; color: #6b7280; text-transform: uppercase; letter-spacing: .05em; margin-top: 20px; }
        .value { font-size: 16px; color: #111; margin-top: 2px; }
        .badge { display: inline-block; background: #dcfce7; color: #166534; border-radius: 999px; padding: 2px 12px; font-size: 13px; }
        .btn { display: inline-block; margin-top: 28px; background: #1d4ed8; color: #fff; text-decoration: none; padding: 12px 24px; border-radius: 6px; font-size: 15px; }
        .footer { margin-top: 32px; font-size: 12px; color: #9ca3af; text-align: center; }
    </style>
</head>
<body>
<div class="card">
    <h1>📅 New Booking Confirmed</h1>
    <p style="color:#6b7280;margin-top:4px;">A new Calendly booking has been received.</p>

    <div class="label">Client Name</div>
    <div class="value">{{ $booking->client_name ?? '—' }}</div>

    <div class="label">Client Email</div>
    <div class="value">{{ $booking->client_email ?? '—' }}</div>

    <div class="label">Meeting Date &amp; Time</div>
    <div class="value">
        @if($booking->starts_at)
            {{ $booking->starts_at->format('l, d F Y') }} at {{ $booking->starts_at->format('H:i') }}
            @if($booking->ends_at)
                – {{ $booking->ends_at->format('H:i') }}
            @endif
        @else
            TBC
        @endif
    </div>

    <div class="label">Event Type</div>
    <div class="value">{{ $booking->event_type_name ?? '—' }}</div>

    <div class="label">Status</div>
    <div class="value"><span class="badge">Confirmed</span></div>

    @if($booking->join_url)
        <a href="{{ $booking->join_url }}" class="btn">Join Meeting</a>
    @endif

    <div class="footer">
        RAB Consulting Services — internal notification
    </div>
</div>
</body>
</html>
