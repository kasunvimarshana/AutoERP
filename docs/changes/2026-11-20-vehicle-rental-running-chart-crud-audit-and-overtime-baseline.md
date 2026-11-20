# 2026-11-20 Vehicle Rental Running Chart CRUD Audit and Agreed Working-Hours Overtime Baseline

## Context & Motivation
Following an end-to-end audit and inspection of the VehicleRental module and Running Chart CRUD operations, key opportunities for workflow streamlining, operational accuracy, and operator ergonomics were identified:
1. **Agreed Working Hours Baseline**: When agreements are created with a driver (`driver_mode === 'with_driver'`), the contract typically specifies an agreed daily working-hours baseline (e.g., standard 8 hours or custom daily shifts). This baseline was previously not captured explicitly as a configurable operational term in agreement terms, requiring manual manual calculation of overtime by dispatchers.
2. **Automatic Overtime Calculation**: Running charts record operational usage from `starts_at` to `ends_at`. Without automated overtime computation from the agreed working-hours baseline, dispatchers had to manually convert times into minutes, calculate overtime, and type the numbers.
3. **Manual Overtime Adjustment Flexibility**: While baseline calculation provides an automated default, operational realities require dispatcher flexibility to manually override overtime (e.g., traffic delays, authorized unpaid breaks, or split allocations).
4. **Human-Readable Durations & Distance Shortcuts**: Time counts in running charts are stored as integer minutes (`normal_ot_minutes`, `double_ot_minutes`), and distances are stored as kilometers. Operators need immediate human-readable hours/minutes feedback (e.g., `150 mins = 2 hrs 30 mins`) and 1-click shortcuts to set commercial distance directly from odometer differences.
5. **Data Protection Across Commercial Sides**: In owner-supplied vehicle operations, owner commercial rates must never leak to customer-facing views or vice versa, while operational terms like agreed working hours must be safely accessible for running chart calculations.

---

## Architectural & Design Decisions

### 1. Unified Agreement Working Hours Term (`working_hours`)
- Rather than creating fragile, disconnected configuration tables or hardcoded magic numbers, `'working_hours'` is defined in `AgreementFields::AMOUNTS`.
- It is validated with `DECIMAL_PATTERN`, formatted to 2 decimal places, and safely stored in the agreement `terms` JSON structure for both `CustomerAgreement` and `OwnerAgreement`.
- Succession lineage preserves or cleanly amends working hours across contract renewals.
- The UI presents a dedicated, prominent input in `AgreementEditor` whenever `driver_mode === 'with_driver'` is selected, pre-populating with standard `'8.0'` hours while allowing custom adjustment or blanking.

### 2. Pure Overtime Calculation Service (`OvertimeCalculator`)
- Implemented `app/Modules/VehicleRental/Services/OvertimeCalculator.php` following single-responsibility and KISS principles.
- `calculateOtMinutes(?string $startsAt, ?string $endsAt, float|int|string|null $baselineHours)`:
  - Parses start and end timestamps into total elapsed duration in minutes.
  - Converts baseline hours into baseline minutes (e.g., 8.0 hrs = 480 mins).
  - Returns `max(0, elapsedMinutes - baselineMinutes)` or 0 if within baseline or invalid.
- `formatMinutes(?int $minutes)`: Returns human-friendly strings like `2 hrs 30 mins` or `45 mins`.

### 3. Safe Resource Exposure Without Leaks
- `VehicleUseResource` and `RunningChartRegisterResource` directly expose `'working_hours' => $agreement->terms['working_hours'] ?? null` on both customer and owner agreements.
- Raw `terms` array remains strictly excluded on `owner_agreement` to maintain commercial secrecy and satisfy strict security assertions in existing regression tests.

### 4. Interactive Running Chart Reactive Calculation & Manual Overrides
- In `RunningChartEditor.tsx`, the baseline hours are derived from `customer_agreement?.working_hours ?? owner_agreement?.working_hours ?? '8.0'`.
- A dedicated "Working Hours & Overtime Baseline" card displays:
  - Actual trip duration (formatted as hours and minutes).
  - Agreed daily baseline with an inline adjustment tool.
  - Calculated overtime minutes and formatted duration.
  - Current recorded overtime with status badge (`Matches calculated` or `Manually adjusted`).
  - Action buttons: "Reset to calculated" and "Apply to Double OT".
- Auto-calculation triggers when `Usage start` or `Usage end` changes, unless the user has manually edited the overtime input.
- Respects backend empty-string contract: if calculated overtime is 0 and no OT is entered, empty string is preserved so the backend cleanly stores `null`.
- Real-time physical trip distance calculator (`end_odometer - start_odometer`) with a "Set commercial distance" shortcut button.

### 5. Multi-View Human-Readable Duration Formatting
- Both `RunningChartsPanel.tsx` and `RunningChartRegisterPage.tsx` display human-readable overtime durations alongside raw minutes in summaries and detail views.
- Fixed print sheet routing in `RunningChartRegisterPage.tsx` to pass the owner agreement ID when printing owner run sheets.

---

## Changes Implemented

### Backend
- `app/Modules/VehicleRental/Constants/AgreementFields.php`:
  - Added `'working_hours'` to `AMOUNTS` list.
- `app/Modules/VehicleRental/Services/OvertimeCalculator.php`:
  - New pure service with `calculateOtMinutes()` and `formatMinutes()`.
- `app/Modules/VehicleRental/Http/Resources/VehicleUseResource.php`:
  - Added `'working_hours'` to `customer_agreement` and `owner_agreement`.
- `app/Modules/VehicleRental/Http/Resources/RunningChartRegisterResource.php`:
  - Added `'working_hours'` to top-level and nested customer/owner agreement resources.
- `app/Modules/VehicleRental/Tests/OvertimeCalculationTest.php`:
  - 5 comprehensive test methods (44 assertions) verifying calculations, baseline handling, agreement persistence/lineage, and resource exposure.

### Frontend
- `resources/js/modules/vehicle-rental/agreements.ts`:
  - Added `working_hours` to `TERM_LABELS` and `OWNER_TERM_LABEL_OVERRIDES`.
- `resources/js/modules/vehicle-rental/AgreementTermsGrid.tsx`:
  - Formatted `working_hours` with `<QuantityDisplay /> hrs/day`.
- `resources/js/modules/vehicle-rental/AgreementEditor.tsx`:
  - Added dedicated working-hours field with auto-suggested `'8.0'` default for with-driver agreements.
- `resources/js/modules/vehicle-rental/vehicleUse.ts` & `runningCharts.ts`:
  - Added `working_hours` to type definitions.
  - Added `DEFAULT_WORKING_HOURS_BASELINE`, `calculateOtMinutes()`, `formatMinutes()`, `formatMinutesDecimalHours()`.
- `resources/js/modules/vehicle-rental/RunningChartEditor.tsx`:
  - Working Hours & Overtime Baseline card with auto-calculation, manual override, reset, and live status.
  - Physical distance auto-calculation with prefill button for commercial distance.
  - Dynamic formatted hour display beside minute inputs.
- `resources/js/modules/vehicle-rental/RunningChartsPanel.tsx`:
  - Displayed overtime duration in summaries and expanded details.
- `resources/js/modules/vehicle-rental/RunningChartRegisterPage.tsx`:
  - Displayed overtime duration in summaries and details. Fixed owner run sheet print parameter.
- `resources/js/modules/vehicle-rental/AgreementEditor.test.tsx` & `RunningChartEditor.test.tsx`:
  - Comprehensive tests for working-hours inputs, overtime calculation, manual adjustments, and odometer calculations.

---

## Verification Record
- **Backend PHPUnit**: 335 passed (2810 assertions) across all VehicleRental test suites.
- **Frontend Vitest**: 38 test files passed (178 tests) across all VehicleRental frontend suites.
- **TypeScript**: `tsc --noEmit` exited code 0 with zero errors.
