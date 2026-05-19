<?php

/**
 * CalendlyWebhookController — Setup Instructions
 * -----------------------------------------------
 * 1. Go to Calendly → Integrations → Webhooks
 * 2. Add webhook URL: https://yourdomain.com/webhooks/calendly
 * 3. Subscribe to events: invitee.created and invitee.canceled
 * 4. Copy the signing secret into CALENDLY_WEBHOOK_SECRET in .env
 * 5. Set CALENDLY_URL to your Calendly event link
 *    (e.g. https://calendly.com/yourname/rapid-consulting)
 * 6. In Calendly event settings → Location, connect Teams or Zoom
 *    so that join_url is automatically populated.
 */

namespace App\Http\Controllers;

use App\Mail\NewBookingNotification;
use App\Models\Booking;
use App\Services\InternalCrmService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class CalendlyWebhookController extends Controller
{
    public function handle(Request $request, InternalCrmService $internalCrm)
    {
        // ── 1. Verify webhook signing secret ──────────────────────────
        $signature = $request->header('Calendly-Webhook-Signature');
        $secret    = config('services.calendly.webhook_secret');

        if ($secret && $signature) {
            // Calendly sends: t=<timestamp>,v1=<hex-signature>
            $parts = [];
            foreach (explode(',', $signature) as $part) {
                [$k, $v]   = explode('=', $part, 2);
                $parts[$k] = $v;
            }
            $timestamp   = $parts['t']  ?? '';
            $receivedSig = $parts['v1'] ?? '';
            $computedSig = hash_hmac('sha256', $timestamp . '.' . $request->getContent(), $secret);

            if (! hash_equals($computedSig, $receivedSig)) {
                return response()->json(['error' => 'Invalid signature'], 401);
            }
        }

        $event   = $request->input('event');
        $payload = $request->input('payload');

        // ── 2. Handle invitee.created ─────────────────────────────────
        if ($event === 'invitee.created') {
            $eventUri   = $payload['scheduled_event']['uri'] ?? '';
            $eventUuid  = basename($eventUri);

            $inviteeUri  = $payload['uri'] ?? '';
            $inviteeUuid = basename($inviteeUri);

            $joinUrl = $payload['scheduled_event']['location']['join_url']
                ?? $payload['scheduled_event']['location']['data']['join_url']
                ?? null;
            $bookingToken = $payload['tracking']['utm_content'] ?? null;

            $booking = Booking::updateOrCreate(
                ['calendly_event_uuid' => $eventUuid],
                [
                    'calendly_invitee_uuid' => $inviteeUuid,
                    'client_name'           => $payload['name']                              ?? null,
                    'client_email'          => $payload['email']                             ?? null,
                    'starts_at'             => $payload['scheduled_event']['start_time']     ?? null,
                    'ends_at'               => $payload['scheduled_event']['end_time']       ?? null,
                    'join_url'              => $joinUrl,
                    'event_type_name'       => $payload['event_type']['name']                ?? null,
                    'status'                => 'active',
                    'raw_payload'           => $payload,
                ]
            );

            $internalCrm->syncLeadForBooking($booking, $bookingToken);

            // Send internal notification email (queued, non-blocking)
            try {
                Mail::to(config('mail.from.address'))
                    ->later(now(), new NewBookingNotification($booking));
            } catch (\Throwable $e) {
                // Log but don't fail the webhook response
                \Illuminate\Support\Facades\Log::error('Booking email failed: ' . $e->getMessage());
            }
        }

        // ── 3. Handle invitee.canceled ────────────────────────────────
        if ($event === 'invitee.canceled') {
            $eventUri  = $payload['scheduled_event']['uri'] ?? '';
            $eventUuid = basename($eventUri);

            $booking = Booking::where('calendly_event_uuid', $eventUuid)->first();

            if ($booking) {
                $booking->update([
                    'status'              => 'cancelled',
                    'cancellation_reason' => $payload['cancellation']['reason'] ?? null,
                ]);

                $internalCrm->syncLeadForCancelledBooking($booking);
            }
        }

        return response()->json(['status' => 'ok']);
    }
}
