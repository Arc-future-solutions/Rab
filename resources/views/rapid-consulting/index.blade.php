@extends('layouts.public')

@section('title', 'Start Diagnostic — RAB Consulting')

@section('content')
<section class="section">
    <div class="container" style="max-width: 900px;">
        <div class="section-header text-center mb-12">
            <h4 class="mb-4 text-center">Step 01 of 04</h4>
            <h1 class="text-center">Initial Diagnostic Details</h1>
            <p class="mt-4 text-slate-600 text-lg" style="max-width: 700px; margin-left: auto; margin-right: auto;">
                Our 15-minute diagnostic helps identify high-level risks and determines if a full consultant-led review is required for your programme or service.
            </p>
        </div>

        <div class="mt-12" x-data="{ showForm: {{ $errors->any() ? 'true' : 'false' }} }">
            {{-- Validation Errors --}}
            @if($errors->any())
                <div class="mb-8 p-6 bg-red-50 border border-red-200 rounded-lg shadow-sm">
                    <div class="flex items-start gap-4">
                        <div class="w-10 h-10 bg-red-600 rounded-full flex items-center justify-center shrink-0">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.268 15c-.77 1.333.192 3 1.732 3z"></path></svg>
                        </div>
                        <div>
                            <h4 class="font-bold text-red-900">Please correct the following errors:</h4>
                            <ul class="mt-2 space-y-1">
                                @foreach($errors->all() as $error)
                                    <li class="text-red-700 text-sm flex items-center gap-2">
                                        <span class="w-1.5 h-1.5 bg-red-400 rounded-full"></span>
                                        {{ $error }}
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    </div>
                </div>
            @endif

            {{-- Welcome / Start Page --}}
            <div class="bg-white p-12 rounded-lg shadow-lg border border-slate-200 text-center">
                <div class="mb-8">
                    <div style="width: 80px; height: 80px; background: var(--bg-highlight); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto;">
                        <svg class="w-10 h-10 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="color: var(--primary);"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    </div>
                </div>
                
                <h2 class="mb-4">Ready to gain initial insight?</h2>
                <p class="mb-8 text-slate-600" style="max-width: 500px; margin-left: auto; margin-right: auto;">
                    This diagnostic takes approximately 15 minutes to complete and provides an initial overview of risks and delivery health.
                </p>

                <a href="{{ route('rapid-consulting.select-type') }}" 
                   class="btn-primary" style="padding: 16px 48px; font-size: 1.1rem; display: inline-block;">
                    Start 15-Minute Diagnostic
                </a>

                @if($hasPhi || $hasItsm)
                <p class="text-slate-400 text-xs mt-6 font-bold uppercase tracking-widest">
                    Or <a href="{{ route('rapid-consulting.dashboard') }}" class="underline hover:text-primary">View Previous Results</a>
                </p>
                @endif
            </div>

        </div>
    </div>
</section>
@endsection
