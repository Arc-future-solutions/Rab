@extends('layouts.public')

@section('title', 'Your Scores — Rapid Consulting')

@section('content')
<section class="section">
    <div class="container" style="max-width: 900px;">
        {{-- Step Indicator --}}
        <div class="mb-12">
            <div class="flex items-center justify-between relative">
                <div class="flex flex-col items-center z-10">
                    <div class="w-10 h-10 bg-green-500 text-white rounded-full flex items-center justify-center font-bold">✓</div>
                    <span class="text-xs mt-2 font-bold text-green-500 uppercase tracking-wider text-center hidden md:block">Your Details</span>
                </div>
                <div class="flex flex-col items-center z-10">
                    <div class="w-10 h-10 bg-green-500 text-white rounded-full flex items-center justify-center font-bold">✓</div>
                    <span class="text-xs mt-2 font-bold text-green-500 uppercase tracking-wider text-center hidden md:block">Assessment Type</span>
                </div>
                <div class="flex flex-col items-center z-10">
                    <div class="w-10 h-10 bg-green-500 text-white rounded-full flex items-center justify-center font-bold">✓</div>
                    <span class="text-xs mt-2 font-bold text-green-500 uppercase tracking-wider text-center hidden md:block">Questions</span>
                </div>
                <div class="flex flex-col items-center z-10">
                    <div class="w-10 h-10 bg-blue-600 text-white rounded-full flex items-center justify-center font-bold">4</div>
                    <span class="text-xs mt-2 font-bold text-blue-600 uppercase tracking-wider text-center">Results</span>
                </div>
                {{-- Progress Line --}}
                <div class="absolute top-5 left-0 w-full h-0.5 bg-gray-200 -z-0">
                    <div class="h-full bg-green-500" style="width: 100%;"></div>
                </div>
            </div>
        </div>

        <div class="section-head text-center">
            <div class="badge">{{ strtoupper($results['type']) }} ASSESSMENT COMPLETE</div>
            <h1 class="text-4xl font-black mt-4 uppercase tracking-tighter">Your Health Assessment Summary</h1>
            <p class="mt-4 text-gray-500 text-lg">Thank you for completing the Rapid Consulting check. Your preliminary scores are ready.</p>
        </div>

        {{-- Overall Score Card --}}
        <div class="mt-12 bg-white rounded-3xl shadow-2xl p-8 md:p-12 border-2 border-gray-100 flex flex-col items-center text-center overflow-hidden relative">
            <div class="absolute top-0 right-0 w-32 h-32 opacity-10">
                <svg viewBox="0 0 100 100" class="fill-current @if($results['rag_status'] === 'Red') text-red-600 @elseif($results['rag_status'] === 'Amber') text-amber-500 @else text-green-500 @endif"><circle cx="50" cy="50" r="50"></circle></svg>
            </div>
            
            <div class="flex flex-col items-center">
                <div class="text-xs font-black text-gray-400 uppercase tracking-widest mb-2">Overall Score</div>
                <div class="text-7xl font-black @if($results['rag_status'] === 'Red') text-red-600 @elseif($results['rag_status'] === 'Amber') text-amber-500 @else text-green-500 @endif">
                    {{ $results['overall_score'] }}<span class="text-2xl text-gray-300">/5</span>
                </div>
                <div class="mt-4 px-8 py-2 rounded-full font-black text-xs uppercase tracking-[0.2em] shadow-xl text-white
                    @if($results['rag_status'] === 'Red') bg-red-600 @elseif($results['rag_status'] === 'Amber') bg-amber-500 @else bg-green-500 @endif">
                    STATUS: {{ $results['rag_status'] }}
                </div>
            </div>

            <div class="mt-10 max-w-2xl">
                <p class="text-xl font-medium text-gray-800 leading-relaxed italic">
                    "@if($results['rag_status'] === 'Red') 
                        Your assessment indicates material delivery risk. We recommend an immediate expert review.
                    @elseif($results['rag_status'] === 'Amber') 
                        Your assessment shows partial control with exposed risk areas. A deeper review is advisable.
                    @else 
                        Your assessment is broadly controlled. A deeper review may still reveal hidden risks.
                    @endif"
                </p>
            </div>
        </div>

        {{-- Index Section (ITSM ONLY) --}}
        @if($results['type'] === 'itsm')
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mt-12">
            @foreach($results['index_scores'] as $name => $score)
                <div class="bg-white p-6 rounded-2xl shadow-lg border border-gray-100 text-center">
                    <div class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1">{{ $name }}</div>
                    <div class="text-3xl font-black @if($score < 2.5) text-red-600 @elseif($score < 3.8) text-amber-500 @else text-green-500 @endif">{{ $score }}</div>
                    <div class="w-full bg-gray-100 h-1.5 mt-3 rounded-full overflow-hidden">
                        <div class="h-full rounded-full @if($score < 2.5) bg-red-600 @elseif($score < 3.8) bg-amber-500 @else bg-green-500 @endif" style="width: {{ ($score/5)*100 }}%"></div>
                    </div>
                </div>
            @endforeach
        </div>
        @endif

        {{-- Pillar Breakdown Table --}}
        <div class="mt-12 bg-white rounded-3xl shadow-xl border overflow-hidden">
            <div class="bg-gray-50 px-8 py-4 border-b flex items-center justify-between">
                <span class="text-sm font-black text-gray-500 uppercase tracking-widest">Pillar Breakdown</span>
                <span class="text-[10px] text-gray-400 font-bold uppercase tracking-widest">Calculated by deterministic scoring</span>
            </div>
            <table class="w-full text-left">
                <tbody>
                    @foreach($results['pillar_scores'] as $pillar)
                        <tr class="border-b last:border-0 hover:bg-gray-50 transition">
                            <td class="px-8 py-5 font-bold text-gray-900">{{ $pillar['name'] }}</td>
                            <td class="px-8 py-5 w-24">
                                <span class="font-black @if($pillar['rag'] === 'Red') text-red-600 @elseif($pillar['rag'] === 'Amber') text-amber-500 @else text-green-500 @endif">{{ $pillar['score'] }}</span>
                            </td>
                            <td class="px-8 py-5 w-32">
                                <div class="px-3 py-1 rounded-full text-[9px] font-black uppercase tracking-widest text-center
                                    @if($pillar['rag'] === 'Red') bg-red-50 text-red-700 border border-red-200 @elseif($pillar['rag'] === 'Amber') bg-amber-50 text-amber-700 border border-amber-200 @else bg-green-50 text-green-700 border border-green-200 @endif">
                                    {{ $pillar['rag'] }}
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        {{-- CTA Section --}}
        <div class="mt-16 flex flex-col items-center text-center space-y-6">
            <div class="bg-blue-50 border-2 border-blue-100 p-8 rounded-3xl max-w-2xl w-full shadow-lg">
                <h3 class="text-2xl font-black text-blue-900 uppercase tracking-tighter">Ready to secure your delivery?</h3>
                <p class="mt-2 text-blue-800 leading-relaxed font-medium">Book a 30-minute consultation call with our experts to review these results in detail and define your recovery plan.</p>
                <div class="mt-8">
                    <a href="{{ route('booking.index', ['name' => $results['user']['name'], 'email' => $results['user']['email']]) }}" 
                       class="btn primary text-xl py-5 px-16 shadow-2xl block w-full md:w-auto uppercase tracking-tighter font-black">
                        Book a Consultation Call &rarr;
                    </a>
                </div>
            </div>
            <div class="pt-4">
                <a href="{{ route('rapid-consulting.dashboard') }}" class="text-gray-400 hover:text-blue-600 text-sm font-black uppercase tracking-widest transition border-b-2 border-transparent hover:border-blue-600">
                    Skip for now — view results dashboard &raquo;
                </a>
            </div>
        </div>
    </div>
</section>
@endsection
