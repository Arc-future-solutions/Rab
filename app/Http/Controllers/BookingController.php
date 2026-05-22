<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Services\InternalCrmService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class BookingController extends Controller
{
    /**
     * Show the public Calendly booking page.
     * Accepts optional name and email query params for prefilling.
     */
    public function index(Request $request)
    {
        return view('booking.index', [
            'name'  => $request->query('name', ''),
            'email' => $request->query('email', ''),
            'bookingToken' => $request->query('booking_token', ''),
        ]);
    }

    public function calendlyScheduled(Request $request, InternalCrmService $internalCrm): JsonResponse
    {
        $validated = $request->validate([
            'event_uri' => ['required', 'url'],
            'invitee_uri' => ['required', 'url'],
            'booking_token' => ['nullable', 'string'],
            'name' => ['nullable', 'string'],
            'email' => ['nullable', 'email'],
        ]);

        $eventUri = $validated['event_uri'];
        $inviteeUri = $validated['invitee_uri'];

        if (! $this->isCalendlyApiUri($eventUri) || ! $this->isCalendlyApiUri($inviteeUri)) {
            return response()->json(['message' => 'Invalid Calendly booking payload.'], 422);
        }

        $event = $this->fetchCalendlyResource($eventUri);
        $invitee = $this->fetchCalendlyResource($inviteeUri);

        $eventUuid = basename(parse_url($eventUri, PHP_URL_PATH));
        $inviteeUuid = basename(parse_url($inviteeUri, PHP_URL_PATH));
        $joinUrl = $event['location']['join_url']
            ?? $event['location']['data']['join_url']
            ?? null;

        $booking = Booking::updateOrCreate(
            ['calendly_event_uuid' => $eventUuid],
            [
                'calendly_invitee_uuid' => $inviteeUuid,
                'client_name' => $invitee['name'] ?? $validated['name'] ?? null,
                'client_email' => $invitee['email'] ?? $validated['email'] ?? null,
                'starts_at' => $event['start_time'] ?? null,
                'ends_at' => $event['end_time'] ?? null,
                'join_url' => $joinUrl,
                'event_type_name' => $event['name'] ?? null,
                'status' => 'active',
                'raw_payload' => [
                    'source' => 'calendly_widget_event_scheduled',
                    'event_uri' => $eventUri,
                    'invitee_uri' => $inviteeUri,
                    'event' => $event,
                    'invitee' => $invitee,
                ],
            ]
        );

        $internalCrm->syncLeadForBooking($booking, $validated['booking_token'] ?? null);

        return response()->json([
            'status' => 'ok',
            'booking_id' => $booking->id,
        ]);
    }

    private function fetchCalendlyResource(string $uri): array
    {
        $token = config('services.calendly.api_token');
        if (! $token) {
            return [];
        }

        try {
            $response = Http::withToken($token)->acceptJson()->get($uri);

            if ($response->successful()) {
                return $response->json('resource') ?? [];
            }

            Log::warning('Calendly resource fetch failed.', [
                'uri' => $uri,
                'status' => $response->status(),
            ]);
        } catch (\Throwable $exception) {
            Log::warning('Calendly resource fetch exception.', [
                'uri' => $uri,
                'message' => $exception->getMessage(),
            ]);
        }

        return [];
    }

    private function isCalendlyApiUri(string $uri): bool
    {
        $parts = parse_url($uri);

        return ($parts['scheme'] ?? null) === 'https'
            && ($parts['host'] ?? null) === 'api.calendly.com';
    }
}
