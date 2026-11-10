# Running Chart Usage Period Validation Message Disambiguation

**Date:** 2026-11-10  
**Module:** `VehicleRental` (`RunningChartValidation.php`, `RunningChartService.php`, `RunningChartEditor.tsx`)  
**Scope:** Resolves the misleading combined validation error (`"Record completed usage ending after its start, not in the future."`) by disambiguating chronological sequence validation from temporal future checks across backend services, request validation, and React components.

---

## 1. Problem & Root Cause Analysis

### A. Misleading Compound Error Message
When an operator selected an end timestamp after the start timestamp but into the future (e.g. `Usage start: 11/10/2026 03:41:09 PM`, `Usage end: 11/11/2026 10:13:47 PM`), the application displayed:
```
Request failed
Record completed usage ending after its start, not in the future.
ends_at: Record completed usage ending after its start, not in the future.
```

### B. Root Cause
In `RunningChartValidation.php`, two distinct domain invariants were evaluated in a single compound `if` statement:
```php
if ($end <= $start || $end->greaterThan($now)) {
    throw ValidationException::withMessages([
        'ends_at' => ['Record completed usage ending after its start, not in the future.']
    ]);
}
```
1. **Rule 1 (Chronological Sequence):** `$end <= $start`. When violated, stating "not in the future" was confusing because the dates could both be in the past.
2. **Rule 2 (Physical Reality of Evidence):** `$end->greaterThan($now)`. When violated with `$end > $start` (such as selecting tomorrow's date), stating "ending after its start" was misleading because the end date *did* follow the start date.
The same compound error message was also mirrored in `RunningChartService::assertCoverage()` and in client-side form validation in `<RunningChartEditor>`.

---

## 2. Solutions Implemented

1. **Separated Validation Rules in [`RunningChartValidation.php`](file:///d:/project-new/AutoERP/app/Modules/VehicleRental/Services/RunningChartValidation.php):**
   - **Start Future Check:** `$start->greaterThan($now)` &rarr; `'Completed usage cannot start in the future.'`
   - **Chronological Sequence:** `$end <= $start` &rarr; `'Usage end must follow usage start.'`
   - **End Future Check:** `$end->greaterThan($now)` &rarr; `'Completed usage cannot end in the future.'`

2. **Synchronized [`RunningChartService.php`](file:///d:/project-new/AutoERP/app/Modules/VehicleRental/Services/RunningChartService.php):**
   - In `assertCoverage()`, updated chronological check `$usageEnd <= $usageStart` to throw `'Usage end must follow usage start.'`.

3. **Synchronized Client Validation in [`RunningChartEditor.tsx`](file:///d:/project-new/AutoERP/resources/js/modules/vehicle-rental/RunningChartEditor.tsx):**
   - Updated `submit()` to return exact, targeted error messages for both alert headers and `error.fields` mapping:
     - `start && end && end <= start` &rarr; `'Usage end must follow usage start.'`
     - `startIso > nowMs` &rarr; `'Completed usage cannot start in the future.'`
     - `endIso > nowMs` &rarr; `'Completed usage cannot end in the future.'`

4. **Updated Test Expectations:**
   - In [`RunningChartTest.php`](file:///d:/project-new/AutoERP/app/Modules/VehicleRental/Tests/RunningChartTest.php), verified distinct error messages for both invalid chronological ordering and future usage dates.
   - In [`RunningChartEditor.test.tsx`](file:///d:/project-new/AutoERP/resources/js/modules/vehicle-rental/RunningChartEditor.test.tsx), updated unit assertions to check for the specific, targeted messages.

---

## 3. Verification & Quality Gates

- **Vitest Unit & Component Tests:**
  - `npx vitest run resources/js/modules/vehicle-rental/RunningChartEditor.test.tsx`: **10 passed (10 tests)**.
  - `npx vitest run resources/js/modules/vehicle-rental`: **29 passed (29 files, 135 tests)**.
- **Backend PHPUnit Tests:**
  - `php artisan test app/Modules/VehicleRental/Tests/RunningChartTest.php`: **19 passed (143 assertions)**.
  - `php artisan test app/Modules/VehicleRental/Tests`: **234 passed (1,981 assertions)**.
- **TypeScript & Lint Verification:**
  - `npm run typecheck`: **0 errors**.
  - `npm run lint`: **0 errors**.
- **Production Asset Build:**
  - `npm run build`: **Built cleanly in 8.51s**.
