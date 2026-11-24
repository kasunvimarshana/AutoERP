# System-Wide Backdated CRUD Safety & Temporal Integrity Remediation

**Date:** 2026-11-24  
**Module:** Core / System-wide (`Invoice`, `Inventory`, `Purchase`, `Payment`, `VehicleRental`, `VehicleService`, `Finance`, `Reporting`)  
**Author & Maintainer:** Kasun Vimarshana (`kasunvmail@gmail.com`)  

---

## 1. Context & Objectives

In ERP and double-entry financial/operational systems, backdated transactions (creating, updating, reversing, or canceling records with effective business dates in the past) are standard operational necessities (e.g., late bills, supplier invoice adjustments, inventory receipts recorded after physical goods arrival, month-end audit corrections).

However, backdating presents severe temporal risks if physical recording timestamps (`created_at`, `posted_at`) are conflated with business operational dates (`invoice_date`, `received_date`, `movement_date`, `posting_date`, `adjustment_date`). 

A rigorous, end-to-end investigation identified 12 critical temporal defects across the AutoERP platform:
1. **Invoice & GRN Tax Document Mappers:** Priority was given to physical `posted_at` (`now()`) over business `invoice_date` and `received_date`, distorting tax periods and VAT/WHT returns for backdated documents.
2. **Invoice Reversals:** `InvoiceReversalService` validated `reversalDate` against physical `posted_at` timestamp instead of business `posting_date` / `invoice_date`, blocking valid reversals that occurred chronologically after the invoice date but before the moment of entry.
3. **Inventory Running Balances on Backdated Movements:** `InventoryMovementPoster` blindly assigned live current warehouse stock balance into `balance_quantity_after` and `balance_value_after` instead of projecting chronologically across subsequent movements.
4. **Valuation Layer Temporal Inversion:** `inventory_valuation_layers` table lacked a business date (`layer_date`), and `AbstractLayerValuationMethod` ordered FIFO layers by physical `created_at`, pushing backdated receipts to the tail and consuming incorrect inventory layers.
5. **Stock Card Chronological Filtering & Ordering:** `StockCardService` filtered and grouped movements by physical `posted_at` instead of operational `movement_date`, causing backdated movements to disappear from historical periods.
6. **Stock State Historical Auditing:** `InventoryStockStateService` hardcoded `occurred_at = now()` instead of operational `movement_date`, corrupting point-in-time balance reconstructions.
7. **Negative Stock on Subsequent Periods:** `InventoryMovementPoster` lacked validation ensuring that a backdated stock issue would not drive stock negative on any intermediate subsequent movement date.
8. **Hardcoded Current Timestamps in Integrations:** Missing support for explicit backdated reversal and movement dates across `InventoryMovementReversalService`, `StockMovementService`, `InventoryFacade`, `InventoryAllocationIssuer`, `PurchaseInventoryIntegrationService`, and `VehicleServiceInventoryFinanceService`.
9. **Stock Adjustment Historical Stock Validation:** `StockAdjustmentService` validated system quantities against live current stock instead of historical stock as-of `adjustment_date`.
10. **Dynamic Point-in-Time Aging Calculations:** `AgingReportService` read mutable current `invoice_balances.remaining_amount` instead of reconstructing point-in-time unpaid balances as-of `asOfDate`.
11. **Cash Receipt Date Policy:** Date policies were not enforced uniformly across payment channels.
12. **Two-Stage Invoicing Reversals:** `TwoStageInvoicingService` reversed provisional allocations with `now()`, creating a temporal inversion with reallocations.

---

## 2. Implemented Architecture & Remediation

### 2.1 Database & Schema Integrity (Axiom 7 Compliance)
- In accordance with Core Axiom 7 (1 table = 1 migration file, 0 alteration patch migrations), the `layer_date` column (`$table->date('layer_date')`) and index (`inventory_valuation_layers_layer_date_ix`) were added directly to the table creation migration: `2026_06_12_140011_create_inventory_valuation_layers_table.php`.
- Zero alteration patch files were introduced, maintaining a clean single-baseline schema.
- Verified via `ModuleMigrationBaselineTest` (1 passed, 3 assertions) and `HistoricalRetentionMigrationTest` (1 passed, 22 assertions).

### 2.2 Tax Document Mappers
- `InvoiceTaxDocumentMapper`: Maps `transactionDate` to `$transactionDate ?? $invoice->invoice_date->toDateString()`, preserving tax reporting period fidelity regardless of when the invoice was posted.
- `GoodsReceiptNoteTaxDocumentMapper`: Maps `transactionDate` and `sourceDate` to `$goodsReceipt->received_date->toDateString()`.

### 2.3 Invoice Reversal Validation
- `InvoiceReversalService`: Updated `validateReversalDate()` to validate against the business posting date (`$invoice->postingPlan?->posting_date?->toDateString() ?? $invoiceDate`), permitting backdated reversals after the invoice date while strictly forbidding reversals prior to the invoice date.

### 2.4 Inventory Chronological Balances & Immutability
- `InventoryMovementPoster`:
  - Enforces negative stock prevention across historical cutoff dates: when backdating an issue, it verifies that `$priorBalanceQty >= $quantity` and that no subsequent movement's `balance_quantity_after` is driven below `$quantity`.
  - When backdating is valid, projects and updates running balances (`balance_quantity_after`, `balance_value_after`) across all subsequent movements.
  - Handled SQLite/MySQL date comparison nuances with explicit `whereDate()` predicates.
- `InventoryMovement`: Updated model lifecycle integrity to permit `$isUpdatingRunningBalance` when only running balance projections change on posted and reversed records (`Posted` or `Reversed`), preserving absolute immutability on all operational transaction facts.
- `AbstractLayerValuationMethod` & `WeightedAverageValuationMethod`:
  - Valuation layers record `layer_date = $movement->movement_date`.
  - FIFO layer resolution orders by `layer_date ASC, created_at ASC, id ASC`, ensuring backdated receipts are consumed in true chronological order.
- `StockCardService`:
  - Filters opening balance and movements strictly by `movement_date`.
  - Orders rows chronologically by `movement_date ASC, created_at ASC, id ASC`.
- `InventoryStockStateService`: Sets `occurred_at` to the movement's `movement_date->startOfDay()` (or explicit timestamp), ensuring accurate point-in-time state tracking.
- `InventoryMovementReversalService`, `StockMovementService`, `InventoryFacade`, `InventoryAllocationIssuer`, `PurchaseInventoryIntegrationService`, and `VehicleServiceInventoryFinanceService`: Added optional `$reversalDate` and `$movementDate` parameters to allow upstream business documents to govern reversal and issue timestamps.
- `StockAdjustmentService`: Validates `$line->system_quantity` against historical stock as of `adjustment_date` using `historicalStockOnHand()`.
- `TwoStageInvoicingService`: Passes `$finalInvoice->invoice_date` when reversing provisional allocations.

### 2.5 Dynamic Point-in-Time Financial Reporting
- `AgingReportService`: Calculates receivables and payables aging dynamically as of `asOfDate` by joining authoritative financial reversal journal dates (`finance_journal_entries.journal_date`), rather than querying mutable physical timestamps `updated_at`.
- `PaymentCashReceiptDatePolicy`: Enforces future-date payment rejection across all payment methods and directions (inbound, outbound, cash, non-cash), while preserving the specialized permission and reason requirements for backdated cash receipts.

---

## 3. Modified Files Summary

| File | Changes Made |
|---|---|
| [`app/Modules/Inventory/Database/Migrations/2026_06_12_140011_create_inventory_valuation_layers_table.php`](file:///d:/project-new/AutoERP/app/Modules/Inventory/Database/Migrations/2026_06_12_140011_create_inventory_valuation_layers_table.php) | Added `layer_date` date column and `layer_date_ix` index to table creation schema. |
| [`app/Modules/Inventory/Models/InventoryValuationLayer.php`](file:///d:/project-new/AutoERP/app/Modules/Inventory/Models/InventoryValuationLayer.php) | Added `'layer_date' => 'date'` in model `$casts`. |
| [`app/Modules/Inventory/Models/InventoryMovement.php`](file:///d:/project-new/AutoERP/app/Modules/Inventory/Models/InventoryMovement.php) | Added `$isUpdatingRunningBalance` condition to `updating` hook, allowing running balance projection updates across posted and reversed records while strictly protecting transaction ledger facts. |
| [`app/Modules/Inventory/Strategies/Valuation/AbstractLayerValuationMethod.php`](file:///d:/project-new/AutoERP/app/Modules/Inventory/Strategies/Valuation/AbstractLayerValuationMethod.php) | Assigned `layer_date` on layer creation; updated `layerQuery` to order by `layer_date ASC, created_at ASC, id ASC`. |
| [`app/Modules/Inventory/Strategies/Valuation/WeightedAverageValuationMethod.php`](file:///d:/project-new/AutoERP/app/Modules/Inventory/Strategies/Valuation/WeightedAverageValuationMethod.php) | Assigned `layer_date` on layer creation. |
| [`app/Modules/Inventory/Services/InventoryMovementPoster.php`](file:///d:/project-new/AutoERP/app/Modules/Inventory/Services/InventoryMovementPoster.php) | Implemented chronological subsequent movement balance projection and multi-period negative stock prevention. |
| [`app/Modules/Inventory/Services/InventoryMovementReversalService.php`](file:///d:/project-new/AutoERP/app/Modules/Inventory/Services/InventoryMovementReversalService.php) | Added `?string $reversalDate = null` parameter defaulting to `now()->toDateString()`. |
| [`app/Modules/Inventory/Services/StockMovementService.php`](file:///d:/project-new/AutoERP/app/Modules/Inventory/Services/StockMovementService.php) | Added `?string $reversalDate = null` parameter on `reverse()`. |
| [`app/Modules/Inventory/Services/InventoryFacade.php`](file:///d:/project-new/AutoERP/app/Modules/Inventory/Services/InventoryFacade.php) | Added `?string $reversalDate = null` parameter on `reverseStockMovement()`. |
| [`app/Modules/Inventory/Services/StockCardService.php`](file:///d:/project-new/AutoERP/app/Modules/Inventory/Services/StockCardService.php) | Filtered and ordered stock movements strictly by `movement_date`. |
| [`app/Modules/Inventory/Services/InventoryStockStateService.php`](file:///d:/project-new/AutoERP/app/Modules/Inventory/Services/InventoryStockStateService.php) | Recorded `occurred_at` as the movement's `movement_date` rather than hardcoded `now()`. |
| [`app/Modules/Inventory/Services/InventoryAllocationIssuer.php`](file:///d:/project-new/AutoERP/app/Modules/Inventory/Services/InventoryAllocationIssuer.php) | Added `?string $movementDate = null` parameter defaulting to `$allocation->allocation_date`. |
| [`app/Modules/Inventory/Services/InventoryAllocationService.php`](file:///d:/project-new/AutoERP/app/Modules/Inventory/Services/InventoryAllocationService.php) | Forwarded `$movementDate` parameter in allocation issuance. |
| [`app/Modules/Inventory/Services/StockAdjustmentService.php`](file:///d:/project-new/AutoERP/app/Modules/Inventory/Services/StockAdjustmentService.php) | Validated `system_quantity` against historical stock as-of `adjustment_date`. |
| [`app/Modules/Invoice/Services/Tax/InvoiceTaxDocumentMapper.php`](file:///d:/project-new/AutoERP/app/Modules/Invoice/Services/Tax/InvoiceTaxDocumentMapper.php) | Mapped `transactionDate` to `$invoice->invoice_date` rather than `posted_at`. |
| [`app/Modules/Invoice/Services/InvoiceReversalService.php`](file:///d:/project-new/AutoERP/app/Modules/Invoice/Services/InvoiceReversalService.php) | Validated reversal date against invoice posting date / invoice date. |
| [`app/Modules/Purchase/Services/Tax/GoodsReceiptNoteTaxDocumentMapper.php`](file:///d:/project-new/AutoERP/app/Modules/Purchase/Services/Tax/GoodsReceiptNoteTaxDocumentMapper.php) | Mapped `transactionDate` and `sourceDate` to `received_date`. |
| [`app/Modules/Purchase/Services/PurchaseInventoryIntegrationService.php`](file:///d:/project-new/AutoERP/app/Modules/Purchase/Services/PurchaseInventoryIntegrationService.php) | Added `?string $reversalDate = null` parameter. |
| [`app/Modules/VehicleRental/Services/TwoStageInvoicingService.php`](file:///d:/project-new/AutoERP/app/Modules/VehicleRental/Services/TwoStageInvoicingService.php) | Passed `$finalInvoice->invoice_date` on provisional allocation reversal. |
| [`app/Modules/VehicleService/Services/VehicleServiceInventoryFinanceService.php`](file:///d:/project-new/AutoERP/app/Modules/VehicleService/Services/VehicleServiceInventoryFinanceService.php) | Added `?string $reversalDate = null` parameter. |
| [`app/Modules/Finance/Services/AgingReportService.php`](file:///d:/project-new/AutoERP/app/Modules/Finance/Services/AgingReportService.php) | Implemented dynamic point-in-time calculation based on payments/credits allocated as-of `asOfDate`, resolving reversal dates via authoritative finance journal entries. |
| [`app/Modules/Payment/Services/PaymentCashReceiptDatePolicy.php`](file:///d:/project-new/AutoERP/app/Modules/Payment/Services/PaymentCashReceiptDatePolicy.php) | Enforced future-date payment validation across all payment methods and directions. |
| [`tests/Feature/Core/BackdatedCrudSafetyTest.php`](file:///d:/project-new/AutoERP/tests/Feature/Core/BackdatedCrudSafetyTest.php) | Added comprehensive feature test suite covering Invoices, Tax, Valuation Layers, Chronological Stock Balances, Negative Stock Prevention, Adjustments, Reversed Movement Projection, Future Date Prevention, and Aging Reports. |
| [`app/Modules/Inventory/Tests/StockCardTest.php`](file:///d:/project-new/AutoERP/app/Modules/Inventory/Tests/StockCardTest.php) | Updated assertions to reflect chronological movement date ordering. |
| [`tests/Feature/Item/ItemBaseUomTest.php`](file:///d:/project-new/AutoERP/tests/Feature/Item/ItemBaseUomTest.php) | Added `layer_date` to raw layer fixture creation. |

---

## 4. Verification & Quality Gate Results

All quality gates passed with zero errors:

1. **Dedicated Backdated CRUD Safety Suite:**
   ```bash
   php artisan test tests/Feature/Core/BackdatedCrudSafetyTest.php
   ```
   *Result:* 9 passed (39 assertions).
     - ✓ invoice and grn tax mappers prioritize business dates
     - ✓ backdated invoice can be reversed before posted at timestamp
     - ✓ fifo valuation layers order by layer date for backdated receipts
     - ✓ backdated inventory issue projects running balances and prevents subsequent negative stock
     - ✓ backdated inventory issue updates subsequent running balances when valid
     - ✓ backdated movement updates subsequent reversed movements without logic exception
     - ✓ future dated payments are rejected for outbound and non cash
     - ✓ stock adjustment validates historical stock as of adjustment date
     - ✓ aging report point in time calculation

2. **Schema & Migration Baseline Integrity Gate (Axiom 7):**
   ```bash
   php artisan test --filter ModuleMigrationBaselineTest
   php artisan test --filter HistoricalRetentionMigrationTest
   ```
   *Result:* 2 passed (25 assertions), 0 alteration patches.

3. **Subsystem Test Suites:**
   - Inventory: 72 passed (554 assertions)
   - Invoice: 169 passed (1,261 assertions)
   - Payment: 112 passed (669 assertions)
   - Finance: 92 passed (3,380 assertions)

4. **Code Style (Laravel Pint PSR-12):**
   ```bash
   php vendor/bin/pint --test [modified files]
   ```
   *Result:* Passed with 0 errors (`{"tool":"pint","result":"passed"}`).

5. **TypeScript Typecheck:**
   ```bash
   npm run typecheck
   ```
   *Result:* Passed with 0 errors (`tsc --noEmit` exited with code 0).

6. **Frontend Linting:**
   ```bash
   npm run lint
   ```
   *Result:* Passed with 0 errors across `resources/js/*`.

7. **Frontend Unit & Component Tests (Vitest):**
   ```bash
   npm test
   ```
   *Result:* 176 test files passed, 739 tests passed.

4. **Code Style (Laravel Pint PSR-12):**
   ```bash
   php vendor/bin/pint --test [modified files]
   ```
   *Result:* Passed with 0 errors (`{"tool":"pint","result":"passed"}`).

5. **TypeScript Typecheck:**
   ```bash
   npm run typecheck
   ```
   *Result:* Passed with 0 errors (`tsc --noEmit` exited with code 0).

6. **Frontend Linting:**
   ```bash
   npm run lint
   ```
   *Result:* Passed with 0 errors across `resources/js/*`.

7. **Frontend Unit & Component Tests (Vitest):**
   ```bash
   npm test
   ```
   *Result:* 176 test files passed, 739 tests passed.

8. **Full Backend Test Suite (PHPUnit):**
   ```bash
   php artisan test
   ```
   *Result:* 1,384 passed (14,327+ assertions).

---

## Author & Maintainer

- **Lead Architect & Developer:** Kasun Vimarshana
- **Email:** [kasunvmail@gmail.com](mailto:kasunvmail@gmail.com)
- **Phone:** [+94713525253](tel:+94713525253)
- **Repository:** [kasunvimarshana/AutoERP](https://github.com/kasunvimarshana/AutoERP)
