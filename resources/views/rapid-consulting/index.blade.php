@extends('layouts.public')

@section('title', 'Your Details — Rapid Consulting Assessment')

@section('content')
<section class="section">
    <div class="container" style="max-width: 800px;">
        {{-- Step Indicator --}}
        <div class="mb-12">
            <div class="flex items-center justify-between relative">
                <div class="flex flex-col items-center z-10">
                    <div class="w-10 h-10 bg-blue-600 text-white rounded-full flex items-center justify-center font-bold">1</div>
                    <span class="text-xs mt-2 font-bold text-blue-600 uppercase tracking-wider text-center">Your Details</span>
                </div>
                <div class="flex flex-col items-center z-10 opacity-40">
                    <div class="w-10 h-10 bg-gray-200 text-gray-500 rounded-full flex items-center justify-center font-bold">2</div>
                    <span class="text-xs mt-2 uppercase tracking-wider text-center">Assessment Type</span>
                </div>
                <div class="flex flex-col items-center z-10 opacity-40">
                    <div class="w-10 h-10 bg-gray-200 text-gray-500 rounded-full flex items-center justify-center font-bold">3</div>
                    <span class="text-xs mt-2 uppercase tracking-wider text-center">Questions</span>
                </div>
                <div class="flex flex-col items-center z-10 opacity-40">
                    <div class="w-10 h-10 bg-gray-200 text-gray-500 rounded-full flex items-center justify-center font-bold">4</div>
                    <span class="text-xs mt-2 uppercase tracking-wider text-center">Results</span>
                </div>
                {{-- Progress Line --}}
                <div class="absolute top-5 left-0 w-full h-0.5 bg-gray-200 -z-0"></div>
            </div>
        </div>

        <div class="section-head text-center" style="display: flex; flex-direction: column; align-items: center;">
            <div class="badge">Step 1 of 4</div>
            <h1 class="text-4xl font-bold mt-4">Programme a Service ITSM Health Check</h1>
            <p class="mt-4 text-gray-600 text-lg">Get an instant health score for your programme or service — free, in under 5 minutes.</p>

            @if(isset($has_previous) && $has_previous)
            <div class="mt-8 p-6 bg-blue-50 border border-blue-200 rounded-2xl flex flex-col md:flex-row items-center justify-between gap-6 max-w-2xl mx-auto shadow-sm">
                <div class="text-left">
                    <h3 class="font-bold text-blue-900 text-lg">We found your previous assessment!</h3>
                    <p class="text-blue-700 text-sm">You can view your current results dashboard immediately.</p>
                </div>
                <a href="{{ route('rapid-consulting.dashboard') }}" class="btn primary whitespace-nowrap">View My Dashboard &rarr;</a>
            </div>
            @endif
        </div>

        <div class="mt-12" x-data="{ showForm: false }">
            {{-- Big Start Button --}}
            <div x-show="!showForm" class="text-center py-12 bg-white rounded-2xl shadow-xl border border-gray-100">
                <div class="mb-6">
                    <svg class="w-20 h-20 mx-auto text-blue-100" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M9 2a1 1 0 000 2h2a1 1 0 100-2H9z" />
                        <path fill-rule="evenodd" d="M4 5a2 2 0 012-2 3 3 0 003 3h2a3 3 0 003-3 2 2 0 012 2v11a2 2 0 01-2 2H6a2 2 0 01-2-2V5zm3 4a1 1 0 000 2h.01a1 1 0 100-2H7zm3 0a1 1 0 000 2h3a1 1 0 100-2h-3zm-3 4a1 1 0 100 2h.01a1 1 0 100-2H7zm3 0a1 1 0 100 2h3a1 1 0 100-2h-3z" clip-rule="evenodd" />
                    </svg>
                </div>
                <button @click="showForm = true" 
                        @if(isset($has_previous) && $has_previous) disabled @endif
                        :class="{ 'opacity-50 cursor-not-allowed grayscale': {{ (isset($has_previous) && $has_previous) ? 'true' : 'false' }} }"
                        class="btn primary px-12 py-5 text-xl shadow-2xl hover:scale-105 transition-transform">
                    Start My Assessment &rarr;
                </button>
                @if(isset($has_previous) && $has_previous)
                <p class="text-amber-600 text-xs mt-4 font-bold uppercase tracking-widest italic">
                    Assessment Locked: Please view your existing dashboard above.
                </p>
                @else
                <p class="text-gray-400 text-sm mt-6 italic">No registration required. Takes ~5 minutes.</p>
                @endif
            </div>

            {{-- The Form (Hidden until button clicked) --}}
            <form x-show="showForm" 
                  x-transition:enter="transition ease-out duration-300"
                  x-transition:enter-start="opacity-0 transform scale-95"
                  x-transition:enter-end="opacity-100 transform scale-100"
                  action="{{ route('rapid-consulting.start') }}" method="POST" class="bg-white p-8 rounded-2xl shadow-xl border border-gray-100">
                @csrf
                {{-- Form Fields --}}
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Full Name <span class="text-red-500">*</span></label>
                        <input type="text" name="name" required placeholder="John Doe" class="w-full px-4 py-3 rounded-lg border border-gray-200 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition shadow-sm">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Job Title <span class="text-red-500">*</span></label>
                        <input type="text" name="job_title" required placeholder="Project Manager" class="w-full px-4 py-3 rounded-lg border border-gray-200 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition shadow-sm">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Company Name <span class="text-red-500">*</span></label>
                        <input type="text" name="company" required placeholder="ACME Corp" class="w-full px-4 py-3 rounded-lg border border-gray-200 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition shadow-sm">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Email Address <span class="text-red-500">*</span></label>
                        <input type="email" name="email" required placeholder="john@example.com" class="w-full px-4 py-3 rounded-lg border border-gray-200 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition shadow-sm">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Phone Number</label>
                        <input type="text" name="phone" placeholder="+1 (555) 000-0000" class="w-full px-4 py-3 rounded-lg border border-gray-200 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition shadow-sm">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Industry <span class="text-red-500">*</span></label>
                        <select name="industry" required class="w-full px-4 py-3 rounded-lg border border-gray-200 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition shadow-sm appearance-none bg-white">
                            <option value="">Select Industry</option>
                            <option value="Technology">Technology</option>
                            <option value="Finance">Finance</option>
                            <option value="Healthcare">Healthcare</option>
                            <option value="Retail">Retail</option>
                            <option value="Manufacturing">Manufacturing</option>
                            <option value="Public Sector">Public Sector</option>
                            <option value="Other">Other</option>
                        </select>
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Main concern or challenge <span class="text-red-500">*</span></label>
                        <textarea name="free_text_concern" required rows="3" placeholder="Briefly describe your main concern or challenge" class="w-full px-4 py-3 rounded-lg border border-gray-200 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition shadow-sm"></textarea>
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Current Confidence Level</label>
                        <div class="flex gap-4 mt-2" x-data="{ level: '' }">
                            <input type="hidden" name="confidence_level" :value="level">
                            <button type="button" @click="level = 'Low'" :class="level === 'Low' ? 'bg-red-500 text-white border-red-500' : 'bg-white text-gray-600 border-gray-200'" class="flex-1 py-2 px-4 rounded-lg border font-medium transition cursor-pointer">Low</button>
                            <button type="button" @click="level = 'Medium'" :class="level === 'Medium' ? 'bg-amber-500 text-white border-amber-500' : 'bg-white text-gray-600 border-gray-200'" class="flex-1 py-2 px-4 rounded-lg border font-medium transition cursor-pointer">Medium</button>
                            <button type="button" @click="level = 'High'" :class="level === 'High' ? 'bg-green-500 text-white border-green-500' : 'bg-white text-gray-600 border-gray-200'" class="flex-1 py-2 px-4 rounded-lg border font-medium transition cursor-pointer">High</button>
                        </div>
                    </div>
                </div>
                <div class="mt-8">
                    <button type="submit" class="w-full btn primary text-lg py-4 shadow-lg active:transform active:scale-[0.98]">Confirm Details & Continue &rarr;</button>
                </div>
                <p class="text-center text-xs text-gray-400 mt-6">By continuing, you agree to our terms and privacy policy. Your data is used only for this assessment.</p>
            </form>
        </div>
    </div>
</section>
@endsection
