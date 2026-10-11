# Rental incident audit UI and stale-read safeguards — 2026-10-10

## Source and intent

Review performed on the unmerged feature branch intended for authoritative `worktree-0.0.8`. This is a targeted change to the fresh Rental-owned incident evidence workflow; no retired Rental code or invented monetary rule is involved.

## Changes

1. Incident recording now requires `expected_use_version` from the guided Vehicle Use selector. The Rental backend validates and compares that version with the row-locked source inside its write transaction. A stale use fails HTTP 409 without an incident or audit event.
2. Incident event history now reuses the existing `ImmutableRentalHistory` ownership model, with a dedicated history resource returning named actor, action, reason and timestamp. The React register exposes the operator-facing audit timeline on demand. The immutable raw snapshots remain database audit evidence and are not exposed as unstructured UI actor-ID rows.
3. Failed incident register reads clear previously loaded entries and do not display the misleading `No incident evidence recorded` message when the backend fails.
4. New focused backend/frontend test sources cover stale use rejection, expected-version submission, actor-labelled history, and safe list-failure rendering. Their presence is **not** evidence of successful execution.

## Verification and release boundary

The new migrations retain one explicit table per file and the required source-use-revision FK. GitHub branch/PR comparison reports mergeable with no commits behind the canonical base, but this does not establish runtime correctness. The existing, automatically triggered `Laravel CI` jobs for PR head `345a8559d1d5e1c7050405e1546b8c7ade76fad9` (run 38070730953) report **failure** for Backend MySQL, Backend SQLite and Frontend with no job steps returned. No GitHub Actions runs were manually requested. A full executable checkout, Composer dependencies, JS project dependencies and MySQL service are unavailable in the tool environment; no passing backend/JavaScript/build/migration/UAT results can be asserted.

**Do not merge, tag, bump release version, or deploy the draft PR as a production release.** Full application and financial acceptance gates in `docs/vehicle-rental/TODO.md` are still outstanding. The requested `origin/worktree-0.0.0.8` does not exist on the connected GitHub remote; the verified source authority remains `worktree-0.0.8`.
