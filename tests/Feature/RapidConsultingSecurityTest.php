<?php

namespace Tests\Feature;

use App\Mail\HighPriorityDiagnosticAlert;
use App\Models\Lead;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
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

    public function test_personal_form_persists_internal_crm_lead_generates_internal_ai_report_and_sends_high_priority_alert(): void
    {
        Mail::fake();
        Config::set('services.anthropic.key', 'test-anthropic-key');
        Config::set('services.anthropic.api_key', 'test-anthropic-key');
        Config::set('services.anthropic.base_url', 'https://example.test/v1');

        $report = [
            'intelligence_brief' => "Paragraph one.\n\nParagraph two.",
            'insight_cards' => [[
                'pillar_code' => 'P1',
                'pillar_name' => 'P1 — Governance & Decision-Making',
                'score' => 2.2,
                'rag' => 'Red',
                'finding' => 'Governance decisions are not being closed.',
                'action' => 'Programme sponsor to reset governance actions this week.',
            ]],
        ];

        Http::fake(['*' => Http::response([
            'id' => 'msg_123',
            'content' => [[
                'type' => 'text',
                'text' => json_encode($report),
            ]],
        ])]);

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
        $this->assertIsArray($lead->snapshot_report_json);
        $this->assertSame('Paragraph one.' . "\n\n" . 'Paragraph two.', $lead->snapshot_report_json['intelligence_brief']);
        $leadId = $lead->id;

        Http::assertSent(function ($request) use ($leadId) {
            $payload = $request->data();
            $input = json_decode((string) str($payload['messages'][0]['content'])->after("\n\n"), true);

            return $request->url() === 'https://api.anthropic.com/v1/messages'
                && $request->hasHeader('x-api-key', 'test-anthropic-key')
                && $payload['system'] === config('ai.pir_snapshot')
                && ! isset($payload['output_config'])
                && ($input['assessment_id'] ?? null) === $leadId
                && ($input['framework'] ?? null) === 'PIR';
        });

        Mail::assertSent(HighPriorityDiagnosticAlert::class);
    }

    public function test_internal_crm_sales_status_is_score_based_for_medium_and_low_priority_leads(): void
    {
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

    public function test_results_dashboard_uses_ai_insight_order_without_legacy_priority_section(): void
    {
        $response = $this->withSession($this->personalFormSession([
            'snapshot_report_json' => [
                'intelligence_brief' => "Brief paragraph one.\n\nBrief paragraph two.",
                'insight_cards' => [
                    [
                        'pillar_code' => 'P1',
                        'pillar_name' => 'P1 — Governance & Decision-Making',
                        'score' => 2.2,
                        'rag' => 'Red',
                        'finding' => 'Governance decisions are not being closed.',
                        'action' => 'Programme sponsor to reset governance actions this week.',
                    ],
                    [
                        'pillar_code' => 'COMPLIANCE',
                        'pillar_name' => 'Compliance Risk Exposure',
                        'score' => 2.6,
                        'rag' => 'Amber',
                        'finding' => 'Compliance evidence remains incomplete.',
                        'action' => 'Compliance lead to confirm the evidence owner.',
                    ],
                ],
            ],
        ]))->get(route('rapid-consulting.dashboard'));

        $response->assertOk()
            ->assertDontSee('Priority Insights & Actions')
            ->assertDontSee('jspdf', false)
            ->assertDontSee('html2canvas', false)
            ->assertSee('Download Report as PDF')
            ->assertSee('Compliance', false);

        $html = $response->getContent();
        $expectedOrder = [
            'Score / 5.0',
            'Amber Status',
            'Pillar Score Heat Map',
            'Indices & Calculations',
            'Intelligence Visualisations',
            'Intelligence Brief',
            'Insight Cards',
            'Unlock Full Intelligence',
            'Book a Full Consultant-Led Review',
        ];

        $lastPosition = -1;
        foreach ($expectedOrder as $text) {
            $position = strpos($html, $text);
            $this->assertNotFalse($position, "{$text} was not rendered.");
            $this->assertGreaterThan($lastPosition, $position, "{$text} rendered out of order.");
            $lastPosition = $position;
        }
    }

    public function test_pdf_mode_renders_cover_page_before_report_content(): void
    {
        $results = $this->personalFormSession([
            'snapshot_report_json' => [
                'intelligence_brief' => "Brief paragraph one.\n\nBrief paragraph two.",
                'insight_cards' => [],
            ],
        ])['rc_results'];

        $results['lead_id'] = 31;
        $results['booking_token'] = 'test-token';
        $results['subject_name'] = 'Acme Recovery Programme';
        $results['assessment_date'] = '2026-05-22';
        $results['user'] = [
            'name' => 'Alex Sponsor',
            'email' => 'alex@example.com',
            'company' => 'Acme Ltd',
        ];
        $results['snapshot_report'] = $results['snapshot_report_json'];

        $html = view('rapid-consulting.dashboard', [
            'results' => $results,
            'pdfMode' => true,
            'hide_nav' => true,
            'logoDataUri' => 'data:image/png;base64,test',
        ])->render();

        $expectedOrder = [
            'Programme Intelligence Snapshot Report',
            'Acme Ltd',
            'Acme Recovery Programme',
            '22 May 2026',
            'Reda Boukhiar, Director, RAB Consulting Services',
            'This report is confidential and prepared exclusively for the named client organisation.',
            'Score / 5.0',
        ];

        $lastPosition = -1;
        foreach ($expectedOrder as $text) {
            $position = strpos($html, $text);
            $this->assertNotFalse($position, "{$text} was not rendered.");
            $this->assertGreaterThan($lastPosition, $position, "{$text} rendered out of order.");
            $lastPosition = $position;
        }
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
                'P4.F1' => 3,
                'P5.F1' => 3,
                'P6.F1' => 3,
                'P7.F1' => 3,
                'P8.F1' => 3,
                'P9.F1' => 3,
                'P10.F1' => 3,
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
