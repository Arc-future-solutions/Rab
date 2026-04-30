@extends('layouts.public')

@section('title', 'Intelligence Review Results — RAB Consulting')

@section('content')
<section class="section">
    <div class="container" style="max-width: 950px;">

        <div class="section-header text-center mb-12">
            <h4 class="mb-4 text-center">Diagnostic Complete</h4>
            <h1 class="text-center">Intelligence Review Summary</h1>
            <p class="mt-4 text-slate-600 text-lg">Preliminary scores based on your objective assessments.</p>
        </div>

        {{-- Overall Score Dashboard --}}
        <div style="display: grid; grid-template-columns: 1fr 2fr; gap: 32px; margin-bottom: 60px;">
            <!-- Score Gauge Block -->
            <div style="background: white; border-radius: var(--radius-lg); border: 1px solid var(--slate-200); padding: 48px; text-align: center; display: flex; flex-direction: column; justify-content: center;">
                <span style="font-size: 0.7rem; font-weight: 700; color: var(--slate-400); text-transform: uppercase; letter-spacing: 0.1em; display: block; margin-bottom: 16px;">Overall Health Index</span>
                <div style="font-size: 5rem; font-weight: 800; line-height: 1; color: {{ $results['rag_status'] === 'Red' ? '#DC2626' : ($results['rag_status'] === 'Amber' ? '#D97706' : '#059669') }};">
                    {{ $results['overall_score'] }}<span style="font-size: 1.5rem; color: var(--slate-300); font-weight: 400;">/5</span>
                </div>
                <div style="margin-top: 24px;">
                    <span style="display: inline-block; padding: 6px 16px; border-radius: 999px; font-size: 0.75rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; background: {{ $results['rag_status'] === 'Red' ? '#FEF2F2' : ($results['rag_status'] === 'Amber' ? '#FFFBEB' : '#ECFDF5') }}; color: {{ $results['rag_status'] === 'Red' ? '#991B1B' : ($results['rag_status'] === 'Amber' ? '#92400E' : '#065F46') }}; border: 1px solid {{ $results['rag_status'] === 'Red' ? '#FEE2E2' : ($results['rag_status'] === 'Amber' ? '#FEF3C7' : '#D1FAE5') }};">
                        Status: {{ $results['rag_status'] }}
                    </span>
                </div>
            </div>

            <!-- Narrative / High Level Message -->
            <div style="background: var(--primary); color: white; border-radius: var(--radius-lg); padding: 48px; display: flex; flex-direction: column; justify-content: center;">
                <h3 style="color: white; margin-bottom: 24px; font-size: 1.5rem;">Executive Interpretation</h3>
                <p style="font-size: 1.15rem; line-height: 1.6; color: var(--slate-200); font-weight: 400;">
                    @if($results['rag_status'] === 'Red') 
                        Your review indicates material risk across several critical domains. Immediate intervention and a formal recovery plan are strongly recommended.
                    @elseif($results['rag_status'] === 'Amber') 
                        The review identifies partial delivery controls but significant exposure in specific pillars. Structured remediation should be prioritised.
                    @else 
                        Delivery appears broadly controlled based on the initial assessment, though ongoing objective oversight is advised to maintain stability.
                    @endif
                </p>
            </div>
        </div>

        {{-- Pillar Breakdown --}}
        <div style="background: white; border-radius: var(--radius-lg); border: 1px solid var(--slate-200); overflow: hidden; margin-bottom: 60px;">
            <div style="padding: 24px 40px; border-bottom: 1px solid var(--slate-100); background: var(--bg-soft);">
                <span style="font-size: 0.75rem; font-weight: 700; color: var(--slate-500); text-transform: uppercase; letter-spacing: 0.05em;">Pillar Identification & Health Breakdown</span>
            </div>
            <div style="padding: 0 40px;">
                <table style="width: 100%; border-collapse: collapse;">
                    @foreach($results['pillar_scores'] as $pillar)
                        <tr style="border-bottom: 1px solid var(--slate-50);">
                            <td style="padding: 24px 0; font-weight: 700; color: var(--slate-900);">{{ $pillar['name'] }}</td>
                            <td style="padding: 24px 0; text-align: center; width: 120px;">
                                <div style="font-size: 1.5rem; font-weight: 800; color: {{ $pillar['rag'] === 'Red' ? '#DC2626' : ($pillar['rag'] === 'Amber' ? '#D97706' : '#059669') }};">
                                    {{ $pillar['score'] }}
                                </div>
                            </td>
                            <td style="padding: 24px 0; text-align: right; width: 140px;">
                                <span style="display: inline-block; padding: 4px 12px; border-radius: 999px; font-size: 0.65rem; font-weight: 700; text-transform: uppercase; background: {{ $pillar['rag'] === 'Red' ? '#FEF2F2' : ($pillar['rag'] === 'Amber' ? '#FFFBEB' : '#ECFDF5') }}; color: {{ $pillar['rag'] === 'Red' ? '#991B1B' : ($pillar['rag'] === 'Amber' ? '#92400E' : '#065F46') }};">
                                    {{ $pillar['rag'] }}
                                </span>
                            </td>
                        </tr>
                    @endforeach
                </table>
            </div>
        </div>

        {{-- Call to Action Card --}}
        <div style="background: var(--bg-highlight); border: 1px solid var(--slate-200); border-radius: var(--radius-lg); padding: 80px 48px; text-align: center;">
            <h2 class="mb-4">Secure your delivery with expert insight</h2>
            <p class="mb-10 text-slate-600" style="max-width: 600px; margin-left: auto; margin-right: auto;">
                Book a 30-minute consultation call with our senior advisors to review these results in detail and define your path to remediation or performance enhancement.
            </p>
            <div style="display: flex; gap: 16px; justify-content: center;">
                <a href="/booking" class="btn-primary" style="padding: 16px 64px; font-size: 1.1rem;">Book Consultation Call</a>
            </div>
            <div class="mt-8">
                <a href="{{ route('rapid-consulting.dashboard') }}" style="font-size: 0.85rem; color: var(--slate-400); text-decoration: underline;">View Detailed Analysis & Charts</a>
            </div>
        </div>
    </div>
</section>
@endsection
