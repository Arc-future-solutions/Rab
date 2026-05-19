# RAB Consulting Services Ltd

## RAB Assessment Platform

Developer Technical Brief

### CRM Module & Assessment Management (Question Bank)

|                |                                                                            |
|----------------|----------------------------------------------------------------------------|
| Document Type  | Technical Brief — Developer Handover                                       |
| Version        | 2.0 — Final                                                                |
| Date           | May 2026                                                                   |
| Owner          | Reda Boukhiar \| RAB Consulting Services Ltd                               |
| Contact        | rboukhiar@rabconsultingservices.com \| +44 7717 544322                     |
| Classification | Confidential — Developer Use Only                                          |
| Platform Stack | Next.js 14 \| TypeScript \| Tailwind CSS \| PostgreSQL \| Prisma \| Vercel |

*This brief is the authoritative specification for two critical platform modules: the CRM integration layer and the Assessment Management back-office (Question Bank). Both modules are mandatory. The platform is not production-ready without them. The new developer must read this document in full before writing a single line of code against either module.*

# 1. Platform Context — What You Are Building

The RAB Assessment Platform is a structured diagnostic and advisory system — not a generic survey tool. It delivers two distinct diagnostic frameworks:

| **Framework**                 | **Code** | **Type**   | **Description**                                                  |
|-------------------------------|----------|------------|------------------------------------------------------------------|
| Programme Intelligence Review | PIR      | 10 Pillars | Assesses programme health, delivery risk and governance maturity |
| Service Intelligence Review   | SIR      | 12 Domains | Assesses IT service management and operational maturity          |

Each framework operates in two modes:

- Snapshot — Free, public-facing, 20–24 questions, lead-generation focused, immediate AI-generated result

- Full Assessment — Consultant-led, 122–126 questions, private back-office, evidence capture, AI-assisted report, human approval before release

Every assessment completion fires a CRM webhook. Every framework's questions, weightings and pillar/domain structure are managed via the Question Bank in the Admin back-office. These are not optional features — they are core to how the platform operates commercially.

# 2. CRM Module — Full Specification

## 2.1 Purpose

The CRM module captures every completed diagnostic as a structured lead record. It enables RAB to triage inbound enquiries by risk level, prioritise follow-up, and track conversion from self-assessment to paid engagement. Every submission — snapshot or full — must feed the CRM.

This is not a third-party CRM integration in Phase 1. It is an internal CRM built into the platform's admin back-office, feeding leads into the /admin/leads dashboard. External CRM export capability (e.g., HubSpot, Salesforce) is a Phase 4 enhancement.

## 2.2 CRM Trigger — When It Fires

The CRM record is created by the server-side webhook at POST /api/webhooks/crm. It fires automatically after both of the following conditions are satisfied:

- Assessment scoring calculation has completed successfully

- Lead data has been saved to the database

> *⚠ The webhook must never fire if consentGiven is false. GDPR compliance is non-negotiable.*
>
> *⚠ On failure: 3 retries with exponential backoff. Log each failure. Trigger an admin alert if all 3 retries fail.*

## 2.3 CRM Webhook Payload — Complete Field Specification

The following payload is sent on every trigger. All fields are mandatory unless explicitly marked optional.

| **Field**                        | **Type**       | **Required** | **Description**                                                    |
|----------------------------------|----------------|--------------|--------------------------------------------------------------------|
| assessmentId                     | string (UUID)  | Yes          | Primary key for the assessment record                              |
| assessmentType                   | string enum    | Yes          | PIR_SNAPSHOT \| PIR_FULL \| SIR_SNAPSHOT \| SIR_FULL               |
| timestamp                        | ISO8601 string | Yes          | UTC timestamp of calculation completion                            |
| lead.name                        | string         | Yes          | Full name from lead capture form                                   |
| lead.email                       | string         | Yes          | Email address — used for all follow-up                             |
| lead.company                     | string         | Yes          | Organisation name                                                  |
| lead.phone                       | string \| null | No           | Optional. E.164 format.                                            |
| consentGiven                     | boolean        | Yes          | Must be true. Webhook never fires if false.                        |
| consentTimestamp                 | ISO8601 string | Yes          | Timestamp of consent checkbox tick                                 |
| overallScore                     | number (2dp)   | Yes          | Weighted overall score                                             |
| ragStatus                        | string enum    | Yes          | Controlled \| AtRisk \| Weak \| ImmediateAction                    |
| actionIndicator                  | string enum    | Yes          | ImmediateActionRequired \| ActionRecommended \| ValidateAndProtect |
| criticalFlag                     | string \| null | No           | E.g. CriticalPillar, GoLiveAlert, NSIProcessAlert. Null if none.   |
| alerts                           | string\[\]     | Yes          | All active alert codes e.g. \[ComplianceAlert, StabilityAlert\]    |
| leadPriority                     | string enum    | Yes          | High \| Medium \| Low — see Section 2.5 for rules                  |
| topThreeInsightAreas             | object\[\]     | Yes          | \[{ pillarOrDomain, score, insightLabel }\] — lowest 3 scores      |
| assessmentContext.type           | string         | Yes          | PIR \| SIR                                                         |
| assessmentContext.deliveryStage  | string \| null | PIR only     | One of the 6 PIR delivery stages                                   |
| assessmentContext.serviceContext | string \| null | SIR only     | One of the 5 SIR service contexts                                  |
| regulatory_context               | string \| null | No           | E.g. fca_uk, dora. Null if standard.                               |
| scoring_version                  | string         | Yes          | Active scoring configuration version at time of assessment         |
| bookingStatus                    | string enum    | Yes          | NotBooked \| BookingRequested \| Booked                            |
| notes                            | string \| null | No           | Consultant notes or assessor observations, if any                  |

## 2.4 High-Priority Alert Rule

If overallScore \< 3.0 OR criticalFlag is set, the system must send an immediate email notification to Reda Boukhiar at rboukhiar@rabconsultingservices.com. This alert must not be queued — it must fire within 30 seconds. Subject line must clearly state HIGH PRIORITY — \[Framework\] — \[Company Name\] — Score: \[X.XX\].

## 2.5 Lead Priority Logic

Lead priority is derived automatically from the assessment result. It is stored against the CRM record and displayed on the leads dashboard. Priority is calculated as follows:

| **Priority** | **Condition**                                 | **Action Required**                                       |
|--------------|-----------------------------------------------|-----------------------------------------------------------|
| HIGH         | overallScore \< 3.0 OR criticalFlag is set    | Immediate email alert to Reda. Follow-up within 24 hours. |
| MEDIUM       | overallScore 3.0–3.49 AND no criticalFlag     | Follow-up within 48 hours. Review full snapshot detail.   |
| LOW          | overallScore 3.5 or above AND no criticalFlag | Standard follow-up within 5 business days.                |

## 2.6 CRM Data Model — Database Tables

The following tables must exist in the database to support the CRM module.

**Table: leads**

| **Field**          | **Type**     | **Notes**                                       |
|--------------------|--------------|-------------------------------------------------|
| id                 | UUID PK      | Auto-generated                                  |
| assessment_id      | UUID FK      | References assessments.id                       |
| framework          | VARCHAR(10)  | PIR \| SIR                                      |
| assessment_type    | VARCHAR(20)  | SNAPSHOT \| FULL                                |
| full_name          | VARCHAR(255) | From lead form                                  |
| email              | VARCHAR(255) | Indexed. Required.                              |
| company_name       | VARCHAR(255) | From lead form                                  |
| phone              | VARCHAR(50)  | Optional. E.164 format.                         |
| overall_score      | DECIMAL(4,2) | Stored to 2dp                                   |
| rag_status         | VARCHAR(30)  | Controlled \| AtRisk \| Weak \| ImmediateAction |
| critical_flag      | VARCHAR(100) | Nullable                                        |
| lead_priority      | VARCHAR(10)  | High \| Medium \| Low                           |
| booking_status     | VARCHAR(30)  | NotBooked \| BookingRequested \| Booked         |
| consent_given      | BOOLEAN      | Must be true to exist in this table             |
| consent_timestamp  | TIMESTAMPTZ  | UTC                                             |
| regulatory_context | VARCHAR(50)  | Nullable. E.g. fca_uk, dora                     |
| scoring_version    | VARCHAR(20)  | Version at time of assessment                   |
| notes              | TEXT         | Nullable. Consultant or admin notes.            |
| created_at         | TIMESTAMPTZ  | UTC. Auto-set on insert.                        |
| updated_at         | TIMESTAMPTZ  | UTC. Auto-updated on change.                    |

## 2.7 Admin Leads Dashboard — Required Columns & Features

The /admin/leads route must display a fully functional leads management table. The following columns are mandatory:

- Date/Time submitted

- Company Name

- Contact Name

- Email

- Framework (PIR / SIR)

- Assessment Type (Snapshot / Full)

- Overall Score

- RAG Status — colour coded (Green / Amber / Red / Dark Red)

- Lead Priority — colour coded badge (High / Medium / Low)

- Critical Flag — shown if set, blank if null

- Booking Status — dropdown editable inline

- Notes — editable inline

- Actions — View Full Assessment \| Convert to Client \| Export PDF

Filtering must be available by: Framework, Priority, RAG Status, Date Range, Booking Status. Sorting must be available on all columns. Export to CSV must be available on the full filtered set.

> *⚠ No lead record should be editable by Client Viewer role. Notes and Booking Status are editable by Assessor and Admin only.*

# 3. Assessment Management — Question Bank

## 3.1 Purpose and Background

The Assessment Management section is the admin-controlled question bank. This is a critical requirement that was present in the previous developer implementation and must be fully reinstated and improved in this build.

In the previous build, Reda had a dedicated section in the admin back-office — labelled 'Assessments' or 'Question Bank' — which allowed the following actions on every question across both frameworks:

- View all questions by framework and pillar/domain

- Edit question wording

- Add new questions to any pillar or domain

- Delete questions

- Adjust the weighting of individual questions

- Reorder questions within a pillar or domain

This section must be rebuilt with the same capability, improved usability, and full version control. It is not optional — without it, the framework cannot be maintained, updated or evolved without developer intervention.

> *⚠ DO NOT hardcode questions in the front-end or back-end. Every question must live in the database. The admin interface is the only legitimate route to changing question content or structure.*

## 3.2 Admin Route Structure — Question Bank

| **Route**                              | **Purpose**                                                 |
|----------------------------------------|-------------------------------------------------------------|
| /admin/settings/questions              | Landing page — select Framework and Assessment Type         |
| /admin/settings/questions/pir/snapshot | PIR Snapshot question bank (20 questions)                   |
| /admin/settings/questions/pir/full     | PIR Full question bank (124 / 126 FCA questions)            |
| /admin/settings/questions/sir/snapshot | SIR Snapshot question bank (24 questions)                   |
| /admin/settings/questions/sir/full     | SIR Full question bank (122 / 124 FCA / 125 DORA questions) |

## 3.3 Question Bank Interface Requirements

The question bank interface must present questions grouped by Pillar (PIR) or Domain (SIR). For each group, the consultant must be able to see:

- Pillar or domain code and name

- Current weighting assigned to this pillar/domain

- Total number of questions in this pillar/domain

- Expandable list of all questions within the group

For each individual question, the following controls must be available inline:

| **Control**        | **Description**                                  | **Validation**                                      |
|--------------------|--------------------------------------------------|-----------------------------------------------------|
| Question Code      | Read-only reference ID. E.g. P1.F1, D3.F4        | Non-editable once published                         |
| Question Text      | Full editable question wording                   | Min 20 chars. Required.                             |
| Help Text          | Optional explanatory note shown to respondent    | Max 500 chars                                       |
| Evidence Guidance  | Guidance on what evidence supports the answer    | Max 500 chars                                       |
| Question Type      | TYPE_SCALE (1–5) \| TYPE_SELECT \| TYPE_TEXT     | Cannot change type on published question            |
| Weight Override    | Individual question weighting within the pillar  | Decimal. Must contribute correctly to pillar total. |
| Order Index        | Display order within the pillar/domain           | Integer. Drag-and-drop reorder supported.           |
| Is Compliance      | Flags question as compliance-specific            | Boolean toggle                                      |
| Is Hybrid          | Flags question as stage-conditional              | Boolean toggle                                      |
| Stage Note         | Shown for hybrid questions at specific stages    | Nullable text                                       |
| Active / Published | Controls whether question appears in assessments | Toggle — unpublish vs delete                        |
| Score Anchors      | 1–5 descriptive anchors shown on scale questions | Array of 5 strings. Required for TYPE_SCALE.        |

## 3.4 Question Bank Database Schema

All questions must be stored in the questions table. No question logic lives outside the database.

| **Field**         | **Type**       | **Notes**                                                          |
|-------------------|----------------|--------------------------------------------------------------------|
| id                | VARCHAR(20) PK | Question code. E.g. P1.F1, D3.F4. Immutable once published.        |
| framework         | VARCHAR(10)    | PIR \| SIR                                                         |
| assessment_type   | VARCHAR(20)    | SNAPSHOT \| FULL                                                   |
| pillar_code       | VARCHAR(10)    | E.g. PP1, PP2, DD1, DD12                                           |
| question_type     | INTEGER        | 1 = TYPE_SCALE \| 2 = TYPE_SELECT \| 3 = TYPE_TEXT                 |
| question_text     | TEXT           | Full question wording. Required. Min 20 chars.                     |
| help_text         | TEXT           | Nullable. Shown to respondent as guidance.                         |
| evidence_guidance | TEXT           | Nullable. Guidance for full assessment evidence capture.           |
| display_type      | VARCHAR(20)    | TYPE_SCALE \| TYPE_SELECT \| TYPE_TEXT                             |
| select_options    | JSONB          | Nullable. Required if display_type = TYPE_SELECT.                  |
| score_anchors     | JSONB          | Array of 5 anchor labels for TYPE_SCALE questions.                 |
| weight_override   | DECIMAL(4,2)   | Nullable. Overrides pillar-level default weight for this question. |
| order_index       | INTEGER        | Display order within pillar. Managed via drag-and-drop in admin.   |
| is_compliance     | BOOLEAN        | True for FCA/DORA specific questions.                              |
| is_hybrid         | BOOLEAN        | True for stage-conditional questions.                              |
| hybrid_context    | TEXT           | Nullable. Describes conditions for hybrid display.                 |
| stage_note        | TEXT           | Nullable. Shown as amber contextual note for hybrid questions.     |
| version           | INTEGER        | Incremented on every edit. Starts at 1.                            |
| published         | BOOLEAN        | False = draft. True = active in assessments.                       |
| created_at        | TIMESTAMPTZ    | UTC. Auto-set.                                                     |
| updated_at        | TIMESTAMPTZ    | UTC. Auto-updated.                                                 |
| created_by        | UUID FK        | References users.id                                                |
| updated_by        | UUID FK        | References users.id                                                |

## 3.5 Pillar and Domain Weight Management

Weighting is managed at two levels and must be configurable from the admin back-office. Both levels must be editable without developer involvement.

| **Level**                | **What It Controls**                                                           | **Where Configured**                                           |
|--------------------------|--------------------------------------------------------------------------------|----------------------------------------------------------------|
| Pillar / Domain Weight   | The relative weight of the pillar or domain within the overall framework score | /admin/settings/frameworks — edit pillar or domain row         |
| Question Weight Override | The relative contribution of a specific question within its pillar or domain   | /admin/settings/questions — weight_override field per question |

Current framework denominators for reference:

- PIR denominator: 12.1 (10 pillars, weights sum to 12.1)

- SIR denominator: 14.5 (12 domains, weights sum to 14.5 — D11 updated to 1.2)

> *⚠ Any change to a pillar weight or question weight must increment the scoring_version. The new version must be stored against all assessments created after the change. Historical assessments must remain reproducible against their original version.*

## 3.6 Version Control and Audit Requirements

All changes to the question bank must be version-controlled and audited. The following rules are non-negotiable:

- Every edit to a question increments its version field

- Every edit is written to the audit_log table with old_value_json and new_value_json

- A new assessment always binds to the currently published version of the framework

- Historical reports remain reproducible against the version that was active at time of completion

- Deleting a question is not permitted once it has been used in a completed assessment — use published = false to retire it instead

- New questions added to a live framework create a new framework version — existing in-progress assessments are not affected

## 3.7 Admin Capabilities Summary — Quick Reference

The following table summarises everything an Admin role must be able to do in the Question Bank section without developer support:

| **Capability**                             | **Status** | **Notes**                                                           |
|--------------------------------------------|------------|---------------------------------------------------------------------|
| View all questions by framework and pillar | Required   | Default view on Question Bank page                                  |
| Edit question wording                      | Required   | Triggers version increment and audit log entry                      |
| Edit help text and evidence guidance       | Required   | Inline edit                                                         |
| Edit score anchors (1–5 labels)            | Required   | Per question. Displayed to respondents.                             |
| Add a new question to any pillar           | Required   | Assigns next available order_index. Starts as draft.                |
| Publish / unpublish a question             | Required   | Toggle published flag. Unpublished = invisible to respondents.      |
| Delete a question (unused only)            | Required   | Hard delete permitted only if never used in a completed assessment. |
| Adjust question weight override            | Required   | Admin must see impact on pillar total in real-time.                 |
| Reorder questions via drag-and-drop        | Required   | Updates order_index. Audited.                                       |
| Adjust pillar/domain weights               | Required   | From /admin/settings/frameworks page                                |
| Mark question as compliance-specific       | Required   | is_compliance toggle. Controls FCA/DORA visibility.                 |
| Mark question as hybrid/conditional        | Required   | is_hybrid toggle + stage_note field.                                |
| View full audit trail of changes           | Required   | /admin/settings/audit — filterable by entity and date               |
| Export question bank to CSV                | Required   | For backup and offline review.                                      |

# 4. Pillar and Domain Management

## 4.1 Purpose

Pillars (PIR) and Domains (SIR) are the structural backbone of both frameworks. They group questions, carry weights, and drive the scoring engine, heatmaps, indices and AI narrative. The admin back-office must allow Reda to add, edit, reorder and deactivate pillars and domains without any developer involvement.

This is a first-class requirement. The frameworks will evolve. New service contexts will emerge. The platform must accommodate that evolution without touching the codebase.

## 4.2 Framework Version Control — Critical Rule

Any structural change to a pillar or domain — adding one, removing one, changing a weight, renaming one — constitutes a framework version change. This is fundamentally different from editing a question.

| **Change Type**                          | **Version Impact**                 | **Effect on Existing Assessments**                                                                |
|------------------------------------------|------------------------------------|---------------------------------------------------------------------------------------------------|
| Edit question wording / anchors          | Question version increments        | No impact on in-progress or completed assessments                                                 |
| Add / remove / reorder a question        | Framework minor version increments | In-progress assessments unaffected. New assessments use new version.                              |
| Add / remove / rename a pillar or domain | Framework major version increments | In-progress assessments unaffected. New assessments use new version. Scoring engine recalibrates. |
| Change a pillar or domain weight         | Framework major version increments | Denominator recalculates. All index formulas re-evaluate against new version only.                |

> *⚠ Historical assessments and their reports must always remain reproducible against the framework version active at the time of completion. This is non-negotiable — it is a client assurance and audit requirement.*

## 4.3 Current Framework Structure — Reference

The developer must seed the database with the following framework structures. These are the live production configurations. Do not alter weights, codes or names without written confirmation from Reda.

**PIR — Programme Intelligence Review**

| **Code** | **Pillar No.** | **Pillar Name**              | **Weight** |
|----------|----------------|------------------------------|------------|
| PP1      | P1             | Governance & Decision-Making | 1.3        |
| PP2      | P2             | Planning & Delivery Control  | 1.3        |
| PP3      | P3             | Business Alignment & Value   | 1.2        |
| PP4      | P4             | Change, Training & Adoption  | 1.2        |
| PP5      | P5             | Data Readiness & Migration   | 1.2        |
| PP6      | P6             | Solution & Process Fit       | 1.1        |
| PP7      | P7             | Cutover & Go-Live Readiness  | 1.3        |
| PP8      | P8             | Risk, Compliance & Security  | 1.2        |
| PP9      | P9             | Supplier & SI Management     | 1.1        |
| PP10     | P10            | Post Go-Live Stabilisation   | 1.2        |
|          |                | TOTAL DENOMINATOR            | 12.1       |

**SIR — Service Intelligence Review**

| **Code** | **Domain No.** | **Domain Name**                              | **Weight** |
|----------|----------------|----------------------------------------------|------------|
| DD1      | D1             | Service Governance & Ownership               | 1.3        |
| DD2      | D2             | Incident & Problem Management                | 1.3        |
| DD3      | D3             | Service Request & Fulfilment                 | 1.1        |
| DD4      | D4             | Change & Release Management                  | 1.2        |
| DD5      | D5             | SLA, Reporting & Service Review              | 1.2        |
| DD6      | D6             | Capacity, Availability & Performance         | 1.2        |
| DD7      | D7             | Service Transition & BAU Readiness           | 1.1        |
| DD8      | D8             | Business Continuity & DR                     | 1.1        |
| DD9      | D9             | Vendor & Third-Party Management              | 1.1        |
| DD10     | D10            | Security & Compliance Posture                | 1.2        |
| DD11     | D11            | Service Tooling, CMDB & Knowledge Management | 1.2        |
| DD12     | D12            | Service Intelligence & CSI                   | 1.1        |
|          |                | TOTAL DENOMINATOR                            | 14.5       |

> *⚠ D11 weight was updated to 1.2 from 0.9. This is the confirmed live value. Do not revert.*

## 4.4 Admin Route — Frameworks Management

The frameworks management page lives at /admin/settings/frameworks. It is accessible to Admin role only. It must display both PIR and SIR in tabbed or toggled views, with full pillar/domain detail for each.

## 4.5 Pillar / Domain Fields — Full Specification

Each pillar or domain has the following configurable fields. All must be manageable from the admin interface without code changes.

| **Field**          | **Type**     | **Description & Validation**                                                                  |
|--------------------|--------------|-----------------------------------------------------------------------------------------------|
| pillar_code        | VARCHAR(10)  | Unique code per framework. E.g. PP1, DD4. Immutable once published. Set on creation.          |
| pillar_name        | VARCHAR(255) | Full display name. Required. Shown on dashboard, heatmap and report.                          |
| pillar_description | TEXT         | Nullable. Internal description. Not shown to respondents.                                     |
| display_order      | INTEGER      | Controls sort order on dashboard and report. Drag-and-drop editable.                          |
| weight             | DECIMAL(4,2) | Relative weight in the scoring formula. Must be confirmed against denominator before saving.  |
| risk_text          | TEXT         | Shown in results when this pillar/domain scores below threshold. Required.                    |
| first_action_text  | TEXT         | The recommended first action for this pillar/domain when at risk. Required.                   |
| active_flag        | BOOLEAN      | True = included in assessments. False = hidden from respondents and scoring. Does not delete. |
| framework_version  | VARCHAR(20)  | Increments on any structural change. Bound to all new assessments from this point.            |

## 4.6 Adding a New Pillar or Domain — Step-by-Step Rules

The following rules govern how a new pillar or domain is added. The developer must enforce these in the UI and the API — not leave them to the admin's discretion.

- Step 1: Admin enters pillar/domain name, code, description, risk text, first action text, and initial weight

- Step 2: System calculates the new denominator total in real-time and displays it before saving — e.g. 'New denominator will be 13.3. Confirm?'

- Step 3: Admin confirms. System saves the new pillar as active_flag = false (inactive/draft) and increments framework_version

- Step 4: Admin adds questions to the new pillar via the Question Bank — all start as published = false

- Step 5: Admin reviews, publishes questions, then activates the pillar by setting active_flag = true

- Step 6: From the next assessment created, the new pillar appears in the assessment flow and scoring engine

- Step 7: All prior completed assessments remain on their original framework version — no retroactive scoring changes

> *⚠ The system must prevent activating a new pillar that has zero published questions. Enforce this at API level, not just UI level.*

## 4.7 Removing or Deactivating a Pillar or Domain

A pillar or domain cannot be hard-deleted if it has been used in any completed assessment. The correct mechanism is deactivation via active_flag = false. The following rules apply:

- Setting active_flag = false hides the pillar from all new assessments immediately

- Existing in-progress assessments that already include this pillar continue to completion unaffected

- Completed assessments and their reports retain the full pillar data permanently

- Hard delete is only permitted by an Admin where the pillar has zero completed assessments against it

- Any deactivation must be logged to audit_log with the admin's user ID and a timestamp

## 4.8 Weight Recalculation — Denominator Display

The admin interface must always show the current denominator total for each framework, and recalculate it dynamically when any pillar weight is adjusted. The admin must be able to see the impact of a weight change before confirming it. The following must be visible at all times on the frameworks management page:

- Current denominator total (e.g. PIR: 12.1 \| SIR: 14.5)

- Proposed new denominator total if a weight change is pending confirmation

- Variance from current denominator — highlighted in amber if it changes

- Warning if the proposed denominator differs from the expected baseline by more than 10%

## 4.9 Admin Capabilities Summary — Pillar and Domain Management

| **Capability**                                         | **Status** | **Notes**                                                                               |
|--------------------------------------------------------|------------|-----------------------------------------------------------------------------------------|
| View all pillars and domains by framework              | Required   | Both PIR and SIR. Tabbed view.                                                          |
| Edit pillar/domain name and description                | Required   | Triggers framework version increment                                                    |
| Edit risk text and first action text                   | Required   | Used in report output and AI prompts                                                    |
| Adjust pillar/domain weight                            | Required   | Denominator recalculates in real-time. Confirm before save.                             |
| Reorder pillars/domains via drag-and-drop              | Required   | Updates display_order. Audited.                                                         |
| Add a new pillar or domain                             | Required   | Starts inactive. Must have questions before activation.                                 |
| Activate / deactivate a pillar or domain               | Required   | active_flag toggle. Audited. Cannot deactivate if in-progress assessments depend on it. |
| Hard delete a pillar or domain                         | Required   | Only permitted if zero completed assessments exist against it.                          |
| View framework version history                         | Required   | Full changelog — what changed, when, by whom.                                           |
| View current denominator and impact of pending changes | Required   | Displayed live. Amber warning if variance \> 10%.                                       |
| Export framework structure to CSV                      | Required   | Full pillar/domain list with weights, question counts, version.                         |

# 5. Security and Access Control

The CRM module, Question Bank and Frameworks Management section are all restricted to authenticated users. Access is governed by role-based access control (RBAC). The following permissions apply:

| **Capability**                             | **Admin** | **Assessor** | **Reviewer** | **Client Viewer** |
|--------------------------------------------|-----------|--------------|--------------|-------------------|
| View CRM Leads Dashboard                   | Yes       | Yes          | Yes          | No                |
| Edit Lead Notes / Booking Status           | Yes       | Yes          | No           | No                |
| Delete Lead Records                        | Yes       | No           | No           | No                |
| View Question Bank                         | Yes       | Yes          | No           | No                |
| Edit / Add / Delete Questions              | Yes       | No           | No           | No                |
| Adjust Question Weight Override            | Yes       | No           | No           | No                |
| View Frameworks Management                 | Yes       | Yes          | No           | No                |
| Add / Edit / Deactivate Pillars or Domains | Yes       | No           | No           | No                |
| Adjust Pillar / Domain Weights             | Yes       | No           | No           | No                |
| View Framework Version History             | Yes       | Yes          | No           | No                |
| View Audit Trail                           | Yes       | No           | No           | No                |

> *⚠ All write operations — add, edit, delete, weight change, publish toggle, activate/deactivate — must be written to audit_log with entity, old value, new value, user ID and timestamp. No exceptions.*

# 6. Acceptance Criteria

The following acceptance criteria must all pass before any module is considered complete. These are minimum requirements — not aspirational targets.

**CRM Module**

- CRM webhook fires automatically after every completed assessment (snapshot and full)

- Webhook does not fire if consentGiven is false

- Retry logic fires on failure: 3 retries with exponential backoff, failure alert if all 3 fail

- All payload fields are populated correctly per the specification in Section 2.3

- High-priority email alert received by Reda within 30 seconds when overallScore \< 3.0 or criticalFlag is set

- Lead priority (High / Medium / Low) calculated correctly per the rules in Section 2.5

- Lead record created in database with all required fields populated

- /admin/leads displays all leads with correct columns, RAG and priority colour coding

- Booking status and notes are editable inline without page reload

- Filters work correctly for Framework, Priority, RAG Status, Date Range and Booking Status

- CSV export of filtered lead set produces correct output

**Assessment Management — Question Bank**

- All questions load from the database — no hardcoded question content anywhere in the codebase

- Admin can view all questions grouped by pillar/domain for all four assessment types

- Admin can edit question wording, help text, evidence guidance and score anchors

- Admin can add a new question to any pillar or domain — it starts in draft/unpublished state

- Admin can publish and unpublish questions — unpublished questions do not appear in assessments

- Admin can adjust question weight override — pillar total recalculates in real-time

- Admin can reorder questions via drag-and-drop — order_index updates correctly

- Admin can delete a question only if it has never been used in a completed assessment

- Every question edit increments the question's version field

- Every question edit is recorded in audit_log with old and new values, timestamp and user

- Scoring version is stored on every new assessment at time of creation

- Historical assessment results remain reproducible against their original scoring version

- Question bank exports correctly to CSV

**Pillar and Domain Management**

- Admin can view all pillars and domains for PIR and SIR from /admin/settings/frameworks

- Admin can add a new pillar or domain — it starts as inactive with no published questions

- System prevents activation of a new pillar/domain that has zero published questions

- Admin can edit pillar name, description, risk text, first action text and weight

- Admin can reorder pillars/domains via drag-and-drop — display_order updates correctly

- Admin can deactivate a pillar/domain — it disappears from all new assessments immediately

- Admin cannot hard-delete a pillar/domain that has been used in a completed assessment

- Every structural change increments the framework version — new assessments bind to the new version

- Current denominator total is displayed live and recalculates dynamically on any weight change

- Amber warning is shown if proposed denominator change exceeds 10% variance

- Framework version history is viewable — full changelog of what changed, when, by whom

- Every pillar/domain change is written to audit_log

- Framework structure exports correctly to CSV

# 7. Contact and Escalation

If there is any ambiguity in this specification, do not make assumptions and do not change behaviour without agreement. Raise the specific point of ambiguity in writing with a proposed resolution and wait for confirmation before proceeding.

| **Contact** | **Details**                         |
|-------------|-------------------------------------|
| Owner       | Reda Boukhiar                       |
| Company     | RAB Consulting Services Ltd         |
| Email       | rboukhiar@rabconsultingservices.com |
| Phone (UK)  | +44 7717 544322                     |
| Phone (MA)  | +212 670 914865                     |
| Company No. | 13683901 \| VAT: GB394822071        |

*© 2026 RAB Consulting Services Ltd. All methodology, question frameworks, indices and scoring are proprietary intellectual property. Confidential — Developer Use Only.*
