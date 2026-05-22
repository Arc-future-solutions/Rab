# RAB Application — AI Report Sections by Assessment Type

This document extracts the AI-generated report sections required for each RAB assessment report type.

The application has two assessment frameworks:

- **PIR** — Programme Intelligence Review
- **SIR** — Service Intelligence Review

Each framework has three report variants:

- **Snapshot**
- **Full Tier 1**
- **Full Tier 2**

The six active AI prompt keys are:

| Assessment | Report Type | Active Prompt Key |
|---|---|---|
| PIR | Snapshot | `pir_snapshot` |
| SIR | Snapshot | `sir_snapshot` |
| PIR | Full Tier 1 | `pir_full_tier1` |
| PIR | Full Tier 2 | `pir_full_tier2` |
| SIR | Full Tier 1 | `sir_full_tier1` |
| SIR | Full Tier 2 | `sir_full_tier2` |

---

## 1. PIR Snapshot Report — AI Sections

**Prompt key:** `pir_snapshot`

AI must generate:

```json
{
  "intelligence_brief": "...",
  "insight_cards": []
}
```

### Section 1 — `intelligence_brief`

Two paragraphs.

#### Paragraph 1

Must include:

- 70–100 words.
- Two weakest PIR pillars with scores.
- `pillar_names` from payload.
- Explicit reference to `delivery_stage`.
- Interpretation of:
  - `BRI`
  - `VRI`
  - `DMI`
- Any compounding weak-pillar combination.

#### Paragraph 2

Must include:

- 55–75 words.
- One specific action per weak area.
- Named role.
- Specific deliverable.
- Exact closing sentence:

> A full Programme Intelligence Review would establish the root causes with evidence and produce a prioritised action plan.

### Section 2 — `insight_cards`

Three cards.

Default logic:

- Use the three lowest PIR pillars from `pillar_scores`.
- Each card must be specific to the pillar.
- Each card requires:
  - `pillar_code`
  - `pillar_name`
  - `score`
  - `rag`
  - `finding`
  - `action`

Compliance logic:

- If `regulatory_context` is set and any compliance question is below 3.0:
  - Cards 1–2: two lowest pillars.
  - Card 3: `COMPLIANCE SIGNAL`.

---

## 2. SIR Snapshot Report — AI Sections

**Prompt key:** `sir_snapshot`

AI must generate:

```json
{
  "intelligence_brief": "...",
  "insight_cards": []
}
```

### Section 1 — `intelligence_brief`

Two paragraphs.

#### Paragraph 1

Must include:

- 70–100 words.
- Two weakest SIR domains with scores.
- `domain_names` from payload.
- Explicit reference to `service_context`.
- Interpretation of:
  - `SSI`
  - `SMI`
  - `SIMI`
  - `BAU-RI`
- If `smi_simi_delta > 0.3`, explain the maturity-versus-improvement gap.

#### Paragraph 2

Must include:

- 55–75 words.
- One specific action per weak area.
- Named role.
- Specific deliverable.
- Exact closing sentence:

> A full Service Intelligence Review would establish the root causes with evidence and produce a prioritised service improvement plan.

### Section 2 — `insight_cards`

Three cards.

Default logic:

- Use the three lowest SIR domains.
- Use service/domain language, not programme language.
- Each card requires:
  - `domain_code` or `pillar_code`, depending implementation naming
  - `domain_name`
  - `score`
  - `rag`
  - `finding`
  - `action`

Special D11 rule:

- If D11 is below 3.0, the finding must focus on **CMDB accuracy**, not generic tooling.

Compliance logic:

- If `regulatory_context` is set and any compliance question is below 3.0:
  - Cards 1–2: two lowest domains.
  - Card 3: `COMPLIANCE SIGNAL`.

---

## 3. PIR Full Tier 1 Report — AI Sections

**Prompt key:** `pir_full_tier1`

### Scope

- All PIR pillars scoring **below 3.0**.
- Pillars at 3.0+ only if there is a compliance finding.

AI must generate these sections:

```json
{
  "cover_letter": "...",
  "executive_position": "...",
  "intelligence_dashboard": {},
  "intelligence_profile": [],
  "reporting_accuracy_risk_finding": null,
  "risk_register": [],
  "raid_summary": {},
  "root_cause_analysis": {},
  "priority_plan": {},
  "final_position": "...",
  "tier1_bridge": "...",
  "compliance_risk_signals": null
}
```

### Section 1 — `cover_letter`

AI writes a 150-word cover letter.

Must include:

- Programme name.
- Client company.
- Main finding requiring a decision.
- `[CONSULTANT TO COMPLETE: specific milestone or date]`.
- Specific decision for the Executive Sponsor.
- Signature: Reda Boukhiar, Director, RAB Consulting Services.

### Section 2 — `executive_position`

AI writes 100–130 words.

Must include:

- `overall_score` and what it means at the current `delivery_stage`.
- Two or three weakest pillars with scores.
- `BRI`, `VRI`, and `DMI` implications.
- Compounding combinations.
- One specific Executive Sponsor decision.

### Section 3 — `intelligence_dashboard`

AI generates interpretation for dashboard data.

Contains:

- `overall`
  - score
  - RAG
  - stage
- `indices`
  - `bri`
  - `vri`
  - `dmi`
  - `rii`
  - `chi`
- `alert_flags`
- `confidence_legend`

Important rule:

- AI interprets values but must **not calculate** them.

### Section 4 — `intelligence_profile`

AI generates one block per pillar below 3.0.

Each block contains:

- `pillar_code`
- `pillar_name`
- `score`
- `rag`
- `confidence`
- `headline`
- `evidence`
- `business_impact`
- `compliance_dimension`
- `action`

Also includes separate findings for:

- `REPORTING_ACCURACY_RISK`, if true in payload.
- `BAU_TRANSITION`, if P9 is below 3.0.

### Section 5 — `risk_register`

AI generates top 5–7 risks.

Each risk contains:

- `risk_title`
- `probability`
- `impact`
- `owner`
- `current_control`
- `action`

Risk titles must be named conditions, not generic categories.

### Section 6 — `raid_summary`

PIR only.

Contains:

- `total_risks`
- `critical_risks`
- `issues_without_owner`
- `overdue_actions`
- `assessment`

### Section 7 — `root_cause_analysis`

Contains:

- 300–400 word narrative.
- `primary_cause`.
- `causal_chain`, 3–5 steps.

### Section 8 — `priority_plan`

30/60/90 plan.

Contains:

- `30_days`: 3–4 actions.
- `60_days`: 2–3 actions.
- `90_days`: 2–3 actions.

Each action needs:

- `action_title`
- `owner`
- `deadline`
- `done_condition`

### Section 9 — `final_position`

Must choose one of:

- `Viable trajectory with focused intervention`
- `Requires structured recovery before go-live can proceed`
- `Go-live should not proceed until identified conditions are resolved`

Also includes:

- `tier1_bridge`
- `compliance_risk_signals`, if regulatory context exists.

---

## 4. PIR Full Tier 2 Report — AI Sections

**Prompt key:** `pir_full_tier2`

### Scope

- All PIR pillars scoring **below 4.0**.
- Pillars at 4.0+ only if there is a compliance finding.

AI must generate these sections:

```json
{
  "cover_letter": "...",
  "executive_position": "...",
  "intelligence_dashboard": {},
  "stakeholder_intelligence": {},
  "intelligence_profile": [],
  "reporting_accuracy_risk_finding": null,
  "risk_register": [],
  "raid_summary": {},
  "root_cause_analysis": {},
  "priority_plan": {},
  "final_position": "...",
  "evidence_validated_statement": "...",
  "compliance_risk_signals": null
}
```

### Section 1 — `cover_letter`

Same base requirement as PIR Tier 1, but calibrated for a deeper Tier 2 review.

Must include:

- Programme name.
- Client company.
- Main finding requiring decision.
- `[CONSULTANT TO COMPLETE: specific milestone or date]`.
- Specific decision for the Executive Sponsor.
- Signature: Reda Boukhiar, Director, RAB Consulting Services.

### Section 2 — `executive_position`

Same base requirement as PIR Tier 1, but broader because Tier 2 covers all pillars below 4.0.

Must include:

- `overall_score`.
- Current `delivery_stage`.
- Weakest pillars with scores.
- Index interpretation.
- Compounding risk combinations.
- Specific Executive Sponsor decision.

### Section 3 — `intelligence_dashboard`

Same structure as PIR Tier 1.

Contains:

- `overall`
- `indices`
- `alert_flags`
- `confidence_legend`

### Section 4 — `stakeholder_intelligence`

This is the main additional Tier 2 section.

Uses `stakeholder_notes` from payload.

Contains:

- `divergence_summary`
- `divergence_areas`
- `governance_implication`

If `stakeholder_notes` is null:

```json
{
  "stakeholder_intelligence": null
}
```

### Section 5 — `intelligence_profile`

Expanded from Tier 1.

Tier 2 covers:

- All pillars below 4.0.
- Pillars at 4.0+ only if there is a compliance finding.

Each profile block contains:

- `pillar_code`
- `pillar_name`
- `score`
- `rag`
- `confidence`
- `headline`
- `evidence`
- `business_impact`
- `compliance_dimension`
- `action`

### Section 6 — `risk_register`

Same base structure as PIR Tier 1.

Each risk contains:

- `risk_title`
- `probability`
- `impact`
- `owner`
- `current_control`
- `action`

### Section 7 — `raid_summary`

PIR only.

Contains:

- `total_risks`
- `critical_risks`
- `issues_without_owner`
- `overdue_actions`
- `assessment`

### Section 8 — `root_cause_analysis`

Contains:

- Root cause narrative.
- `primary_cause`.
- `causal_chain`.

### Section 9 — `priority_plan`

Expanded Tier 2 30/60/90 plan.

Contains:

- `30_days`: 4–5 actions.
- `60_days`: 4–5 actions.
- `90_days`: 4–5 actions.

Each action needs:

- `action_title`
- `owner`
- `deadline`
- `done_condition`

### Section 10 — `final_position`

Tier 2 does **not** use `tier1_bridge`.

Instead, it includes:

- `final_position`
- `evidence_validated_statement`
- `compliance_risk_signals`, if regulatory context exists.

---

## 5. SIR Full Tier 1 Report — AI Sections

**Prompt key:** `sir_full_tier1`

### Scope

- All SIR domains scoring **below 3.0**.

AI must generate these sections:

```json
{
  "cover_letter": "...",
  "executive_position": "...",
  "intelligence_dashboard": {},
  "intelligence_profile": [],
  "risk_register": [],
  "root_cause_analysis": {},
  "priority_plan": {},
  "final_position": "...",
  "tier1_bridge": "...",
  "compliance_risk_signals": null
}
```

Important distinction:

- SIR has **no `raid_summary`** section.

### Section 1 — `cover_letter`

AI writes a 150-word Service Intelligence Review cover letter.

Must include:

- Service name.
- Client company.
- Main finding requiring decision.
- `[CONSULTANT TO COMPLETE]`.
- Signature: Reda Boukhiar, Director, RAB Consulting Services.

### Section 2 — `executive_position`

AI writes 100–130 words.

Must include:

- `overall_score` and what it means in the `service_context`.
- `SSI`
- `SMI`
- `SIMI`
- `BAU-RI`
- `smi_simi_delta`, if above 0.3.
- Two or three weakest domains.
- Compounding combinations.
- One specific CIO or IT Director decision.

### Section 3 — `intelligence_dashboard`

Contains:

- `overall`
  - score
  - RAG
  - service context
- `indices`
  - `ssi`
  - `smi`
  - `simi`
  - `bau_ri`
  - `chi`
- `alert_flags`
- `confidence_legend`

### Section 4 — `intelligence_profile`

AI generates one block per domain below 3.0.

Each block contains:

- Domain code/name.
- Score.
- RAG.
- Confidence.
- Headline.
- Evidence.
- Business impact.
- Compliance dimension, if relevant.
- Action.

Special D11 rule:

- If D11 appears, the finding must name **CMDB accuracy** as the primary issue.

### Section 5 — `risk_register`

Service-focused risk register.

Each risk contains:

- `risk_title`
- `probability`
- `impact`
- `owner`
- `current_control`
- `action`

### Section 6 — `root_cause_analysis`

Service-specific causal analysis.

Examples:

- D5 causing D2.
- D1 governance gap preventing D4 resolution.
- KEDB not maintained because problem management lacks governance mandate.

### Section 7 — `priority_plan`

30/60/90 service improvement plan.

Each action needs:

- `action_title`
- `owner`
- `deadline`
- `done_condition`

### Section 8 — `final_position`

Must choose one of:

- `Stable and improving with focused action`
- `Stable but not resilient — improvement programme required`
- `Needs stabilisation before further transformation can be absorbed`

Also includes:

- `tier1_bridge`.

### Section 9 — `compliance_risk_signals`

Only if `regulatory_context` is set.

Can reference:

- FCA
- DORA
- NHS/CQC

Always closes with:

> Operational intelligence only. Engage legal and compliance advisers.

---

## 6. SIR Full Tier 2 Report — AI Sections

**Prompt key:** `sir_full_tier2`

### Scope

- All SIR domains scoring **below 4.0**.

AI must generate these sections:

```json
{
  "cover_letter": "...",
  "executive_position": "...",
  "intelligence_dashboard": {},
  "stakeholder_intelligence": {},
  "intelligence_profile": [],
  "risk_register": [],
  "root_cause_analysis": {},
  "priority_plan": {},
  "final_position": "...",
  "evidence_validated_statement": "...",
  "compliance_risk_signals": null
}
```

Important distinction:

- SIR Tier 2 has **no `raid_summary`**.

### Section 1 — `cover_letter`

Same base requirement as SIR Tier 1, but calibrated for a deeper Tier 2 service review.

### Section 2 — `executive_position`

Same base requirement as SIR Tier 1, but broader because Tier 2 covers domains below 4.0.

Must include:

- `overall_score`.
- `service_context`.
- `SSI`.
- `SMI`.
- `SIMI`.
- `BAU-RI`.
- `smi_simi_delta`, where relevant.
- Weakest domains.
- Specific CIO or IT Director decision.

### Section 3 — `intelligence_dashboard`

Contains:

- `overall`
- `indices`
- `alert_flags`
- `confidence_legend`

### Section 4 — `stakeholder_intelligence`

Uses `stakeholder_notes`.

Primary mapping:

- IT Director view.
- Service Manager view.

Contains:

- `divergence_summary`
- `divergence_areas`
- `governance_implication`

If `stakeholder_notes` is null:

```json
{
  "stakeholder_intelligence": null
}
```

### Section 5 — `intelligence_profile`

Expanded from Tier 1.

Tier 2 covers:

- All domains below 4.0.
- Domains at 4.0+ only if there is a compliance finding.

Each profile block contains:

- Domain code/name.
- Score.
- RAG.
- Confidence.
- Headline.
- Evidence.
- Business impact.
- Compliance dimension, if relevant.
- Action.

Special D11 rule:

- If D11 appears, the finding must name **CMDB accuracy** as the primary issue.

### Section 6 — `risk_register`

Service-focused risk register.

Each risk contains:

- `risk_title`
- `probability`
- `impact`
- `owner`
- `current_control`
- `action`

### Section 7 — `root_cause_analysis`

Service-specific root cause analysis.

### Section 8 — `priority_plan`

Expanded Tier 2 30/60/90 plan.

Contains:

- `30_days`: 4–5 actions.
- `60_days`: 4–5 actions.
- `90_days`: 4–5 actions.

Each action needs:

- `action_title`
- `owner`
- `deadline`
- `done_condition`

### Section 9 — `final_position`

Must choose one of:

- `Stable and improving with focused action`
- `Stable but not resilient — improvement programme required`
- `Needs stabilisation before further transformation can be absorbed`

### Section 10 — `evidence_validated_statement`

Tier 2 includes an evidence validation statement instead of a Tier 1 bridge.

Also includes:

- `compliance_risk_signals`, if regulatory context exists.

---

## Developer Implementation Mapping

Use this prompt-selection logic:

```ts
if (framework === "PIR" && assessmentType === "SNAPSHOT") {
  promptKey = "pir_snapshot";
}

if (framework === "SIR" && assessmentType === "SNAPSHOT") {
  promptKey = "sir_snapshot";
}

if (framework === "PIR" && assessmentType === "FULL" && tier === "TIER_1") {
  promptKey = "pir_full_tier1";
}

if (framework === "PIR" && assessmentType === "FULL" && tier === "TIER_2") {
  promptKey = "pir_full_tier2";
}

if (framework === "SIR" && assessmentType === "FULL" && tier === "TIER_1") {
  promptKey = "sir_full_tier1";
}

if (framework === "SIR" && assessmentType === "FULL" && tier === "TIER_2") {
  promptKey = "sir_full_tier2";
}
```

---

## Key Implementation Rule

AI should generate the **narrative/report intelligence sections only**.

The following should come from the app/backend/template layer, not from AI:

- Scores.
- Indices.
- RAG statuses.
- Alert flags.
- Question data.
- Appendix extracts.
- Charts.
- PDF layout.
