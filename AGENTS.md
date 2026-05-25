# RAB Codex Working Rules

## Status Tracking

- After every implementation task, update `docs/RAB_IMPLEMENTATION_STATUS_AUDIT.md`.
- Do not rewrite the whole audit unless necessary.
- Always update Implementation Log.
- Update Critical Blockers only if blocker status changed.
- Update Next Safest Action after each task.

Never mark PIR/SIR Tier 1/Tier 2 complete unless the full owner-defined workflow is proven:
admin create assessment → score questions → save evidence → build payload → send Claude request → store response → render report → download PDF.

Fake Claude or fake PDF tests count as partial technical proof only.
