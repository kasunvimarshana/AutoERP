# Vehicle Use register — 2026-09-10

Baseline: `d7a05c285800f4008cfca65913b1e80e8f88c76f`, latest verified `worktree-0.0.8` before editing.

Add a fresh Rental-owned read-only Vehicle Use register across agreements in the selected tenant and organization. Operators can search vehicle/agreement/party labels, filter enum states and original planned-period overlap, distinguish actual custody from planned dates, and expand odometer observations and immutable history. Replacement predecessor and company/owner supply remain readable. No prices, distance totals or utilization percentages are inferred.

Use the existing use-view permission, tenant/organization/feature middleware, paginated resource and eager-loaded canonical relationships. A user with use-view alone can reach the register without agreement management rights. Group search and open-ended interval predicates within trusted context. Open-ended plans remain unbounded; touching endpoints do not overlap. Invalid timestamps/states/periods fail explicitly, and failed filter requests hide stale results.

Relationship review: no schema, foreign-key, inverse relationship or module-ownership changes. Existing use-to-agreement and directed replacement links provide the required context. No removed AutoERP Rental code was restored or reused. The scratch workspace reverted during development; recover the published fresh baseline, recreate the uncommitted register and repeat verification before publishing.

Update `docs/knowledgebase.md`, the operational contract and the TODO to distinguish completed register browsing from remaining actual-period utilization, complete lineage reports and financial scope. Preserve legitimate open requirements; do not delete them as obsolete.

Verification on the final recreated implementation:

- Full PHP 8.3/SQLite suite: **713 tests, 8,081 assertions passed**, including fresh migration/architecture checks and real-login acceptance.
- Frontend: **81 files, 300 tests passed**.
- PHP Pint, ESLint, TypeScript, production build and whitespace checks passed.
- Backend register tests cover bounded/open-ended planned overlap, state filters, unknown readings, no contextual rate exposure, tenant/organization search isolation and invalid filters. Authenticated tests cover view-only access, denied permissions/features and completed-custody retrieval. UI tests cover readable context, lazy history, explicit-offset filters and stale-result suppression; navigation tests cover independent use-view access.

No migration is added or changed. SQLite test setup exercises fresh schema creation; real MySQL contention, production upgrade rehearsal and browser/UAT remain unverified. Free local tools only, no GitHub Actions or production deployment. Commercial versions, canonical driver identity, calculations/consumption, financial handoffs, unresolved policy gates and complete audiovisual review remain outstanding. This change does not complete the entire Vehicle Rental module.
