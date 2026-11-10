# Vehicle Rental: End-to-End Datetime Clamping Elimination and Custody Flow Hardening

**Date:** 2026-11-11  
**Module:** `VehicleRental` (`app/Modules/VehicleRental`, `resources/js/modules/vehicle-rental`)  
**Scope:** Eliminates all remaining browser-native HTML datetime input clamping (`min`/`max` attributes) across the entire Vehicle Rental module, replaces rigid calendar lockouts with contextual client-side submit validation and backend enforcement, and hardens operational custody workflows.

---

## 1. Problem & Root Cause Analysis

### A. Date-Picker Greyouts & Native Input Locks
- **Symptoms:**
  - Across vehicle custody assignment (`VehicleUseEditor.tsx`), custody transitions (`VehicleUsePanel.tsx`), agreement successors (`AgreementSuccessorForm.tsx`), base rent billing (`BaseRentBillingPanel.tsx`), base rent preview (`BaseRentPreviewPanel.tsx`), and running chart reports (`RentalRunningChartReportPage.tsx`), native HTML attributes `min` and `max` on `<input type="date">` and `<input type="datetime-local">` caused browser calendar popovers (especially on Chromium/Windows) to disable, grey out, or lock date and hour pickers.
  - Users attempting to adjust planned handovers, replacements, report periods, or returns were blocked whenever related fields held partial timestamps or differing date values.
- **Root Cause:**
  - Reliance on HTML attribute-level constraints (`min={...}`, `max={...}`) rather than verifying chronological precedence and business rules dynamically upon form submission.

### B. Premature Physical Evidence Finalization
- **Symptoms:**
  - Finalization button was previously enabled on active draft running charts, causing submissions to fail with `"Validation failed: starts_at: Completed usage cannot start in the future"` when users attempted to finalize upcoming or ongoing trips.
- **Root Cause:**
  - Conflation between saving scheduled running chart drafts during active contracts and finalization of physical evidence. Drafts can be saved in advance, but physical evidence finalization requires completed historical custody.

---

## 2. Implemented Solutions

### A. Total Elimination of Browser-Native Datetime Clamping
1. **`VehicleUseEditor.tsx`:**
   - Removed `min={minStart}` and `max={maxStart}` from planned handover/replacement time input.
   - Removed `min={...}` and `max={maxEnd}` from planned return input.
   - Replaced HTML attribute clamping with client-side submit validations:
     - Planned handover cannot precede agreement start date.
     - Planned return cannot exceed agreement end date.
     - Planned return must follow planned handover.
     - Replacement time must follow previous handover and cannot be in the future (+2 min clock drift grace).
   - Removed dead clamping variables (`minStart`, `maxEnd`, `todayEnd`, `maxStart`, `replacementHandoverLocal`).

2. **`VehicleUsePanel.tsx`:**
   - Removed `min={...}` and `max={...}` from "Actual event time" input.
   - Preserved instant client-side submit validation: handover/return cannot be in the future (+2 min clock drift grace), and return must follow handover.
   - Retained numerical minimum bounds on physical odometer readings (`min={action.row.handover_odometer}`).

3. **`AgreementSuccessorForm.tsx` & `AgreementEditor.tsx`:**
   - Removed `min` and `max` date clamping from start date, end date, and effective dates.
   - Enforced chronological precedence on submit.

4. **`BaseRentBillingPanel.tsx` & `BaseRentPreviewPanel.tsx`:**
   - Removed `min` and `max` date clamping from billing period (`from`/`until`) and due dates.
   - Enforced charge period validation in submit handler.

5. **`RunningChartRegisterPage.tsx` & `VehicleUseRegisterPage.tsx`:**
   - Removed `min` and `max` attributes from optional period filter inputs, allowing unrestricted chronological filtering.

6. **`RentalRunningChartReportPage.tsx`:**
   - Removed `min={startDate}` from `endDate` input.
   - Added client-side submit validation: `if (startDate && endDate && endDate < startDate)` rejects with `"Period end date must be on or after period start date."`.

### B. Unit & Integration Test Modernization
- Updated `AgreementEditor.test.tsx`, `RunningChartRegisterPage.test.tsx`, `VehicleUseEditor.test.tsx`, `VehicleUsePanel.test.tsx`, and `VehicleUseRegisterPage.test.tsx` to assert that date/datetime inputs avoid native attribute clamping while validating business boundaries accurately.

---

## 3. Quality Verification & Verification Gates

All automated verification gates passed with zero errors:

- **Backend PHPUnit Tests:**
  - `php artisan test app/Modules/VehicleRental/Tests`: **234 passed (2,007 assertions)** in 39.56s.
- **Frontend Vitest Suites:**
  - `npx vitest run resources/js/modules/vehicle-rental`: **29 passed (29 test files, 138 tests)** in 53.75s.
- **TypeScript Typecheck:**
  - `npm run typecheck`: **0 errors**.
- **ESLint Code Quality:**
  - `npm run lint`: **0 errors, 0 warnings**.
- **Production Asset Build:**
  - `npm run build`: **Built successfully in 9.26s** (754 modules transformed, all chunks compiled and hashed).
