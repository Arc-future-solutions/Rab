@extends('admin.layouts.app')

@section('header', 'Bookings')

@section('content')

{{-- Summary Cards --}}
<div class="grid grid-cols-1 sm:grid-cols-3 gap-6 mb-8">
    <div class="bg-white rounded-2xl shadow-xl border border-gray-200 p-6 flex items-center gap-5 transition-transform hover:scale-[1.02]">
        <div class="bg-blue-600/10 text-blue-600 rounded-2xl p-4 ring-1 ring-blue-500/20">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                      d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
            </svg>
        </div>
        <div>
            <div class="text-[10px] font-black text-slate-400 uppercase tracking-widest leading-none mb-1">Total Scheduled</div>
            <div class="text-3xl font-black text-slate-900 tracking-tight">{{ $bookings->total() }}</div>
        </div>
    </div>
    <div class="bg-white rounded-2xl shadow-xl border border-gray-200 p-6 flex items-center gap-5 transition-transform hover:scale-[1.02]">
        <div class="bg-green-600/10 text-green-600 rounded-2xl p-4 ring-1 ring-green-500/20">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
            </svg>
        </div>
        <div>
            <div class="text-[10px] font-black text-slate-400 uppercase tracking-widest leading-none mb-1">Active Calls</div>
            <div class="text-3xl font-black text-slate-900 tracking-tight">
                {{ \App\Models\Booking::where('status','active')->count() }}
            </div>
        </div>
    </div>
    <div class="bg-white rounded-2xl shadow-xl border border-gray-200 p-6 flex items-center gap-5 transition-transform hover:scale-[1.02]">
        <div class="bg-indigo-600/10 text-indigo-600 rounded-2xl p-4 ring-1 ring-indigo-500/20">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                      d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
        </div>
        <div>
            <div class="text-[10px] font-black text-slate-400 uppercase tracking-widest leading-none mb-1">Upcoming</div>
            <div class="text-3xl font-black text-slate-900 tracking-tight">
                {{ \App\Models\Booking::where('starts_at', '>', now())->count() }}
            </div>
        </div>
    </div>
</div>

{{-- Main Table Container --}}
<div class="bg-white shadow-xl border border-gray-200 rounded-2xl overflow-hidden">
    {{-- Search & Filters --}}
    <div class="px-8 py-6 border-b border-gray-100 flex justify-between items-center bg-gray-50/50">
        <form method="GET" action="{{ route('admin.bookings.index') }}" class="flex gap-4 items-center w-full">
            <div class="relative w-80">
                <input type="text" name="search" placeholder="Search by name or email..." value="{{ request('search') }}" 
                       class="w-full rounded-xl border-gray-200 shadow-sm pl-4 pr-10 py-2.5 border focus:ring-blue-500/20 focus:border-blue-500 transition-all text-sm">
            </div>

            <select name="status" class="rounded-xl border-gray-200 shadow-sm px-4 py-2.5 border focus:ring-blue-500/20 focus:border-blue-500 transition-all text-sm appearance-none bg-white min-w-[150px]" onchange="this.form.submit()">
                <option value="">All Statuses</option>
                <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Active</option>
                <option value="cancelled" {{ request('status') === 'cancelled' ? 'selected' : '' }}>Cancelled</option>
            </select>

            <button type="submit" class="bg-slate-900 text-white px-6 py-2.5 rounded-xl text-xs font-black uppercase tracking-widest hover:bg-slate-800 transition-colors shadow-lg shadow-slate-900/10">Filter</button>

            @if(request()->anyFilled(['search', 'status']))
                <a href="{{ route('admin.bookings.index') }}" class="text-[10px] font-black uppercase tracking-widest text-blue-600 hover:text-blue-800">Clear</a>
            @endif

            <div class="ml-auto">
                <a href="/booking" target="_blank" class="flex items-center gap-2 bg-blue-600/10 text-blue-600 border border-blue-200 px-6 py-2.5 rounded-xl text-[10px] font-black uppercase tracking-widest hover:bg-blue-600 hover:text-white transition-all">
                    Public Booking Page &rarr;
                </a>
            </div>
        </form>
    </div>

    {{-- Content --}}
    @if($bookings->isEmpty())
        <div class="flex flex-col items-center justify-center py-32 text-slate-300">
            <div class="w-20 h-20 bg-slate-50 rounded-full flex items-center justify-center mb-6 ring-4 ring-slate-100 italic font-black text-4xl">?</div>
            <p class="text-xl font-black text-slate-900 uppercase tracking-tight">No Bookings Found</p>
            <p class="text-sm mt-2 text-slate-400 font-medium">Wait for Calendly webhooks or check your connection.</p>
        </div>
    @else
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-100">
                <thead>
                    <tr class="bg-gray-50/30">
                        <th class="px-8 py-4 text-left text-[10px] font-black text-slate-400 uppercase tracking-widest">Client Contact</th>
                        <th class="px-8 py-4 text-left text-[10px] font-black text-slate-400 uppercase tracking-widest">Meeting Window</th>
                        <th class="px-8 py-4 text-left text-[10px] font-black text-slate-400 uppercase tracking-widest text-center">Event Type</th>
                        <th class="px-8 py-4 text-left text-[10px] font-black text-slate-400 uppercase tracking-widest text-center">Status</th>
                        <th class="px-8 py-4 text-right text-[10px] font-black text-slate-400 uppercase tracking-widest">Action</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-50">
                    @foreach($bookings as $booking)
                        <tr class="hover:bg-blue-50/50 transition-colors group">
                            <td class="px-8 py-5">
                                <div class="font-black text-slate-800 text-sm group-hover:text-blue-600 transition-colors">{{ $booking->client_name ?? 'Anonymous' }}</div>
                                <div class="text-[10px] font-bold text-slate-400 uppercase mt-0.5 tracking-tighter">{{ $booking->client_email ?? 'No email' }}</div>
                            </td>
                            <td class="px-8 py-5">
                                @if($booking->starts_at)
                                    <div class="text-sm font-black text-slate-800">{{ $booking->starts_at->format('D, d M') }}</div>
                                    <div class="text-[10px] font-bold text-slate-400 uppercase">{{ $booking->starts_at->format('H:i') }} @if($booking->ends_at) — {{ $booking->ends_at->format('H:i') }} @endif</div>
                                @else
                                    <span class="text-slate-200 italic font-medium">—</span>
                                @endif
                            </td>
                            <td class="px-8 py-5 text-center">
                                <span class="text-[10px] font-black uppercase tracking-widest text-slate-500 bg-slate-100 px-3 py-1 rounded-full border border-slate-200">{{ $booking->event_type_name ?? 'Rapid Consulting' }}</span>
                            </td>
                            <td class="px-8 py-5 text-center">
                                @if($booking->status === 'active')
                                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-[10px] font-black uppercase tracking-widest bg-green-500/10 text-green-600 border border-green-200">
                                        Active
                                    </span>
                                @else
                                    <div class="flex flex-col items-center">
                                        <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-[10px] font-black uppercase tracking-widest bg-red-500/10 text-red-600 border border-red-200">
                                            Cancelled
                                        </span>
                                        @if($booking->cancellation_reason)
                                            <span class="text-[9px] text-red-300 font-bold mt-1 text-center truncate max-w-[100px]">{{ $booking->cancellation_reason }}</span>
                                        @endif
                                    </div>
                                @endif
                            </td>
                            <td class="px-8 py-5 text-right">
                                @if($booking->join_url)
                                    <a href="{{ $booking->join_url }}" target="_blank"
                                       class="inline-flex items-center gap-2 bg-blue-600 text-white text-[10px] font-black uppercase tracking-widest px-4 py-2 rounded-xl transition-all shadow-lg shadow-blue-500/20 hover:bg-blue-500 hover:scale-105 active:scale-95">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                                                  d="M15 10l4.553-2.069A1 1 0 0121 8.82v6.36a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                        </svg>
                                        Join Meeting
                                    </a>
                                @else
                                    <span class="text-[10px] font-black text-slate-200 uppercase italic">Link Pending</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        @if($bookings->hasPages())
            <div class="px-8 py-6 border-t border-gray-100 bg-gray-50/20">
                {{ $bookings->links() }}
            </div>
        @endif
    @endif
</div>
@endsection
