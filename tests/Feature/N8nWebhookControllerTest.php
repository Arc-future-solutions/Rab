<?php

namespace Tests\Feature;

use App\Models\Assessment;
use App\Models\Client;
use App\Models\Lead;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class N8nWebhookControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_callback_stores_structured_snapshot_report_object_and_raw_output(): void
    {
        $lead = $this->lead();

        $response = $this->post(route('rapid-consulting.webhook.receive'), [
            'lead_id' => $lead->id,
            'output' => 'Raw snapshot output',
            'snapshot_report_json' => [
                'intelligence_brief' => "Paragraph one.\n\nParagraph two.",
                'insight_cards' => [[
                    'pillar_code' => 'P1',
                    'pillar_name' => 'P1 — Governance & Decision-Making',
                    'score' => 2.2,
                    'rag' => 'Red',
                    'finding' => 'Finding',
                    'action' => 'Action',
                ]],
            ],
        ]);

        $response->assertOk();
        $lead->refresh();

        $this->assertSame('Raw snapshot output', $lead->ai_recommendation);
        $this->assertSame('Paragraph one.' . "\n\n" . 'Paragraph two.', $lead->snapshot_report_json['intelligence_brief']);
    }

    public function test_callback_parses_structured_json_from_output_string(): void
    {
        $lead = $this->lead();
        $json = json_encode([
            'intelligence_brief' => "Paragraph one.\n\nParagraph two.",
            'insight_cards' => [[
                'pillar_code' => 'P1',
                'pillar_name' => 'P1 — Governance & Decision-Making',
                'score' => 2.2,
                'rag' => 'Red',
                'finding' => 'Finding',
                'action' => 'Action',
            ]],
        ]);

        $this->post(route('rapid-consulting.webhook.receive'), [
            'lead_id' => $lead->id,
            'output' => $json,
        ])->assertOk();

        $lead->refresh();

        $this->assertSame($json, $lead->ai_recommendation);
        $this->assertSame('Paragraph one.' . "\n\n" . 'Paragraph two.', $lead->snapshot_report_json['intelligence_brief']);
    }

    public function test_callback_accepts_legacy_recommendation_only_payload(): void
    {
        $lead = $this->lead();

        $this->post(route('rapid-consulting.webhook.receive'), [
            'lead_id' => $lead->id,
            'recommendation' => 'Legacy plain text recommendation',
        ])->assertOk();

        $lead->refresh();

        $this->assertSame('Legacy plain text recommendation', $lead->ai_recommendation);
        $this->assertNull($lead->snapshot_report_json);
    }

    public function test_callback_stores_full_assessment_report_payload(): void
    {
        $assessment = $this->assessment();
        $draft = [
            'cover_letter' => 'Formal transmittal',
            'executive_position' => 'Recoverable with intervention',
            'final_position' => 'Proceed with controlled recovery',
        ];

        $this->post(route('rapid-consulting.webhook.receive'), [
            'assessment_id' => $assessment->id,
            'report' => $draft,
            'top_5_risks' => ['Risk one'],
        ])->assertOk();

        $assessment->refresh();

        $this->assertSame($draft, $assessment->ai_draft_json);
        $this->assertSame(json_encode($draft), $assessment->ai_recommendation);
        $this->assertSame('["Risk one"]', $assessment->top_5_risks);
        $this->assertSame('completed', $assessment->status);
    }

    public function test_callback_parses_full_assessment_report_from_fenced_output_json(): void
    {
        $assessment = $this->assessment();
        $draft = [
            'cover_letter' => 'Formal transmittal',
            'executive_position' => 'Recoverable with intervention',
            'final_position' => 'Proceed with controlled recovery',
        ];

        $this->post(route('rapid-consulting.webhook.receive'), [
            'assessment_id' => $assessment->id,
            'output' => "```json\n" . json_encode($draft) . "\n```",
        ])->assertOk();

        $assessment->refresh();

        $this->assertSame($draft, $assessment->ai_draft_json);
        $this->assertSame("```json\n" . json_encode($draft) . "\n```", $assessment->ai_recommendation);
        $this->assertSame('completed', $assessment->status);
    }

    private function lead(): Lead
    {
        return Lead::create([
            'name' => 'Alex Sponsor',
            'company' => 'Acme Ltd',
            'email' => 'alex@example.com',
            'type' => 'PIR',
            'assessment_type' => 'PIR_SNAPSHOT',
            'overall_score' => 2.8,
            'rag_status' => 'Amber',
            'priority' => 'High',
            'lead_status' => 'Hot',
            'booking_status' => 'NotBooked',
            'consent_given' => true,
        ]);
    }

    private function assessment(): Assessment
    {
        $client = Client::create([
            'company_name' => 'Acme Ltd',
            'primary_contact' => 'Alex Sponsor',
        ]);

        return Assessment::create([
            'client_id' => $client->id,
            'name' => 'PIR Assessment',
            'type' => 'PIR',
            'report_tier' => 'Tier 2 Full',
            'overall_score' => 3.5,
            'rag_status' => 'Amber',
            'status' => 'in_progress',
        ]);
    }
}
