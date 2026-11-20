# Vehicle Rental Module: End-to-End Workflow & Internal Vehicle Fleet Support

**Date:** 2026-11-20  
**Scope:** `app/Modules/VehicleRental/`, `resources/js/modules/vehicle-rental/`, `docs/changes/`

## 1. Architectural Context & Requirement Analysis

The Vehicle Rental module must cleanly support three distinct operational scenarios:
1. **Company-Owned Vehicles Rented to Customers or Used Internally**: Vehicles owned outright by the tenant, deployed either under a Customer Agreement or directly for internal operational use.
2. **Third-Party Vehicles Rented for Internal Company Use**: Vehicles leased from external owners/suppliers solely for tenant company operations (e.g. logistics, site maintenance, staff transport), with owner payable settlement cycles but **without requiring customer assignment or fictitious customer records**.
3. **Third-Party Vehicles Sub-Leased to Customers**: Vehicles leased from external owners/suppliers and subleased to external customers under coupled Customer and Owner agreements.

### Evaluation of Unified vs. Separate Running Chart Interfaces
We thoroughly analyzed whether Owner and Customer Running Charts should be managed through separate CRUD interfaces or a unified interface:
- **Physical Trip Layer (Unified Truth)**: A trip is a physical, objective fact in the physical world. A vehicle leaves at a specific timestamp and odometer reading, is driven by a designated driver with specific air conditioning and terrain conditions, and returns at an exact timestamp and odometer reading. Maintaining two separate CRUD interfaces would force duplicate data entry by operators, inevitably cause odometer drift and human divergence, and violate physical continuity.
- **Commercial & Financial Representation (Segregated Channels)**: What differs between the Customer and Owner is strictly the commercial agreement terms (billing rates, excess mileage rates, overtime baselines, included allowances). 
- **Architectural Conclusion**: The single unified physical running chart—combined with context-aware overtime calculation (calculating customer overtime baseline when customer agreement is present; calculating owner overtime baseline when deployed internally) and segregated commercial print sheets/billing cycles—is the most reliable, maintainable, intuitive, and error-resistant design.

## 2. Issues & Root Causes Addressed

1. **Mandatory Customer Agreement on Vehicle Uses**:
   - `vehicle_rental_uses` schema previously required `customer_agreement_id` as `NOT NULL`. This prevented deploying vehicles leased from third parties for internal company operations without fabricating a dummy customer.
2. **Missing Owner-Side Vehicle Deployment Workflow**:
   - `VehicleUseService` only supported planning vehicle uses originating from a customer agreement (`plan(...)`). There was no direct route or service method to deploy an owner vehicle for internal operations.
3. **Null-Safety in Serializers, Incident Lookups & Print Reports**:
   - `VehicleUseResource`, `RunningChartRegisterResource`, and `IncidentVehicleUseOptionResource` attempted to unconditionally access `$use->customerAgreement->reference` or `$use->customerAgreement->party_name_snapshot`, causing 500 errors on null customer uses.
   - `RunningChartReportService` unconditionally populated `$clientName` as `'VARIOUS LESSEES'` rather than recognizing internal company deployments.
   - Frontend UI (`VehicleUsePanel`, `VehicleUseEditor`, `RunningChartsPanel`, `RunningChartRegisterPage`, `VehicleUseRegisterPage`) assumed non-null `customer_agreement` properties in metadata headers and filters.

## 3. Changes Implemented

### Backend
- **Database Migration (`2026_09_08_000001_create_vehicle_rental_uses_table.php`)**:
  - Altered `customer_agreement_id` and `customer_agreement_version` to nullable `unsignedBigInteger`.
  - Added explicit foreign key index on `owner_agreement_id`.
- **Constants (`OperationalFields.php`)**:
  - Added `customer_agreement_id` to `MUTABLE_USE`.
- **Services**:
  - `VehicleUseService`:
    - Added `planOwner(AgreementContext $context, int $ownerAgreementId, int $expectedVersion, array $input)` allowing direct operational deployment of an owner vehicle without customer assignment.
    - Updated `transition(...)` and `replace(...)` with null-safe handling of customer agreements for internal uses.
  - `RunningChartReportService`:
    - Updated client name resolution and daily lessee labels to display `'INTERNAL / COMPANY USE'` when generating owner report sheets for internal operations.
  - `RentalIncidentService`:
    - Eager loaded and indexed `ownerAgreement` in `vehicleUseOptions` and `list` so incidents on internal owner uses show owner agreement references and supplier names.
  - `UsageChargeBilling`:
    - Corrected missing agreement validation message to dynamically reflect `$kind->value`.
- **Http Controllers & Routes**:
  - `VehicleUseController`: Updated `store` to branch between customer and owner deployment based on route kind; added explicit `storeOwner` handler.
  - `Routes/api.php`: Registered route `POST owner/agreements/{agreement}/vehicles`.
- **Resources**:
  - `VehicleUseResource`: Made `customer_agreement` null-safe.
  - `RunningChartRegisterResource`: Made both top-level and nested `customer_agreement` properties null-safe.
  - `IncidentVehicleUseOptionResource`: Added `owner_agreement_reference` and `owner_party_name` for internal uses without customer agreements.

### Frontend
- **Types & API**:
  - `agreements.ts`: Added optional `kind?: AgreementKind` to `Agreement` interface.
  - `vehicleUse.ts`: Made `customer_agreement: AgreementReference | null`.
  - `runningCharts.ts`: Made `customer_agreement: ... | null`.
  - `vehicleUseApi.ts`: Added optional `kind` parameter to `planVehicleUse`.
- **Components & Pages**:
  - `VehicleUsePanel.tsx`: Added "Deploy for internal use" button on Owner agreements; made customer agreement display and driver mode badge null-safe.
  - `VehicleUseEditor.tsx`: Added support for owner agreement deployment (auto-preselects owner vehicle, simplifies input fields).
  - `RunningChartsPanel.tsx`: Safe `agreementId` fallback for owner sheets; made customer link null-safe and conditioned "Print Sheet".
  - `RunningChartRegisterPage.tsx`: Safe customer agreement handling in print, filter, and card grids.
  - `VehicleUseRegisterPage.tsx`: Safe customer agreement handling in header, badges, and metadata grid.

### Automated Tests
- Created `OwnerVehicleInternalUseTest.php`:
  - `test_plan_owner_vehicle_for_internal_company_use_without_customer`: Verifies planning and API retrieval of internal vehicle deployments.
  - `test_handover_and_return_transitions_for_internal_use`: Verifies custody transitions and vehicle fleet status synchronization.
  - `test_running_chart_recording_and_overtime_baseline_on_internal_use`: Verifies running chart trip logs, overtime baseline deduction against owner agreement terms, register filtering, and owner report generation.
  - `test_owner_billing_cycle_and_settlement_for_internal_use_running_charts`: Verifies owner billing cycles, running chart attachment, consolidated revision finalization, and payable invoice creation.
  - `test_plan_owner_rejects_mismatched_vehicle_and_out_of_coverage_periods`: Verifies period boundary and vehicle validation.

## 4. Verification

1. **Backend Tests (PHPUnit)**:
   - `php artisan test app/Modules/VehicleRental/Tests/OwnerVehicleInternalUseTest.php`: 5 passed (69 assertions).
   - `php artisan test app/Modules/VehicleRental/Tests/`: All 340 tests passed (2,886 assertions, 0 failures).
2. **Frontend Tests (Vitest)**:
   - `npm run test -- resources/js/modules/vehicle-rental`: All 38 test suites passed (181 passed, 0 failures).
3. **TypeScript Typecheck**:
   - `npm run typecheck`: Passed with 0 errors.
