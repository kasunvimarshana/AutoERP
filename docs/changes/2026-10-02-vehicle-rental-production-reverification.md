# Vehicle Rental production re-verification — 2026-10-02

## Scope

End-to-end continuation audit for the fresh Vehicle Rental implementation using the latest `worktree-0.0.8` as engineering authority, TACGL as the primary business/conflict authority, all four supplied Vehicle Rental videos as authoritative practical workflow evidence, and root `RULES.md` / `AGENTS.md` as engineering constraints.

No removed/legacy Rental runtime was restored, copied, cherry-picked or used as an implementation dependency.

Authoritative branch at audit start:

- `worktree-0.0.8`: `7601749d35696556b3585a9737cd6bd4ec299c30`
- tree: `f7a5d7321d2be254fe1df9845a654f583369ccd6`

Production branch at audit start:

- `worktree`: `dcb1b2578966caab1f10a83afa3da0125f6ee43f`
- tree: `f7a5d7321d2be254fe1df9845a654f583369ccd6`

The two branches were file-tree identical at audit start; production differed only by merge history. There were no open pull requests and no open Vehicle Rental issue.

## Source evidence re-validation

Fresh local source checks produced the same registered evidence hashes:

- `TACGL.zip` — `0e0733fff720072af4c3aaa787995ff128bfa79060a37739d6d2ebbe18a25313`
- `TACGL(20261001-005122).zip` — `79c240494943437978754169c3360bb7c6e35d911ef8263c4b2d6b6246384d77`
- `TACGL.rar` — `0001a91ab4dfdf31d60f669b30f1ccc2da475d7841b2ceea505ef97eff5bedbf`
- `1.mp4` — `ac4ca8e632081c32cd2a1d2e6facb070acf4a1f5304a4dc7a468ca7073b953cf`
- `Recording 2026-06-21 132314.mp4` — `11866d255dbb709055b43bb7428538a3e2f0858a8ee1d0144187bcdaf4616ffa`
- `2.mp4` — `cd2ba1399f149003f19080327458e4bbe4619b88eed9416053c7f8d21431c36f`
- `ScreenVideo_03-04-2026_18-02-52.mp4` — `c9853b7923e7cb95f1014cf598416faa550bfbd56f19da56b613f160d0528ce9`

Both TACGL ZIPs were normalized again. Each contains exactly 452 non-directory business files, with identical normalized relative paths and identical per-file SHA-256 values.

Video durations were re-read as approximately:

- 40:50
- 41:58
- 21:14
- 12:24

No source drift or conflicting new business evidence was found.

## Protected backup investigation

The nested archive `DATABACKUP/!   CTACGLDATABACKUP202503271759.rar` was inspected again non-destructively with free local tooling.

Results:

- 86 entries;
- archive requires a password;
- all 86 entries require the password;
- no archive comment;
- accessible TACGL configuration/static content was searched for explicit WinRAR/archive command credentials;
- accessible application-user credential data was intentionally not reused as archive-password guesses;
- backup history evidence still shows BACKUPM/BACKUPD activity around the dated backup, but no backup credential;
- no explicit archive password was found.

No brute force, dictionary attack, credential reuse or arbitrary password mutation was performed. The protected backup password was not legitimately recovered. The archive remains non-blocking because inaccessible content is not treated as evidence and the completed runtime depends only on accessible evidence and explicit named production policies.

## Runtime ancestry and scope review

The current authoritative branch was compared with the executed Vehicle Rental runtime baseline `9f492b5094039522c53b4e7509be296217b2d5b9`.

Result:

- current branch is 33 commits ahead;
- zero changed files under `app/Modules/VehicleRental/`;
- zero changed files under `resources/js/modules/vehicle-rental/`;
- zero migration files changed after the executed Rental baseline;
- later changes belong to other owning modules, shared UI/reporting, tests and documentation.

Therefore the current Vehicle Rental runtime, frontend and schema lineage remain the same code that received the integrated Rental verification.

## Deep architecture and relationship review

The following high-risk relationships were re-read from current migrations/models and remain justified:

- successor Agreement -> predecessor Agreement;
- Vehicle Use -> Customer Agreement;
- Vehicle Use -> optional Owner Agreement/source;
- Vehicle Use -> physical Vehicle;
- replacement Vehicle Use -> predecessor Vehicle Use;
- Running Chart -> Vehicle Use;
- corrected Running Chart -> predecessor chart;
- Rental source charge -> owner-module financial document through source allocation.

Current schema controls remain directional and scope-safe:

- Vehicle Use keeps a unique one-way `replaces_use_id` and restrictive self-FK;
- Running Chart keeps a unique one-way `corrects_chart_id` and restrictive self-FK;
- agreement successor lineage is unique and tenant-scoped;
- Vehicle Use freezes customer/owner agreement revision foreign keys;
- Running Chart freezes the Vehicle Use history revision;
- finalized/history-bearing records prohibit destructive deletion;
- financial document lifecycle is not duplicated into Rental mutable state.

No redundant inverse pointer, circular Rental dependency, duplicate ledger or relationship change was justified.

## Business-policy and magic-value review

High-risk billing/configuration services were re-read.

Findings:

- base-rent policy remains the named enum `actual_calendar_days_v1`;
- mileage policy remains the named enum `commercial_calendar_cycles_v1`;
- usage policy remains the named enum `recorded_minutes_and_nights_v1`;
- OT minute conversion uses the named `MINUTES_PER_HOUR` constant;
- customer and owner source types use enums;
- tax, invoice direction/type/status and finance account/profile identities come from their owning modules;
- monetary precision, scale, zero, version and field limits use named constants;
- no hidden fixed-30 monthly divisor, hardcoded statutory percentage, copied TACGL GL account, automatic replacement surcharge, downtime formula or deposit-forfeiture priority was introduced.

The closed `docs/vehicle-rental/TODO.md` ledger remains accurate and contains no open product-policy TODO item.

## External authority re-check

Current authoritative research was used only to validate boundaries, not to invent business values:

- the IASB concluded in July 2026 that IFRS 16 is overall working as intended; IFRS 16 remains accounting guidance and does not define AutoERP operational Rental tariffs;
- Sri Lanka Inland Revenue Department 2026 circulars include revised VAT tax-invoice guidance and WHT/AIT guidance, confirming that statutory tax behavior is effective-dated and belongs to Tax/Invoice/Payment ownership;
- MySQL 8.4 documents `SELECT ... FOR UPDATE` / `FOR SHARE` locking reads and recommends consistent transaction lock ordering to reduce deadlocks.

No external source justified a new Rental rate, tax percentage, withholding threshold, free-KM rule, replacement charge, downtime deduction or deposit priority.

## Fresh verification evidence

A fresh dependency-backed local run supplied after the latest frontend cleanup records the current application verification gate as green:

- `php artisan test`: **881 passed / 9,697 assertions**;
- `npm run typecheck -- --pretty false`: passed;
- `npm run lint`: passed with no errors or warnings;
- `npm run build`: passed, 693 modules transformed;
- `npm run test`: **101 / 101 test files passed**, **374 / 374 tests passed**.

The backend run includes the Vehicle Rental agreement, authenticated journey, base-rent, mileage, OT/night-out, deposit, Running Chart, driver, odometer, successor/cutover, commercial coverage, Vehicle Use and replacement test families.

Earlier integrated verification on the same unchanged Vehicle Rental runtime/schema additionally passed SQLite and MariaDB/InnoDB execution, clean install, baseline upgrade, rollback/reapply, fresh seed, 238-table schema metadata parity and FK/integrity checks. Because no migration file and no Vehicle Rental runtime/frontend file changed after that baseline, no new migration behavior exists to re-qualify in this continuation.

## Decision

No evidence-backed Vehicle Rental runtime, frontend, schema, relationship, security, financial-boundary or business-policy defect was found.

Creating a production-code change merely to manufacture release activity would violate the project rules. The only justified changes in this continuation are documentation accuracy:

- record the fresh current-state audit and full green verification;
- update `docs/knowledgebase.md` to point to this latest re-verification and current test evidence.

Result: **zero known unfinished evidence-backed Vehicle Rental requirements within the repository/project boundary**.

This is a repository production release decision. It does not claim a live hosting deployment, live production database migration, infrastructure smoke test or human production UAT that was not actually executed.
