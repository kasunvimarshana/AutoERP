# Vehicle Rental: Running Chart Usage Period Validation & Workspace Timezone Alignment

**Date:** 2026-11-10  
**Modules:** `VehicleRental`  
**Scope:** Running Chart Editor & Validation: Temporal validation rules, workspace timezone (`Asia/Colombo`) alignment, clock drift tolerance, and UI boundaries.

---

## 1. Problem Statement & Root Cause Analysis

Operators recording completed vehicle usage in `Customer Agreements → Assigned Vehicles & Handover → Record Running Chart` encountered the validation error:
`ends_at: Record completed usage ending after its start, not in the future.`

### Identified Root Causes:
1. **Unbounded Datetime Input in UI (`RunningChartEditor.tsx`)**:
   - The `<Input type="datetime-local">` component for `Usage end` set `max={custodyEnd}`. When vehicle custody was active and open-ended (`use.returned_at === null`), `custodyEnd` evaluated to `undefined`.
   - Without an upper limit, opening the date picker defaulted to the current calendar date (e.g. `11/10/2026`), leading operators who keyed in 12-hour times (e.g. `11:58:00 PM`) to inadvertently submit timestamps 23+ hours in the future instead of the intended trip end.
2. **Zero Clock-Skew Tolerance on Server Validation (`RunningChartValidation.php`)**:
   - `RunningChartValidation` executed `$end->isFuture()` with zero tolerance for client-server clock drift. When an operator clicked "Use current time", any sub-minute or sub-second discrepancy where the client machine was ahead of the server clock immediately triggered the future error.
3. **Workspace Timezone Misalignment in Operational Time Comparisons**:
   - `RunningChartValidation` did not resolve the tenant's configured commercial calendar (`RentalCalendar::timezone($context)`, configured as `Asia/Colombo`), leaving comparisons evaluated against the global application clock without explicitly converting parsed Carbon instances to the tenant's workspace timezone.
   - Similarly, vehicle return transitions in `VehicleUseService::transition` evaluated `$at->isFuture()` without tenant timezone conversion or clock drift tolerance.

---

## 2. Changes Implemented

1. **`app/Modules/VehicleRental/Services/RentalCalendar.php`**:
   - Added `now(AgreementContext $context): CarbonImmutable` returning the current commercial instant in the tenant's workspace timezone (`Asia/Colombo`).
2. **`app/Modules/VehicleRental/Services/RunningChartValidation.php`**:
   - Injected `RentalCalendar $calendar`.
   - Converted both `starts_at` and `ends_at` to the workspace timezone.
   - Verified that usage cannot start in the future (`$start > $now`).
   - Verified that completed usage cannot end on or before its start (`$end <= $start`) and cannot end in the future (`$end > $now->addMinute()`), providing a 1-minute skew tolerance consistent with `VehicleUseService`.
3. **`app/Modules/VehicleRental/Services/VehicleUseService.php`**:
   - Updated `ReturnVehicle` and `Handover` transitions to explicitly convert event timestamps to the workspace timezone and check against `$now = CarbonImmutable::now($tz)->addMinute()`.
4. **`resources/js/modules/vehicle-rental/RunningChartEditor.tsx`**:
   - Computed `currentNow` in the business timezone.
   - Enforced `maxEnd = custodyEnd && custodyEnd < currentNow ? custodyEnd : currentNow` and `maxStart = end && end < maxEnd ? end : maxEnd` on both input fields, preventing future datetime selection in the browser.
   - Enhanced the "Use current time" button to clamp to `maxEnd`, and introduced "Use custody return time" when vehicle return evidence is already recorded.
   - Added client-side pre-submit guards for immediate UI feedback.
5. **Tests**:
   - `RunningChartTest.php`: Added `test_usage_period_validation_enforces_order_rejects_future_and_tolerates_clock_skew_in_workspace_timezone`.
   - `VehicleUseTest.php`: Added `test_return_enforces_order_rejects_future_and_tolerates_clock_skew_in_workspace_timezone`.
   - `RunningChartEditor.test.tsx`: Added tests verifying `max` bounds, client-side validation, and custody return shortcut buttons.

---

## 3. Verification

- **PHPUnit Backend Tests**:
  - `Modules\VehicleRental\Tests`: **229 passed (1,936 assertions)** with 0 failures.
- **Frontend Vitest Suites**:
  - `resources/js/modules/vehicle-rental`: **29 passed test files, 130 passed tests** with 0 failures.
- **Static Analysis & Linting**:
  - `npm run typecheck`: **0 errors**.
  - `npm run lint`: **0 errors, 0 warnings**.
