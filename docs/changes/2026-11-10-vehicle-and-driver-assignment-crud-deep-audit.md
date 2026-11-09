# Vehicle & Driver Assignment: Deep Audit, CRUD Architecture & Operational Integrity

**Date:** 2026-11-10  
**Modules:** `VehicleRental`  
**Scope:** In-depth end-to-end audit, architectural validation, and ergonomics alignment of Vehicle Assignment & Change (Reassignment) and Driver Assignment & Change (Reassignment) across backend services, controllers, form editors, panels, and registers.

---

## 1. Architectural Understanding & Business Logic Foundations

### A. Vehicle Assignment & Change (Reassignment) CRUD

1. **Create (Planning & Allocation):**
   - **Contract Linkage:** Vehicle assignment links a physical vehicle from the fleet to an active Customer Agreement (`POST customer/agreements/{agreement}/vehicles`).
   - **Supply Source:** Every vehicle assignment requires proven supply coverage:
     - External Lessor: Covered by an active `OwnerAgreement` spanning the vehicle usage period (`starts_at` to `ends_at`). Auto-selection queries `OwnerSourceService` to find and pre-select matching supplier agreements.
     - Internal Fleet: Toggled as `company_owned`, validated against `CompanyVehicleCoverageService`.
   - **Collision Detection:** Planned usage periods are half-open intervals `[starts_at, ends_at)`. `VehicleUseAvailabilityBlocker` checks for collisions against active custody, planned assignments, workshop maintenance blocks, and confirmed reservations across the entire tenant workspace.
   - **Open-Ended Support:** Assignments may omit `ends_at`, which defaults planning coverage to the Customer Agreement's boundary.

2. **Read (Custody State, Traceability & History):**
   - **Contextual Visualization:** Assigned vehicles are queried via `GET customer/agreements/{agreement}/vehicles` with human-readable plates, vehicle model, assigned supplier, and lifecycle status badges (`Planned`, `InCustody`, `Returned`, `Cancelled`).
   - **Full Audit Register:** Global register `GET vehicle-uses` provides tenant-wide visibility, multi-attribute searching (plate, customer, notes), date filtering, and status filtering.
   - **Point-in-Time History:** Every lifecycle transition is recorded in `vehicle_use_histories` and inspectable via `VehicleUseHistoryPanel.tsx` (`GET vehicle-uses/{use}/history`).

3. **Update & Reassignment (Atomic Transitions & Vehicle Replacement):**
   - **Physical Custody Transitions:**
     - `handover`: Requires `occurred_at` (actual handover timestamp within agreement boundary) and `odometer` reading, moving status from `Planned` to `InCustody`.
     - `return`: Requires `occurred_at` ($\ge$ `handed_over_at` and not in the future) and `odometer` ($\ge$ `handover_odometer`), moving status from `InCustody` to `Returned`.
     - `cancel`: Allowed only for `Planned` assignments with a mandatory audit reason.
   - **Reassignment / Replacement Workflow:**
     - Addresses breakdowns, maintenance swaps, or vehicle upgrades during an ongoing rental.
     - Handled via `POST vehicle-uses/{use}/replace` in a single atomic database transaction:
       1. The current vehicle assignment is returned at `replacement_time` with `old_odometer`.
       2. The replacement vehicle assignment is created with `replaces_use_id` pointing to the predecessor.
       3. The replacement vehicle is handed over at `replacement_time` with `new_odometer`.
     - If supply coverage or availability checks fail for the replacement vehicle, the entire transaction rolls back, leaving the original assignment intact.

4. **Delete (Strict Immutability):**
   - Deletion of `vehicle_uses` is strictly prohibited (`MethodNotAllowedHttpException` / `LogicException`) to preserve legal custody chains and billing auditability. Operators must cancel planned uses or record returns for active vehicles.

---

### B. Driver Assignment & Change (Reassignment) CRUD

1. **Create (Trip Assignment & Running Charts):**
   - **Trip Linkage:** Drivers are assigned per physical trip (`RunningChart`) under an active `VehicleUse` (`POST vehicle-uses/{use}/running-charts`).
   - **Driver Identities:**
     - `Employee`: Selected from HR staff via `DriverDirectory` / `EmployeeQueryService`. Server-side validation captures authoritative employee code and name snapshots. A timeline concurrency check ensures that an employee driver cannot be finalized on two overlapping vehicle trips simultaneously.
     - `External`: For client-appointed or outsourced drivers, requiring a normalized name and stable identifier (NIC/license).
   - **Smart Defaults:** In `RunningChartsPanel.tsx`, new trip forms inherit the previous trip's driver (`initialDriver`) and end odometer (`initialOdometer`) for rapid data entry.

2. **Read (Trip Records, Daily Matrix & Multi-Driver Aggregation):**
   - Displayed in `RunningChartsPanel.tsx` with start/end odometers, mileage, and driver details.
   - Global view available in `RunningChartRegisterPage.tsx`.
   - Printable daily matrix sheet generated via `running-chart-sheet.blade.php`.
   - `RunningChartReportService.php` calculates multi-driver mileage splits, normal/overtime hours, night out allowances, and fuel consumption.

3. **Update & Reassignment (Draft Amendments vs. Finalized Corrections):**
   - **Draft State:** Operators can freely reassign drivers, correct odometers, or adjust trip timestamps via `RunningChartEditor.tsx`.
   - **Finalized State:** Finalized running charts represent legal evidence. If driver reassignment is required post-finalization:
     - The operator performs a reversal via `RunningChartAction::Reverse` with an audit reason.
     - A corrective running chart is created with `corrects_chart_id` referencing the original trip.

4. **Delete (Integrity Guarantee):**
   - Hard deletion is strictly forbidden via `RunningChartController` and `RunningChartService` to maintain complete trip and financial audit integrity.

---

## 2. Changes Implemented

1. **`resources/js/modules/vehicle-rental/VehicleUseEditor.tsx`**:
   - Converted `replacement.handed_over_at` to dynamic workspace local timestamp (`replacementHandoverLocal`) via `localTimestampValue()` rather than slicing raw strings.
   - Adjusted `minStart` to minute precision (`.slice(0, 16)`) to ensure standard HTML5 datetime picker boundary compatibility while preserving second precision in form state.

2. **`resources/js/modules/vehicle-rental/VehicleUsePanel.tsx`**:
   - Standardized `actionHandoverLocal`, `actionPlannedStartLocal`, and `actionPlannedEndLocal` using dynamic `localTimestampValue()`.
   - Updated `min` and `max` constraints to use `.slice(0, 16)` minute-level attributes, guaranteeing error-free interaction across all browsers and timezones.
   - Replaced raw string slices with workspace-aware local timestamps in the "Use planned handover time" action button.

---

## 3. Verification & Test Metrics

- **Vitest Frontend Suites:**
  - `npx vitest run resources/js/modules/vehicle-rental`: **29 passed (29 files, 131 tests)**.
- **Type Checking & Linting:**
  - `npm run typecheck`: **0 errors**.
  - `npm run lint`: **0 errors**.
- **Backend PHPUnit Suites:**
  - `php artisan test app/Modules/VehicleRental/Tests`: **230 passed (1,945 assertions)**.
