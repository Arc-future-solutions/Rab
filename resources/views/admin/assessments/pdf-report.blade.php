@php
    $report = is_array($report ?? null) ? $report : [];
    $profile = $report['intelligence_profile'] ?? [];
    $riskRegister = $report['risk_register'] ?? [];
    $priorityPlan = $report['priority_plan'] ?? [];
    $dashboard = $report['intelligence_dashboard'] ?? [];
    $indices = $dashboard['indices'] ?? [];
    $scores = $assessment->pillarScores->sortBy('score')->values();
    $isPir = $meta['is_pir'];
    $isBriefing = $meta['is_briefing'];
    $appendix = is_array($appendix ?? null) ? $appendix : [];
    $appendixRows = $appendix['rows'] ?? [];
    $appendixIsTier2 = (bool) ($appendix['is_tier2'] ?? $isBriefing);
    $appendixNote = $appendix['methodology_note'] ?? '';
    $logo = $logo ?? '';
    $scoreValue = (float) $assessment->overall_score;
    $scorePct = max(0, min(100, round(($scoreValue / 5) * 100)));
    $scoreTone = function (float $score): array {
        if ($score >= 4) return ['label' => 'Controlled', 'color' => '#166534', 'bg' => '#DCFCE7'];
        if ($score >= 3) return ['label' => 'At Risk', 'color' => '#B45309', 'bg' => '#FEF3C7'];
        if ($score >= 2) return ['label' => 'Weak', 'color' => '#B91C1C', 'bg' => '#FEE2E2'];
        return ['label' => 'Critical Failure', 'color' => '#7B0000', 'bg' => '#FEE2E2'];
    };
    $dialTone = $scoreTone($scoreValue);
    $confidenceLegendRaw = $dashboard['confidence_legend'] ?? [
        ['label' => 'High', 'color' => '#166534', 'bg' => '#DCFCE7', 'definition' => 'Confirmed by documentary evidence and interview.'],
        ['label' => 'Medium', 'color' => '#B45309', 'bg' => '#FEF3C7', 'definition' => 'Supported by interview or partial evidence.'],
        ['label' => 'Low', 'color' => '#B91C1C', 'bg' => '#FEE2E2', 'definition' => 'Single-source, contradictory, or weakly evidenced.'],
    ];
    $confidenceLegend = [];
    $confidenceTone = [
        'high' => ['label' => 'High', 'color' => '#166534', 'bg' => '#DCFCE7'],
        'medium' => ['label' => 'Medium', 'color' => '#B45309', 'bg' => '#FEF3C7'],
        'low' => ['label' => 'Low', 'color' => '#B91C1C', 'bg' => '#FEE2E2'],
    ];

    if (is_array($confidenceLegendRaw)) {
        foreach ($confidenceTone as $key => $tone) {
            if (isset($confidenceLegendRaw[$key]) && is_string($confidenceLegendRaw[$key])) {
                $confidenceLegend[] = $tone + ['definition' => $confidenceLegendRaw[$key]];
            }
        }

        if ($confidenceLegend === []) {
            foreach ($confidenceLegendRaw as $row) {
                if (!is_array($row)) {
                    continue;
                }

                $label = (string) ($row['label'] ?? 'Confidence');
                $tone = $confidenceTone[strtolower($label)] ?? ['label' => $label, 'color' => '#475569', 'bg' => '#F1F5F9'];
                $confidenceLegend[] = [
                    'label' => $label,
                    'color' => $row['color'] ?? $tone['color'],
                    'bg' => $row['bg'] ?? $tone['bg'],
                    'definition' => (string) ($row['definition'] ?? ''),
                ];
            }
        }
    }
    $radarEntries = $scores->map(function ($score) use ($scoreTone) {
        $parts = explode(' — ', $score->name);
        $code = $parts[0] ?? $score->name;

        return [
            'code' => $code,
            'label' => $score->name,
            'score' => (float) $score->score,
            'tone' => $scoreTone((float) $score->score),
        ];
    })->values()->all();
    $barEntries = collect($radarEntries)->sortBy('score')->values()->all();
    $riskScaleLabel = function ($value): string {
        $normalised = strtolower(trim((string) $value));

        return match (true) {
            $normalised === 'h', str_starts_with($normalised, 'high') => 'High',
            $normalised === 'l', str_starts_with($normalised, 'low') => 'Low',
            default => 'Medium',
        };
    };
    $riskScaleCode = fn ($value): string => strtoupper(substr($riskScaleLabel($value), 0, 1));
    $riskMatrixBuckets = [];
    foreach ($riskRegister as $index => $risk) {
        $probability = $riskScaleCode($risk['probability'] ?? 'Medium');
        $impact = $riskScaleCode($risk['impact'] ?? 'Medium');
        $riskMatrixBuckets["{$probability}|{$impact}"][] = $index + 1;
    }
    $riskMatrixTone = function (string $probability, string $impact): string {
        if ($probability === 'H' && $impact === 'H') return 'risk-cell risk-cell-critical';
        if ($probability === 'H' || $impact === 'H') return 'risk-cell risk-cell-high';
        if ($probability === 'M' || $impact === 'M') return 'risk-cell risk-cell-medium';
        return 'risk-cell risk-cell-low';
    };

    $ragClass = function ($value) {
        $score = is_numeric($value) ? (float) $value : null;
        if ($score !== null) {
            if ($score >= 4) return 'rag-green';
            if ($score >= 3) return 'rag-amber';
            if ($score >= 2) return 'rag-red';
            return 'rag-dark-red';
        }

        $normalised = strtolower((string) $value);
        return match (true) {
            str_contains($normalised, 'green'), str_contains($normalised, 'controlled'), str_contains($normalised, 'low') => 'rag-green',
            str_contains($normalised, 'amber'), str_contains($normalised, 'medium'), str_contains($normalised, 'at risk') => 'rag-amber',
            str_contains($normalised, 'critical') => 'rag-dark-red',
            default => 'rag-red',
        };
    };

    $asText = function ($value) use (&$asText) {
        if (is_array($value)) {
            return implode(' ', array_filter(array_map($asText, $value)));
        }

        return trim((string) $value);
    };

    $rowsForHorizon = function ($items) {
        if (!is_array($items)) return [];
        return array_values($items);
    };

    $renderRadarSvg = function (array $entries) {
        if ($entries === []) {
            return '<div class="panel">No radar data available.</div>';
        }

        $count = count($entries);
        $cx = 180;
        $cy = 170;
        $radius = 104;
        $rings = '';
        $spokes = '';
        $labels = '';
        $areaPoints = [];

        for ($ring = 1; $ring <= 5; $ring++) {
            $points = [];
            for ($i = 0; $i < $count; $i++) {
                $angle = (-90 + (360 / $count) * $i) * (M_PI / 180);
                $ringRadius = $radius * ($ring / 5);
                $x = $cx + cos($angle) * $ringRadius;
                $y = $cy + sin($angle) * $ringRadius;
                $points[] = round($x, 2) . ',' . round($y, 2);
            }
            $rings .= '<polygon points="' . implode(' ', $points) . '" fill="none" stroke="#CBD5E1" stroke-width="1" />';
        }

        for ($i = 0; $i < $count; $i++) {
            $angle = (-90 + (360 / $count) * $i) * (M_PI / 180);
            $x = $cx + cos($angle) * $radius;
            $y = $cy + sin($angle) * $radius;
            $spokes .= '<line x1="' . $cx . '" y1="' . $cy . '" x2="' . round($x, 2) . '" y2="' . round($y, 2) . '" stroke="#E2E8F0" stroke-width="1" />';

            $labelRadius = $radius + 28;
            $lx = $cx + cos($angle) * $labelRadius;
            $ly = $cy + sin($angle) * $labelRadius;
            $labelAnchor = abs(cos($angle)) < 0.15 ? 'middle' : (cos($angle) > 0 ? 'start' : 'end');
            $labels .= '<text x="' . round($lx, 2) . '" y="' . round($ly, 2) . '" text-anchor="' . $labelAnchor . '" fill="#374151" font-size="9" font-weight="700">' . e($entries[$i]['code']) . '</text>';
        }

        foreach ($entries as $i => $entry) {
            $angle = (-90 + (360 / $count) * $i) * (M_PI / 180);
            $normalized = max(0.1, min(1, $entry['score'] / 5));
            $x = $cx + cos($angle) * $radius * $normalized;
            $y = $cy + sin($angle) * $radius * $normalized;
            $areaPoints[] = round($x, 2) . ',' . round($y, 2);
        }

        $poly = '<polygon points="' . implode(' ', $areaPoints) . '" fill="#2563EB" fill-opacity="0.16" stroke="#2563EB" stroke-width="2" />';
        $dots = '';
        foreach ($entries as $i => $entry) {
            $angle = (-90 + (360 / $count) * $i) * (M_PI / 180);
            $normalized = max(0.1, min(1, $entry['score'] / 5));
            $x = $cx + cos($angle) * $radius * $normalized;
            $y = $cy + sin($angle) * $radius * $normalized;
            $dots .= '<circle cx="' . round($x, 2) . '" cy="' . round($y, 2) . '" r="3.5" fill="#2563EB" stroke="#fff" stroke-width="1.5" />';
        }

        return '<svg viewBox="0 0 360 315" role="img" aria-label="Radar chart">' .
            '<rect x="0" y="0" width="360" height="315" fill="#fff" />' .
            $rings . $spokes . $poly . $dots . $labels .
            '</svg>';
    };

    $renderBarSvg = function (array $entries) {
        if ($entries === []) {
            return '<div class="panel">No bar chart data available.</div>';
        }

        $width = 460;
        $rowHeight = 26;
        $chartHeight = 40 + count($entries) * $rowHeight;
        $maxBarWidth = 260;
        $svg = '<svg viewBox="0 0 460 ' . $chartHeight . '" role="img" aria-label="Sorted horizontal bar chart">';
        $svg .= '<rect x="0" y="0" width="460" height="' . $chartHeight . '" fill="#fff" />';
        $svg .= '<line x1="140" y1="20" x2="140" y2="' . ($chartHeight - 16) . '" stroke="#E2E8F0" stroke-width="1" />';

        foreach ($entries as $i => $entry) {
            $y = 26 + ($i * $rowHeight);
            $barWidth = round(($entry['score'] / 5) * $maxBarWidth, 2);
            $tone = $entry['tone'];
            $svg .= '<text x="6" y="' . ($y + 10) . '" fill="#0F172A" font-size="10" font-weight="700">' . e($entry['code']) . '</text>';
            $svg .= '<rect x="140" y="' . ($y - 2) . '" rx="4" ry="4" width="' . $barWidth . '" height="16" fill="' . e($tone['color']) . '" />';
            $svg .= '<text x="' . (148 + $barWidth) . '" y="' . ($y + 10) . '" fill="#0F172A" font-size="10" font-weight="700">' . number_format($entry['score'], 1) . '</text>';
        }

        $svg .= '</svg>';

        return $svg;
    };
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>{{ $meta['report_label'] }} | {{ $meta['client'] }}</title>
    <style>
        @page { size: A4; margin: 10mm 10mm 24mm 10mm; }
        * { box-sizing: border-box; }
        body { margin: 0; font-family: Inter, Arial, sans-serif; color: #0F172A; font-size: 11pt; line-height: 1.45; background: #fff; }

        /* ── Cover ── */
        .cover { width: 100%; height: 277mm; padding: 40px; background: #1E3A8A; color: #fff; page-break-after: always; position: relative; overflow: hidden; }
        .cover-logo { width: 180px; filter: brightness(0) invert(1); }
        .cover-type { margin-top: 52px; font-size: 13pt; font-weight: 800; letter-spacing: 2px; text-transform: uppercase; }
        .cover-rule { height: 1px; background: #fff; margin-top: 20px; width: 100%; opacity: .9; }
        .cover-main { position: absolute; left: 40px; right: 40px; top: 38%; transform: translateY(-35%); text-align: center; }
        .cover-client { font-size: 42pt; line-height: 1.05; font-weight: 800; margin: 0 0 18px; }
        .cover-subject { font-size: 22pt; font-style: italic; margin-bottom: 16px; }
        .cover-context { font-size: 15pt; }
        .cover-bottom { position: absolute; left: 40px; right: 40px; bottom: 40px; display: grid; grid-template-columns: 1fr 1fr; gap: 28px; align-items: end; }
        .cover-tier { font-size: 13pt; margin-bottom: 8px; }
        .cover-date { font-size: 12pt; }
        .cover-confidential { font-size: 11pt; text-align: right; }
        .cover-stamp { margin-top: 10px; font-size: 9pt; text-align: right; opacity: .9; }

        /* ── Report pages ── */
        .report-page { page-break-after: always; position: relative; padding: 0; }
        .report-page:last-child { page-break-after: auto; }
        .report-page::before { content: "CONFIDENTIAL"; position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%) rotate(-45deg); font-family: Inter, Arial, sans-serif; font-size: 72pt; font-weight: 700; letter-spacing: 8px; color: rgba(30,58,138,0.05); white-space: nowrap; pointer-events: none; user-select: none; z-index: 0; }
        .report-page > * { position: relative; z-index: 1; }

        /* ── Page header ── */
        .page-header { display: grid; grid-template-columns: 1fr 1fr; gap: 24px; align-items: start; padding-bottom: 10px; border-bottom: .5pt solid #1E3A8A; margin-bottom: 20px; }
        .header-left { display: flex; gap: 12px; align-items: center; }
        .header-logo { width: 80px; }
        .header-type { font-size: 9pt; color: #1E3A8A; font-weight: 800; text-transform: uppercase; letter-spacing: 1px; }
        .header-right { text-align: right; }
        .header-client { font-size: 10pt; font-weight: 800; color: #0F172A; }
        .header-subject { font-size: 9pt; color: #374151; }
        .header-confidential { font-size: 8pt; color: #1E3A8A; font-weight: 800; letter-spacing: 2px; }

        /* ── Typography ── */
        h1 { font-size: 18pt; color: #1E3A8A; margin: 0 0 14px; }
        h2 { font-size: 14pt; color: #0F172A; margin: 0 0 8px; }
        p { margin: 0 0 10px; }

        /* ── Panels & cards (avoid breaking mid-element) ── */
        .panel { border: 1px solid #E5E7EB; background: #F8FAFC; padding: 14px; margin-bottom: 14px; page-break-inside: avoid; }
        .grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
        .grid-3 { display: grid; grid-template-columns: repeat(3, 1fr); gap: 10px; }
        .index-grid { display: grid; grid-template-columns: repeat(5, 1fr); gap: 8px; margin: 14px 0; }
        .index-card { border: 1px solid #CBD5E1; padding: 10px; min-height: 92px; background: #fff; page-break-inside: avoid; }
        .index-name { font-size: 8pt; color: #64748B; font-weight: 800; text-transform: uppercase; min-height: 24px; }
        .index-value { font-size: 24pt; font-weight: 800; color: #0F172A; line-height: 1.1; }
        .index-rag { display: inline-block; margin-top: 6px; padding: 3px 7px; border-radius: 999px; font-size: 7pt; font-weight: 800; text-transform: uppercase; letter-spacing: .5px; }
        .methodology-stamp, .chart-attribution { font-size: 7pt; color: #1E3A8A; margin-top: 7px; }
        .chart-attribution { color: #64748B; }

        /* ── Score dial ── */
        .score-dial-wrap { display: flex; align-items: center; justify-content: center; gap: 18px; flex-wrap: wrap; page-break-inside: avoid; }
        .score-dial {
            width: 200px;
            height: 200px;
            border-radius: 50%;
            padding: 12px;
            background: conic-gradient(#2563EB {{ $scorePct }}%, #E2E8F0 0);
            flex: 0 0 auto;
        }
        .score-dial-inner {
            width: 100%;
            height: 100%;
            border-radius: 50%;
            background: #fff;
            border: 1px solid #CBD5E1;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            text-align: center;
        }
        .score-dial-score { font-size: 56pt; line-height: 1; font-weight: 800; color: #0F172A; }
        .score-dial-label { margin-top: 4px; font-size: 10pt; font-weight: 800; text-transform: uppercase; letter-spacing: 1px; color: {{ $dialTone['color'] }}; }
        .score-dial-sub { margin-top: 4px; font-size: 8pt; color: #64748B; text-transform: uppercase; letter-spacing: .5px; }
        .score-legend { display: flex; gap: 6px; flex-wrap: wrap; justify-content: center; margin-top: 8px; }
        .score-legend span { display: inline-block; padding: 4px 7px; font-size: 7pt; font-weight: 800; text-transform: uppercase; border: 1px solid #E2E8F0; background: #fff; color: #334155; }

        /* ── Alerts ── */
        .alert-stack { display: grid; gap: 8px; }
        .alert-banner { border-left: 5px solid #B45309; background: #FEF3C7; color: #78350F; padding: 10px 12px; font-size: 9pt; font-weight: 700; page-break-inside: avoid; }
        .alert-banner-critical { border-left-color: #B91C1C; background: #FEE2E2; color: #7B0000; }
        .alert { padding: 9px 10px; margin-bottom: 7px; border-left: 4px solid #B45309; background: #FEF3C7; color: #78350F; font-weight: 700; page-break-inside: avoid; }
        .alert-critical { border-color: #B91C1C; background: #FEE2E2; color: #7B0000; }

        /* ── Confidence ── */
        .confidence-legend { display: grid; gap: 8px; margin: 10px 0 14px; }
        .confidence-row { display: grid; grid-template-columns: 70px 1fr; gap: 10px; align-items: start; border: 1px solid #E2E8F0; background: #fff; padding: 8px 10px; page-break-inside: avoid; }
        .confidence-badge { display: inline-block; min-width: 56px; text-align: center; padding: 4px 8px; border-radius: 999px; font-size: 7pt; font-weight: 800; text-transform: uppercase; letter-spacing: .5px; color: #fff; }
        .confidence-def { font-size: 9pt; color: #374151; }

        /* ── Charts ── */
        .chart-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; align-items: start; }
        .chart-card { border: 1px solid #E5E7EB; background: #fff; padding: 12px; page-break-inside: avoid; }
        .chart-card h2 { font-size: 12pt; margin-bottom: 10px; }
        .chart-shell { position: relative; width: 100%; min-height: 300px; }
        .chart-canvas { width: 100%; height: 300px; display: block; }
        .chart-fallback { width: 100%; }
        .chart-fallback.is-hidden { display: none; }

        /* ── Dashboard ── */
        .summary-table td:first-child { font-weight: 800; color: #1E3A8A; text-transform: uppercase; }
        .dashboard-lead { display: grid; grid-template-columns: 1.05fr .95fr; gap: 16px; align-items: start; }
        .dashboard-stats { display: flex; flex-direction: column; gap: 10px; }

        /* ── Risk matrix ── */
        .risk-matrix { margin-top: 12px; page-break-inside: avoid; }
        .risk-matrix h2 { font-size: 12pt; color: #1E3A8A; margin: 0 0 6px; }
        .risk-matrix-helper { font-size: 9pt; color: #475569; margin-bottom: 10px; }
        .risk-matrix-axis-title { font-size: 8pt; font-weight: 800; color: #334155; text-transform: uppercase; letter-spacing: .5px; margin-bottom: 5px; text-align: center; }
        .risk-matrix-grid { display: grid; grid-template-columns: 92px repeat(3, 1fr); grid-template-rows: 30px repeat(3, minmax(62px, auto)); gap: 4px; align-items: stretch; }
        .risk-matrix-axis { display: flex; align-items: center; justify-content: center; font-size: 8pt; font-weight: 800; color: #334155; background: #F1F5F9; border: 1px solid #CBD5E1; text-align: center; }
        .risk-matrix-row-label { display: flex; align-items: center; justify-content: center; font-size: 8pt; font-weight: 800; color: #334155; background: #F1F5F9; border: 1px solid #CBD5E1; text-align: center; }
        .risk-cell { position: relative; min-height: 62px; border: 1px solid #CBD5E1; padding: 8px; font-size: 8pt; font-weight: 800; overflow: hidden; display: flex; flex-wrap: wrap; align-content: flex-start; gap: 4px; }
        .risk-cell-low { background: #DCFCE7; color: #166534; }
        .risk-cell-medium { background: #FEF3C7; color: #B45309; }
        .risk-cell-high { background: #FEE2E2; color: #B91C1C; }
        .risk-cell-critical { background: #7B0000; color: #fff; }
        .risk-cell-point { display: inline-flex; align-items: center; justify-content: center; min-width: 20px; height: 20px; border-radius: 999px; background: rgba(255,255,255,0.95); color: #0F172A; border: 1px solid rgba(15,23,42,0.18); font-size: 8pt; font-weight: 800; padding: 0 6px; }
        .risk-cell-empty { color: rgba(15,23,42,0.35); font-weight: 700; }
        .risk-grid-caption { margin-top: 8px; font-size: 7pt; color: #64748B; }
        .risk-legend { margin-top: 10px; border-top: 1px solid #E2E8F0; padding-top: 8px; }
        .risk-legend-row { font-size: 9pt; margin-bottom: 4px; }
        .risk-legend-marker { display: inline-block; min-width: 18px; height: 18px; line-height: 18px; border-radius: 999px; background: #0F172A; color: #fff; text-align: center; font-size: 8pt; font-weight: 800; margin-right: 6px; }

        /* ── Tables ── */
        .compliance-table td:first-child { font-weight: 700; }
        table { width: 100%; border-collapse: collapse; margin: 10px 0 16px; }
        thead { display: table-header-group; }
        th { text-align: left; background: #1E3A8A; color: #fff; font-size: 8pt; padding: 7px; text-transform: uppercase; letter-spacing: .5px; }
        td { border-bottom: 1px solid #E5E7EB; padding: 7px; vertical-align: top; font-size: 9pt; }
        tr { page-break-inside: avoid; }
        .appendix-table { table-layout: fixed; }
        .appendix-table th { font-size: 7pt; padding: 6px; }
        .appendix-table td { font-size: 7.5pt; padding: 6px; word-break: break-word; }
        .appendix-table .appendix-code { font-weight: 800; color: #1E3A8A; }
        .appendix-intro { margin-bottom: 10px; }
        .appendix-note { font-size: 10pt; line-height: 1.7; }

        /* ── Heat map ── */
        .heat-map { display: grid; grid-template-columns: repeat(6, 1fr); gap: 6px; margin-bottom: 16px; page-break-inside: avoid; }
        .heat-cell { padding: 8px; font-size: 8pt; font-weight: 800; text-align: center; }
        .rag-green { background: #DCFCE7; color: #166534; }
        .rag-amber { background: #FEF3C7; color: #B45309; }
        .rag-red { background: #FEE2E2; color: #B91C1C; }
        .rag-dark-red { background: #7B0000; color: #fff; }

        /* ── Misc ── */
        .badge { display: inline-block; padding: 3px 6px; font-size: 7pt; font-weight: 800; text-transform: uppercase; }
        .evidence { font-size: 10pt; color: #374151; font-style: italic; }
        .risk-map { display: grid; grid-template-columns: repeat(3, 1fr); grid-template-rows: repeat(3, 30px); border: 1px solid #CBD5E1; margin-top: 10px; }
        .risk-map div { border: 1px solid #CBD5E1; text-align: center; font-size: 8pt; padding-top: 7px; }
        .appendix-label { font-weight: 800; color: #1E3A8A; }
    </style>
</head>
<body>
<section class="cover">
    <img src="{{ $logo }}" class="cover-logo" alt="RAB Consulting Services">
    <div class="cover-type">{{ $meta['report_type'] }}</div>
    <div class="cover-rule"></div>
    <div class="cover-main">
        <h1 class="cover-client">{{ $meta['client'] }}</h1>
        <div class="cover-subject">{{ $meta['subject'] }}</div>
        <div class="cover-context">{{ $meta['context'] ?: 'Assessment Context' }} · {{ $meta['date'] }}</div>
    </div>
    <div class="cover-bottom">
        <div>
            <div class="cover-tier">{{ $meta['report_label'] }}</div>
            <div class="cover-date">{{ $meta['date'] }}</div>
        </div>
        <div>
            <div class="cover-confidential">CONFIDENTIAL — Prepared exclusively for {{ $meta['client'] }}. Not for distribution.</div>
            <div class="cover-stamp">RAB Platform · Scoring Version {{ $meta['scoring_version'] }} · {{ $assessment->id }}</div>
        </div>
    </div>
</section>

{{-- Footer is rendered natively by Puppeteer via ReportPdfService::footerTemplate() --}}

@once
    @php
        $pageTemplate = function ($title, $slot) use ($logo, $meta) {
            return '<section class="report-page">'
                . '<header class="page-header"><div class="header-left">'
                . '<img src="' . e($logo) . '" class="header-logo" alt="RAB"><div class="header-type">' . e($meta['report_type']) . '</div></div>'
                . '<div class="header-right"><div class="header-client">' . e($meta['client']) . '</div><div class="header-subject">' . e($meta['subject']) . '</div><div class="header-confidential">CONFIDENTIAL</div></div></header>'
                . '<h1>' . e($title) . '</h1>'
                . $slot
                . '</section>';
        };
    @endphp
@endonce

{!! $pageTemplate('Transmittal Letter', '<p>' . nl2br(e($report['cover_letter'] ?? 'AI transmittal letter not available.')) . '</p>') !!}

@php
    $alerts = $dashboard['alert_flags'] ?? $report['intelligence_dashboard']['alert_flags'] ?? [];
    if (!is_array($alerts)) $alerts = [];
    $exec = '<div class="dashboard-lead">';
    $exec .= '<div class="score-dial-wrap"><div class="score-dial"><div class="score-dial-inner"><div class="score-dial-score">' . e(number_format($scoreValue, 1)) . '</div><div class="score-dial-label">' . e($dialTone['label']) . '</div><div class="score-dial-sub">RAG ' . e($assessment->rag_status) . ' · ' . e($assessment->type) . '</div></div></div><div class="score-legend"><span>Controlled</span><span>At Risk</span><span>Weak</span><span>Critical Failure</span></div></div>';
    $exec .= '<div><p>' . nl2br(e($report['executive_position'] ?? 'Executive position not available.')) . '</p><div class="alert-stack">';
    foreach ($alerts as $alert) {
        $class = str_contains(strtolower((string) $alert), 'critical') || str_contains(strtolower((string) $alert), 'compliance') ? ' alert-banner-critical' : '';
        $exec .= '<div class="alert-banner' . $class . '">' . e($asText($alert)) . '</div>';
    }
    $exec .= $alerts === [] ? '<div class="panel">No alert flags reported.</div>' : '';
    $exec .= '</div></div>';
@endphp
{!! $pageTemplate('Executive Intelligence Position', $exec) !!}

@php
    $dashboardHtml = '<div class="index-grid">';
    foreach (['bri' => 'BRI', 'vri' => 'VRI', 'dmi' => 'DMI', 'rii' => 'RII', 'chi' => 'CHI'] as $storedName => $displayName) {
        $item = $indices[$storedName] ?? $indices[$displayName] ?? null;
        if ($item === null) {
            continue;
        }

        $value = is_array($item) ? ($item['score'] ?? $item['value'] ?? '') : $item;
        $interpretation = is_array($item) ? ($item['interpretation'] ?? '') : '';
        $tone = is_numeric($value) ? $scoreTone((float) $value) : $scoreTone((float) ($assessment->overall_score ?? 0));
        $dashboardHtml .= '<div class="index-card"><div class="index-name">' . e($displayName) . '</div><div class="index-value">' . e($value) . '</div><div class="index-rag" style="color:' . e($tone['color']) . '; background:' . e($tone['bg']) . ';">' . e($tone['label']) . '</div><div class="evidence" style="font-style: normal; margin-top: 6px;">' . e($interpretation) . '</div><div class="methodology-stamp">RAB Proprietary Methodology™</div></div>';
    }
    $dashboardHtml .= '</div>';
    $dashboardHtml .= '<div class="confidence-legend">';
    foreach ($confidenceLegend as $row) {
        $dashboardHtml .= '<div class="confidence-row"><span class="confidence-badge" style="background:' . e($row['color']) . ';">' . e($row['label']) . '</span><div class="confidence-def">' . e($row['definition']) . '</div></div>';
    }
    $dashboardHtml .= '</div>';
    $dashboardHtml .= '<div class="chart-grid">';
    $dashboardHtml .= '<div class="chart-card"><h2>Radar Chart</h2><div class="chart-shell"><canvas id="pdfRadarChart" class="chart-canvas" width="460" height="300"></canvas><div id="pdfRadarFallback" class="chart-fallback">' . $renderRadarSvg($radarEntries) . '</div></div><div class="chart-attribution">© RAB Consulting Services Ltd. Proprietary methodology.</div></div>';
    $dashboardHtml .= '<div class="chart-card"><h2>Bar Chart</h2><div class="chart-shell"><canvas id="pdfBarChart" class="chart-canvas" width="460" height="300"></canvas><div id="pdfBarFallback" class="chart-fallback">' . $renderBarSvg($barEntries) . '</div></div><div class="chart-attribution">© RAB Consulting Services Ltd. Proprietary methodology.</div></div>';
    $dashboardHtml .= '</div>';
    $dashboardHtml .= '<div class="panel"><h2>30/60/90 Summary</h2><table class="summary-table"><tr><th>Horizon</th><th>Headline</th><th>Owner</th></tr>';
    foreach (['30_days', '60_days', '90_days'] as $horizon) {
        $items = $rowsForHorizon($priorityPlan[$horizon] ?? []);
        $first = $items[0] ?? [];
        $dashboardHtml .= '<tr><td>' . e(str_replace('_', ' ', $horizon)) . '</td><td>' . e($first['action_title'] ?? $first['action'] ?? '-') . '</td><td>' . e($first['owner'] ?? '-') . '</td></tr>';
    }
    $dashboardHtml .= '</table></div>';
@endphp
{!! $pageTemplate('Intelligence Dashboard', $dashboardHtml) !!}

@if($isPir && !$isBriefing)
    @php
        $reportingAccuracy = $report['reporting_accuracy_risk_finding'] ?? null;
        $reportingAccuracyHtml = '<div class="panel"><p>'
            . e($asText($reportingAccuracy) ?: 'No reporting accuracy risk finding recorded.')
            . '</p></div>';
    @endphp
    {!! $pageTemplate('Reporting Accuracy Risk Finding', $reportingAccuracyHtml) !!}
@endif

@if($isBriefing)
    @php
        $stakeholder = $report['stakeholder_intelligence'] ?? null;
        $stakeholderHtml = $stakeholder
            ? '<p>' . nl2br(e($asText($stakeholder['divergence_summary'] ?? ''))) . '</p><div class="panel"><strong>Governance implication:</strong> ' . e($asText($stakeholder['governance_implication'] ?? '')) . '</div>'
            : '<div class="panel">Stakeholder intelligence was not returned by the AI response.</div>';
    @endphp
    {!! $pageTemplate('Stakeholder Intelligence', $stakeholderHtml) !!}
@endif

@php
    $profileHtml = '<div class="heat-map">';
    foreach ($scores as $score) {
        $profileHtml .= '<div class="heat-cell ' . $ragClass($score->score) . '">' . e(explode(' — ', $score->name)[0]) . '<br>' . e(number_format((float) $score->score, 1)) . '</div>';
    }
    $profileHtml .= '</div>';
    foreach ($profile as $finding) {
        $confidence = strtoupper((string) ($finding['confidence'] ?? 'Medium'));
        $confidenceTone = match ($confidence) {
            'HIGH' => ['color' => '#166534', 'bg' => '#DCFCE7'],
            'LOW' => ['color' => '#B91C1C', 'bg' => '#FEE2E2'],
            default => ['color' => '#B45309', 'bg' => '#FEF3C7'],
        };
        $profileHtml .= '<div class="panel"><h2>' . e($finding['headline'] ?? $finding['pillar_name'] ?? $finding['domain_name'] ?? 'Finding') . '</h2>'
            . '<p><span class="confidence-badge" style="background:' . e($confidenceTone['bg']) . '; color:' . e($confidenceTone['color']) . ';">' . e($confidence) . '</span> Score: ' . e($finding['score'] ?? '-') . '</p>'
            . '<p class="evidence">' . e($asText($finding['evidence'] ?? 'Evidence not available.')) . '</p>'
            . '<p>' . e($asText($finding['business_impact'] ?? '')) . '</p>'
            . '<p><strong>Action:</strong> ' . e($asText($finding['action'] ?? '')) . '</p></div>';
    }
    if ($profile === []) $profileHtml .= '<div class="panel">No intelligence profile findings were returned.</div>';
@endphp
{!! $pageTemplate('Intelligence Briefing Profile', $profileHtml) !!}

@php
    $riskHtml = '<table><tr><th>#</th><th>Risk</th><th>Probability</th><th>Impact</th><th>Owner</th><th>Current Control</th><th>Action</th></tr>';
    foreach ($riskRegister as $index => $risk) {
        $riskHtml .= '<tr><td>' . ($index + 1) . '</td><td>' . e($risk['risk_title'] ?? '-') . '</td><td>' . e($riskScaleLabel($risk['probability'] ?? 'Medium')) . '</td><td>' . e($riskScaleLabel($risk['impact'] ?? 'Medium')) . '</td><td>' . e($risk['owner'] ?? '-') . '</td><td>' . e($risk['current_control'] ?? '-') . '</td><td>' . e($risk['action'] ?? '-') . '</td></tr>';
    }
    $riskHtml .= '</table>';
    $riskHtml .= '<div class="risk-matrix">';
    $riskHtml .= '<h2>Risk Heat Map — Probability × Impact</h2>';
    $riskHtml .= '<p class="risk-matrix-helper">Risks are positioned by probability and impact. Higher probability and higher impact risks require earlier management attention.</p>';
    if ($riskRegister === []) {
        $riskHtml .= '<div class="panel">No risks available for heat map rendering.</div>';
    } else {
        $riskHtml .= '<div class="risk-matrix-axis-title">Impact — Low / Medium / High</div>';
        $riskHtml .= '<div class="risk-matrix-grid">';
        $riskHtml .= '<div class="risk-matrix-axis">Probability — Low / Medium / High</div><div class="risk-matrix-axis">Impact: Low</div><div class="risk-matrix-axis">Impact: Medium</div><div class="risk-matrix-axis">Impact: High</div>';
        foreach (['H' => 'High', 'M' => 'Medium', 'L' => 'Low'] as $probability => $probabilityLabel) {
            $riskHtml .= '<div class="risk-matrix-row-label">Probability: ' . e($probabilityLabel) . '</div>';
            foreach (['L' => 'Low', 'M' => 'Medium', 'H' => 'High'] as $impact => $impactLabel) {
                $bucket = $riskMatrixBuckets["{$probability}|{$impact}"] ?? [];
                $classes = $riskMatrixTone($probability, $impact);
                $riskHtml .= '<div class="' . $classes . '">';
                if ($bucket === []) {
                    $riskHtml .= '<div class="risk-cell-empty">' . e($probabilityLabel . ' probability / ' . $impactLabel . ' impact') . '</div>';
                }
                foreach ($bucket as $number) {
                    $riskHtml .= '<span class="risk-cell-point">' . e($number) . '</span>';
                }
                $riskHtml .= '</div>';
            }
        }
        $riskHtml .= '</div>';
        $riskHtml .= '<div class="risk-legend">';
        foreach ($riskRegister as $index => $risk) {
            $riskHtml .= '<div class="risk-legend-row"><span class="risk-legend-marker">' . e($index + 1) . '</span>' . e($index + 1) . ' — ' . e($risk['risk_title'] ?? 'Untitled risk') . '</div>';
        }
        $riskHtml .= '</div>';
        $riskHtml .= '<div class="risk-grid-caption">Numbered points correspond to the risk register rows above. Green = lower exposure, amber = medium exposure, red = higher exposure.</div>';
    }
    $riskHtml .= '<div class="chart-attribution">© RAB Consulting Services Ltd. Proprietary methodology.</div>';
@endphp
{!! $pageTemplate('Risk Register + Risk Heat Map', $riskHtml) !!}

@if($isPir)
    @php
        $raid = $report['raid_summary'] ?? [];
        $raidHtml = '<div class="grid-2"><div class="panel"><h2>' . e($raid['total_risks'] ?? 0) . '</h2>Total Risks</div><div class="panel"><h2>' . e($raid['critical_risks'] ?? 0) . '</h2>Critical</div><div class="panel"><h2>' . e($raid['issues_without_owner'] ?? 0) . '</h2>No Owner</div><div class="panel"><h2>' . e($raid['overdue_actions'] ?? 0) . '</h2>Overdue</div></div><p>' . e($raid['assessment'] ?? 'RAID summary not returned.') . '</p>';
    @endphp
    {!! $pageTemplate('RAID Summary', $raidHtml) !!}
@endif

@php
    $root = $report['root_cause_analysis'] ?? [];
    $rootHtml = '<p>' . nl2br(e($root['narrative'] ?? 'Root cause analysis not returned.')) . '</p><div class="panel"><strong>Primary cause:</strong> ' . e($root['primary_cause'] ?? '-') . '</div><ol>';
    foreach (($root['causal_chain'] ?? []) as $step) $rootHtml .= '<li>' . e($step) . '</li>';
    $rootHtml .= '</ol>';
@endphp
{!! $pageTemplate('Root Cause Analysis', $rootHtml) !!}

@php
    $planHtml = '<table><tr><th>#</th><th>Horizon</th><th>Action</th><th>Owner</th><th>Deadline</th><th>Done Condition</th></tr>';
    $count = 1;
    foreach (['30_days', '60_days', '90_days'] as $horizon) {
        foreach ($rowsForHorizon($priorityPlan[$horizon] ?? []) as $item) {
            $planHtml .= '<tr><td>' . $count++ . '</td><td>' . e(str_replace('_', ' ', $horizon)) . '</td><td>' . e($item['action_title'] ?? $item['action'] ?? '-') . '</td><td>' . e($item['owner'] ?? '-') . '</td><td>' . e($item['deadline'] ?? '-') . '</td><td>' . e($item['done_condition'] ?? '-') . '</td></tr>';
        }
    }
    $planHtml .= '</table>';
@endphp
{!! $pageTemplate('Priority Plan 30/60/90', $planHtml) !!}

@if(!empty($report['compliance_risk_signals']))
    @php
        $complianceRows = [];
        if (is_array($report['compliance_risk_signals'])) {
            foreach ($report['compliance_risk_signals'] as $item) {
                if (is_array($item)) {
                    $complianceRows[] = $item;
                } else {
                    $complianceRows[] = ['finding' => $item];
                }
            }
        } else {
            $complianceRows[] = ['finding' => $asText($report['compliance_risk_signals'])];
        }

        $complianceHtml = '<table class="compliance-table"><tr><th>Finding</th><th>Domain</th><th>Obligation</th><th>Action</th><th>Deadline</th></tr>';
        foreach ($complianceRows as $row) {
            $complianceHtml .= '<tr><td>' . e($row['finding'] ?? '-') . '</td><td>' . e($row['domain'] ?? '-') . '</td><td>' . e($row['obligation'] ?? '-') . '</td><td>' . e($row['action'] ?? '-') . '</td><td>' . e($row['deadline'] ?? '-') . '</td></tr>';
        }
        $complianceHtml .= '</table>';
    @endphp
    {!! $pageTemplate('Compliance Risk Signals', $complianceHtml) !!}
@endif

@php
    $finalHtml = '<div class="panel"><h2>' . e($report['final_position'] ?? 'Final position not returned.') . '</h2></div>';
    if (!$isBriefing && !empty($report['tier1_bridge'])) {
        $finalHtml .= '<p><strong>Tier 1 bridge:</strong> ' . e($asText($report['tier1_bridge'])) . '</p>';
    }
@endphp
{!! $pageTemplate('Final Position', $finalHtml) !!}

@php
    $appendixHtml = '<div class="panel appendix-intro"><p><span class="appendix-label">Evidence Base:</span> Database extract from stored assessment responses. This section is not AI-generated.</p></div>';

    if ($appendixRows === []) {
        $appendixHtml .= '<div class="panel">No stored question responses were found for this assessment.</div>';
    } else {
        $appendixHtml .= '<table class="appendix-table"><thead><tr>'
            . '<th style="width:8%;">Question ID</th>'
            . '<th style="width:24%;">Question Text</th>'
            . '<th style="width:8%;">Pillar / Domain</th>'
            . '<th style="width:6%;">Score</th>'
            . '<th style="width:10%;">RAG</th>'
            . '<th style="width:' . ($appendixIsTier2 ? '16%' : '44%') . ';">Evidence Note</th>';

        if ($appendixIsTier2) {
            $appendixHtml .= '<th style="width:8%;">Confidence Level</th>'
                . '<th style="width:8%;">Respondent Role</th>'
                . '<th style="width:8%;">Document Source</th>'
                . '<th style="width:12%;">Stakeholder Divergence</th>';
        }

        $appendixHtml .= '</tr></thead><tbody>';

        foreach ($appendixRows as $row) {
            $appendixHtml .= '<tr>'
                . '<td class="appendix-code">' . e($row['question_id'] ?? '-') . '</td>'
                . '<td>' . e($row['question_text'] ?? '-') . '</td>'
                . '<td>' . e($row['pillar_code'] ?? '-') . '</td>'
                . '<td>' . e(number_format((float) ($row['score'] ?? 0), 0)) . '</td>'
                . '<td>' . e($row['rag'] ?? '-') . '</td>'
                . '<td>' . e($row['evidence_note'] ?: '-') . '</td>';

            if ($appendixIsTier2) {
                $appendixHtml .= '<td>' . e($row['confidence_level'] ?: '-') . '</td>'
                    . '<td>' . e($row['respondent_role'] ?: '-') . '</td>'
                    . '<td>' . e($row['document_source'] ?: '-') . '</td>'
                    . '<td>' . e($row['stakeholder_divergence_note'] ?: '-') . '</td>';
            }

            $appendixHtml .= '</tr>';
        }

        $appendixHtml .= '</tbody></table>';
    }

    $appendixNoteHtml = '<div class="panel appendix-note"><p>' . e($appendixNote) . '</p></div>';
@endphp
{!! $pageTemplate('Appendix — Database Extract', $appendixHtml) !!}
{!! $pageTemplate('Appendix — Methodology Note', $appendixNoteHtml) !!}

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.2/dist/chart.umd.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels@2.2.0/dist/chartjs-plugin-datalabels.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const radarData = @json($radarEntries);
    const barData = @json($barEntries);

    const fallbackRadar = document.getElementById('pdfRadarFallback');
    const fallbackBar = document.getElementById('pdfBarFallback');
    const radarCanvas = document.getElementById('pdfRadarChart');
    const barCanvas = document.getElementById('pdfBarChart');

    const showFallback = () => {
        if (fallbackRadar) fallbackRadar.classList.remove('is-hidden');
        if (fallbackBar) fallbackBar.classList.remove('is-hidden');
        if (radarCanvas) radarCanvas.style.display = 'none';
        if (barCanvas) barCanvas.style.display = 'none';
    };

    if (typeof Chart === 'undefined') {
        showFallback();
        return;
    }

    if (typeof ChartDataLabels !== 'undefined') {
        Chart.register(ChartDataLabels);
    }

    const radarLabels = radarData.map((item) => item.code);
    const radarValues = radarData.map((item) => item.score);
    const radarColors = radarData.map((item) => item.tone.color);

    if (radarCanvas) {
        if (fallbackRadar) fallbackRadar.classList.add('is-hidden');
        radarCanvas.style.display = 'block';
        new Chart(radarCanvas, {
            type: 'radar',
            data: {
                labels: radarLabels,
                datasets: [{
                    data: radarValues,
                    backgroundColor: 'rgba(37, 99, 235, 0.16)',
                    borderColor: '#2563EB',
                    pointBackgroundColor: '#2563EB',
                    pointRadius: 3,
                    borderWidth: 2
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                animation: false,
                scales: {
                    r: {
                        min: 1,
                        max: 5,
                        ticks: { stepSize: 1, backdropColor: 'transparent' },
                        grid: { color: 'rgba(148,163,184,0.25)' },
                        angleLines: { color: 'rgba(148,163,184,0.25)' }
                    }
                },
                plugins: {
                    legend: { display: false },
                    datalabels: { display: false }
                }
            }
        });
    }

    if (barCanvas) {
        if (fallbackBar) fallbackBar.classList.add('is-hidden');
        barCanvas.style.display = 'block';
        new Chart(barCanvas, {
            type: 'bar',
            data: {
                labels: barData.map((item) => item.code),
                datasets: [{
                    data: barData.map((item) => item.score),
                    backgroundColor: barData.map((item) => item.tone.color),
                    borderRadius: 4,
                    barThickness: 14
                }]
            },
            options: {
                indexAxis: 'y',
                responsive: true,
                maintainAspectRatio: false,
                animation: false,
                scales: {
                    x: {
                        min: 0,
                        max: 5,
                        ticks: { stepSize: 1 },
                        grid: { color: 'rgba(148,163,184,0.18)' }
                    },
                    y: {
                        grid: { display: false }
                    }
                },
                plugins: {
                    legend: { display: false },
                    datalabels: {
                        anchor: 'end',
                        align: 'end',
                        clamp: true,
                        formatter: (value) => Number(value).toFixed(1),
                        color: '#0F172A',
                        font: { weight: 'bold', size: 9 }
                    }
                }
            }
        });
    }
});
</script>
</body>
</html>
