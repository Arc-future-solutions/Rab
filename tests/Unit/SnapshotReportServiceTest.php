<?php

namespace Tests\Unit;

use App\Services\SnapshotReportService;
use Tests\TestCase;

class SnapshotReportServiceTest extends TestCase
{
    public function test_it_uses_structured_snapshot_json_when_available(): void
    {
        $service = new SnapshotReportService();

        $report = $service->build([
            'type' => 'pir',
            'pillar_scores' => [
                'P1' => ['name' => 'P1 — Governance & Decision-Making', 'score' => 2.2, 'rag' => 'Red'],
            ],
            'index_scores' => ['BRI' => 2.4, 'VRI' => 2.8],
        ], json_encode([
            'intelligence_brief' => "Paragraph one.\n\nParagraph two.",
            'insight_cards' => [[
                'pillar_code' => 'P1',
                'pillar_name' => 'P1 — Governance & Decision-Making',
                'score' => 2.2,
                'rag' => 'Red',
                'finding' => 'Governance decisions are not being closed.',
                'action' => 'Programme sponsor to reset governance actions this week.',
            ]],
        ]));

        $this->assertSame("Paragraph one.\n\nParagraph two.", $report['intelligence_brief']);
        $this->assertCount(1, $report['insight_cards']);
        $this->assertSame('P1', $report['insight_cards'][0]['code']);
        $this->assertSame('Governance decisions are not being closed.', $report['insight_cards'][0]['finding']);
    }

    public function test_it_prefers_stored_snapshot_report_json_over_ai_recommendation_string(): void
    {
        $service = new SnapshotReportService();

        $report = $service->build(
            [
                'type' => 'pir',
                'pillar_scores' => [
                    'P1' => ['name' => 'P1 — Governance & Decision-Making', 'score' => 2.2, 'rag' => 'Red'],
                ],
                'index_scores' => ['BRI' => 2.4],
            ],
            json_encode([
                'intelligence_brief' => 'Old recommendation',
                'insight_cards' => [],
            ]),
            [
                'intelligence_brief' => 'Stored report',
                'insight_cards' => [[
                    'pillar_code' => 'P1',
                    'finding' => 'Stored finding',
                    'action' => 'Stored action',
                ]],
            ]
        );

        $this->assertSame('Stored report', $report['intelligence_brief']);
        $this->assertSame('Stored finding', $report['insight_cards'][0]['finding']);
    }

    public function test_it_builds_fallback_compliance_card_when_structured_json_is_missing(): void
    {
        $service = new SnapshotReportService();

        $report = $service->build([
            'type' => 'sir',
            'service_context' => 'Transformation',
            'regulatory_context' => 'dora_eu',
            'pillar_scores' => [
                'D1' => ['name' => 'D1 — Service Governance & Ownership', 'score' => 2.3, 'rag' => 'Red'],
                'D2' => ['name' => 'D2 — Incident & Major Incident Management', 'score' => 2.6, 'rag' => 'Amber'],
                'D11' => ['name' => 'D11 — Service Tooling, CMDB & Knowledge Management', 'score' => 2.8, 'rag' => 'Amber'],
            ],
            'index_scores' => ['SSI' => 2.7, 'SMI' => 2.9, 'SIMI' => 2.4, 'BAURI' => 2.5, 'CHI' => 2.1],
        ]);

        $this->assertNotEmpty($report['intelligence_brief']);
        $this->assertCount(3, $report['insight_cards']);
        $this->assertSame('COMPLIANCE', $report['insight_cards'][2]['code']);
    }

    public function test_it_extracts_structured_report_and_raw_text_from_callback_payload(): void
    {
        $service = new SnapshotReportService();
        $payload = [
            'output' => json_encode([
                'intelligence_brief' => 'Brief',
                'insight_cards' => [],
            ]),
        ];

        $this->assertSame('Brief', $service->structuredReportFromPayload($payload)['intelligence_brief']);
        $this->assertSame($payload['output'], $service->rawRecommendationFromPayload($payload));
    }
}
