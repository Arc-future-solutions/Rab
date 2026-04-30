@extends('layouts.public')

@section('title', 'Select Assessment Type — RAB Consulting')

@section('content')
<section class="section">
    <div class="container" style="max-width: 900px;" x-data="{ selectedType: '' }">
        {{-- Step Indicator --}}
        <div class="mb-16">
            <div style="display: flex; align-items: center; justify-content: space-between; position: relative;">
                <div style="display: flex; flex-direction: column; align-items: center; z-index: 10;">
                    <div style="width: 40px; height: 40px; background: var(--primary); color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 700;">1</div>
                    <span style="font-size: 0.7rem; margin-top: 8px; font-weight: 700; color: var(--primary); text-transform: uppercase; letter-spacing: 0.05em;">Type</span>
                </div>
                <div style="display: flex; flex-direction: column; align-items: center; z-index: 10; opacity: 0.3;">
                    <div style="width: 40px; height: 40px; background: var(--slate-200); color: var(--slate-500); border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 700;">2</div>
                    <span style="font-size: 0.7rem; margin-top: 8px; font-weight: 700; color: var(--slate-500); text-transform: uppercase; letter-spacing: 0.05em;">Questions</span>
                </div>
                <div style="display: flex; flex-direction: column; align-items: center; z-index: 10; opacity: 0.3;">
                    <div style="width: 40px; height: 40px; background: var(--slate-200); color: var(--slate-500); border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 700;">3</div>
                    <span style="font-size: 0.7rem; margin-top: 8px; font-weight: 700; color: var(--slate-500); text-transform: uppercase; letter-spacing: 0.05em;">Details</span>
                </div>
                <div style="display: flex; flex-direction: column; align-items: center; z-index: 10; opacity: 0.3;">
                    <div style="width: 40px; height: 40px; background: var(--slate-200); color: var(--slate-500); border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 700;">4</div>
                    <span style="font-size: 0.7rem; margin-top: 8px; font-weight: 700; color: var(--slate-500); text-transform: uppercase; letter-spacing: 0.05em;">Report</span>
                </div>
                {{-- Progress Line --}}
                <div style="position: absolute; top: 20px; left: 0; width: 100%; height: 2px; background: var(--slate-100); z-index: 1;">
                    <div style="height: 100%; background: var(--primary); width: 25%;"></div>
                </div>
            </div>
        </div>

        <div class="section-header text-center mb-12">
            <h4 class="mb-4 text-center">Step 01 of 03</h4>
            <h1 class="text-center">Select Diagnostic Focus</h1>
            <p class="mt-4 text-slate-600 text-lg" style="max-width: 600px; margin-left: auto; margin-right: auto;">
                Choose the intelligence review that best aligns with your immediate operational or delivery focus.
            </p>
        </div>

        <div class="grid-cards" style="grid-template-columns: repeat(2, 1fr); gap: 32px;">
            {{-- PIR Card --}}
            <div @click="selectedType = 'pir'"
                 :style="selectedType === 'pir' ? 'border-color: var(--primary); background: var(--bg-highlight);' : ''"
                 class="card-service cursor-pointer hover:shadow-lg" 
                 style="transition: all 0.3s ease; display: flex; flex-direction: column;">
                <div style="width: 48px; height: 48px; background: white; border-radius: var(--radius); display: flex; align-items: center; justify-content: center; margin-bottom: 24px; border: 1px solid var(--slate-100);">
                    <svg style="width: 24px; height: 24px; color: var(--primary);" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M11 3.055A9.001 9.001 0 1020.945 13H11V3.055z"/><path d="M20.488 9H15V3.512A9.025 9.025 0 0120.488 9z"/></svg>
                </div>
                <h3>Programme Intelligence</h3>
                <p>Focus on transformation health, governance, delivery risk, and go-live readiness. Designed for complex programme environments.</p>
                <div style="margin-top: auto; padding-top: 24px;">
                    <span :style="selectedType === 'pir' ? 'color: var(--primary); font-weight: 700;' : ''" style="font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.1em; color: var(--slate-400);">
                        <span x-text="selectedType === 'pir' ? 'Focus Selected' : 'Select PIR Focus'"></span>
                    </span>
                </div>
            </div>

            {{-- SIR Card --}}
            <div @click="selectedType = 'sir'"
                 :style="selectedType === 'sir' ? 'border-color: var(--secondary); background: var(--bg-soft);' : ''"
                 class="card-service cursor-pointer hover:shadow-lg" 
                 style="transition: all 0.3s ease; display: flex; flex-direction: column;">
                <div style="width: 48px; height: 48px; background: white; border-radius: var(--radius); display: flex; align-items: center; justify-content: center; margin-bottom: 24px; border: 1px solid var(--slate-100);">
                    <svg style="width: 24px; height: 24px; color: var(--secondary);" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
                </div>
                <h3>Service Intelligence</h3>
                <p>Focus on operational stability, service management maturity, incident control, and resilience. Designed for operational support environments.</p>
                <div style="margin-top: auto; padding-top: 24px;">
                    <span :style="selectedType === 'sir' ? 'color: var(--secondary); font-weight: 700;' : ''" style="font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.1em; color: var(--slate-400);">
                        <span x-text="selectedType === 'sir' ? 'Focus Selected' : 'Select SIR Focus'"></span>
                    </span>
                </div>
            </div>

            <form x-ref="typeForm" action="{{ route('rapid-consulting.store-type') }}" method="POST" style="display: none;">
                @csrf
                <input type="hidden" name="type" :value="selectedType">
            </form>
        </div>

        <div class="mt-16 text-center" x-show="selectedType !== ''" x-cloak>
            <button @click="$refs.typeForm.submit()" class="btn-primary" style="padding: 16px 64px; font-size: 1.1rem;">
                Proceed to Diagnostic Questions →
            </button>
        </div>
        
        <div class="mt-12 text-center">
            <a href="{{ route('rapid-consulting.index') }}" style="color: var(--slate-400); font-size: 0.8rem; display: inline-flex; align-items: center; gap: 8px;">
                <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                Return to details
            </a>
        </div>
    </div>
</section>
@endsection
