<?php

namespace App\Services;

class SnapshotReportService
{
    public function build(array $results, ?string $aiRecommendation = null, ?array $storedReport = null): array
    {
        $parsed = $this->extractStructuredReport($storedReport)
            ?? $this->extractStructuredReport($aiRecommendation);

        return [
            'intelligence_brief' => $parsed['intelligence_brief'] ?? $this->fallbackBrief($results),
            'insight_cards' => $this->normaliseCards(
                $parsed['insight_cards'] ?? [],
                $results
            ),
        ];
    }

    public function extractStructuredReport(mixed $value): ?array
    {
        if (is_array($value)) {
            return $this->isStructuredReport($value) ? $value : null;
        }

        $raw = trim((string) $value);
        if ($raw === '') {
            return null;
        }

        $candidate = $raw;

        if (preg_match('/```(?:json)?\s*(\{.*\})\s*```/s', $raw, $matches)) {
            $candidate = $matches[1];
        }

        $decoded = json_decode($candidate, true);

        if (! is_array($decoded)) {
            return null;
        }

        return $this->isStructuredReport($decoded) ? $decoded : null;
    }

    public function rawRecommendationFromPayload(array $payload): ?string
    {
        $raw = $payload['output'] ?? $payload['recommendation'] ?? null;

        if ($raw === null && isset($payload['snapshot_report_json'])) {
            return json_encode($payload['snapshot_report_json'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }

        if (is_string($raw)) {
            return trim($raw) !== '' ? $raw : null;
        }

        if (is_array($raw)) {
            return json_encode($raw, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }

        return null;
    }

    public function structuredReportFromPayload(array $payload): ?array
    {
        foreach (['snapshot_report_json', 'report', 'output', 'recommendation'] as $field) {
            if (! array_key_exists($field, $payload)) {
                continue;
            }

            $structured = $this->extractStructuredReport($payload[$field]);
            if ($structured !== null) {
                return $structured;
            }
        }

        return null;
    }

    private function isStructuredReport(array $decoded): bool
    {
        return isset($decoded['intelligence_brief']) || isset($decoded['insight_cards']);
    }

    private function fallbackBrief(array $results): string
    {
        $scores = collect($results['pillar_scores'] ?? []);
        $weakest = $scores->sortBy('score')->take(2)->values();
        $context = $results['delivery_stage'] ?? $results['service_context'] ?? null;
        $indexBits = collect($results['index_scores'] ?? [])
            ->take(4)
            ->map(function ($score, $name) {
                if ($name === 'CHI') {
                    return null;
                }

                return sprintf('%s %s', $name, number_format((float) $score, 1));
            })
            ->filter()
            ->implode(', ');

        $first = $weakest->get(0);
        $second = $weakest->get(1);
        $subject = strtolower($results['type'] ?? 'pir') === 'sir' ? 'service' : 'programme';

        $paragraphOne = sprintf(
            'This snapshot indicates the greatest exposure sits in %s (%s)%s%s. %s.',
            $first['name'] ?? 'the lowest-scoring area',
            isset($first['score']) ? number_format((float) $first['score'], 1) : 'n/a',
            $second ? sprintf(' and %s (%s)', $second['name'], number_format((float) $second['score'], 1)) : '',
            $context ? sprintf(' in the current context of %s', $context) : '',
            $indexBits !== '' ? 'The index profile currently reads ' . $indexBits : 'The current scoring pattern points to uneven control maturity'
        );

        $paragraphTwo = sprintf(
            'Within the next two weeks, the responsible lead should confirm the immediate control gaps in these areas, assign owners, and evidence the first corrective actions. A full %s Intelligence Review would establish the root causes with evidence and produce a prioritised action plan.',
            ucfirst($subject)
        );

        return $paragraphOne . "\n\n" . $paragraphTwo;
    }

    private function normaliseCards(array $cards, array $results): array
    {
        if ($cards !== []) {
            $normalised = collect($cards)
                ->filter(fn ($card) => is_array($card))
                ->map(fn ($card) => $this->normaliseCard($card, $results))
                ->filter()
                ->values()
                ->all();

            if ($normalised !== []) {
                return $normalised;
            }
        }

        return $this->fallbackCards($results);
    }

    private function normaliseCard(array $card, array $results): ?array
    {
        $score = isset($card['score']) ? (float) $card['score'] : null;
        $code = (string) ($card['pillar_code'] ?? $card['domain_code'] ?? '');
        $name = (string) ($card['pillar_name'] ?? $card['domain_name'] ?? '');

        if ($code === '' && $name === '') {
            return null;
        }

        $normalisedCode = $code !== '' ? $code : 'AREA';
        $normalisedName = $name !== '' ? $name : 'Priority Area';

        return [
            'pillar_code' => $normalisedCode,
            'pillar_name' => $normalisedName,
            'code' => $normalisedCode,
            'name' => $normalisedName,
            'score' => $score,
            'rag' => $card['rag'] ?? ($score !== null ? $this->ragForScore($score) : 'Amber'),
            'finding' => trim((string) ($card['finding'] ?? '')),
            'action' => trim((string) ($card['action'] ?? '')),
        ];
    }

    private function fallbackCards(array $results): array
    {
        $framework = strtolower((string) ($results['type'] ?? 'pir'));
        $scores = collect($results['pillar_scores'] ?? []);
        $cards = $scores->sortBy('score')->take(3)->map(function ($pillar, $code) use ($framework, $results) {
            $score = (float) ($pillar['score'] ?? 0);
            $context = $results['delivery_stage'] ?? $results['service_context'] ?? null;

            return [
                'code' => $code,
                'name' => $pillar['name'] ?? $code,
                'score' => $score,
                'rag' => $pillar['rag'] ?? $this->ragForScore($score),
                'finding' => $this->fallbackFinding($pillar['name'] ?? $code, $score, $framework, $context),
                'action' => $this->fallbackAction($pillar['name'] ?? $code, $framework),
            ];
        })->values();

        if (! empty($results['regulatory_context']) && isset($results['index_scores']['CHI']) && (float) $results['index_scores']['CHI'] < 3) {
            $cards = $cards->take(2)->values();
            $cards->push([
                'code' => 'COMPLIANCE',
                'name' => 'Compliance Risk Signal',
                'score' => (float) $results['index_scores']['CHI'],
                'rag' => $this->ragForScore((float) $results['index_scores']['CHI']),
                'finding' => sprintf(
                    'The %s context is showing weak compliance-health indicators. The snapshot suggests a control gap that needs evidence-led review before any assurance statement is relied upon.',
                    strtoupper((string) $results['regulatory_context'])
                ),
                'action' => 'Compliance lead to confirm the affected control area and commission a full evidence-backed review.',
            ]);
        }

        return $cards->all();
    }

    private function fallbackFinding(string $name, float $score, string $framework, ?string $context): string
    {
        $subject = $framework === 'sir' ? 'service' : 'programme';
        $timing = $context ? ' in the current context of ' . $context : '';

        if ($score < 2.5) {
            return sprintf(
                '%s is materially exposed%s. At %.1f, the current control position is not sufficient to protect the %s from avoidable failure or escalation.',
                $name,
                $timing,
                $score,
                $subject
            );
        }

        return sprintf(
            '%s is below the expected control threshold%s. At %.1f, the area requires near-term correction before risk compounds into a broader delivery issue.',
            $name,
            $timing,
            $score
        );
    }

    private function fallbackAction(string $name, string $framework): string
    {
        $map = $framework === 'sir'
            ? [
                'Service Governance' => 'Service owner to define the control owner, reporting cadence, and first remediation checkpoint.',
                'Incident' => 'Incident manager to review the last major incidents and agree the first corrective actions with named owners.',
                'Change' => 'Change manager to validate the failed-change pattern and reset approval and assurance controls.',
                'CMDB' => 'Tooling owner to validate CMDB accuracy and confirm where operational decisions rely on incomplete data.',
            ]
            : [
                'Governance' => 'Programme sponsor to confirm decision rights, escalation ownership, and the next control review date.',
                'Planning' => 'PMO lead to re-baseline the immediate plan and evidence where current milestones are no longer supportable.',
                'Data' => 'Data lead to confirm the first remediation backlog and the evidence required to close it.',
                'Change' => 'Change lead to define the adoption actions, owners, and reporting checkpoints for the next fortnight.',
            ];

        foreach ($map as $needle => $action) {
            if (str_contains($name, $needle)) {
                return $action;
            }
        }

        return $framework === 'sir'
            ? 'Service manager to assign an owner, define the first recovery deliverable, and evidence progress within two weeks.'
            : 'Programme lead to assign an owner, define the first recovery deliverable, and evidence progress within two weeks.';
    }

    private function ragForScore(float $score): string
    {
        if ($score < 2.5) {
            return 'Red';
        }

        if ($score < 3.8) {
            return 'Amber';
        }

        return 'Green';
    }
}
