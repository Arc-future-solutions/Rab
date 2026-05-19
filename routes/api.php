<?php

use App\Http\Controllers\TeamsMeetingController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Http;


if (! function_exists('getAccessToken')) {
    function getAccessToken() {
        $response = Http::withOptions(['verify' => false])->asForm()->post(
            'https://login.microsoftonline.com/'.env('TEAMS_TENANT_ID').'/oauth2/v2.0/token',
            [
                'client_id' => env('TEAMS_CLIENT_ID'),
                'client_secret' => env('TEAMS_CLIENT_SECRET'),
                'scope' => 'https://graph.microsoft.com/.default',
                'grant_type' => 'client_credentials',
            ]
        );

        return $response->json()['access_token'];
    }
}

Route::get('/teams/users', function () {

    $token = getAccessToken(); // make sure this function exists

    $response = Http::withOptions(['verify' => false])
        ->withToken($token)
        ->get('https://graph.microsoft.com/v1.0/users');

    return response()->json($response->json());
});


Route::post('/arrange-teams-meeting', [TeamsMeetingController::class, 'arrangeMeet'])->name('teams.arrange.api');
