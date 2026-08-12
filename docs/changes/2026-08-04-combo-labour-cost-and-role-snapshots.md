# Combo labour cost and role snapshots

Date: 2026-08-04

## Purpose

Made each labour child in an Item combo own one fixed, combo-specific commission cost and one default Vehicle Service workforce role without introducing another configuration table.

## Configuration

- Added `unit_cost` and nullable `default_workforce_role` to the authoritative `item_bundles` create migration.
- Labour bundle lines require a non-negative cost and a controlled workforce role.
- Non-labour bundle lines cannot carry commission cost or a workforce role.
- The Bundle UI captures and displays the role and commission cost for each labour child.
- Supported roles are Supervisor, Technician, Helper, Inspector, Under Wash, Body Wash, Finishing, and Custom.
- The same labour item may have a different cost in each combo because the value belongs to the exact bundle row.

## Job and assignment flow

- Combo expansion copies the bundle cost into the existing job-line `unit_cost` and copies the role into the new `default_workforce_role` snapshot.
- Combo children remain non-billable with a zero selling price; the combo parent remains the only customer-billable line.
- Labour children no longer require an independent service selling price.
- Selecting a combo labour line prefills its snapshotted role, but the employee assignment role remains editable.
- The backend fixes the assignment commission pool to `job-line quantity × job-line unit cost`; clients cannot override it.
- The existing exact split engine divides the pool among active employees and reallocates it after creation, update, cancellation, or deletion.
- Existing item-level labour commission defaults remain the fallback for standalone labour lines.

## Totals and supervisor behavior

- Added `commission_cost_total` and `net_after_commission` to the authoritative Vehicle Service job create migration.
- Assignment changes now recalculate job commission totals inside the locked transaction.
- A combo child whose default role is Supervisor replaces the global supervisor calculation, preventing double counting.
- Stored Job Card and employee-assignment snapshots are not changed by later combo edits.

## Database baseline

No new configuration table or incremental patch migration was introduced. Because the authoritative create migrations changed, disposable databases must be rebuilt with `php artisan migrate:fresh` after pulling this change.

## Verification

- PHP syntax checks passed for all changed PHP files.
- Item and Vehicle Service engine suites passed: 38 tests, 232 assertions.
- Focused combo snapshot, combo expansion, labour split, and Item bundle tests passed.
- Assignment form Vitest passed: 5 tests.
- Employee assignment and Item one-shot Vitest passed: 3 tests.
- TypeScript type checking and targeted ESLint passed.
