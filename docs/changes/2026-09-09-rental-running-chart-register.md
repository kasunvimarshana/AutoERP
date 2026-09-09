# Fresh Rental Running Chart register — 2026-09-09

Baseline: `0c221d67d033ace96b6f7479fa7f7beac71748e0`, verified against `worktree-0.0.8` before editing.

## Purpose and implementation

The operational TODO required a Running Chart register/detail view. Operators previously had to open each vehicle use separately. Add a fresh read-only register service, API resource/controller route, React page, navigation entry and route entitlement. Reuse chart-view permission and existing authenticated tenant/organization/feature context.

Search matches chart/vehicle/agreement/party labels. Optional enum state and explicit-offset period filters select overlapping charts with half-open endpoints. All search alternatives remain grouped inside organization and tenant scope. Original periods and quantities are displayed intact; filtering does not imply proration, billing eligibility, consumption or financial totals. Unknown measurements remain unknown. Detail review includes recorded measurements, AC mode, driver observation and notes, with lazy history.

Relationship review: no schema or relationship changes. The register traverses existing chart → use → agreements and predecessor links; it does not duplicate vehicle/customer/owner identity. Contextual resources expose readable names/references and replacement/correction lineage, without exposing agreement rates. This is Rental-owned operational evidence browsing; no Reporting/Invoice/Payment/Tax/Finance responsibility is moved into Rental.

The shared Rental fixture now owns the reusable actual-custody setup previously private to chart tests. Existing lifecycle tests retain their assertions. All implementation is fresh; no removed Rental code was restored or reused.

Source meaning remains based on the existing TACGL/video evidence ledger (especially V04 physical chart context). Register filter behavior is explicitly documented as engineering retrieval semantics, not a newly discovered commercial rule. Knowledge base, operational contract and TODO are updated accordingly.

## Verification

- Backend register tests cover exact overlap boundaries, preserved whole-chart quantities, party/vehicle search, state filtering, organization/tenant isolation, malformed filters and chart-view authorization.
- Frontend tests cover readable party/lineage context, unknown versus zero, explicit filter submission, empty results and hiding stale rows after failed filtering.
- Full PHP/SQLite suite: **700 tests, 7,913 assertions passed**.
- Full Vitest suite: **80 files, 296 tests passed**.
- TypeScript, Rental/navigation/entitlement ESLint, PHP Pint, production Vite build and whitespace checks passed.
- Free local tools only; no GitHub Actions or production deployment.

This completes the Running Chart register/detail TODO, not the entire Rental module. Financial calculations/handoffs, driver identity, broader reports, full audiovisual review, real MySQL concurrency and production/browser acceptance remain outstanding. No new financial policy was guessed.
