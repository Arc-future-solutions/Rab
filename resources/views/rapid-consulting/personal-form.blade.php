@extends('layouts.public')

@section('title', 'Final Step — RAB Consulting')

@section('content')
<section class="section">
    <div class="container" style="max-width: 900px;">
        <div class="section-header text-center mb-12">
            <h4 class="mb-4 text-center">Step 03 of 03</h4>
            <h1 class="text-center">Confirm Your Details</h1>
            <p class="mt-4 text-slate-600 text-lg" style="max-width: 700px; margin-left: auto; margin-right: auto;">
                Almost there! Please provide your professional details to generate your tailored Intelligence Report and Dashboard access.
            </p>
        </div>

        <div class="mt-12">
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

            <form action="{{ route('rapid-consulting.process-personal-form') }}" method="POST" class="bg-white p-10 rounded-lg shadow-lg border border-slate-200">
                @csrf
                
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 32px; margin-bottom: 32px;">
                    <div>
                        <label class="block font-semibold text-slate-700 mb-2">Full Name</label>
                        <input type="text" name="name" required value="{{ old('name', auth()->user()->name ?? '') }}" placeholder="E.g. John Smith" style="width: 100%; padding: 12px; border: 1px solid var(--slate-200); border-radius: var(--radius);">
                    </div>
                    <div>
                        <label class="block font-semibold text-slate-700 mb-2">Work Email</label>
                        <input type="email" name="email" required value="{{ old('email', auth()->user()->email ?? '') }}" placeholder="john@company.com" style="width: 100%; padding: 12px; border: 1px solid var(--slate-200); border-radius: var(--radius);">
                    </div>
                </div>
                
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 32px; margin-bottom: 32px;">
                    <div>
                        <label class="block font-semibold text-slate-700 mb-2">Company</label>
                        <input type="text" name="company" required value="{{ old('company') }}" placeholder="Company Name" style="width: 100%; padding: 12px; border: 1px solid var(--slate-200); border-radius: var(--radius);">
                    </div>
                    <div>
                        <label class="block font-semibold text-slate-700 mb-2">Role</label>
                        <input type="text" name="job_title" required value="{{ old('job_title') }}" placeholder="E.g. Programme Director" style="width: 100%; padding: 12px; border: 1px solid var(--slate-200); border-radius: var(--radius);">
                    </div>
                </div>
                
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 32px; margin-bottom: 32px;">
                    <div>
                        <label class="block font-semibold text-slate-700 mb-2">Phone</label>
                        <input type="tel" name="phone" value="{{ old('phone') }}" placeholder="+44 000 000 000" style="width: 100%; padding: 12px; border: 1px solid var(--slate-200); border-radius: var(--radius);">
                    </div>
                    <div>
                        <label class="block font-semibold text-slate-700 mb-2">Company Sector</label>
                        <select name="industry" required style="width: 100%; padding: 12px; border: 1px solid var(--slate-200); border-radius: var(--radius); background-color: white;">
                            <option value="" disabled {{ old('industry') ? '' : 'selected' }}>Select Sector</option>
                            <option value="Technology" {{ old('industry') == 'Technology' ? 'selected' : '' }}>Technology</option>
                            <option value="Finance" {{ old('industry') == 'Finance' ? 'selected' : '' }}>Finance</option>
                            <option value="Healthcare" {{ old('industry') == 'Healthcare' ? 'selected' : '' }}>Healthcare</option>
                            <option value="Manufacturing" {{ old('industry') == 'Manufacturing' ? 'selected' : '' }}>Manufacturing</option>
                            <option value="Retail" {{ old('industry') == 'Retail' ? 'selected' : '' }}>Retail</option>
                            <option value="Energy" {{ old('industry') == 'Energy' ? 'selected' : '' }}>Energy</option>
                            <option value="Public Sector" {{ old('industry') == 'Public Sector' ? 'selected' : '' }}>Public Sector</option>
                            <option value="Education" {{ old('industry') == 'Education' ? 'selected' : '' }}>Education</option>
                            <option value="Other" {{ old('industry') == 'Other' ? 'selected' : '' }}>Other</option>
                        </select>
                    </div>
                </div>

                <div class="mb-8 p-5 border border-slate-200 rounded-lg bg-slate-50">
                    <label class="flex items-start gap-3 text-sm text-slate-700 leading-relaxed">
                        <input type="checkbox" name="consent_given" value="1" {{ old('consent_given') ? 'checked' : '' }} style="margin-top: 2px;">
                        <span>
                            I consent to RAB Consulting Services storing my details and diagnostic submission, generating my report, and sending the resulting assessment data to its CRM workflow as described in the <a href="/privacy" class="underline">Privacy Policy</a>.
                        </span>
                    </label>
                </div>

                <button type="submit" class="btn-primary" style="width: 100%; padding: 16px; font-size: 1.1rem;">
                    Generate My Intelligence Report →
                </button>
                
                <p class="mt-6 text-center text-xs text-slate-400">
                    By continuing, you agree to our <a href="/privacy" class="underline">Privacy Policy</a> and <a href="/terms" class="underline">Terms</a>.
                </p>
            </form>
        </div>
    </div>
</section>
@endsection
