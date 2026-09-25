# Vehicle Rental Running Chart driver schema correction

Date: 2026-09-25

## Problem

The fresh Vehicle Rental Running Chart domain already implemented structured driver identity in validation, services, model casts, API resources, frontend types and focused tests. The authoritative migration that creates `vehicle_rental_running_charts`, however, still contained only the older narrative `driver_observation` field and did not persist the structured identity columns.

A fresh database therefore could not execute the implemented Employee/External driver workflow reliably even though the higher layers described and tested it.

## Root-cause fix

An additive Vehicle Rental migration now persists exactly the existing driver identity contract:

- nullable `driver_identity_source`;
- nullable `driver_employee_id`;
- nullable immutable `driver_name_snapshot`;
- nullable immutable `driver_reference_snapshot`.

The snapshot lengths reuse `RunningChartFields::DRIVER_REFERENCE_LENGTH`; no new raw length value is introduced.

Employee identity uses a one-way composite foreign key `(driver_employee_id, tenant_id) -> hr_employees(id, tenant_id)`. This prevents cross-tenant references while preserving HR's existing visibility model, where an employee can belong to the selected organization or be tenant-global with `organization_unit_id = null`. Rental does not add an HR back-reference or duplicate employee lifecycle/payroll data.

Two indexes mirror the existing driver-overlap queries:

- tenant + identity source + employee ID + status + start time;
- tenant + identity source + external reference snapshot + status + start time.

No new business rule, approval stage, driver rate, payroll behavior or inferred identity was introduced.

## Maintainability cleanup

`VehicleUse` odometer casts now reference `AgreementFields::DECIMAL_SCALE` instead of embedding the raw scale `6`. This keeps the existing Vehicle Rental decimal scale as the single source of truth without changing stored precision or behavior.

## Relationship review

The new database relationship is not a new domain dependency; it physically enforces the already-implemented one-way Running Chart -> HR employee reference.

The FK intentionally does not include `organization_unit_id`: HR explicitly supports tenant-global employees in addition to organization-owned employees, and `EmployeeQueryService` remains the owner of organization visibility. Adding organization equality at the FK layer would incorrectly reject valid tenant-global employees.

No circular or bidirectional relationship is introduced.

## Regression coverage

`RunningChartDriverSchemaTest` verifies that a fresh schema exposes all four structured driver fields and that the database rejects a direct cross-tenant HR employee reference even if application validation is bypassed.

Existing `DriverIdentityTest` continues to cover Employee snapshot capture, External reference normalization, application-level foreign-tenant rejection, finalized identity immutability, same-driver overlap rejection and adjacent non-overlapping usage.

## Verification evidence

- Exact migration blob was materialized and `php -l` passed under PHP 8.4.23.
- Exact `RunningChartDriverSchemaTest.php` blob was materialized and `php -l` passed.
- Exact `VehicleUse.php` blob was materialized and `php -l` passed.
- Local `git hash-object` values match the corresponding GitHub blob SHAs for all three PHP files.
- GitHub Actions are not used as verification evidence.
- Full dependency-backed migration/PHPUnit/MySQL/frontend execution is reported only if actually available; it is not inferred from static review.
