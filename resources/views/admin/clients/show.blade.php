@extends('admin.layouts.app')

@section('header')
    <div class="flex items-center gap-4">
        <a href="{{ route('admin.clients.index') }}" class="text-gray-500 hover:text-gray-700">&larr; Back</a>
        <span>{{ $client->company_name }}</span>
    </div>
@endsection

@section('content')
<div class="bg-white shadow rounded-lg mb-6 p-6">
    <div class="flex items-start justify-between gap-4">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 flex-1">
            <div>
                <span class="block text-sm font-medium text-gray-500">Company Name</span>
                <span class="block mt-1 text-lg text-gray-900">{{ $client->company_name }}</span>
            </div>
            <div>
                <span class="block text-sm font-medium text-gray-500">Primary Contact</span>
                <span class="block mt-1 text-lg text-gray-900">{{ $client->primary_contact }}</span>
                <a href="mailto:{{ $client->email }}" class="text-blue-600 hover:underline">{{ $client->phone }}</a>
            </div>
            <div>
                <span class="block text-sm font-medium text-gray-500">Industry</span>
                <span class="block mt-1 text-lg text-gray-900">{{ $client->industry ?: 'N/A' }}</span>
            </div>
        </div>
        <div class="flex-shrink-0">
            <a href="{{ route('booking.index', ['name' => $client->primary_contact, 'email' => $client->email]) }}"
               target="_blank"
               class="inline-flex items-center gap-2 bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm font-semibold transition-colors shadow-sm">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                </svg>
                Book a Call
            </a>
        </div>
    </div>
</div>

<div x-data="{ tab: 'phi' }" class="bg-white shadow rounded-lg">
    <div class="border-b border-gray-200">
        <nav class="-mb-px flex space-x-8 px-6" aria-label="Tabs">
            <button @click="tab = 'phi'" :class="tab === 'phi' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'" class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">
                PHI Assessments
                <span class="ml-2 bg-gray-100 text-gray-900 py-0.5 px-2.5 rounded-full text-xs">{{ $phiAssessments->count() }}</span>
            </button>
            <button @click="tab = 'itsm'" :class="tab === 'itsm' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'" class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">
                ITSM Assessments
                <span class="ml-2 bg-gray-100 text-gray-900 py-0.5 px-2.5 rounded-full text-xs">{{ $itsmAssessments->count() }}</span>
            </button>
        </nav>
    </div>

    <div class="p-0">
        <!-- PHI Tab -->
        <div x-show="tab === 'phi'">
            @if($phiAssessments->isEmpty())
                <div class="p-6 text-center text-gray-500">No PHI Assessments found.</div>
            @else
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Name</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Score</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @foreach($phiAssessments as $assessment)
                            <tr class="hover:bg-gray-50 cursor-pointer" onclick="window.location='{{ route('admin.assessments.show', $assessment) }}'">
                                <td class="px-6 py-4 whitespace-nowrap font-medium text-blue-600">{{ $assessment->name }}</td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full 
                                        @if($assessment->rag_status === 'Green') bg-green-100 text-green-800 
                                        @elseif($assessment->rag_status === 'Amber') bg-amber-100 text-amber-800 
                                        @else bg-red-100 text-red-800 @endif">
                                        {{ number_format($assessment->overall_score, 1) }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 capitalize">{{ str_replace('_', ' ', $assessment->status) }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $assessment->created_at->format('M d, Y') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </div>

        <!-- ITSM Tab -->
        <div x-show="tab === 'itsm'" x-cloak>
            @if($itsmAssessments->isEmpty())
                <div class="p-6 text-center text-gray-500">No ITSM Assessments found.</div>
            @else
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Name</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Score</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @foreach($itsmAssessments as $assessment)
                            <tr class="hover:bg-gray-50 cursor-pointer" onclick="window.location='{{ route('admin.assessments.show', $assessment) }}'">
                                <td class="px-6 py-4 whitespace-nowrap font-medium text-blue-600">{{ $assessment->name }}</td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full 
                                        @if($assessment->rag_status === 'Green') bg-green-100 text-green-800 
                                        @elseif($assessment->rag_status === 'Amber') bg-amber-100 text-amber-800 
                                        @else bg-red-100 text-red-800 @endif">
                                        {{ number_format($assessment->overall_score, 1) }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 capitalize">{{ str_replace('_', ' ', $assessment->status) }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $assessment->created_at->format('M d, Y') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </div>
    </div>
</div>
@endsection
