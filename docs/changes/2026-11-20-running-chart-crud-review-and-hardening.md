# 2026-11-20 Running Chart CRUD Skeptical Review, Hardening, and Streamlining

## Context & Scope
Review and hardening of the complete Running Chart and Vehicle Rental CRUD operations following the prior attempt. Evaluation of customer vs owner running chart management, eliminating operational bottlenecks, ensuring timezone causality, and hardening edge cases across backend services and frontend interfaces.

## Defects Identified & Fixed

### 1. Running Chart Report on Replaced Vehicles Threw 422 in Owner Sheet Mode
- **Input**: Generating or printing an owner running chart sheet (`sheet_mode=owner`) for a customer agreement where the active trip is on a replacement vehicle (`vehicle_use_id` provided).
- **Expected**: Resolves the replacement vehicle use, finds its owner agreement, populates the replacement vehicle's registration number and lessor details in the header, and generates the sheet.
- **Actual**: `RunningChartReportService` ignored `vehicle_use_id` when determining `$primaryUse`, defaulting to `firstWhere('replaces_use_id', null)`. If the initial vehicle was company-owned, `$ownerAgreement` was null, throwing a `422 Unprocessable Entity` ("Selected agreement does not have an owner-supplied vehicle"), and for lessee mode displayed the original vehicle's registration number instead of the replacement vehicle.
- **Root Cause**: `RunningChartReportService::generate()` did not check `firstWhere('id', $selectedUseId)` for `$primaryUse`.
- **Fix**: Updated `RunningChartReportService.php` to resolve `$primaryUse` from `$selectedUseId` when specified, eager-loaded `ownerAgreement.party` on owner uses, and exposed `vehicle_use_id` in the report header.

### 2. Concurrency Version Mismatch in `RunningChartRegisterResource`
- **Input**: Fetching `vehicle_use` from `RunningChartRegisterResource` after the vehicle use had transitioned (e.g. returned).
- **Expected**: `vehicle_use.row_version` reflects the current version of the vehicle use row so subsequent actions use the correct version.
- **Actual**: `RunningChartRegisterResource` mapped `'row_version' => $this->vehicle_use_version`, which was the historical snapshot stored on the running chart when the chart was created.
- **Root Cause**: Field mapping conflated `$use->row_version` with `$this->vehicle_use_version`.
- **Fix**: Updated `RunningChartRegisterResource.php` to map `row_version` and `version` to `$use->row_version` while retaining `chart_use_version` for the historical snapshot.

### 3. Quick Vehicle Selection Bottlenecks in `RunningChartRegisterPage`
- **Input**: Dispatcher logging running charts for recently returned vehicles, planned dispatches, or locating a specific vehicle among many active dispatches.
- **Expected**: Dispatcher can filter by dispatch status (`in_custody`, `returned`, `planned`, or all) and search by vehicle registration number, customer name, or agreement reference, with starting odometer, start time, and driver pre-filled from the previous trip.
- **Actual**: Modal was hardcoded to page 1 of `InCustody` vehicles without search or status filters, completely blocking entry for recently returned vehicles from the register, and did not pre-fill odometer or driver data.
- **Root Cause**: Minimal modal implementation in prior attempt lacked dynamic filtering and did not fetch recent chart context for pre-fill.
- **Fix**: Added dynamic status selector and search input calling `listVehicleUseRegister`, and updated `handleProceedWithVehicle` to fetch recent chart data via `listCharts()` to pre-fill starting odometer, start time, and driver identity in `RunningChartEditor`.

### 4. Owner Agreement Search in `RentalRunningChartReportPage`
- **Input**: User selecting "Owner Sheet" mode to generate a run sheet directly from an Owner Agreement.
- **Expected**: User can select and search Owner Agreements via `searchOwnerAgreements`.
- **Actual**: Form only offered `searchCustomerAgreements` with a fixed "Customer Agreement" label.
- **Root Cause**: UI did not support switching agreement kind when `sheet_mode === 'owner'`.
- **Fix**: Added "Agreement Source" selector (`Owner Agreement (Direct)` vs `Customer Agreement (Lessee contract)`) when `sheetMode === 'owner'`, dynamically switching the `LookupSelect` to `searchOwnerAgreements`.

### 5. Timezone-Unsafe String Slicing in Print Handlers
- **Input**: Running charts or vehicle uses with UTC timestamps crossing local midnight boundaries (e.g. UTC 21:00 = 02:30 Colombo).
- **Expected**: Print query dates use the tenant's business calendar date (`YYYY-MM-DD`).
- **Actual**: Used raw `.slice(0, 10)` on ISO strings, resulting in dates one day behind the local business date.
- **Root Cause**: Bypassed business timezone utility functions.
- **Fix**: Switched to `businessDateInputValue(new Date(...))` in `RunningChartRegisterPage.tsx` and `RunningChartsPanel.tsx`.

### 6. Stale Drawer State on Page/Filter Change in `VehicleUseRegisterPage`
- **Input**: User opens running charts drawer on a vehicle use, then applies a filter or changes pagination page.
- **Expected**: Drawer state resets to prevent displaying mismatched vehicle charts.
- **Actual**: `activeChartsUseId` remained set across page and filter changes.
- **Fix**: Added `setActiveChartsUseId(null)` to filter apply, clear, and page change handlers.

## Verification
- **Backend Tests**: `php artisan test app/Modules/VehicleRental/Tests/` — 330 passed (2,765 assertions), 0 failures.
  - Added `test_running_chart_report_with_replacement_vehicle_and_owner_sheet()` in `RunningChartReportTest.php`.
  - Added assertions for `row_version` and `chart_use_version` in `RunningChartRegisterTest.php`.
- **Frontend Tests**: `npm run test -- resources/js/modules/vehicle-rental` — 38 passed (175 tests), 0 failures.
  - Added component tests for status filtering, plate search, and pre-filled starting odometer in `RunningChartRegisterPage.test.tsx`.
- **TypeScript**: `npm run typecheck` (`tsc --noEmit`) — Clean exit code 0.
