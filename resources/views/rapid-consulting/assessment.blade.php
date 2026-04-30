@extends('layouts.public')

@php $hide_nav = true; @endphp

@section('title', 'Assessment — Rapid Consulting')

@section('content')
<section class="min-h-screen flex items-center justify-center bg-white p-4" x-data="assessmentHandler()">
    {{-- Full-Page Loading Overlay --}}
    <div x-show="submitting" 
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         class="fixed inset-0 z-[100] bg-slate-900/95 backdrop-blur-xl flex flex-col items-center justify-center text-center p-6" 
         x-cloak>
        <div class="relative w-32 h-32 mb-10">
            <div class="absolute inset-0 border-4 border-blue-500/20 rounded-full"></div>
            <div class="absolute inset-0 border-4 border-blue-500 border-t-transparent rounded-full animate-spin"></div>
            <div class="absolute inset-6 bg-gradient-to-br from-blue-600 to-indigo-700 rounded-2xl shadow-lg flex items-center justify-center">
                <svg class="w-10 h-10 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>
            </div>
        </div>
        <h2 class="text-4xl font-black text-white tracking-tighter mb-4">Rab getting your insights ready...</h2>
    </div>

    <div class="w-full max-w-[56rem] mx-auto">
        {{-- Progress Bar (Top) --}}
        <div class="mb-12 text-center">
            <h1 class="text-xl md:text-2xl font-black text-slate-900 uppercase tracking-tighter mb-4" x-text="currentPillar">
                {{ $flatQuestions[0]['pillarName'] ?? 'Intelligence Review' }}
            </h1>
            <div class="h-1.5 w-16 bg-blue-600 mx-auto rounded-full"></div>
        </div>
        <div class="mb-12">
            <div class="w-full bg-gray-100 h-2 rounded-full overflow-hidden">
                <div class="bg-blue-600 h-full transition-all duration-500" :style="`width: ${progress}%`"></div>
            </div>
            <div class="flex justify-between mt-2 text-[10px] font-black text-blue-800 uppercase tracking-widest">
                <span>Step 02 of 03: Diagnostic Progress</span>
                <span><span x-text="answeredCount">0</span> / <span x-text="totalQuestions">0</span></span>
            </div>
        </div>

        @php 
            $flatQuestions = [];
            // dd($questions);
            foreach($questions as $pillarCode => $pillar) {
                foreach($pillar['questions'] as $qCode => $qData) {
                    $flatQuestions[] = [
                        'pillarCode' => $pillarCode,
                        'pillarName' => $pillar['name'],
                        'qCode' => $qCode,
                        'text' => $qData['text'],
                        'type' => $qData['type'],
                        'label' => $qData['label'],
                        'anchors' => $qData['anchors'] ?? [],
                        'type3_cards' => $qData['type3_cards'] ?? [],
                    ];
                }
            }
        @endphp

        <form action="{{ route('rapid-consulting.submit') }}" method="POST" @submit="validateSubmission($event)">
            @csrf
            
            @foreach($flatQuestions as $index => $q)
                <div class="question-page" x-show="currentIndex === {{ $index }}" x-cloak x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 translate-x-4" x-transition:enter-end="opacity-100 translate-x-0">
                    <div class="text-center" x-data="{ score: '' }" x-init="$watch('score', (val) => { scores['{{ $q['qCode'] }}'] = val; updateCount(); })">
                        <div class="mb-6 flex flex-col items-center justify-center">
                            <span class="px-3 py-1 bg-blue-50 text-blue-700 text-xs font-black uppercase tracking-widest rounded-full border border-blue-100 shadow-sm mb-2">
                                Type {{ $q['type'] ?? 'N/A' }}
                            </span>
                            <span class="text-xs font-bold text-slate-500 uppercase tracking-widest">
                                {{ $q['label'] ?? 'Question' }}
                            </span>
                        </div>
                        <h2 class="text-2xl md:text-[1.75rem] font-black text-gray-900 mb-12 leading-tight tracking-tight">
                            {{ $q['text'] }}
                        </h2>
                        
                        <input type="hidden" name="{{ $q['qCode'] }}" x-model="score">
                        
                        @if(!empty($q['type3_cards']))
                            <div class="grid grid-cols-1 gap-3 max-w-3xl mx-auto mb-8 text-left mt-6">
                                @foreach($q['type3_cards'] as $card)
                                    <button type="button"
                                            @click="score = {{ $card['score'] }}"
                                            :class="score == {{ $card['score'] }} ? 'border-blue-600 bg-blue-50 ring-2 ring-blue-600 ring-offset-2 shadow-md' : 'border-slate-200 bg-white hover:border-blue-300 hover:bg-slate-50 hover:shadow-sm'"
                                            class="w-full p-4 rounded-xl border-2 transition-all duration-200 flex items-start gap-4 text-left group">
                                        <div :class="score == {{ $card['score'] }} ? 'bg-blue-600 text-white' : 'bg-slate-100 text-slate-500 group-hover:bg-blue-100 group-hover:text-blue-600'"
                                             class="flex-shrink-0 w-8 h-8 rounded-full flex items-center justify-center font-black text-sm mt-0.5 transition-colors">
                                            {{ $card['score'] }}
                                        </div>
                                        <div class="text-[13px] md:text-sm font-medium text-slate-700 leading-snug pt-1">
                                            {{ $card['response'] }}
                                        </div>
                                    </button>
                                @endforeach
                            </div>
                        @else
                            <div class="flex justify-center gap-2 md:gap-4 mb-6">
                                <template x-for="i in [1, 2, 3, 4, 5]">
                                    <button type="button" 
                                            @click="score = i" 
                                            :class="getButtonClass(i, score)"
                                            class="w-14 h-14 md:w-20 md:h-20 rounded-2xl border-2 font-bold text-xl md:text-2xl transition-all duration-200 transform active:scale-95 flex items-center justify-center">
                                        <span x-text="i"></span>
                                    </button>
                                </template>
                            </div>
                        @endif
                        @if(!empty($q['anchors']) && empty($q['type3_cards']))
                            <div class="mt-8 bg-slate-50 border border-slate-100 rounded-2xl p-4 mx-auto w-full mb-8">
                                <h4 class="text-[10px] font-black uppercase tracking-[0.15em] text-slate-400 mb-4 text-center">Scoring Anchors</h4>
                                <div class="grid grid-cols-1 md:grid-cols-3 gap-3 text-sm text-slate-700 font-medium">
                                    @if(isset($q['anchors']['1']))
                                        <div class="flex items-start gap-3 p-3 bg-white rounded-xl border border-slate-100 shadow-sm hover:shadow-md transition-shadow">
                                            <span class="flex-shrink-0 w-6 h-6 rounded-full bg-red-100 text-red-600 flex items-center justify-center font-bold text-xs mt-0.5">1</span>
                                            <span class="leading-snug text-xs text-left">{{ $q['anchors']['1'] }}</span>
                                        </div>
                                    @endif
                                    @if(isset($q['anchors']['3']))
                                        <div class="flex items-start gap-3 p-3 bg-white rounded-xl border border-slate-100 shadow-sm hover:shadow-md transition-shadow">
                                            <span class="flex-shrink-0 w-6 h-6 rounded-full bg-amber-100 text-amber-600 flex items-center justify-center font-bold text-xs mt-0.5">3</span>
                                            <span class="leading-snug text-xs text-left">{{ $q['anchors']['3'] }}</span>
                                        </div>
                                    @endif
                                    @if(isset($q['anchors']['5']))
                                        <div class="flex items-start gap-3 p-3 bg-white rounded-xl border border-slate-100 shadow-sm hover:shadow-md transition-shadow">
                                            <span class="flex-shrink-0 w-6 h-6 rounded-full bg-green-100 text-green-600 flex items-center justify-center font-bold text-xs mt-0.5">5</span>
                                            <span class="leading-snug text-xs text-left">{{ $q['anchors']['5'] }}</span>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        @else
                            <div class="flex justify-between max-w-md mx-auto text-[10px] font-bold uppercase tracking-widest text-gray-400 mb-10">
                        
                            </div>
                        @endif


                        {{-- Optional Notes/Evidence field --}}
                        <div class="mt-4 max-w-lg mx-auto" x-show="score !== ''" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 -translate-y-2" x-transition:enter-end="opacity-100 translate-y-0">
                            <textarea name="note_{{ $q['qCode'] }}" 
                                      class="w-full p-5 rounded-3xl border-2 border-transparent focus:ring-4 focus:ring-blue-500/10 focus:border-blue-500/30 text-sm font-semibold text-slate-700 transition-all duration-300 placeholder:text-slate-400 shadow-sm"
                                      
                                      style="border-color: #0027e952"
                                      placeholder="Add an optional note, evidence or 'P.S.' for this response..."
                                      rows="3"></textarea>
                            <p class="mt-3 text-[10px] font-black uppercase tracking-widest text-slate-400">Optional Context / Evidence</p>
                        </div>
                    </div>
                </div>
            @endforeach

            <div class="mt-16 flex justify-between items-center max-w-md mx-auto">
                <button type="button" 
                        @click="prev()" 
                        x-show="currentIndex > 0"
                        class="text-gray-400 font-bold hover:text-gray-900 transition flex items-center gap-2 text-sm uppercase tracking-widest">
                    &larr; Back
                </button>
                <div x-show="currentIndex === 0" class="flex-1"></div>


                <div x-show="currentIndex === totalQuestions - 1" class="flex justify-center mt-12">
                    <button type="submit" 
                            :disabled="!isCurrentAnswered()"
                            :class="isCurrentAnswered() ? 'bg-blue-600 text-white shadow-2xl shadow-blue-500/30 scale-105' : 'bg-gray-100 text-gray-400 pointer-events-none'"
                            class="px-12 py-5 rounded-3xl font-black uppercase tracking-tighter transition-all duration-500 hover:bg-blue-700 active:scale-95">
                        Continue to Final Step &rarr;
                    </button>
                </div>

                <div x-show="currentIndex < totalQuestions - 1" class="flex justify-end">
                    <button type="button" 
                            @click="next()" 
                            :disabled="!isCurrentAnswered()"
                            :class="isCurrentAnswered() ? 'bg-blue-900 text-white shadow-xl shadow-slate-200' : 'bg-gray-50 text-gray-300 pointer-events-none'"
                            class="px-10 py-4 rounded-2xl font-black uppercase tracking-tighter transition-all duration-300 hover:bg-black active:scale-95">
                        Next Question &rarr;
                    </button>
                </div>
            </div>
        </form>
    </div>
</section>
    </div>
</section>

@push('scripts')
<script>
function assessmentHandler() {
    return {
        totalQuestions: {{ count($flatQuestions) }},
        answeredCount: 0,
        currentIndex: 0,
        progress: 0,
        submitting: false,
        scores: {},
        pillarNames: [
            @foreach($flatQuestions as $q)
                "{!! addslashes($q['pillarName']) !!}",
            @endforeach
        ],
        get currentPillar() {
            return this.pillarNames[this.currentIndex];
        },
        
        init() {
            // Initialize scores object with qCodes
            @foreach($flatQuestions as $q)
                this.scores['{{ $q['qCode'] }}'] = '';
            @endforeach
        },

        next() {
            if (this.currentIndex < this.totalQuestions - 1 && this.isCurrentAnswered()) {
                this.currentIndex++;
                window.scrollTo({ top: 0, behavior: 'smooth' });
            }
        },

        prev() {
            if (this.currentIndex > 0) {
                this.currentIndex--;
                window.scrollTo({ top: 0, behavior: 'smooth' });
            }
        },

        isCurrentAnswered() {
            const qCodes = [
                @foreach($flatQuestions as $q)
                    '{{ $q['qCode'] }}',
                @endforeach
            ];
            return this.scores[qCodes[this.currentIndex]] !== '';
        },
        
        updateCount() {
            let count = 0;
            for (let key in this.scores) {
                if (this.scores[key] !== '') count++;
            }
            this.answeredCount = count;
            // Progress based on current index for better UX in paged mode
            this.progress = Math.round(((this.currentIndex + (this.isCurrentAnswered() ? 1 : 0)) / this.totalQuestions) * 100);
        },

        getButtonClass(i, score) {
            let base = 'transition-all duration-300 ';
            if (score === i) {
                switch(i) {
                    case 1: return base + 'bg-red-600 border-red-700 text-white shadow-lg shadow-red-200 scale-105';
                    case 2: return base + 'bg-orange-500 border-orange-600 text-white shadow-lg shadow-orange-100 scale-105';
                    case 3: return base + 'bg-amber-400 border-amber-500 text-white shadow-lg shadow-amber-100 scale-105';
                    case 4: return base + 'bg-lime-500 border-lime-600 text-white shadow-lg shadow-lime-100 scale-105';
                    case 5: return base + 'bg-green-600 border-green-700 text-white shadow-lg shadow-green-200 scale-105';
                }
            }
            return base + 'bg-white border-gray-100 text-gray-400 hover:border-gray-300 hover:text-gray-600';
        },

        getNoteStyle(score) {
            if (!score) return 'background: #f8fafc;';
            switch(score) {
                case 1: return 'background: #fef2f2; border-color: #fee2e2;';
                case 2: return 'background: #fff7ed; border-color: #ffedd5;';
                case 3: return 'background: #fffbeb; border-color: #fef3c7;';
                case 4: return 'background: #f7fee7; border-color: #ecfccb;';
                case 5: return 'background: #f0fdf4; border-color: #dcfce7;';
                default: return 'background: #f8fafc;';
            }
        },

        validateSubmission(e) {
            if (this.answeredCount < this.totalQuestions) {
                e.preventDefault();
                alert('Please answer all questions before submitting.');
            } else {
                this.submitting = true;
            }
        }
    }
}
</script>
@endpush
@endsection
