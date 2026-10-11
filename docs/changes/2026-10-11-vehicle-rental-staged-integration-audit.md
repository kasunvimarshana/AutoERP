# Combined Vehicle Rental release-candidate integration — 2026-10-11

## Business authority and branch scope
- TACGL remains primary business evidence, corroborated by the already-audited four videos. New uploaded copies were independently content-hash-verified in the latest authoritative knowledge base; no new tariff, tax or financial liability may be inferred.
- Authoritative target `worktree-0.0.8` @ `0b28bb6372d5ada787a514e020080aa2cb8c2fc8`; the mistyped `worktree-0.0.0.8` does not exist.
- Integration candidate includes seller/worktree merge `38c2c3907b0092d9e34a9d294dab40568474ecec` (draft PR #126), factual Rental incident evidence `27c90f1f73562f5821c40fa3b99498bcc6752c55` (draft PR #123), and latest target records `0b28bb6372d5ada787a514e020080aa2cb8c2fc8`.
- This is an **isolated staging branch**. The production branch, release tags and runtime data are unchanged. Legacy Rental code is not revived.

## Exact source and conflict reconciliation
- PR #126 already reconciles seller/worktree with original target, retaining its source branch history and the concurrent Payment/Vehicle Service improvements as recorded in its dated audit.
- PR #123 modifies 34 paths; only `resources/js/app/router.tsx` overlaps PR #126's changed paths. This router retains all seller/worktree routes and adds the Rental incident import, lazy page and permission-guarded route.
- Of the remaining paths, 32 incident file blobs are retained at their exact source Git blob SHAs. `docs/knowledgebase.md` is manually combined to preserve PR #123's incident facts and the target's new source-identity verification. The target's append-only `2026-10-11-vehicle-rental-upload-source-reverification.md` remains byte-identical.
- Source revisions are retained as ordered merge parents; no force-push, squash or source branch rewrite. The TODO ledger contains an evidence-only achievement and still lists all open financial release gates.
- Factual incident confirmation **does not** establish owner/customer responsibility or authorize financial notes, deductions or payments.

## Quality assurance and release boundary
- Actual verification performed: GitHub branch/source SHA checks, per-path blob preservation, exact conflict intersection and manual semantic reconciliation for the sole router overlap plus knowledge base. **No complete application test was executed on this candidate.**
- Full checkout fails shell DNS resolution of github.com; Composer/vendor, npm node_modules and application database are unavailable. Laravel/API/SQLite/MySQL, migration and InnoDB contention, TypeScript/lint/Vitest/build, UI/permissions, E2E UAT, financial ledger tie-out, dependency/security audit, backup/restore and deployment smoke tests remain **not run** for this integrated candidate.
- Existing GitHub Actions fail before any test steps execute on the target branch (issue #124). No paid tools or manual GitHub Actions were used.
- `docs/vehicle-rental/TODO.md` §R remains open for incident liability approval, debit/credit note lifecycles, Tax/Finance FX/GL correctness, Payment settlement, reporting and integration/release tests.

**Decision: STAGED / NOT PRODUCTION READY.** Do not promote to `worktree-0.0.8`, tag or deploy until actual free-tool release verification and outstanding business/finance acceptance gates pass on the exact final SHA.
