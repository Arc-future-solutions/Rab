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
    $logo = 'file://' . public_path('assets/images/logo-rab.png');

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
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>{{ $meta['report_label'] }} | {{ $meta['client'] }}</title>
    <style>
        @page { size: A4; margin: 10mm; }
        * { box-sizing: border-box; }
        body { margin: 0; font-family: Inter, Arial, sans-serif; color: #0F172A; font-size: 11pt; line-height: 1.45; background: #fff; }
        .cover { width: 210mm; min-height: 297mm; margin: -10mm; padding: 40px; background: #1E3A8A; color: #fff; page-break-after: always; position: relative; overflow: hidden; }
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
        .report-page { min-height: 277mm; page-break-after: always; position: relative; overflow: hidden; padding: 0 0 28mm; }
        .report-page::before { content: "CONFIDENTIAL"; position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%) rotate(-45deg); font-family: Inter, Arial, sans-serif; font-size: 72pt; font-weight: 700; letter-spacing: 8px; color: rgba(30,58,138,0.05); white-space: nowrap; pointer-events: none; user-select: none; z-index: 0; }
        .report-page > * { position: relative; z-index: 1; }
        .page-header { display: grid; grid-template-columns: 1fr 1fr; gap: 24px; align-items: start; padding-bottom: 10px; border-bottom: .5pt solid #1E3A8A; margin-bottom: 20px; }
        .header-left { display: flex; gap: 12px; align-items: center; }
        .header-logo { width: 80px; }
        .header-type { font-size: 9pt; color: #1E3A8A; font-weight: 800; text-transform: uppercase; letter-spacing: 1px; }
        .header-right { text-align: right; }
        .header-client { font-size: 10pt; font-weight: 800; color: #0F172A; }
        .header-subject { font-size: 9pt; color: #374151; }
        .header-confidential { font-size: 8pt; color: #1E3A8A; font-weight: 800; letter-spacing: 2px; }
        h1 { font-size: 18pt; color: #1E3A8A; margin: 0 0 14px; }
        h2 { font-size: 14pt; color: #0F172A; margin: 0 0 8px; }
        p { margin: 0 0 10px; }
        .panel { border: 1px solid #E5E7EB; background: #F8FAFC; padding: 14px; margin-bottom: 14px; }
        .grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
        .grid-3 { display: grid; grid-template-columns: repeat(3, 1fr); gap: 10px; }
        .index-grid { display: grid; grid-template-columns: repeat(5, 1fr); gap: 8px; margin: 14px 0; }
        .index-card { border: 1px solid #CBD5E1; padding: 10px; min-height: 92px; background: #fff; }
        .index-name { font-size: 8pt; color: #64748B; font-weight: 800; text-transform: uppercase; }
        .index-value { font-size: 24pt; font-weight: 800; color: #0F172A; line-height: 1.1; }
        .methodology-stamp, .chart-attribution { font-size: 7pt; color: #1E3A8A; margin-top: 7px; }
        .chart-attribution { color: #64748B; }
        .alert { padding: 9px 10px; margin-bottom: 7px; border-left: 4px solid #B45309; background: #FEF3C7; color: #78350F; font-weight: 700; }
        .alert-critical { border-color: #B91C1C; background: #FEE2E2; color: #7B0000; }
        table { width: 100%; border-collapse: collapse; margin: 10px 0 16px; }
        th { text-align: left; background: #1E3A8A; color: #fff; font-size: 8pt; padding: 7px; text-transform: uppercase; letter-spacing: .5px; }
        td { border-bottom: 1px solid #E5E7EB; padding: 7px; vertical-align: top; font-size: 9pt; }
        .heat-map { display: grid; grid-template-columns: repeat(6, 1fr); gap: 6px; margin-bottom: 16px; }
        .heat-cell { padding: 8px; font-size: 8pt; font-weight: 800; text-align: center; }
        .rag-green { background: #DCFCE7; color: #166534; }
        .rag-amber { background: #FEF3C7; color: #B45309; }
        .rag-red { background: #FEE2E2; color: #B91C1C; }
        .rag-dark-red { background: #7B0000; color: #fff; }
        .badge { display: inline-block; padding: 3px 6px; font-size: 7pt; font-weight: 800; text-transform: uppercase; }
        .evidence { font-size: 10pt; color: #374151; font-style: italic; }
        .risk-map { display: grid; grid-template-columns: repeat(3, 1fr); grid-template-rows: repeat(3, 30px); border: 1px solid #CBD5E1; margin-top: 10px; }
        .risk-map div { border: 1px solid #CBD5E1; text-align: center; font-size: 8pt; padding-top: 7px; }
        .footer { position: absolute; bottom: 0; left: 0; right: 0; border-top: 1px solid #E5E7EB; padding-top: 6px; color: #64748B; font-size: 8pt; }
        .footer-grid { display: grid; grid-template-columns: 1fr auto; gap: 12px; align-items: end; }
        .footer-main { font-size: 7.3pt; line-height: 1.25; }
        .footer-contact { margin-top: 4px; font-size: 7.5pt; }
        .page-number { white-space: nowrap; font-size: 8pt; }
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

@php
    $page = 1;
    $totalPages = 9 + ($isBriefing ? 1 : 0) + ($isPir ? 1 : 0) + (!empty($report['compliance_risk_signals']) ? 1 : 0);
    $footerText = 'This report is produced by RAB Consulting Services Ltd. All findings are based on information provided during the engagement. This constitutes operational intelligence and professional advisory guidance only. It does not constitute legal, regulatory, financial, or compliance advice. Clients should engage their own legal, compliance, and regulatory advisers before acting on any finding. © 2026 RAB Consulting Services Ltd. All methodology, indices, scoring frameworks, and report content are proprietary intellectual property. Reproduction or distribution without written consent is prohibited.';
    $footerContact = 'RAB Consulting Services Ltd | rboukhiar@rabconsultingservices.com | +44 7717 544322 | rabconsultingservices.com';
@endphp

@once
    @php
        $pageTemplate = function ($title, $slot, $appendix = false) use (&$page, $totalPages, $logo, $meta, $footerText, $footerContact) {
            $pageLabel = ($appendix ? 'Appendix — ' : '') . 'Page ' . $page . ' of ' . $totalPages;
            $html = '<section class="report-page">'
                . '<header class="page-header"><div class="header-left">'
                . '<img src="' . e($logo) . '" class="header-logo" alt="RAB"><div class="header-type">' . e($meta['report_type']) . '</div></div>'
                . '<div class="header-right"><div class="header-client">' . e($meta['client']) . '</div><div class="header-subject">' . e($meta['subject']) . '</div><div class="header-confidential">CONFIDENTIAL</div></div></header>'
                . '<h1>' . e($title) . '</h1>'
                . $slot
                . '<footer class="footer"><div class="footer-grid"><div><div class="footer-main">' . e($footerText) . '</div><div class="footer-contact">' . e($footerContact) . '</div></div><div class="page-number">' . e($pageLabel) . '</div></div></footer>'
                . '</section>';
            $page++;
            return $html;
        };
    @endphp
@endonce

{!! $pageTemplate('Transmittal Letter', '<p>' . nl2br(e($report['cover_letter'] ?? 'AI transmittal letter not available.')) . '</p>') !!}

@php
    $alerts = $dashboard['alert_flags'] ?? $report['intelligence_dashboard']['alert_flags'] ?? [];
    if (!is_array($alerts)) $alerts = [];
    $exec = '<div class="grid-2"><div><p>' . nl2br(e($report['executive_position'] ?? 'Executive position not available.')) . '</p></div><div>';
    foreach ($alerts as $alert) {
        $class = str_contains(strtolower((string) $alert), 'critical') || str_contains(strtolower((string) $alert), 'compliance') ? ' alert-critical' : '';
        $exec .= '<div class="alert' . $class . '">' . e($asText($alert)) . '</div>';
    }
    $exec .= $alerts === [] ? '<div class="panel">No alert flags reported.</div>' : '';
    $exec .= '</div></div>';
@endphp
{!! $pageTemplate('Executive Intelligence Position', $exec) !!}

@php
    $dashboardHtml = '<div class="panel"><strong>Overall:</strong> ' . e($assessment->overall_score) . ' · ' . e($assessment->rag_status) . '</div><div class="index-grid">';
    foreach ($indices as $name => $item) {
        $value = is_array($item) ? ($item['score'] ?? $item['value'] ?? '') : $item;
        $interpretation = is_array($item) ? ($item['interpretation'] ?? '') : '';
        $dashboardHtml .= '<div class="index-card"><div class="index-name">' . e($name) . '</div><div class="index-value">' . e($value) . '</div><div>' . e($interpretation) . '</div><div class="methodology-stamp">RAB Proprietary Methodology™</div></div>';
    }
    $dashboardHtml .= '</div><div class="grid-2"><div class="panel"><h2>Score Distribution</h2><div class="heat-map">';
    foreach ($scores as $score) {
        $dashboardHtml .= '<div class="heat-cell ' . $ragClass($score->score) . '">' . e(explode(' — ', $score->name)[0]) . '<br>' . e($score->score) . '</div>';
    }
    $dashboardHtml .= '</div><div class="chart-attribution">© RAB Consulting Services Ltd. Proprietary methodology.</div></div>';
    $dashboardHtml .= '<div class="panel"><h2>Priority Plan Summary</h2><table><tr><th>Horizon</th><th>Headline</th><th>Owner</th></tr>';
    foreach (['30_days', '60_days', '90_days'] as $horizon) {
        $items = $rowsForHorizon($priorityPlan[$horizon] ?? []);
        $first = $items[0] ?? [];
        $dashboardHtml .= '<tr><td>' . e(str_replace('_', ' ', $horizon)) . '</td><td>' . e($first['action_title'] ?? $first['action'] ?? '-') . '</td><td>' . e($first['owner'] ?? '-') . '</td></tr>';
    }
    $dashboardHtml .= '</table></div></div>';
@endphp
{!! $pageTemplate('Intelligence Dashboard', $dashboardHtml) !!}

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
        $profileHtml .= '<div class="heat-cell ' . $ragClass($score->score) . '">' . e(explode(' — ', $score->name)[0]) . '<br>' . e($score->score) . '</div>';
    }
    $profileHtml .= '</div>';
    foreach ($profile as $finding) {
        $profileHtml .= '<div class="panel"><h2>' . e($finding['headline'] ?? $finding['pillar_name'] ?? $finding['domain_name'] ?? 'Finding') . '</h2>'
            . '<p><span class="badge ' . $ragClass($finding['score'] ?? null) . '">' . e($finding['confidence'] ?? 'Medium') . '</span> Score: ' . e($finding['score'] ?? '-') . '</p>'
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
        $riskHtml .= '<tr><td>' . ($index + 1) . '</td><td>' . e($risk['risk_title'] ?? '-') . '</td><td>' . e($risk['probability'] ?? '-') . '</td><td>' . e($risk['impact'] ?? '-') . '</td><td>' . e($risk['owner'] ?? '-') . '</td><td>' . e($risk['current_control'] ?? '-') . '</td><td>' . e($risk['action'] ?? '-') . '</td></tr>';
    }
    $riskHtml .= '</table><div class="risk-map">';
    foreach (['L/H','M/H','H/H','L/M','M/M','H/M','L/L','M/L','H/L'] as $cell) $riskHtml .= '<div>' . $cell . '</div>';
    $riskHtml .= '</div><div class="chart-attribution">© RAB Consulting Services Ltd. Proprietary methodology.</div>';
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
    {!! $pageTemplate('Compliance Risk Signals', '<div class="panel">' . nl2br(e($asText($report['compliance_risk_signals']))) . '</div>') !!}
@endif

@php
    $finalHtml = '<div class="panel"><h2>' . e($report['final_position'] ?? 'Final position not returned.') . '</h2></div>';
    if (!$isBriefing && !empty($report['tier1_bridge'])) {
        $finalHtml .= '<p><strong>Tier 1 bridge:</strong> ' . e($asText($report['tier1_bridge'])) . '</p>';
    }
@endphp
{!! $pageTemplate('Final Position', $finalHtml) !!}

{!! $pageTemplate('Appendix — Evidence Base + Methodology Note', '<p><span class="appendix-label">Evidence Base:</span> database extract to be added under Step 13.</p><p>ABOUT RAB INTELLIGENCE INDICES — The indices in this briefing are proprietary commercial intelligence signals developed by RAB Consulting Services. They are not standard industry metrics. They are weighted composites calculated by the RAB Platform scoring engine. The AI generation engine uses them — it does not compute them. © 2026 RAB Consulting Services Ltd. All rights reserved.</p>', true) !!}
</body>
</html>
