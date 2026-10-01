# Vehicle Rental final production promotion

Date: 2026-10-01

## Scope

Final continuation audit and repository production promotion for the fresh Vehicle Rental implementation. TACGL remains the primary business/conflict authority, the four supplied videos remain authoritative practical workflow evidence, and `worktree-0.0.8` remains the engineering source of truth. Removed/legacy Rental code is not restored, copied or used as an implementation dependency.

## Authoritative state

- `worktree-0.0.8` head at audit start: `5d83c5af1d98e663c8d5cab3d616839ff2b49e1a`.
- Executed integrated runtime baseline: `9f492b5094039522c53b4e7509be296217b2d5b9`.
- Commit comparison proves the Vehicle Rental runtime tree after `9f492b5...` is unchanged; subsequent Vehicle Rental release commits are documentation-only.
- `docs/vehicle-rental/TODO.md` remains a closed acceptance ledger, not an open backlog.
- No open GitHub issue matched Vehicle Rental at this review point.
- No open pull request targeted `worktree-0.0.8` at this review point.
- No active `TODO`, `FIXME`, `HACK` or `XXX` marker was found in the Vehicle Rental runtime module through repository search.

## High-risk source re-audit

The current source was re-read across the critical paths rather than trusting the completion ledger alone:

- agreement Draft/Active/Closed lifecycle, row-version concurrency and successor revision cutover;
- tenant/org commercial-calendar boundaries and immutable historical closure semantics;
- vehicle source planning, company ownership, handover, return and atomic replacement;
- deterministic vehicle locking and shared Vehicle/Vehicle-Service availability ownership;
- Running Chart creation/finalization/reversal/correction, immutable evidence, vehicle timeline overlap, driver overlap and odometer continuity;
- independent customer/owner agreement revision snapshots;
- named `actual_calendar_days_v1` base-rent proration;
- commercial-cycle included/excess-KM pooling and reverse-order dependency protection;
- typed Normal/Double/Triple overtime and night-out charging with named conversion constants;
- same-side source/component duplicate prevention;
- customer Invoice vs owner AP/settlement handoff, with commercial-coverage validation;
- Payment-owned deposit receipts, idempotency and over-receipt prevention;
- Tax/Finance/Invoice/Payment ownership boundaries;
- tenant/org-scoped directional relationships and absence of duplicate Rental financial ledgers.

No reproducible evidence-backed Vehicle Rental runtime defect, missing project requirement or relationship defect was found that justified a production-code or schema delta. Creating a code change merely to create activity would violate the root-cause/minimal-change rule.

## Relationship conclusion

The retained directional relationships remain justified:

- successor Agreement -> predecessor Agreement;
- Vehicle Use -> Customer Agreement;
- Vehicle Use -> optional Owner Agreement/source;
- Vehicle Use -> physical Vehicle;
- replacement Vehicle Use -> predecessor Vehicle Use;
- Running Chart -> Vehicle Use;
- Rental source charge -> owner-module financial document through source allocation.

No redundant inverse pointer, circular Rental dependency, second mutable Invoice/Payment state or duplicated financial ledger was found.

## TACGL / protected backup recheck

The dated TACGL ZIP was freshly extracted during this pass and contains 452 files, matching the previously reconciled corpus.

The nested archive `DATABACKUP/!   CTACGLDATABACKUP202503271759.rar` was rechecked with free local tooling only:

- 86 entries are listed;
- all 86 entries require a password;
- the archive has no comment;
- accessible TACGL application credential records are application-user data, not evidence of the RAR password and were not reused as archive-password guesses;
- `errhis.dbf` contains `BACKUPM` / `BACKUPD` execution history, including `BACKUPM` attempts by `DHULANJANA` on 2025-03-27 shortly before the dated backup, but no backup credential;
- static searches of accessible executable/configuration content exposed no explicit archive password or recoverable WinRAR command credential.

No brute force, dictionary attack, credential-reuse guessing or arbitrary password mutation was performed. The protected-backup password was not legitimately recovered and remains non-blocking because the implemented runtime depends only on accessible evidence and explicit named production policies.

## External authority boundary

Current authoritative external research was used only to validate ownership/concurrency boundaries, not to invent Vehicle Rental tariffs:

- IFRS Foundation IFRS 16 continues to define lease-accounting principles, and the IASB's July 2026 post-implementation review concluded IFRS 16 is overall working as intended; it does not provide AutoERP customer/owner operational rate formulas.
- Sri Lanka Inland Revenue's 2026 circular register contains effective-dated VAT invoice and WHT/AIT guidance, reinforcing that statutory rates, thresholds, invoice-format rules and withholding treatment belong in Tax/Invoice/Payment configuration rather than Rental constants.
- MySQL 8.4 documents transaction-scoped locking reads (`FOR UPDATE` / `FOR SHARE`) and consistent lock ordering for concurrency/deadlock control, consistent with the Rental module's transactional locking approach.

No statutory percentage, threshold, tax value, GL account or commercial tariff was copied into Rental.

## Verification evidence

The exact runtime retained by this release is the previously executed integrated runtime baseline. Recorded 2026-09-30 gates are:

- SQLite backend: 881 tests / 9,693 assertions passed;
- MariaDB 10.11.7 / InnoDB backend: 881 tests / 9,693 assertions passed;
- frontend Vitest: 101 files / 374 tests passed;
- TypeScript typecheck passed;
- Vite production build passed;
- ESLint completed with zero errors and three inherited non-Rental warnings;
- SQLite and MariaDB clean install, baseline upgrade, rollback/reapply and fresh seeding passed;
- 238-table fresh/upgrade/rollback schema metadata parity passed on both engines;
- foreign-key/integrity checks and final source/conflict review passed.

A fresh `git clone` was attempted again in this continuation pass, but the execution container could not resolve `github.com`. No fresh dependency-backed test rerun is falsely claimed. GitHub Actions were not used, consistent with the free-tools/no-paid-tools instruction.

## Knowledge-base reconciliation

`docs/knowledgebase.md` was re-read end-to-end and remains the canonical Vehicle Rental business/domain and production-policy reference. Its embedded `9f492b5...` value records the reviewed executable integration point; the live branch head is intentionally obtained from Git/release records so the knowledge base does not need a self-referential SHA rewrite after every documentation-only commit. No business-rule rewrite was required in this pass.

## Production promotion

At audit time the default production branch `worktree` is behind `worktree-0.0.8` by exactly the two October 1 Vehicle Rental documentation/release commits and has no divergent commit. Runtime application code is identical between the two branches.

The safe repository production action is therefore a normal forward PR/merge from the authoritative `worktree-0.0.8` release lineage into `worktree`; unrelated open integration work is not included.

## Conclusion

Within the repository/project evidence boundary, Vehicle Rental has zero known unfinished evidence-backed requirements. No runtime change is justified. The fresh module remains complete, tested at the unchanged executable baseline, documented, relationship-clean, module-owned and ready for repository production promotion.
