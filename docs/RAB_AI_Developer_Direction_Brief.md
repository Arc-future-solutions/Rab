# RAB AI Report Generation — Developer Direction Brief

## Updated for Full Report Phase — May 2026

> Read this fully before touching any code. Supersedes the previous version.

---

## The Core Problem to Solve

You have **6 AI prompts** and **4 assessment types** (PIR Snapshot, SIR Snapshot, PIR Full Tier 1, SIR Full Tier 1 — plus Tier 2 variants). Each assessment type has a pre-defined payload structure and a matching prompt key in `config/ai.php`. Your job is to wire them together correctly. Nothing more.

Snapshots are complete and signed off. This brief now governs full report work.

---

## What You Must NOT Do

- **Do not create separate service classes per assessment type.** One `ReportService` handles all 6. It already exists and works.
- **Do not add retry logic, streaming config, or custom timeouts.** The existing ReportService is correct. Do not modify it except to confirm `max_tokens` is 16000 for full report calls.
- **Do not calculate any numeric value** (BRI, VRI, DMI, RII, SSI, SMI, SIMI, BAU-RI, CHI, smi_simi_delta). These come out of the scoring engine already. Pass them in the payload as-is.
- **Do not create new config files.** All 6 prompts are in `config/ai.php`. That is the only prompt config file.
- **Do not modify the prompts.** Paste them verbatim from the build guide. Any edit breaks the output quality.
- **Do not run full report generation synchronously.** 16k tokens in a web request will time out. Full reports go to a queue. No exceptions.

---

## API Request — Confirmed Working Configuration

Headers:

```php
'x-api-key'         => config('services.anthropic.key'),
'anthropic-version' => '2023-06-01',
'content-type'      => 'application/json',
```

> No `anthropic-beta` header. It was tested and rejected by this account. Do not add it back.

Body:

```php
[
    'model'      => 'claude-sonnet-4-6',   // confirmed working model for this account
    'max_tokens' => 16000,                 // full reports — snapshots use 2000
    'system'     => $prompt,
    'messages'   => [
        [
            'role'    => 'user',
            'content' => "Analyse the following payload and return the JSON output as instructed.\n\n" . json_encode($payload)
        ]
    ],
]
```

**Max tokens by prompt key:**

| Prompt key     | max_tokens |
| -------------- | ---------- |
| pir_snapshot   | 2000       |
| sir_snapshot   | 2000       |
| pir_full_tier1 | 16000      |
| sir_full_tier1 | 16000      |
| pir_full_tier2 | 16000      |
| sir_full_tier2 | 16000      |

---

## The Queue Pattern for Full Reports — Non-Negotiable

Snapshots run synchronously. Full reports do not. Every full report generation must go through a Laravel queue job.

```php
// Controller — dispatch immediately and return status
GenerateFullReport::dispatch($promptKey, $payload, $assessmentId);
return response()->json(['status' => 'processing']);

// Job — does the actual ReportService::generate() call
// Stores result on the assessment record when complete
// Sets a status field: pending → processing → complete | failed

// Admin UI polls GET /admin/assessments/{id}/report-status
// When status = complete: page reloads or refreshes the report section
```

The assessment record must carry a `full_report_status` field:

- `null` — not yet generated
- `pending` — job dispatched, not yet picked up
- `processing` — job running
- `complete` — result stored
- `failed` — job failed, error logged

On failure: log the full error, set status to `failed`, display the error message in the admin UI. Do not silently fail.

---

## Full Report Payload Structure

Full report payloads contain everything in the snapshot payload plus the following additional fields. All values are read from the database — nothing is calculated here.

### Fields added for all full reports (PIR and SIR):

```php
'tier'               => 'Review',    // Tier 1 — or 'Briefing' for Tier 2
'consultant_name'    => string,      // from the assessment record
'sponsor_name'       => string,      // from the assessment record
'interview_count'    => int,         // number of interviews conducted
'documents_reviewed' => int,         // number of documents reviewed
'programme_value'    => float|null,  // PIR: £ value of programme. null if not known.
'annual_service_cost'=> float|null,  // SIR: annual cost of service. null if not known.
'evidence_notes'     => array,       // see structure below — most important field
```

### PIR-only additional fields:

```php
'reporting_accuracy_risk'     => bool,         // true if consultant flagged reporting accuracy concern
'reporting_accuracy_evidence' => string|null,  // notes supporting the flag
```

### Tier 2-only additional field:

```php
'stakeholder_notes' => string|null,  // pass null for Tier 1
```

### evidence_notes structure — critical:

This is the most important field in the full report payload. Without it the AI produces generic output identical to a snapshot. It must be keyed by question ID.

```php
'evidence_notes' => [
    'P1.F1' => [
        'note'       => 'string — what the consultant observed or was told',
        'source'     => 'string — document name or interview role',
        'confidence' => 'High|Medium|Low',
        'respondent' => 'string — who provided the information',
    ],
    'P1.F2' => [ ... ],
    // one entry per question where the consultant entered notes
    // questions with no notes are omitted — do not include empty entries
]
```

> If `evidence_notes` has fewer than 10 populated entries, the AI output will be thin. Do not test full report generation without at least 10–15 questions having real notes populated by the consultant.

### question_responses for full reports:

Same structure as snapshots — only these four fields per question:

```php
['id' => 'P1.F1', 'pillar_code' => 'P1', 'score' => 3, 'is_compliance' => false]
```

---

## Payload Rules — Non-Negotiable

### PIR full report payload must include:

- All snapshot fields: `pillar_scores`, `pillar_names`, `bri`, `vri`, `dmi`, `rii`, `chi`, `delivery_stage`, `regulatory_context`, `overall_score`, `rag_status`
- `alert_flags`, `question_responses`, `compliance_question_scores`
- All full report additions listed above

### SIR full report payload must include:

- All snapshot fields: `domain_scores`, `domain_names`, `ssi`, `smi`, `simi`, `bau_ri`, `chi`, `smi_simi_delta`, `service_context`, `regulatory_context`, `overall_score`, `rag_status`
- `alert_flags`, `question_responses`, `compliance_question_scores`
- All full report additions listed above

---

## What to Build — In This Order

Snapshots are done. For full reports:

1. Identify the database tables and columns where evidence notes, confidence, source, and respondent are stored per question. Report back before building anything.
2. Build `FullReportAiPayloadBuilder` — same pattern as `SnapshotAiPayloadBuilder`. DB-driven. `buildFromAssessment($assessment, string $tier): array`.
3. Add the queue job `GenerateFullReport`. It calls `ReportService::generate()` and stores the result.
4. Add `full_report_status` and `full_report_json` columns to the assessments/leads table if not already present.
5. PIR Full Tier 1 POC — extract payload from a real assessment with populated evidence notes, save as JSON, dispatch the job, save the response as JSON, verify against the expected schema.
6. SIR Full Tier 1 POC — same pattern after PIR is signed off.
7. Tier 2 variants after both Tier 1s are confirmed.

**Do not proceed to step 5 until steps 1–4 are confirmed.**

---

## Rules That Never Change

- Never calculate BRI, VRI, DMI, RII, SSI, SMI, SIMI, BAU-RI, CHI, smi_simi_delta
- Never use max_tokens above 2000 for snapshots
- Never run full report generation synchronously
- Never modify the prompts
- Never create a second ReportService
- Results pages and admin views always read from stored JSON — never call the AI on page load
- Do not proceed to the next step until the current one is confirmed and signed off

---

_RAB Consulting Services Ltd — Developer Direction Brief v2 — May 2026_
