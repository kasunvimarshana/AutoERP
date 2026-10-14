# Incident-to-Running-Chart evidence selection — 2026-10-11

## Source and scope
- Staged integration branch based on reviewed TACGL/video business sources, existing canonical knowledge base and fresh Incident Register (PR #123). No old Rental code was restored.
- The Incident service already accepted an optional same-Vehicle-Use `running_chart_id` and validated its tenant/org/use relationship. However, the new UI never supplied it: the practical workflow could not link a specific Running Chart to an incident.

## Module-owned fix
- Rental Incident API now provides a paginated minimum-data chart selector for a chosen Vehicle Use, with Incident.Record permission and tenant/org scoping. Unscoped Vehicle Uses are rejected before any chart can be shown.
- Rental Incident React register displays the optional chart selector, clears selections when the vehicle assignment changes, sends an explicit chart ID or null and displays recorded chart provenance.
- No billing, owner settlement, credit, tax or payment rule changes; evidence confirmation still does not create money.
- No unrelated modules or migrations modified. Running Chart/Vehicle Use associations and foreign keys are not altered, avoiding speculative relationships.

## Verification status
- Authored a PHP service regression test for same-use chart lookup and cross-organization rejection.
- Authored a React regression test for chart selection and preserved the original unlinked-evidence case.
- Checked the source anchors and changes against the staged GitHub branch before writing.
- Composer/vendor, npm node_modules and application DB are unavailable to this execution context; executable PHPUnit, TypeScript/ESLint, Vitest, build and API smoke were not run. CI remains blocked by issue #124.
- `docs/vehicle-rental/TODO.md` §R financial release gates remain open. This limited feature is not proof of production readiness.

**Decision:** Staged on integration candidate only; do not promote to release without executed verification.
