# Vehicle Rental knowledge-base authority refresh

Date: 2026-09-29
Branch: `worktree-0.0.8`
Reviewed head before this documentation change: `2c80584446536fa8b1ebfec4c05adbd129706f45`

## Scope

Reconcile the canonical Vehicle Rental business knowledge base with the user-supplied TACGL archives, all four supplied videos, `RULES.md` / `AGENTS.md`, and the latest authoritative implementation branch.

## Source reconciliation

- `TACGL.zip` SHA-256: `0e0733fff720072af4c3aaa787995ff128bfa79060a37739d6d2ebbe18a25313`.
- `TACGL(20260929-141809).zip` SHA-256: `79c240494943437978754169c3360bb7c6e35d911ef8263c4b2d6b6246384d77`.
- Both ZIPs contain 452 non-directory business files. After removing the outer `TACGL/` wrapper used only by the canonical ZIP, normalized relative paths and every file SHA-256 are identical. The dated upload therefore adds no conflicting business evidence.
- `TACGL.rar` was retained as corroborating packaging; no business rule is inferred from inaccessible encrypted backup content.
- Video hashes/durations were revalidated and match the canonical knowledge-base evidence set.

## Implementation reconciliation

The authoritative branch already contains the fresh Vehicle Rental module and a closed acceptance ledger. The latest reviewed head strengthens, rather than changes, the documented business model:

- migration correction keeps published migration identities while making foreign-key drops portable across SQLite/MySQL;
- agreement successor cutover now uses actual custody state correctly: Planned uses planned end, Returned uses actual return, and open/In-Custody usage blocks cutover until actual return;
- tenant/organization commercial-calendar boundaries remain Configuration-owned;
- no legacy Rental code is restored and no compatibility workaround is introduced.

No new business tariff, tax percentage, withholding threshold, proration convention, downtime credit, replacement surcharge, deposit-forfeiture rule, or other monetary rule was invented during this reconciliation.

## Documentation change

`docs/knowledgebase.md` is refreshed to:

1. identify TACGL as primary/tie-breaker and the four videos as authoritative practical workflow evidence;
2. record the 2026-09-29 dated archive as content-equivalent to the canonical corpus;
3. reference current authoritative implementation head `2c80584446536fa8b1ebfec4c05adbd129706f45` as the reviewed code baseline;
4. keep explicit evidence classes and uncertainty handling;
5. preserve the simple operator flow while documenting hidden integrity controls;
6. preserve the closed TODO/acceptance ledger instead of reopening already-completed implementation scope.

## Verification

Executed in this reconciliation session:

- SHA-256 checks for TACGL ZIP/RAR and all four videos;
- archive inventory comparison for both ZIP uploads;
- normalized path + per-file SHA-256 equality comparison across all 452 TACGL business files;
- video duration verification with `ffprobe`;
- GitHub branch-head verification;
- latest Vehicle Rental commit review;
- canonical knowledge-base and closed TODO ledger review.

Not executed in this documentation-only change:

- Laravel test suite;
- MySQL suite;
- frontend tests/typecheck/lint/build;
- browser UAT.

Those executable gates were not rerun because this reconciliation changes documentation only and does not alter runtime code, schema, APIs, permissions, or UI.
