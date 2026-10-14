# Safe Git integration: sell-module + worktree → worktree-0.0.8 candidate (2026-10-10)

## Exact branches and history

The user-provided spelling `worktree-0.0.0.8` does not exist. The authoritative **target** is `worktree-0.0.8` at `6ab137240f177a271337c015e14813a10f1bfb5a`; source branches are `sell-module` at `b095644d3dbe3e47bca7b877c1ad8cf44e7e724a` and `worktree` at `c7519acab6f66e89757b970735dc678757f2cb2b`. No changes were made to source branches or to the target. Local working-tree uncommitted changes cannot be examined from the GitHub API; no local worktree was touched.

Staging branch: `integration/sell-module-worktree-into-0.0.8-20261010` from the exact target. Commit `a3fe97084c8e66533b0a00368f89f350d2b9b802` merges `worktree` history with the target's original tree unmodified; comparison confirmed that `worktree`'s tree equals its common ancestor tree. Commit `89a2431f0dfeaa2cd22dad2277f21c67372cb09f` merges the original `sell-module` HEAD as the second parent of the fully reconciled staging source.

## Content reconciliation evidence

The target and `sell-module` diverged at `a333eb14e13f099bff7b7852481edc3b6e23fe0a`; source has 13 unique commits and 130 changed paths, target 176 unique commits at the starting comparison.

- **116 source-only changed paths** adopted at their exact sell Git blob SHA (51 additions, 65 modifications), where the target blob equaled the common base.
- **14 concurrently modified files** three-way reconciled. Seven had disjoint line hunks and were combined after verifying both change sets reconstructed their source. Other conflicts were explicitly resolved:
  - Payment resource: retain target settlement transitions and source card-brand metadata.
  - Vehicle Service job: retain target admission service and source job-line service injection.
  - Payment detail UI: retain settlement and allocation panels and instrument state columns; add source card-brand column.
  - Vehicle Service line editor: same original line deletion performed once.
  - Service sales summary: combine new multi-level report layout and target cleanup; remove superseded unused hook/type imports.
  - Payment preparation tests: deduplicate the same expected text change.
  - Vehicle Service Payment Prepare: use the seller's redesigned direct/credit and card/cash workflow, retaining the target's stable payment method dependency and cancelled queued auto-default update. Follow-up fixes retain backend-supported cheque and digital/mobile-wallet payment methods and other method selection, so a three-kind UI does not silently remove existing payment capabilities.
- All **541 target-only changed paths** retained at exact target SHA, all 116 source-only changed paths retained at exact source SHA. The staged candidate changed exactly the 130 source-touched paths before this documentation record, with no unexpected paths changed.

## Discovered test misalignment

The original `sell-module` version of `VehicleServicePaymentPreparePage.test.tsx` still expected a retired two-step `Review payment` / `prepareVehicleServicePayment` workflow. The selling page now directly finalizes. Tests were corrected **in the owning Vehicle Service test** to exercise finalization and financial payload, and new cheque/wallet cases were added. These are authored tests, **not executed or passing tests**.

## Validation evidence and release decision

- GitHub branch heads, two-parent merge ancestry, Git tree identity, unique/source-only blob preservation, overlapping file inventory and final integration compare were independently checked. The staging PR is #126, intentionally draft. Earlier source-to-staging PR #125 is closed and superseded.
- The repository's existing GitHub Actions jobs (SQLite, MySQL, Frontend) fail before executing any steps on both the unchanged target and draft integration head; issue #124 records the reproducible platform/runner-layer blocker. It is not evidence for an application test result.
- Local checkout from github.com fails DNS resolution; complete Laravel/Composer, node_modules and MySQL infrastructure are unavailable in the local container. PHP unit/integration/API, database migrations/rollback, TypeScript, lint, Vitest, build, security/performance, browser E2E and production smoke have **not passed or been executed** for this candidate.
- No destructive Git operation, remote-history rewrite, target merge, version bump, release tag or deployment took place. The separately unverified Rental incident PR #123 remains outside this source-branch merge.

**Decision: RELEASE BLOCKED / NOT PRODUCTION READY.** Resolve the root GitHub runner issue, execute all checks on the exact final SHA, correct discovered failures in responsible modules, and obtain full application acceptance before merging PR #126 or promoting to production.
