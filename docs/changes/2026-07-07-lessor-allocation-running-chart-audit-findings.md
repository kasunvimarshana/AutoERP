# Lessor Agreement, Allocation, Running Chart Audit Findings

Date: 2026-07-07

## Scope

Audited the vehicle-rental lessor agreement, allocation, custody handover, running-chart, usage context, and calculation flow end to end after the recent fixes recorded in `/docs/changes`.

No runtime code was changed in this audit. This record captures remaining issues and confirmed guardrails for the next implementation pass.

## Findings

### Driver assignment status is not enforced when creating running-chart usage

`RentalUsageService::resolveDriverAssignment()` validates that the submitted driver assignment belongs to the selected allocation and covers the full usage period, but it does not require the assignment to be active. The running-chart UI filters active assignments before submission, but the backend is the authoritative data-integrity boundary and can still accept a planned, completed, or cancelled assignment if its dates cover the usage period.

This can attach operational mileage and billing context to a driver assignment that is not actually active.

Recommended fix: require `RentalDriverAssignmentStatus::Active` in the backend usage creation path and add contract tests for planned, completed, and cancelled assignment rejection.

### Running-chart event capture exceeds supported rate configuration

Running-chart usage events support operational and billable event types such as parking, toll, waiting, outstation, pass, fuel, damage, repair, and other. Calculation maps several of these to rate component codes, but `pass` has no corresponding `RentalRateComponentCode` or calculation mapping. The agreement create UI also only exposes the base rental, excess km, driver salary, overtime, and night-out components, so normal users cannot configure rates for many event charges that the running chart lets them enter.

This means event data can be recorded successfully but later produce no customer invoice or owner payable line unless matching rate components were created outside the visible agreement workflow. Pass events are currently unpriced even through the backend component enum.

Recommended fix: define the supported commercial event/rate component set as one source of truth, either by adding missing billable components and guided rate UI or by removing/unexposing unsupported event types from billable flows.

### Deposit customer-rental invariant is indirect at the database layer

Recent records describe a deposit schema invariant based on `agreement_kind`, but the current deposit table uses a composite `agreement_id`, `tenant_id`, `customer_id` foreign key instead. This blocks normal lessor agreements because owner-supply agreements have no customer, and the service/request layer also rejects lessor deposit payloads. However, the database does not explicitly enforce that deposit requirements can only target `customer_rental` agreements if inconsistent agreement rows are introduced later.

Recommended fix: either document the current customer-side composite invariant precisely or add an explicit database-level agreement-kind invariant in a future schema pass.

## Confirmed Guardrails

- Lessor and lessee agreement detail routes now hide wrong-mode actions instead of exposing mutable panels.
- Lessor agreement creation rejects security-deposit payloads at request/service level.
- Draft structural agreement edits are blocked once dependent allocations, rate versions, or deposits exist.
- Allocation activation validates active customer agreements, active owner source agreements, source allocation availability, and expected versions.
- Running-chart usage creation validates active customer allocation, active source allocation, expected allocation versions, and owner/customer applicability.
- Usage facts cap commercial distance to approved physical distance and require physical approval before commercial approval.

## Verification

Ran:

```bash
php artisan test tests/Unit/VehicleRental/RentalAgreementIntegrityContractTest.php tests/Unit/VehicleRental/RentalCalculationIntegrityContractTest.php tests/Unit/VehicleRental/RentalEndToEndContractFixTest.php tests/Feature/VehicleRental/RentalAgreementCreateTest.php
```

Result: 18 tests passed, 343 assertions.
