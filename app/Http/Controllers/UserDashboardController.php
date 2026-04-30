<?php

namespace App\Http\Controllers;

use App\Models\Lead;
use Illuminate\Http\Request;

class UserDashboardController extends Controller
{
    public function index()
    {
        $leads = Lead::where('email', auth()->user()->email)
            ->orderBy('created_at', 'desc')
            ->get();
            
        // If there's only one assessment, redirect to it directly for a better experience
        if ($leads->count() === 1) {
            return redirect()->route('user.results', $leads->first()->id);
        }
            
        return view('user.dashboard', compact('leads'));
    }

    public function results(Lead $lead)
    {
        // Simple security check
        if ($lead->email !== auth()->user()->email && auth()->user()->role !== 'admin') {
            abort(403);
        }

        return view('user.results', compact('lead'));
    }
}
