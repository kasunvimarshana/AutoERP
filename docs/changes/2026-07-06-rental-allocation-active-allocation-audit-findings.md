# Rental Allocation Active Allocation Audit Findings

Date: 2026-07-06 07:29:52 +05:30

## Scope

Audited the rental allocation activation lifecycle, the active vehicle allocation running-chart selector, custody confirmation, and replacement-related custody paths.

## Findings

1. Active allocations are not gated by active agreements.
   - `RentalAllocationService::create()` allows allocations for draft or active agreements.
   - `RentalAllocationService::activate()` activates a planned allocation without checking that the agreement is currently active.
   - `RentalUsageService::assertAllocation()` only checks customer-rental kind and allocation status, so running charts can be recorded when the allocation is active even if the agreement remains draft or later becomes suspended.
   - Impact: a vehicle can become rented and billable usage can be recorded before the rental contract is active or while it is suspended.

2. Custody confirmation can confirm activation events after the allocation changed.
   - Custody event creation is version-checked against the allocation, but confirmation only checks the custody event row version.
   - `activateOwnerAllocation()` and `activateCustomerAllocation()` only call allocation activation when the current allocation status is planned; if the allocation was cancelled or otherwise changed after event creation, the custody event can still be confirmed and may write `start_odometer` onto a non-active lifecycle state.
   - Impact: stale draft custody events can pollute a cancelled/changed allocation timeline.

3. Replacement custody events are still reachable through the public custody endpoint.
   - `StoreRentalCustodyEventRequest` accepts every `RentalCustodyEventType` and a public `replacement_id`.
   - `RentalCustodyService::assertEventAllowed()` permits `replacement_in` and `replacement_out` for customer agreements and only checks whether a replacement id is present, not whether it belongs to the allocation/replacement workflow being executed.
   - Impact: callers can bypass `RentalReplacementService::replace()` and create replacement custody events directly, undermining the single atomic replacement workflow.

4. Custody page keeps stale allocation versions after confirmation.
   - The page reloads custody events after creating or confirming events, but it does not reload the selected allocation details.
   - The next custody event submission still uses the old `allocationDetails.row_version`.
   - Impact: normal handover-then-return workflows can fail with a stale allocation version until the user reloads or reselects the allocation.

5. The running-chart "Active vehicle allocation" selector is capped and not searchable.
   - The page loads `status: "active"`, customer-rental allocations with `per_page: 100` into a fixed select.
   - If the target active allocation is outside that first page, the URL may contain an allocation id but the selected allocation object is missing and save remains disabled.
   - Impact: active allocations can be valid but unreachable from the running-chart form at scale.

## Verification

- Read recent `/docs/changes` records before auditing.
- Reviewed rental allocation service, custody service/controller/request, usage service/request, running-chart page, allocation detail page, routes, replacement service, and existing contract tests.
- Confirmed `storage/logs/laravel.log` had no recent tail output during this audit.
- `php artisan test tests/Unit/VehicleRental/RentalEndToEndContractFixTest.php tests/Unit/VehicleRental/VehicleRentalModuleBaselineTest.php`
- `npx vitest run resources/js/modules/vehicle-rental/pages/RentalCustodyPage.test.tsx --reporter=dot`

## Notes

No runtime code was changed in this audit pass.
