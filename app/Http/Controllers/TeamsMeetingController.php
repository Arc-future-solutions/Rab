<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Carbon\Carbon;

class TeamsMeetingController extends Controller
{
    public function arrangeMeeting(Request $request)
    {
        $request->validate([
            'full_name' => 'required',
            'email' => 'required|email',
            'date' => 'required|date',
            'time' => 'required',
        ]);

        $tenantId = env('TEAMS_TENANT_ID', 'placeholder-tenant-id');
        $clientId = env('TEAMS_CLIENT_ID', 'placeholder-client-id');
        $clientSecret = env('TEAMS_CLIENT_SECRET', 'placeholder-client-secret');
        $userId = env('TEAMS_USER_ID', 'placeholder-user-id');

        // Note: For a real MS Graph integration, you would retrieve the access token here.
        // We will simulate the graph API response for demonstration if credentials are placeholders.
        if ($tenantId === 'placeholder-tenant-id') {
            // Mocking the success response if no real credentials are provided
            $mockJoinUrl = "https://teams.microsoft.com/l/meetup-join/19%3ameeting_xyz";
            if ($request->wantsJson()) {
                return response()->json(['status' => 'success', 'join_url' => $mockJoinUrl, 'message' => 'Teams meeting request received!']);
            }
            return redirect()->route('thank-you')->with('success', 'Teams meeting request received! A consultant will join you at the provided time.');
        }

        // 1. Get Access Token
        $tokenResponse = Http::withOptions(['verify' => false])->asForm()->post("https://login.microsoftonline.com/{$tenantId}/oauth2/v2.0/token", [
            'client_id' => $clientId,
            'client_secret' => $clientSecret,
            'grant_type' => 'client_credentials',
            'scope' => 'https://graph.microsoft.com/.default',
        ]);

        if (!$tokenResponse->successful()) {
            $errorMessage = $tokenResponse->json('error_description') ?? $tokenResponse->body();
            if ($request->wantsJson()) {
                return response()->json(['status' => 'error', 'message' => 'MS Auth Error: ' . $errorMessage]);
            }
            return back()->with('error', 'MS Auth Error: ' . $errorMessage);
        }

        $accessToken = $tokenResponse->json('access_token');

        $startDateTime = Carbon::parse($request->date . ' ' . $request->time)->format('Y-m-d\TH:i:s');
        $endDateTime = Carbon::parse($request->date . ' ' . $request->time)->addMinutes(30)->format('Y-m-d\TH:i:s');

        $meetingData = [
            'subject' => 'Rapid Consulting: ' . $request->full_name,
            'start' => [
                'dateTime' => $startDateTime,
                'timeZone' => 'UTC'
            ],
            'end' => [
                'dateTime' => $endDateTime,
                'timeZone' => 'UTC'
            ],
            'isOnlineMeeting' => true,
            'onlineMeetingProvider' => 'teamsForBusiness'
        ];

        $meetingResponse = Http::withOptions(['verify' => false])
            ->withToken($accessToken)
            ->post("https://graph.microsoft.com/v1.0/users/{$userId}/events", $meetingData);

        if ($meetingResponse->successful()) {
            $joinUrl = $meetingResponse->json('onlineMeeting.joinUrl');
            if ($request->wantsJson()) {
                return response()->json(['status' => 'success', 'join_url' => $joinUrl, 'message' => 'Teams meeting successfully created!']);
            }
            return back()->with('success', 'Teams meeting successfully created! Join URL: ' . $joinUrl);
        }

        if ($request->wantsJson()) {
            return response()->json(['status' => 'error', 'message' => 'Failed to create Teams meeting: ' . $meetingResponse->body()]);
        }
        return back()->with('error', 'Failed to create Teams meeting: ' . $meetingResponse->body());
    }
    public function arrangeMeet(Request $request)
    {
        $request->validate([
            'full_name' => 'required',
            'email' => 'required|email',
            'date' => 'required|date',
            'time' => 'required',
        ]);

        $tenantId = env('TEAMS_TENANT_ID', 'placeholder-tenant-id');
        $clientId = env('TEAMS_CLIENT_ID', 'placeholder-client-id');
        $clientSecret = env('TEAMS_CLIENT_SECRET', 'placeholder-client-secret');
        $userId = env('TEAMS_USER_ID', 'placeholder-user-id');

        // Note: For a real MS Graph integration, you would retrieve the access token here.
        // We will simulate the graph API response for demonstration if credentials are placeholders.
        if ($tenantId === 'placeholder-tenant-id') {
            // Mocking the success response if no real credentials are provided
            $mockJoinUrl = "https://teams.microsoft.com/l/meetup-join/19%3ameeting_xyz";
            if ($request->wantsJson()) {
                return response()->json(['status' => 'success', 'join_url' => $mockJoinUrl, 'message' => 'Teams meeting request received!']);
            }
            return redirect()->route('thank-you')->with('success', 'Teams meeting request received! A consultant will join you at the provided time.');
        }

        // 1. Get Access Token
        $tokenResponse = Http::withOptions(['verify' => false])->asForm()->post("https://login.microsoftonline.com/{$tenantId}/oauth2/v2.0/token", [
            'client_id' => $clientId,
            'client_secret' => $clientSecret,
            'grant_type' => 'client_credentials',
            'scope' => 'https://graph.microsoft.com/.default',
        ]);

        // dd($tokenResponse->json('access_token'));

        if (!$tokenResponse->successful()) {
            $errorMessage = $tokenResponse->json('error_description') ?? $tokenResponse->body();
            if ($request->wantsJson()) {
                return response()->json(['status' => 'error', 'message' => 'MS Auth Error: ' . $errorMessage]);
            }
            return back()->with('error', 'MS Auth Error: ' . $errorMessage);
        }

        $accessToken = $tokenResponse->json('access_token');
// Run this once to find your licensed users
$usersResponse = Http::withOptions(['verify' => false])
    ->withToken($accessToken)
    ->get("https://graph.microsoft.com/v1.0/users?\$select=id,displayName,mail,assignedLicenses");

dd($usersResponse->json());
        // Get the user's intended timezone (pass it from the request, or use your app default)
$timezone = 'Morocco Standard Time'; // fallback to your server/app timezone

$startDateTime = Carbon::parse($request->date . ' ' . $request->time)
    ->format('Y-m-d\TH:i:s');

$endDateTime = Carbon::parse($request->date . ' ' . $request->time)
    ->addMinutes(30)
    ->format('Y-m-d\TH:i:s');

$meetingData = [
    'subject'              => 'Rapid Consulting: ' . $request->full_name,
    'start'                => [
        'dateTime' => $startDateTime,
        'timeZone' => $timezone,
    ],
    'end'                  => [
        'dateTime' => $endDateTime,
        'timeZone' => $timezone,
    ],
    'isOnlineMeeting'      => true,
    'onlineMeetingProvider' => 'teamsForBusiness',
];
// dd([
//     'url'         => "https://graph.microsoft.com/v1.0/users/{$userId}/events",
//     'meetingData' => $meetingData,
//     'token_set'   => !empty($accessToken),
// ]);
$meetingResponse = Http::withOptions(['verify' => false])
    ->withToken($accessToken)
    ->withBody(json_encode($meetingData), 'application/json')  // 👈 keeps auth + forces JSON body
    ->post("https://graph.microsoft.com/v1.0/users/{$userId}/events");

    dd([
    'token_first_50' => substr($accessToken, 0, 50),
    'token_last_10'  => substr($accessToken, -10),
    'user_id'        => $userId,
    'encoded_body'   => json_encode($meetingData),
    'json_error'     => json_last_error_msg(),
]);

//     $parts = explode('.', $accessToken);
// $payload = json_decode(base64_decode(str_pad($parts[1], strlen($parts[1]) + (4 - strlen($parts[1]) % 4) % 4, '=')), true);
// $userCheck = Http::withOptions(['verify' => false])
//     ->withToken($accessToken)
//     ->get("https://graph.microsoft.com/v1.0/users/");

// dd([
//     'status' => $userCheck->status(),
//     'body'   => $userCheck->json(),
// ]);
// dd([
//     'aud'   => $payload['aud'] ?? 'missing',   // must be "https://graph.microsoft.com"
//     'roles' => $payload['roles'] ?? 'missing', // must include "Calendars.ReadWrite"
//     'scp'   => $payload['scp'] ?? 'missing',   // delegated scopes (if any)
//     'exp'   => isset($payload['exp']) ? Carbon::createFromTimestamp($payload['exp'])->toDateTimeString() : 'missing', // must not be in the past
//     'tid'   => $payload['tid'] ?? 'missing',   // tenant ID
//     'oid'   => $payload['oid'] ?? 'missing',   // object ID of the app/user
// ]);
    dd([
    'status'  => $meetingResponse->status(),
    'body'    => $meetingResponse->body(),
    'json'    => $meetingResponse->json(),
    'headers' => $meetingResponse->headers(),
]);

        if ($meetingResponse->successful()) {
            $joinUrl = $meetingResponse->json('onlineMeeting.joinUrl');
            if ($request->wantsJson()) {
                return response()->json(['status' => 'success', 'join_url' => $joinUrl, 'message' => 'Teams meeting successfully created!']);
            }
            return back()->with('success', 'Teams meeting successfully created! Join URL: ' . $joinUrl);
        }

        if ($request->wantsJson()) {
            return response()->json(['status' => 'error', 'message' => 'Failed to create Teams meeting: ' . $meetingResponse->body()]);
        }
            return response()->json(['status' => 'error', 'message' => 'Failed to create Teams meeting: ' . $meetingResponse->body()]);
        
    }
}
