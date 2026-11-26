# Universal Dual-Timestamp & Backdated CRUD Architecture

## Status
Accepted as Core Architectural Standard (Axiom 6 & Domain Authority).

## Lead Architect & Author
- **Lead Architect & Developer:** Kasun Vimarshana
- **Email:** [kasunvmail@gmail.com](mailto:kasunvmail@gmail.com)
- **Phone:** [+94713525253](tel:+94713525253)
- **Repository:** [AutoERP](https://github.com/kasunvimarshana/AutoERP)
- **Date:** 2026-11-26

---

## 1. Executive Summary & Core Principles

In enterprise resource planning (ERP) systems operating under GAAP and IFRS financial accounting principles, naive in-place backdated `UPDATE` and `DELETE` operations on finalized operational or financial records are strictly invalid. Financial statements, operational ledgers, inventory valuation queues, and regulatory audit trails require chronological immutability. 

AutoERP adopts a **Universal Dual-Timestamp Architecture** coupled with **Governed Linked Adjustments** to provide complete, robust backdating support across all CRUD pathways without compromising data integrity, legal compliance, or audit defensibility.

### Core Architectural Invariants
1. **Strict Timestamp Separation**:
   - **Physical Server Recording Timestamps** (`created_at`, `posted_at`, `reversed_at`, `updated_at`, `deleted_at`): Authoritative, immutable server-owned audit facts (`now()`). Physical ingestion timestamps are never overridden by client inputs.
   - **Civil Operational Business Dates** (`invoice_date`, `movement_date`, `received_date`, `layer_date`, `payment_date`, `journal_date`, `voucher_date`, `job_date`, `punch_time`): User-entered or business-event dates governing accounting periods, chronological valuation queues, tax mapping, point-in-time aging, and ledger sequencing.
2. **Backend Authority (Axiom 5)**:
   - All temporal validations, period assertions, running balance recalculations, and immutability rules are strictly enforced in backend service layers and Eloquent model lifecycle hooks (`booted()`).
3. **Optimistic Concurrency Control (OCC) (Axiom 6)**:
   - Draft records and configuration entities enforce `row_version` with caller-provided `expected_version` to prevent concurrent write collisions.
4. **Governed Adjustment Pattern (Axiom 6)**:
   - Posted, finalized, or approved records are permanently immutable in place. Corrections are executed through linked reciprocal audit documents (Credit Notes, Debit Notes, Stock Adjustments, Attendance Adjustments, Retro Pay Adjustments, or Governed Reversals).
5. **Point-in-Time Reconstruction (Fidelity Principle)**:
   - Historical reports, balances, and agings filter and sequence strictly by civil operational business dates, dynamically unwinding downstream payments, credit allocations, and reversals that occurred after the cutoff date.

---

## 2. Universal Backdated CRUD Behavioral Contract

The table below defines the authoritative system-wide behavioral contract across all entities and modules in AutoERP:

| Operation | Draft Status | Posted / Finalized / Approved Status | Master Data / Reference Entities | Downstream Consistency & Integrity Controls |
|---|:---:|:---:|:---:|---|
| **CREATE (Backdated)** | **Allowed** | **Allowed** | **Allowed** *(via Effective Dating)* | 1. **Accounting Period Assertion**: Verified via `AccountingPeriodService::assertPostingDateAllowed`.<br>2. **Temporal Causality**: Downstream civil date cannot precede upstream date (`reversalDate >= origDate`; `receivedDate >= poDate`; `returnDate >= grnDate`; `debitNoteDate >= returnDate`; `dueDate >= invoiceDate`).<br>3. **Negative Stock Projection**: Backdated stock issues check prior balance, live balance, AND all subsequent movement balances (`InventoryMovementPoster`).<br>4. **Sequence Partitioning**: Document numbers partition into period buckets based on civil date (`{PERIOD}` in `InvoiceNumberService`, `ExpenseNumberService`, `JournalNumberService`, `PaymentNumberService`).<br>5. **Cash Fraud Guardrail**: Backdated cash receipts require `PAYMENTS_BACKDATE_CASH` permission + mandatory justification notes (`PaymentCashReceiptDatePolicy`). |
| **READ (Point-in-Time)** | **Allowed** | **Allowed** | **Allowed** *(Temporal Filter)* | 1. **Civil Date Sequencing**: Must filter and sequence by operational civil dates (`<= $asOfDate`), never by physical `created_at` or `updated_at`.<br>2. **Dynamic Unwinding**: Point-in-time aging and stock cards reconstruct historical state by unwinding payments, credits, UOM revisions, and reversals dated after the cutoff date (`AgingReportService`, `StockCardService`, `AccountBalanceService`). |
| **UPDATE (Backdated)** | **Allowed** | **STRICTLY PROHIBITED IN PLACE**<br>*(Throws `LogicException`)* | **Allowed** *(Drafts) / Versioned (Effective)* | 1. **Drafts**: Editable with optimistic concurrency control (`row_version` asserting `expected_version`).<br>2. **Posted Records**: In-place edits on core financial/operational attributes are blocked at the database model level via `booted()` lifecycle hooks throwing `LogicException`.<br>3. **Governed Adjustment Pattern**: Corrections executed via linked audit documents: **Credit Notes**, **Debit Notes**, **Stock Adjustments**, **Attendance Adjustments**, or **Retro Pay Arrears**.<br>4. **Governed Reversal & Reissue**: Reverse original record on/after its operational date, then issue a corrected backdated transaction. |
| **DELETE (Backdated)** | **Allowed** | **STRICTLY PROHIBITED IN PLACE**<br>*(Throws `LogicException`)* | **Restricted** *(Foreign Key Restraints)* | 1. **Drafts**: Safely deletable / soft-deletable.<br>2. **Posted Records**: In-place deletions strictly blocked by model `booted()` hooks throwing `LogicException`.<br>3. **Audit Retention**: History and ledger tables enforce `restrictOnDelete()` on all foreign keys (`HistoricalRetentionMigrationTest`).<br>4. **Governed Cancellation**: Only governed reversal workflows are permitted (`reverse()`). |

---

## 3. Subsystem Technical Architecture & Invariants

### 3.1 Finance & General Ledger (`app/Modules/Finance`)
- **Ledger Balance Projection (`LedgerBalanceProjectionService`)**:
  When a backdated journal entry is posted at date $T_{past}$, `rebuildForAccounts` obtains pessimistic row locks (`lockForUpdate()`) on all affected accounts in tenant scope. It fetches all ledger entries where `entry_date >= $earliestDate` ordered by `entry_date ASC, id ASC`, and recomputes the forward running balance (`balance_after`). To update projections without violating the Eloquent model immutability hook (`FinanceLedgerEntry::updating`), it intentionally executes raw query builder updates (`DB::table('finance_ledger_entries')->where('id', ...)->update(...)`).
- **Accounting Period Enforcement (`AccountingPeriodService`)**:
  `assertPostingDateAllowed` checks whether the target civil `postingDate` falls within an open `FinanceAccountingPeriod`. Scope resolution first queries for an organizational unit period (`organization_scope_key = $organizationUnitId`), falling back to tenant-level (`organization_scope_key = 0`). If the period is closed, an `InvalidArgumentException` is thrown, halting the transaction.
- **Journal Reversal Temporal Guard (`JournalReversalService`)**:
  Enforces `if ($reversalDate < $journal->journal_date->toDateString()) throw new InvalidArgumentException(...)`. Posts an inverse reciprocal journal entry dated `$reversalDate`, preserves the original entry untouched, and sets `reversed_at = now()`.
- **Dynamic Point-in-Time Account Balances (`AccountBalanceService`)**:
  Point-in-time trial balances and general ledger reports filter entries using `whereDate('entry_date', '<=', $dateTo)`. Entries with `entry_date < $dateFrom` form the opening balance, while entries in the interval form period movements. Physical ingestion times have zero impact on the report.

### 3.2 Inventory Management (`app/Modules/Inventory`)
- **Multi-Period Intermediate Negative Stock Check (`InventoryMovementPoster`)**:
  Posting a backdated inventory issue at date $T_{past}$ presents a critical risk: stock might be sufficient at $T_{past}$ and today, but intermediate stock issues on dates $T_{intermediate}$ ($T_{past} < T_{intermediate} \le T_{today}$) could drop below zero. AutoERP solves this by evaluating:
  1. Historical stock balance as of $T_{past}$ (`$priorBalanceQty >= $quantity`).
  2. Live stock balance today (`$balance->quantity_available >= $quantity`).
  3. Every subsequent movement's forward running balance (`$subsequent->balance_quantity_after >= $quantity`).
  If any balance would breach zero, the issue is rejected with `InvalidArgumentException('Inventory issue would cause negative stock in subsequent periods.')`.
- **Valuation Layer FIFO Queue (`AbstractLayerValuationMethod`)**:
  Valuation layers store `layer_date = $movement->movement_date`. The FIFO consumption query orders layers strictly by `layer_date ASC, created_at ASC, id ASC`. When backdated stock receipts are posted today, they are inserted chronologically into the historical consumption queue, preserving valuation accuracy.
- **Backdated Physical Stock Adjustments (`StockAdjustmentService`)**:
  When drafting a physical inventory adjustment for date $T_{count}$, `historicalStockOnHand` calculates system stock as of $T_{count}$ (`whereDate('movement_date', '<=', $asOfDate)`). When posting, it re-verifies that the historical baseline has not drifted due to concurrent backdated entries, throwing `InvalidArgumentException` if concurrency drift is detected.
- **Chronological UOM Revision Evaluation (`StockCardService`)**:
  Unit of Measure (UOM) revision factors track `applied_at` civil dates. When building stock cards, `currentQuantity` evaluates whether `$appliedDate < $movementDate`. If a movement occurred *after* a UOM revision, it was already recorded in the revised UOM; if it occurred *before*, it is converted using the historical factor.

### 3.3 Invoicing & Debtors / Creditors (`app/Modules/Invoice`)
- **Temporal Validations (`InvoiceValidationService`)**:
  Enforces `dueDate >= invoiceDate` and `supplyPeriodEnd >= supplyPeriodStart`.
- **Tax Mapping Fidelity (`InvoiceTaxDocumentMapper`)**:
  Maps `sourceDate` and `transactionDate` to `invoice_date->toDateString()`, ensuring tax returns reflect civil transaction dates rather than physical system entry timestamps.
- **Reversal Date Policy (`InvoiceReversalService`)**:
  Enforces dual checks: `reversalDate >= invoiceDate` AND `reversalDate >= postingDate`. Rejects any reversal attempting to un-post an invoice prior to its creation date.
- **Dynamic Point-in-Time Aging (`AgingReportService`)**:
  Reconstructs receivables/payables dynamically as of any `$asOfDate`:
  - Payment allocations effective after `$asOfDate` are unwound.
  - Credit note allocations dated after `$asOfDate` are unwound.
  - Invoices reversed after `$asOfDate` are treated as active outstanding obligations as of `$asOfDate`.

### 3.4 Payments, Cash Receipts & Realized FX (`app/Modules/Payment`)
- **Cash Receipt Date Policy & Fraud Guard (`PaymentCashReceiptDatePolicy`)**:
  Future-dated payments are prohibited (`paymentDate > today`). Backdated cash receipts present audit and embezzlement vulnerabilities; therefore, AutoERP enforces:
  1. Mandatory permission check for `PAYMENTS_BACKDATE_CASH`.
  2. Mandatory business justification in `notes` or `backdate_reason`.
- **Realized FX Gain/Loss on Backdated Settlements (`PaymentAllocationFinanceService`)**:
  When allocating a multi-currency payment to an invoice where exchange rates differ, realized exchange gains or losses are calculated:
  $$\Delta_{FX} = \text{Allocation Amount} \times (\text{Payment Rate} - \text{Invoice Rate})$$
  The posting date is resolved as `$allocation->allocation_date ?? $payment->payment_date ?? now()->toDateString()`. This date is passed to `FinancePostingService` and strictly checked against open accounting periods.
- **Payment Reversal Propagation (`PaymentReversalService`)**:
  Enforces `reversalDate >= paymentDate`. Reverses linked financial journals, restores invoice outstanding balances, reverses realized FX entries, and stamps `reversed_at = now()`.

### 3.5 Purchasing & Procurement (`app/Modules/Purchase`)
- **Temporal Causality Chain**:
  The procurement pipeline enforces chronological progression across civil business dates:
  1. `purchase_order_date` (PO creation).
  2. `received_date >= purchase_order_date` (Goods Receipt Note).
  3. `return_date >= received_date` (Purchase Return).
  4. `debit_note_date >= return_date` (Purchase Debit Note).
- **Goods Receipt Reversal Coordination (`PurchaseGoodsReceiptPostingCoordinator`)**:
  Enforces `reversalDate >= received_date`. Synchronizes the reversal of GRN status, inventory movements (`InventoryMovementReversalService`), and finance GL postings (`FinancePostingService`).

### 3.6 Selling & POS (`app/Modules/Selling`)
- **Operational Date Propagation (`SalePostingService`)**:
  When a sale is finalized, the civil `sale_date` is propagated consistently across all downstream artifacts:
  - Inventory issue movement date (`StockMovementData::$movementDate`).
  - Historical item price resolution date (`resolvePrice(..., date: $saleDate)`).
  - Tax calculation date (`TaxCalculationData::$documentDate`).
  - Sales invoice civil date (`CreateInvoiceData::$invoiceDate`).
  - General ledger posting date (`InvoicePostingPlan::$postingDate`).
  Physical `posted_at` is stamped with server `now()`.
- **Sale Returns (`SaleReturnPostingService`)**:
  Enforces `return_date >= sale->sale_date`. Propagates `return_date` to return stock movements and credit invoice postings.

### 3.7 Document Sequence Number Partitioning (`app/Modules/Sequence`)
- **Civil Period Resolution (`GenerateSequenceNumberService`)**:
  Sequence generation accepts an optional `at_date` parameter. For sequences partitioned by year or month (e.g. `INV-{PERIOD}-{SEQ:6}`), `{PERIOD}` is evaluated based on `at_date`:
  - An invoice dated in `2025` entered in `2026` receives a number from the `2025` bucket (`INV-2025-000142`), preventing gaps and sequence corruption in `2026` numbers (`INV-2026-000001`).

### 3.8 Human Resources, Attendance & Biometrics (`app/Modules/Hr`)
- **Biometric Punch Ingestion (`BiometricLogIngestionService`)**:
  Separates physical hardware capture time (`punch_time`) from server ingestion time (`received_at`). Offline biometric devices syncing days later transmit historical `punch_time` stamps.
- **Finalized Period Lock Protection (`AttendanceCalculationEngine`)**:
  If punches arrive for an attendance period marked `AttendanceLockStatus::Finalized`, the engine throws `InvalidArgumentException('Cannot recalculate finalized attendance records.')`. The ingestion service catches this, marks the raw punch `sync_status = Failed`, preserving raw hardware evidence without modifying finalized attendance records.
- **Attendance Regularization (`AttendanceRegularizationService`)**:
  Regularizations cannot modify finalized periods. Approved regularizations create linked `HrAttendanceAdjustment` records.

### 3.9 Payroll & Retroactive Pay (`app/Modules/Payroll`)
- **Payslip Immutability (`PayrollPayslip`)**:
  Approved and paid payslips are immutable in place and cannot be deleted (`LogicException`).
- **Retroactive Pay Arrears Engine (`PayrollRetroactivePayService`)**:
  When salary adjustments or promotions take effect retroactively, `calculateSalaryArrears` queries historical paid payslips, computes arrears deltas, and creates a linked `PayrollAdjustment` of type `RetroPay` in the next open pay run. Historical payslips remain untouched.

### 3.10 Vehicle Rental & Fleet Management (`app/Modules/VehicleRental`)
- **Monotonic Odometer & Custody Enforcement (`VehicleUseService`)**:
  Handover and return operations accept civil dates (`return_date >= handover_date`). Odometer validation asserts non-decreasing readings, preventing backdated returns from corrupting historical mileage logs.

### 3.11 Unified Presentation Voucher Register (`app/Modules/Voucher`)
- **Multi-Source Aggregation (`VoucherQueryService`)**:
  Aggregates payment vouchers, journal entries, and payment reversals into a unified presentation ledger. Queries filter and sequence by `voucher_date` (`entry_date` / `payment_date`), ensuring consistent point-in-time financial registers regardless of entry sequence.

### 3.12 Audit Subsystem (`app/Modules/Audit`)
- **Dual-Timestamp Audit Schema (`audit_logs`)**:
  Schema explicitly separates `occurred_at` (civil business date/time) from `recorded_at` (`timestamp useCurrent()`). Audit log queries index and filter by `occurred_at` for accurate historical forensic reconstruction.

---

## 4. Master Data: Effective-Dating vs. Snapshotting Patterns

To preserve historical accuracy across backdated operations, AutoERP strictly distinguishes between document snapshots and master data effective-dating:

```
+-----------------------------------------------------------------------------------+
|                              MASTER DATA ARCHITECTURE                             |
+-----------------------------------------------------------------------------------+
| 1. DOCUMENT SNAPSHOT PATTERN                      2. TEMPORAL EFFECTIVE-DATING    |
|    (Operational Facts & Legal Records)               (Rules, Rates & Conversions) |
|                                                                                   |
|    - Parties: Customer/Supplier Name,               - Item Prices:                |
|      Address, Tax Registration                        effective_from / to         |
|    - Currency: Exchange Rate Snapshots              - Tax Rates:                  |
|    - Line Metadata: Item Name, SKU,                   effective_from / to         |
|      Historical UOM Code                            - Posting Profile Rules:      |
|                                                       effective_from / to         |
|    Frozen at document creation time.                - UOM Conversion Revisions:   |
|    Never mutated by subsequent changes.               applied_at civil evaluation |
+-----------------------------------------------------------------------------------+
```

1. **Historical Document Snapshot Pattern**:
   Financial documents (`InvoiceDocumentSnapshot`, `InvoiceLine::metadata`, `PaymentLine::payment_method_type_snapshot`) capture frozen master data values at creation time. Historical invoices always display the legal party name, tax registration number, and address applicable when the document was issued, even if the party updates their profile today.
2. **Temporal Effective-Dating Pattern**:
   Configuration tables governing calculations (`item_prices`, `tax_rates`, `finance_posting_profile_rules`) maintain `effective_from` and `effective_to` civil dates. Backdated transactions resolve pricing and tax rules using their civil operational date (`$documentDate`), ensuring that retroactive entries compute based on the rules in effect on that date.

---

## 5. Blueprint: Fixed Assets Module (IAS 16 / IAS 8 Compliance)

When implementing the upcoming `FixedAssets` module, developers must comply with the following GAAP/IFRS standards:

1. **Backdated Asset Capitalization**:
   - Record `acquisition_date` (civil date) and `depreciation_start_date`. Physical `created_at` remains server `now()`.
   - Asset capitalization journal posts to GL dated `acquisition_date` via `FinancePostingService`, asserting `AccountingPeriodService::assertPostingDateAllowed`.
2. **Retroactive Depreciation Handling**:
   - **Open Period**: Depreciation is computed and posted into each respective open monthly period.
   - **Closed Period (Prior Fiscal Year)**: Under IAS 8 / ASC 250, closed periods are never altered in place. The system must calculate cumulative catch-up depreciation and post a single adjusting entry in the current open period (or prior period adjustment to Opening Retained Earnings if material and authorized by financial controller).
3. **Disposal Immutability**:
   - Disposals enforce `disposal_date >= acquisition_date`. Depreciation cannot run on dates subsequent to `disposal_date`.
   - Asset capitalization and depreciation journals are strictly immutable once posted.

---

## 6. Implementation Guidelines & Developer Checklist

All new modules and transactions must implement the **Canonical 5-Point Backdated CRUD Checklist**:

```markdown
### Backdated CRUD & Temporal Integrity Checklist
- [ ] 1. **Dual Timestamps**: Physical timestamps (`created_at`, `posted_at`, `reversed_at`, `updated_at`, `deleted_at`) are strictly server-owned (`now()`). Civil business dates (`*_date`) are client-provided and validated.
- [ ] 2. **Accounting Period & Temporal Causality**:
  - Validates `AccountingPeriodService::assertPostingDateAllowed(..., $operationalDate)`.
  - Rejects future dates where prohibited (cash payments, expenses, returns).
  - Enforces `$downstreamDate >= $upstreamDate` across the lifecycle.
- [ ] 3. **Intermediate Running Balances**:
  - Multi-period projections verify that intermediate balances on any subsequent date never breach constraints (e.g. negative stock in `InventoryMovementPoster`).
- [ ] 4. **Model Lifecycle Immutability**:
  - `booted()` lifecycle hooks throw `LogicException` on in-place `updating` or `deleting` of posted/approved/finalized records.
  - Corrections are handled via linked audit documents or governed reversals (`reverse()`).
- [ ] 5. **Point-in-Time Query Fidelity**:
  - Reports and registers filter and sequence strictly by civil operational dates (`whereDate('entry_date', '<=', $asOfDate)`), unwinding downstream reversals and allocations effective after `$asOfDate`.
```

### Standard Model Immutability Pattern
```php
namespace App\Modules\Shared\Models;

use App\Modules\Shared\Enums\DocumentStatus;
use Illuminate\Database\Eloquent\Model;
use LogicException;

abstract class GovernedTransactionalModel extends Model
{
    protected static function booted(): void
    {
        self::updating(static function (self $model): void {
            if (! $model->isDirty('row_version')) {
                $model->row_version = ((int) $model->getOriginal('row_version', 1)) + 1;
            }

            $originalStatus = $model->getOriginal('status');
            if ($originalStatus !== null && $originalStatus !== DocumentStatus::Draft) {
                $immutableAttributes = [
                    'document_number',
                    'document_date',
                    'total_amount',
                    'currency_id',
                    'party_id',
                ];

                if ($model->isDirty($immutableAttributes)) {
                    throw new LogicException(
                        'Posted transactions are immutable in place. Use credit notes or governed reversals.'
                    );
                }
            }
        });

        self::deleting(static function (self $model): void {
            if ($model->status !== DocumentStatus::Draft) {
                throw new LogicException(
                    'Only draft documents can be deleted. Use governed reversals.'
                );
            }
        });
    }
}
```

---

## 7. Known Anti-Patterns & Remediation Notes

1. **Promotion Qualification Wall-Clock Leakage**:
   - *Current State*: In [`PromotionRuntimeService.php#L52`](file:///d:/project-new/AutoERP/app/Modules/Promotion/Services/PromotionRuntimeService.php#L52), promotion qualification checks `$today = now()->toDateString()` instead of the vehicle service `job_date`.
   - *Remediation*: Pass `?string $asOfDate = null` (defaulting to `$job->job_date`) into `JobPromotionHandlerInterface::availableCards` so backdated jobs qualify for promotions that were active on the job date.
2. **Three-State Accounting Period Lifecycle**:
   - *Enhancement*: Extend `FinanceAccountingPeriod` from binary `Open`/`Closed` to `Open`, `SoftClosed`, and `HardClosed`. `SoftClosed` allows authorized financial controllers to post adjusting journals while blocking general operational entries.

---

## 8. Verification & Test Evidence

The dual-timestamp and backdated CRUD architecture is actively validated by comprehensive automated test suites:
- **`Tests\Feature\Core\BackdatedCrudSafetyTest`**: 13 passed tests (tax document mapping, reversal before posted_at, FIFO layer ordering, running balance projections, intermediate negative stock prevention, reversed movement stability, future date rejection, historical stock validation, point-in-time aging, and model immutability hooks).
- **`Modules\Finance\Tests\FinanceEngineTest`**: 9 passed tests (ledger balance projections, foreign currency journals, balanced reversals, and backdated posting projections).
- **`Modules\Inventory\Tests\StockCardTest`**: 7 passed tests (point-in-time stock cards, reversals, opening balances, workspace timezone resolution, and cascading UOM revisions).

Command:
```bash
php artisan test --filter "BackdatedCrudSafetyTest|FinanceEngineTest|StockCardTest"
```
**Result**: 29 passed (169 assertions).
