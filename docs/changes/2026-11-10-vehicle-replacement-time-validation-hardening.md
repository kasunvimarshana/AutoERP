# Vehicle Rental: Vehicle Replacement Time Validation Hardening & Precision Disambiguation

**Date:** 2026-11-10  
**Modules:** `VehicleRental`  
**Scope:** Resolves the ambiguous vehicle replacement error `"Return must follow handover and cannot be in the future."`, fixes initial timestamp defaulting on replacement modal, bounds replacement inputs strictly to physical execution limits (between handover and now), disambiguates backend error messaging, and enforces atomic version checks across both backend and frontend layers.

---

## 1. Problem & Root Cause Analysis

### A. Frontend Form Initializer & Missing Upper Bound
- **Symptoms:**
  1. Opening the "Replace Vehicle" modal/card for an in-custody vehicle prefilled the "Replacement time" input with the old vehicle's exact handover timestamp (`handed_over_at`).
  2. The input's `max` boundary was set to `end || maxEnd`, where `maxEnd` was the agreement's end date (e.g. November 2027), allowing operators to select future dates (such as `11/12/2026 11:51:38 PM` shown in the screenshot).
  3. When operators submitted a future date or left the prefilled timestamp unchanged, the system failed with:
     ```
     starts_at: Return must follow handover and cannot be in the future.
     ```
- **Root Causes:**
  1. **Incorrect Initial Value:** Initializing `start` with `replacementHandoverLocal` caused `starts_at` to equal `handed_over_at` by default. Under the rule that return/replacement must strictly succeed handover, submitting the form without manual adjustment guaranteed failure.
  2. **Overly Permissive Upper Bound:** Vehicle replacement is an immediate real-world custody swap (the old vehicle is returned and the new one handed over). Physical custody handovers cannot occur in the future. Bounding `max` to the agreement end date in 2027 allowed operators to enter future dates that were guaranteed to fail backend validation.
  3. **Lack of Contextual Guidance:** The hint under the input only stated the previous handover time without explaining that replacement must occur between the previous handover and the current time.

### B. Backend Ambiguous Combined Validation & Concurrency Timing
- **Symptoms:**
  Backend `VehicleUseService::transition()` combined two distinct validation rules into a single error string:
  ```php
  if ($at->greaterThan($now) || $at <= $record->handed_over_at) {
      throw ValidationException::withMessages(['occurred_at' => ['Return must follow handover and cannot be in the future.']]);
  }
  ```
  And when `VehicleUseService::replace()` caught this error, it mapped `occurred_at` directly to `starts_at` without updating the domain action context.
- **Root Causes:**
  1. **Compound Error String:** If an operator entered a future date (e.g. `11/12/2026`), the error message claimed both conditions simultaneously, leaving operators confused about whether the error was due to the future date or precedence relative to handover.
  2. **Mismatched Action Verbiage:** In a replacement workflow, the operator is replacing an assigned vehicle, not executing a standalone return. Presenting an error saying "Return must follow handover" on a field titled "Replacement time" violates clear UI/UX feedback principles.
  3. **Premature State Mutation vs. Concurrency Checking:** Concurrency version checking must take precedence over domain status assertions to ensure proper 409 Conflict responses on concurrent updates.

---

## 2. Solutions & Implementation

### A. Backend Foundation (`VehicleUseService.php`)
1. **Disambiguated `ReturnVehicle` and `Handover` Validation:**
   - Separated future time checks from handover boundary checks in `transition()`:
     - Future return: `"Return time cannot be in the future."`
     - Pre-handover return: `"Return must follow handover."`
     - Future handover: `"Handover time cannot be in the future."`
     - Past return handover: `"Record an actual handover before the planned return."`
2. **Early Validation in `replace()`:**
   - Validates `$startsAt->greaterThan($now)` before starting the database transaction or acquiring row locks, throwing `starts_at => 'Replacement time cannot be in the future.'`.
   - In `DB::transaction`:
     - Verifies optimistic concurrency version immediately (`$this->version($previous->row_version, $expectedVersion)`).
     - Validates that previous vehicle is currently in custody (`status => 'Only in-custody vehicles can be replaced.'`).
     - Validates that replacement time strictly succeeds previous handover (`starts_at => "Replacement time must follow the previous vehicle's handover time."`).
3. **Replacement Context Error Mapping:**
   - In `replace()` catch blocks for both transition steps, mapped any remaining `occurred_at` errors to replacement-specific wording on `starts_at`.

### B. Frontend Implementation (`VehicleUseEditor.tsx`)
1. **Dynamic Initialization:**
   - Initialized `start` for replacements to the current local timestamp (`localTimestampValue(new Date().toISOString())`).
2. **Strict Time Bounding:**
   - Enforced `max={replacement ? (end && end < todayEnd ? end : todayEnd) : (end || maxEnd)}`, capping replacement time to `${businessDateInputValue()}T23:59:59`.
3. **Immediate Client-Side Feedback:**
   - Added immediate client validation on submit checking that replacement time strictly follows `replacement.handed_over_at` and is not in the future (with 1-minute clock skew tolerance).
4. **Contextual Guidance:**
   - Updated the hint on "Replacement time" to:
     `Replacement time must follow previous handover (${formatBusinessDateTime(replacement.handed_over_at)}) and cannot be in the future.`

---

## 3. Verification & Quality Gates

- **Backend PHPUnit Tests (`php artisan test app/Modules/VehicleRental/Tests`):**
  - Added test coverage in `VehicleUseReplacementValidationTest.php`:
    - `test_future_replacement_time_is_rejected_before_existing_custody_changes`
    - `test_replacement_time_at_or_before_previous_handover_is_rejected_before_custody_changes`
    - `test_valid_replacement_between_handover_and_now_succeeds_end_to_end`
  - **Result:** **233 passed (1,975 assertions)**.
- **Frontend Vitest Tests (`npx vitest run resources/js/modules/vehicle-rental`):**
  - Added test coverage in `VehicleUseEditor.test.tsx`:
    - Verified `min`, `max`, and contextual guidance rendering on replacement time.
    - Verified immediate client validation rejection of future replacement times.
    - Verified immediate client validation rejection of replacement times at or before previous handover.
  - **Result:** **29 passed (29 test files, 133 tests)**.
- **Static Analysis & Linting:**
  - `npm run typecheck`: **0 errors**.
  - `npm run lint`: **0 errors**.
