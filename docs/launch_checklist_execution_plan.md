# Step 15 Launch Checklist Execution Plan

Updated: 2026-05-18

## Purpose

This document is the execution strategy for proving the full RAB application works after the changes from `docs/RAB_Developer_Final_Build_Guide_v5.md`.

The goal is not only to tick the 31 launch checklist items. The goal is to prove the complete user journey works across public diagnostics, admin assessment creation, consultant evidence capture, scoring, AI generation, PDF export, webhooks, email alerts, and Reda review items.

## Release Decision

Do not run a client-facing test until all of these are true:

- Every `code-pass` item has current evidence from this test cycle, not only historical evidence.
- Every `pending-manual` item has a screenshot, PDF, saved record, or network capture attached to the evidence pack.
- Every `pending-external` item has an external log, inbox receipt, webhook capture, or explicit blocker note.
- Every `reda-review` item has written Reda sign-off or remains open.
- Every `blocked` item is either implemented, explicitly descoped by Reda, or recorded as a launch blocker.

## Test Strategy

### Gate 0 — Environment and Baseline

Run this before any manual walkthrough. If this gate fails, stop and fix the environment first.

1. Confirm the test target: local, staging, or production.
2. Confirm `.env` points to the intended database, mailer, queue, AI provider, CRM webhook, and N8N webhook.
3. Confirm Chromium/Browsershot dependencies are installed on the machine that generates PDFs.
4. Confirm a test admin account exists and can sign in.
5. Confirm test inboxes and webhook receivers are accessible.
6. Record the commit hash, database name, test URL, and tester name in the evidence pack.

Suggested commands:

```bash
git rev-parse --short HEAD
php artisan about
php artisan config:clear
php artisan test
npm run build
```

Pass condition: the app boots, the frontend builds, and the automated suite passes or any failure is documented as unrelated and accepted by the release owner.

### Gate 1 — Automated Regression

Run the full automated suite first, then targeted tests for the build-guide changes.

Required commands:

```bash
php artisan test
php artisan test tests/Unit/AssessmentIndexCalculatorTest.php
php artisan test tests/Feature/AssessmentAiPayloadBuilderTest.php
php artisan test tests/Feature/GuideStageNotesSeederTest.php
php artisan test tests/Feature/AiPromptConfigTest.php
php artisan test tests/Feature/AdminGenerateReportPromptTest.php
php artisan test tests/Feature/AdminReportPdfExportTest.php
php artisan test tests/Feature/RapidConsultingSecurityTest.php
php artisan test tests/Feature/SendToCrmWebhookTest.php
```

Pass condition: no failing tests. If a test is intentionally obsolete, update the test or checklist before continuing; do not ignore it silently.

### Gate 2 — Data Integrity Verification

Run database checks against the same database used for the manual test target.

Required checks:

- PIR full bank is `127` questions with expected hybrid and compliance counts.
- SIR full bank is `127` questions with expected hybrid and compliance counts.
- `P2.F9` exists and has the build-guide stage note.
- The exact build-guide stage-note set returns `PIR|18`, `SIR|5`, `ALL|23`.
- D11 renders as `Service Tooling, CMDB & Knowledge Management`.
- New assessments stamp `scoring_version = 1.0`.

Pass condition: database values match the checklist matrix. Do not proceed if the manual UI is connected to a different database from the verified one.

### Gate 3 — Public Diagnostic Smoke Test

Run Flow A and Flow B once each using ordinary user data.

Required evidence:

- Screenshot of assessment type selection.
- Screenshot proving context screen appears before question 1.
- Screenshot proving TYPE_SELECT answers hide numeric score values.
- Screenshot proving the personal-details form appears only after all questions.
- Browser network capture proving `hidden_risk` and `score_anchors` are absent from public responses.
- Saved lead/dashboard result for both PIR and SIR.

Pass condition: both public journeys complete without server errors, privacy leaks, or ordering regressions.

### Gate 4 — Admin Full Assessment Test

Run Flow D for four combinations:

- PIR, `Tier 1 Rapid`
- PIR, `Tier 2 Full`
- SIR, `Tier 1 Rapid`
- SIR, `Tier 2 Full`

For Tier 2 assessments, enter evidence notes, respondent role, document source, stakeholder divergence notes, delivery/service context, regulatory context, and summary notes.

Pass condition: all fields save, reload, and appear in the AI payload/report path where expected.

### Gate 5 — Scoring and Manual Calculation

Use controlled input values for at least one PIR and one SIR full assessment.

Required checks:

- SIR `SSI` matches `(D2×1.4+D5×1.3)/2.7`.
- SIR D11 uses weight `1.2` and denominator `14.5`.
- `SMI`, `SIMI`, and `smi_simi_delta` match manual calculation.
- PIR `RII` excludes `P2.F9`.
- Five complete diagnostics are compared against manual calculations for Reda item `#30`.

Pass condition: application scores match manual calculations to the agreed rounding precision.

### Gate 6 — AI and PDF Review

Run Flow E for one PIR Tier 2 and one SIR Tier 2 assessment.

Required evidence:

- AI output capture for each report.
- PDF export for each report.
- Cover letter contains the correct product name and no duration claim.
- `[CONSULTANT TO COMPLETE]` placeholder is visible where expected.
- PDF uses Browsershot output and not DomPDF.
- Watermark, footer, index cards, chart attribution, risk heat map, RAID summary, and root-cause sections are visible where applicable.

Pass condition: technical generation works and Reda signs off on quality-sensitive items.

### Gate 7 — External Integration Test

Run one high-risk public diagnostic and one lower-risk diagnostic.

Required evidence:

- Internal CRM evidence: `/admin/leads` contains the completed snapshot leads with correct risk priority, Hot/Warm/Cold sales status, consent fields, and booking status.
- Booking evidence: Calendly webhook or simulated appointment creates `/admin/bookings` record and updates the matching lead by email.
- Conversion evidence: starting a Tier 1 or Tier 2 assessment from a lead creates/reuses a client, marks the lead converted, and links the assessment to the original snapshot.
- N8N webhook request payload and receiver response if enabled.
- Queue/log evidence for high-priority alert dispatch.
- Destination inbox receipt timestamp for high-priority alert.
- Client PDF email receipt, or a blocker record if the feature is not implemented.
- AI request log or HTTP capture proving the ZDR header is present.

Pass condition: N8N receives the expected payload if enabled, internal CRM lifecycle records are present in the admin dashboard, and unresolved external dependencies are marked as blockers.

Execution result on `2026-05-18`: `technical-pass`.

- Internal CRM lead evidence: controlled PIR snapshot created lead `#2` with `priority=High`, `lead_status=Hot`, `assessment_type=PIR_SNAPSHOT`, `critical_flag=CriticalAreaBelowThreshold`, `consent_given=true`.
- Booking evidence: signed Calendly `invitee.created` simulation using the real configured webhook secret created booking `#2` and updated the matching lead by email to `booking_status=Booked`.
- Mail evidence: Mailpit received `High-Priority PIR Diagnostic — RAB Gate 7 Real Calendly Company` and `New Booking — Gate Seven Real Calendly Tester`.
- Queue evidence: N8N queued job completed; booking notification job completed; queue ended with `0` pending jobs and `0` failed jobs.
- N8N evidence: direct receiver verification POST returned HTTP `200`.
- Calendly evidence: `CALENDLY_WEBHOOK_SECRET` is configured and no longer uses the placeholder value; app signature verification passed with the configured secret.

### Gate 8 — Final Sign-Off

Create one evidence pack containing:

- Test target URL, commit hash, database name, and execution date.
- Automated test output.
- Database query output.
- Screenshots for Flows A to E.
- Webhook and email evidence for Flow F.
- Generated PDFs.
- Manual score calculation sheet for the five complete diagnostics.
- Reda sign-off notes for checklist items `#15`, `#20`, `#21`, `#25`, `#29`, `#30`, and `#31`.

Pass condition: no unresolved blocker remains, and Reda has signed off all `[REDA]` launch items.

Execution result on `2026-05-18`: `technical-pass`, `launch-signoff-blocked`.

- Target: local Laravel app, `APP_URL=http://localhost`
- Commit: `2960a11`
- Database: SQLite, `u231809987_rab`
- Automated regression: `php artisan test` -> `34 passed, 236 assertions`
- Frontend build: `npm run build` -> passed
- Deployment cache check: `php artisan route:cache` -> passed
- Runtime baseline: `php artisan about` -> Laravel `13.3.0`, PHP `8.4.20`, mail `smtp`, queue `database`, DB `sqlite`
- Queue health after Gate 7/8: `0` pending jobs, `0` failed jobs
- Admin access evidence: `admin@rab.com` exists with `role=admin` and verified local password hash for `password`
- Internal CRM evidence: latest controlled Gate 7 lead `#2` is `priority=High`, `lead_status=Hot`, `booking_status=Booked`, `assessment_type=PIR_SNAPSHOT`, `consent_given=true`
- Booking evidence: latest controlled Calendly booking `#2` is active for the matched Gate 7 lead email and was signed with the real configured Calendly secret
- Mail evidence: Mailpit received `High-Priority PIR Diagnostic — RAB Gate 7 Real Calendly Company` and `New Booking — Gate Seven Real Calendly Tester`
- N8N evidence: configured webhook accepted a direct verification POST with HTTP `200`
- Calendly evidence: `CALENDLY_WEBHOOK_SECRET` is configured and no longer uses the placeholder value
- PDF evidence: generated PIR and SIR PDFs are stored in `/tmp/rab-gate6`
- Screenshot evidence: public diagnostic evidence is stored in `/tmp/rab-gate3`; admin flow evidence is stored in `/tmp/rab-gate4`
- Manual scoring evidence: controlled PIR/SIR score comparison is stored in `/tmp/rab-gate5-results.json`

Gate 8 cannot be marked full release pass until these remaining blockers are closed:

- Client PDF email delivery is not visible in this repository and remains either a missing feature or an external process to confirm.
- Reda sign-off is still required for checklist items `#15`, `#20`, `#21`, `#25`, `#29`, `#30`, and `#31`.
- `APP_DEBUG=true`, `APP_ENV=local`, `APP_URL=http://localhost`, and `public/storage` is not linked; these are acceptable for local testing but not for production launch.

## Defect Handling Rules

- `P0`: Security leak, wrong score, broken public diagnostic, failed payment/client-facing journey, or missing legal/privacy requirement. Stop testing and fix before continuing.
- `P1`: AI/PDF generation failure, webhook/email failure, lost consultant evidence, broken admin full-assessment flow. Fix before launch.
- `P2`: Visual defects, copy issues, non-critical admin usability defects. Can be accepted only with Reda approval.
- `P3`: Cosmetic or backlog items. Record separately; do not block unless Reda requests it.

Retest rule: after any code or data fix, rerun the failed flow plus the relevant automated test file. If the fix touches scoring, prompts, payloads, PDF, or public diagnostic security, rerun Gates 1 through 6.

## Evidence Snapshot

- Automated suite: `php artisan test` -> `29 passed, 199 assertions`
- Seeded full-question counts from live SQLite (`u231809987_rab`):
  - `PIR|full|127|2|15`
  - `SIR|full|127|5|17`
- Stage-note verification on the exact build-guide set:
  - `PIR|18`
  - `SIR|5`
  - `ALL|23`
- `P2.F9` stage note exists in the live DB.
- Current D11 pillar name in the live DB: `D11|Service Tooling, CMDB & Knowledge Management`

## Status Labels

- `code-pass`: proven by tests, query, or direct code inspection
- `pending-manual`: needs in-app walkthrough
- `pending-external`: needs logs, inbox, webhook receiver, or another external system
- `reda-review`: requires Reda Boukhiar sign-off
- `blocked`: cannot be honestly marked complete from the current repo state

## Fixed Manual Flows

### Flow A — Public PIR Diagnostic

1. Open `/rapid-consulting`
2. Click `Start Diagnostic`
3. Choose `PIR`
4. Confirm `/rapid-consulting/context` appears before any questions
5. Select a delivery stage and optional regulatory context
6. Continue to `/rapid-consulting/assessment`
7. Complete all questions
8. Confirm the personal-details form appears only after the questions
9. Submit the lead form with consent
10. Open `/rapid-consulting/dashboard`

### Flow B — Public SIR Diagnostic

1. Open `/rapid-consulting`
2. Click `Start Diagnostic`
3. Choose `SIR`
4. Confirm `/rapid-consulting/context` appears before any questions
5. Select a service context and optional regulatory context
6. Continue to `/rapid-consulting/assessment`
7. Complete all questions
8. Confirm the personal-details form appears only after the questions
9. Submit the lead form with consent
10. Open `/rapid-consulting/dashboard`

### Flow C — Admin Framework Verification

1. Sign in as admin
2. Open `/admin/frameworks`
3. Search for `D11`
4. Search for `P2.F9`
5. Spot-check `assessment_questions` records rendered in the framework UI

### Flow D — Admin Full Assessment Scoring

1. Sign in as admin
2. Open `/admin/assessments/create`
3. Create PIR and SIR assessments for both `Tier 1 Rapid` and `Tier 2 Full`
4. Open the scoring screens
5. Enter evidence notes, respondent role, document source, and stakeholder divergence data on Tier 2
6. Save context and question-level responses
7. Open `/admin/assessments/{id}`

### Flow E — Admin AI + PDF

1. From `/admin/assessments/{id}`, click `Generate AI Insights` or `Rebuild AI Insights`
2. Confirm AI content appears on the assessment page
3. Click `Export Report`
4. Open the downloaded PDF
5. Inspect the cover, charts, appendix, and page watermark/footer behavior

### Flow F — External Integration Verification

1. Review Laravel logs / queue logs
2. Review CRM webhook receiver logs
3. Review N8N receiver logs
4. Review the destination mailbox for high-priority alert emails
5. Review the destination mailbox for client PDF emails

## Checklist Matrix

### 1. Platform fixes (Step 2): lead capture order, TYPE_SELECT hidden, stage before Q1, time claim

- Owner: `Dev`
- Code proof: public diagnostic routes and views show stage/context before assessment; landing copy says `under 30 minutes`
- Manual walkthrough: Flow A and Flow B. Confirm stage/context screen before question 1, personal form only after questions, and no numeric scores shown in TYPE_SELECT questions.
- Expected evidence: screenshots of `/rapid-consulting`, `/rapid-consulting/context`, `/rapid-consulting/assessment`, and `/rapid-consulting/personal-form`
- Current status: `pending-manual`

### 2. D11 naming fix in database and both JSON files

- Owner: `Dev`
- Code proof: `AssessmentAiPayloadBuilderTest` passes; live DB returns `D11|Service Tooling, CMDB & Knowledge Management`
- Manual walkthrough: Flow C. Search `D11` and confirm all visible SIR references use the corrected name.
- Expected evidence: framework screenshot and DB/query output
- Current status: `code-pass`, `pending-manual`

### 3. PIR 27 additional questions seeded — COUNT(*) = 127 PIR FULL

- Owner: `Dev`
- Code proof: live DB query returns `PIR|full|127|2|15`
- Manual walkthrough: Flow C. Spot-check several PIR full questions in the admin framework list; use DB count as the authoritative pass condition.
- Expected evidence: query output and framework screenshot
- Current status: `code-pass`

### 4. SIR 43 additional questions seeded — COUNT(*) = 127 SIR FULL

- Owner: `Dev`
- Code proof: live DB query returns `SIR|full|127|5|17`
- Manual walkthrough: Flow C. Spot-check several SIR full questions in the admin framework list; use DB count as the authoritative pass condition.
- Expected evidence: query output and framework screenshot
- Current status: `code-pass`

### 5. P2.F9 in database with stage_note populated — verify with SELECT

- Owner: `Dev`
- Code proof: live DB query returns `P2.F9|<full stage note>`
- Manual walkthrough: Flow C. Search `P2.F9` and open the question details in the admin framework UI.
- Expected evidence: query output and screenshot
- Current status: `code-pass`, `pending-manual`

### 6. 18 PIR stage notes run — SELECT COUNT(*) WHERE stage_note IS NOT NULL = 23

- Owner: `Dev`
- Code proof: targeted query against the exact build-guide question set returns `PIR|18`, and `GuideStageNotesSeederTest` passes
- Manual walkthrough: Flow C. Spot-check PIR stage-note questions such as `P5.F1`, `P6.F5`, `P7.F1`, and `P2.F9`
- Expected evidence: targeted query output and screenshots
- Note: a naive global `stage_note is not null` count is higher in the live DB because many other full-bank questions also carry stage notes
- Current status: `code-pass`, `pending-manual`

### 7. 5 SIR context notes run

- Owner: `Dev`
- Code proof: targeted query against the exact build-guide question set returns `SIR|5`, and `GuideStageNotesSeederTest` passes
- Manual walkthrough: Flow C. Spot-check `D7.F1`, `D3.F1`, `D10.F1`, `D8.F1`, `D11.F1`
- Expected evidence: targeted query output and screenshots
- Current status: `code-pass`, `pending-manual`

### 8. Pillar/domain name lookup in scoring engine — passed in all AI payloads

- Owner: `Dev`
- Code proof: `AssessmentAiPayloadBuilderTest` passes for PIR and SIR name lookup
- Manual walkthrough: Flow D then Flow E. Create PIR and SIR assessments, generate AI, and confirm payload-driven labels appear correctly in downstream output.
- Expected evidence: test pass plus generated output screenshots
- Current status: `code-pass`, `pending-manual`

### 9. SSI formula: `(D2×1.4+D5×1.3)/2.7` — test case `D2=3.5,D5=2.8 = 3.16`

- Owner: `Dev`
- Code proof: `AssessmentIndexCalculatorTest` asserts `SSI = 3.16`
- Manual walkthrough: Flow D with a synthetic SIR assessment if desired; unit test is the authoritative pass condition.
- Expected evidence: test output
- Current status: `code-pass`

### 10. D11 weight `0.9→1.2` and SIR denominator `14.2→14.5`

- Owner: `Dev`
- Code proof: `AssessmentIndexCalculatorTest` passes with the updated SIR math
- Manual walkthrough: Flow D with an SIR assessment spot-check if desired
- Expected evidence: test output
- Current status: `code-pass`

### 11. SMI and SIMI formulas verified against test cases

- Owner: `Dev`
- Code proof: `AssessmentIndexCalculatorTest` asserts `SMI`, `SIMI`, and `smi_simi_delta`
- Manual walkthrough: Flow D with an SIR assessment spot-check if desired
- Expected evidence: test output
- Current status: `code-pass`

### 12. RII formula added — `P2.F9` NOT in `RII`

- Owner: `Dev`
- Code proof: `AssessmentIndexCalculatorTest` includes the `P2.F9` exclusion path and passes
- Manual walkthrough: Flow D with a PIR assessment spot-check if desired
- Expected evidence: test output
- Current status: `code-pass`

### 13. All 11 scoring verification tests pass (Step 7.3)

- Owner: `Dev`
- Code proof: `AssessmentIndexCalculatorTest` passes
- Manual walkthrough: optional Flow D sanity check only
- Expected evidence: test output
- Current status: `code-pass`

### 14. `smi_simi_delta` pre-calculated in SIR payload

- Owner: `Dev`
- Code proof: `AssessmentIndexCalculatorTest` and `AssessmentAiPayloadBuilderTest` both cover it
- Manual walkthrough: Flow D and Flow E with an SIR assessment, then inspect generated output or payload capture
- Expected evidence: test output and generated AI output
- Current status: `code-pass`, `pending-manual`

### 15. Gap 3 confirmed with Reda: portal exists or scope agreed for new build

- Owner: `Dev+Reda`
- Code proof: none; this is an owner confirmation step
- Manual walkthrough: Flow D. Show the current consultant scoring screens and Tier 2 data-entry fields to Reda.
- Expected evidence: Reda confirmation note, email, or sign-off record
- Current status: `reda-review`

### 16. Consultant portal data entry form — all required fields present (Step 8b)

- Owner: `Dev`
- Code proof: admin scoring templates and autosave path support Tier 2 evidence fields
- Manual walkthrough: Flow D on `Tier 2 Full` PIR and SIR. Confirm evidence note, respondent role, document source, stakeholder divergence note, and context fields are visible and saveable.
- Expected evidence: screenshots and a saved assessment record
- Current status: `pending-manual`

### 17. All 6 prompts in `config/ai.php` — correct keys, verbatim paste

- Owner: `Dev`
- Code proof: `AiPromptConfigTest` currently passes against the build-guide markdown source
- Manual walkthrough: not meaningfully UI-testable; validate via config source and generated output only
- Expected evidence: test output and source review
- Current status: `blocked`
- Blocker: `docs/config_ai_prompts.php` conflicts with the current canonical build-guide-backed prompt test, and supervisor confirmation is still needed before changing prompt authority

### 18. Deprecated prompts marked in `config/ai.php`

- Owner: `Dev`
- Code proof: `AiPromptConfigTest` passes and deprecated keys are absent
- Manual walkthrough: not meaningfully UI-testable
- Expected evidence: test output
- Current status: `code-pass`

### 19. ReportService uses tier field for prompt selection

- Owner: `Dev`
- Code proof: `AssessmentAiPayloadBuilderTest` and `AdminGenerateReportPromptTest` pass
- Manual walkthrough: Flow D and Flow E. Create Tier 1 and Tier 2 assessments and generate AI for each.
- Expected evidence: test output and generated report screenshots
- Current status: `code-pass`, `pending-manual`

### 20. [REDA] PIR snapshot test: cover letter uses product name not duration; stage calibration fires for Build stage P7 test; BRI/VRI/DMI from payload

- Owner: `Reda`
- Code proof: public PIR diagnostic flow plus AI output capture
- Manual walkthrough: Flow A with a PIR scenario designed to exercise Build-stage calibration and PIR index output, then hand the generated output to Reda
- Expected evidence: generated PIR snapshot text and Reda sign-off
- Current status: `reda-review`

### 21. [REDA] SIR snapshot test: SSI/SMI/SIMI/BAU-RI from payload; `smi_simi_delta` used; conversion sentence is `service improvement plan`

- Owner: `Reda`
- Code proof: public SIR diagnostic flow plus AI output capture
- Manual walkthrough: Flow B with an SIR scenario designed to exercise `smi_simi_delta`, then hand the generated output to Reda
- Expected evidence: generated SIR snapshot text and Reda sign-off
- Current status: `reda-review`

### 22. Browsershot installed. DomPDF not used for full reports

- Owner: `Dev`
- Code proof: `AdminReportPdfExportTest` passes and `ReportPdfService` uses Browsershot
- Manual walkthrough: Flow E. Export a report from `/admin/assessments/{id}`.
- Expected evidence: downloaded PDF and code inspection
- Current status: `code-pass`, `pending-manual`

### 23. ZDR header on every AI API call — confirmed in server log

- Owner: `Dev`
- Code proof: `AdminGenerateReportPromptTest` and `RapidConsultingSecurityTest` assert the `anthropic-beta: zdr-2024-10-23` header
- Manual walkthrough: Flow F. Inspect outbound request logs or HTTP capture while generating admin AI and public diagnostic AI
- Expected evidence: log lines or HTTP capture
- Current status: `code-pass`, `pending-external`

### 24. Internal CRM lead lifecycle works, lead priority correct

- Owner: `Dev`
- Code proof: `RapidConsultingSecurityTest` and `InternalCrmLifecycleTest` pass
- Manual walkthrough: Flow A or Flow B, then inspect `/admin/leads`. Submit high-risk, medium-risk, and low-risk diagnostics and confirm Hot/Warm/Cold sales status plus High/Medium/Low risk priority.
- Expected evidence: admin lead rows, stored consent fields, score-derived priority/status, booking status, and conversion link to client/assessment
- Current status: `code-pass`, `pending-manual`

### 25. High-priority email to Reda within 30 seconds for score `2.5` test

- Owner: `Dev+Reda`
- Code proof: Step 14 tests prove dispatch, not inbox delivery timing
- Manual walkthrough: Flow A or Flow B with a high-risk run, then Flow F to confirm queued delivery and arrival time
- Expected evidence: queue log timestamp and destination inbox receipt
- Current status: `pending-external`

### 26. `hidden_risk` and `score_anchors` absent from all public API responses

- Owner: `Dev`
- Code proof: `RapidConsultingSecurityTest` and `AdminAssessmentShowTest` pass
- Manual walkthrough: Flow A or Flow B with browser dev tools open; inspect public JSON responses such as `/rapid-consulting/report-status`
- Expected evidence: network capture and test output
- Current status: `code-pass`, `pending-manual`

### 27. `scoring_version 1.0` stamped on every assessment

- Owner: `Dev`
- Code proof: migration/default value exists and PDF tests include `scoring_version`
- Manual walkthrough: Flow D and Flow E. Create a new assessment and confirm the PDF cover stamp includes `Scoring Version 1.0`.
- Expected evidence: DB record and PDF screenshot
- Current status: `code-pass`, `pending-manual`

### 28. Client PDF email received within 30 seconds

- Owner: `Dev+Reda`
- Code proof: no client-facing PDF email delivery flow is discoverable in the current repo
- Manual walkthrough: Flow F only if the feature exists outside the visible codebase; otherwise this item remains failed/blocked
- Expected evidence: outbound mail log and destination inbox receipt
- Current status: `blocked`
- Blocker: no clear implementation of client PDF email delivery is present in the current codebase

### 29. [REDA] PDF: CONFIDENTIAL watermark every page. RAB Proprietary™ on index cards. Chart attribution on all charts. Risk heat map present. RAID summary present (PIR). Root cause section present.

- Owner: `Reda`
- Code proof: `AdminReportPdfExportTest` covers the presence of these elements in rendered report HTML
- Manual walkthrough: Flow E with PIR and SIR exports, then Reda visual review
- Expected evidence: exported PDFs and Reda sign-off
- Current status: `reda-review`

### 30. [REDA] 5 complete test diagnostics reviewed. All scores match manual calculation. Cover letter: no duration, correct product name, `[CONSULTANT TO COMPLETE]` placeholder visible.

- Owner: `Reda`
- Code proof: combined public, admin, scoring, and PDF flows
- Manual walkthrough: run 5 complete diagnostics across Flows A, B, D, and E; compare against manual score calculations and report text expectations
- Expected evidence: 5 saved runs, 5 PDFs, manual calculation sheet, and Reda sign-off
- Current status: `reda-review`

### 31. EU Article 27 GDPR representative appointed before first EU client. Use GDPR-Rep.eu.

- Owner: `Reda`
- Code proof: none in app
- Manual walkthrough: external business/legal confirmation only
- Expected evidence: signed engagement or appointment record
- Current status: `reda-review`

## First Real Blockers

1. `#17` prompt-source authority is unresolved because `docs/config_ai_prompts.php` conflicts with the current build-guide-backed prompt regression test.
2. `#28` client PDF email delivery is not visible in the current repo and cannot be marked complete without either implementation or external confirmation.
3. `#15`, `#20`, `#21`, `#25`, `#29`, `#30`, and `#31` require Reda or external sign-off and cannot be closed from code alone.

## Immediate Next Actions

1. Run Flow A once and capture screenshots for item `#1`, item `#24`, item `#26`, and the public consent/internal CRM path.
2. Run Flow D and Flow E once on a Tier 2 PIR assessment and once on a Tier 2 SIR assessment for items `#16`, `#19`, `#22`, `#27`, and `#29`.
3. Decide prompt authority with the supervisor before touching `config/ai.php`.
4. Confirm whether client PDF email delivery exists outside this repo; if not, treat `#28` as a missing feature rather than a validation task.
