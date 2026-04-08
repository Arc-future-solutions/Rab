@extends('layouts.public')

@section('title', 'Select Assessment Type — Rapid Consulting Assessment')

@section('content')
<style>
    .selection-grid {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 32px;
        margin-top: 48px;
    }
    @media (max-width: 860px) {
        .selection-grid { grid-template-columns: 1fr; }
    }
    /* Specific override for assessment cards to avoid the pill-shaped button styles */
    .type-card {
        background: var(--surface);
        border: 2px solid var(--line);
        border-radius: 32px;
        padding: 40px;
        text-align: left;
        cursor: pointer;
        transition: all 0.3s ease;
        display: flex;
        flex-direction: column;
        height: 100%;
        position: relative;
    }
    .type-card:hover {
        border-color: var(--gold);
        transform: translateY(-4px);
        box-shadow: var(--shadow-gold);
    }
    .type-card.selected {
        border-color: var(--accent);
        background: var(--accent-dim);
        box-shadow: 0 0 0 4px rgba(37,99,235,0.1);
    }
    .type-card .icon-box {
        width: 64px;
        height: 64px;
        background: var(--bg-3);
        border-radius: 16px;
        display: flex;
        align-items: center;
        justify-content: center;
        margin-bottom: 24px;
        color: var(--gold);
    }
    .type-card h3 {
        font-family: var(--serif);
        font-size: 1.8rem;
        margin-bottom: 16px;
        line-height: 1.2;
    }
    .type-card p {
        font-size: 1rem;
        color: var(--text-2);
        line-height: 1.6;
        margin-bottom: 24px;
    }
    .type-card .cta-link {
        margin-top: auto;
        display: flex;
        align-items: center;
        font-weight: 700;
        color: var(--accent);
        text-transform: uppercase;
        font-size: 0.8rem;
        letter-spacing: 0.1em;
    }
</style>

<section class="section" x-data="{ selectedType: '' }">
    <div class="container" style="max-width: 1000px;">
        {{-- Step Indicator --}}
        <div class="mb-12">
            <div class="flex items-center justify-between relative">
                <div class="flex flex-col items-center z-10">
                    <div class="w-10 h-10 bg-green-500 text-white rounded-full flex items-center justify-center font-bold">✓</div>
                    <span class="text-xs mt-2 font-bold text-green-500 uppercase tracking-wider text-center">Your Details</span>
                </div>
                <div class="flex flex-col items-center z-10">
                    <div class="w-10 h-10 bg-blue-600 text-white rounded-full flex items-center justify-center font-bold">2</div>
                    <span class="text-xs mt-2 font-bold text-blue-600 uppercase tracking-wider text-center">Assessment Type</span>
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
                <div class="absolute top-5 left-0 w-full h-0.5 bg-gray-200 -z-0">
                    <div class="h-full bg-green-500" style="width: 33.33%;"></div>
                </div>
            </div>
        </div>

        <div class="section-head" style="display: block; text-align: center;">
            <div class="badge">Step 2 of 4</div>
            <h1 style="margin-top: 16px;">Which area shall we assess today?</h1>
            <p style="margin-top: 16px;">Choose the assessment that best matches your immediate focus area.</p>
        </div>

        <div class="selection-grid">
            {{-- PHI Card --}}
            <div @click="selectedType = 'phi'" :class="selectedType === 'phi' ? 'selected' : ''" class="type-card">
                <div class="icon-box">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 3.055A9.001 9.001 0 1020.945 13H11V3.055z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.488 9H15V3.512A9.025 9.025 0 0120.488 9z"></path></svg>
                </div>
                <h3>Programme Health Check</h3>
                <p>For transformation programmes, ERP implementations, or any complex delivery initiative. Assess governance, planning, delivery risk, and go-live readiness.</p>
                <div class="cta-link">
                    <span x-text="selectedType === 'phi' ? 'Selected' : 'Select PHI Assessment'"></span>
                    <svg class="w-4 h-4 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"></path></svg>
                </div>
            </div>

            {{-- ITSM Card --}}
            <div @click="selectedType = 'itsm'" :class="selectedType === 'itsm' ? 'selected' : ''" class="type-card">
                <div class="icon-box">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path></svg>
                </div>
                <h3>Service Health Check</h3>
                <p>For IT service operations, support functions, or managed services. Assess incident control, change management, service maturity, and operational resilience.</p>
                <div class="cta-link">
                    <span x-text="selectedType === 'itsm' ? 'Selected' : 'Select ITSM Assessment'"></span>
                    <svg class="w-4 h-4 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"></path></svg>
                </div>
            </div>

            <form x-ref="typeForm" action="{{ route('rapid-consulting.store-type') }}" method="POST" class="hidden">
                @csrf
                <input type="hidden" name="type" :value="selectedType">
            </form>
        </div>

        <div class="mt-12 text-center" x-show="selectedType !== ''" x-cloak>
            <button @click="$refs.typeForm.submit()" class="btn primary" style="padding: 20px 48px; font-size: 1.1rem;">
                Start My Assessment &rarr;
            </button>
        </div>
        
        <div class="mt-12 flex justify-center">
            <a href="{{ route('rapid-consulting.index') }}" style="color: var(--text-3); font-size: 0.8rem; font-weight: 500; display: inline-flex; align-items: center; gap: 8px;">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
                Go back to details
            </a>
        </div>
    </div>
</section>
@endsection
