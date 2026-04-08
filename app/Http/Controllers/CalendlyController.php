<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Carbon\Carbon;

class CalendlyController extends Controller
{
    /**
     * Get the personal access token from env.
     */
    private function getToken()
    {
        return env('CALENDLY_API_TOKEN');
    }

    /**
     * Get the user URI from env or fetch it if not set.
     */
    private function getUserUri()
    {
        $uri = env('CALENDLY_USER_URI');
        if ($uri && $uri !== 'https://api.calendly.com/users/me') {
            return $uri;
        }

        $response = Http::withOptions(['verify' => false])->withToken($this->getToken())->get('https://api.calendly.com/users/me');
        if ($response->successful()) {
            return $response->json('resource.uri');
        }

        return null;
    }

    /**
     * List scheduled events (meetings).
     */
    public function listMeetings()
    {
        $token = $this->getToken();
        $userUri = $this->getUserUri();

        if (!$token || $token === 'placeholder_token' || !$userUri) {
            // Mock data for demonstration when no token is provided
            return [
                [
                    'name' => 'Expert Consultation - John Doe',
                    'start_time' => Carbon::now()->addHours(2)->toIso8601String(),
                    'end_time' => Carbon::now()->addHours(2)->addMinutes(30)->toIso8601String(),
                    'status' => 'active',
                    'location' => 'Zoom',
                    'invitee_email' => 'john.doe@example.com'
                ],
                [
                    'name' => 'Strategy Session - Jane Smith',
                    'start_time' => Carbon::now()->addDay()->setTime(10, 0)->toIso8601String(),
                    'end_time' => Carbon::now()->addDay()->setTime(10, 30)->toIso8601String(),
                    'status' => 'active',
                    'location' => 'Teams',
                    'invitee_email' => 'jane.smith@client.com'
                ]
            ];
        }

        // Real API call
        $response = Http::withOptions(['verify' => false])->withToken($token)->get('https://api.calendly.com/scheduled_events', [
            'user' => $userUri,
            'status' => 'active',
            'min_start_time' => Carbon::now()->toIso8601String(),
            'count' => 10
        ]);

        if ($response->successful()) {
            $events = $response->json('collection');
            return array_map(function ($event) {
                return [
                    'name' => $event['name'],
                    'start_time' => $event['start_time'],
                    'end_time' => $event['end_time'],
                    'status' => $event['status'],
                    'location' => $event['location']['type'] ?? 'Online',
                    'invitee_email' => $event['event_memberships'][0]['user_email'] ?? 'Unknown'
                ];
            }, $events);
        }

        return [];
    }
}
