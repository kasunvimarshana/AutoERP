# Invoice Module Deep Architectural Audit & Hardening

**Author:** Kasun Vimarshana (<kasunvmail@gmail.com>)  
**Date:** 2026-11-27  
**Scope:** Invoice Module (`app/Modules/Invoice`, `resources/js/modules/invoice`), Cross-Module Integration (`Payment`, `Finance`), End-to-End Lifecycle, Database Schema, Models, Services, API, UI, Concurrency, and Core Decimal Math

---

## 1. Context & Motivation

A comprehensive architectural and functional end-to-end audit of invoice operations and related CRUD workflows was conducted across the codebase. The audit inspected database schemas, lifecycle state transitions, dual-timestamp compliance, concurrency guards (`row_version`), backend validation, exception handling, print formatting, party/currency snapshots, and frontend bindings.

Key vulnerabilities, architectural shortcomings, and design discrepancies were identified and resolved at root cause:

1. **Uncaught Fatal Errors in `ManualInvoiceService.php`**: Several `throw new InvalidArgumentException`, `throw new LogicException`, and `catch (JsonException)` statements lacked class imports in the `Modules\Invoice\Services` namespace, causing fatal runtime crashes upon validation or idempotency failure.
2. **Snapshot Immutability Breach on Posted Invoices (Axiom 6)**: While `Invoice::$immutableAttributes` previously checked financial amounts and basic party IDs, it omitted all party and currency snapshots (`party_number_snapshot`, `party_code_snapshot`, `party_name_snapshot`, `party_legal_name_snapshot`, `party_email_snapshot`, `party_phone_snapshot`, `party_tax_registration_snapshot`, `currency_code_snapshot`, `currency_name_snapshot`, `currency_symbol_snapshot`) and `exchange_rate`. In-place mutations to these legal snapshot fields on approved or posted invoices were silently permitted.
3. **Missing Lifecycle Guardrails Across Related Invoice Sub-Models**:
   - `InvoiceLine`: Did not reliably resolve unhydrated `$line->invoice` relations before checking draft status, and lacked protections blocking modification of structural foreign keys (`invoice_id`, `tenant_id`).
   - `InvoiceSource` and `InvoiceSourceLine`: Completely lacked `booted()` lifecycle hooks, allowing in-place edits and deletions of source allocations on finalized invoices and modification of structural tenancy/invoice keys.
   - `InvoiceAdjustment` and `InvoiceAdjustmentAllocation`: Completely lacked `booted()` lifecycle hooks, permitting in-place modifications and deletions of adjustments attached to posted invoices.
   - `InvoiceCreditAllocation`: Lacked `booted()` hooks, allowing in-place modifications and deletions of historical credit allocation ledger entries.
   - `InvoiceBalance`: Lacked `booted()` hooks, permitting in-place changes to `invoice_id` and deletion of balance ledgers on posted invoices.
4. **`InvoiceLayout` Deletion Guardrails & Defensive Paper/Orientation Normalization**:
   - `InvoiceLayout` previously allowed soft-deleting system default layouts (`is_system = true`) and deleting layouts actively assigned to existing invoices, creating orphaned print references.
   - Helper methods (`cssClass()`, `cssPageSize()`, `cssPageMargin()`, `pdfPaper()`, `isCompact()`, `toLegacyPrintLayout()`) assumed attributes were always backed enums, failing when attributes were populated with string values from raw database reads or API requests.
5. **Inconsistent State Filtering in `InvoiceBalanceProvider`**:
   - `getOutstandingTotalsForParties` and `getPartyExposureInCurrency` explicitly excluded `Draft`, `Reversed`, `Cancelled`, and `Void` invoices. However, `getPayableBalancesForParty` filtered by exclusion rather than inclusion, omitting `Approved` invoices. Invoices in `Approved` status cannot be settled (`InvoiceStatusService::assertCanSettle` throws `InvalidArgumentException: Only posted invoices can be settled`). When payment allocation services queried payable balances for a party with an approved invoice, it attempted to allocate payment and crashed with a fatal exception.
   - In `getInvoiceBalance()` and `balanceData()`, missing balance relationships threw unhandled `NullReferenceException` instead of providing safe guards and explicit exceptions.
6. **Null-Safety & Concurrency Stale Reads in `InvoiceSettlementService`**:
   - Direct access to `$invoice->balance->remaining_amount` risked fatal errors when the balance relation was missing.
   - Furthermore, `balanceBefore` was captured outside and prior to acquiring the exclusive row lock in `InvoiceBalanceUpdater::mutateBalance`, resulting in stale `balanceBefore` in `SettlementResultData` under concurrent payment applications or reversals.
7. **Type Declarations & Tenant Scoping in `InvoiceSourceService::documents()`**:
   - Type declaration `int $organizationUnitId` prevented querying sources for tenant-level documents having `null` organization unit IDs.
8. **Axiom 9 Zero-Magic Values Violations**:
   - `ListInvoiceRequest` used hardcoded string validation `'party_type' => ['nullable', 'string', 'in:customer,supplier']` instead of the typed PHP backed enum `InvoicePartyType`.
   - `InvoiceWhatsAppShareService` used magic string check `$invoice->party_type === 'customer'` instead of `InvoicePartyType::Customer`.
9. **Missing Polarity Symmetry in `DecimalMath` & Raw `bccomp` in `InvoicePrintService`**:
   - `DecimalMath` provided `isNegative` and `isZero` but lacked `isPositive`.
   - `InvoicePrintService` directly used raw `bccomp((string) $invoice->balance_due, '0', 6) > 0` instead of encapsulated math domain logic.
10. **Concurrency & Optimistic Locking in `assignLayout` (Axiom 6 & 10)**:
    - `InvoiceController::assignLayout` modified the invoice's layout without exclusive row locking, database transaction, or checking `expected_version`, and did not return the updated `row_version`.
    - It did not block layout modifications on historical read-only invoices belonging to retired source modules (`$type->belongsToRetiredSourceModule()`), nor on terminal-status invoices (`Cancelled`, `Void`, `Reversed`).
11. **Concurrency Race Conditions in `InvoiceLayoutService`**:
    - `create`, `update`, and `delete` in `InvoiceLayoutService` lacked `DB::transaction` and `lockForUpdate()`, allowing concurrent modifications and race conditions during layout management.
12. **Status Transition Balance Orphanage**:
    - When `InvoiceStatusService::transitionLocked` transitioned an invoice to `Cancelled`, `Void`, or `Reversed`, it did not zero `balance_due` on the invoice nor cancel the `InvoiceBalance` record, leaving positive remaining balances and active status if transitioned outside `InvoiceController::cancel`.
13. **Non-Deterministic Pagination Ordering**:
    - `InvoiceController::index` ordered solely by `latest('invoice_date')` without a primary key tie-breaker, leading to non-deterministic ordering across page boundaries when invoices shared identical civil dates.

---

## 2. Changes Made

### Backend

1. **`app/Modules/Invoice/Services/ManualInvoiceService.php`**:
   - Added missing imports: `use InvalidArgumentException;`, `use LogicException;`, and `use JsonException;`.
2. **`app/Modules/Invoice/Models/Invoice.php`**:
   - Added all legal snapshot attributes (`party_number_snapshot`, `party_code_snapshot`, `party_name_snapshot`, `party_legal_name_snapshot`, `party_email_snapshot`, `party_phone_snapshot`, `party_tax_registration_snapshot`, `currency_code_snapshot`, `currency_name_snapshot`, `currency_symbol_snapshot`, and `exchange_rate`) to `$immutableAttributes` in the `booted()` updating hook, strictly preventing in-place tampering on non-draft invoices.
3. **`app/Modules/Invoice/Models/InvoiceLine.php`**:
   - Hardened `booted()` updating/deleting hooks to safely resolve unhydrated `$line->invoice` relationships via database query fallback, verify that the parent invoice is in `Draft` status, and prohibit in-place changes to structural keys (`invoice_id`, `tenant_id`).
4. **`app/Modules/Invoice/Models/InvoiceSource.php`**:
   - Added `booted()` lifecycle hooks preventing update or deletion of invoice sources on non-draft parent invoices and blocking in-place changes to `invoice_id` and `tenant_id`.
5. **`app/Modules/Invoice/Models/InvoiceSourceLine.php`**:
   - Added `booted()` lifecycle hooks preventing update or deletion of source lines on non-draft parent invoices and blocking in-place changes to `invoice_id` and `tenant_id`.
6. **`app/Modules/Invoice/Models/InvoiceAdjustment.php`**:
   - Added `booted()` lifecycle hooks preventing update or deletion of adjustments on non-draft parent invoices and blocking in-place changes to `invoice_id` and `tenant_id`.
7. **`app/Modules/Invoice/Models/InvoiceAdjustmentAllocation.php`**:
   - Added `booted()` lifecycle hooks preventing update or deletion of adjustment allocations on non-draft parent invoices and blocking in-place changes to `invoice_id` and `tenant_id`.
8. **`app/Modules/Invoice/Models/InvoiceCreditAllocation.php`**:
   - Added `booted()` lifecycle hooks throwing `LogicException` on both `updating` and `deleting`, enforcing absolute immutability of historical credit allocation ledger entries.
9. **`app/Modules/Invoice/Models/InvoiceBalance.php`**:
   - Added `booted()` lifecycle hooks blocking in-place mutation of structural keys (`invoice_id`, `tenant_id`) and prohibiting deletion of balance records when the parent invoice is not in `Draft` status.
10. **`app/Modules/Invoice/Models/InvoiceLayout.php`**:
    - Added `booted()` deleting hook preventing soft-deletion of system default layouts (`is_system = true`) and layouts assigned to existing invoices.
    - Defensively normalized `paper_size` and `orientation` across `cssClass()`, `cssPageSize()`, `cssPageMargin()`, `pdfPaper()`, `isCompact()`, and `toLegacyPrintLayout()` to handle both backed enums and string representations.
11. **`app/Modules/Invoice/Services/InvoiceBalanceProvider.php`**:
    - Added `use InvalidArgumentException;`.
    - Hardened `getPayableBalancesForParty()` to strictly query `whereIn('invoices.status', [InvoiceStatus::Posted->value, InvoiceStatus::PartiallyPaid->value])`, ensuring un-postable or non-settleable `Approved`, `Draft`, `Reversed`, `Cancelled`, and `Void` invoices are never returned for payment settlement.
    - Added explicit null guards and checks in `getInvoiceBalance()` and `balanceData()`.
12. **`app/Modules/Invoice/Services/InvoiceBalanceUpdater.php`**:
    - In `mutateBalance()`: Captured `$previousRemaining = (string) $balance->remaining_amount;` under exclusive row lock prior to executing the mutation closure. Attached `setAttribute('previous_remaining_amount', $previousRemaining)` to the returned synced balance model.
13. **`app/Modules/Invoice/Services/InvoiceSettlementService.php`**:
    - Guarded against null `balance` in `applyPaymentAllocation()` and `reversePaymentAllocation()`, throwing `InvalidArgumentException('Invoice balance record is missing.')`.
    - Updated `SettlementResultData::balanceBefore` to read the authoritative locked pre-mutation remaining amount from `$balance->getAttribute('previous_remaining_amount') ?? $before`.
14. **`app/Modules/Invoice/Services/InvoiceStatusService.php`**:
    - In `transitionLocked()`: When transitioning to `Cancelled`, `Void`, or `Reversed`, automatically and atomically forced `$updates['balance_due'] = '0.000000'`, locked `$balance = $invoice->balance()->lockForUpdate()->first()`, set `remaining_amount = '0.000000'`, and set `status` to `Cancelled`, `Void`, or `Reversed`.
15. **`app/Modules/Invoice/Services/InvoiceLayoutService.php`**:
    - Wrapped `create()`, `update()`, and `delete()` in `DB::transaction(..., 3)`.
    - Added `lockForUpdate()` on layout retrieval and unique code collision checks, eliminating concurrent race conditions.
16. **`app/Modules/Invoice/Http/Controllers/InvoiceController.php`**:
    - In `index()`: Appended `->orderByDesc('id')` to `->latest('invoice_date')` for deterministic pagination ordering.
    - In `show()`: Eager loaded `'balance'` relation alongside `'layout'` via `$model->loadMissing(['layout', 'balance'])`.
    - In `assignLayout()`: Wrapped entire operation in `DB::transaction(..., 3)` with `lockForUpdate()`. Validated that the invoice does not belong to a retired source module (`$type->belongsToRetiredSourceModule()`), is not in terminal status (`Cancelled`, `Void`, `Reversed`), and matches `expected_version` (throwing `ConflictHttpException`).
17. **`app/Modules/Invoice/Services/InvoiceSourceService.php`**:
    - Updated `documents()` signature to accept nullable `?int $organizationUnitId`, explicitly using `whereNull('organization_unit_id')` when null.
18. **`app/Modules/Invoice/Http/Requests/ListInvoiceRequest.php`**:
    - Replaced hardcoded string validation `'party_type' => ['nullable', 'string', 'in:customer,supplier']` with `Rule::enum(InvoicePartyType::class)`.
19. **`app/Modules/Invoice/Services/InvoiceWhatsAppShareService.php`**:
    - Replaced raw string comparison with `InvoicePartyType::Customer` enum check.
20. **`app/Modules/Core/Services/DecimalMath.php`**:
    - Added `isPositive(int|string $value, int $scale = self::SCALE): bool` method.
21. **`app/Modules/Invoice/Services/InvoicePrintService.php`**:
    - Replaced direct `bccomp((string) $invoice->balance_due, '0', 6) > 0` with `$this->math->isPositive((string) $invoice->balance_due)`.

### Frontend

1. **`resources/js/modules/invoice/invoiceLayoutApi.ts`**:
   - Updated `assignInvoiceLayout` signature to accept optional `expectedVersion?: number` and send `expected_version` in the PATCH payload; included optional `row_version?: number` in the return type.
2. **`resources/js/modules/invoice/pages/InvoiceDetailPage.tsx`**:
   - Passed `invoice.data?.row_version` into `assignInvoiceLayout(id, layoutId, invoice.data?.row_version)`.
   - Updated local `row_version` state with the newly incremented `res.row_version` upon successful layout assignment.
3. **`resources/js/modules/invoice/pages/InvoiceListPage.tsx`**:
   - Imported `humanize` from `@/shared/utils/object` and formatted the `type` column as `${humanize(row.invoice_type) || '-'} / ${humanize(row.direction) || '-'}`.

### Tests

1. **`app/Modules/Invoice/Tests/InvoiceBalanceProviderTest.php`**:
   - Added `test_approved_invoices_are_excluded_from_payable_balances()` verifying that `getPayableBalancesForParty` excludes `Approved` invoices and only includes them once transitioned to `Posted`.
2. **`app/Modules/Invoice/Tests/InvoiceAuditAndHardeningTest.php`**:
   - Created comprehensive test suite verifying:
     - Immutability of party snapshots, legal snapshots, and exchange rate on posted invoices.
     - Lifecycle guards on `InvoiceSource`, `InvoiceSourceLine`, `InvoiceCreditAllocation`, and `InvoiceBalance`.
     - Deletion protection on system default and assigned `InvoiceLayout` records.
     - Exclusion of reversed and approved invoices from payable balances query.
     - Settlement service guard against missing invoice balance record.
     - Concurrency and optimistic locking validation in `assignLayout`.
     - Rejection of layout changes on historical retired source module invoices.
     - Rejection of layout changes on terminal status (`Cancelled`, `Void`, `Reversed`) invoices.
     - Atomic zeroing of `balance_due` and cancellation of `InvoiceBalance` during status transition to `Cancelled`.
     - Layout CSS and PDF paper size enum and string normalization.
3. **`tests/Unit/Core/DecimalMathTest.php`**:
   - Added unit test coverage for `isPositive`, `isZero`, and `isNegative`.

---

## 3. Verification Record

- **PHPUnit Full Invoice Suite**: `php artisan test app/Modules/Invoice/Tests`
  - **Result**: `72 passed (405 assertions) (100% Pass)`
- **PHPUnit Cross-Module Payment Allocation Suite**: `php artisan test --filter=PaymentAllocation`
  - **Result**: `3 passed (39 assertions) (100% Pass)`
- **PHPUnit Decimal Math Unit Test**: `php artisan test --filter DecimalMath`
  - **Result**: `7 passed (19 assertions) (100% Pass)`
- **Vitest Invoice Frontend Suite**: `npm run test -- resources/js/modules/invoice`
  - **Result**: `6 test files passed, 25 tests passed (100% Pass)`
- **TypeScript Typecheck**: `npm run typecheck`
  - **Result**: `0 errors (Clean)`
- **Pint Style Fixer**: `php vendor/bin/pint --test`
  - **Result**: `0 violations (Passed)`
