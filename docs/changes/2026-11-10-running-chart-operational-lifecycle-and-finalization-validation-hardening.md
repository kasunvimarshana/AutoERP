# Vehicle Rental: Running Chart Operational Lifecycle & Datetime Finalization Validation Hardening

**Date:** 2026-11-10  
**Modules:** `VehicleRental`  
**Scope:** Comprehensive deep audit, root cause resolution, and operational hardening of Running Chart datetime validations, physical evidence finalization, vehicle return custody boundaries, and frontend/backend lifecycle synchronization.

---

## 1. Problem & Root Cause Analysis

### A. Premature Finalization of Future / In-Progress Running Charts
- **Symptoms:**
  - An operator creating or reviewing a draft running chart whose usage is scheduled in the future (or currently in progress) was presented with an active **"Finalize usage"** button.
  - Clicking "Confirm finalization" triggered a red validation banner:
    `starts_at: Completed usage cannot start in the future.` (or `ends_at: Completed usage cannot end in the future.`).
- **Root Causes:**
  1. *UX Decoupling Deficit:* While draft charts legitimately permit future start/end timestamps (allowing fleet operators to schedule shifts and log upcoming journeys in advance), the running chart panel (`RunningChartsPanel.tsx`) treated all draft charts identically. It unconditionally rendered an active "Finalize usage" button, inviting operators into an immediate 422 validation failure.
  2. *Misleading Validation Messaging:* The backend error string `"Completed usage cannot start in the future."` was confusing because the operator was interacting with a draft or scheduled trip, not declaring a completed physical record.
  3. *Clock Skew Fragility:* A hardcoded 2-minute offset was scattered ad-hoc across `VehicleUseService.php` and `RunningChartService.php`, lacking a centralized constant and leaving operations vulnerable to distributed browser-server clock drift.

### B. Returned Vehicle Custody Boundary Enforcement
- **Symptoms:**
  - When a vehicle had already been returned (custody ended at `returned_at`), drafting or finalizing a running chart starting after vehicle return produced generic or confusing error feedback.
- **Root Causes:**
  - In `RunningChartService::assertCoverage()`, custody boundary checks only verified `$usageEnd > $return`. If a chart was recorded starting at or after actual return, it fell into a generic custody mismatch rather than explicitly indicating that usage cannot start after vehicle return.

### C. Date-Picker Clamping & Filter Input Greyouts
- **Symptoms:**
  - Setting browser-native `min` on `<input type="datetime-local">` in `VehicleAvailabilityPage.tsx` caused Chromium-based date-time pickers to disable past or future hour selections.
- **Root Causes:**
  - Over-reliance on browser-native HTML attribute clamping instead of explicit client-side form validation on submit.

---

## 2. Solutions & Implementation

### A. Centralized Clock Skew Tolerance & Single Source of Truth
1. **`OperationalFields.php`:**
   - Defined `public const CLOCK_DRIFT_TOLERANCE_MINUTES = 2;` as the single source of truth for clock drift tolerance across all operational time checks.
2. **`VehicleUseService.php`:**
   - Replaced ad-hoc `CarbonImmutable::now($tz)->addMinutes(2)` with `$this->calendar->now($context)->addMinutes(OperationalFields::CLOCK_DRIFT_TOLERANCE_MINUTES)` in `handover`, `returnVehicle`, and `replace`.

### B. Context-Aware Finalization & Custody Enforcement
1. **`RunningChartService.php`:**
   - In `assertCoverage()`:
     - Added explicit validation when vehicle has been returned:
       ```php
       if ($usageStart >= $return) {
           throw ValidationException::withMessages([
               'period' => ['Usage must fall within actual customer custody.'],
               'starts_at' => ['Usage cannot start after vehicle return.'],
           ]);
       }
       ```
   - In `Finalize` action:
     - Differentiated upcoming trips from ongoing trips with precise, user-friendly messages:
       - If `$chart->starts_at->setTimezone($tz)->greaterThan($now)`:
         `'starts_at' => ['Usage has not started yet. Running charts can only be finalized as physical evidence after the trip has concluded.']`
       - If `$chart->ends_at->setTimezone($tz)->greaterThan($now)`:
         `'ends_at' => ['Usage is currently ongoing. Running charts can only be finalized as physical evidence after the trip has concluded.']`

### C. Frontend Operational Lifecycle & Action Guidance
1. **`RunningChartsPanel.tsx`:**
   - Introduced operational state classification for draft running charts:
     - **Upcoming trip:** `starts_at > now + grace` &rarr; Badged as `Upcoming trip` (sky blue); displays guidance: *"Scheduled trip starts {starts_at}. Physical evidence can be finalized after the trip concludes."*; disables the "Finalize usage" button.
     - **Trip in progress:** `starts_at <= now + grace` and `ends_at > now + grace` &rarr; Badged as `Trip in progress` (amber); displays guidance: *"Trip is currently ongoing (scheduled end {ends_at}). Finalize after the trip concludes, or edit draft if concluded early."*; disables the "Finalize usage" button.
     - **Custody overrun:** `ends_at > vehicle.returned_at` &rarr; Badged as `Exceeds return custody` (rose); displays guidance: *"Usage ends after the vehicle was returned ({returned_at}). Edit draft to adjust usage times within actual custody."*; disables "Finalize usage".
     - **Ready to finalize:** `ends_at <= now + grace` &rarr; Badged as `Ready to finalize` (emerald); enables the "Finalize usage" button for immediate one-click confirmation.
   - In `submit()` for finalization: Pre-validates lifecycle on the client with clear, friendly messages before sending network requests.

2. **`RunningChartEditor.tsx`:**
   - Added live contextual callout when a trip extends into the future, assuring operators that future dates are safely saved as drafts and will become eligible for finalization once completed.
   - Refined custody validation messages to explicitly reference actual vehicle handover and return timestamps.

3. **`VehicleAvailabilityPage.tsx`:**
   - Removed native `min` boundary clamping from `<input type="datetime-local">`, restoring unobstructed date/time selection in browser pickers.
   - Enforced chronological order (`endsAt > startsAt`) via client-side submit validation with clear error feedback.

---

## 3. Verification & Quality Gates

- **Backend PHPUnit Tests:**
  - `php artisan test app/Modules/VehicleRental/Tests`: **234 passed (1,986 assertions)**.
- **Frontend Vitest Tests:**
  - `npx vitest run resources/js/modules/vehicle-rental`: **29 passed (29 files, 138 tests)**.
- **TypeScript Typecheck:**
  - `npm run typecheck`: **0 errors**.
- **ESLint:**
  - `npm run lint`: **0 errors**.
- **Production Asset Build:**
  - `npm run build`: **Built successfully (all assets compiled and bundled in 8.96s)**.
