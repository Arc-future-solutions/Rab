<?php

namespace App\Http\Controllers;

use App\Models\Lead;
use Illuminate\Http\Request;

class ServiceLeadController extends Controller
{
    public function submitDev(Request $request)
    {
        return $this->processLead($request, 'Dev', 'Development Inquiry');
    }

    public function submitConsulting(Request $request)
    {
        return $this->processLead($request, 'Consulting', 'Strategic Consulting');
    }

    public function submitProject(Request $request)
    {
        return $this->processLead($request, 'Project', 'Project Management');
    }

    public function submitAI(Request $request)
    {
        return $this->processLead($request, 'AI', 'AI Implementation');
    }

    public function submitContact(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'company' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'phone' => 'nullable|string|max:20',
            'type' => 'required|string',
            'message' => 'required|string',
        ]);

        Lead::create([
            'name' => $validated['name'],
            'company' => $validated['company'],
            'email' => $validated['email'],
            'phone' => $validated['phone'] ?? null,
            'source' => 'Website Contact',
            'type' => $validated['type'],
            'lead_status' => 'Warm',
            'answers_json' => ['message' => $validated['message']],
            'overall_score' => 0,
            'rag_status' => 'Amber',
            'priority' => 'Normal',
        ]);

        return redirect()->route('thank-you')->with('success', "Thank you! Your contact request has been received.");
    }

    private function processLead(Request $request, string $source, string $type)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'phone' => 'nullable|string|max:20',
            'position' => 'nullable|string|max:255',
            'organization' => 'required|string|max:255',
        ]);

        $requirements = $request->except(['_token', 'name', 'email', 'phone', 'position', 'organization']);

        Lead::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'] ?? null,
            'role_title' => $validated['position'] ?? null,
            'company' => $validated['organization'],
            'source' => $source,
            'type' => $type,
            'lead_status' => 'Warm',
            'answers_json' => $requirements,
            'overall_score' => 0,
            'rag_status' => 'Amber', // Default for new inquiries
            'priority' => 'Normal',
        ]);

        return redirect()->route('thank-you')->with('success', "Thank you! Your {$source} inquiry has been received.");
    }
}
