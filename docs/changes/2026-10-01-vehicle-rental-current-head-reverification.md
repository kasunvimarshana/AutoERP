# Vehicle Rental current-head re-verification

## Context

Continue the complete Vehicle Rental audit/release from the latest authoritative `worktree-0.0.8` state without restoring, copying or referencing removed Rental runtime code. TACGL remains the primary business/conflict authority; the four supplied Vehicle Rental videos remain authoritative workflow evidence; `RULES.md` / `AGENTS.md` remain the engineering constraints.

Authoritative head at the start of this pass:

- branch: `worktree-0.0.8`
- commit: `1035f4f4a7ad35a4ef633b155883fc8e31d72b57`

Executed Vehicle Rental runtime baseline retained by that head:

- `9f492b5094039522c53b4e7509be296217b2d5b9`

## Source evidence re-validation

The mounted source corpus was re-hashed before making any decision:

- `TACGL.zip` — `0e0733fff720072af4c3aaa787995ff128bfa79060a37739d6d2ebbe18a25313`
- `TACGL(20261001-005122).zip` — `79c240494943437978754169c3360bb7c6e35d911ef8263c4b2d6b6246384d77`
- `TACGL.rar` — `0001a91ab4dfdf31d60f669b30f1ccc2da475d7841b2ceea505ef97eff5bedbf`
- `1.mp4` — `ac4ca8e632081c32cd2a1d2e6facb070acf4a1f5304a4dc7a468ca7073b953cf`
- `Recording 2026-06-21 132314.mp4` — `11866d255dbb709055b43bb7428538a3e2f0858a8ee1d0144187bcdaf4616ffa`
- `2.mp4` — `cd2ba1399f149003f19080327458e4bbe4619b88eed9416053c7f8d21431c36f`
- `ScreenVideo_03-04-2026_18-02-52.mp4` — `c9853b7923e7cb95f1014cf598416faa550bfbd56f19da56b613f160d0528ce9`

Both TACGL ZIPs were normalized and compared again: each contains exactly 452 non-directory business files, with the same normalized relative paths and identical per-file hashes. The dated package adds provenance, not conflicting business evidence.

Video durations were also rechecked and remain consistent with the canonical audit: approximately 40:50, 41:58, 21:14 and 12:24 respectively.

## Protected backup investigation

The nested archive `DATABACKUP/!   CTACGLDATABACKUP202503271759.rar` was rechecked using non-destructive archive inspection:

- 86 entries are present;
- the archive requires a password;
- all entries require the password;
- no archive comment exposes a credential;
- accessible TACGL executable/configuration/static strings were searched again for an explicit WinRAR command/password or backup credential;
- the bundled WinRAR binary identifies itself as WinRAR 2.90 beta 4, but exposes no backup credential;
- accessible application-user password records were not reused or guessed as archive passwords.

No brute-force, dictionary attack, credential reuse or unsupported password guessing was performed. The protected backup password was **not legitimately recovered**. The backup is not a runtime dependency and inaccessible content is not treated as business evidence.

## Runtime ancestry review

Comparison from executable baseline `9f492b5094039522c53b4e7509be296217b2d5b9` to current head `1035f4f4a7ad35a4ef633b155883fc8e31d72b57` found:

- eight commits of repository history;
- zero changes under `app/Modules/VehicleRental`;
- zero changes under `resources/js/modules/vehicle-rental`;
- later runtime changes belong to other owning modules and shared integration.

The shared `LookupSelect` change used by Rental was inspected separately. It adds an optional caller-supplied label formatter while preserving the existing `code - name` formatter as the default. Existing Rental selectors therefore retain their previous behavior unless they explicitly opt into a custom formatter.

No legacy Rental code was restored, copied, cherry-picked or referenced.

## Deep architecture and relationship re-audit

The current fresh module was re-reviewed across the highest-risk paths:

- agreement creation, activation, closure and successor cutover;
- immutable commercial terms and exact history revisions;
- tenant/org commercial-calendar boundaries and immutable `closed_on` dates;
- external-owner versus company-owned vehicle source coverage;
- Vehicle Use planning, handover, return and atomic replacement;
- shared Vehicle / Vehicle Service availability ownership;
- Running Chart validation, finalization, driver overlap, odometer continuity, reversal and correction;
- Daily/Monthly base rent under named `actual_calendar_days_v1` policy;
- customer/owner independent mileage allowance pools and reverse-order void dependency;
- Normal/Double/Triple OT and night-out components with named minute/hour conversion;
- customer and owner financial handoff through Invoice/Tax/Finance ownership;
- customer security-deposit receipt through Payment ownership;
- same-side duplicate source-consumption guards;
- authorization separation for agreements, custody, chart finalization/reversal and customer/owner billing.

Relationship conclusion remains unchanged and justified:

- successor Agreement → predecessor Agreement;
- Vehicle Use → Customer Agreement;
- Vehicle Use → optional Owner Agreement/source;
- Vehicle Use → physical Vehicle;
- replacement Vehicle Use → predecessor Vehicle Use;
- Running Chart → Vehicle Use;
- corrected Running Chart → reversed predecessor chart;
- Rental source charge → owner-module financial document through source allocation.

Schema review confirms unique one-way replacement/correction/successor pointers, tenant/org-scoped agreement successor identity, tenant-scoped physical relationships, immutable history revision foreign keys and restrictive deletes. No redundant inverse pointer, circular Rental dependency, duplicate financial ledger, or duplicated mutable Invoice/Payment lifecycle state was found. No relationship change is justified.

## Business-policy review

The closed acceptance ledger remains correct: no open product-policy TODO item is present.

The implementation continues to distinguish evidence from production policy:

- Customer and Owner commercial sides remain independent.
- Physical truth may be recorded outside commercial coverage but automatic money may not be created outside valid agreement coverage.
- `actual_calendar_days_v1` remains an explicit production proration policy; no hidden fixed-30 divisor is introduced.
- Included/excess KM pools remain side-specific and cycle-defined rather than inferred across arbitrary periods.
- Replacement alone creates no surcharge/credit/double rent.
- Workshop/off-road status alone creates no automatic downtime deduction.
- AC/OT/night-out evidence does not create money without an explicit matching rate/policy.
- Security-deposit application/forfeiture order is not invented.
- tax, withholding, statutory effective dates and rounding remain Tax/Payment owned.
- TACGL GL account numbers are not copied into AutoERP as magic values.

## External authority boundary

Current official research was used only to validate ownership and integrity boundaries:

- IFRS Foundation material confirms IFRS 16 remains the relevant lease-accounting framework but does not define AutoERP customer/owner operational tariffs or commercial formulas.
- Sri Lanka Inland Revenue Department 2026 material confirms VAT invoice and WHT/AIT rules are effective-dated statutory concerns; Rental therefore must continue to delegate statutory tax/withholding behavior to the Tax/Invoice/Payment owners rather than hardcode percentages or thresholds.
- MySQL 8.4 documentation confirms transactional locking-read semantics and recommends consistent lock ordering to reduce deadlocks; the Rental physical-operation paths continue to use deterministic row locking and transactions.

No external source was used to invent a Rental tariff, tax rate, withholding rate, downtime formula, deposit priority, free-KM rule or replacement charge.

## Verification evidence and current execution boundary

The exact Vehicle Rental runtime retained by this pass was previously exercised as part of the integrated executable baseline with these recorded results:

- SQLite backend: **881 tests / 9,693 assertions** passed;
- MariaDB 10.11.7 / InnoDB backend: **881 tests / 9,693 assertions** passed;
- frontend Vitest: **101 files / 374 tests** passed;
- TypeScript typecheck passed;
- Vite production build passed;
- ESLint completed with zero errors and three inherited non-Rental warnings;
- SQLite and MariaDB clean install, baseline upgrade, rollback/reapply and fresh seed passed;
- 238-table schema metadata parity passed on both engines;
- foreign-key/integrity and final source/conflict checks passed.

This continuation additionally revalidated source hashes, current Git ancestry, current critical services, constants/enums, permissions, model immutability, relationship migrations and the additive shared `LookupSelect` change.

A fresh dependency-backed full suite is **not** represented as rerun in this pass: the available container still cannot resolve `github.com` for a new clone and no complete local repository checkout is mounted. GitHub Actions are intentionally not used under the project's free-tools-only instruction. Because the Vehicle Rental runtime and frontend are unchanged from the executed baseline, no new runtime test result is fabricated.

## Current-head decision

No evidence-backed Vehicle Rental runtime, schema, relationship, security, financial-boundary or frontend defect was found. Creating production-code churn merely to satisfy an instruction to “change something” would violate the project rules.

The justified change in this pass is documentation accuracy:

- `docs/knowledgebase.md` now labels `9f492b...` as the executed Vehicle Rental runtime baseline rather than a permanently embedded live branch head;
- the live branch head is resolved from Git/release records to avoid self-referential docs-only SHA churn;
- the current-head ancestry, relationship and verification boundary are recorded explicitly.

Result: **zero known unfinished evidence-backed Vehicle Rental requirements within the repository/project boundary** at this re-verification point.

Repository production release is distinct from live-host deployment. This record does not claim production-server deployment, live database migration, infrastructure validation or human UAT that was not actually performed.
