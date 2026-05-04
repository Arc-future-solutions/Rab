@extends('layouts.public')

@section('title', 'Cookie Policy | RAB Consulting Services Ltd')

@section('content')
<section class="section">
    <div class="container" style="max-width: 900px;">
        <h1 class="font-manrope text-4xl md:text-5xl font-extrabold text-ink mb-4">Cookie Policy</h1>
        <p class="text-sm md:text-base text-slate-500 mb-3">Last updated: April 2026</p>
        <p class="text-sm md:text-base text-slate-500 mb-12">Published at: rabconsultingservices.com/cookies</p>

        <div class="space-y-10 text-slate-700 leading-8">
            <section>
                <h2 class="font-manrope text-2xl font-bold text-ink mb-4">3.1 What Cookies Are</h2>
                <p>Cookies are small text files stored on your device when you visit a website. They allow the website to recognise your device, remember your preferences, and function correctly. Similar technologies include local storage, session storage, and pixel tags; where we refer to "cookies" we include these unless stated otherwise.</p>
            </section>

            <section>
                <h2 class="font-manrope text-2xl font-bold text-ink mb-4">3.2 Our Cookie Approach</h2>
                <p>We use only the cookies necessary for our platform to function and to record your consent. We do not use third-party advertising cookies, social media tracking pixels, or cross-site tracking technologies. We do not sell data collected via cookies.</p>
            </section>

            <section>
                <h2 class="font-manrope text-2xl font-bold text-ink mb-4">3.3 Cookies We Use</h2>
                <div class="overflow-x-auto border border-slate-200 rounded-2xl">
                    <table class="min-w-full text-sm">
                        <thead class="bg-slate-50">
                            <tr>
                                <th class="px-5 py-4 text-left font-semibold text-ink">Cookie Name / Type</th>
                                <th class="px-5 py-4 text-left font-semibold text-ink">Purpose</th>
                                <th class="px-5 py-4 text-left font-semibold text-ink">Duration &amp; Controls</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200">
                            <tr class="align-top">
                                <td class="px-5 py-4 font-medium text-ink">Session cookie (PHPSESSID or Laravel session)</td>
                                <td class="px-5 py-4">Maintains your assessment session: stores your assessment ID, current question number, and scores as you progress. Without this cookie the diagnostic cannot function across multiple page loads.</td>
                                <td class="px-5 py-4">Session — deleted when you close your browser. Cannot be disabled without breaking the platform.</td>
                            </tr>
                            <tr class="align-top">
                                <td class="px-5 py-4 font-medium text-ink">CSRF token (XSRF-TOKEN)</td>
                                <td class="px-5 py-4">Security token that prevents cross-site request forgery attacks on form submissions. Required for all form submissions to function correctly.</td>
                                <td class="px-5 py-4">Session. Cannot be disabled — security requirement.</td>
                            </tr>
                            <tr class="align-top">
                                <td class="px-5 py-4 font-medium text-ink">Consent record (_rab_consent)</td>
                                <td class="px-5 py-4">Records that you have read and accepted the Privacy Policy and Terms. Prevents the consent notice re-appearing on every visit.</td>
                                <td class="px-5 py-4">12 months. Can be cleared by deleting cookies in your browser settings, which will cause the consent notice to reappear.</td>
                            </tr>
                            <tr class="align-top">
                                <td class="px-5 py-4 font-medium text-ink">Analytics (_rab_analytics — if enabled)</td>
                                <td class="px-5 py-4">Anonymous page view and journey data. Counts visits and pages viewed. No personal data. Used to understand how the platform is used and to improve it.</td>
                                <td class="px-5 py-4">12 months. Decline this cookie in the consent banner on first visit. Does not affect platform functionality.</td>
                            </tr>
                            <tr class="align-top">
                                <td class="px-5 py-4 font-medium text-ink">Third-party cookies</td>
                                <td class="px-5 py-4">We do not use third-party advertising, social media, retargeting, or behavioural tracking cookies.</td>
                                <td class="px-5 py-4">N/A</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </section>

            <section>
                <h2 class="font-manrope text-2xl font-bold text-ink mb-4">3.4 Managing Cookies</h2>
                <p>You can control cookies through your browser settings. Note that disabling strictly necessary cookies will prevent the diagnostic from functioning.</p>
                <div class="mt-6 overflow-x-auto border border-slate-200 rounded-2xl">
                    <table class="min-w-full text-sm">
                        <tbody class="divide-y divide-slate-200">
                            <tr>
                                <td class="px-5 py-4 font-medium text-ink w-1/3">Chrome</td>
                                <td class="px-5 py-4">Settings &gt; Privacy and Security &gt; Cookies and other site data</td>
                            </tr>
                            <tr>
                                <td class="px-5 py-4 font-medium text-ink">Firefox</td>
                                <td class="px-5 py-4">Options &gt; Privacy &amp; Security &gt; Cookies and Site Data</td>
                            </tr>
                            <tr>
                                <td class="px-5 py-4 font-medium text-ink">Safari</td>
                                <td class="px-5 py-4">Preferences &gt; Privacy &gt; Manage Website Data</td>
                            </tr>
                            <tr>
                                <td class="px-5 py-4 font-medium text-ink">Microsoft Edge</td>
                                <td class="px-5 py-4">Settings &gt; Cookies and site permissions &gt; Cookies and site data</td>
                            </tr>
                            <tr>
                                <td class="px-5 py-4 font-medium text-ink">iOS Safari</td>
                                <td class="px-5 py-4">Settings &gt; Safari &gt; Privacy &amp; Security</td>
                            </tr>
                            <tr>
                                <td class="px-5 py-4 font-medium text-ink">Android Chrome</td>
                                <td class="px-5 py-4">Settings &gt; Site settings &gt; Cookies</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </section>

            <section>
                <h2 class="font-manrope text-2xl font-bold text-ink mb-4">3.5 Do Not Track</h2>
                <p>Our website does not currently respond to Do Not Track (DNT) browser signals, as there is no universal standard for how DNT should be interpreted. Our cookie use is minimal and described in full above.</p>
            </section>
        </div>
    </div>
</section>
@endsection
