# Editable automatic inventory batch numbers

Date: 2026-09-14

## Requirement

Automatically populate batch/tracking-number inputs with a suitable internal reference while keeping the value editable when a supplier or operational process requires a different batch number.

## Implementation

- Added an Inventory-owned `BatchNumberService` as the single source for internal batch numbers.
- Reused Inventory's existing transactionally locked, tenant-scoped daily number sequence.
- Generated numbers use the readable format `BAT-YYYYMMDD-000001`.
- Added a tracking-manage-protected Inventory endpoint for the standalone batch creation form.
- Added a goods-receipt-create-protected Purchase endpoint for GRN users. The Purchase action delegates number generation to Inventory rather than duplicating Inventory logic or requiring an unrelated Inventory permission.
- The Inventory tracking form requests and fills a batch number after the user selects a tracked item.
- The GRN line editor requests and fills a batch number when the user clicks `Add batch`.
- Both fields remain editable, and choosing an existing batch keeps the new-batch inputs disabled as before.
- If automatic generation fails in the GRN workflow, an empty allocation remains available with explicit guidance so the user can enter a number manually.
- Supplier lot numbers remain manual because they represent external supplier/manufacturer references.

## Concurrency and data integrity

- Number allocation uses the existing `inventory_number_sequences` transaction and row lock, so overlapping users receive different numbers.
- Existing tenant/item/batch database uniqueness remains the final integrity guard.
- A number is consumed when it is shown. Cancelling a form or replacing the suggestion can therefore leave a harmless sequence gap; numbers are never reused.
- No schema migration or historical data change was required.

## Verification

- PHP syntax checks passed for the new and changed backend classes.
- PHP Pint passed.
- TypeScript compiler passed.
- Focused GRN frontend suite passed: 12 tests.
- Inventory integrity and permission suites passed: 11 tests, 167 assertions.
- Purchase batch-number permission-boundary test passed.
- Production Vite build passed with 665 modules transformed.
- `git diff --check` passed.
- The existing `PurchaseOrderApiTest` suite could not reach its assertions because its login setup returned `422`: the request host is not assigned to an active verified tenant. The same environment-level failure was reproduced by running only its batch-specific test.
