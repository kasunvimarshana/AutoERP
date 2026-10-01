# Vehicle Rental production-readiness release

Date: 2026-10-01

## Scope

This record closes the requested end-to-end Vehicle Rental production-readiness review against the authoritative `worktree-0.0.8` branch.

Business authority remains TACGL as the primary conflict tie-breaker plus the four supplied Vehicle Rental videos as practical workflow evidence. Engineering constraints remain root `RULES.md` / `AGENTS.md`. The removed legacy Rental implementation remains prohibited as an implementation dependency.

## Authoritative state reviewed

- Release-candidate branch head at review start: `e9ce7ec11c1238a35bcd5ad6ad1eb380587d5d98`.
- Last executable integrated runtime head: `9f492b5094039522c53b4e7509be296217b2d5b9`.
- `9f492b5... -> e9ce7ec...` contains exactly two documentation files: `docs/knowledgebase.md` and the 2026-10-01 knowledge-base reconciliation record.
- Therefore the application/runtime tree at the release candidate is identical to the previously executed and verified integrated runtime.
- `docs/vehicle-rental/TODO.md` contains no unchecked implementation items and remains a closed acceptance ledger rather than an open backlog.
- No open pull request currently targets `worktree-0.0.8`.

## Runtime re-audit

Critical Vehicle Rental paths were re-read at source level rather than accepted only from the closed ledger:

- agreement Draft/Active/Closed lifecycle and immutable successor revisions;
- tenant/org commercial-calendar boundaries;
- vehicle source/use planning, handover, return and atomic replacement;
- shared Vehicle / Vehicle-Service availability integration;
- Running Chart physical evidence, finalization, reversal/correction and odometer continuity;
- independent customer and owner agreement snapshots;
- named base-rent proration;
- cycle-based included/excess-KM assessment;
- typed Normal/Double/Triple OT and night-out charging;
- same-side duplicate source/component prevention;
- Invoice/AP handoff and commercial coverage checks;
- Payment-owned deposit receipts and idempotency;
- Tax/Finance ownership boundaries;
- tenant isolation, expected-version conflicts, immutable history and one-way relationship lineage;
- human-readable agreement-first frontend workflow and associated frontend tests.

No reproducible evidence-backed Vehicle Rental runtime defect or missing project requirement was found that justified changing production code in this release pass. Introducing code merely to create a delta would violate the project's root-cause/minimal-change rule.

## Relationship review

The current directional relationships remain justified and were retained:

- successor agreement -> predecessor agreement;
- Vehicle Use -> Customer Agreement;
- Vehicle Use -> optional Owner Agreement/source;
- Vehicle Use -> physical Vehicle;
- replacement Vehicle Use -> predecessor Vehicle Use;
- Running Chart -> Vehicle Use;
- Rental source charge -> owner-module financial document through source allocation.

No redundant inverse pointer, circular production-module dependency, duplicate financial ledger, or second mutable Invoice/Payment state was identified in Rental. Customer and Owner commercial aggregates remain separate because they represent distinct legal/economic sides, not because of legacy screen structure.

## TACGL and video reconciliation

The supplied 2026-10-01 TACGL ZIP and canonical TACGL ZIP contain the same normalized 452-file business payload. The dated package introduces no conflicting business evidence. The four supplied videos remain consistent with the canonical operator flow and do not justify additional approval stages, guessed tariffs or copied legacy repair procedures.

The current knowledge base already records this reconciliation and was merged immediately before this release review. No duplicate rewrite was made here because no new business rule was discovered.

## Protected backup investigation

The protected nested backup `DATABACKUP/!   CTACGLDATABACKUP202503271759.rar` was investigated only with free local/static tooling and source-derived evidence.

Confirmed:

- RAR v2/solid archive;
- 86 listed encrypted entries;
- archive reports password protection;
- no archive comment;
- outer TACGL payload contains an accessible `tacdata/password.DBF`, but its rows are application-user credential records and are not evidence of the RAR password;
- TACGL error history contains `BACKUPM` / `BACKUPD` executions, including activity by `DHULANJANA` around the dated backup, but it contains no backup credential;
- static executable/configuration searches exposed no explicit archive password or WinRAR command containing a recoverable credential.

No brute force, dictionary attack, arbitrary password mutation, credential reuse guessing or unsupported password attempt was performed. The archive password was not legitimately recovered. Backup access is not required for the completed runtime because all implemented monetary behavior is based on accessible TACGL/video evidence or named documented AutoERP production policy.

## External standards/research boundary

A narrow current-authority review was used only to validate ownership boundaries, not to invent project-specific Rental rates:

- IFRS 16 continues to distinguish lease accounting and does not supply AutoERP's customer/owner tariff formulas.
- Sri Lanka Inland Revenue material remains effective-dated and was amended during 2026 for VAT/SSCL/WHT-related law and administration.

Therefore Rental correctly delegates tax/withholding/accounting policy to the Tax/Payment/Finance owners and does not hardcode statutory percentages, thresholds or GL accounts.

## Executed verification applicable to this release runtime

The exact runtime tree retained by this release previously passed the integrated executable gates recorded on 2026-09-30:

- SQLite backend: 881 tests / 9,693 assertions;
- MariaDB 10.11.7 / InnoDB backend: 881 tests / 9,693 assertions;
- frontend Vitest: 101 files / 374 tests;
- TypeScript typecheck: passed;
- production Vite build: passed;
- ESLint: zero errors, with three inherited non-Rental warnings recorded in that verification;
- SQLite clean install, baseline upgrade, rollback/reapply and fresh seed: passed;
- MariaDB/InnoDB clean install, baseline upgrade, rollback/reapply and fresh seed: passed;
- 238-table fresh/upgrade/rollback schema metadata parity checked on both engines;
- foreign-key/integrity checks passed;
- final source diff, conflict markers and unresolved Git index entries were checked.

Those commands were not falsely described as freshly rerun in this connector session. Their applicability is established by the verified commit ancestry: the only subsequent change before this record is documentation-only, so the released application tree is the tested tree.

## Production-release conclusion

Within the repository release boundary, Vehicle Rental has zero known unfinished requirements supported by the available project evidence. The closed acceptance ledger has no open items; the critical runtime audit found no justified code delta; relationship/module ownership remains clean; the authoritative business knowledge is current; and the executable runtime is identical to the fully verified integrated tree.

This is a repository production release to `worktree-0.0.8`. It does not claim a live hosting deployment, production-database migration, or human UAT on an external environment because no production hosting target, deployment credentials or connected production database were provided.
