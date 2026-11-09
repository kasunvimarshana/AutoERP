# Vehicle & Driver Assignment & Change CRUD Comprehensive Audit

**Date:** 2026-11-09  
**Module:** `VehicleRental`  
**Scope:** Vehicle Assignment & Reassignment (`VehicleUseService`, `VehicleUseEditor`, `VehicleUsePanel`), Driver Assignment & Reassignment (`RunningChartService`, `RunningChartValidation`, `RunningChartEditor`, `RunningChartsPanel`, `RunningChartReportService`, `running-chart-sheet.blade.php`), and Register Services (`VehicleUseRegisterService`, `RunningChartRegisterService`, `OwnerSourceService`).

---

## 1. Audit Scope & Summary of Issues Identified

An end-to-end audit of the vehicle assignment & change and driver assignment & change workflows was conducted across backend services, request validators, and frontend interactive panels:

1. **Search Length Discrepancies in Register & Source Queries**:
   - `VehicleUseRegisterService` and `RunningChartRegisterService` enforced `max:100` (`AgreementFields::REFERENCE_LENGTH`) on search query inputs, while the HTTP form requests (`VehicleUseRegisterRequest`, `RunningChartRegisterRequest`) permitted up to 255 characters. Searching by long customer names, supplier names, or references produced validation exceptions.
   - `OwnerSourceService` similarly capped search queries at 100 characters when looking up covering owner agreements and vehicles.

2. **Single-Driver Limitation on Multi-Trip/Multi-Chart Days**:
   - In `RunningChartReportService::generate()`, daily matrix aggregation extracted only `$firstChart->driver_name_snapshot ?? ''`. When multiple drivers operated a vehicle on the same calendar day (e.g., driver shift changeover, relief driver, morning/evening split), only the first driver appeared on the report.
   - In the printable running chart matrix view (`running-chart-sheet.blade.php`), the `Remark` column did not surface driver identity when distinct driver evidence was available.

3. **Running Chart Panel Driver Visibility**:
   - `RunningChartsPanel.tsx` displayed chart reference, time period, and distance for each running chart card, but omitted the assigned driver identity (`chart.driver`). Operators inspecting running charts could not see which driver was assigned to each chart or track driver reassignment across trips.

4. **Running Chart Editor Driver Details Accessibility**:
   - In `RunningChartEditor.tsx`, driver selection, air conditioning, and additional mileage inputs were placed inside a `<details>` disclosure element without an `open` state or error auto-expansion. When editing or correcting a chart with driver info, or when backend validation failed on a driver field, the details remained collapsed, obscuring the fields and validation messages.

5. **Operational Ergonomics & Custody Guardrails**:
   - `RunningChartEditor.tsx` lacked custody time boundaries (`min`/`max` constraints based on `handed_over_at` and `returned_at`), as well as shortcuts for "Use custody start time" and "Use current time".
   - `RunningChartEditor.tsx` start odometer did not prefill with the expected odometer reading from the previous chart or handover.
   - In `VehicleUseEditor.tsx`, the vehicle `LookupSelect` lacked `loadOnOpen` and `minSearchLength={0}`. In replacement mode, no shortcut existed to quickly populate the current time.
   - In `VehicleUsePanel.tsx`, the "Use current time" shortcut was present for handover but absent for vehicle return.

---

## 2. Changes Implemented

### Backend (`app/Modules/VehicleRental/Services/`)
1. **`VehicleUseRegisterService.php` & `RunningChartRegisterService.php`**:
   - Updated the `search` validation rule from `max:100` to `max:255` to align with HTTP request contracts and accommodate full customer/party names.
2. **`OwnerSourceService.php`**:
   - Updated the `search` validation rule to `max:255` for party and supplier vehicle lookups.
3. **`RunningChartReportService.php`**:
   - Updated daily driver name aggregation to extract and join all unique driver snapshots on active days:
     `$driverName = $dayCharts->pluck('driver_name_snapshot')->filter()->unique()->implode(', ');`

### Blade Print View (`resources/views/reports/vehicle-rental/running-chart-sheet.blade.php`)
- Enhanced the `Remark` column to visibly print driver name(s) alongside remarks when driver identity evidence is recorded on that date.

### Frontend (`resources/js/modules/vehicle-rental/`)
1. **`VehicleUseEditor.tsx`**:
   - Added `loadOnOpen minSearchLength={0}` to the Vehicle `LookupSelect` for immediate option visibility.
   - Added a "Use current time" button for replacement time in replacement mode.
2. **`VehicleUsePanel.tsx`**:
   - Added a "Use current time" shortcut button to the Return vehicle action dialog.
3. **`RunningChartsPanel.tsx`**:
   - Added driver name and reference display to running chart cards:
     `<p className="text-sm text-slate-700">Driver: {chart.driver ? `${chart.driver.name} · ${chart.driver.reference}` : 'Not recorded'}</p>`.
   - Extracted `initialOdometer` from the latest chart's end odometer (or handover odometer) and forwarded it to `RunningChartEditor`.
4. **`RunningChartEditor.tsx`**:
   - Added custody date constraints (`custodyStart`, `custodyEnd`) to usage start and end inputs with contextual hints.
   - Added shortcut buttons for "Use custody start time" and "Use current time".
   - Automatically prefilled `start_odometer` with `initialOdometer` when creating a new chart.
   - Implemented reactive `detailsOpen` management that automatically opens when driver or detail content is present or when validation errors occur on inner fields.

---

## 3. Verification

- **PHPUnit / Pest:**
  - `php artisan test app/Modules/VehicleRental` $\rightarrow$ **220 tests passed** (1,885 assertions) across 35 test suites.
- **Vitest:**
  - `npx vitest run resources/js/modules/vehicle-rental` $\rightarrow$ **28 suites passed** (124 tests passed).
- **TypeScript:**
  - `npm run typecheck` $\rightarrow$ **0 errors**.
- **ESLint:**
  - `npm run lint` $\rightarrow$ **0 warnings / 0 errors**.
