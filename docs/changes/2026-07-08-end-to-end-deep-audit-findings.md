# End-to-End Deep Audit Findings

Date: 2026-07-08

## Context

Audited the current Laravel log, recent change records, Vehicle Rental deposit schema flow, and Vehicle Service job request/form contract after a local migration failure was reported.

No runtime code was changed in this pass. This record captures the confirmed findings and verification evidence for the follow-up implementation pass.

## Findings

### Rental deposit requirement migration blocks MySQL migrations

`storage/logs/laravel.log` shows MySQL rejecting `2026_06_12_200022_create_rental_deposit_requirements_table.php` with errno 150 when adding `rental_deposit_req_agreement_kind_customer_fk`.

The referenced parent column `rental_agreements.agreement_kind` is defined as `string(30)`, while the child column `rental_deposit_requirements.agreement_kind` is defined as a MySQL enum. MySQL foreign key columns must have compatible definitions, so the composite foreign key cannot be created.

Local `php artisan migrate:status` confirms the database is stopped at this migration: `2026_06_12_200022_create_rental_deposit_requirements_table` and all later migrations remain pending.

Recommended fix: keep the parent/child schema definitions compatible. The simplest clean fix is to define `rental_deposit_requirements.agreement_kind` with the same string length as `rental_agreements.agreement_kind`, and keep the customer-rental invariant through the existing service/request/database relationship checks rather than using an incompatible enum column in the FK.

### MySQL schema compatibility is not covered by the current automated test gate

The focused Vehicle Rental feature and unit tests pass under the configured PHPUnit database connection, but `phpunit.xml` sets `DB_CONNECTION=sqlite`. SQLite does not catch the MySQL-specific FK definition mismatch shown in the live log.

Recommended fix: add a lightweight MySQL migration smoke gate for schema changes that rely on composite foreign keys, or at minimum add a static migration contract asserting that FK participant column definitions stay compatible.

### Vehicle Service job request can throw on nullable commission type

`StoreVehicleServiceJobRequest` allows `supervisor_commission_type` to be nullable, but `toData()` builds the enum with `VehicleServiceCommissionType::from((string) $this->input('supervisor_commission_type', 'none'))`.

If an API client submits `null`, validation can pass through the nullable rule, then `toData()` casts `null` to an empty string and raises a `ValueError` before reaching service-layer validation. The current React form sends a concrete value, so this is a backend API contract edge case rather than the active UI regression.

Recommended fix: normalize nullable commission inputs in the request DTO conversion so missing or null values resolve to `VehicleServiceCommissionType::None` and the default commission value, while invalid non-null values still fail validation.

## Verified Non-Findings

- The old Vehicle Service `billToCustomer is not defined` frontend regression is covered by `VehicleServiceJobForm.test.tsx`, and the current form sends `bill_to_customer_id`.
- Other scanned Vehicle Rental enum migration columns do not participate in composite foreign keys with mismatched parent column types.
- Vehicle Rental and Vehicle Service routes are registered for the audited surfaces.

## Verification

- Read the latest `/docs/changes` records before reviewing code.
- Reviewed `storage/logs/laravel.log`.
- Reviewed Vehicle Rental agreement/deposit migrations, deposit model/resource/service, agreement integrity tests, and deposit creation tests.
- Reviewed Vehicle Service job request, DTO, controller, service, resource, frontend form, API type contract, and regression test.
- `php artisan migrate:status`
- `php artisan test tests/Feature/VehicleRental/RentalAgreementCreateTest.php tests/Unit/VehicleRental/RentalAgreementIntegrityContractTest.php`
- `npx vitest run resources/js/modules/vehicle-service/components/VehicleServiceJobForm.test.tsx --reporter=dot`
- `npm run typecheck -- --pretty false`
- `php artisan route:list --path=vehicle-service/jobs`
- `php artisan route:list --path=vehicle-rental`

Result: focused backend tests passed with 11 tests and 73 assertions; the focused frontend regression test passed; TypeScript passed. The local MySQL migration state remains blocked at the rental deposit requirement migration until the schema mismatch is fixed.
