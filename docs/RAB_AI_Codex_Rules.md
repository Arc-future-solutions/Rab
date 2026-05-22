# RAB Platform — AI Section: Codex Agent Rules

**Owner:** Reda Boukhiar — RAB Consulting Services Ltd
**Applies to:** All AI generation work in the RAB Platform AI section
**Stack:** Laravel · Next.js 14 · TypeScript · PostgreSQL · Prisma · Vercel
**Last updated:** May 2026

---

## 0. Ground Rule — No Invention, No Assumptions

The AI agent must not invent, infer, or hallucinate any value, structure, field, section, or behaviour. Every decision must be traceable to either:

- A field explicitly present in the payload received from the Laravel service, or
- A rule explicitly stated in this document.

If something is not in the payload and not in these rules — do not do it. Stop and flag the gap.

---

## 1. Service Architecture — What Has Changed

The n8n webhook has been **replaced** by a **Laravel service**. The AI agent must operate within this new architecture:

| Component | Requirement |
|---|---|
| AI trigger | The Laravel `ReportService` calls the AI. There is no n8n webhook. |
| Prompt source | All prompts are stored in `config/ai.php`. The agent reads them from there. No prompts are hardcoded elsewhere. |
| Prompt selection | Selection is based on the `tier` field in the payload. Do not guess the prompt. Read `tier` and map it. |
| Output | The AI returns structured JSON only. No prose wrapping. No markdown. No preamble. |

---

## 2. Payload Contract — Only Use What Is Given

### 2.1 Core Rule

**The AI does not calculate any numeric value.** All indices, scores, and deltas arrive pre-calculated in the payload from the Laravel scoring engine. The agent uses them verbatim.

### 2.2 Values the AI Must Never Calculate

| Value | Rule |
|---|---|
| `BRI`, `VRI`, `DMI`, `RII`, `CHI` | PIR indices — read from payload, never compute |
| `SSI`, `SMI`, `SIMI`, `BAU-RI` | SIR indices — read from payload, never compute |
| `smi_simi_delta` | Read from payload. Do not subtract `SMI` minus `SIMI` yourself. |
| `overall_score` | Read from payload. Do not average pillar or domain scores. |
| Pillar/domain scores | Read from `pillar_scores` or `domain_scores` in payload. Do not re-derive. |
| RAG status | Read from payload. Do not re-derive from score thresholds. |

### 2.3 Payload Fields the Agent Must Respect

The agent must consume these fields when present and must not fabricate them when absent:

- `delivery_stage` (PIR) — values: `Mobilisation`, `Design`, `Build`, `Test`, `Cutover`, `PostGoLive`
- `service_context` (SIR) — values: `NSI`, `Established`, `UnderPressure`, `Transformation`, `LegacyPreRetirement`
- `regulatory_context` — values: `null`, `fca_uk`, `dora_eu`, `nhs_cqc`, `public_sector`, `gdpr_only`
- `pillar_names` — full display names from the lookup. Always use these, never abbreviate.
- `domain_names` — full display names from the lookup. Always use these, never abbreviate.
- `programme_name`, `client_company`, `service_name` — use exactly as provided.
- `alert_flags` — use from payload exactly. Do not add or remove flags.
- `stakeholder_notes` — Tier 2 PIR only. If `null`, set `stakeholder_intelligence` to `null`.
- `confidence_level` per response — `High`, `Medium`, or `Low` only.
- `programme_value` / `annual_service_cost` — if present, anchor all business impact estimates to this figure.

---

## 3. Prompt Rules — Per Prompt Key

### 3.1 Prompt Selection Logic

```
tier field in payload → prompt key in config/ai.php

pir + snapshot     → pir_snapshot
sir + snapshot     → sir_snapshot
pir + full_tier1   → pir_full_tier1
pir + full_tier2   → pir_full_tier2
sir + full_tier1   → sir_full_tier1
sir + full_tier2   → sir_full_tier2
```

If `tier` is missing or unrecognised, throw an exception. Do not default to any prompt.

### 3.2 Rules Shared Across All Prompts

- **Ghost mode:** Write as Reda Boukhiar, Director, RAB Consulting Services. Senior consulting practitioner. Not AI-generated.
- **British English only:** programme, behaviour, recognise, organisation, analyse, prioritise, colour, favour.
- **Banned words (all prompts):** leverage, utilise, holistic, robust, granular, actionable, streamline, empower, ecosystem, synergy, impactful, going forward, learnings, journey.
- **Banned openers:** "It is worth noting", "It is important to", "In conclusion", "This highlights".
- **No duration references in cover letters.** Do not reference days spent, time taken, or diagnostic duration.
- **Output:** Valid JSON only. No preamble. No markdown fences. No disclaimers inside JSON.

### 3.3 SIR-Specific Language Rules

- Use **domains**, not pillars.
- Never use: programme, go-live, cutover, ERP, pillar (in a SIR report).
- Additional banned phrases for SIR: "service improvement journey", "mature your ITSM", "best practice framework".
- **D11 naming is mandatory:** Always `"Service Tooling, CMDB & Knowledge Management"`. Never `"Automation, Tooling & Knowledge"` or any other variant.

### 3.4 PIR-Specific Language Rules

- Use **pillars**, not domains.
- Never use: domain, ITSM, SLA (in a PIR report).

---

## 4. Stage and Context Calibration — Required Behaviour

### 4.1 PIR Stage Calibration

The agent must adjust findings based on `delivery_stage`. Apply these rules exactly:

| Condition | Behaviour |
|---|---|
| Work not yet due for current stage | Use FORWARD ALERT format: `"Not yet required at [stage] — expected by [next stage]."` |
| Work due but absent | Apply full urgency. No softening. |
| Mobilisation | P5, P6, P7, P9 are all forward alerts. |
| Design | Mock migrations, cutover, hypercare = forward. P6 design = current. |
| Build | Data quality and test prep are due. Cutover rehearsals not yet due. |
| Test | UAT, integration, reconciliation are due. Cutover plan must exist. |
| Cutover Prep | Everything is due. No forward alerts. All gaps are risks. |
| Go-Live / Hypercare | BAU transition is critical. P4 adoption is in execution mode. |

### 4.2 SIR Service Context Calibration

| Context | Behaviour |
|---|---|
| NSI | D7 = immediate risk. BAU-RI is the primary signal. Below 2.5 = service cannot absorb introduction. State explicitly. |
| UnderPressure | If D2 < 3.0 = StabilityAlert. No softening. |
| Transformation | BAU-RI is primary. Below 2.5 = cannot absorb change. State explicitly. |
| Established | Reference trajectory. |
| LegacyPreRetirement | Continuity and wind-down focus. Not improvement. |

---

## 5. Scope Rules — What Gets Covered

### 5.1 PIR Scope

| Tier | Scope |
|---|---|
| Snapshot | 3 insight cards: 3 lowest pillars (or 2 lowest + compliance card if `regulatory_context` set and any compliance question below 3.0). |
| Full Tier 1 | All pillars below 3.0. Above 3.0 only if there is a compliance finding. |
| Full Tier 2 | All pillars below 4.0. Above 4.0 only if there is a compliance finding. |

### 5.2 SIR Scope

| Tier | Scope |
|---|---|
| Snapshot | 3 insight cards: 3 lowest domains (or 2 lowest + compliance card — same rule as PIR). |
| Full Tier 1 | All domains below 3.0. |
| Full Tier 2 | All domains below 4.0. |

### 5.3 RAID Summary

RAID summary is **PIR only**. Never generate a RAID summary section in a SIR report.

---

## 6. Output Schema Compliance

### 6.1 The Agent Must Return Only These Top-Level Keys (Full PIR Tier 1)

```json
{
  "cover_letter": "...",
  "executive_position": "...",
  "intelligence_dashboard": {
    "overall": {},
    "indices": {},
    "alert_flags": [],
    "confidence_legend": {}
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
  "tier1_bridge": "...",
  "compliance_risk_signals": null
}
```

- Tier 2 replaces `tier1_bridge` with evidence pack status and adds `stakeholder_intelligence`.
- SIR reports omit `raid_summary`.
- Snapshot reports return only `intelligence_brief` and `insight_cards`.

### 6.2 Field-Level Rules

- `cover_letter` must end with: `Reda Boukhiar, Director, RAB Consulting Services.`
- `cover_letter` must contain `[CONSULTANT TO COMPLETE: specific milestone or date]` placeholder — do not fill it in.
- `intelligence_brief` Para 1: 70–100 words. Para 2: 55–75 words.
- `intelligence_brief` Para 2 (PIR snapshot) must end exactly: `'A full Programme Intelligence Review would establish the root causes with evidence and produce a prioritised action plan.'`
- `intelligence_brief` Para 2 (SIR snapshot) must end exactly: `'A full Service Intelligence Review would establish the root causes with evidence and produce a prioritised service improvement plan.'`
- `insight_cards`: exactly 3 cards. Each `finding` max 220 characters. Each `action` max 160 characters.
- `priority_plan` actions: Tier 1 = 2–4 per horizon. Tier 2 = 4–5 per horizon.
- `final_position`: use only the permitted strings as defined in the prompts. No hedging or partial phrases.
- `compliance_risk_signals`: only generate if `regulatory_context` is set. Always close with: `'Operational intelligence only. Engage legal and compliance advisers.'`

### 6.3 Index Card Interpretation Thresholds

| Index | Threshold | Required text |
|---|---|---|
| RII < 2.5 | PIR | `"RII of [X] means risk management has broken down — RAID log does not reflect the true programme position."` |
| SSI < 2.5 | SIR | `"SSI of [X] means fundamental instability. Nothing else matters first."` |
| smi_simi_delta > 0.3 | SIR | `"SMI of [smi] against SIMI of [simi] — delta [delta] — means maturity built but not used to improve. Strategic gap, not process gap."` |
| BAU-RI < 2.5 (Transformation context) | SIR | State explicitly that service cannot absorb change. |

---

## 7. Quality Standard — Writing Rules

The agent must test every sentence before outputting it. Apply these filters:

### 7.1 Evidence Standard

| | |
|---|---|
| **Passing** | `"The Programme Director confirmed the last SteerCo escalation was November 2025. Three critical risks raised. None resolved in 5 working days."` |
| **Failing** | `"Governance processes appear weak."` |

### 7.2 Business Impact Standard

| | |
|---|---|
| **Passing** | `"Without a named Change Authority, scope changes above £50,000 are approved informally."` (anchor to `programme_value` if in payload) |
| **Failing** | `"This may cause issues."` |

### 7.3 SIR Specificity Standard

| | |
|---|---|
| **Passing** | `"D2 scored 2.8. Three P1 incidents had no PIR. That is the process."` |
| **Failing** | `"Incident management processes present challenges."` |

### 7.4 Sentence Variety

Vary sentence length. A short sentence after a complex one signals authority. Do not use uniform sentence length throughout.

### 7.5 Generic Sentences Test

Test every sentence: could it appear unchanged in a different programme or service report? If yes — rewrite it with specific evidence from the payload.

---

## 8. D11 — Mandatory Naming Rule

In every SIR output, Domain 11 must be named exactly: **"Service Tooling, CMDB & Knowledge Management"**

When D11 scores below 3.0, the CMDB accuracy must be the primary named finding. Required text:

> "The CMDB is the foundation of service management. If inaccurate: change impact assessment is guesswork. Incident routing is personal knowledge. DR planning cannot be executed by anyone other than those who built the estate."

---

## 9. Security Rules — Non-Negotiable

| Rule | Requirement |
|---|---|
| Hidden fields | `hidden_risk` and `score_anchors` must never appear in any AI response or any API response returned to a client. |
| Input validation | Scores must be integers 1–5. Enforce via Zod schemas before the AI call. |
| Rate limiting | 10 diagnostics/hour/IP on public endpoints. |
| Consent block | Lead data must never be saved if `consentGiven` is false. Block at API level. |
| PDF URLs | Pre-signed. Expire after 72 hours. |

---

## 10. PDF and Report Rendering Rules

The agent's JSON output feeds the PDF renderer. The following rules govern what the agent must and must not do to support correct rendering.

| Rule | Requirement |
|---|---|
| PDF library | Browsershot (Spatie). Never DomPDF. |
| Watermark | Agent must not render watermarks — the Blade template handles this. Do not include watermark instructions in JSON. |
| Compliance table | Only generate `compliance_risk_signals` if `regulatory_context` is set. |
| Appendix | The Evidence Base appendix is generated from a **database extract — not AI**. The agent must not generate appendix content. |
| Methodology note | The methodology note on the last appendix page is hard-coded. The agent does not write it. |
| Chart data | The agent provides score values in the JSON schema. It does not render charts. Charts are built by the frontend from `intelligence_profile` and `risk_register` data. |

---

## 11. Pillar and Domain Name Reference

Always use the full display name from the payload's `pillar_names` or `domain_names` fields. Never abbreviate or shorten these.

**PIR Pillars (reference only — use payload values):**
P1 — Governance & Decision-Making · P2 — Planning, Stage Gates & Delivery Control · P3 — Business Alignment, Value & Financial Control · P4 — Change Management, Training & Adoption · P5 — Data Readiness, Migration & GDPR · P6 — Solution, Process Fit & UAT · P7 — Cutover, Go-Live, Decommissioning & Archiving · P8 — Delivery Capability, Security & RACI · P9 — Operational, Automation Readiness & Data Archiving · P10 — Digital & Transformation Maturity

**SIR Domains (reference only — use payload values):**
D1 — Service Governance & Ownership · D2 — Incident & Major Incident Management · D3 — Service Request Management · D4 — Problem Management · D5 — Change & Release Management · D6 — Service Performance, SLA & Reporting · D7 — Service Transition & BAU Readiness · D8 — Service Operations & Support Model · D9 — Supplier & Vendor Management · D10 — Operational Resilience & Continuity · D11 — Service Tooling, CMDB & Knowledge Management · D12 — Service Intelligence & Continuous Value

---

## 12. What the Agent Must Never Do

- Never calculate any score, index, or delta. Read from payload.
- Never fill in `[CONSULTANT TO COMPLETE]` placeholders. Leave them verbatim.
- Never reference days, hours, or time spent in a cover letter.
- Never use DomPDF. Never reference it.
- Never expose `hidden_risk` or `score_anchors` in any output.
- Never generate appendix content. That is a database extract.
- Never add, remove, or modify `alert_flags`. Use exactly what is in the payload.
- Never generate `compliance_risk_signals` if `regulatory_context` is `null`.
- Never generate a `raid_summary` in a SIR report.
- Never use "Tier 1" or "Tier 2" in client-facing report content.
- Never use Tier 2 `stakeholder_intelligence` content in a Tier 1 report.
- Never default to a fallback prompt if `tier` is unrecognised. Throw and flag.

---

## 13. Items Requiring Reda Review Before Going Live

The following items require Reda Boukhiar's personal sign-off and must not be marked complete without confirmation:

| Item | What to check |
|---|---|
| PIR snapshot test | Cover letter uses product name not duration. Stage calibration fires correctly. BRI/VRI/DMI read from payload. |
| SIR snapshot test | SSI/SMI/SIMI/BAU-RI read from payload. `smi_simi_delta` used. Conversion sentence: "service improvement plan". |
| AI output language | British English throughout. No banned words. No AI-sounding phrases. |
| PDF visual review | CONFIDENTIAL watermark on every content page. RAB Proprietary™ on all index cards. Chart attribution on all charts. Risk heat map present. RAID summary present (PIR). Root cause section present. |
| 5 complete test diagnostics | All scores match manual calculation. Cover letter has no duration. `[CONSULTANT TO COMPLETE]` placeholder visible. |

Contact: rboukhiar@rabconsultingservices.com | +44 7717 544322

---

*© 2026 RAB Consulting Services Ltd. Confidential. Internal use only.*
