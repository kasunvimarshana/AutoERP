# Vehicle Rental Two-Stage Flexible Invoicing, Fleet Reassignments & Supplier AP Settlements

## Context & Motivation
In vehicle rental enterprise operations, billing workflows require high flexibility depending on customer commercial arrangements:
1. **Optional Running Charts:** Some clients operate under fixed corporate monthly leases where log-sheet telematics are not captured, while others require detailed journey-by-journey running charts. The system must support generating invoices both with and without running charts.
2. **Two-Stage Invoicing Lifecycle:** Many corporate agreements mandate issuing an upfront provisional Base Rent invoice at the beginning or middle of a billing period (Stage 1), followed by attaching finalized running charts, pooled excess mileage, normal and double driver overtime (OT), night-out allowances, highway tolls, ad-hoc additions (driver salaries, fuel adjustments), and contract deductions (breakdown rebates, vehicle downtime compensation) at the close of the period (Stage 2).
3. **Invoicing Finalization Modes:** When finalizing Stage 2, businesses require two distinct strategies:
   - **Mode A (Supplementary Usage Invoice):** Keeps the initial Stage 1 Base Rent invoice untouched and issues a supplementary invoice strictly for usage charges, driver fees, and period deltas.
   - **Mode B (Consolidated Revision & Void):** Issues a single comprehensive revision invoice reflecting the full final balance, voids the provisional Stage 1 invoice via an audited Credit Note, and automatically transfers any payments made against Stage 1 onto the new revision invoice via `PaymentAllocationService`.
4. **Mid-Contract Fleet Substitutions & Driver Reassignments:** Operational disruptions require mid-contract vehicle replacements or driver shift handovers. Vehicle replacements must rebalance unearned lessor base rent entitlements at the exact cutover timestamp ($T_{\text{cut}}$) with audited pro-rata credit adjustments, and driver reassignments must check for overlapping trip conflicts and record immutable audit history.
5. **Vehicle Owner Inbound AP Supplier Bills:** Vehicle owner disbursement statements must be formally convertible into Inbound Purchase Invoices (Supplier Bills) in Accounts Payable, accurately deducting statutory 10% Withholding Tax (`:3305-000 WHT`) and confirmed maintenance chargebacks.
6. **Mathematical Precision:** Elimination of PHP native floating-point math and `round()` across financial calculation paths in favor of BCMath (`DecimalMath`) to prevent truncation and rounding discrepancies.

---

## Changes Implemented

### 1. Database Migrations (KISS + Explicit)
Created six dedicated, isolated migrations adhering strictly to KISS, atomic execution, and optimistic locking principles:
- `2026_11_14_100001_create_vehicle_rental_agreement_adjustments_table.php`: Core financial adjustments (credit/debit notes, rebates, chargebacks) linked to invoices.
- `2026_11_14_100002_create_vehicle_rental_agreement_adjustments_history_table.php`: Append-only audit trail for adjustments.
- `2026_11_14_100003_create_vehicle_rental_billing_cycles_table.php`: Periodic billing cycles tracking initial, supplementary, and final invoices with snapshots.
- `2026_11_14_100004_create_vehicle_rental_billing_cycle_charts_table.php`: Attached running chart telemetry (commercial KM, normal/double OT minutes, night outs, tolls).
- `2026_11_14_100005_create_vehicle_rental_billing_cycle_items_table.php`: Ad-hoc addition/deduction line items (driver salaries, fuel surcharges, downtime rebates).
- `2026_11_14_100006_create_vehicle_rental_reassignments_table.php`: Immutable event ledger for vehicle replacements and driver reassignments with cutover timestamps and financial deltas.

### 2. Domain Enums & Eloquent Models
- **Enums (`app/Modules/VehicleRental/Enums`):**
  - `BillingCycleStatus`: `draft`, `stage1_generated`, `charts_attached`, `finalized`, `cancelled`.
  - `BillingCycleMode`: `monthly`, `weekly`, `daily`, `custom`.
  - `BillingFinalizationMode`: `supplementary` (Mode A), `consolidated` (Mode B).
  - `BillingCycleItemType`: `addition`, `deduction`.
  - `BillingCycleItemCategory`: `driver_salary`, `overtime`, `excess_km`, `fuel`, `toll`, `night_out`, `deduction`, `other`.
  - `ReassignmentType`: `vehicle_replacement`, `driver_reassignment`.
  - `ReassignmentFinancialStatus`: `none`, `pending`, `settled`.
  - `AgreementAdjustmentType`, `AgreementAdjustmentCategory`, `AgreementAdjustmentStatus`.
- **Models (`app/Modules/VehicleRental/Models`):**
  - `BillingCycle`, `BillingCycleChart`, `BillingCycleItem`, `VehicleRentalReassignment`, `AgreementAdjustment`, `AgreementAdjustmentHistory`.
- **Refactoring:**
  - `RentalChargeDocuments.php`: Refactored to persist real `AgreementAdjustment` and `AgreementAdjustmentHistory` records instead of synthetic IDs.
  - `OwnerDisbursementReportService.php`: Replaced all native floating-point math and `round()` calls with `DecimalMath` BCMath operations.

### 3. Domain Business Services
- **`TwoStageInvoicingService.php` (`app/Modules/VehicleRental/Services`):**
  - `createCycle()`: Computes base rent using `BaseRentPreview`, initializes `BillingCycle`, and optionally generates Stage 1 provisional `Invoice` of type `rental`.
  - `attachCharts()`: Links finalized running charts, calculates pooled excess KM, normal and double OT hours, night outs, and highway tolls.
  - `addItem()` / `removeItem()`: Manages dynamic additions and deductions with optimistic locking (`row_version`).
  - `previewFinal()`: Aggregates full financial position, tax amounts, Stage 1 deductions, and gross settlement deltas.
  - `finalizeCycle()`:
    - Mode A: Issues Supplementary Usage Invoice for delta amounts.
    - Mode B: Issues Consolidated Revision Invoice, creates Credit Note to offset Stage 1 invoice, and automatically transfers existing payments via `PaymentAllocationService`.
- **`VehicleDriverReassignmentService.php` (`app/Modules/VehicleRental/Services`):**
  - `reassignRunningChartDriver()`: Atomically updates driver on draft/unfinalized running chart, verifies no conflicting trips exist for the driver, and records a permanent audit entry.
  - `replaceVehicle()`: Closes current vehicle custody at cutover timestamp $T_{\text{cut}}$, returns original vehicle odometer, dispatches replacement vehicle, calculates pro-rata lessor entitlement rebalancing, and creates financial adjustments.
- **`OwnerSettlementBillingService.php` (`app/Modules/VehicleRental/Services`):**
  - Generates formal Inbound Purchase Invoices (Supplier Bills) in the `Invoice` module for owner disbursements.
  - Automatically calculates and deducts statutory 10% Withholding Tax (`:3305-000 WHT`) and applies owner-allocated fleet maintenance expense offsets.

### 4. API Endpoints, Form Requests & Controllers
- **Form Requests (`app/Modules/VehicleRental/Http/Requests`):**
  - `BillingCycleRequest`, `AgreementAdjustmentRequest`, `VehicleDriverReassignmentRequest`, `OwnerSettlementBillRequest`.
- **Controllers (`app/Modules/VehicleRental/Http/Controllers`):**
  - `BillingCycleController`: Index, store, show, attachCharts, addItem, removeItem, preview, finalize.
  - `AgreementAdjustmentController`: Index, store, show.
  - `VehicleDriverReassignmentController`: Index, reassignDriver, replaceVehicle.
  - `OwnerSettlementBillController`: Store.
- **Routes (`app/Modules/VehicleRental/Routes/api.php`):**
  - `/api/v1/vehicle-rental/billing-cycles/*`
  - `/api/v1/vehicle-rental/{kind}/agreements/{agreement}/billing-cycles`
  - `/api/v1/vehicle-rental/running-charts/{chart}/reassign-driver`
  - `/api/v1/vehicle-rental/vehicle-uses/{use}/replace-vehicle`
  - `/api/v1/vehicle-rental/reassignments`
  - `/api/v1/vehicle-rental/owner-settlement-bill`

### 5. Frontend UI Components & Client Integration
- **API Client (`resources/js/modules/vehicle-rental/agreementApi.ts`):**
  - Added TypeScript interfaces: `BillingCycle`, `BillingCycleChart`, `BillingCycleItem`, `BillingCyclePreview`, `ReassignmentRecord`.
  - Added API methods: `listBillingCycles`, `createBillingCycle`, `getBillingCycle`, `attachCycleCharts`, `addCycleItem`, `removeCycleItem`, `previewCycleFinal`, `finalizeBillingCycle`, `reassignRunningChartDriver`, `replaceVehicleUse`, `listReassignments`, `generateOwnerSettlementBill`.
- **Components Created (`resources/js/modules/vehicle-rental`):**
  - `BillingCycleWizardModal.tsx`: Multi-step wizard supporting cycle creation, running chart attachment, additions/deductions editor, real-time financial delta preview, and Mode A/B finalization.
  - `ReassignDriverModal.tsx`: Driver reassignment modal with controlled `LookupSelect` autocomplete for employee drivers and mandatory audit reason.
  - `ReassignmentHistoryPanel.tsx`: Audit timeline table displaying fleet vehicle substitutions, cutover timestamps, driver handovers, and lessor pro-rata financial settlement statuses.
  - `OwnerSettlementModal.tsx`: Dialog generating formal Accounts Payable Inbound Bills from owner disbursement reports with 10% WHT and maintenance deductions.
- **Parent Views Updated:**
  - `AgreementFinancialSummaryPanel.tsx`: Integrated Billing Cycles management section and Reassignment History timeline.
  - `RunningChartsPanel.tsx`: Added "Reassign Driver" button on active running chart cards.
  - `OwnerDisbursementReportPage.tsx`: Added "Generate Inbound AP Bill" action button and modal integration.

---

## Verification & Test Results
1. **PHPUnit Automated Test Suite:**
   - `TwoStageInvoicingTest.php`: 5 passed (29 assertions) covering initial cycle, running chart attachment, ad-hoc additions/deductions, Mode A supplementary invoice, and Mode B consolidated revision.
   - `VehicleDriverReassignmentTest.php`: 3 passed (15 assertions) covering driver reassignment, conflict prevention, and vehicle replacement with pro-rata lessor rebalance.
   - `OwnerSettlementBillingTest.php`: 2 passed (8 assertions) covering AP Supplier Bill generation with 10% WHT and maintenance deductions.
   - Complete Vehicle Rental test suite: **244 passed (2,059 assertions) with 0 errors**.
2. **TypeScript Compilation:**
   - `npm run typecheck`: Passed with 0 errors (`tsc --noEmit`).
3. **Frontend Vitest Suite:**
   - `BillingCycleWizardModal.test.tsx`: 2 passed.
   - `ReassignDriverModal.test.tsx`: 1 passed.
   - `OwnerSettlementModal.test.tsx`: 1 passed.
   - All 32 test files and 142 tests passing cleanly.
4. **Vite Production Asset Build:**
   - `npm run build`: Compiled cleanly in 8.77s with full client chunks and manifest generated.
