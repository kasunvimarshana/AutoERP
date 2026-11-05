# Vehicle Rental Planned Return Optionality and Owner Agreement Coverage Fix

## Context
When assigning vehicles to customer agreements (`VehicleUseEditor`), users were required to provide both "Planned Handover" (`starts_at`) and "Planned Return" (`ends_at`), even though "Planned Return" was marked as `(optional)` in the UI. When `Planned Return` was left empty:
1. `OwnerSourceService::list` had `'ends_at' => ['present', 'nullable', 'string']`, rejecting omitted parameters with validation errors.
2. If `ends_at` was empty, `OwnerSourceService::list` filtered by `whereNull('ends_on')`, hiding all active owner agreements with valid contractual end dates.
3. In `VehicleUseService::assertCoverage()`, the condition `($agreement->ends_on !== null && ($end === null || $agreement->ends_on->toDateString() < $end))` rejected ANY agreement with an `ends_on` date whenever `$end` was null.
4. `OperationalTime::plannedPeriod()` threw an exception when an empty string `""` was passed for `$endsAt`.
5. Empty strings in optional inputs (odometers, filter ranges) could trigger validator or parsing errors across vehicle custody flows.

## What Changed
1. **`OperationalTime.php`**:
   - Updated `plannedPeriod()` to normalize empty or whitespace string `$endsAt` to `null`.
2. **`OwnerSourceService.php`**:
   - Changed validation for `ends_at` to `['sometimes', 'nullable', 'string']`.
   - Added `'customer_agreement_id' => ['sometimes', 'nullable', 'integer', 'min:1']`.
   - When `$end === null` and `customer_agreement_id` is supplied, uses `$customerAgreement->ends_on` as the coverage boundary so active owner agreements covering that customer agreement (or open-ended ones) are returned in the dropdown.
3. **`VehicleUseController.php`**:
   - Updated `sources()` to include `customer_agreement_id` in forwarded request parameters.
4. **`VehicleUseService.php`**:
   - In `planRules()` and `validatePlanInput()`, normalized empty string `ends_at` and `notes` to `null`.
   - In `plan()`, when `$end === null`:
     - If `$customer->ends_on !== null`, effective vehicle coverage bound for owner agreement and company ownership is the end of the customer agreement date.
     - If `$customer->ends_on === null` and `$owner->ends_on !== null`, rejects the assignment with an explicit validation error: *"An open-ended customer rental requires open-ended vehicle supply."*
   - In `assertCoverage()`, updated the check so that when `$end === null`, it only checks that the agreement has not already ended prior to `$start` (`$agreement->ends_on->toDateString() < $start`), rather than rejecting all agreements with an end date.
   - In `transition()` and `replace()`, normalized empty string odometers (`odometer`, `return_odometer`, `handover_odometer`) to `null`.
5. **`VehicleUseRegisterService.php`**:
   - Normalized empty string query filters (`from`, `until`, `use_status`, `search`) to `null` to prevent parsing crashes when empty strings are received from GET query parameters.
6. **`VehicleUseEditor.tsx`**:
   - Forwarded `customer_agreement_id: agreement.id` in the `sources` lookup callback.
   - Updated helper and prompt text to clarify that Planned Return is optional and explain that the assignment covers up to agreement end (or remains open-ended).
7. **Tests**:
   - Added tests in `OwnerSourceTest.php` verifying that owner agreements covering the customer agreement are returned when `ends_at` is empty string or null.
   - Added tests in `VehicleUseTest.php` verifying that assignments with owner agreements succeed when `ends_at` is omitted and the owner covers the customer agreement, and fail appropriately if the owner agreement terminates prior to customer agreement completion.
   - Updated `VehicleUseEditor.test.tsx` and added test verifying owner agreement assignments can be saved with empty planned return.

## Verification
- All 194 PHPUnit tests in `Modules\VehicleRental\Tests` pass (1,699 assertions).
- All 115 Vitest tests in `resources/js/modules/vehicle-rental` pass.
- TypeScript check (`npm run typecheck`) passed with 0 errors.
- ESLint (`npm run lint`) passed with 0 errors.
- Vite build (`npm run build`) succeeded without warnings or errors.
