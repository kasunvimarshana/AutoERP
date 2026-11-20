# 2026-11-20 Running Chart CRUD Streamlining and Owner-Customer Dual Interface Architecture

## Context & Motivation
The operational workflow for Vehicle Rental running charts previously suffered from severe navigation friction and friction in managing owner-supplied vehicles:
1. Operators had to navigate through deep, nested hierarchies (Agreements -> Agreement Review -> Vehicles Tab -> Vehicle Row -> Running Charts Modal/Drawer -> Action Forms) just to log a daily trip or finalize a draft chart.
2. In the global Running Chart Register (`/vehicle-rental/running-charts`), records were strictly read-only, forcing users to leave the register, locate the agreement and vehicle custody record, and perform edits elsewhere.
3. Owner agreements lacked a direct view of vehicles supplied under them and their running charts, leaving owner operators blind to daily physical operations without switching to customer contracts.
4. Evaluation was needed on whether Customer and Owner running charts should be managed separately or in a unified interface.

## Architectural Decision: Physical Fact Unification vs Commercial Settlement Separation
In vehicle rental operations involving owner-supplied (sub-leased) vehicles, a physical trip is an indivisible physical reality:
- **Physical Trip Layer (Unified Fact)**: An actual physical trip undertaken by a vehicle has one start/end time, one start/end odometer reading, one driver, and specific AC/fuel operational conditions. If Customer running charts and Owner running charts were managed separately, operators would be forced to duplicate data entry for every trip across two separate screens, virtually guaranteeing human sync errors, conflicting odometer readings, and violated DRY principles. Therefore, physical running chart recording, draft edits, finalization, corrections, and reversal are managed in a **single, unified interface and dataset**.
- **Commercial Settlement & Representation Layer (Clear Separation)**: Billing rates, allowances, overtime rules, payable rates, and monthly invoice statements differ between the Customer (Accounts Receivable) and the Owner (Accounts Payable). These are cleanly separated:
  - Printable run sheets allow separate rendering for Customer (`sheet_mode=customer`) and Owner (`sheet_mode=owner`).
  - Monthly usage charges and financial settlements are segregated in `UsageChargePanel` (separate tabs for Customer usage charges vs Owner payables).
  - Register filtering provides a first-class `kind` filter (`All supplies`, `Company supply only`, `Owner-supplied only`) as well as direct filtering by `customer_agreement_id` and `owner_agreement_id`.
  - Owner Agreements now feature a dedicated "Supplied Vehicles & Usage" section showing all vehicle uses supplied under that owner agreement, displaying customer context, and linking directly to their running charts.

## Changes Implemented

### 1. Backend Enhancements
- **Owner Agreement Vehicle Uses Endpoint**:
  - `app/Modules/VehicleRental/Services/VehicleUseService.php`: Added `listOwner(AgreementContext $context, int $ownerAgreement, int $perPage)` to fetch vehicle uses supplied under an owner agreement.
  - `app/Modules/VehicleRental/Http/Controllers/VehicleUseController.php`: Updated `index` and added `ownerUses(AgreementRequest $request, int $agreement)` mapping to `VehicleUseService::listOwner`.
  - `app/Modules/VehicleRental/Routes/api.php`: Bound `GET owner/agreements/{agreement}/vehicles` and `GET {kind}/agreements/{agreement}/vehicles`.
- **Running Chart Register Advanced Filters & Enriched Resources**:
  - `app/Modules/VehicleRental/Http/Requests/RunningChartRegisterRequest.php`: Added validation and normalization for `customer_agreement_id`, `owner_agreement_id`, `vehicle_use_id`, and `kind` (`customer` vs `owner`).
  - `app/Modules/VehicleRental/Http/Controllers/RunningChartController.php`: Passed all filter criteria directly to `RunningChartRegisterService::list()`.
  - `app/Modules/VehicleRental/Services/RunningChartRegisterService.php`: Filtered register queries by `customer_agreement_id`, `owner_agreement_id`, `vehicle_use_id`, and `kind` (owner-supplied vs company-owned/customer-direct).
  - `app/Modules/VehicleRental/Http/Resources/RunningChartRegisterResource.php`: Enriched `vehicle_use` data structure to return complete `VehicleUse` properties (`row_version`, `status`, `handed_over_at`, `returned_at`, `customer_agreement`, `owner_agreement`, `vehicle`), eliminating N+1 roundtrips from the frontend.

### 2. Frontend Streamlining & Workflow Reductions
- **Running Chart Register Direct CRUD Operations (`RunningChartRegisterPage.tsx`)**:
  - Elevated the register from a read-only list into an operational center.
  - Added a "Record Running Chart" modal with a quick in-custody vehicle selector so dispatchers can log charts immediately without navigating through agreements.
  - Added direct action controls on each running chart row: "Edit draft", "Finalize usage", "Reverse usage", "Create correction", and "Reassign driver".
  - Added direct printable run sheet actions ("Print Sheet" for Customer, "Print Owner Sheet" for Owner).
  - Added Commercial Supply filter (`All supplies`, `Company supply only`, `Owner-supplied only`).
- **Vehicle Use Panel & Register Upgrades (`VehicleUsePanel.tsx`, `VehicleUseRegisterPage.tsx`)**:
  - Added `kind` support to `VehicleUsePanel`: Owner agreement reviews show "Supplied vehicles & usage" with customer details, omit customer-specific "Assign vehicle", and allow direct running chart management.
  - Added an inline "Running Charts" toggle button on every row in `VehicleUseRegisterPage`, giving operators 1-click access to running charts without navigating into an agreement.
- **Running Charts Panel Dual Transparency (`RunningChartsPanel.tsx`)**:
  - Added commercial context banner showing both Customer Agreement and Owner Agreement (or Company Supply).
  - Added expandable "Chart details" grid showing odometer breakdown, garage and commercial distances, overtime hours, night-outs, AC mode, and notes.
- **Client APIs & Type Definitions**:
  - `resources/js/modules/vehicle-rental/vehicleUseApi.ts`: Updated `listVehicleUses` to support `AgreementKind.Owner`.
  - `resources/js/modules/vehicle-rental/runningCharts.ts`: Updated `ChartRegisterRow` and `ChartRegisterFilters` with complete agreement context and filters.

## Verification
- **Backend Test Suite**:
  - `php artisan test app/Modules/VehicleRental/Tests/`: 329 passed (2,745 assertions), 0 failures.
  - Included `RunningChartRegisterTest` and new test cases in `VehicleRentalCrudAuditAndEdgeCasesTest` covering owner agreement vehicles and register filtering by kind and agreement.
- **Frontend Test Suite**:
  - `npm run test -- resources/js/modules/vehicle-rental`: 38 test files passed (174 tests), 0 failures.
  - `npm run typecheck`: Passed with 0 errors.
