# 2026-11-20 Vehicle Rental Running Chart CRUD Audit & Overtime Baseline Review

## Context & Scope
Skeptical review, verification, and hardening of the VehicleRental module Running Chart CRUD operations, Owner & Customer Agreement workflows, configurable default working-hours baseline, and automatic overtime calculation with manual adjustment flexibility.

---

## Defects Identified & Fixed

### 1. Duplicate `working_hours` Input & Self-Drive Inconsistency in `AgreementEditor.tsx`
- **Input**: User opens `AgreementEditor` for Customer or Owner agreements with `driver_mode === 'with_driver'` or `self_drive`.
- **Expected**:
  - For with-driver agreements, a dedicated "Agreed daily working hours" baseline input appears under Driver arrangement.
  - The "Agreed rates and allowances" terms grid does not duplicate `working_hours`.
  - For self-drive agreements, `working_hours` is neither prompted nor submitted as a non-null term.
- **Actual**:
  - `visibleTermKeys(kind)` included `'working_hours'`, so `AgreementEditor` rendered `working_hours` twice when with-driver was selected (once in the dedicated card and once in the rates grid).
  - When self-drive was selected, `working_hours` appeared in the rates grid.
  - Switching from with-driver to self-drive preserved the `'8.0'` string and sent it to the backend.
- **Root Cause**: `AgreementEditor` did not filter out `working_hours` from `ratesTermKeys`, and did not clear or nullify `working_hours` upon switching to self-drive.
- **Fix**: Filtered `working_hours` out of `ratesTermKeys` in `AgreementEditor.tsx`, nullified `terms.working_hours` on switching to `self_drive`, and ensured `submittedTerms.working_hours` is `null` when `driver !== DriverMode.WithDriver`.

### 2. Poor UX / Nested Steps to Adjust Overtime in `RunningChartEditor.tsx`
- **Input**: User records or edits a running chart where calculated overtime is shown. User needs to manually adjust OT (e.g. deduct break time or traffic delay).
- **Expected**:
  - In the "Working Hours & Overtime Baseline" section, the user can directly view, adjust, and reset the Overtime minutes (with live hours/minutes format preview), with zero nested clicks or hunting through collapsed accordions.
- **Actual**:
  - The "Working Hours & Overtime Baseline" card only displayed "Recorded Normal OT" as a static non-editable `<span>`.
  - The editable `normal_ot_minutes` input was buried inside a collapsed `<details>` accordion titled "Additional usage observations", among 6 other distance and count fields.
  - The user had to scroll down, expand the accordion, find the field, and edit it there — violating the requirement for "simple workflows, minimal nested steps, clear UI/UX".
- **Root Cause**: The OT input was not integrated directly into the Overtime Baseline section.
- **Fix**: Moved `Normal OT (minutes)` input directly into the 4th stat box of the Overtime Baseline card with live human-readable hours preview (`formatMinutes`) and error reporting. Removed `normal_ot_minutes` from the generic count loop in "Additional usage observations" to eliminate duplicate labels.

### 3. Stale / Incorrect `isOtManuallyAdjusted` on Existing Running Charts
- **Input**: User opens an existing draft running chart where `normal_ot_minutes` had been automatically calculated previously (e.g. 120 mins for a 10-hour trip on an 8-hour baseline), and modifies the trip start or end time (e.g. changes duration to 9 hours).
- **Expected**:
  - Since the previous OT was NOT manually adjusted (it was calculated), changing start/end time should recalculate OT to 60 mins.
- **Actual**:
  - `isOtManuallyAdjusted` was initialized as `Boolean(chart && chart.normal_ot_minutes !== null && chart.normal_ot_minutes !== undefined)`.
  - For ANY existing chart with OT, `isOtManuallyAdjusted` was set to `true`.
  - Therefore, editing trip start or end times did NOT recalculate OT. The recorded OT stayed at 120 mins, and falsely displayed "✎ Manually adjusted (120 mins vs calculated 60 mins)".
- **Root Cause**: Initializer checked `Boolean(chart?.normal_ot_minutes)` rather than checking whether `chart.normal_ot_minutes !== initialCalculatedOt`.
- **Fix**: Initialized `isOtManuallyAdjusted` by comparing `chart.normal_ot_minutes !== initialCalculatedOt`. If they match, `isOtManuallyAdjusted` is `false`, allowing reactive recalculation when trip bounds change.

### 4. Text Artifact When Overtime is Redirected to Double OT
- **Input**: User clicks "Apply to Double OT" in `RunningChartEditor`.
- **Expected**:
  - Clearly displays that calculated OT was applied to Double OT.
- **Actual**:
  - Rendered `Manually adjusted ( mins vs calculated 120 mins)` with a missing value before `mins` because `values.normal_ot_minutes` was set to `''`.
- **Root Cause**: UI string formatting didn't handle `normal_ot_minutes === ''` when double OT was applied or when normal OT was 0/empty.
- **Fix**: Added explicit handling when `double_ot_minutes` is set and `normal_ot_minutes` is empty to display `Applied {double_ot_minutes} mins to Double OT (calculated OT was {calculatedOtMinutes} mins)`, opened the additional usage observations drawer so Double OT is visible, and safeguarded manual override string formatting.

### 5. `OvertimeCalculator.php` Dead Code and Non-Nullable Crash Risk
- **Input**: Calling `OvertimeCalculator::calculateOtMinutes` with `null`, empty, or invalid timestamp strings.
- **Expected**:
  - Safely returns `0` without uncaught exceptions or `TypeError` crashes.
  - The calculator is actively integrated into the backend resource layer (`RunningChartResource`, `RunningChartRegisterResource`) as the single source of truth for calculated overtime and agreed baseline hours.
- **Actual**:
  - Parameters `$startsAt` and `$endsAt` were non-nullable `CarbonImmutable|string`, throwing `TypeError` if null was passed.
  - Uncaught exception on malformed timestamp strings.
  - Calculator was never used in any application code, violating AGENTS.md rule on dead code.
- **Root Cause**: Lack of nullable type hints and exception handling in `OvertimeCalculator.php`, plus omission from API resources.
- **Fix**:
  - Updated `OvertimeCalculator::calculateOtMinutes(CarbonImmutable|string|null $startsAt = null, CarbonImmutable|string|null $endsAt = null, ...)` with `try / catch (\Throwable)` block.
  - Exposed `calculated_ot_minutes` and `agreed_baseline_hours` in `RunningChartResource` and `RunningChartRegisterResource`.

### 6. `RunningChartsPanel.tsx` Passing Wrong Agreement ID on "Print Owner Sheet"
- **Input**: User clicks "Print Owner Sheet" in `RunningChartsPanel.tsx` for an owner-supplied vehicle use.
- **Expected**:
  - Passes `use.owner_agreement.id` to `exportRunningChartReport`.
- **Actual**:
  - Hardcoded `agreement_id: use.customer_agreement.id`, causing backend to generate lessee report or throw error if owner agreement was expected.
- **Root Cause**: Incomplete copy/paste in `handlePrint` of `RunningChartsPanel.tsx`.
- **Fix**: Derived `agreementId = (mode === 'owner' && use.owner_agreement?.id) ? use.owner_agreement.id : use.customer_agreement.id` in `RunningChartsPanel.tsx`.

### 7. Incomplete Agreement Version Exposure in `RunningChartRegisterResource.php`
- **Input**: Fetching running chart rows with nested `customer_agreement` and `owner_agreement` from the register endpoint.
- **Expected**:
  - Both top-level and nested `customer_agreement` and `owner_agreement` expose `version` to comply with the TypeScript `AgreementReference` contract (`version: number`).
- **Actual**:
  - `version` was missing on `customer_agreement` and `owner_agreement` in `RunningChartRegisterResource`.
- **Fix**: Added `'version' => $use->customer_agreement_version` and `'version' => $use->owner_agreement_version` to both top-level and nested agreement mappings in `RunningChartRegisterResource.php`.

---

## Verification Record
- **Backend PHPUnit**:
  - `php artisan test app/Modules/VehicleRental/Tests/OvertimeCalculationTest.php`: 5 passed (51 assertions).
  - `php artisan test app/Modules/VehicleRental/Tests/`: 335 passed (2817 assertions), 0 failures.
- **Frontend Vitest**:
  - `npm run test -- resources/js/modules/vehicle-rental`: 38 test files passed (181 tests), 0 failures.
  - `npx vitest run resources/js/modules/vehicle-rental/AgreementEditor.test.tsx`: 5 passed (5 tests).
  - `npx vitest run resources/js/modules/vehicle-rental/RunningChartEditor.test.tsx`: 15 passed (15 tests).
  - `npx vitest run resources/js/modules/vehicle-rental/RunningChartsPanel.test.tsx`: 8 passed (8 tests).
- **TypeScript**:
  - `npm run typecheck` (`tsc --noEmit`): Clean exit code 0.
