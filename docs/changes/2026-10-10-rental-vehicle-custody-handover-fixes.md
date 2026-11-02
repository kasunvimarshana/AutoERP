# Rental Vehicle Custody Handover Fixes

## Why

Vehicle handover in vehicle rental was failing and exhibiting broken behavior:
1. Recording, confirming, or showing custody events failed with a 500 `RelationNotFoundException: Call to undefined relationship [attachments] on model [Modules\VehicleRental\Models\RentalCustodyEvent]` because `RentalCustodyService::relations` referenced a non-existent `attachments` relation. A similar bug was also present in `RentalExpenseService`.
2. Created custody events did not explicitly initialize `row_version: 1` or refresh before serialization, resulting in `row_version: 0` in API responses. This caused optimistic concurrency checks in `ConfirmRentalCustodyEventRequest` (`expected_version: min:1`) to fail with 422 Unprocessable Entity when confirming drafts.
3. In `RentalReplacementService`, calls to `$this->custody->confirm` omitted the required `expectedVersion` parameter, passing `$userId` directly and causing type errors or concurrency validation exceptions.
4. On the frontend (`RentalCustodyPage.tsx`), the event dropdown showed all possible events regardless of allocation lifecycle state (e.g., offering handover `company_to_customer` even when the vehicle was already active and handed over, which then threw backend validation errors).
5. The custody form defaulted `odometer` to `'0'` instead of pre-filling from the allocation's `start_odometer` (or last confirmed event), causing zero or invalid odometer readings to be recorded.
6. The custody form defaulted `occurred_at` to browser local time without clamping to `allocated_from` and `allocated_to`, causing submissions on future-dated allocations to fail with `Custody event must be inside the allocation period.`
7. Custody events were only saved as drafts without an immediate "Save & confirm" flow, leaving allocations in `planned` status unless users found and clicked a separate "Confirm" button in the table below. When confirmed, `allocationDetails` was not reloaded, leaving the page in a stale state.
8. Lookups in `RentalLookups.tsx` (`RentalAllocationLookupSelect` and `RentalAgreementLookupSelect`) did not open on focus without typing 2 characters (`loadOnOpen minSearchLength={0}` was missing).

9. For owner-supplied vehicles, customer handover confirmation failed because:
   - The owner-supplied vehicle had not been received by the company via an `owner_to_company` handover event on the source allocation.
   - When attempting to confirm `owner_to_company` on the source allocation, `RentalAvailabilityService::assertVehicle` detected the planned customer allocation as a conflicting allocation, completely deadlocking the custody workflow.
   - When customer handover was attempted without owner receipt, `RentalCustodyService` threw an unhandled `InvalidArgumentException` rather than a standard `ValidationException`.
10. Agreement creation on `/vehicle-rental/agreements/create` failed with `expected_version: The rental rate version changed after it was loaded. Reload and review the latest version.` because `RentalRateVersionService::createDraft` did not explicitly persist `'row_version' => 1`.

## What Changed

- **Backend Bug Fixes**:
  - `RentalAvailabilityService.php`: Updated vehicle conflict query to exclude child allocations referencing the source allocation (`source_allocation_id === $excludeAllocationId`), resolving the custody activation deadlock between owner and customer allocations.
  - `RentalRateVersionService.php`: Explicitly added `'row_version' => 1` upon draft rate version creation.
  - `RentalAgreementService.php`: Updated rate version activation call to pass `(int) ($version->row_version ?: 1)`.
  - `RentalCustodyService.php`:
    - Removed non-existent `'attachments'` from `$this->relations()`.
    - Explicitly persisted `'row_version' => 1` and called `$event->refresh()->load($this->relations())` upon creation.
    - In `assertEventAllowed` and `activateCustomerAllocation`, validated that owner-supplied vehicles must be received by the company before customer handover, throwing clear `ValidationException` messages (`event_type: Owner-supplied vehicle must be received by the company from the owner before customer handover can be confirmed.`).
  - `RentalExpenseService.php`: Removed non-existent `'attachments'` from `$this->relations()`.
  - `RentalReplacementService.php`: Fixed calls to `$this->custody->confirm()` to supply `(int) $event->row_version` as `expectedVersion`.
  - Added backend feature tests in `tests/Feature/VehicleRental/RentalCustodyHandoverTest.php` verifying both standard customer handover and owner-supplied vehicle custody handover lifecycles.

- **Frontend Enhancements**:
  - `RentalCustodyPage.tsx`:
    - Detected owner-supplied allocations requiring owner receipt (`vehicle_source_type === 'owner_supplied'` with planned `source_allocation`).
    - Displayed an explanatory amber guidance banner with a 1-click shortcut button: **"Receive vehicle from owner first"** (`/vehicle-rental/custody?allocation_id=${source_allocation.id}`).
    - Disabled customer handover submission and confirmation buttons with clear explanatory status text when owner receipt is pending.
    - Filtered available event types by allocation lifecycle status: `planned` allocations offer handover events, `active` allocations offer return events, and closed allocations show a completed notice.
    - Pre-filled `odometer` from the allocation's `start_odometer`.
    - Clamped `occurred_at` within `allocated_from` and `allocated_to`, and set `min` and `max` constraints on the datetime input.
    - Added a "Save & confirm" button for single-action handover completion, alongside "Save custody event" for draft preparation.
    - Reloaded allocation details immediately after confirmation so the UI transitions from `planned` to `active` seamlessly.
    - Added an allocation summary card displaying vehicle, agreement, status, and odometer details.
  - `RentalLookups.tsx`: Added `loadOnOpen` and `minSearchLength={0}` to `RentalAllocationLookupSelect` and `RentalAgreementLookupSelect`.
  - `RentalAllocationDetailPage.tsx`: Contextualized custody action button to read "Handover vehicle" (primary) when `planned`, and "Return vehicle" when `active`.
  - `RentalAllocationPage.tsx`: Added a quick "Handover" link in the allocations table for planned allocations.

## Verification

- `php artisan test tests/Feature/VehicleRental/RentalCustodyHandoverTest.php` (2 passed, 18 assertions)
- `php artisan test --filter=VehicleRental` (18 passed, 211 assertions)
- `npx vitest run resources/js/modules/vehicle-rental --reporter=dot` (4 test files, 12 passed)
- `npm run typecheck` (passed with 0 errors)
- `npm run build` (built production bundle cleanly in 2.37s)
- `git diff --check` (clean diff, no whitespace or formatting errors)
