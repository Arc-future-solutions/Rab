<?php

namespace Tests\Feature;

use App\Jobs\SendToN8nWebhook;
use App\Mail\HighPriorityDiagnosticAlert;
use App\Models\Lead;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class RapidConsultingSecurityTest extends TestCase
{
    use RefreshDatabase;

    public function test_personal_form_requires_explicit_consent(): void
    {
        Http::fake(['*' => Http::response(['output' => 'AI output'])]);

        $response = $this->withSession($this->personalFormSession())
            ->post(route('rapid-consulting.process-personal-form'), $this->personalFormData());

        $response->assertSessionHasErrors('consent_given');
        $this->assertDatabaseCount('leads', 0);
    }

    public function test_personal_form_persists_internal_crm_lead_dispatches_n8n_and_sends_high_priority_alert(): void
    {
        Queue::fake();
        Mail::fake();
        Http::fake(['*' => Http::response(['output' => 'AI output'])]);

        $response = $this->withSession($this->personalFormSession())
            ->post(route('rapid-consulting.process-personal-form'), $this->personalFormData([
                'consent_given' => '1',
            ]));

        $response->assertRedirect(route('rapid-consulting.results'));

        $lead = Lead::first();

        $this->assertNotNull($lead);
        $this->assertTrue($lead->consent_given);
        $this->assertNotNull($lead->consent_timestamp);
        $this->assertSame('High', $lead->priority);
        $this->assertSame('Hot', $lead->lead_status);
        $this->assertSame('NotBooked', $lead->booking_status);
        $this->assertSame('PIR_SNAPSHOT', $lead->assessment_type);
        $this->assertSame('CriticalAreaBelowThreshold', $lead->critical_flag);
        $this->assertSame('1.0', $lead->scoring_version);
        $this->assertCount(3, $lead->top_three_insight_areas_json);
        $this->assertSame(['P1.F1' => 'high', 'P2.F1' => 'medium', 'P3.F1' => 'low'], $lead->confidence_json);

        Http::assertSent(function ($request) {
            return str_contains($request->url(), 'n8n.srv1139767.hstgr.cloud')
                && $request->hasHeader('anthropic-beta', 'zdr-2024-10-23');
        });

        Queue::assertPushed(SendToN8nWebhook::class);

        Mail::assertSent(HighPriorityDiagnosticAlert::class);
    }

    public function test_internal_crm_sales_status_is_score_based_for_medium_and_low_priority_leads(): void
    {
        Queue::fake();
        Mail::fake();
        Http::fake(['*' => Http::response(['output' => 'AI output'])]);

        $mediumSession = $this->personalFormSession([
            'overall_score' => 3.2,
            'pillar_scores' => [
                'P1' => ['name' => 'P1', 'score' => 3.2, 'is_critical' => false],
                'P2' => ['name' => 'P2', 'score' => 3.3, 'is_critical' => false],
                'P3' => ['name' => 'P3', 'score' => 3.4, 'is_critical' => false],
            ],
        ]);

        $this->withSession($mediumSession)
            ->post(route('rapid-consulting.process-personal-form'), $this->personalFormData([
                'email' => 'medium@example.com',
                'consent_given' => '1',
            ]))
            ->assertRedirect(route('rapid-consulting.results'));

        $lowSession = $this->personalFormSession([
            'overall_score' => 3.8,
            'pillar_scores' => [
                'P1' => ['name' => 'P1', 'score' => 3.8, 'is_critical' => false],
                'P2' => ['name' => 'P2', 'score' => 3.9, 'is_critical' => false],
                'P3' => ['name' => 'P3', 'score' => 4.0, 'is_critical' => false],
            ],
        ]);

        $this->withSession($lowSession)
            ->post(route('rapid-consulting.process-personal-form'), $this->personalFormData([
                'email' => 'low@example.com',
                'consent_given' => '1',
            ]))
            ->assertRedirect(route('rapid-consulting.results'));

        $this->assertSame('Medium', Lead::where('email', 'medium@example.com')->value('priority'));
        $this->assertSame('Warm', Lead::where('email', 'medium@example.com')->value('lead_status'));
        $this->assertSame('Low', Lead::where('email', 'low@example.com')->value('priority'));
        $this->assertSame('Cold', Lead::where('email', 'low@example.com')->value('lead_status'));
    }

    public function test_personal_form_endpoint_is_rate_limited(): void
    {
        Queue::fake();
        Mail::fake();
        Http::fake(['*' => Http::response(['output' => 'AI output'])]);

        for ($attempt = 0; $attempt < 10; $attempt++) {
            $this->withServerVariables(['REMOTE_ADDR' => '203.0.113.10'])
                ->withSession($this->personalFormSession())
                ->post(route('rapid-consulting.process-personal-form'), $this->personalFormData([
                    'consent_given' => '1',
                ]))
                ->assertRedirect(route('rapid-consulting.results'));
        }

        $this->withServerVariables(['REMOTE_ADDR' => '203.0.113.10'])
            ->withSession($this->personalFormSession())
            ->post(route('rapid-consulting.process-personal-form'), $this->personalFormData([
                'consent_given' => '1',
            ]))
            ->assertStatus(429);
    }

    public function test_public_report_status_response_does_not_expose_question_ip_fields(): void
    {
        $response = $this->withSession([
            'rc_results' => [
                'overall_score' => 3.2,
                'rag_status' => 'Amber',
            ],
        ])->get(route('rapid-consulting.report-status'));

        $response->assertOk()
            ->assertJsonMissing(['hidden_risk' => true])
            ->assertJsonMissing(['score_anchors' => true]);
    }

    private function personalFormData(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Alex Sponsor',
            'job_title' => 'Programme Director',
            'company' => 'Acme Ltd',
            'industry' => 'Technology',
            'email' => 'alex@example.com',
            'phone' => '+44123456789',
        ], $overrides);
    }

    private function personalFormSession(array $resultOverrides = []): array
    {
        $results = array_replace_recursive([
            'overall_score' => 2.8,
            'rag_status' => 'Amber',
            'type' => 'pir',
            'pillar_scores' => [
                'P1' => [
                    'name' => 'P1 — Governance & Decision-Making',
                    'score' => 2.2,
                    'rag' => 'Red',
                    'is_critical' => true,
                ],
                'P2' => [
                    'name' => 'P2 — Planning, Stage Gates & Delivery Control',
                    'score' => 2.9,
                    'rag' => 'Amber',
                    'is_critical' => false,
                ],
                'P3' => [
                    'name' => 'P3 — Business Alignment, Value & Financial Control',
                    'score' => 3.6,
                    'rag' => 'Amber',
                    'is_critical' => false,
                ],
            ],
            'index_scores' => [
                'BRI' => 2.7,
                'VRI' => 2.4,
                'DMI' => 3.1,
                'RII' => 2.2,
                'CHI' => 2.6,
            ],
        ], $resultOverrides);

        return [
            'rc_type' => 'pir',
            'rc_answers' => [
                'P1.F1' => 2,
                'P2.F1' => 3,
                'P3.F1' => 4,
            ],
            'rc_confidence' => [
                'P1.F1' => 'high',
                'P2.F1' => 'medium',
                'P3.F1' => 'low',
            ],
            'rc_delivery_stage' => 'Build',
            'rc_regulatory_context' => 'gdpr_only',
            'rc_results' => $results,
        ];
    }
}
