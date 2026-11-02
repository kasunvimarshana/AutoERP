# Rental Running Chart Allocation Visibility & Handover Guidance

**Date:** 2026-10-10  
**Module:** Vehicle Rental (`Modules\VehicleRental`, `resources/js/modules/vehicle-rental`)  
**Context:** The user reported that rental allocations were not showing in the running chart page ("ental allocatio not showining in running chart").

---

## 1. Problem Diagnosis & Root Cause
1. **Hardcoded Status Filter**: In `resources/js/modules/vehicle-rental/pages/RentalRunningChartPage.tsx`, allocations were previously loaded using `listRentalAllocations({ status: "active", per_page: 100 })`.
2. **Missing Planned Allocations**: Newly created vehicle allocations start in `status: "planned"`. Because the query strictly requested `status: "active"`, planned allocations were never included in the loaded list.
3. **Silent Dropdown Failure**: When navigating from the allocation detail page (`/vehicle-rental/running-chart?allocation_id=<id>`), the page did not fetch the allocation by ID (`getRentalAllocation`), but tried to match it from the static `allocationRows` list. As a result, the `<Select>` dropdown rendered blank, `selectedAllocation` evaluated to `null`, and active company drivers were unavailable.
4. **Backend Invariant**: `RentalUsageService::assertAllocation` strictly requires that a daily running chart must be recorded against an `active` customer rental allocation where the vehicle has already been handed over. Attempting to record usage against a planned allocation results in an unhandled 422 error without actionable guidance.

---

## 2. Key Changes Made

### Frontend (`resources/js/modules/vehicle-rental/`)

1. **`RentalRunningChartPage.tsx`**:
   - Replaced static 100-item `<Select>` with `RentalAllocationLookupSelect` so operators can search and select any allocation by number or vehicle registration.
   - Synchronized URL search parameter `?allocation_id=...` with direct allocation loading via `getRentalAllocation(id, signal)`.
   - Added an **Allocation Details** summary panel (Status badge, Vehicle, Agreement, Start Odometer).
   - Added lifecycle-aware state handling:
     - **Planned Allocation**: Renders a prominent guidance banner informing the operator that the allocation is planned and requires vehicle handover, with a one-click **"Handover vehicle now"** button leading directly to `/vehicle-rental/custody?allocation_id=<id>`. The physical usage entry form is hidden.
     - **Active Allocation**: Displays the physical usage form, auto-populates valid company drivers from the allocation's driver assignments, and automatically pre-fills `start_odometer` using either the latest usage log's end odometer or the allocation's start odometer.
     - **Closed Allocation** (`returned`, `completed`, `replaced`): Displays an informational panel noting that vehicle custody has ended and no new entries can be made, while keeping historical running chart logs and facts accessible in read-only mode.
     - **Owner Supply Allocation**: Displays guidance indicating that running charts are recorded only for customer rental agreements.
   - Updated the usage logs table empty state to check `selectedAllocationId` rather than the old local variable.

2. **`RentalAllocationPage.tsx`**:
   - Added a quick **"Running chart"** link button for active allocations in the allocations data table.

3. **`vehicleRentalTypes.ts`**:
   - Enhanced `RentalAllocation.agreement` with `RentalAllocationAgreementSummary` typing (`agreement_number`, `agreement_kind`, `status`).

4. **`RentalRunningChartPage.test.tsx`**:
   - Added automated tests verifying:
     - Planned allocations display allocation details and handover guidance with link to custody handover.
     - Planned allocations hide the physical usage form.
     - Active allocations render the usage form, pre-fill start odometer, populate driver dropdown, and record usage logs.
     - Continuous odometer prefill from previous log's end odometer.
     - Closed allocations display completion notice without entry form.

---

## 3. Verification Results
- **PHPUnit / Pest**: 17 passed (202 assertions) (`php artisan test --filter=VehicleRental`).
- **Vitest**: 11 passed (4 test files across vehicle rental module).
- **TypeScript**: `npm run typecheck` passed with 0 errors.
- **Vite Build**: `npm run build` completed cleanly in 13.50s.
