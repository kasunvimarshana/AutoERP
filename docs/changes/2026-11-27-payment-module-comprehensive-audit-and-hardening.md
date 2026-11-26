# Payment Module Comprehensive Audit & Hardening

**Author:** Kasun Vimarshana (<kasunvmail@gmail.com>)  
**Date:** 2026-11-27  
**Scope:** Payment Module, End-to-End Lifecycle, Database Schema, Models, Services, API, UI, and Cross-Module Integration

---

## 1. Context & Motivation

A comprehensive architectural and functional end-to-end audit of payment operations and related CRUD workflows was conducted across the codebase. The audit inspected database schemas, lifecycle state transitions, dual-timestamp compliance, concurrency guards (`row_version`), backend validation, exception handling, receipt generation, and frontend panel bindings.

Key vulnerabilities, architectural shortcomings, and design discrepancies were identified and resolved at root cause:
1. **Uncaught Fatal Error in `PaymentMethodService.php`**: `ConflictHttpException` was thrown on optimistic version conflict without being imported, causing fatal `Class "Modules\Payment\Services\ConflictHttpException" not found`.
2. **Runtime `ArgumentCountError` in `InvoicePaymentIntegrationService.php`**: `PaymentAllocationService::allocate` requires 4 parameters (`$payment`, `$allocations`, `$expectedVersion`, `$actorId`), but `InvoicePaymentIntegrationService` forwarded only 2 parameters.
3. **Invalid Table References, Incomplete Party Resolution & Multi-Tenant Isolation Leak in `PaymentReceiptService.php`**:
   - Receipt agreement reference lookup queried nonexistent tables `customer_agreements` and `owner_agreements` instead of `vehicle_rental_customer_agreements` and `vehicle_rental_owner_agreements`.
   - Raw party fallback query omitted tenant scoping (`->where('tenant_id', $payment->tenant_id)`).
   - Inbound payment direction check risked fatal error when direction is passed as string.
   - String concatenation added redundant `' Only'` to `AmountInWordsService::convert()` output, which already appends `' Only'`.
   - Party resolution failed for employee parties (such as staff advances and payroll disbursements), as only `customers` and `suppliers` were checked without checking `hr_employees`.
4. **Floating Point Precision Bug in `PaymentRefundPolicyService.php`**: `reservedUnpostedAmount` invoked database `->sum('amount')` which returns IEEE-754 binary floats on SQLite/MySQL, violating the strict `DecimalMath` streaming standard used across money operations.
5. **Missing Lifecycle Immutability & Snapshot Tampering Protection (Rule 6 violations)**:
   - `Payment`: Allowed in-place mutation of legal party snapshots (`party_name_snapshot`, `party_code_snapshot`, `party_email_snapshot`, `party_phone_snapshot`), currency snapshots (`currency_code_snapshot`, `currency_symbol_snapshot`), and source pointers on non-draft payments.
   - `PaymentLine`: Allowed in-place mutation of core financial attributes (`amount`, `payment_method_id`, `line_number`, `organization_unit_id`) and payment method snapshots (`payment_method_name_snapshot`, `payment_method_code_snapshot`, `payment_method_type_snapshot`, `requires_reference_snapshot`, `requires_instrument_details_snapshot`) on non-draft parent payments.
   - `PaymentAllocation`: Historical snapshot columns (`invoice_number_snapshot`, `invoice_date_snapshot`, `invoice_currency_code_snapshot`, `invoice_exchange_rate_snapshot`, `invoice_total`, `invoice_balance_before`, `invoice_balance_after`, `previously_allocated_amount`, `allocation_method`, `organization_unit_id`) lacked in-place mutation protection once active or reversed.
   - `PaymentUnappliedBalance`: Financial balance records lacked deletion guards on approved/posted payments.
   - `ChequePrintLog`: Audit print logs had no immutable lifecycle guards blocking in-place updates or deletions.
   - `PaymentMethod`: Permitted deletion of payment methods having recorded historical payment transactions.
   - `ChequeTemplate`: Permitted deletion of default templates or templates with recorded print logs.
6. **Orphaned Dead Model `PaymentStatusHistory.php`**:
   - `PaymentStatusHistory.php` pointed to a nonexistent table `payment_status_histories`. The actual active audit event log is `payment_lifecycle_events` handled by `PaymentLifecycleEvent.php`. The dead model was removed in accordance with Axiom 3.
7. **Enum Direct Strict Equality Comparison Risks**:
   - `PaymentAllocationStateService`, `PaymentCapabilityService`, and `PaymentCashReceiptDatePolicy` compared `$payment->payment_type` directly without normalizing strings to `PaymentType`.
8. **Frontend Display & Safety Issues**:
   - `PaymentDetailPage.tsx`: History table columns were mapped to nonexistent `['occurred_at', 'event_type', 'from_document_status', 'to_document_status', 'reason']` instead of `['occurred_at', 'dimension', 'from_state', 'to_state', 'reason']`.
   - `PaymentDetailPage.tsx`: Refunds and reversals tables requested nonexistent `status` columns, rendering blank headers. `PaymentResource` was enhanced to provide structured refund payment numbers and statuses, and reversal finance journal references.
   - `PaymentDetailPage.tsx`: Missing "PDF receipt" action button in document header.
   - `PaymentMethodListPage.tsx`: Deleting a payment method lacked confirmation, allowing accidental destructive clicks. Added `useConfirmDialog`.
   - `PaymentMethodFormPage.tsx`: Update mutations omitted `expected_version`, bypassing optimistic concurrency version check on payment method edit.
   - `PaymentAllocationReversalPanel.tsx`: Lack of empty select option caused UI mismatch between default state and selected option.

---

## 2. Changes Made

### Backend

1. **`app/Modules/Payment/Services/PaymentMethodService.php`**:
   - Imported `Symfony\Component\HttpKernel\Exception\ConflictHttpException`.
2. **`app/Modules/Payment/Services/InvoicePaymentIntegrationService.php`**:
   - Extended `allocatePaymentToInvoices` to accept optional `?int $expectedVersion = null, ?int $actorId = null` and defaulted `$expectedVersion ?? (int) $payment->row_version`.
3. **`app/Modules/Payment/Services/PaymentReceiptService.php`**:
   - Safely resolved `PaymentDirection` enum before comparing `$isInbound`.
   - Updated agreement reference lookup to check `Schema::hasTable` and query `vehicle_rental_customer_agreements` / `vehicle_rental_owner_agreements`.
   - Added tenant scope `->where('tenant_id', $payment->tenant_id)` to party fallback query.
   - Added `hr_employees` lookup support for employee disbursement receipts.
   - Removed duplicated `' Only'` concatenation.
4. **`app/Modules/Payment/Services/PaymentRefundPolicyService.php`**:
   - Refactored `reservedUnpostedAmount` to stream exact persisted decimal strings into `$this->math->sum(...)` via cursor, eliminating binary float precision loss.
5. **`app/Modules/Payment/Models/Payment.php`**:
   - Enforced lifecycle immutability on party snapshots (`party_name_snapshot`, `party_code_snapshot`, `party_email_snapshot`, `party_phone_snapshot`), currency snapshots (`currency_code_snapshot`, `currency_symbol_snapshot`), and source links on non-draft payments.
6. **`app/Modules/Payment/Models/PaymentLine.php`**:
   - Added `updating()` hook blocking in-place mutations of `payment_id`, `tenant_id`, `organization_unit_id`, `line_number`, `payment_method_id`, `payment_method_code_snapshot`, `payment_method_name_snapshot`, `payment_method_type_snapshot`, `requires_reference_snapshot`, `requires_instrument_details_snapshot`, and `amount` on non-draft payments.
   - Normalized status check in `deleting()` hook.
7. **`app/Modules/Payment/Models/PaymentAllocation.php`**:
   - Added `updating()` hook guarding all financial and snapshot attributes once active or reversed, while allowing realization to populate balance snapshots on pending allocations.
   - Guarded `deleting()` hook to reject deletions of non-draft or non-pending allocations.
8. **`app/Modules/Payment/Models/PaymentUnappliedBalance.php`**:
   - Added `deleting()` hook preventing deletion of unapplied balance records for non-draft payments.
9. **`app/Modules/Payment/Models/PaymentStatusHistory.php`**:
   - Removed orphaned dead model referencing nonexistent table.
10. **`app/Modules/Payment/Models/ChequePrintLog.php`**:
    - Added `updating: never` and `deleting: never` lifecycle hooks enforcing absolute immutability on audit print logs.
11. **`app/Modules/Payment/Models/PaymentMethod.php`**:
    - Added `deleting()` hook blocking deletion of payment methods that have recorded payment transactions.
12. **`app/Modules/Payment/Models/ChequeTemplate.php`**:
    - Added `deleting()` hook blocking deletion of default templates or templates with recorded print logs.
13. **`app/Modules/Payment/Http/Resources/PaymentResource.php`**:
    - Structured `refunds` to include `refund_payment_number` and `refund_payment_status`.
    - Structured `reversals` to include `finance_reversal_reference`.
14. **`app/Modules/Payment/Services/PaymentAllocationStateService.php`**, **`PaymentCapabilityService.php`**, **`PaymentCashReceiptDatePolicy.php`**:
    - Safely resolved `$payment->payment_type` enum before checks.

### Frontend

1. **`resources/js/modules/payment/pages/PaymentDetailPage.tsx`**:
   - Corrected history table fields to `['occurred_at', 'dimension', 'from_state', 'to_state', 'reason']`.
   - Corrected refunds fields to `['refund_number', 'refund_date', 'amount', 'refund_payment_number', 'refund_payment_status', 'reason']`.
   - Corrected reversals fields to `['reversal_number', 'reversal_date', 'original_amount', 'reversed_amount', 'finance_reversal_reference', 'reason']`.
   - Added "PDF receipt" action button.
2. **`resources/js/modules/payment/pages/PaymentMethodListPage.tsx`**:
   - Added `useConfirmDialog` prompt before deleting payment methods.
3. **`resources/js/modules/payment/pages/PaymentMethodFormPage.tsx`**:
   - Included `expected_version: initialValue.row_version` in update payload.
4. **`resources/js/modules/payment/components/PaymentAllocationReversalPanel.tsx`**:
   - Added `{ value: '', label: 'Select allocation...' }` placeholder option to the allocation dropdown.

### Tests

1. **`app/Modules/Payment/Tests/PaymentHardeningAuditTest.php`** (New):
   - 20 automated test cases (42 assertions) verifying:
     - `PaymentMethodService` conflict exception handling.
     - Agreement reference resolution for vehicle rentals.
     - Line financial and snapshot immutability on approved payments.
     - Allocation financial and snapshot immutability on active allocations.
     - Cheque print log immutability (updating/deleting blocked).
     - Payment method and cheque template deletion guards.
     - `InvoicePaymentIntegrationService` version parameter forwarding.
     - Exact decimal summation in `PaymentRefundPolicyService`.
     - Payment header snapshot immutability on approved payments.
     - Payment unapplied balance deletion guard on approved payments.
     - Employee party information resolution in receipts.
     - Optimistic locking / CAS conflict rejection on payment allocation reversal (stale payment version, stale allocation version, already reversed allocation).
2. **`app/Modules/Payment/Tests/PaymentReceiptTest.php`**:
   - Added assertion verifying receipt words formatting contains a single "Only".

---

## 3. Verification

- `php artisan test --filter PaymentHardeningAuditTest`: 20 passed (42 assertions).
- `php artisan test --filter Payment`: 138 passed (763 assertions) across Payment, Invoice, Finance, Tax, Rental, Purchase, Payroll, and Voucher modules.
- `php artisan test --filter PurchaseEngineTest`: 38 passed (174 assertions).
- `php artisan test --filter VehicleServiceEngineTest`: 81 passed (565 assertions).
- `php artisan test --filter ModuleMigrationBaselineTest`: 1 passed (3 assertions).
- `php artisan test --filter HistoricalRetentionMigrationTest`: 1 passed (22 assertions).
- `vendor/bin/pint --test app/Modules/Payment`: 100% passed with 0 style issues.
- `npm run typecheck`: 0 TypeScript errors.
- `npm run lint`: 0 ESLint errors across `resources/js`.
- `npx vitest run resources/js/modules/payment`: 5 test files passed (12 tests).

---

## Author & Maintainer

- **Lead Architect & Developer:** Kasun Vimarshana
- **Email:** [kasunvmail@gmail.com](mailto:kasunvmail@gmail.com)
- **Phone:** [+94713525253](tel:+94713525253)
- **Repository:** [https://github.com/kasunvimarshana/AutoERP](https://github.com/kasunvimarshana/AutoERP)
