<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Client;
use Illuminate\Http\Request;

class ClientController extends Controller
{
    public function index(Request $request)
    {
        $query = Client::query();

        if ($request->filled('industry')) {
            $query->where('industry', $request->industry);
        }

        if ($request->filled('search')) {
            $query->where('company_name', 'like', '%' . $request->search . '%')
                  ->orWhere('primary_contact', 'like', '%' . $request->search . '%');
        }

        $clients = $query->with('assessments')->orderBy('company_name')->get();

        // Calculate some aggregates for the view
        $clients->each(function ($client) {
            $client->assessment_count = $client->assessments->count();
            $latest = $client->assessments->sortByDesc('created_at')->first();
            $client->latest_assessment_date = $latest ? $latest->created_at : null;
            $client->latest_rag_status = $latest ? $latest->rag_status : null;
        });

        // Unique industries for filter dropdown
        $industries = Client::whereNotNull('industry')->pluck('industry')->unique();

        return view('admin.clients.index', compact('clients', 'industries'));
    }

    public function show(Client $client)
    {
        $client->load('assessments');
        $phiAssessments = $client->assessments->where('type', 'PHI');
        $itsmAssessments = $client->assessments->where('type', 'ITSM');
        
        return view('admin.clients.show', compact('client', 'phiAssessments', 'itsmAssessments'));
    }
}
