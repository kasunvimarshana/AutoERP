# Running Chart Date Picker: Unconstrained Calendar Navigation & Time Wheel Accessibility

**Date:** 2026-11-10  
**Module:** `VehicleRental` (`RunningChartEditor.tsx`, `RunningChartsPanel.tsx`, `RunningChartValidation.php`)  
**Scope:** Resolves the disabled and greyed-out past and future dates in the Running Chart date/time pickers (`Usage start` and `Usage end`), explains the architectural distinction between domain business rules and native HTML input constraints, restores full calendar navigation, and verifies end-to-end data integrity across client and server layers.

---

## 1. Problem & Root Cause Investigation

### A. Why Past and Future Dates Appeared Disabled in the Calendar
When operators opened the native datepicker modal for `Usage start` or `Usage end`, every date on the calendar except the current day (November 10, 2026) was greyed out and unselectable, and the previous/next month navigation arrows were disabled.

**Technical Root Cause:**
The React component `<RunningChartEditor>` placed native HTML `min` and `max` constraints directly on `<input type="datetime-local">`:
```tsx
const minStart = toHourFloor(custodyStart);
const maxEnd = custodyEnd ?? todayEnd;
const maxStart = maxEnd;
const minEnd = toHourFloor(start || custodyStart);

<Input label="Usage start" type="datetime-local" min={minStart} max={maxStart} ... />
<Input label="Usage end" type="datetime-local" min={minEnd} max={maxEnd} ... />
```
When an active vehicle was handed over on the current day (e.g. `2026-11-10T15:41:09`), `minStart` was clamped to `2026-11-10T15:00` (or `15:41`) and `maxStart` / `maxEnd` was clamped to today's end (`2026-11-10T23:59:59`).
Because both `min` and `max` fell on the exact same calendar day (`2026-11-10`), browser layout engines (notably Chromium/Blink) applied strict native input constraints:
1. Every date before November 10 was disabled.
2. Every date after November 10 was disabled.
3. Month switching was disabled.
4. The time wheel omitted or truncated candidate hours preceding the sub-hour floor (e.g. `03 PM` was missing, leaving only `04 PM` through `11 PM`).

### B. Intentional Business Rules vs. Implementation Bug
- **Domain Business Rules (Intentional):**
  - *Usage within physical custody:* A vehicle cannot be operated before physical customer handover (`starts_at < handed_over_at`), nor after vehicle return (`ends_at > returned_at`), because the customer did not possess the vehicle.
  - *Completed usage not in the future:* Running chart logs record historical evidence (starting/ending odometer readings, driver overtime, commercial distance, highway passes). Completed trips cannot occur in the future (`ends_at > now`).
  - *Chronological integrity:* Usage end must strictly follow usage start (`ends_at > starts_at`), and end odometer cannot decrease.
- **Enforcement Mechanism via Native HTML `min` / `max` (Bug):**
  - Attempting to enforce physical domain boundaries through native browser HTML attributes `min` and `max` broke the user experience:
    1. It locked down the calendar UI, preventing operators from selecting past days for ongoing multi-day rentals or backdating completed trips.
    2. It mutilated the time dropdown wheel, causing operators to select distant hours (e.g., `11 PM` instead of `03 PM`), which then triggered server rejection: `ends_at: Record completed usage ending after its start, not in the future.`.
    3. It broke the user rule: *"MUST design all user interfaces for speed, clarity, and task completion efficiency. Users should be able to finish actions with minimal steps and without cognitive overload."*

---

## 2. Solution Implemented

1. **Unconstrained Date/Time Navigation in `<RunningChartEditor>`:**
   - Removed native `min` and `max` attributes from `Usage start` and `Usage end` `<Input type="datetime-local">` controls.
   - Operators can now freely navigate past, current, and future months and days, and select any hour on the time wheel without browser-enforced input suppression.

2. **Clean React Application-Layer Validation:**
   - In `submit()`, client-side domain validations verify the entered timestamps with clear, contextual feedback before dispatching to the API:
     - `end <= start` -> *"Record completed usage ending after its start, not in the future."*
     - `end_odometer < start_odometer` -> *"End odometer cannot be lower than start odometer."*
     - `start` or `end` beyond current time with drift allowance -> *"Record completed usage ending after its start, not in the future."*
     - `start` before custody start or `end` after custody return -> *"Usage must fall within actual customer custody."*

3. **Backend Authoritative Enforcement (`RunningChartValidation.php`):**
   - Retained strict backend validation rules as the authoritative single source of truth, including tenant-timezone awareness and clock drift tolerance.

---

## 3. Verification & Quality Gates

- **Vitest Unit & Component Tests:**
  - `npx vitest run resources/js/modules/vehicle-rental/RunningChartEditor.test.tsx`: **10 passed (10 tests)**.
  - `npx vitest run resources/js/modules/vehicle-rental`: **29 passed (29 files, 135 tests)**.
- **TypeScript & Lint Verification:**
  - `npm run typecheck`: **0 errors**.
  - `npm run lint`: **0 errors**.
- **Production Asset Build:**
  - `npm run build`: **Built cleanly in 8.35s**.
- **Backend PHPUnit Tests:**
  - `php artisan test app/Modules/VehicleRental/Tests`: **234 passed (1,981 assertions)**.
