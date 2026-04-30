@extends('admin.layouts.app')

@section('header', 'Dashboard')

@section('content')
<div class="grid grid-cols-1 md:grid-cols-5 gap-6 mb-6">
    <div class="bg-white rounded-lg shadow p-6">
        <h3 class="text-sm font-medium text-gray-500 mb-1">Total Clients</h3>
        <div class="text-3xl font-bold">{{ $totalClients }}</div>
    </div>
    <div class="bg-white rounded-lg shadow p-6">
        <h3 class="text-sm font-medium text-gray-500 mb-1">Active Assessments</h3>
        <div class="text-3xl font-bold">{{ $activeAssessments }}</div>
    </div>
    <div class="bg-white rounded-lg shadow p-6">
        <h3 class="text-sm font-medium text-gray-500 mb-1">Pending Leads</h3>
        <div class="text-3xl font-bold">{{ $leadsCount }}</div>
    </div>
    <div class="bg-white rounded-lg shadow p-6">
        <h3 class="text-sm font-medium text-gray-500 mb-1">Assessments Pending Review</h3>
        <div class="text-3xl font-bold">{{ $pendingReview }}</div>
    </div>
    <div class="bg-white rounded-lg shadow p-6 border-l-4 border-blue-500">
        <h3 class="text-sm font-medium text-gray-500 mb-1">Upcoming Bookings</h3>
        <div class="text-3xl font-bold text-blue-600">{{ $upcomingBookingsCount }}</div>
        <a href="{{ route('admin.bookings.index') }}" class="text-xs text-blue-500 hover:underline mt-1 inline-block">View all &rarr;</a>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <div class="lg:col-span-2 bg-white rounded-lg shadow">
        <div class="px-6 py-4 border-b border-gray-200">
            <h3 class="text-lg font-medium">Recent Assessments</h3>
        </div>
        <div class="p-0 overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Client</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Type</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Score</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @foreach($recentAssessments as $item)
                        <tr class="hover:bg-gray-50 cursor-pointer" onclick="window.location='{{ $item['url'] }}'">
                            <td class="px-6 py-4 whitespace-nowrap font-medium text-gray-900">
                                <div class="flex flex-col">
                                    <span class="font-bold text-slate-800">{{ $item['subject'] }}</span>
                                    @if($item['is_snapshot'])
                                        <span class="text-[9px] text-blue-600 font-bold uppercase tracking-widest">Web Snapshot</span>
                                    @endif
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $item['type'] }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm">
                                <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full 
                                    @if($item['rag'] === 'Green') bg-green-100 text-green-800 
                                    @elseif($item['rag'] === 'Amber') bg-amber-100 text-amber-800 
                                    @else bg-red-100 text-red-800 @endif">
                                    {{ number_format($item['score'], 1) }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 capitalize">{{ $item['status'] }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $item['date']->format('M d, Y') }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    
    <div class="bg-white rounded-lg shadow">
        <div class="px-6 py-4 border-b border-gray-200">
            <h3 class="text-lg font-medium">RAG Distribution</h3>
        </div>
        <div class="p-6">
            <canvas id="ragChart"></canvas>
        </div>
        
        <div class="px-6 py-4 border-t border-gray-200 bg-gray-50">
            <h3 class="text-sm font-semibold text-gray-700 uppercase tracking-wider">Upcoming Meetings (Calendly)</h3>
        </div>
        <div class="p-4 space-y-4">
            @forelse($scheduledMeetings as $meeting)
                <div class="flex items-center gap-3 p-3 border rounded-lg hover:bg-gray-50">
                    <div class="bg-blue-100 text-blue-600 p-2 rounded-full">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-medium text-gray-900 truncate">{{ $meeting['name'] }}</p>
                        <p class="text-xs text-gray-500">{{ \Carbon\Carbon::parse($meeting['start_time'])->format('M d, H:i') }} &middot; {{ $meeting['location'] }}</p>
                    </div>
                    <div class="text-right">
                        <span class="text-xs bg-green-50 text-green-700 px-2 py-1 rounded">Confirmed</span>
                    </div>
                </div>
            @empty
                <p class="text-sm text-gray-500 text-center py-4">No meetings scheduled.</p>
            @endforelse
        </div>
    </div>
</div>

{{-- Upcoming Bookings (from Calendly Webhook DB) --}}
<div class="mt-6 bg-white rounded-lg shadow">
    <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
        <h3 class="text-lg font-semibold text-gray-800">Upcoming Bookings</h3>
        <a href="{{ route('admin.bookings.index') }}"
           class="text-sm text-blue-600 hover:underline">
            View all &rarr;
        </a>
    </div>
    <div class="divide-y divide-gray-100">
        @forelse($upcomingBookings as $booking)
            <div class="flex items-center gap-4 px-6 py-4 hover:bg-gray-50 transition-colors">
                <div class="flex-shrink-0 w-10 h-10 rounded-full bg-blue-100 text-blue-600 flex items-center justify-center">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                    </svg>
                </div>
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-semibold text-gray-900 truncate">{{ $booking->client_name ?? 'Unknown' }}</p>
                    <p class="text-xs text-gray-500">
                        {{ $booking->starts_at ? $booking->starts_at->format('D d M, H:i') : 'TBC' }}
                        @if($booking->event_type_name) &middot; {{ $booking->event_type_name }} @endif
                    </p>
                </div>
                <div class="flex-shrink-0">
                    @if($booking->join_url)
                        <a href="{{ $booking->join_url }}" target="_blank"
                           class="inline-flex items-center gap-1 bg-blue-600 hover:bg-blue-700 text-white text-xs font-semibold px-3 py-1.5 rounded-lg transition-colors">
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M15 10l4.553-2.069A1 1 0 0121 8.82v6.36a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                            </svg>
                            Join
                        </a>
                    @else
                        <span class="text-xs text-gray-400">No link</span>
                    @endif
                </div>
            </div>
        @empty
            <div class="flex flex-col items-center justify-center py-10 text-gray-400">
                <svg class="w-10 h-10 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                          d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                </svg>
                <p class="text-sm">No upcoming bookings.</p>
                <a href="/booking" target="_blank" class="mt-2 text-xs text-blue-500 hover:underline">Open booking page &rarr;</a>
            </div>
        @endforelse
    </div>
</div>
@endsection

@stack('scripts')
<script>
document.addEventListener("DOMContentLoaded", function() {
    const ctx = document.getElementById('ragChart').getContext('2d');
    new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: ['Red', 'Amber', 'Green'],
            datasets: [{
                data: {{ json_encode($ragDistribution) }},
                backgroundColor: ['#f87171', '#fbbf24', '#34d399'],
                borderWidth: 0
            }]
        },
        options: {
            responsive: true,
            cutout: '70%',
            plugins: {
                legend: { position: 'bottom' }
            }
        }
    });
});
</script>
