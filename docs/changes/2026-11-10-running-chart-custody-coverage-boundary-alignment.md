# Vehicle Rental: Running Chart Custody Coverage Boundary & Sub-Minute Alignment

**Date:** 2026-11-10  
**Modules:** `VehicleRental`  
**Scope:** Running Chart Custody Period Validation: Boundary sub-minute alignment, seconds preservation in UI, and actual customer custody verification.

---

## 1. Problem Statement & Root Cause Analysis

Operators attempting to record a running chart starting at the handover time (e.g. `11/09/2026 11:51:00 PM`) after clicking "Use custody start time" or entering the handover minute received a validation error:
`period: Usage must fall within actual customer custody.`

### Identified Root Causes:
1. **Frontend Seconds Stripping in Custody Shortcuts (`RunningChartEditor.tsx`)**:
   - `custodyStart` performed `.slice(0, 16)` on `localTimestampValue(use.handed_over_at)`.
   - When the actual handover had non-zero seconds (e.g. `Nov 9, 2026, 11:51:38 PM` / `2026-11-09 18:21:38 UTC`), `.slice(0, 16)` truncated `:38`, leaving `2026-11-09T23:51`.
   - Submitting `timestampWithOffset("2026-11-09T23:51")` attached `:00` seconds (`11:51:00 PM` / `18:21:00 UTC`).
2. **Strict Second-Level String Comparison in `RunningChartService::assertCoverage`**:
   - The server compared `$start < $use->handed_over_at->format('Y-m-d H:i:s')`.
   - Because `18:21:00 < 18:21:38` evaluated to true by 38 seconds within the identical minute, the submission was rejected as falling outside custody.
   - Operators typing `11:51 PM` into a standard datetime picker (which defaults seconds to `:00`) were similarly trapped, even though the vehicle was handed over at 11:51 PM.

---

## 2. Changes Implemented

1. **`app/Modules/VehicleRental/Services/RunningChartService.php` (`assertCoverage`)**:
   - Converted handover, return, usage start, and usage end to `CarbonImmutable` UTC instances for robust temporal boundary comparison.
   - When `$usageStart < $handover` but falls within 1 minute before handover (e.g. operator keyed the handover minute or seconds were zeroed), `$start` is gracefully aligned to the exact handover boundary (`$use->handed_over_at`).
   - If `$usageStart` precedes handover by more than 1 minute, it is rejected with `Usage must fall within actual customer custody.`.
   - When `$use->returned_at` is present, `$usageEnd` within 1 minute after return is similarly aligned to the return boundary; usage ending more than 1 minute after return is rejected.
   - Verified that after boundary alignment, `$usageEnd` must still be strictly greater than `$usageStart`.
2. **`resources/js/shared/utils/businessDate.ts`**:
   - Added `formatBusinessDateTimeWithSeconds` using `timeStyle: 'medium'` to display exact commercial seconds in operational UI hints.
3. **`resources/js/modules/vehicle-rental/RunningChartEditor.tsx`**:
   - Preserved full timestamp precision in `custodyStart` and `custodyEnd` without slicing off seconds.
   - Set `min` on `Usage start` to the beginning of the handover minute (`custodyStart.slice(0, 16) + ':00'`), allowing operators to select any second in that minute while preventing entries prior to the handover minute.
   - Updated the hint to display exact seconds (`Vehicle in custody since: Nov 9, 2026, 11:51:38 PM`).
   - Clicking "Use custody start time" and "Use custody return time" populates the exact boundary timestamp.
4. **Tests**:
   - `RunningChartTest.php`: Added `test_usage_coverage_accepts_sub_minute_handover_alignment_and_rejects_prior_period` covering sub-minute handover alignment, strict rejection of usage prior to handover, return skew alignment, and rejection of usage after return.
   - `RunningChartEditor.test.tsx`: Verified custody shortcut buttons set full second precision and input bounds.

---

## 3. Verification

- **PHPUnit Backend Tests**:
  - `Modules\VehicleRental\Tests`: **230 passed (1,945 assertions)** with 0 failures.
- **Frontend Vitest Tests**:
  - `resources/js/modules/vehicle-rental`: **29 test files, 130 passed tests** with 0 failures.
- **Static Analysis & Linting**:
  - `npm run typecheck`: **0 errors**.
  - `npm run lint`: **0 errors, 0 warnings**.
