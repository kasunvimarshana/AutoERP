# Vehicle Rental: Custody Journey Audit, Dynamic Clock Skew Alignment & Report Timezone Synchronization

**Date:** 2026-11-10  
**Modules:** `VehicleRental`  
**Scope:** Customer Agreements → Assigned Vehicles & Handover → Running Charts: Comprehensive workflow audit across vehicle assignment, handover, running chart recording, and running chart report generation.

---

## 1. Problem Statement & Audit Findings

Across the complete operator journey shown in the audit screenshots:
1. **Screen 1 (Vehicle Assignment to Customer Agreement)**:
   - Operators assigning vehicle (e.g. `CCC3945`) with open-ended planned return encountered an empty Owner Agreement lookup dropdown and validation rejection (`Select a valid owner agreement from the list.`).
   - *Root cause resolved in `OwnerSourceService` and `VehicleUseService`:* Submissions without an explicit return date now evaluate coverage against the handover date instead of requiring the owner agreement to outlast the customer agreement.
2. **Screen 2 (Record Running Chart - Usage End Future Validation)**:
   - Operators recording completed usage encountered `ends_at: Record completed usage ending after its start, not in the future.`.
   - *Root cause identified & resolved:* 
     - Component-level `currentNow` in `RunningChartEditor.tsx` was captured once at component mount. If an operator spent minutes filling form fields, `maxEnd` was evaluated against the past mount timestamp, causing current entries or the "Use current time" button to fail client validation.
     - The HTML `<input type="datetime-local">` had an unconstrained upper bound when vehicle custody was open-ended, allowing operators to select future days inadvertently when adjusting 12-hour times.
3. **Screen 3 (Record Running Chart - Custody Coverage Boundary)**:
   - Operators entering `Usage start` matching the handover minute (e.g. `11/09/2026 11:51:00 PM`) encountered `period: Usage must fall within actual customer custody.`.
   - *Root cause resolved in `RunningChartService` and `RunningChartEditor`:* Handover timestamps with non-zero seconds (e.g. `23:51:38`) now accommodate 1-minute sub-minute alignment tolerance on the server and preserve full seconds precision in the client shortcuts.
4. **Running Chart Report Timezone Discrepancy (`RunningChartReportService.php`)**:
   - Running chart report generation parsed start/end dates and formatted trip on/off times in UTC instead of the tenant's configured commercial calendar (`RentalCalendar::timezone($context)`). This caused night trips in Sri Lanka (`Asia/Colombo`, UTC+5:30) to render UTC morning hours on printed customer/owner sheets.

---

## 2. Changes Implemented

1. **`resources/js/modules/vehicle-rental/RunningChartEditor.tsx`**:
   - Replaced static mount-time `currentNow` with dynamic `freshLocalNow()` and `freshToleranceNow()` (+1 minute skew tolerance matching backend `$now->addMinute()`).
   - Bounded the `Usage end` input `max` to `custodyEnd` (when vehicle is returned) or the end of the current operational day (`${businessDateInputValue()}T23:59:59`) when custody is active, preventing accidental future-day selections while permitting valid entries throughout the day.
   - Updated "Use current time" button to compute the fresh instant at the click moment, clamping to `custodyEnd` only if the vehicle was already returned in the past.
   - Aligned client-side pre-submit validation with server-side tolerance, eliminating false-positive "in the future" rejections on current entries.
2. **`resources/js/modules/vehicle-rental/VehicleUsePanel.tsx`**:
   - Bounded the Return actual event time input `max` to the current operational day (`${businessDateInputValue()}T23:59:59`).
   - Preserved full timestamp precision (seconds) on Handover and Return "Use current time" shortcuts without stripping them via `.slice(0, 16)`.
3. **`app/Modules/VehicleRental/Services/RunningChartReportService.php`**:
   - Injected `RentalCalendar $calendar` and resolved tenant workspace timezone (`Asia/Colombo`).
   - Parsed report period start and end dates with the tenant timezone.
   - Converted operational dates to UTC for database queries (`$startUtc`, `$endUtc`).
   - Grouped charts by civil date in the workspace timezone.
   - Formatted `onTime` and `offTime` in the workspace timezone, ensuring printed reports reflect actual operational times.
4. **Automated Verification**:
   - `RunningChartEditor.test.tsx`: Added test verifying pre-submit clock drift tolerance and rejection of distant future timestamps.
   - `RunningChartReportTest.php`: Verified matrix report generation and controller endpoints with tenant timezone resolution.

---

## 3. Verification

- **PHPUnit Backend Tests**:
  - `Modules\VehicleRental\Tests`: **230 passed (1,945 assertions)** with 0 failures.
- **Frontend Vitest Suites**:
  - `resources/js/modules/vehicle-rental`: **29 passed test files, 131 passed tests** with 0 failures.
- **Static Analysis & Linting**:
  - `npm run typecheck`: **0 errors**.
  - `npm run lint`: **0 errors, 0 warnings**.
