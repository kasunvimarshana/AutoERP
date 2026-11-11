# Vehicle Rental: Running Chart Draft Future Dates & Completion Workflow Hardening

**Date:** 2026-11-10  
**Modules:** `VehicleRental`  
**Scope:** Resolves the ambiguous compound validation error `"Record completed usage ending after its start, not in the future."`, separates draft running chart saving from completed usage record finalization, permits future end dates for scheduled (`Planned`) and ongoing (`InCustody`) agreements while strictly enforcing chronological precedence (`ends_at > starts_at`), restricts future end dates solely at completion/finalization time or against returned vehicles, and hardens end-to-end test coverage.

---

## 1. Problem & Root Cause Analysis

### A. Premature Enforcement on Draft Creation & Ambiguous Compound Error
- **Symptoms:**
  1. When operators attempted to draft upcoming or ongoing shifts/usage with an end date in the future (e.g. today at 11:52 PM or tomorrow), the system failed with:
     ```
     ends_at: Record completed usage ending after its start, not in the future.
     ```
  2. The compound message conflated two distinct validation rules:
     - Chronological order: `ends_at <= starts_at`
     - Physical completion: `ends_at > now`
  3. Because both checks were evaluated together in `RunningChartValidation::validate()`, selecting a future end date that was chronologically after start resulted in an inaccurate and confusing error claiming the end date did not follow the start.
- **Root Causes:**
  1. **Premature Completion Gate:** `RunningChartValidation::validate()` treated initial draft entry as an already-completed historical trip. Ongoing rentals (`InCustody`) and scheduled rentals (`Planned`) legitimately need operators to log planned itineraries or draft shift blocks ending later in the day or during upcoming days.
  2. **Compound Error String:** Blending `$end <= $start` and `$end->greaterThan($now)` into a single exception message violated user feedback clarity and prevented actionable error resolution.

---

## 2. Solutions & Implementation

### A. Backend Validation Decoupling & Authority Separation
1. **`RunningChartValidation.php`:**
   - Decoupled chronological validation from completion checks.
   - For draft creation and modification, strictly enforces chronological ordering:
     - If `$end <= $start`, throws:
       ```php
       ['ends_at' => ['Usage end must follow usage start.']]
       ```
   - Removed premature future-time check from the input validation layer so draft usage periods can be planned or tracked ahead of real-time completion.

2. **`RunningChartService.php`:**
   - Injected `RentalCalendar` for tenant-aware timezone resolution.
   - **Coverage Assertions (`assertCoverage()`):**
     - Expanded valid statuses to include `VehicleUseStatus::Planned`, `VehicleUseStatus::InCustody`, and `VehicleUseStatus::Returned`.
     - For `InCustody`: ensures `$usageStart >= $use->handed_over_at` (with 1-minute drift tolerance).
     - For `Returned`: ensures `$usageEnd <= $use->returned_at` (with 1-minute drift tolerance) with message `"Usage must fall within actual customer custody."`.
     - For `Planned`: ensures `$usageStart >= $use->starts_at` with message `"Usage must fall within planned assignment period."`.
     - Enforces strict chronological order `$usageEnd <= $usageStart` with `"Usage end must follow usage start."`.
   - **Record Completion Workflow (`RunningChartAction::Finalize`):**
     - Established `Finalize` as the authoritative record completion boundary.
     - Rejects finalization on planned assignments: `"Hand over the vehicle before finalizing running charts."`.
     - Enforces physical completion using tenant calendar with 2-minute clock skew allowance:
       - If `$chart->starts_at->setTimezone($tz)->greaterThan($now)`: `"Completed usage cannot start in the future."`.
       - If `$chart->ends_at->setTimezone($tz)->greaterThan($now)`: `"Completed usage cannot end in the future."`.

### B. Frontend Workflow & UI Guidance
1. **`RunningChartEditor.tsx`:**
   - In `submit()`:
     - Enforces client-side chronological validation `$end <= $start` with `"Usage end must follow usage start."`.
     - Validates that usage falls within actual custody (`start >= custodyStart - 60s` and, for returned vehicles, `end <= custodyEnd + 60s`).
     - Allows draft saving for ongoing custody without artificial future-date blockers.
   - Updated helper text under `Usage end` input to `'Usage end must follow usage start.'` (when not returned) instead of claiming future dates are disallowed during draft input.

2. **`RunningChartsPanel.tsx`:**
   - Enabled `Record usage` action for `Planned`, `InCustody`, and `Returned` vehicle uses so operators can pre-draft usage or record post-custody logs.

---

## 3. Verification & Quality Gates

- **Vitest Frontend Tests:**
  - `npx vitest run resources/js/modules/vehicle-rental`: **29 passed (29 files, 136 tests)**.
- **Backend PHPUnit Tests:**
  - `php artisan test app/Modules/VehicleRental/Tests/RunningChartTest.php`: **19 passed (143 assertions)**.
  - `php artisan test app/Modules/VehicleRental/Tests`: **234 passed (1,981 assertions)**.
- **Type Checking & Linting:**
  - `npm run typecheck`: **0 errors**.
  - `npm run lint`: **0 errors**.
- **Production Asset Build:**
  - `npm run build`: **Built successfully (all assets compiled and bundled in 9.25s)**.
