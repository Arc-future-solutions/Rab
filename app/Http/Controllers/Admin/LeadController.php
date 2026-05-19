<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Lead;
use App\Services\InternalCrmService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

use App\Traits\HasAssessmentQuestions;

class LeadController extends Controller
{
    use HasAssessmentQuestions;

    public function index(Request $request)
    {
        $query = Lead::query()->with(['bookings' => fn ($query) => $query->latest('starts_at')]);

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
        
        if ($request->filled('source')) {
            $query->where('source', $request->source);
        }

        if ($request->filled('booking_status')) {
            $query->where('booking_status', $request->booking_status);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $leads = $query->orderBy('created_at', 'desc')->get();
        $questions = [
            'PHI' => $this->phiQuestions,
            'ITSM' => $this->itsmQuestions,
            'PIR' => $this->phiQuestions,
            'SIR' => $this->itsmQuestions,
        ];

        return view('admin.leads.index', compact('leads', 'questions'));
    }

    public function update(Request $request, Lead $lead)
    {
        $data = $request->validate([
            'booking_status' => 'sometimes|required|in:NotBooked,BookingRequested,Booked',
            'note' => 'sometimes|nullable|string|max:5000',
        ]);

        $lead->update($data);

        return response()->json([
            'success' => true,
            'lead' => $lead->fresh()->load(['bookings' => fn ($query) => $query->latest('starts_at')]),
        ]);
    }

    public function export(Request $request): StreamedResponse
    {
        $query = Lead::query();

        foreach (['type', 'rag_status', 'priority', 'lead_status', 'source', 'booking_status'] as $filter) {
            if ($request->filled($filter)) {
                $query->where($filter, $request->input($filter));
            }
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $filename = 'rab-leads-' . now()->format('Ymd-His') . '.csv';

        return response()->streamDownload(function () use ($query) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, [
                'Submitted At',
                'Company',
                'Contact',
                'Email',
                'Framework',
                'Assessment Type',
                'Overall Score',
                'RAG Status',
                'Risk Priority',
                'Lead Status',
                'Booking Status',
                'Critical Flag',
                'Notes',
            ]);

            $query->orderBy('created_at', 'desc')->chunk(100, function ($leads) use ($handle) {
                foreach ($leads as $lead) {
                    fputcsv($handle, [
                        optional($lead->created_at)->toIso8601String(),
                        $lead->company,
                        $lead->name,
                        $lead->email,
                        $lead->type,
                        $lead->assessment_type,
                        $lead->overall_score,
                        $lead->rag_status,
                        $lead->priority,
                        $lead->lead_status,
                        $lead->booking_status,
                        $lead->critical_flag,
                        $lead->note,
                    ]);
                }
            });

            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv']);
    }

    public function convert(Lead $lead, InternalCrmService $internalCrm)
    {
        if (!$lead->converted_to_client) {
            $internalCrm->convertLeadToClient($lead);
        }

        return redirect()->route('admin.clients.show', $lead->client_id)->with('success', 'Lead successfully converted to Client.');
    }
}
