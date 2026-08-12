# Legacy vehicle number registration sync

Date: 2026-08-10

## Purpose

Created a guarded SQL correction that replaces the generated vehicle number on imported legacy vehicles with the vehicle's registration value.

## Behavior

- Targets tenant 1 records whose code matches `LEGACY-VEH-%`.
- Requires all 1,888 expected legacy vehicles to exist.
- Rejects missing registrations, duplicate legacy registrations, and registration values that collide with another vehicle's vehicle number.
- Sets `vehicle_number = registration_number` only when the two values differ.
- Increments `row_version` exactly once for changed rows and updates `updated_at`.
- Runs in one transaction and is safe to rerun without incrementing versions again.

## Output

- `database/imports/2026-08-10-legacy-vehicle-number-from-registration.sql`

## Verification

- Current database inspection confirmed 1,888 legacy vehicles, zero missing registrations, and zero target vehicle-number conflicts.
- Executed the SQL twice inside a rollback-only transaction.
- The first pass synchronized all 1,888 rows and increased the row-version total from 1,888 to 3,776.
- The second pass left the row-version total unchanged at 3,776, confirming rerun safety.
- Rollback restored zero matching vehicle numbers and the original row-version total of 1,888; no correction was permanently applied during verification.
