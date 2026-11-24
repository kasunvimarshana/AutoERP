# End-to-End Backdated CRUD Safety Audit & Model Lifecycle Guardrails

**Date:** 2026-11-24  
**Module:** Core / System-Wide (`Finance`, `Inventory`, `Invoice`, `Payment`, `Purchase`, `Selling`, `Expense`, `Tax`, `Reporting`)  
**Author & Maintainer:** Kasun Vimarshana (`kasunvmail@gmail.com`)  
**Phone:** +94713525253  
**Repository:** [kasunvimarshana/AutoERP](https://github.com/kasunvimarshana/AutoERP)

---

## 1. Context & Objectives

An end-to-end architectural audit was conducted across all 31 AutoERP modules to determine whether historical records can be safely created, updated, and deleted using backdated business dates while preserving data integrity, audit trails, and downstream operational consistency.

### Core Dual-Timestamp Architectural Invariant
1. **Physical Ingestion Timestamps (`created_at`, `posted_at`, `reversed_at`, `updated_at`, `deleted_at`)**: Authoritative, server-owned physical audit facts recording when an operation occurred in real time. They must never be overridden, accepted from client inputs, or altered.
2. **Operational Business Dates (`invoice_date`, `payment_date`, `movement_date`, `received_date`, `layer_date`, `expense_date`, `allocation_date`)**: Civil legal dates recording the business event. They govern accounting periods, VAT/tax filing intervals, FIFO layer consumption queues, point-in-time aging reports, and stock card sequencing.

---

## 2. Comprehensive Backdated CRUD Safety Boundaries

| Operation | Draft Status | Posted / Finalized / Reversed Status | Downstream Consistency Controls |
|---|:---:|:---:|---|
| **CREATE (Backdated)** | **Allowed** | **Allowed** | Must validate that operational date falls within an open `FinanceAccountingPeriod` (via `FinancePostingInterface`). Future dates are rejected where prohibited (e.g., payments, expenses). In inventory, running balances are projected across all subsequent dates, and intermediate negative stock is strictly prevented. |
| **UPDATE (Backdated)** | **Allowed** | **STRICTLY PROHIBITED IN PLACE** | Drafts update with optimistic concurrency control (`row_version`). Modifying financial amounts, quantities, dates, currencies, items, or accounts on posted/finalized records in place is blocked at the model level via `booted()` lifecycle hooks throwing `LogicException`. All operational adjustments must be separate linked audit records (credit notes, debit notes, adjustment entries). |
| **DELETE (Backdated)** | **Allowed** | **STRICTLY PROHIBITED** | Drafts may be safely archived or deleted. Deleting posted, approved, or finalized transactions orphans general ledger entries and stock movements. Blocked at the model level via `booted()` hooks throwing `LogicException`. Governed reversals or cancellations must be used. |

---

## 3. Remediated Defects & Implemented Guardrails

### 3.1 Baseline Migrations (Axiom 7 Compliance)
- **`2026_06_12_150008_create_invoice_credit_allocations_table.php`**: Replaced `cascadeOnDelete()` with `restrictOnDelete()` on the `invoices` foreign key constraint to comply with historical retention.
- **`2026_06_12_150006_create_invoice_adjustment_allocations_table.php`**: Replaced `cascadeOnDelete()` with `restrictOnDelete()` on the `invoices` foreign key constraint.

### 3.2 Dynamic Point-in-Time Aging Query (`AgingReportService.php`)
- Corrected lines 100–140: Replaced queries filtering by physical ingestion timestamps (`created_at`, `updated_at`) with joins against operational documents (`invoices.invoice_date`, `sale_returns.return_date`).
- Ensured that credit notes and return credits effective after an `$asOfDate` do not prematurely diminish receivables as of the reporting cutoff date, preserving temporal reporting accuracy.

### 3.3 Inventory UOM Reversal Temporal Lookup (`InventoryMovementReversalService.php`)
- Fixed line 123: Replaced `where('applied_at', '>=', $movement->created_at)` with `whereDate('applied_at', '>=', $movementDate)` where `$movementDate = $movement->movement_date?->toDateString()`.
- Guarantees that backdated inventory movements ingested after a UOM revision was applied correctly use the UOM conversion factor active as of the civil movement date.

### 3.4 Multi-Day In-Transit Transfer Receipt Date (`StockTransferService.php`)
- Added optional `?string $receivedDate = null` parameter to `StockTransferService::receive()` and validated `$effectiveDate >= $transfer->transfer_date`.
- Enables recording the true operational arrival date when goods arrive days or weeks after dispatch.

### 3.5 Model-Level Lifecycle Guardrails (Throwing `LogicException`)
Added status-aware `updating()` and `deleting()` lifecycle hooks across core transactional models:
- **`Invoice`**: `updating()` blocks modifications to immutable financial attributes (`invoice_number`, `invoice_date`, `currency_id`, `party_id`, `subtotal`, `grand_total`, etc.) once status is beyond `Draft`. `deleting()` blocks deletion on non-draft records.
- **`InvoiceLine`**: Blocks modification or deletion if parent invoice is non-draft.
- **`Payment`**: `updating()` blocks changes to `total_amount`, `payment_date`, `currency_id`, etc. once document status is not `Draft`. `deleting()` blocks archiving non-draft payments.
- **`PaymentLine`**: Blocks deleting lines of non-draft payments.
- **`PaymentAllocation`**: Blocks deletion and prevents in-place changes to allocated amounts or targets.
- **`Expense`**: Blocks deletion of expenses and prevents in-place mutations on posted or reversed expenses.
- **`GoodsReceiptNote` & `GoodsReceiptNoteLine`**: Blocks deletion and mutation of financial/quantity fields on non-draft GRNs.
- **`PurchaseOrder`**: Blocks deletion of approved or received orders.
- **`PurchaseReturn` & `PurchaseDebitNote`**: Blocks deletion of non-draft returns and debit notes.
- **`Sale` & `SaleLine`**: Blocks deletion or in-place modification once `posted_at` is set.
- **`SaleReturn`**: Blocks deletion once `posted_at` is set.
- **`InventoryTransfer` & `InventoryAdjustment`**: Blocks deletion of non-draft transfers and stock adjustments.
- **`VehicleServiceJob` & `VehicleServiceJobLine`**: Blocks deletion of completed or invoiced jobs and protects service job lines from deletion once billed/invoiced.

---

## 4. Modified Files Summary

| File Path | Description of Changes |
|---|---|
| `app/Modules/Invoice/Database/Migrations/2026_06_12_150008_create_invoice_credit_allocations_table.php` | Changed `invoices` foreign key from `cascadeOnDelete()` to `restrictOnDelete()`. |
| `app/Modules/Invoice/Database/Migrations/2026_06_12_150006_create_invoice_adjustment_allocations_table.php` | Changed `invoices` foreign key from `cascadeOnDelete()` to `restrictOnDelete()`. |
| `app/Modules/Finance/Services/AgingReportService.php` | Joined operational documents for credit and adjustment allocations to use civil business dates instead of physical `created_at`. |
| `app/Modules/Purchase/Services/PurchaseGoodsReceiptPostingCoordinator.php` | Propagated `$reversalDate` parameter to `GoodsReceiptNoteService::reverse()`. |
| `app/Modules/Purchase/Services/GoodsReceiptNoteService.php` | Forwarded `$reversalDate` to inventory integration service to eliminate GL/Inventory temporal divergence. |
| `app/Modules/Inventory/Services/InventoryMovementReversalService.php` | Replaced physical `created_at` with operational `movement_date` for UOM revision lookups. |
| `app/Modules/Inventory/Services/StockTransferService.php` | Added optional `$receivedDate` parameter to `receive()` for accurate in-transit arrival dates. |
| `app/Modules/Invoice/Models/Invoice.php` | Added `updating()` hook blocking in-place financial mutations on posted/paid invoices. |
| `app/Modules/Invoice/Models/InvoiceLine.php` | Added `updating()` and `deleting()` hooks protecting lines of non-draft invoices. |
| `app/Modules/Payment/Models/Payment.php` | Added `updating()` hook blocking in-place mutations on non-draft payments. |
| `app/Modules/Payment/Models/PaymentLine.php` | Added `deleting()` hook protecting lines of non-draft payments. |
| `app/Modules/Payment/Models/PaymentAllocation.php` | Added `updating()` and `deleting()` hooks protecting allocation amounts and targets. |
| `app/Modules/Expense/Models/Expense.php` | Added `updating()` and `deleting()` hooks preventing direct deletion or financial edits on posted expenses. |
| `app/Modules/Purchase/Models/GoodsReceiptNote.php` | Added `updating()` and `deleting()` hooks protecting posted GRNs. |
| `app/Modules/Purchase/Models/GoodsReceiptNoteLine.php` | Added `deleting()` hook protecting lines of non-draft GRNs. |
| `app/Modules/Purchase/Models/PurchaseOrder.php` | Added `deleting()` hook protecting approved/closed purchase orders. |
| `app/Modules/Purchase/Models/PurchaseReturn.php` | Added `deleting()` hook protecting non-draft purchase returns. |
| `app/Modules/Purchase/Models/PurchaseDebitNote.php` | Added `deleting()` hook protecting non-draft debit notes. |
| `app/Modules/Selling/Models/Sale.php` | Added `updating()` and `deleting()` hooks protecting posted sales. |
| `app/Modules/Selling/Models/SaleLine.php` | Added `deleting()` hook protecting lines of posted sales. |
| `app/Modules/Selling/Models/SaleReturn.php` | Added `deleting()` hook protecting posted sale returns. |
| `app/Modules/Inventory/Models/InventoryTransfer.php` | Added `deleting()` hook protecting non-draft inventory transfers. |
| `app/Modules/Inventory/Models/InventoryAdjustment.php` | Added `deleting()` hook protecting non-draft stock adjustments. |
| `app/Modules/VehicleService/Models/VehicleServiceJob.php` | Added `deleting()` hook protecting completed, invoiced, and paid vehicle service jobs. |
| `app/Modules/VehicleService/Models/VehicleServiceJobLine.php` | Added `deleting()` hook protecting lines of invoiced and paid service jobs. |
| `tests/Feature/Core/BackdatedCrudSafetyTest.php` | Expanded test suite with comprehensive test methods covering lifecycle guardrails, GRN reversal date propagation, credit allocation operational dates, and UOM revision reversal dates. |
| `AGENTS.md` | Codified permanent rules for Dual-Timestamp Architecture and Backdated CRUD Safety. |
| `.agents/skills/autoerp-engineering/SKILL.md` | Updated engineering procedures with permanent Backdated CRUD rules. |

---

## 5. Verification & Quality Gate Results

| Quality Gate | Tooling / Command | Result |
|---|---|---|
| **Backdated CRUD Safety Suite** | `php artisan test tests/Feature/Core/BackdatedCrudSafetyTest.php` | **PASS (13 passed, 67 assertions)** |
| **Purchase Engine Suite** | `php artisan test app/Modules/Purchase/Tests/PurchaseEngineTest.php` | **PASS (38 passed, 174 assertions)** |
| **Payment Crud & Settlement Suite** | `php artisan test app/Modules/Payment/Tests/PaymentCrudTest.php app/Modules/Payment/Tests/PaymentSettlementServiceTest.php` | **PASS (12 passed, 39 assertions)** |
| **Expense & Invoice Settlement Suite** | `php artisan test app/Modules/Invoice/Tests/InvoiceSettlementServiceTest.php app/Modules/Expense/Tests/ExpensePostingTest.php` | **PASS (5 passed, 42 assertions)** |
| **Baseline Migration Integrity** | `php artisan test --filter ModuleMigrationBaselineTest` | **PASS (1 passed, 3 assertions)** |
| **Historical Retention Integrity** | `php artisan test --filter HistoricalRetentionMigrationTest` | **PASS (1 passed, 22 assertions)** |
| **Backend Code Style (Pint)** | `php vendor/bin/pint --test <modified-files>` | **PASS (`{"tool":"pint","result":"passed"}`)** |
| **TypeScript Typecheck** | `npm run typecheck` | **PASS (0 errors, exit code 0)** |

---

## 6. Author & Maintainer

- **Lead Architect & Developer:** Kasun Vimarshana
- **Email:** [kasunvmail@gmail.com](mailto:kasunvmail@gmail.com)
- **Phone:** [+94713525253](tel:+94713525253)
- **Repository:** [kasunvimarshana/AutoERP](https://github.com/kasunvimarshana/AutoERP)
