<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Client;
use App\Models\Assessment;
use App\Models\Lead;
use App\Http\Controllers\CalendlyController;

class DashboardController extends Controller
{
    public function index()
    {
        $totalClients = Client::count();
        $activeAssessments = Assessment::whereIn('status', ['draft', 'in_progress'])->count();
        $leadsCount = Lead::where('converted_to_client', false)->count();
        $pendingReview = Assessment::where('status', 'in_progress')->count(); // assuming in_progress implies pending review before approval

        $assessments = Assessment::with('client')
            ->orderBy('created_at', 'desc')
            ->take(5)
            ->get()
            ->map(function($a) {
                return [
                    'id' => $a->id,
                    'subject' => $a->client->company_name ?? 'Unknown',
                    'type' => $a->type,
                    'score' => $a->overall_score,
                    'rag' => $a->rag_status,
                    'status' => str_replace('_', ' ', $a->status),
                    'date' => $a->created_at,
                    'is_snapshot' => false,
                    'url' => route('admin.assessments.show', $a)
                ];
            });

        $snapshotLeads = Lead::orderBy('created_at', 'desc')
            ->take(5)
            ->get()
            ->map(function($l) {
                return [
                    'id' => $l->id,
                    'subject' => $l->company,
                    'type' => $l->type . ' Snapshot',
                    'score' => $l->overall_score,
                    'rag' => $l->rag_status,
                    'status' => 'New Lead',
                    'date' => $l->created_at,
                    'is_snapshot' => true,
                    'url' => route('admin.leads.index') // Leads index shows snapshots
                ];
            });

        $recentAssessments = $assessments->concat($snapshotLeads)
            ->sortByDesc('date')
            ->take(8);

        // RAG distribution
        $redCount = Assessment::where('rag_status', 'Red')->count();
        $amberCount = Assessment::where('rag_status', 'Amber')->count();
        $greenCount = Assessment::where('rag_status', 'Green')->count();
        $ragDistribution = [$redCount, $amberCount, $greenCount];
        
        $calendly = new CalendlyController();
        $scheduledMeetings = $calendly->listMeetings();

        // Upcoming bookings from DB (Calendly webhook)
        $upcomingBookingsCount = Booking::upcoming()->count();
        $upcomingBookings = Booking::upcoming()
            ->orderBy('starts_at')
            ->take(5)
            ->get();
        
        return view('admin.dashboard', compact(
            'totalClients',
            'activeAssessments',
            'leadsCount',
            'pendingReview',
            'recentAssessments',
            'ragDistribution',
            'scheduledMeetings',
            'upcomingBookingsCount',
            'upcomingBookings'
        ));
    }
}
