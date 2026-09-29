# Vehicle Rental migration-order and commercial-calendar integrity

Date: 2026-09-26

## Successor migration-order defect

A continuation fresh-schema audit of the live `worktree-0.0.8` branch found that Vehicle Rental loads both `Database/Migrations` and `Database/UpgradeMigrations` on every install. The earlier upgrade migration `2026_09_25_000002_link_agreement_successors.php` already creates `supersedes_agreement_id` on both customer and owner agreement tables. The later migration `2026_09_25_170000_add_successor_lineage_to_vehicle_rental_agreements.php` attempted to create the same columns again.

Because both migration paths are registered by `VehicleRentalServiceProvider`, a fresh migration sequence would reach the later migration with the successor columns already present. That is a schema-ownership defect, not a business-rule ambiguity.

### Root-cause fix

The earlier upgrade migration remains the single owner of successor-column creation and its original tenant-scoped constraints.

The later migration now performs only the intended integrity hardening:

1. remove the earlier tenant-only successor FK and uniqueness constraint;
2. add the scoped `(id, tenant_id, organization_unit_id)` identity key required by the stronger self-FK;
3. enforce one direct successor per predecessor;
4. recreate the successor self-FK across predecessor ID, tenant and organization unit;
5. on rollback, restore the earlier migration's exact tenant-scoped constraints and leave column removal to that earlier migration's own `down()` method.

No `Schema::hasColumn()` compatibility guard or duplicate column alias was introduced. Migration ownership is explicit and ordered.

## Vehicle-use commercial-calendar defect

Agreement closure, successor activation and automatic financial coverage already use Configuration-owned tenant/org `localization.timezone`. Vehicle-use planning and handover still compared agreement civil dates to the civil date embedded in the client timestamp offset.

Those are not equivalent. A timestamp can be September 8 in its submitted offset while still being September 7 in the tenant's commercial timezone, or the reverse. The old comparison could therefore reject a valid covered assignment or admit physical use whose business-calendar date was already outside the agreement.

### Root-cause fix

`VehicleUseService` now uses `RentalCalendar` for agreement coverage:

- planned half-open intervals convert their start instant and last covered instant (`end - 1 microsecond`) to the tenant/org commercial timezone before comparing agreement civil dates;
- handover checks the actual handover instant in that same commercial calendar;
- customer and owner agreement coverage use the identical rule;
- the original input timestamps and offsets remain preserved as operational evidence.

This aligns physical assignment with successor activation, lifecycle closure, base-rent, mileage and Invoice/AP commercial-coverage checks without changing timestamp storage or inventing a new timezone setting.

## Owner-source lookup alignment

The owner-source selector used the same old client-offset civil-date comparison. That could make the operator lookup disagree with `VehicleUseService`: a source might be hidden even though the tenant calendar considered it covered, or displayed even though the actual assignment would be outside the agreement date.

`OwnerSourceService` now resolves the planned half-open period through the same `RentalCalendar` before filtering active Owner Agreements. The selector and the command path therefore share one source of truth for agreement-day coverage.

## Related cleanup

`VehicleUse` odometer casts now reference `AgreementFields::DECIMAL_SCALE`, matching the schema and the rest of Vehicle Rental instead of repeating the raw scale value `6`.

## Relationship review

The business relationship is unchanged and remains intentionally one-way:

`successor agreement -> predecessor agreement`

No inverse pointer, circular relationship, extra ledger or cross-module dependency is added. The database hardening only ensures the existing lineage cannot cross tenant/organization boundaries and cannot fork into two direct successors. The calendar corrections change no relationships at all.

## Regression/verification evidence

- Current module migration registration was inspected and confirms both migration directories are loaded.
- The full current Vehicle Rental migration tree was inspected to verify that the duplicate successor column ownership is isolated to these two migrations.
- `VehicleUseTenantCalendarTest` covers both offset directions against an `Asia/Colombo` workspace and includes plan, handover and return on the covered path.
- `OwnerSourceTenantCalendarTest` proves the owner-source selector returns the same eligibility as the tenant commercial calendar in both offset directions.
- Existing successor and Vehicle Use tests use `RefreshDatabase`; when dependency-backed PHPUnit executes, the fresh migration chain runs before those feature assertions.
- Current Running Chart schema/model/service naming was re-read from `worktree-0.0.8`; the live schema already uses canonical `corrects_chart_id`, so no stale rename/compatibility migration was introduced.
- Exact changed PHP files were materialized and checked with the available PHP runtime; the successor hardening migration, `VehicleUse`, `VehicleUseService`, `OwnerSourceService`, `VehicleUseTenantCalendarTest` and `OwnerSourceTenantCalendarTest` all report `No syntax errors detected`.
- A final direct GitHub checkout retry still failed with `Could not resolve host: github.com`; the container therefore has no repository/vendor/node dependency checkout from which to execute Composer, PHPUnit, MySQL/MariaDB, npm typecheck/lint/build or `migrate:fresh`.
- Those dependency-backed commands are not falsely reported as passed.
- GitHub Actions are not used as verification evidence.
