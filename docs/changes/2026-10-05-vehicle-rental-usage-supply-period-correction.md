# Vehicle Rental usage-charge supply-period correction — 2026-10-05

## Scope

Continue the end-to-end Vehicle Rental production audit from the latest authoritative `worktree-0.0.8` state without restoring, copying, cherry-picking or depending on removed/legacy Rental runtime code.

Audit start:

- authoritative branch: `worktree-0.0.8`
- start SHA: `a8346d1244a6cb2fa0b8b6b82fcc40980dfc6970`
- open pull requests: none
- `docs/vehicle-rental/TODO.md`: closed acceptance ledger with no unchecked product-policy item.

TACGL remains the business/conflict authority and the four supplied Vehicle Rental videos remain authoritative practical workflow evidence. Root `RULES.md` / `AGENTS.md` remain the engineering constraints.

## Source and protected-backup recheck

The accessible TACGL corpus and previously registered source hashes remain the current evidence set. The dated TACGL package adds no conflicting business evidence.

The nested protected backup `DATABACKUP/!   CTACGLDATABACKUP202503271759.rar` was legitimately re-investigated through non-destructive accessible-source/configuration/string review. No explicit archive password or documented backup credential was found. Generic application/password strings are not evidence of the archive password and were not reused as guesses.

No brute force, dictionary attack, credential reuse or arbitrary password mutation was performed. The encrypted backup remains non-blocking and inaccessible content is not treated as business evidence.

## Deep implementation / relationship audit

The fresh module remains responsibility-owned:

- Customer Agreement and Owner Agreement are separate aggregates;
- successor lineage is one-way;
- Vehicle Use is the operational junction for Customer Agreement, optional Owner Agreement and physical Vehicle;
- replacement lineage is one-way;
- Running Chart belongs to Vehicle Use and correction lineage is one-way;
- Rental source calculations remain separate from Invoice/Payment/Tax/Finance lifecycle state;
- no redundant inverse pointer, duplicate Rental financial ledger or circular relationship was justified.

Current runtime/static review also found no hardcoded Rental statutory percentage or GL account, no process-environment shortcut, no raw SQL workaround, no destructive history deletion path, and no browser confirmation shortcut in Rental.

## Defect found

The final audit found one source-period integrity defect in non-mileage Running Chart usage charges (Normal/Double/Triple OT and night-out).

The Running Chart interval is modeled as an exact operational interval with an **exclusive end instant**. Commercial coverage already respects that rule by subtracting the final instant when deriving inclusive civil dates.

However, `UsageChargeBilling::create` previously stored:

- `period_from` from the chart start date;
- `period_until` directly from the chart end date.

For a chart covering:

`2026-09-07T00:00:00Z <= usage < 2026-09-09T00:00:00Z`

the last covered civil day is September 8, but the charge stored September 9 as `period_until`.

The Invoice handoff uses the charge period when no frozen `supply_from` / `supply_until` exists, so the generated document could expose a supply-period end one civil day too late.

A second integrity consequence existed on reissue: old OT/night-out calculation snapshots stored chart timestamps but not the resolved business-calendar supply dates/timezone. A later `localization.timezone` change could cause commercial coverage to reinterpret those timestamps under the new workspace timezone.

This defect is internal to the fresh Rental source-period handoff. It is not evidence for a new tariff or business formula.

## Root-cause correction

### RentalCalendar owns the conversion

`RentalCalendar` now provides one `coveredCivilPeriod` boundary:

- resolve Configuration-owned tenant/org `localization.timezone`;
- convert the exact start instant into its business civil date;
- treat the end as exclusive by subtracting the final microsecond before deriving the inclusive last covered civil date;
- return the timezone together with the inclusive `from` / `until` dates.

This keeps commercial civil-date interpretation in the existing Rental calendar owner instead of duplicating date math in billing services.

### Usage charge freezes immutable supply context

At first OT/night-out charge creation, `UsageChargeBilling` now stores:

- `timezone`;
- `supply_from`;
- `supply_until`;

inside the immutable calculation snapshot.

The charge `period_from` / `period_until` use the same resolved inclusive dates.

Invoice creation and reissue therefore consume the immutable source period already preserved by the charge. A later workspace-timezone change cannot reinterpret that charge.

Mileage is intentionally unchanged. `commercial_calendar_cycles_v1` already freezes its commercial timezone and `supply_from` / `supply_until` in the mileage calculation.

### Shared fallback

`RentalChargeDocuments` reuses the same `RentalCalendar::coveredCivilPeriod` helper when it must derive a chart period from a calculation that does not contain frozen supply dates. No second date-conversion rule remains in the financial handoff.

## Regression coverage

`UsageChargeBillingTest` was extended to assert:

- the existing chart ending exactly at `2026-09-09T00:00:00Z` produces an Invoice supply-period end of `2026-09-08`;
- an Asia/Colombo chart whose UTC instants map exactly to local midnights freezes `2026-09-08` as both source dates;
- the charge snapshot records `Asia/Colombo`, `supply_from` and `supply_until`;
- after the workspace timezone is changed to UTC, reissue preserves the original `2026-09-08` supply period.

The test uses existing synthetic Rental rates and does not introduce production financial values.

## Verification boundary

Only executed verification may be called passed.

The latest dependency-backed green baseline remains:

- Laravel: **881 tests / 9,697 assertions passed**;
- TypeScript typecheck: passed;
- ESLint: passed cleanly;
- Vite production build: passed;
- Vitest: **101 / 101 files, 374 / 374 tests passed**;
- earlier integrated SQLite and MariaDB/InnoDB migration/schema/FK verification passed.

A normal fresh checkout still cannot be obtained in the current execution container because `github.com` DNS resolution fails.

The repository's existing free GitHub Actions workflow automatically attempted the current authoritative head, but Backend SQLite, Backend MySQL and Frontend all terminated in approximately two to four seconds with **zero workflow steps executed**. The same no-step failure pattern exists on previously dependency-backed green Vehicle Rental commits. This is CI execution/infrastructure evidence, not a failing application test assertion. No workflow was manually rerun or newly triggered for this task.

Therefore the focused regression added by this correction is reviewed source coverage and is **not falsely reported as executed** in this environment.

This correction changes no migration/schema, no relationship, no API shape, no tax/withholding logic, no posting account, no deposit policy and no commercial rate/formula.

## External-authority boundary

Current external authority was used only to recheck architecture boundaries:

- IFRS contract-modification guidance remains consistent with prospective immutable revision/successor handling rather than rewriting historical economics;
- Sri Lanka IRD material continues to demonstrate effective-dated tax/withholding rules, supporting Tax/Invoice/Payment ownership instead of Rental constants;
- MySQL InnoDB guidance continues to support transactional locking reads and consistent lock order for concurrency-sensitive commands.

No external publication was used to invent a Rental amount, threshold, replacement surcharge, downtime deduction, deposit-forfeiture priority, free-KM rule or statutory applicability decision.

## Decision

The newly discovered source-period defect is fixed in the module that owns it, with no compatibility workaround and no duplicated date policy.

After this correction, no additional evidence-backed Vehicle Rental runtime/schema/relationship/business-policy gap was identified in the final source review.
