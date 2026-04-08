@extends('layouts.public')

@section('title', 'Assessment — Rapid Consulting')

@section('content')
<section class="section" x-data="assessmentHandler()">
    <div class="container" style="max-width: 900px;">
        {{-- Step Indicator --}}
        <div class="mb-12">
            <div class="flex items-center justify-between relative px-2 md:px-0">
                <div class="flex flex-col items-center z-10 transition duration-500 transform hover:scale-110">
                    <div class="w-10 h-10 bg-green-500 text-white rounded-full flex items-center justify-center font-bold shadow-lg shadow-green-100">✓</div>
                    <span class="text-xs mt-2 font-bold text-green-500 uppercase tracking-widest text-center hidden md:block">Your Details</span>
                </div>
                <div class="flex flex-col items-center z-10 transition duration-500 transform hover:scale-110">
                    <div class="w-10 h-10 bg-green-500 text-white rounded-full flex items-center justify-center font-bold shadow-lg shadow-green-100">✓</div>
                    <span class="text-xs mt-2 font-bold text-green-500 uppercase tracking-widest text-center hidden md:block">Assessment Type</span>
                </div>
                <div class="flex flex-col items-center z-10 transition duration-500 transform hover:scale-110">
                    <div class="w-10 h-10 bg-blue-600 text-white rounded-full flex items-center justify-center font-bold shadow-lg shadow-blue-200">3</div>
                    <span class="text-xs mt-2 font-bold text-blue-600 uppercase tracking-widest text-center">Questions</span>
                </div>
                <div class="flex flex-col items-center z-10 opacity-30">
                    <div class="w-10 h-10 bg-gray-200 text-gray-500 rounded-full flex items-center justify-center font-bold">4</div>
                    <span class="text-xs mt-2 uppercase tracking-widest text-center hidden md:block">Results</span>
                </div>
                {{-- Progress Line --}}
                <div class="absolute top-5 left-0 w-full h-0.5 bg-gray-200 -z-0">
                    <div class="h-full bg-green-500 transition-all duration-1000" style="width: 66.66%;"></div>
                </div>
            </div>
        </div>

        <div class="section-head text-center">
            <div class="badge">Step 3 of 4</div>
            <h1 class="text-4xl font-bold mt-4 uppercase tracking-tight">{{ strtoupper($type) }} Health Assessment</h1>
            <p class="mt-4 text-gray-500 text-lg">Please score each statement based on your current observation of the {{ $type === 'phi' ? 'programme' : 'service' }}.</p>
        </div>

        {{-- Progress Tooltip --}}
        <div class="mt-12 sticky top-5 z-20 bg-white/95 backdrop-blur-md px-6 py-4 rounded-full border shadow-2xl flex items-center justify-between transition duration-300">
            <div class="flex-1 mr-6">
                <div class="w-full bg-gray-100 h-3 rounded-full overflow-hidden">
                    <div class="bg-blue-600 h-full transition-all duration-500 shadow-[0_0_12px_rgba(37,99,235,0.4)]" :style="`width: ${progress}%` text-align: right"></div>
                </div>
            </div>
            <div class="text-sm font-black text-blue-800 whitespace-nowrap">
                <span x-text="answeredCount">0</span> / <span x-text="totalQuestions">0</span> ANSWERED
            </div>
        </div>

        <form action="{{ route('rapid-consulting.submit') }}" method="POST" @submit="validateSubmission($event)" class="mt-16 space-y-16 pb-20">
            @csrf
            
            @foreach($questions as $pillarCode => $pillar)
                <div class="pillar-section">
                    <div class="flex items-center gap-4 mb-8">
                        <div class="w-12 h-12 bg-gray-900 text-white rounded-xl flex items-center justify-center font-bold text-lg shadow-xl">{{ $pillarCode }}</div>
                        <h2 class="text-2xl font-black text-gray-900 uppercase tracking-wide">{{ $pillar['name'] }}</h2>
                    </div>
                    
                    <div class="space-y-10 pl-4 border-l-2 border-gray-100">
                        @foreach($pillar['questions'] as $qCode => $text)
                            <div class="question-container" x-data="{ score: '' }">
                                <p class="text-xl text-gray-800 font-medium mb-6 leading-snug">{{ $text }}</p>
                                <input type="hidden" name="{{ $qCode }}" x-model="score">
                                
                                <div class="grid grid-cols-5 gap-3 md:gap-4 max-w-2xl">
                                    <template x-for="i in [1, 2, 3, 4, 5]">
                                        <button type="button" 
                                                @click="score = i; updateCount()" 
                                                :class="getButtonClass(i, score)"
                                                style="border-radius: 16px !important;"
                                                class="group relative h-16 rounded-2xl border-2 font-bold text-lg transition-all duration-300 transform active:scale-90 flex flex-col items-center justify-center overflow-hidden">
                                            <span x-text="i"></span>
                                            {{-- Label on hover/select --}}
                                            <div class="absolute bottom-0 left-0 w-full h-1 bg-black/10 scale-x-0 group-hover:scale-x-100 transition duration-300"></div>
                                        </button>
                                    </template>
                                </div>
                                <div class="flex justify-between mt-3 px-2 text-[10px] font-black uppercase tracking-widest text-gray-400">
                                    <span>Critical / Not in place</span>
                                    <span>Strong / Fully embedded</span>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endforeach

            <div class="pt-12 border-t flex flex-col items-center">
                <p x-show="answeredCount < totalQuestions" class="text-amber-600 font-bold mb-4 bg-amber-50 px-6 py-2 rounded-full border border-amber-200 animate-pulse" x-cloak>
                    Please complete all <span x-text="totalQuestions - answeredCount"></span> remaining statements to submit.
                </p>
                <button type="submit" 
                        :disabled="answeredCount < totalQuestions"
                        :class="answeredCount === totalQuestions ? 'primary opacity-100 scale-110 shadow-2xl' : 'bg-gray-200 text-gray-400 pointer-events-none'"
                        class="btn w-full md:w-auto text-2xl py-6 px-16 rounded-3xl transition duration-500 font-black uppercase tracking-tighter">
                    Generate Results Dashboard &rarr;
                </button>
            </div>
        </form>
    </div>
</section>

@push('scripts')
<script>
function assessmentHandler() {
    return {
        totalQuestions: {{ collect($questions)->flatMap->questions->count() }},
        answeredCount: 0,
        progress: 0,
        
        updateCount() {
            const inputs = document.querySelectorAll('input[type="hidden"]');
            let count = 0;
            inputs.forEach(input => {
                if(input.value !== '') count++;
            });
            this.answeredCount = count;
            this.progress = Math.round((count / this.totalQuestions) * 100);
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

        validateSubmission(e) {
            if (this.answeredCount < this.totalQuestions) {
                e.preventDefault();
                alert('Please answer all questions before submitting.');
            }
        }
    }
}
</script>
@endpush
@endsection
