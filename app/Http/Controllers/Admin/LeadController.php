<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Lead;
use App\Models\Client;
use Illuminate\Http\Request;

class LeadController extends Controller
{
    public function index(Request $request)
    {
        $query = Lead::query();

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        if ($request->filled('rag_status')) {
            $query->where('rag_status', $request->rag_status);
        }

        if ($request->filled('priority')) {
            $query->where('priority', $request->priority);
        }

        if ($request->filled('lead_status')) {
            $query->where('lead_status', $request->lead_status);
        }

        if ($request->filled('converted')) {
            $query->where('converted_to_client', $request->converted === 'yes');
        }

        $leads = $query->orderBy('created_at', 'desc')->get();

        return view('admin.leads.index', compact('leads'));
    }

    public function convert(Lead $lead)
    {
        if (!$lead->converted_to_client) {
            $client = Client::create([
                'company_name' => $lead->company,
                'primary_contact' => $lead->name,
                // Email not typically in standard leads unless added, assuming we have it here
            ]);

            $lead->update([
                'converted_to_client' => true,
                'client_id' => $client->id,
                'lead_status' => 'Hot'
            ]);
        }

        return redirect()->route('admin.clients.show', $lead->client_id)->with('success', 'Lead successfully converted to Client.');
    }
}
