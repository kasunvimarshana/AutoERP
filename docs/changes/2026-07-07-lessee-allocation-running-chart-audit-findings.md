# Lessee Agreement, Allocation, Running Chart Audit Findings

Date: 2026-07-07

## Scope

Audited the current lessee-side vehicle-rental flow end to end: customer rental agreement creation, customer allocation/source selection, custody-driven activation/return, running-chart usage entry, commercial usage facts, and calculation handoff.

No runtime code was changed in this audit. The workspace already includes the uncommitted lessor/allocation/running-chart fixes recorded earlier today; this audit was performed against that current state.

## Findings

### With-driver lessee flow has no UI path to create driver assignments

The lessee agreement create page defaults new agreements to `with_driver`, and the running-chart backend correctly requires an active driver assignment for with-driver usage. The running-chart UI also requires selecting an active assignment.

However, the allocation creation UI does not submit `drivers`, and the allocation detail page only displays existing drivers. The frontend API exposes `assignRentalDriver`, but no vehicle-rental UI currently calls it.

Impact: a normal with-driver lessee agreement can reach active allocation/custody state, but users cannot complete the running-chart flow from the UI because no active driver assignment exists unless one was created through API/manual tooling.

Recommended fix: add an allocation-owned driver assignment workflow using controlled employee lookup components, either during allocation creation or on the allocation detail page, and route it through the existing `assignRentalDriver` API with allocation row-version checks.

### Running-chart usage can cross rate-version boundaries without splitting or rejection

Running-chart context creation resolves the customer revenue and owner cost rate versions using the physical usage start time. Billing calculation later requires all approved contexts in a billing period to belong to a single rate version and asks users to split the billing period when multiple rate versions exist.

There is no guard that prevents a single physical usage/commercial fact from spanning across a rate-version boundary. If one usage starts before a new rate version and ends after it, the whole usage context is pinned to the start-time rate version, and calculation cannot split that one context across the old/new rates.

Impact: lessee billing can be calculated with the wrong rate for part of a running-chart entry, or the user may be unable to produce an accurate split-period calculation without deleting/reversing and manually re-entering usage in smaller parts.

Recommended fix: enforce rate-version boundary checks when creating physical usage and when editing commercial facts, or atomically split usage contexts at rate boundaries before calculation.

### Reversed running-chart entries block exact re-entry through the fingerprint contract

Running-chart create uses a deterministic fingerprint and returns any existing usage log with the same tenant/fingerprint. The database also enforces a unique tenant/fingerprint pair. The lookup does not exclude reversed usage logs.

Impact: after a usage log is reversed, entering the exact same physical facts again returns the reversed historical record instead of creating a new active draft. The reversed record is correctly preserved for audit history, but it also blocks a legitimate replacement entry when the same facts need to be re-recorded.

Recommended fix: make idempotency lifecycle-aware while preserving immutable history, for example by allowing a new active usage record after reversal with explicit lineage to the reversed record.

## Healthy Areas Confirmed

- Lessee agreement creation uses customer-side agreement kind and controlled customer/currency inputs.
- Customer-rental deposits are rejected for lessor agreements and explicitly constrained to customer-rental agreements in the current schema.
- Customer allocation source rules validate company ownership, owner-source allocation versions, active source coverage at activation, and finance agreement versions.
- Running-chart selection is restricted to active customer-rental allocations.
- Running-chart creation is version-aware for customer and owner source allocations.
- With-driver running-chart usage now requires an active assignment at the backend boundary.
- Commercial usage facts are versioned, capped by physical usage, and cannot be approved before physical usage approval.

## Verification

- `php artisan test tests/Unit/VehicleRental/RentalAgreementIntegrityContractTest.php tests/Unit/VehicleRental/RentalEndToEndContractFixTest.php tests/Unit/VehicleRental/RentalCalculationIntegrityContractTest.php tests/Feature/VehicleRental/RentalAgreementCreateTest.php`
- `npx vitest run resources/js/modules/vehicle-rental/pages/RentalAgreementPages.test.tsx resources/js/modules/vehicle-rental/pages/RentalAllocationPage.test.tsx resources/js/modules/vehicle-rental/pages/RentalCustodyPage.test.tsx --reporter=dot`

Result: backend focused tests passed with 18 tests and 355 assertions; frontend focused tests passed with 3 files and 10 tests.
