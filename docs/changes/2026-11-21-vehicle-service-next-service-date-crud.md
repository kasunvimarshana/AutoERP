# VehicleService Next Service Date CRUD Integration

**Date:** 2026-11-21  
**Module:** Vehicle Service (`app/Modules/VehicleService`, `resources/js/modules/vehicle-service`)

## 1. Summary of Changes
Added an optional `next_service_date` field across the VehicleService module's CRUD operations (Create, Read/View, Update, and backend validation/persistence):
- **Database Schema**: Added canonical column `$table->date('next_service_date')->nullable();` directly into `2026_06_12_190001_create_vehicle_service_jobs_table.php`, maintaining single table per migration architectural standard with zero alteration patches.
- **Model & DTO**: Added `'next_service_date' => 'date'` cast to `VehicleServiceJob.php`, and added `nextServiceDate` and `nextServiceDateProvided` to `VehicleServiceJobData.php`.
- **Validation**: Added validation rule `'next_service_date' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:job_date']` to `StoreVehicleServiceJobRequest.php`.
- **Defaulting & Persistence Logic**: In `VehicleServiceJobService::attributes()`, if `next_service_date` is omitted upon creation, it automatically defaults to one calendar month after the service job date (`CarbonImmutable::parse($data->jobDate)->addMonthNoOverflow()->toDateString()`). If explicitly provided (including `null` when the user leaves it empty), the explicitly specified value is stored. On updates, users can freely modify the date or clear it (store `null`).
- **API Resource**: Exposed `next_service_date` (`$this->next_service_date?->toDateString()`) in `VehicleServiceJobResource.php`.
- **Frontend Contracts & Helper**: Updated `vehicleServiceTypes.ts` (`VehicleServiceJob` and `VehicleServiceJobPayload`) to include optional/nullable `next_service_date`. Implemented civil date helper `defaultNextServiceDate(jobDate)` that safely calculates +1 calendar month with day clamping for leap years and shorter months without timezone skew.
- **Frontend Create / Edit / View**:
  - `VehicleServiceJobForm.tsx`: Automatically initializes `next_service_date` to 1 month after `job_date` on create; tracks job date changes to update the default if untouched; allows user modification or clearing; submits `null` when left empty; loads existing `next_service_date` in edit mode.
  - `VehicleServiceSummaryPanel.tsx`: Displays formatted `Next service date` via `formatDate()` alongside existing job metadata.

## 2. Verification
- **PHP Tests**: Added 3 new tests in `VehicleServiceEngineTest.php`:
  - `test_next_service_date_defaults_to_one_month_after_job_date_when_omitted`
  - `test_next_service_date_can_be_customized_or_explicitly_left_empty_and_updated`
  - `test_next_service_date_validation_via_http_endpoint`
  - All 91 VehicleService backend tests passed (705 assertions).
  - `ModuleMigrationBaselineTest` passed with 0 errors.
  - Laravel Pint code style passed with 0 issues on all modified files.
- **TypeScript / React Tests**:
  - Added unit and integration tests in `VehicleServiceJobForm.test.tsx` verifying helper date clamping across month boundaries and leap years, auto-defaulting, user editing, user clearing, edit pre-population, and summary panel rendering.
  - All 18 frontend test suites in `resources/js/modules/vehicle-service` passed (95 tests).
  - `npm run typecheck` passed with 0 errors.
  - `npm run lint` passed with 0 errors on modified files.
