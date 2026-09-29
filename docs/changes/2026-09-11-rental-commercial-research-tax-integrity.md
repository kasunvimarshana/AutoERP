# Rental commercial research and Tax integrity — 2026-09-11

Baseline: `bfe1f058c9861a21dee0ee148ea231e4aa104d9e`, verified latest `worktree-0.0.8` before editing and publication preparation.

The user authorized primary-source industry research where TACGL/video evidence is incomplete. Add `docs/vehicle-rental/commercial-research.md` with 15 scoped source entries, covering operator terms, billing period/credit examples, MySQL locking and Sri Lankan tax legislation including the 2026 amendments. Distinguish statutory applicability from commercial choices and engineering derivations. No vendor rate or contract threshold becomes a Rental default. Update the knowledge base's authority language and research conclusions. Reconcile three obsolete operational TODO entries against current routes and availability contracts; retain genuinely unfinished commercial and acceptance work.

The financial prerequisite audit reproduced four Tax defects before changing the service:

- Malformed calculation lines were silently omitted.
- Duplicate line numbers could produce ambiguous source-to-tax snapshot mapping.
- Inclusive header tax was added to the already-inclusive gross.
- Header withholding was added and subtracted, leaving no reduction in payment.

Validate all lines and unique line numbers before any determination. Use the existing calculated header payment effect instead of reconstructing it from unsigned tax totals. Preserve breakdowns, existing tax determination, non-contiguous valid source-line numbers, decimal arithmetic and before/after-tax adjustment ordering. Centralize the calculator's shared zero representation in a private named constant. No new tax rates, thresholds, posting accounts or Rental policy defaults.

Ownership/relationship review: these defects belong to Tax, not Rental. The current Tax API consumes the corrected service. No table, foreign key, inverse relationship, source relationship or module dependency changes are necessary. No historical financial rows are changed. No removed Rental code was inspected or restored.

Verification on the changed runtime:

- Four new regression failures reproduced against the baseline before the fix.
- Full PHP 8.3/SQLite suite: **726 tests, 8,161 assertions passed**.
- Tax engine coverage: **21 tests, 93 assertions**, including mixed inclusive/withholding header taxes with line tax and both adjustment stages.
- Frontend: **82 files, 303 tests passed**.
- ESLint, TypeScript, production Vite build, PHP Pint and whitespace review passed.
- `migrate:fresh --seed --force` succeeded against a newly created isolated SQLite database; no existing user database was used.

This change does not implement Rental commercial calculation/snapshot/consumption/handoff workflows. It does not establish all taxpayer classifications or configure statutory aggregation. Real MySQL contention, an installed production upgrade, full continuous video review and browser/UAT remain unverified. The previous exact-candidate protected-backup investigation remains unsuccessful; no new password evidence arose. Do not relabel these items complete. Free local tools and public primary sources only; no GitHub Actions or deployment.
