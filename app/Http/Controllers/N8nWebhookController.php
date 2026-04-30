<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class N8nWebhookController extends Controller
{
    public function receive(Request $request)
    {
        Log::info('N8n Webhook Received', ['payload' => $request->all()]);

        $request->validate([
            'lead_id' => 'required',
            'recommendation' => 'required|string'
        ]);

        $lead = \App\Models\Lead::find($request->lead_id);

        if (!$lead) {
            Log::error('N8n Webhook Error: Lead not found', ['lead_id' => $request->lead_id]);
            return response()->json(['error' => 'Lead not found'], 404);
        }

        $lead->update([
            'ai_recommendation' => $request->recommendation
        ]);

        return response()->json(['status' => 'success']);
    }
}
