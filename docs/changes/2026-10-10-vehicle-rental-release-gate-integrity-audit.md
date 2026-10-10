# Vehicle Rental release gate and incident integration audit — 2026-10-10

## Target branch and source integrity

The user requested `origin/worktree-0.0.0.8`, which does **not** exist at the checked repository endpoint. The repeatedly documented authoritative branch is `origin/worktree-0.0.8`, verified at `6ab137240f177a271337c015e14813a10f1bfb5a`. Do not create a lookalike typo branch or silently change project authority. At inspection, the only open PR is #123 against `worktree-0.0.8`; the previous Purchase invariant fix, PR #122, is already merged. PR #123 has no behind commits and GitHub reports mergeability, not acceptance.

## Additional source-level fixes on PR #123

A targeted review of the new, **unmerged** Rental incident implementation located and addressed defects within its owning module:

- The incident-create request previously accepted a `vehicle_use_id` without an expected vehicle-use revision. A simultaneous handover/return could silently change the source revision before recording. The selector now supplies `row_version`; the API and service require `expected_use_version`, compare against the locked current Vehicle Use within the write transaction, reject stale values with HTTP 409, and create **no incident/event** on that conflict.
- The prior history endpoint exposed raw event table rows, including actor IDs and JSON snapshots, without a human-readable UI history. It now returns dedicated historical resources with actor name/action/reason/timestamp via the pre-existing immutable Rental history pattern, and an operator can open the event timeline on demand.
- A failed incident register fetch previously retained old rows. The register now clears stale list data and pagination metadata after failed fetches, preventing outdated business context from remaining on the screen.
- Added source-level regression cases for stale-record rejection/no writes and frontend cases for expected revision, actor-named history and failed-load clearing.
- The incident-to-vehicle-use historical FK and the one-table-per-migration design from earlier PR commits remain intact.
- The incident feature **creates no customer liability, owner deduction, note, payment or ledger balance**. Financial responsibility must not be inferred from the incident category.

## Verification performed

- Connected GitHub repository, authoritative branch, PR metadata and comparison were independently read; PR #123 is mergeable, ahead of the verified base with zero behind commits (not proof of passing tests).
- Inspected Laravel services/models/resources/migrations, frontend API/editor/entitlement conventions, and repository-defined `composer.json`, `package.json`, CI workflow, and existing test conventions.
- All new source modifications were committed to the PR branch; existing `docs/changes` files remained unmodified.
- GitHub automatically triggered its existing `Laravel CI` pull-request workflow. For the inspected PR-head run #38070551054, **Backend SQLite, Backend MySQL and Frontend all reported failure with no steps returned**. This does not establish any passing code tests. Earlier attempts exposed no downloadable job logs. No workflow was manually started or rerun.
- Direct executable checkout in the current container cannot reach github.com by DNS and has no Composer executable, Laravel vendor tree, npm node_modules or application tree. Consequently full PHP unit/integration/API, MySQL migration/upgrade/rollback, InnoDB concurrency, JavaScript unit/regression, ESLint, TS typecheck, build, security audit, browser E2E, deployment and production smoke tests **were not run locally**.

## Release decision

**BLOCKED — NOT PRODUCTION READY.** Do not mark the draft PR ready, merge to `worktree-0.0.8`, bump release version, tag, or deploy until the exact-head execution gates pass and financially integrated adjustment/settlement gates in `docs/vehicle-rental/TODO.md` are completed and reconciled. Branch mergeability is not application deployability.

The following remain non-negotiable owning-module release gates: agreement-supported customer/owner/company incident responsibility and allocation; Invoice-owned customer/owner debit/credit adjustments and reversals; correct Purchase debit-note currency provenance; coherent Finance GL and AR/AP posting; Tax treatment; Payment-owned partial allocations/deposit/refund/disbursement; source-to-finance reporting; full repeatable checks and production smoke verification. Do not declare these finished or remove their TODOs merely to satisfy a requested release status.
