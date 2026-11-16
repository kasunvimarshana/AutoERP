# Vehicle Rental Itemized Invoice and Supplier Bill Generation System

**Date:** 2026-11-16  
**Scope:** `VehicleRental`, `Invoice`, `Frontend / Vehicle Rental`  
**Focus:** Complete itemized customer invoice and vehicle owner bill generation across all operational components (base rent custody proration, excess km, multi-tier overtime, night outs, tolls, additions, contractual deductions/rebates, statutory WHT, fleet maintenance recoveries), telematics statements, and financial transparency.

---

## 1. Problem Statement & Root Cause
Prior to this implementation:
1. **Single-Line Invoicing Fallback**: In `TwoStageInvoicingService`, both Stage 1 and Stage 2 finalized invoices previously generated a single generic charge line (`self::WHOLE_CHARGE` with `$deltaAmount` or `$totalAmount`). Operational charges (excess kilometers, normal/double/triple overtime, driver night-out allowances, highway tolls) and ad-hoc additions/deductions were summarized in notes but completely absent as discrete line items on official invoices.
2. **Owner Settlement AP Bill Dummy Lines**: In `OwnerSettlementBillingService`, withholding tax (WHT) and fleet maintenance chargeback deductions were inserted as dummy lines with `unit_price = '0.000000'` alongside a single aggregated gross lease line, causing confusing $0.00 entries on official tax printouts.
3. **Missing Deduction Mapping**: In `TaxedSourceInvoiceFactory`, header adjustments are prohibited prior to tax preparation (`$source->adjustments !== []` throws an exception). Deductions and downtime rebates needed to be mathematically mapped as line discounts (`InvoiceLineData::$discountAmount`) without violating the `unit_price >= 0` constraint.
4. **Telematics & Operational Transparency**: Customers and vehicle owners lacked access to itemized telematics data (total km run, 1.5x/2.0x/3.0x overtime breakdown, night-out nights, assigned driver names, and custody days) directly from billing document detail views.

---

## 2. Key Changes & Implementation Details

### A. Backend (`app/Modules/VehicleRental/Services/TwoStageInvoicingService.php`)
1. **Itemized Stage 1 Provisional Invoicing (`buildStage1Lines`)**:
   - Calculates physical vehicle custody days across all assigned vehicles during the billing cycle.
   - For multi-vehicle arrangements (including replacement vehicles), prorates base rent across vehicles by custody days, emitting individual lines with vehicle registration and custody day counts: `Base Rental Fee · {RegNo} · {Days} Days ({Period})`.
   - Generates unique, traceable source lines (`sourceLineType = 'vehicle_rental_billing_cycle'`, `sourceLineId = ($sourceId * 1000) + lineNumber`).
   - Populates `purchaserReferenceFields` (Agreement Reference, Cycle Number, Billing Period, Assigned Vehicles) for automatic inclusion on official tax printouts.
2. **Itemized Supplementary Invoicing (`buildSupplementaryLines`)**:
   - Accurately itemizes each operational charge:
     - Base Rent (if Stage 1 provisional invoice was not issued)
     - Excess Kilometers: `Excess Mileage Charge · {ExcessKm} km @ {Rate}/km (Included: {FreeKm} km)`
     - Normal Overtime (1.5x): `Driver Overtime · Normal (1.5x) · {NormalHours} hrs @ {Rate}/hr`
     - Double Overtime (2.0x): `Driver Overtime · Double (2.0x) · {DoubleHours} hrs @ {Rate}/hr`
     - Triple Overtime (3.0x): `Driver Overtime · Triple (3.0x) · {TripleHours} hrs @ {Rate}/hr`
     - Night-Out Allowances: `Driver Night-Out Allowance · {Nights} Nights @ {Rate}`
     - Highway Tolls & Consumables: `Tolls and Highway Charges`
     - Ad-Hoc Additions: Itemized with associated vehicle and driver attribution snapshots.
   - Deductions & Downtime Rebates: Safely allocated as line-level discounts (`discountAmount`) across charge lines via `applyDeductionsAsDiscounts`, ensuring strict compliance with `unit_price >= 0` and exact mathematical reconciliation with `net_subtotal`.
3. **Itemized Consolidated Revision Invoicing (`buildConsolidatedLines`)**:
   - Replaces Stage 1 and Stage 2 with a single comprehensive invoice consolidating prorated base rent per vehicle custody, all operational usage charges, additions, and deductions.
4. **Credit Note Invoicing (`buildCreditNoteLines` & `issueCreditNoteInvoice`)**:
   - For Mode A negative deltas (downtime rebates exceeding usage) and Mode B provisional voids, itemizes specific deduction items as positive charge lines on authentic Credit Notes (`InvoiceType::Credit`), attaching structured reference fields and allocating credit via `InvoiceBalanceUpdater`.

### B. Backend (`app/Modules/VehicleRental/Services/OwnerSettlementBillingService.php`)
1. **Itemized Owner Lease & Usage Charges**:
   - Itemizes base lease rent per `OwnerBaseCharge` record and operational usage/overtime per `OwnerUsageCharge` record.
   - Queries and incorporates unbilled owner `BillingCycle` records if present.
2. **Elimination of Dummy Lines & Accurate Adjustment Accounting**:
   - Completely eliminated dummy zero-amount lines (`unitPrice = 0`).
   - Statutory 10% Withholding Tax and maintenance chargebacks are recorded purely as authoritative `InvoiceAdjustmentData` (`AdjustmentType::Withholding` and `AdjustmentType::Discount`), properly reducing grand total to net payable while maintaining gross lease expense in the GL posting plan.
   - Populates structured `purchaserReferenceFields` detailing Agreement Reference, Vehicle Registration, Gross Lease Payable, WHT deduction, Maintenance recovery, and Net Settlement Payable.

### C. Backend (`RentalBillingDocumentService.php`, `RentalBillingDocumentController.php`, `Routes/api.php`)
1. **Enriched Document Detail (`getDocumentDetail`)**:
   - Exposes `discount_amount` on each invoice line.
   - Exposes operational `telematics` summary (`trips_count`, `commercial_km`, `normal_ot_hours`, `double_ot_hours`, `triple_ot_hours`, `night_outs`, assigned vehicles, assigned drivers).
   - Exposes comprehensive `financial_breakdown` (Base Rent, Excess KM, Overtime, Night Outs, Tolls, Additions, Deductions, Gross Subtotal, Taxes, WHT Withheld, Maintenance Deductions, Grand Total, Paid Total, Balance Due).
   - Generates `statement_url`, `print_url`, and `pdf_url`.
2. **Dedicated Statement Endpoint**:
   - Added `getStatementData` on `RentalBillingDocumentService`.
   - Added `statement` action on `RentalBillingDocumentController`.
   - Registered `GET /api/v1/vehicle-rental/billing/documents/{invoice}/statement` in `api.php`, supporting HTML, Print, and PDF views using the dedicated `reports.vehicle-rental.billing-cycle-statement` template.

### D. Frontend (`resources/js/modules/vehicle-rental/`)
1. **`rentalBillingApi.ts`**:
   - Added `discount_amount` to `RentalBillingLine`.
   - Added `RentalTelematicsSummary` and `RentalFinancialBreakdown` interfaces.
   - Added `telematics`, `financial_breakdown`, `statement_url`, `print_url`, and `pdf_url` to `RentalBillingDetail`.
   - Added `getRentalBillingStatementUrl(invoiceId, format)` helper.
2. **`RentalBillingDocumentDetailModal.tsx`**:
   - Rendered an **Operational & Telematics Summary Strip** displaying Commercial KM, Normal OT, Double OT, and Night Outs.
   - Added dynamic **Discount / Rebate** column in Itemized Charges table when discounts are present.
   - Upgraded financial totals to a **Comprehensive Financial Breakdown Card** presenting gross charges, statutory WHT, maintenance deductions, taxes, paid total, and balance due.
   - Added a direct **Telematics Statement** action button in the footer actions.
3. **`RentalInvoicesAndBillsPage.test.tsx`**:
   - Updated mock document data to test and verify new telematics and financial breakdown fields.

---

## 3. Verification & Test Results
- **Unit & Architecture Tests**:
  - `php artisan test tests/Unit/Architecture`: 12 passed (124 assertions).
- **Vehicle Rental Billing Tests**:
  - `php artisan test app/Modules/VehicleRental/Tests/RentalBillingDocumentTest.php`: 10 passed (76 assertions).
  - `php artisan test app/Modules/VehicleRental/Tests/TwoStageInvoicingTest.php`: 20 passed (141 assertions).
  - `php artisan test app/Modules/VehicleRental/Tests/OwnerSettlementBillingTest.php`: 3 passed (11 assertions).
  - Combined suite: 33 passed (228 assertions).
- **Frontend Tests & Typechecking**:
  - `npx vitest run resources/js/modules/vehicle-rental/RentalInvoicesAndBillsPage.test.tsx`: 8 passed (8 tests).
  - `npm run typecheck`: 0 errors.
