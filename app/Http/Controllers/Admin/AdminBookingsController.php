<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use Illuminate\Http\Request;

class AdminBookingsController extends Controller
{
    public function index(Request $request)
    {
        $query = Booking::query()->with('lead')->orderBy('starts_at', 'desc');

        // Filter by status
        if ($request->filled('status') && in_array($request->status, ['active', 'cancelled'])) {
            $query->where('status', $request->status);
        }

        // Search by client name or email
        if ($request->filled('search')) {
            $search = '%' . $request->search . '%';
            $query->where(function ($q) use ($search) {
                $q->where('client_name',  'like', $search)
                  ->orWhere('client_email', 'like', $search);
            });
        }

        $bookings = $query->paginate(20)->withQueryString();

        return view('admin.bookings.index', compact('bookings'));
    }
}
