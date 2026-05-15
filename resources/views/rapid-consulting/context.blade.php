@extends('layouts.public')

@section('title', 'Assessment Context — RAB Consulting')

@section('content')
<section class="section">
    <div class="container" style="max-width: 900px;">
        <div class="section-header text-center mb-12">
            <h4 class="mb-4 text-center">Step 02 of 04</h4>
            <h1 class="text-center">
                {{ $type === 'pir' ? 'Select Delivery Stage' : 'Select Service Context' }}
            </h1>
            <p class="mt-4 text-slate-600 text-lg" style="max-width: 680px; margin-left: auto; margin-right: auto;">
                {{ $type === 'pir'
                    ? 'Choose the current programme stage before answering the diagnostic questions.'
                    : 'Choose the service context before answering the diagnostic questions.' }}
            </p>
        </div>

        @if($errors->any())
            <div class="mb-8 p-5 bg-red-50 border border-red-200 rounded-lg">
                @foreach($errors->all() as $error)
                    <p class="text-sm font-semibold text-red-700">{{ $error }}</p>
                @endforeach
            </div>
        @endif

        <form action="{{ route('rapid-consulting.store-context') }}" method="POST" class="bg-white p-8 md:p-10 rounded-lg shadow-lg border border-slate-200">
            @csrf

            @if($type === 'pir')
                <div class="grid-cards" style="grid-template-columns: repeat(3, 1fr); gap: 16px;">
                    @foreach([
                        'Mobilisation' => 'Set-up, governance and mobilisation.',
                        'Design' => 'Solution, process and operating model design.',
                        'Build' => 'Configuration, build and delivery execution.',
                        'Test' => 'Testing, UAT and readiness validation.',
                        'Cutover' => 'Cutover preparation and go-live control.',
                        'PostGoLive' => 'Hypercare, stabilisation and BAU transition.',
                    ] as $value => $description)
                        <label class="card-service cursor-pointer" style="padding: 20px;">
                            <input type="radio" name="delivery_stage" value="{{ $value }}" class="mr-2" {{ old('delivery_stage') === $value ? 'checked' : '' }}>
                            <strong>{{ $value === 'PostGoLive' ? 'Post Go-Live' : $value }}</strong>
                            <p class="mt-2 text-sm text-slate-500">{{ $description }}</p>
                        </label>
                    @endforeach
                </div>
            @else
                <div class="grid-cards" style="grid-template-columns: repeat(2, 1fr); gap: 16px;">
                    @foreach([
                        'NSI' => 'New service introduction or transition into BAU.',
                        'Established' => 'Stable service with normal improvement focus.',
                        'UnderPressure' => 'Service under operational pressure or instability.',
                        'Transformation' => 'Service absorbing structural change.',
                        'LegacyPreRetirement' => 'Legacy service approaching retirement or replacement.',
                    ] as $value => $description)
                        <label class="card-service cursor-pointer" style="padding: 20px;">
                            <input type="radio" name="service_context" value="{{ $value }}" class="mr-2" {{ old('service_context') === $value ? 'checked' : '' }}>
                            <strong>{{ preg_replace('/(?<!^)([A-Z])/', ' $1', $value) }}</strong>
                            <p class="mt-2 text-sm text-slate-500">{{ $description }}</p>
                        </label>
                    @endforeach
                </div>
            @endif

            <div class="mt-8">
                <label for="regulatory_context" class="block text-xs font-black uppercase tracking-widest text-slate-500 mb-2">Regulatory Context</label>
                <select id="regulatory_context" name="regulatory_context" class="w-full rounded-lg border border-slate-200 p-4 text-sm font-semibold">
                    <option value="">None / not applicable</option>
                    <option value="fca_uk" {{ old('regulatory_context') === 'fca_uk' ? 'selected' : '' }}>FCA UK</option>
                    <option value="dora_eu" {{ old('regulatory_context') === 'dora_eu' ? 'selected' : '' }}>DORA EU</option>
                    <option value="nhs_cqc" {{ old('regulatory_context') === 'nhs_cqc' ? 'selected' : '' }}>NHS / CQC</option>
                    <option value="public_sector" {{ old('regulatory_context') === 'public_sector' ? 'selected' : '' }}>Public sector</option>
                    <option value="gdpr_only" {{ old('regulatory_context') === 'gdpr_only' ? 'selected' : '' }}>GDPR only</option>
                </select>
            </div>

            <div class="mt-10 flex items-center justify-between">
                <a href="{{ route('rapid-consulting.select-type') }}" class="text-slate-400 text-sm font-bold uppercase tracking-widest">Back</a>
                <button type="submit" class="btn-primary" style="padding: 14px 42px;">Continue</button>
            </div>
        </form>
    </div>
</section>
@endsection
