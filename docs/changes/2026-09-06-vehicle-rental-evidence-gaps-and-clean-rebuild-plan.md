# Vehicle Rental evidence corrections and clean rebuild plan — 2026-09-06

## Purpose

Independently inspect the re-supplied TACGL archives and video evidence, correct concrete gaps in the current knowledge base, and make the fresh implementation backlog executable without reviving removed Rental code or inventing business policies.

Baseline: `kasunvimarshana/AutoERP`, `worktree-0.0.8`, `d4aaa693706c2d3fe693244c8ea0f8d9e4ae326c`.

## Verified source work

- Compared every non-directory file in `TACGL.zip`, `TACGL(9).zip` and `TACGL.rar` by normalized relative path and SHA-256: all 452 files match.
- Verified the four media hashes and durations against the source register.
- Confirmed uploaded and current root/docs AGENTS/RULES files are identical.
- Inspected DBF schemas and business records, report text, and XLS inventories/headers; extracted all PDF text and surveyed video frames across the full media durations with selected native-resolution checks.
- Independently reconciled `LCH2005407 -> INV2005519 -> REC2003089 allocation -> GL`, including the receipt's 12 allocations and balanced invoice/receipt journals.
- Reconciled all six active source lines of `INV2005580` to 289,400, including meal 3,800 and highway/parking 3,150.
- Reconfirmed 25 active positive Rental Payment debits across 21 vouchers and six normalized vehicle-registration collision groups.

## Knowledge corrections

- Added record-level E01–E16 and media-position V01–V07 evidence anchors.
- Corrected the daily examples: 14 × 8,000 is self-drive car hire; the with-driver jeep example is 3 × 35,000.
- Replaced the unanchored 544 × 50 example with the directly inspected 544 × 300 deleted/active evidence.
- Documented a confirmed narrative/stored-amount conflict: `LCH2005408` says 1,080 × 90, while the source line, invoice and GL retain 81,000.
- Documented invalid `31/09/2025` narratives without changing or silently correcting historical data.
- Distinguished duration notations: `24.30` is consistent with hours/minutes in one transaction, while `7.5` is consistent with decimal hours in another. Neither is a universal parser rule.
- Added distance-priced van hiring separately from excess-distance billing.
- Clarified that a September 13/30 arithmetic fit cannot distinguish fixed-30 from actual-month proration.
- Distinguished TOPRO AUTO CARE archive accounting evidence from the dedicated AT Tours rental application shown in the videos; schemas and aliases must not be conflated.
- Added explicit handling for unknown versus zero, external-driver context, timing/calendar policies and conflicting displayed party labels.

## Current implementation reconciliation

Reviewed current Vehicle availability/ownership, Vehicle Service blocking/status, Invoice creation/allocation/lifecycle/reversal, Payment public request restrictions, Tenant feature retirement, Invoice UI retirement and Finance posting vocabulary.

The fresh rebuild needs coordinated owner-module contracts. The existing availability interface does not by itself prove cross-organization resource safety or reciprocal workshop/rental concurrency safety. These are recorded as focused test/design work, not asserted runtime defects without reproduction.

No removed Rental source files, migrations, routes or tests were restored, copied or used as implementation dependencies. Current shared owner-module code was read to establish integration boundaries.

## Coverage and completion limits

This refresh is **not** a completed continuous audiovisual audit or a production module delivery:

- narration was not fully transcribed/reviewed;
- interval frames can miss short actions and cannot establish spoken-only rules;
- the nested backup could not be read because the available libarchive reader reports unsupported solid-RAR extraction;
- compiled legacy applications were not executed or decompiled;
- the dedicated rental agreement/Running Chart dataset shown in the videos has not been located in the inspected archive tables;
- PHP/Composer are not on PATH in this environment, and no Laravel/database/browser runtime test suite was run;
- no live production database was accessed.

These limits remain explicit in the canonical knowledge base and TODO. Full module implementation, integration and UAT remain outstanding. Existing architecture/backlog content is retained; this change adds evidence, corrections, concrete dependencies and acceptance cases rather than rewriting history or claiming the backlog is complete.

## Files changed

- `docs/knowledgebase.md`
- `docs/vehicle-rental/TODO.md`
- this append-only change record

## Validation

Exact-decimal assertions checked source arithmetic, E02's confirmed discrepancy, both GL balances, the receipt allocation total, all six `INV2005580` lines, deleted/active evidence and Rental Payment counts. Documentation checks verified policy IDs, current integration paths, relative links and clean patch whitespace.

No runtime files, migrations, permissions, routes, providers or workflows changed. No paid tools or GitHub Actions were used for validation. The documentation commit includes `[skip ci]` to avoid triggering the existing push CI workflow.
