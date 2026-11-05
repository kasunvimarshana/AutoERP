# Vehicle Rental: Running Chart CRUD, Parity, and Optional Fields Forensic Audit

**Date:** 2026-11-05  
**Module:** `VehicleRental`  
**Scope:** Backend (`app/Modules/VehicleRental/`), Frontend (`resources/js/modules/vehicle-rental/`), Tests

## Context & Purpose
A comprehensive, end-to-end audit was conducted across the Running Chart (`RunningChart`) CRUD lifecycle, covering Create (Record), Read (List / Show / Register / History / Matrix / Print), Update (Edit draft, Finalize, Reverse, Correct transitions), and Delete (strictly prohibited hard delete to protect physical evidence integrity).

Key issues identified and corrected:
1. **Pre-Validation Handling of Optional Fields and Empty Strings**:
   - When optional fields (`ac_mode`, `driver_identity_source`, `driver_employee_id`, `start_odometer`, `end_odometer`, `garage_km`, `commercial_km`, `normal_ot_minutes`, `double_ot_minutes`, `triple_ot_minutes`, `night_outs`, `driver_name_snapshot`, `driver_reference_snapshot`, `driver_observation`, `notes`) were sent as empty strings `""` or whitespace:
     - `Rule::enum(AirConditioningMode::class)` threw 422 validation errors because `AirConditioningMode::tryFrom("")` is `null`.
     - `Rule::enum(DriverIdentitySource::class)` threw 422 validation errors similarly.
     - `AgreementFields::DECIMAL_PATTERN` regex threw 422 on `""` distance inputs.
     - Integer fields failed validation or, if coerced, risked converting blanks to `0`.
   - `RunningChartValidation::validate()` now pre-normalizes all mutable optional fields so that empty or whitespace strings are converted to `null` before validation rules execute.
   - Distances submitted as integers or floats are cleanly cast to string for regex validation while remaining `null` when omitted or blank.
   - Preserved distinction between `null` (unknown/unrecorded observation) and `0.000000` (verified zero quantity).

2. **Draft Update Reference Preservation and Partial Update Support**:
   - In `RunningChartService::change()`, updating a draft chart previously required `reference` in the input payload. If omitted or sent empty, validation failed.
   - `change()` now merges incoming draft updates with existing draft attributes so omitted fields (such as `starts_at`, `ends_at`, or `reference`) retain their existing values, while explicitly cleared optional fields update to `null`.
   - Added explicit `RunningChartService::delete()` method throwing `LogicException('Preserve Running Chart evidence. Use reversal.')`.

3. **Running Chart Register Filter Normalization & Search Expansion**:
   - In `RunningChartRegisterService::list()`, filters `from`, `until`, `chart_status`, and `search` sent as `""` (empty strings) caused `OperationalTime::parse()` to throw 422 errors because `"" !== null`.
   - Pre-normalized filter strings in `RunningChartRegisterService::list()` so that empty strings are converted to `null` before validation and before time parsing.
   - Expanded search query to match `notes` and `driver_observation` in addition to chart reference, vehicle registration, driver snapshot, and customer/owner agreement references.

4. **Actor Null-Safety in Running Chart History**:
   - In `RunningChartHistoryResource`, `$this->actor->first_name` lacked null-safety. If an actor record was missing, deleted, or generated via system automation, history deserialization produced a 500 error.
   - Made actor name resolution null-safe: `$this->actor ? trim(($this->actor->first_name ?? '').' '.($this->actor->last_name ?? '')) : 'System'`.

5. **Hard Delete Endpoint Handling**:
   - Registered `DELETE /api/v1/vehicle-rental/running-charts/{chart}` and `DELETE /api/v1/vehicle-rental/vehicle-uses/{use}/running-charts/{chart}` in routes and added `RunningChartController::destroy()`.
   - Returns HTTP 405 Method Not Allowed explaining that Running Charts represent physical legal and operational evidence and cannot be deleted, instructing users to use reversal instead.
   - Added `deleteChart(chartId)` to `runningChartApi.ts`.

6. **Frontend Ergonomics & Form Normalization**:
   - In `RunningChartEditor.tsx`, helper parsers ensure distance and numeric count strings are trimmed, converting empty or whitespace-only inputs to `null` before sending to the API.
   - In `RunningChartsPanel.tsx`, trimmed reversal reason strings on submission.
   - In `RunningChartRegisterPage.tsx`, added `syncUrl()` using `window.history.replaceState()` on filter application and filter clearing to synchronize browser URL parameters seamlessly.

## Changes Made

### Backend
1. **`app/Modules/VehicleRental/Services/RunningChartValidation.php`**:
   - Pre-normalized all optional mutable fields (`RunningChartFields::MUTABLE` except `starts_at` and `ends_at`) converting whitespace-only strings and empty strings to `null`.
   - Normalized integer/float distance inputs to strings for regex validation without altering `null` semantics.
   - Preserved `integer` validation rule behavior for counts without prematurely casting floats to ints.
   - Trimmed text snapshots (`driver_name_snapshot`, `driver_reference_snapshot`, `driver_observation`, `notes`) and set empty strings to `null`.
2. **`app/Modules/VehicleRental/Services/RunningChartService.php`**:
   - In `change()`, for `Update` action on `Draft` charts, merged incoming payload with existing chart attributes so omitted fields (such as `starts_at`, `ends_at`, and `reference`) are safely preserved.
   - Added `delete()` method throwing `LogicException('Preserve Running Chart evidence. Use reversal.')`.
3. **`app/Modules/VehicleRental/Services/RunningChartRegisterService.php`**:
   - Normalized empty string inputs for `from`, `until`, `chart_status`, and `search` to `null` prior to validation and `OperationalTime::parse()`.
   - Added search clauses for `notes` and `driver_observation`.
4. **`app/Modules/VehicleRental/Http/Resources/RunningChartHistoryResource.php`**:
   - Made actor name resolution null-safe: `trim(($this->actor->first_name ?? '').' '.($this->actor->last_name ?? '')) : 'System'`.
5. **`app/Modules/VehicleRental/Http/Controllers/RunningChartController.php`**:
   - Added `destroy(AgreementRequest $request, ?int $first = null, ?int $second = null): JsonResponse` throwing `MethodNotAllowedHttpException` explaining why Running Charts cannot be deleted.
6. **`app/Modules/VehicleRental/Routes/api.php`**:
   - Added DELETE routes for running charts.
7. **`app/Modules/VehicleRental/Tests/RunningChartTest.php`**:
   - Added `test_optional_fields_can_be_omitted_or_passed_as_empty_strings`.
   - Added `test_draft_update_without_reference_retains_existing_reference`.
   - Added `test_running_chart_destroy_endpoint_returns_405_method_not_allowed`.
8. **`app/Modules/VehicleRental/Tests/RunningChartRegisterTest.php`**:
   - Added `test_register_filters_accept_empty_strings_without_validation_error`.
   - Added `test_register_searches_notes_and_driver_observation`.

### Frontend
1. **`resources/js/modules/vehicle-rental/RunningChartEditor.tsx`**:
   - Implemented `parseDistance()` and `parseCount()` helpers to trim strings and send `null` when blank.
   - Trimmed external driver snapshots, driver observation, and notes before submitting.
2. **`resources/js/modules/vehicle-rental/RunningChartsPanel.tsx`**:
   - Trimmed reversal reason on submission.
3. **`resources/js/modules/vehicle-rental/RunningChartRegisterPage.tsx`**:
   - Added `syncUrl()` with `window.history.replaceState()` on filter apply and clear.
4. **`resources/js/modules/vehicle-rental/runningChartApi.ts`**:
   - Added `deleteChart()` function.
5. **`resources/js/modules/vehicle-rental/RunningChartEditor.test.tsx`**:
   - Added test verifying null values for blank optional fields upon chart creation.
6. **`resources/js/modules/vehicle-rental/RunningChartRegisterPage.test.tsx`**:
   - Added test verifying URL query parameter synchronization when filters are applied and cleared.

## Verification
- **PHPUnit Tests**: `php artisan test --filter=RunningChart` $\rightarrow$ 24 passed (218 assertions).
- **Module PHPUnit Suite**: `php artisan test --filter=VehicleRental` $\rightarrow$ 206 passed (1785 assertions).
- **Frontend Vitest Suite**: `npx vitest run resources/js/modules/vehicle-rental` $\rightarrow$ 28 test files passed (121 tests).
- **TypeScript Typecheck**: `npx tsc --noEmit` $\rightarrow$ 0 errors.
- **ESLint**: `npm run lint` $\rightarrow$ 0 errors, 0 warnings.
- **Production Build**: `npm run build` $\rightarrow$ Built in 11.20s successfully.
