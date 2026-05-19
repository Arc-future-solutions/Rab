<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

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
}
