# Vehicle Rental: Form Simplification, Browser Datetime Clamping Elimination, and Progressive Disclosure Refactoring

**Date:** 2026-11-10  
**Modules:** `VehicleRental`  
**Scope:** Streamlines VehicleRental editors and billing panels to eliminate cognitive clutter, eliminates browser-native `min`/`max` datetime input clamping that locked datepickers, replaces HTML attribute clamping with robust client-side validation on submit, and reorganizes forms using progressive disclosure.

---

## 1. Problem & Root Cause Analysis

### A. Date-Picker Locking & Input Greyouts
- **Symptoms:**
  - In `AgreementEditor.tsx`, `BaseRentBillingPanel.tsx`, `BaseRentPreviewPanel.tsx`, `MileageAssessmentPanel.tsx`, and `UsageChargePanel.tsx`, native attributes such as `max={endsOn}`, `min={from}`, and `min={invoiceDate}` on `<input type="date">` caused browser-native datepicker popovers to grey out and disable dates.
  - When users wanted to change a start date or document date after already selecting an end date or due date, the picker blocked selection and forced users to manually clear preceding fields before adjusting.
- **Root Causes:**
  - Reliance on HTML attribute-level clamping (`min` and `max`) rather than validating chronological precedence during form submission.

### B. Form Cognitive Clutter & Responsive Density
- **Symptoms:**
  - In `RentalExpenseEditor.tsx`, a rigid 4-column desktop layout (`md:grid-cols-4`) crammed input fields into narrow columns, while optional secondary fields (Expense Number override, Supplier/Station, Bill/Receipt Reference, Description) were mixed directly into primary financial inputs.
- **Root Causes:**
  - Lack of progressive disclosure separating primary transaction essentials from optional secondary metadata.

---

## 2. Solutions & Implementation

### A. Elimination of Native Datetime Clamping
1. **`AgreementEditor.tsx`:**
   - Removed `max={endsOn || undefined}` from `startsOn`, allowing unobstructed date selection.
   - Enforced chronological consistency on submit: `if (endsOn && endsOn < startsOn)` rejects with a clear field error `"End date must be on or after start date."`.
2. **`BaseRentBillingPanel.tsx`:**
   - Removed `min={from}` from `until` and `min={invoiceDate}` from `dueDate`.
   - Enforced chronological validation in `submit()` for both charge period and due date.
3. **`BaseRentPreviewPanel.tsx`:**
   - Removed `min={from}` from `until`, restoring free calendar navigation.
   - Added pre-submission validation ensuring estimate through date follows estimate from date.
4. **`MileageAssessmentPanel.tsx` & `UsageChargePanel.tsx`:**
   - Removed `min={invoiceDate}` from `dueDate`.
   - Enforced client-side validation that due date cannot precede document date.

### B. Progressive Disclosure & Responsive Form Ergonomics
1. **`RentalExpenseEditor.tsx`:**
   - Restructured layout to responsive 3-column rows (`sm:grid-cols-2 lg:grid-cols-3`).
   - Kept primary essentials in the main form flow: Vehicle, Expense Type, Expense Date, Currency, Net Amount, Tax Amount.
   - Moved optional secondary fields (Expense Number override, Supplier/Station, Bill/Receipt Reference, Description) into an expandable `<details>` section with clear summary guidance.
   - Added client-side validation on submit ensuring net and tax amounts are non-negative decimals.

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
  - `npm run build`: **Built successfully (all assets compiled and bundled in 8.87s)**.
