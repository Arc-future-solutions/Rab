**RAB CONSULTING SERVICES LTD**

# RAB PLATFORM

**Developer Final Build Guide — v5.0**

**Complete · Final · Definitive — Do Not Request a v6**

| Version | v5.0 — Final — May 2026 |
| --- | --- |
| Owner | Reda Boukhiar — RAB Consulting Services Ltd |
| Contact | rboukhiar@rabconsultingservices.com \| +44 7717 544322 |
| Files in this package | RAB_Developer_Final_Build_Guide_v5.docx · pir_additional_questions_final.json · sir_additional_questions.json |
| Stack | Next.js 14 · TypeScript · PostgreSQL · Prisma · Laravel · Vercel |
| PDF generation | Browsershot (Spatie) — NOT DomPDF. See Step 11b. |
| Prompts | 6 complete prompts. Paste verbatim into config/ai.php. |
| PIR questions | pir_additional_questions_final.json — 27 questions including P2.F9 |
| SIR questions | sir_additional_questions.json — 43 questions including 5 hybrid |

Section 0 is mandatory reading before starting. One question must be answered before implementation begins. Read it now.

# 0. Handover Brief — Read This Before Anything Else

**What You Are Receiving**

| File | What It Is | Action |
| --- | --- | --- |
| RAB_Developer_Final_Build_Guide_v5.docx | This document. Complete build instructions. | Read Section 0 first. Then execute steps 1–16 in order. |
| pir_additional_questions_final.json | 27 additional PIR questions including P2.F9 (Change Control). | Seed per Step 5. Do not modify. |
| sir_additional_questions.json | 43 additional SIR questions including 5 hybrid (FCA + DORA). | Fix D11 naming in file first (Step 3), then seed per Step 5. |

**One Question You Must Answer Before Starting**

Do not begin implementation until you have answered this question and confirmed the answer with Reda Boukhiar.

| The question | Does the RAB platform currently have a consultant portal with a data entry form where the consultant enters question-by-question evidence notes, respondent roles, and pillar/domain summaries for a full assessment — or does that form need to be built from scratch? |
| --- | --- |
| Why it matters | The full report AI payload requires consultant-entered data fields beyond the question scores. If the portal form does not exist, this is a significant additional build (3–5 days). If it exists, it needs extending with new fields. You cannot build the full report generation without this. |
| What to confirm with Reda | (a) Does the portal exist? (b) If yes — share a screenshot or describe the current fields so the extension is scoped correctly. (c) If no — confirm scope and timeline before starting Step 9 (full report prompts). |
| Where this is specified | Section 8b of this document — Consultant Portal UI. Read it fully before confirming with Reda. |

**Implementation Order**

| Order | Steps | What It Covers | Estimated Time |
| --- | --- | --- | --- |
| First | Steps 1–3 | Platform fixes, database schema, D11 naming fix | 2–4 hours |
| Second | Steps 4–6 | Lookup object, question seeding, stage notes | 2–3 hours |
| Third | Step 7 | Scoring engine updates, verification tests | 2–3 hours |
| Fourth — CONFIRM GAP 3 FIRST | Steps 8–8b | AI payload structure + consultant portal UI | Depends on portal state — confirm with Reda first |
| Fifth | Step 9 | All 6 AI prompts in config/ai.php | 1 hour — paste verbatim |
| Sixth | Step 10 | ReportService prompt selection | 30 minutes |
| Seventh | Steps 11–13 | PDF report structure, design, visuals, appendix | 3–5 days — most complex build |
| Eighth | Steps 14–15 | CRM webhook, security checklist | 1–2 hours |
| Final | Step 16 | Launch checklist — 31 items. All must pass. | 1 day with Reda sign-off on [REDA] items |

**Contact for [REDA] Review Items**

| Reda Boukhiar | rboukhiar@rabconsultingservices.com \| +44 7717 544322 |
| --- | --- |
| [REDA] items in launch checklist | Items 18–20, 28–30. These require Reda to personally review AI output quality and PDF visual design before any client-facing test. Do not mark them complete without Reda confirmation. |
| What [REDA] items cover | Cover letter language, stage calibration, British English in AI output, watermarks on every page, confidence badges, risk heat map, RAID summary, score accuracy. |

# 1. Build Status

| Item | Status | Action |
| --- | --- | --- |
| Platform live at rabconsulting.uk | ✅ Done | Confirm 4 fixes — Step 2 |
| Legal pages | ✅ Done | Insert ICO ZB number on receipt |
| PIR snapshot — 20 questions seeded | ✅ Done | Stage notes on 18 questions — Step 6 |
| SIR snapshot — 24 questions seeded | ✅ Done | D11 naming fix — Step 3 |
| PIR full — 100 questions seeded | ⚠️ Partial | Seed 27 additional from pir_additional_questions_final.json — Step 5 |
| SIR full — 84 questions seeded | ⚠️ Partial | Fix D11 naming in file first, then seed 43 from sir_additional_questions.json — Step 5 |
| Scoring engine — PIR | ✅ Done | Add RII formula — Step 7 |
| Scoring engine — SIR | ⚠️ Fix required | D11: 0.9→1.2 · Denominator: 14.2→14.5 · SSI formula — Step 7 |
| AI prompts in config/ai.php | ⚠️ Replace all | 6 complete prompts — Step 9. Old prompts deprecated. |
| Consultant portal data entry form | ⚠️ Confirm first | Answer question in Section 0 before starting Step 8b |
| PDF report templates | ❌ Not built | Steps 11–13. Use Browsershot — NOT DomPDF. |
| Visual elements — charts, heat maps | ❌ Not built | Step 12 |
| CRM webhook | ❌ Not built | Step 14 |
| Appendix generation | ❌ Not built | Step 13 |

# 2. Platform Fixes — Do These First

| Fix | Requirement | Pass Condition |
| --- | --- | --- |
| Lead capture after questions | Lead form appears AFTER all questions, BEFORE results. Never before questions. | Complete test diagnostic. Confirm lead form appears post-questions. |
| TYPE_SELECT numbers hidden | display_type TYPE_SELECT: respondent sees 5 descriptions only. Score numbers never visible on screen. | Complete a TYPE_SELECT question. Confirm no numbers visible. |
| Stage/context selection BEFORE question 1 | Delivery stage (PIR) or service context (SIR) selected on a dedicated screen before question 1. | Start new PIR. Stage selection screen appears before Q1. |
| Time claim | All copy reads "under 30 minutes". No specific minute claim anywhere. | Check landing page, results screen, all emails. |

# 3. Database Changes and Naming Fixes

## 3.1 Schema

```sql
ALTER TABLE questions ADD COLUMN IF NOT EXISTS hybrid_context VARCHAR(50) NULL DEFAULT NULL;
ALTER TABLE questions ADD COLUMN IF NOT EXISTS stage_note TEXT NULL;
ALTER TABLE questions ADD COLUMN IF NOT EXISTS is_hybrid BOOLEAN DEFAULT FALSE;
ALTER TABLE questions ADD COLUMN IF NOT EXISTS is_compliance BOOLEAN DEFAULT FALSE;
ALTER TABLE assessments ADD COLUMN IF NOT EXISTS scoring_version TEXT NOT NULL DEFAULT '1.0';
ALTER TABLE assessments ADD COLUMN IF NOT EXISTS regulatory_context TEXT NULL;
-- Values: NULL|'fca_uk'|'dora_eu'|'nhs_cqc'|'public_sector'|'gdpr_only'
ALTER TABLE assessments ADD COLUMN IF NOT EXISTS service_context TEXT NULL;
-- Values (SIR): 'NSI'|'Established'|'UnderPressure'|'Transformation'|'LegacyPreRetirement'
ALTER TABLE assessments ADD COLUMN IF NOT EXISTS delivery_stage TEXT NULL;
-- Values (PIR): 'Mobilisation'|'Design'|'Build'|'Test'|'Cutover'|'PostGoLive'
ALTER TABLE question_responses ADD COLUMN IF NOT EXISTS confidence_level TEXT DEFAULT 'Medium';
ALTER TABLE question_responses ADD COLUMN IF NOT EXISTS respondent_role TEXT;
ALTER TABLE question_responses ADD COLUMN IF NOT EXISTS document_source TEXT;
ALTER TABLE question_responses ADD COLUMN IF NOT EXISTS stakeholder_divergence_note TEXT;
CREATE INDEX IF NOT EXISTS idx_questions_hybrid ON questions(framework,assessment_type,is_hybrid,hybrid_context);
```

## 3.2 D11 Naming Fix — CRITICAL

Current name in all SIR data: "Automation, Tooling & Knowledge". Correct: "Service Tooling, CMDB & Knowledge Management". Fix database AND both JSON files before seeding.

```sql
UPDATE questions SET pillar_code='DD11 — Service Tooling, CMDB & Knowledge Management'
  WHERE framework='SIR' AND pillar_code LIKE '%DD11%';
-- Verify: SELECT DISTINCT pillar_code FROM questions WHERE framework='SIR' AND pillar_code LIKE '%11%';
-- Also: global find-and-replace in sir_additional_questions.json:
-- Find: "DD11 — Automation, Tooling & Knowledge"
-- Replace: "DD11 — Service Tooling, CMDB & Knowledge Management"
```

# 4. Pillar and Domain Names Lookup — Required in Every AI Payload

```ts
// lib/lookups/pillar-names.ts
export const PIR_PILLAR_NAMES = {
```

**PP1:"P1 — Governance & Decision-Making",**

**PP2:"P2 — Planning, Stage Gates & Delivery Control",**

**PP3:"P3 — Business Alignment, Value & Financial Control",**

**PP4:"P4 — Change Management, Training & Adoption",**

**PP5:"P5 — Data Readiness, Migration & GDPR",**

**PP6:"P6 — Solution, Process Fit & UAT",**

**PP7:"P7 — Cutover, Go-Live, Decommissioning & Archiving",**

**PP8:"P8 — Delivery Capability, Security & RACI",**

**PP9:"P9 — Operational, Automation Readiness & Data Archiving",**

**PP10:"P10 — Digital & Transformation Maturity",**

```ts
};
export const SIR_DOMAIN_NAMES = {
```

**DD1:"D1 — Service Governance & Ownership",**

**DD2:"D2 — Incident & Major Incident Management",**

**DD3:"D3 — Service Request Management",**

**DD4:"D4 — Problem Management",**

**DD5:"D5 — Change & Release Management",**

**DD6:"D6 — Service Performance, SLA & Reporting",**

**DD7:"D7 — Service Transition & BAU Readiness",**

**DD8:"D8 — Service Operations & Support Model",**

**DD9:"D9 — Supplier & Vendor Management",**

**DD10:"D10 — Operational Resilience & Continuity",**

**DD11:"D11 — Service Tooling, CMDB & Knowledge Management",**

**DD12:"D12 — Service Intelligence & Continuous Value",**

```ts
};
```

# 5. Question Seeding

| File | Steps | Result |
| --- | --- | --- |
| pir_additional_questions_final.json | Seed all 27 records. Includes P2.F9 (Change Control). No modifications needed. | PIR Full total: 127 (100 + 27) |
| sir_additional_questions.json | Apply D11 naming fix to file first (Step 3). Then seed all 43. | SIR Full total: 127 (84 + 43) |

```sql
-- Verify after seeding:
SELECT framework,assessment_type,COUNT(*) total,
```

**SUM(CASE WHEN is_hybrid THEN 1 ELSE 0 END) hybrid,**

**SUM(CASE WHEN is_compliance THEN 1 ELSE 0 END) compliance_q**

```sql
FROM questions WHERE assessment_type='FULL' GROUP BY framework,assessment_type;
-- Expected PIR FULL: 127 total, 2 hybrid, 15 compliance
-- Expected SIR FULL: 127 total, 5 hybrid, 17 compliance
```

# 6. Stage Note UPDATE Statements — Run All 23

Run before testing AI prompts. 18 PIR (includes P2.F9) + 5 SIR = 23 total.

## 6.1 PIR — 18 Statements

```sql
UPDATE questions SET stage_note='At Design: score whether data migration strategy exists as a plan. At Build: strategy is being executed. At Test: mock migrations completed. At Cutover: production-readiness confirmed.' WHERE id='P5.F1' AND framework='PIR';
UPDATE questions SET stage_note='At Design: data owners should be identified. At Build/Test: owners actively engaged in data quality management.' WHERE id='P5.F2' AND framework='PIR';
UPDATE questions SET stage_note='At Design: data quality assessment should have commenced. At Build: profiling complete. At Test/Cutover: remediation evidenced.' WHERE id='P5.F3' AND framework='PIR';
UPDATE questions SET stage_note='At Design: cleansing strategy defined. At Build: cleansing underway. At Test: substantially complete.' WHERE id='P5.F4' AND framework='PIR';
UPDATE questions SET stage_note='Not expected at Design. At Build: first mock planned. At Test: at least one mock completed. At Cutover: multiple rehearsals with documented outcomes expected.' WHERE id='P5.F5' AND framework='PIR';
UPDATE questions SET stage_note='Not expected at Design. At Build: reconciliation approach defined. At Test: reconciliation tested in mock migrations.' WHERE id='P5.F6' AND framework='PIR';
UPDATE questions SET stage_note='Not expected before Build. At Test/Cutover: active business validation expected.' WHERE id='P5.F7' AND framework='PIR';
UPDATE questions SET stage_note='At Design: key decisions in progress. At Build: formally validated. At Test: validate decisions under testing conditions.' WHERE id='P6.F5' AND framework='PIR';
UPDATE questions SET stage_note='At Design/early Build: workarounds being identified. At UAT/Cutover: formally accepted or eliminated.' WHERE id='P6.F7' AND framework='PIR';
UPDATE questions SET stage_note='Not expected before Test. At UAT: data migration tested. At Cutover Prep: full end-to-end in production-equivalent environment expected.' WHERE id='P7.F1' AND framework='PIR';
UPDATE questions SET stage_note='Not expected before Test. Reconciliation framework should be defined by Build.' WHERE id='P7.F2' AND framework='PIR';
UPDATE questions SET stage_note='At Design: Day 1 process design commenced. At Build: substantially defined. At Cutover: all confirmed and trained.' WHERE id='P7.F5' AND framework='PIR';
UPDATE questions SET stage_note='Not expected before Cutover Prep. At Build/Test: cutover runbook in development.' WHERE id='P7.F9' AND framework='PIR';
UPDATE questions SET stage_note='Not expected before Test. At Cutover Prep: at least one full rehearsal completed and findings resolved.' WHERE id='P7.F11' AND framework='PIR';
UPDATE questions SET stage_note='At Design: hypercare model defined. At Build: resourcing confirmed. At Go-Live: hypercare team deployed and operational.' WHERE id='P7.F14' AND framework='PIR';
UPDATE questions SET stage_note='At Design: operational reporting requirements identified. At Build: reporting design confirmed. At Test: validated. At Go-Live: live and accepted.' WHERE id='P9.F1' AND framework='PIR';
UPDATE questions SET stage_note='Not expected before Build. At Test/Go-Live: AI/automation components tested and governed.' WHERE id='P9.F3' AND framework='PIR';
UPDATE questions SET stage_note='At Mobilisation: Change Control process and authority must be defined before any delivery commences. At Design: process active for design decisions. At Build/Test: no scope, technical, or budget changes without formal approval — this is the highest-risk period for undocumented change. At Cutover: strict change freeze or emergency-only process in force.' WHERE id='P2.F9' AND framework='PIR';
```

## 6.2 SIR Context Notes — 5 Statements

```sql
UPDATE questions SET stage_note='NSI: assess whether transition plan is complete before introduction. Transformation: assess whether service can absorb change alongside current load.' WHERE id='D7.F1' AND framework='SIR';
UPDATE questions SET stage_note='Under Pressure: assess whether change freeze has been considered. Transformation: assess whether change capacity is explicitly managed.' WHERE id='D3.F1' AND framework='SIR';
UPDATE questions SET stage_note='Legacy Pre-Retirement: assess whether continuity is formally planned through wind-down.' WHERE id='D10.F1' AND framework='SIR';
UPDATE questions SET stage_note='NSI: D8 capacity score is the primary BAU readiness signal. Below 3.0 = service cannot safely absorb the new introduction.' WHERE id='D8.F1' AND framework='SIR';
UPDATE questions SET stage_note='Transformation: D11 governance and CMDB accuracy must be assessed before any structural change proceeds.' WHERE id='D11.F1' AND framework='SIR';
-- Verify: SELECT id,framework,LEFT(stage_note,50) FROM questions WHERE stage_note IS NOT NULL ORDER BY framework,id;
-- Expected: 23 rows
```

# 7. Scoring Engine Updates

## 7.1 PIR — Add RII Formula Only. Nothing Else Changes.

P2.F9 (Change Control) is NOT added to RII. RII measures risk escalation discipline — a different signal to change control governance. RII formula is unchanged.

```ts
export const PIR_WEIGHTS={P1:1.5,P2:1.4,P3:1.3,P4:1.2,P5:1.2,P6:1.1,P7:1.2,P8:1.1,P9:1.0,P10:1.1};
export const PIR_DENOMINATOR=12.1; // VERIFIED
export const RII_QUESTIONS=["P1.F1","P1.F2","P1.F4","P1.F5","P2.F4","P2.F5","P2.F6"]; // P2.F9 NOT included
export const calculateRII=q=>{const s=RII_QUESTIONS.map(id=>q[id]).filter(v=>v!==undefined);return s.length?Math.round(s.reduce((a,b)=>a+b,0)/s.length*100)/100:null;}
export const calculateBRI=p=>Math.round(((p.P3*1.3)+(p.P4*1.2)+(p.P5*1.2)+(p.P7*1.2))/4.9*100)/100;
export const calculateVRI=p=>Math.round(((p.P3*1.3)+(p.P9*1.0))/2.3*100)/100;
export const calculateDMI=p=>{const r=Math.round(((p.P9*1.0)+(p.P10*1.1))/2.1*100)/100;return p.P10<2.5?Math.min(r,3.0):r;};
```

## 7.2 SIR — Three Changes Together

Apply all three simultaneously: D11 weight 0.9→1.2, SSI new formula, denominator 14.2→14.5.

```ts
export const SIR_WEIGHTS={D1:1.4,D2:1.4,D3:1.0,D4:1.2,D5:1.3,D6:1.2,D7:1.2,D8:1.2,D9:1.0,D10:1.3,D11:1.2,D12:1.1};
export const SIR_DENOMINATOR=14.5; // UPDATED from 14.2
// SSI = Service Stability Index — CORRECTED FORMULA
// D2 (Incident) and D5 (Change) are the two primary operational stability signals.
// D3 (Service Request) does not measure stability — it is excluded.
export const calculateSSI=d=>Math.round(((d.D2*1.4)+(d.D5*1.3))/2.7*100)/100;
// Verify: D2=3.5, D5=2.8 → (4.9+3.64)/2.7 = 3.16 ✓
export const calculateSMI=d=>Math.round(((d.D4*1.2)+(d.D6*1.2)+(d.D11*1.2))/3.6*100)/100;
export const calculateSIMI=d=>Math.round(((d.D4*1.2)+(d.D11*1.2)+(d.D12*1.1))/3.5*100)/100;
export const calculateBAURI=d=>Math.round((d.D7+d.D8)/2*100)/100;
export const calculateSMISIMIDelta=(smi,simi)=>Math.round((smi-simi)*100)/100;
```

## 7.3 Verification Tests — All Must Pass Before Continuing

| Test | Input | Expected Output |
| --- | --- | --- |
| PIR overall | All 10 pillars = 3.5 | 3.50 |
| PIR BRI | P3=3.0, P4=4.0, P5=2.5, P7=3.5 | 3.20 |
| PIR VRI | P3=4.5, P9=2.5 | 3.63 |
| PIR DMI with floor | P9=4.0, P10=2.3 | 3.0 (floored from 3.11) |
| PIR RII | All 7 questions = 2.5 | 2.50 |
| SIR overall | All 12 domains = 3.5 | 3.50 |
| SIR SSI | D2=3.5, D5=2.8 | 3.16 |
| SIR SMI | D4=3.0, D6=3.5, D11=3.2 | 3.23 |
| SIR SIMI | D4=3.0, D11=3.2, D12=2.5 | 2.91 |
| SIR BAU-RI | D7=2.8, D8=3.4 | 3.10 |
| SMI/SIMI delta | SMI=3.23, SIMI=2.91 | 0.32 |

# 8. AI Payload Structure

## 8.1 Snapshot Payloads

```ts
// PIR Snapshot:
{ framework:"PIR", assessment_id, client_company, programme_name, programme_type,
```

**delivery_stage, primary_concern, regulatory_context, assessment_date,**

**overall_score, rag_status,**

```ts
  pillar_scores: { P1..P10 },   // all pre-calculated
```

**pillar_names: PIR_PILLAR_NAMES, // from lookup in Step 4**

**bri, vri, dmi, rii, chi,      // all pre-calculated**

**alert_flags: string[],**

**question_responses: [{ id, pillar_code, score, is_compliance }],**

**compliance_question_scores: { [id]: number }**

```ts
}
```
```ts
// SIR Snapshot: same structure, replace pillar fields with domain fields:
{ framework:"SIR", service_name, service_context,
  domain_scores: { D1..D12 }, domain_names: SIR_DOMAIN_NAMES,
```

**ssi, smi, simi, bau_ri, chi,**

**smi_simi_delta,  // pre-calculated — AI never subtracts these itself**

**...rest same as PIR snapshot**

```ts
}
```

## 8.2 Full Report Payload — Additional Fields

```ts
{ ...snapshot_payload,
```

**tier: "Review"|"Briefing",         // Review=Tier1, Briefing=Tier2**

**consultant_name: string,           // default "Reda Boukhiar"**

**sponsor_name: string|null,**

**interview_count: number,**

**documents_reviewed: string[],**

**confidence_level: "High"|"Medium"|"Low",**

programme_value: number|null,      // PIR — if known. AI anchors commercial estimates.

annual_service_cost: number|null,  // SIR — same purpose.

**evidence_notes: {**

```ts
    [question_id]: {
```

**note: string,**

**respondent_role: string|null,**

**document_source: string|null,**

**confidence: "High"|"Medium"|"Low"**

```ts
    }
  },
```

**emerging_issues: string[],**

**reporting_accuracy_risk: boolean,  // PIR only**

**reporting_accuracy_evidence: string|null,**

**stakeholder_notes: {              // Tier 2 only — null for Tier 1**

**sponsor_position: string|null,**

**operational_position: string|null,**

**divergence_areas: string[]**

```ts
  }|null
}
```

# 8b. Consultant Portal UI — Confirm Before Building

STOP. Before building the full report AI generation, confirm with Reda Boukhiar whether the consultant portal data entry form exists. The fields below are required for the full report payload. If they do not exist in the portal, they cannot be passed to the AI — and the report cannot be generated.

## 8b.1 The Question to Confirm with Reda

| Question | Does the consultant portal currently have a screen where the consultant enters evidence notes, respondent roles, and pillar/domain summaries per question for a full PIR or SIR assessment? |
| --- | --- |
| If YES (portal exists) | Read Section 8b.2 — extend the existing form with the new fields listed. |
| If NO (portal does not exist) | Read Section 8b.3 — the portal form must be built from scratch. Confirm scope and timeline with Reda before starting. |

## 8b.2 If the Portal Exists — Fields to Add

Add these fields to the existing question-level response form in the consultant portal. These fields populate the evidence_notes object in the full report payload.

| Field | Type | Where | What the Consultant Enters |
| --- | --- | --- | --- |
| evidence_note | Text area | Per question — full assessment only | What the consultant observed: interview quote, document reference, or specific finding. This becomes the "evidence" in the intelligence profile. |
| respondent_role | Text input | Per question | The role of the person interviewed who provided this evidence. e.g. "Programme Director", "CFO", "Data Architect". Leave blank if from document. |
| document_source | Text input | Per question | Name of the document reviewed for this question. e.g. "RAID Log v2.3", "Budget Actuals March 2026". Leave blank if from interview. |
| confidence_level | Dropdown: High / Medium / Low | Per question | High = confirmed by document + interview. Medium = interview only. Low = single source or contradicted. |
| stakeholder_divergence_note | Text area | Per question — Tier 2 only | Note on whether the Sponsor and operational team gave different answers to this question. Used in Stakeholder Intelligence section. |

**Add these fields at the assessment level (not per question):**

| Field | Type | Where | Purpose |
| --- | --- | --- | --- |
| interview_count | Number | Assessment header | Total number of stakeholder interviews conducted. Appears in full report payload. |
| documents_reviewed | Tag input / text list | Assessment header | Names of documents reviewed. e.g. "RAID Log", "Budget Report", "Test Strategy". |
| programme_value | Currency input — optional | PIR assessment header | The total programme value in £. If entered, the AI anchors commercial impact estimates to actual investment. If blank, AI uses ranges. |
| annual_service_cost | Currency input — optional | SIR assessment header | Annual cost of the service in £. Same purpose as programme_value. |
| reporting_accuracy_risk | Checkbox | PIR assessment footer | Tick if the consultant identified a material discrepancy between what was reported to the board and what the evidence shows. |
| reporting_accuracy_evidence | Text area — appears when checkbox ticked | PIR only | Describe the specific discrepancy. e.g. "Status reports show Green across all workstreams. RAID log shows 7 critical risks unresolved for 45+ days." |

**For Tier 2 only — add a Stakeholder Notes section to the assessment:**

| Field | Type | Purpose |
| --- | --- | --- |
| sponsor_position | Text area | What the Executive Sponsor or CIO said about the programme/service state in their interview. Free text. |
| operational_position | Text area | What the operational team (Programme Director, Service Manager, PMO) said about the state. Free text. |
| divergence_areas | Tag input / text list | Key areas where sponsor and operational views diverged. e.g. "Budget trajectory", "Go-live date confidence", "Data quality". |

## 8b.3 If the Portal Does Not Exist — What Must Be Built

If the consultant portal data entry form does not exist, the full report AI generation cannot be tested. The AI receives an empty evidence_notes object and will produce generic output. This is not acceptable for a paid engagement. Confirm build scope with Reda before starting.

| What needs building | A consultant-facing web form where the consultant fills in scores, evidence notes, respondent roles, and document sources per question — then triggers AI report generation. Think: a structured interview capture form, not a client-facing questionnaire. |
| --- | --- |
| Minimum viable version | Assessment header (programme/service name, stage/context, interview count, documents). Question list with evidence_note + respondent_role + confidence_level per question. Generate report button. |
| Full version (Tier 2) | Same as above plus stakeholder notes section, divergence areas, stakeholder_divergence_note per question. |
| Estimated build | 3–5 days for minimum viable. 5–7 days for full Tier 2 version. |
| Pre-requisite | Confirm with Reda: what does the consultant workflow look like? Does the consultant use the platform live during an engagement, or does they upload scores after the fact? |

# 9. The 6 AI Prompts — Paste Verbatim into config/ai.php

Replace existing prompt keys in full. Do not mix old and new. Add DEPRECATED comment for old keys — see Section 9.7.

```ts
Cover letter rule for all 4 full report prompts: "In conducting our [product name] of [programme/service], the finding that demands your decision before [CONSULTANT TO COMPLETE: specific milestone or date] is [finding]. The recommendation in this briefing follows directly from that finding." — No duration reference ever. [CONSULTANT TO COMPLETE] is a literal placeholder that the consultant fills in during their review of the AI draft.
```

## 9.1 pir_snapshot

```php
'pir_snapshot' => <<<'PROMPT'
GHOST MODE: Write as Reda Boukhiar, Director RAB Consulting Services.
20 years experience. Must be indistinguishable from senior consulting practitioner.
Zero boilerplate. Zero filler. Zero AI language.
CRITICAL — PRE-CALCULATED. DO NOT COMPUTE:
All values in payload are pre-calculated. Never calculate any numeric value.
Use ONLY exact values provided.
BRITISH ENGLISH: programme, behaviour, recognise, organisation, analyse, prioritise.
BANNED: leverage, utilise, holistic, robust, granular, actionable, streamline,
empower, ecosystem, synergy, impactful, going forward, learnings, journey.
BANNED OPENERS: It is worth noting / It is important to / In conclusion / This highlights.
STAGE CALIBRATION (delivery_stage in payload):
Work not yet due = FORWARD ALERT: 'Not yet required at [stage] — expected by [next stage].'
Work due but absent = full urgency.
PIR (P5,P6,P7,P9): Mobilisation all forward. Design mock/cutover forward. Build data due.
Test UAT due. Cutover everything due no forward alerts.
JSON OUTPUT ONLY:
{
  intelligence_brief: TWO PARAGRAPHS (no headers, no bullets).
    Para 1 (70-100w): two weakest pillars named+scored (use pillar_names), delivery_stage,
      BRI/VRI/DMI from payload with one-line interpretation each.
    Para 2 (55-75w): two weeks action. Named role. Specific.
      End EXACTLY: 'A full Programme Intelligence Review would establish the root
      causes with evidence and produce a prioritised action plan.'
  insight_cards: [3 cards]
    DEFAULT (regulatory_context null): 3 lowest pillars from pillar_scores.
      Each: pillar_code, pillar_name(from payload), score(from payload), rag,
      finding(2 sentences max 220 chars), action(1 sentence max 160 chars named role).
    WHEN regulatory_context set AND any compliance question below 3.0:
      Cards 1-2: 2 lowest pillars.
      Card 3 COMPLIANCE SIGNAL: pillar_code:'COMPLIANCE',
        pillar_name:'Compliance Risk Signal', score:chi(from payload), rag:matching,
        finding: name context+area+meaning (max 220 chars, NEVER specific article),
        action: what full PIR adds (max 160 chars).
}
PROMPT,
```

## 9.2 sir_snapshot

```php
'sir_snapshot' => <<<'PROMPT'
GHOST MODE: Same standard as pir_snapshot. IT service management language.
Domains not pillars. Never: programme, go-live, cutover, ERP.
CRITICAL — PRE-CALCULATED. DO NOT COMPUTE:
SSI, SMI, SIMI, BAU-RI, CHI, smi_simi_delta — all from payload.
smi_simi_delta: use payload value. Do not subtract SMI minus SIMI yourself.
BRITISH ENGLISH + BANNED WORDS: same as pir_snapshot plus:
service improvement journey, mature your ITSM, best practice framework.
SERVICE CONTEXT CALIBRATION (service_context in payload):
NSI: D7 immediate risk. BAU-RI primary. UnderPressure: D2<3.0=StabilityAlert no softening.
Transformation: BAU-RI primary, below 2.5 state cannot absorb change.
Established: reference trajectory. LegacyPreRetirement: continuity focus.
JSON OUTPUT ONLY:
{
  intelligence_brief: TWO PARAGRAPHS.
    Para 1 (70-100w): two weakest domains named+scored (domain_names from payload),
      service_context, SSI/SMI/SIMI/BAU-RI from payload one-line each.
      If smi_simi_delta above 0.3: name divergence explicitly.
    Para 2 (55-75w): action. Named role.
      End EXACTLY: 'A full Service Intelligence Review would establish the root
      causes with evidence and produce a prioritised service improvement plan.'
  insight_cards: same structure as PIR, 3 domains or compliance card.
}
PROMPT,
```

## 9.3 pir_full_tier1 — Programme Intelligence Review

```php
'pir_full_tier1' => <<<'PROMPT'
GHOST MODE:
Ghostwriting a RAB Programme Intelligence Review on behalf of Reda Boukhiar,
Director RAB Consulting Services. Executive Sponsor and Programme Board audience.
Big 4 senior partner standard. Not AI-generated.
Test every sentence: could it appear in a different programme report unchanged? Rewrite it.
QUALITY STANDARD — READ BEFORE WRITING:
PASSING EVIDENCE: 'The Programme Director confirmed the last SteerCo escalation
was November 2025. Three critical risks raised. None resolved in 5 working days.
RAID log shows same risks open February 2026.'
FAILING: 'Governance processes appear weak.'
PASSING BUSINESS IMPACT: 'Without a named Change Authority, scope changes above
£50,000 are approved informally. At this programme scale, undocumented changes
typically add £200,000-£400,000 before go-live.'
If programme_value in payload: anchor ALL estimates to actual investment.
FAILING: 'This may cause issues.'
CRITICAL — PRE-CALCULATED. DO NOT COMPUTE:
BRI, VRI, DMI, RII, CHI — all from payload. Never calculate.
BRITISH ENGLISH: programme, behaviour, recognise, organisation, analyse, prioritise.
Never: domain, ITSM, SLA in a PIR report.
BANNED: leverage, utilise, holistic, robust, granular, actionable, streamline, empower,
ecosystem, synergy, impactful, going forward, learnings, journey, assurance (filler),
it is worth noting, it is important to, in conclusion, this highlights.
Vary sentence length. Short after complex = authority.
'P7 scored 1.9. There is no tested rollback plan.' — Correct.
'Cutover readiness presents some challenges.' — Not acceptable.
STAGE CALIBRATION (delivery_stage in payload):
Forward alert for work not yet due. Full urgency for work due but absent.
Mobilisation: P5/P6/P7/P9 all forward alerts.
Design: mock migrations, cutover, hypercare = forward. P6 design = current.
Build: data quality and test prep due. Cutover rehearsals not yet due.
Test: UAT, integration, reconciliation due. Cutover plan must exist.
Cutover Prep: everything due. No forward alerts. All gaps are risks.
Go-Live/Hypercare: BAU transition critical. P4 adoption in execution mode.
SCOPE: all pillars below 3.0. Above 3.0 only if compliance finding.
NINE SECTIONS IN ORDER:
1. COVER_LETTER (150 words):
   'In conducting our Programme Intelligence Review of [programme_name] at [client_company],
   the finding that demands your decision before [CONSULTANT TO COMPLETE: specific milestone
   or date] is [single most critical finding as plain fact].
   The recommendation in this briefing follows directly from that finding.'
   Close: specific decision for Executive Sponsor. Named.
   DO NOT reference days, duration, or time spent.
   DO NOT use: warrants, requires your attention, important to note.
   Sign: Reda Boukhiar, Director, RAB Consulting Services.
2. EXECUTIVE_POSITION (100-130 words):
   First: overall_score + what it means at delivery_stage. Not the number — the meaning.
   Second: two or three weakest pillars named with scores (use pillar_names).
   Third: BRI, VRI, DMI from payload — one-line commercial implication each.
   Fourth: compounding combinations named explicitly.
   Final: single decision for Executive Sponsor.
3. INTELLIGENCE_DASHBOARD:
   overall: {score:payload, rag:payload, stage:delivery_stage}
   indices: bri/vri/dmi/rii/chi — all from payload, 160-char interpretation each.
     RII<2.5: 'RII of [X] means risk management has broken down — RAID log does not
     reflect the true programme position.'
   alert_flags: from payload exactly.
   confidence_legend: {high:'Confirmed by documentary evidence and interview',
     medium:'Confirmed by interview — independent documentary evidence not available',
     low:'Single source, contradicted by other data, or evidence not available'}
4. INTELLIGENCE_PROFILE (all pillars below 3.0, score order):
   For each: pillar_code, pillar_name(from pillar_names), score(payload), rag,
   confidence(High/Medium/Low), headline, evidence(2-3 named sources),
   business_impact(commercial consequence — quantify if programme_value in payload),
   compliance_dimension(ONLY if is_compliance questions below 3.0 — always close with
   'Operational intelligence only. Engage legal and compliance advisers.'),
   action(one step, named role, specific timeline, done condition).
   REPORTING_ACCURACY_RISK: if true in payload — named finding separate from blocks.
   BAU_TRANSITION: if P9 below 3.0 — named finding separate from go-live readiness.
   'A programme ready to go live but not ready to sustain what it builds is not a
   controlled go-live. It is a delayed failure.'
5. RISK_REGISTER (top 5-7 risks across all pillars):
   For each: risk_title(named condition not category), probability(H/M/L),
   impact(H/M/L), owner(named role), current_control, action.
6. RAID_SUMMARY (PIR only):
   total_risks, critical_risks, issues_without_owner, overdue_actions,
   assessment: 2-sentence RAID health statement from evidence notes.
7. ROOT_CAUSE_ANALYSIS:
   narrative(300-400 words, cross-cutting, not repeat of pillar findings),
   primary_cause(single sentence), causal_chain(3-5 steps).
8. PRIORITY_PLAN (30/60/90):
   30_days: 3-4 actions — stop immediate risk.
   60_days: 2-3 actions — regulatory first if context set.
   90_days: 2-3 actions — foundation for controlled go-live.
   Each: action_title(named condition), owner(named role), deadline(specific), done_condition.
9. FINAL_POSITION:
   'Viable trajectory with focused intervention' |
   'Requires structured recovery before go-live can proceed' |
   'Go-live should not proceed until identified conditions are resolved'
   No hedging.
   tier1_bridge: name gaps this Review could not fully evidence.
   compliance_risk_signals: ONLY if regulatory_context set.
     fca_uk: PS21/3 for P7. SMCR for P1. Not DORA in PIR.
     Always close: 'Operational intelligence only. Engage legal and compliance advisers.'
JSON SCHEMA:
{ cover_letter, executive_position,
  intelligence_dashboard:{overall,indices,alert_flags,confidence_legend},
  intelligence_profile:[{pillar_code,pillar_name,score,rag,confidence,headline,
    evidence,business_impact,compliance_dimension|null,action}],
  reporting_accuracy_risk_finding:string|null,
  risk_register:[{risk_title,probability,impact,owner,current_control,action}],
  raid_summary:{total_risks,critical_risks,issues_without_owner,overdue_actions,assessment}|null,
  root_cause_analysis:{narrative,primary_cause,causal_chain:string[]},
  priority_plan:{30_days:[...],60_days:[...],90_days:[...]},
  final_position, tier1_bridge, compliance_risk_signals:string|null }
RETURN VALID JSON ONLY. No preamble. No markdown. No disclaimers in JSON.
PROMPT,
```

## 9.4 pir_full_tier2 — Programme Intelligence Briefing

Same as pir_full_tier1 with these differences: (1) Cover letter: "Programme Intelligence Briefing" not "Review". (2) SCOPE: all pillars below 4.0. (3) Add STAKEHOLDER_INTELLIGENCE section after INTELLIGENCE_DASHBOARD. (4) PRIORITY_PLAN: 4-5 actions per horizon. (5) FINAL_POSITION: remove tier1_bridge, add evidence pack status. (6) JSON schema: add stakeholder_intelligence, remove tier1_bridge.

```php
'pir_full_tier2' => <<<'PROMPT'
GHOST MODE — MANDATORY:
Ghostwriting a RAB Programme Intelligence Briefing on behalf of Reda Boukhiar,
Director RAB Consulting Services. 20 years experience.
Executive Sponsor and Programme Board audience.
Big 4 senior partner standard. Not AI-generated.
Treat this report tier as Briefing.
Test every sentence: could it appear in a different programme report unchanged?
If yes, rewrite it.

QUALITY STANDARD — READ BEFORE WRITING:
PASSING EVIDENCE: 'The Programme Director confirmed the last SteerCo escalation
was November 2025. Three critical risks raised. None resolved in 5 working days.
RAID log shows same risks open February 2026.'
FAILING: 'Governance processes appear weak.'

PASSING BUSINESS IMPACT: 'Without a named Change Authority, scope changes above
£50,000 are approved informally. At this programme scale, undocumented changes
typically add £200,000–£400,000 before go-live.'
If programme_value in payload: anchor ALL estimates to actual investment.

PASSING ACTION: 'Executive Sponsor commissions an independent governance audit
within 10 working days...' (specific scope, specific done condition).
FAILING: 'Improve governance.'

CRITICAL — PRE-CALCULATED. DO NOT COMPUTE:
BRI, VRI, DMI, RII, CHI — all from payload. Never calculate.

BRITISH ENGLISH — MANDATORY:
programme · behaviour · recognise · organisation · analyse · prioritise · optimise ·
authorise · realise · minimise · maximise · centre.
Never: domain, ITSM, SLA.

BANNED WORDS:
leverage, utilise, holistic, robust, granular, actionable, streamline, empower,
ecosystem, synergy, impactful, going forward, learnings, journey, assurance.

BANNED OPENERS:
It is worth noting · It is important to · In conclusion · This highlights ·
This demonstrates · Furthermore · It is clear that.

Vary sentence length deliberately. Short sentences after complex analysis = authority.

STAGE CALIBRATION — REDA'S RULE (delivery_stage in payload):
A low score at Design on Build-stage work is NOT a risk. It is a forward alert.
A low score at Cutover Prep on Cutover work IS a risk. Apply full urgency.

Mobilisation: P5/P6/P7/P9 ALL forward alerts.
Design: mock migrations, cutover, hypercare = forward.
Build: data quality and test prep DUE. Cutover rehearsals NOT yet due.
Test: UAT, integration, reconciliation DUE. Cutover plan MUST exist.
Cutover Prep: EVERYTHING due. No forward alerts.
Go-Live/Hypercare: BAU transition critical.

SCOPE (TIER 2 BRIEFING): All pillars scoring below 4.0.
Pillars at 4.0+ only if compliance finding.

TEN SECTIONS IN ORDER:

1. COVER_LETTER (150 words):
   'In conducting our Programme Intelligence Briefing of [programme_name] at [client_company],
   the finding that demands your decision before [CONSULTANT TO COMPLETE: specific milestone
   or date] is [single most critical finding as plain fact].
   The recommendation in this briefing follows directly from that finding.'
   Close: specific decision for Executive Sponsor.
   DO NOT reference days, duration, or time spent.
   Sign: Reda Boukhiar, Director, RAB Consulting Services.

2. EXECUTIVE_POSITION (100-130 words):
   overall_score meaning at delivery_stage, weakest pillars, BRI/VRI/DMI implications,
   compounding combinations, single decision.

3. INTELLIGENCE_DASHBOARD:
   overall, indices, alert_flags, confidence_legend.

4. STAKEHOLDER_INTELLIGENCE (TIER 2 ONLY — distinguishes Briefing from Review):
   Use stakeholder_notes from payload.
   divergence_summary (150 words):
     'The Executive Sponsor believes [A]. The Programme Director knows [B].
      That is a governance failure, not a communication gap.'
   divergence_areas: [{ area, sponsor_view, operational_view, finding }]
   governance_implication: what the Sponsor must do specifically.
   If stakeholder_notes is null: set stakeholder_intelligence to null.

5. INTELLIGENCE_PROFILE (all pillars below 4.0, score order):
   Each finding: pillar_code, pillar_name, score, rag, confidence, headline,
   evidence, business_impact, compliance_dimension|null, action.

6. RISK_REGISTER (top 5-7 risks):
   Each risk: risk_title, probability, impact, owner, current_control, action.

7. RAID_SUMMARY (PIR only):
   total_risks, critical_risks, issues_without_owner, overdue_actions, assessment.

8. ROOT_CAUSE_ANALYSIS:
   narrative (300-400 words), primary_cause, causal_chain (3-5 steps).

9. PRIORITY_PLAN (30/60/90) — TIER 2:
   30_days: 4-5 actions — stop immediate risk.
   60_days: 4-5 actions — regulatory first if context set.
   90_days: 4-5 actions — foundation for controlled go-live.
   Each: action_title (named condition), owner, deadline, done_condition.

10. FINAL_POSITION (TIER 2 — no tier1_bridge):
    Take one of three positions — no hedging.
    evidence_validated_statement: 'The evidence has been validated through direct document
      review and interview. Confidence level is stated per finding in the intelligence profile.'
    compliance_risk_signals: ONLY if regulatory_context set.
      Include evidence pack status at day 90:
      'If all recommended actions are completed, the following evidence will exist
       for a regulatory reviewer at day 90: [list specific evidence pieces].'
      Always close: 'Operational intelligence only. Engage legal and compliance advisers.'

JSON OUTPUT CONTRACT — MANDATORY:
Return JSON only. Use exactly these top-level keys and no others:
{
  "cover_letter": "...",
  "executive_position": "...",
  "intelligence_dashboard": {
    "overall": {},
    "indices": {},
    "alert_flags": [],
    "confidence_legend": {}
  },
  "stakeholder_intelligence": {
    "divergence_summary": "...",
    "divergence_areas": [],
    "governance_implication": "..."
  },
  "intelligence_profile": [],
  "reporting_accuracy_risk_finding": null,
  "risk_register": [],
  "raid_summary": {},
  "root_cause_analysis": {},
  "priority_plan": {
    "30_days": [],
    "60_days": [],
    "90_days": []
  },
  "final_position": "...",
  "evidence_validated_statement": "...",
  "compliance_risk_signals": null
}

Do not use alternate top-level keys such as opening, overall_position, key_themes,
or instruction_to_sponsor.
Do not include tier1_bridge for Tier 2.
Include stakeholder_intelligence.
Include evidence_validated_statement.

ANTI-REPETITION TEST before returning JSON:
  - No two intelligence_profile actions name the same role with the same verb.
  - Stakeholder divergence_areas each name a different area.
  - Priority plan actions are all uniquely named conditions.

RETURN VALID JSON ONLY. No preamble. No markdown. No disclaimers in JSON.
PROMPT,
```

## 9.5 sir_full_tier1 — Service Intelligence Review

```php
'sir_full_tier1' => <<<'PROMPT'
GHOST MODE: Service Intelligence Review on behalf of Reda Boukhiar.
CIO and IT Director audience. IT service management language only.
Never programme delivery language. Big 4 standard. Not AI-generated.
Test every sentence: could it appear in a different service report? Rewrite it.
QUALITY STANDARD:
PASSING: 'Change failure rate reported as 4% in SLA packs for three quarters.
Change log shows 12 failed of 61 in Q1 = 19.7%. Discrepancy acknowledged.'
FAILING: 'Change management has some weaknesses.'
CRITICAL — PRE-CALCULATED. DO NOT COMPUTE:
SSI, SMI, SIMI, BAU-RI, CHI, smi_simi_delta — all from payload.
smi_simi_delta: use from payload. Do not subtract SMI minus SIMI yourself.
D11 NAMING — MANDATORY:
'Service Tooling, CMDB & Knowledge Management'. Never 'Automation, Tooling...'
D11 below 3.0: CMDB accuracy is the primary named finding.
'The CMDB is the foundation of service management. If inaccurate: change impact
 assessment is guesswork. Incident routing is personal knowledge.
 DR planning cannot be executed by anyone other than those who built the estate.'
BRITISH ENGLISH + BANNED WORDS: same as PIR plus service improvement journey,
mature your ITSM, best practice framework.
Domains not pillars. Never: programme, go-live, cutover, ERP, pillar.
'D2 scored 2.8. Three P1 incidents had no PIR. That is the process.' — Pass.
'Incident management processes present challenges.' — Fail.
SERVICE CONTEXT CALIBRATION (service_context in payload):
NSI: D7=immediate risk. BAU-RI primary. <2.5=cannot absorb introduction.
UnderPressure: D2<3.0=StabilityAlert. No softening.
Transformation: BAU-RI primary. <2.5=cannot absorb change. State explicitly.
Established: trajectory. LegacyPreRetirement: continuity+wind-down not improvement.
SCOPE: all domains below 3.0.
NINE SECTIONS IN ORDER:
1. COVER_LETTER:
   'In conducting our Service Intelligence Review of [service_name] at [client_company],
   the finding that demands your decision before [CONSULTANT TO COMPLETE] is [finding].
   The recommendation in this briefing follows directly from that finding.'
   DO NOT reference days, duration, or time spent.
   Sign: Reda Boukhiar, Director, RAB Consulting Services.
2. EXECUTIVE_POSITION (100-130 words):
   First: overall_score + what it means for this service in service_context.
   Second: SSI, SMI, SIMI, BAU-RI from payload — one direct clause each.
   If smi_simi_delta >0.3: 'SMI of [smi] against SIMI of [simi] — delta [delta]
   — means maturity built but not used to improve. Strategic gap, not process gap.'
   Third: two or three weakest domains named (use domain_names).
   Fourth: compounding combinations. Final: single decision for CIO/IT Director.
3. INTELLIGENCE_DASHBOARD:
   overall:{score:payload,rag:payload,context:service_context}
   indices: ssi/smi/simi/bau_ri/chi — all from payload, 160-char each.
   SSI<2.5: 'SSI of [X] means fundamental instability. Nothing else matters first.'
   simi: if smi_simi_delta>0.3 use divergence framing.
   alert_flags: from payload exactly.
   confidence_legend: same as PIR.
4. INTELLIGENCE_PROFILE (domains below 3.0, score order):
   Same structure as PIR but domain language throughout.
   business_impact: what business users/customers/regulators experience.
   If annual_service_cost in payload: anchor estimates.
5. RISK_REGISTER: same structure as PIR. Service management risk language.
   NOTE: SIR has no RAID_SUMMARY section.
6. ROOT_CAUSE_ANALYSIS:
   SIR causal chains: D5 causing D2. D1 gap preventing D4 resolution.
   'The KEDB is not maintained because D4 has no governance mandate.
   D4 has no governance mandate because D1 accountability is unclear.
   That is a governance failure, not a problem management failure.'
7. PRIORITY_PLAN (30/60/90): same structure as PIR. Service actions.
8. FINAL_POSITION:
   'Stable and improving with focused action' |
   'Stable but not resilient — improvement programme required' |
   'Needs stabilisation before further transformation can be absorbed'
   Single most important decision for CIO and IT Director.
   tier1_bridge: name gaps this Review could not fully evidence.
9. compliance_risk_signals (ONLY if regulatory_context set):
   fca_uk: PS21/3 for D10. SMCR for D1. SYSC 13.9 for D9.
   dora_eu: Article 28 for D9. Title IV for D10. Title II for D11.
   nhs_cqc: CQC for D1 and D10.
   Always close: 'Operational intelligence only. Engage legal and compliance advisers.'
JSON SCHEMA: same as pir_full_tier1 except domain fields, no raid_summary.
RETURN VALID JSON ONLY. No preamble. No markdown. No disclaimers in JSON.
PROMPT,
```

## 9.6 sir_full_tier2 — Service Intelligence Briefing

```php
'sir_full_tier2' => <<<'PROMPT'
// IDENTICAL TO sir_full_tier1 WITH THESE EXACT CHANGES:
// CHANGE 1: Cover letter: 'Service Intelligence Briefing' not 'Review'.
// CHANGE 2: SCOPE: all domains below 4.0.
// CHANGE 3: Add STAKEHOLDER_INTELLIGENCE as Section 4:
// IT Director vs Service Manager divergence is the primary mapping.
// 'The IT Director believes the service is stable. The Service Manager describes
//  three recurring incidents not in any SLA report. That is a governance finding.'
// divergence_summary, divergence_areas, governance_implication.
// If stakeholder_notes null: set to null.
// CHANGE 4: PRIORITY_PLAN — 4-5 actions per horizon.
// CHANGE 5: FINAL_POSITION — remove tier1_bridge.
// If regulated: state whether recoverable before next regulatory review.
// 'Evidence validated on-site. Confidence stated per finding.'
// compliance_risk_signals: include evidence pack status at day 90.
// CHANGE 6: JSON schema — add stakeholder_intelligence, remove tier1_bridge.
PROMPT,
```

## 9.7 Deprecated Prompts

```ts
// Add this block to config/ai.php above the deprecated keys:
// ================================================================
// DEPRECATED — DO NOT CALL FOR NEW REPORT GENERATION
// full_report, pir_full_report, sir_full_report,
// pir_regulatory, sir_regulatory
// Active prompts: pir_snapshot, sir_snapshot,
// pir_full_tier1, pir_full_tier2, sir_full_tier1, sir_full_tier2
// ================================================================
```

# 10. ReportService — One Code Change

```ts
// Find the prompt key lookup in app/Services/ReportService.php
// REPLACE:
// $systemPrompt = config('ai.prompts.full_report');
```
```ts
// WITH:
$framework = str_starts_with($assessment->type, 'PIR') ? 'pir' : 'sir';
$tier = ($assessment->tier === 'Briefing') ? 'tier2' : 'tier1';
// tier field value: 'Review' = Tier 1, 'Briefing' = Tier 2
$promptKey = "{$framework}_full_{$tier}";
$systemPrompt = config("ai.prompts.{$promptKey}");
if (!$systemPrompt) throw new \Exception("Prompt not found: {$promptKey}");
```

# 11. PDF Report Structure — Page by Page

## 11.1 Footer — Every Page — Exact Text

| Footer text (exact) | This report is produced by RAB Consulting Services Ltd. All findings are based on information provided during the engagement. This constitutes operational intelligence and professional advisory guidance only. It does not constitute legal, regulatory, financial, or compliance advice. Clients should engage their own legal, compliance, and regulatory advisers before acting on any finding. © 2026 RAB Consulting Services Ltd. All methodology, indices, scoring frameworks, and report content are proprietary intellectual property. Reproduction or distribution without written consent is prohibited. |
| --- | --- |
| Also includes | RAB Consulting Services Ltd \| rboukhiar@rabconsultingservices.com \| +44 7717 544322 \| rabconsultingservices.com |
| Page numbering | Bottom right: "Page X of Y" — 8pt #64748B. Cover not counted. Appendix: "Appendix — Page X". |

## 11.2 Complete Page Order — Both Tiers

| Page | Section | Tier 1 | Tier 2 | Content Source |
| --- | --- | --- | --- | --- |
| Cover | Visual cover page | ✅ | ✅ | Metadata. Not AI. Not counted in page numbers. |
| 1 | Transmittal letter | ✅ | ✅ | AI: cover_letter |
| 2 | Executive Intelligence Position | ✅ | ✅ | AI: executive_position + alert_flags |
| 3 | Intelligence Dashboard | ✅ | ✅ | AI: intelligence_dashboard. Index cards + RAB™. Radar. Bar chart. 30/60/90 summary. Confidence legend. |
| 4 | Stakeholder Intelligence | ❌ | ✅ | AI: stakeholder_intelligence. Tier 2 only. |
| 5–8 | Intelligence Briefing Profile | ✅ <3.0 | ✅ <4.0 | AI: intelligence_profile. RAG heat map. Confidence badge per finding. |
| Next | Risk Register + Risk Heat Map | ✅ | ✅ | AI: risk_register. Table + 3×3 probability×impact matrix. |
| Next (PIR only) | RAID Summary | ✅ PIR | ✅ PIR | AI: raid_summary. 4-cell summary box. |
| Next | Root Cause Analysis | ✅ | ✅ | AI: root_cause_analysis. Narrative + causal chain. |
| Next | Priority Plan 30/60/90 | ✅ | ✅ | AI: priority_plan. 5-column table. |
| Next (if regulated) | Compliance Risk Signals | Conditional | Conditional | AI: compliance_risk_signals. |
| Final | Final Position | ✅ | ✅ | AI: final_position + tier1_bridge (Tier 1 only). |
| Appendix | Evidence Base + Methodology Note | ✅ | ✅ | Database extract. NOT AI. See Step 13. |

# 11b. PDF Generation Library, Cover Page, Headers, Watermarks

## 11b.1 PDF Library — BROWSERSHOT. NOT DomPDF.

```ts
Use Browsershot (Spatie/Browsershot). NOT Laravel DomPDF. DomPDF cannot render the watermark CSS pseudo-elements, complex grid layouts, or custom fonts reliably. Every watermark, every chart, and every complex layout requires a real browser renderer.
```
```ts
// Install:
composer require spatie/browsershot
npm install puppeteer
```
```ts
// Usage in ReportService.php:
use Spatie\Browsershot\Browsershot;
```
```ts
Browsershot::html($renderedHtml)
```

**->waitUntilNetworkIdle()**

**->format('A4')**

**->margins(10, 10, 10, 10)**

```ts
  ->save('/path/to/report.pdf');
```
```ts
// The HTML is rendered from the Blade template.
// Browsershot sends it through Chromium and produces a pixel-accurate PDF.
// All CSS including ::before watermarks, CSS Grid, custom fonts — all work.
```

## 11b.2 Cover Page Elements

| Element | Spec |
| --- | --- |
| Background | #1E3A8A full bleed. No other background colour. |
| RAB Logo | Top left. White version. 180px. 40px padding. |
| Report type all-caps | 13pt Inter Bold white letter-spaced. "RAB PROGRAMME INTELLIGENCE REVIEW" (Tier 1) or "RAB PROGRAMME INTELLIGENCE BRIEFING" (Tier 2). |
| Horizontal rule | 1px white full-width 20px below report type. |
| Client company name | 42pt Inter Bold white. Centre page. |
| Programme/service name | 22pt Inter Italic white. |
| Stage or context | 15pt Inter white. e.g. "Build Stage · May 2026" |
| Tier label (bottom left) | 13pt white. "Programme Intelligence Review" or "Programme Intelligence Briefing". No "Tier 1" or "Tier 2" on client documents. |
| Date | 12pt white. "May 2026" |
| Confidentiality statement (bottom right) | 11pt white. "CONFIDENTIAL — Prepared exclusively for [Client]. Not for distribution." |
| Scoring version stamp | 9pt white. "RAB Platform · Scoring Version 1.0 · [Assessment ID]" |

## 11b.3 Page Header — Every Content Page

```ts
/* Left: RAB logo 80px + report type 9pt #1E3A8A below */
/* Right (right-aligned): */
/*   [Client Company] — 10pt bold #0F172A */
/*   [Programme/Service] — 9pt #374151 */
/*   CONFIDENTIAL — 8pt #1E3A8A bold all-caps letter-spaced 2px */
/* Below header: 0.5pt solid #1E3A8A divider full width */
```

## 11b.4 Watermarks — Mandatory

| Watermark | Spec | Where |
| --- | --- | --- |
| CONFIDENTIAL diagonal | 72pt Inter Bold · rgba(30,58,138,0.05) · -45deg · centred · z-index:0 · CSS ::before on .report-page. All content z-index:1. | Every content page including appendix. Not on cover. |
| RAB Proprietary Methodology™ stamp | 7pt Inter navy below each index card. | Intelligence Dashboard — all 5 index cards. |
| Chart attribution | 7pt #64748B below every chart/heat map. "© RAB Consulting Services Ltd. Proprietary methodology." | Below radar chart, bar chart, risk heat map. |

```ts
.report-page { position:relative; overflow:hidden; }
.report-page::before {
  content:"CONFIDENTIAL"; position:absolute; top:50%; left:50%;
  transform:translate(-50%,-50%) rotate(-45deg);
  font-family:"Inter",Arial,sans-serif; font-size:72pt; font-weight:700;
  letter-spacing:8px; color:rgba(30,58,138,0.05);
  white-space:nowrap; pointer-events:none; user-select:none; z-index:0;
}
.report-page > * { position:relative; z-index:1; }
```

## 11b.5 Typography and Colours

| Element | Font | Size | Colour |
| --- | --- | --- | --- |
| Body | Inter/Arial | 11pt | #0F172A |
| Section headers | Inter Bold | 18pt | #1E3A8A |
| Finding headlines | Inter Bold | 14pt | #0F172A |
| Evidence notes | Inter Italic | 10pt | #374151 |
| Footer | Inter | 8pt | #64748B |
| Cover title | Inter Bold | 42pt | White on #1E3A8A |

| Colour | Hex |
| --- | --- |
| RAB Navy | #1E3A8A |
| RAB Blue | #2563EB |
| Dark | #0F172A |
| Grey | #64748B |
| Light Grey | #F8FAFC |
| Green | #166534 |
| Green BG | #DCFCE7 |
| Amber | #B45309 |
| Amber BG | #FEF3C7 |
| Red | #B91C1C |
| Red BG | #FEE2E2 |
| Dark Red | #7B0000 |
| Watermark | rgba(30,58,138,0.05) |

# 12. Visual Elements — All Specifications

| Visual | Page | Library | Data Source | Specification |
| --- | --- | --- | --- | --- |
| Score dial | Page 2 | D3.js/Chart.js arc | overall_score, rag_status | 200px circle. Score 56pt bold. RAG ring 12px. Controlled/At Risk/Weak/Critical Failure label. |
| Alert flag banners | Page 2 | HTML divs | alert_flags | One per flag. Red: Critical/Compliance. Amber: others. Hidden if empty. |
| 5 Index cards + RAB™ | Page 3 | HTML/CSS | Pre-calculated indices from payload | Index name \| value (large) \| RAG \| interpretation. "RAB Proprietary Methodology™" below each. |
| Confidence legend | Page 3 | HTML | confidence_legend from AI | 3 rows with badge: High (green) \| Medium (amber) \| Low (red) + definition. |
| Radar chart + attribution | Page 3 | Chart.js radar | All pillar/domain scores | 10pt PIR / 12pt SIR. Range 1-5. RAG fill. "© RAB..." below. |
| Bar chart sorted low-high + attribution | Page 3 | Chart.js horizontal | All scores | Bar colour = RAG. Score at end of bar. "© RAB..." below. |
| 30/60/90 summary | Page 3 | HTML table | priority_plan | 3 rows: horizon \| headline \| named role. Summary only. |
| RAG heat map grid | Profile top | HTML grid | pillar/domain scores | All codes. RAG background. Score shown. Sorted lowest first. |
| Confidence badge | Per finding | HTML badge | confidence from AI | HIGH (green) \| MEDIUM (amber) \| LOW (red). Next to score. |
| Risk register table | Risk section | HTML table | risk_register from AI | Risk \| Probability \| Impact \| Owner \| Current Control \| Action. |
| Risk heat map 3×3 | After risk table | D3.js scatter | risk_register probability+impact | 3×3 grid. Axes: Probability (L/M/H) × Impact (L/M/H). Green BL, amber middle, red TR. Risks plotted as numbered points. "© RAB..." below. |
| RAID summary 4-cell box | RAID section PIR | HTML grid | raid_summary from AI | 4 cells: Total Risks \| Critical \| No Owner \| Overdue. Large number + label. Amber/Red colouring where elevated. |
| 30/60/90 full table | Priority Plan | HTML table | priority_plan | 5 cols: # \| Action (named condition) \| Owner \| Deadline \| Done Condition. |
| Compliance table | Compliance section | HTML table | compliance_risk_signals | Finding \| Domain \| Obligation \| Action \| Deadline. |

# 13. Appendix — Database Extract. Not AI.

## 13.1 Tier 1 Query

```sql
SELECT r.question_id,q.question_text,q.pillar_code,r.score,
  CASE WHEN r.score>=4 THEN 'Controlled' WHEN r.score>=3 THEN 'At Risk'
       WHEN r.score>=2 THEN 'Weak' ELSE 'Critical Failure' END rag,
```

**r.evidence_note**

```sql
FROM question_responses r JOIN questions q ON q.id=r.question_id
WHERE r.assessment_id=:id ORDER BY q.order_index;
```

## 13.2 Tier 2 — Add These Columns

**r.confidence_level, r.respondent_role, r.document_source, r.stakeholder_divergence_note**

## 13.3 Methodology Note — Last Page of Appendix (Hard-Coded)

"ABOUT RAB INTELLIGENCE INDICES — The indices in this briefing are proprietary commercial intelligence signals developed by RAB Consulting Services. They are not standard industry metrics. They are weighted composites calculated by the RAB Platform scoring engine. The AI generation engine uses them — it does not compute them. © 2026 RAB Consulting Services Ltd. All rights reserved."

# 14. CRM Webhook and Security

## 14.1 Webhook Payload

```ts
// POST /api/webhooks/crm — fires after: assessment calculated + consent_given=true
// Auth: HMAC-SHA256 in X-RAB-Signature. Secret: CRM_WEBHOOK_SECRET env var.
// Retry: 3× exponential (1s, 3s, 9s)
{ assessmentId, assessmentType, tier, timestamp:ISO8601,
```

**lead:{name,email,company,phone|null},**

**consentGiven:true, consentTimestamp:ISO8601,**

**overallScore, ragStatus, actionIndicator, criticalFlag|null, alerts,**

**leadPriority:"High"|"Medium"|"Low",**

**topThreeInsightAreas:[{pillarOrDomain,score,insightLabel}],**

assessmentContext:{framework,deliveryStage|null,serviceContext|null,regulatoryContext|null},

**scoring_version:"1.0"**

```ts
}
// Priority: overallScore<3.0||criticalFlag = High. <=3.5 = Medium. else Low.
// High priority = email to rboukhiar@rabconsultingservices.com within 30 seconds.
// ZDR on every AI call (MANDATORY): "anthropic-beta":"zdr-2024-10-23"
```

## 14.2 Security

| Item | Requirement |
| --- | --- |
| Question IP | hidden_risk and score_anchors NEVER in any API response. No public endpoint for question_definitions. |
| ZDR header | Every Anthropic call: anthropic-beta: zdr-2024-10-23. Hardcoded. |
| Input validation | Score: integer 1-5. Email validated. Zod schemas on all inputs. |
| Rate limiting | 10 diagnostics/hour/IP on public endpoints. |
| Consent block | Lead save blocked at API level if consentGiven=false. |
| PDF URLs | Pre-signed, expire after 72 hours. |

# 15. Launch Checklist — 31 Items. All Mandatory.

Items marked [REDA] require Reda Boukhiar personal review. Contact: rboukhiar@rabconsultingservices.com | +44 7717 544322. Do not mark [REDA] items complete without Reda confirmation.

| # | Item | Owner | Done |
| --- | --- | --- | --- |
| 1 | Platform fixes (Step 2): lead capture order, TYPE_SELECT hidden, stage before Q1, time claim | Dev | [ ] |
| 2 | D11 naming fix in database and both JSON files | Dev | [ ] |
| 3 | PIR 27 additional questions seeded — COUNT(*) = 127 PIR FULL | Dev | [ ] |
| 4 | SIR 43 additional questions seeded — COUNT(*) = 127 SIR FULL | Dev | [ ] |
| 5 | P2.F9 in database with stage_note populated — verify with SELECT | Dev | [ ] |
| 6 | 18 PIR stage notes run — SELECT COUNT(*) WHERE stage_note IS NOT NULL = 23 | Dev | [ ] |
| 7 | 5 SIR context notes run | Dev | [ ] |
| 8 | Pillar/domain name lookup in scoring engine — passed in all AI payloads | Dev | [ ] |
| 9 | SSI formula: (D2×1.4+D5×1.3)/2.7 — test case D2=3.5,D5=2.8 = 3.16 | Dev | [ ] |
| 10 | D11 weight 0.9→1.2 · SIR denominator 14.2→14.5 | Dev | [ ] |
| 11 | SMI and SIMI formulas verified against test cases | Dev | [ ] |
| 12 | RII formula added — P2.F9 NOT in RII — verify with test case | Dev | [ ] |
| 13 | All 11 scoring verification tests pass (Step 7.3) | Dev | [ ] |
| 14 | smi_simi_delta pre-calculated in SIR payload | Dev | [ ] |
| 15 | Gap 3 confirmed with Reda: portal exists or scope agreed for new build | Dev+Reda | [ ] |
| 16 | Consultant portal data entry form — all required fields present (Step 8b) | Dev | [ ] |
| 17 | All 6 prompts in config/ai.php — correct keys, verbatim paste | Dev | [ ] |
| 18 | Deprecated prompts marked in config/ai.php | Dev | [ ] |
| 19 | ReportService uses tier field for prompt selection | Dev | [ ] |
| 20 [REDA] | PIR snapshot test: cover letter uses product name not duration. Stage calibration fires for Build stage P7 test. BRI/VRI/DMI from payload. | Reda | [ ] |
| 21 [REDA] | SIR snapshot test: SSI/SMI/SIMI/BAU-RI from payload. smi_simi_delta used. Conversion sentence: "service improvement plan". | Reda | [ ] |
| 22 | Browsershot installed. DomPDF not used for full reports. | Dev | [ ] |
| 23 | ZDR header on every AI API call — confirmed in server log | Dev | [ ] |
| 24 | CRM webhook fires with correct payload, lead priority correct | Dev | [ ] |
| 25 | High-priority email to Reda within 30 seconds for score 2.5 test | Dev+Reda | [ ] |
| 26 | hidden_risk and score_anchors absent from all public API responses | Dev | [ ] |
| 27 | scoring_version 1.0 stamped on every assessment | Dev | [ ] |
| 28 | Client PDF email received within 30 seconds | Dev+Reda | [ ] |
| 29 [REDA] | PDF: CONFIDENTIAL watermark every page. RAB Proprietary™ on index cards. Chart attribution on all charts. Risk heat map present. RAID summary present (PIR). Root cause section present. | Reda | [ ] |
| 30 [REDA] | 5 complete test diagnostics reviewed. All scores match manual calculation. Cover letter: no duration, correct product name, [CONSULTANT TO COMPLETE] placeholder visible. | Reda | [ ] |
| 31 | EU Article 27 GDPR representative appointed before first EU client. Use GDPR-Rep.eu. | Reda | [ ] |

**RAB Consulting Services Ltd  |  Company No: 13683901  |  VAT: GB394822071**

rboukhiar@rabconsultingservices.com  |  +44 7717 544322  |  +212 670 914865  |  rabconsultingservices.com

© 2026 RAB Consulting Services Ltd. Developer Final Build Guide v5.0 — Confidential.
