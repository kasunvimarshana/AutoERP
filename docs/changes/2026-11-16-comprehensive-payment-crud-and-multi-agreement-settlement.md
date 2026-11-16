# Comprehensive Payment CRUD & Multi-Agreement Financial Settlement

## 1. Context & Business Need
In vehicle rental and enterprise financial workflows, customers and vehicle owners often hold multiple concurrent agreements (e.g., corporate accounts with fleets of vehicles or suppliers with several leased cars). Previously:
- The `Payment` module lacked unposted draft modification (`update`) and cancellation/deletion (`destroy`), preventing operators from correcting draft receipts and disbursements prior to posting.
- Payments could only be recorded against an individual rental agreement, requiring manual piecemeal payments and preventing single-transaction settlements across multiple agreements belonging to the same party.
- Receipts did not display multiple agreement references when an allocation spanned across different agreements.
- `AgreementFinancialSummaryService` was double-counting party-wide unapplied advances against every agreement in an agreement payments list, skewing agreement-level net balances.
- In the `Invoice` module, invoice filtering by `party_id` lacked `party_type` constraints, risking cross-contamination between customers and suppliers with colliding IDs.

## 2. Changes Made

### A. Core Payment Module (`Modules\Payment`)
1. **Draft Payment CRUD (`update` & `destroy`)**:
   - Implemented `PaymentUpdateService` to update unposted draft payments (`document_status === 'draft'` and `posting_status === 'not_posted'`). Safely replaces child payment lines and pending allocations, recalculates totals using `PaymentCalculationService`, recomputes unapplied balances, and enforces optimistic locking via `row_version`.
   - Implemented `PaymentDraftDeletionService` to clean up draft lines, allocations, and unapplied balances before soft-deleting draft payments while maintaining foreign key integrity and audit logs.
   - Added `UpdatePaymentData` DTO, `UpdatePaymentRequest`, and `DeletePaymentRequest` with optimistic lock validation (`expected_version`).
   - Exposed `PUT /api/v1/payments/{payment}` and `DELETE /api/v1/payments/{payment}` controller actions and registered them with `PaymentPermission::PAYMENTS_UPDATE` and `PaymentPermission::PAYMENTS_VOID`.
2. **Multi-Agreement Payment Receipts**:
   - Updated `PaymentReceiptService` and `resources/views/payment/receipt.blade.php` to extract distinct agreement references from allocations and payment metadata, displaying multiple agreement references in the header and an Agreement Reference column in the document allocation table.

### B. Invoice Module (`Modules\Invoice`)
1. **Party Type Filtering**:
   - Updated `ListInvoiceRequest` to validate `'party_type' => ['nullable', 'string', 'in:customer,supplier']`.
   - Updated `InvoiceController::index()` to filter invoices by `party_type`, preventing cross-party ID collision.

### C. Vehicle Rental Module (`Modules\VehicleRental`)
1. **Multi-Agreement Settlements & Bug Fixes (`RentalPaymentService`)**:
   - Fixed hardcoded permission checks in `reversePayment()` and `allocateUnappliedFunds()` to authorize dynamically using `AgreementKind $kind` (customer vs owner).
   - Fixed fatal SQL error in `listAgreementPayments`: changed invalid column query `Invoice::query()->where('source_document_number', ...)` to `whereHas('sources', fn ($q) => ...)`.
   - Added organization unit isolation: scoped `getPartyOpenDocuments`, `recordMultiAgreementPayment`, and `allocatePartyUnappliedFunds` by `organization_unit_id`.
   - Added optimistic locking (`expectedVersion`) validation in `recordMultiAgreementPayment` against party and agreement versions.
   - Added currency consistency validation in `recordMultiAgreementPayment` (rejects cross-currency allocations in a single payment).
   - Fixed empty string effective dates in `allocateUnappliedFunds`, `allocatePartyUnappliedFunds`, and `reversePayment` to default safely to `now()->toDateString()`.
   - Added `getPartyOpenDocuments()`: fetches all active agreements for a party, open invoices with `remaining_amount > 0`, enriches them with vehicle registration and driver context via `RentalBillingDocumentService::bulkLoadRentalContexts()`, and returns agreement and document summaries.
   - Added `recordMultiAgreementPayment()`: records single receipt or disbursement tendered across multiple agreements and invoices, preserving multi-agreement metadata (`agreement_ids`, `agreement_references`, `primary_agreement_reference`).
   - Added `allocatePartyUnappliedFunds()`: draws from existing unapplied advances to settle open invoices across any agreement belonging to that party.
   - Updated `AgreementFinancialSummaryService`: fixed unapplied advance double-counting by asserting that only payments initiated from the specific agreement (`source_id === $agreement->id`) deduct from that agreement's net balance, while exposing `party_unapplied_advance` separately; replaced blanket `catch (\Throwable)` with specific `catch (\Illuminate\Auth\Access\AuthorizationException)`.
2. **API Routes & Controller Actions (`RentalPaymentController`)**:
   - Registered `GET /api/v1/vehicle-rental/{kind}/parties/{partyId}/open-documents` (with `rental_only` filter support).
   - Registered `POST /api/v1/vehicle-rental/{kind}/parties/{partyId}/payments`.
   - Registered `POST /api/v1/vehicle-rental/{kind}/parties/{partyId}/payments/{payment}/allocate`.

### D. Frontend Workspaces & Multi-Agreement UI
1. **API Clients**:
   - `paymentApi.ts`: added `updatePayment`, `deletePayment`, `getPaymentReceiptUrl`.
   - `agreementApi.ts`: added `PartyOpenDocument`, `PartyOpenDocumentsResponse`, `getPartyOpenRentalDocuments`, `recordPartyRentalPayment`, `allocatePartyRentalPayment`, and exported `AgreementKind`.
2. **Payment Workspaces**:
   - `PaymentDetailPage.tsx`: added "Edit Draft" button for draft payments (gated by `paymentPermissions.update`), "Delete Draft" with version check (gated by `paymentPermissions.void`), and "View Receipt" / "Print Receipt" actions.
   - `PaymentEntryPage.tsx`:
     - Added edit mode (`/payments/:id/edit`) for unposted draft payments, ensuring open rental documents load in edit mode.
     - Added interactive Document Allocation Grid when a Customer or Supplier is selected: fetches open documents across all party agreements, enables filtering by agreement, provides "Pay full" and "Pay full balance on all", live summary (Total Tendered, Total Allocated, Unapplied Advance / Surplus, Balance After Payment), and passes allocations to creation and update services.
   - `AgreementPaymentModal.tsx`: added cross-agreement settlement toggle allowing operators to view and settle open documents across all agreements of a customer or owner directly from the agreement workspace.
   - `router.tsx` & `financeRouteEntitlements.ts`: registered `/payments/:id/edit` with `paymentPermissions.update`.

## 3. Verification & Test Results
- **Payment Unit & Feature Tests**: `php artisan test app/Modules/Payment/Tests` -> 51 passed (241 assertions) including `PaymentCrudTest`.
- **Vehicle Rental Unit & Feature Tests**: `php artisan test app/Modules/VehicleRental/Tests` -> 303 passed (2,466 assertions) including `RentalMultiAgreementPaymentTest` (6 tests).
- **Invoice Unit & Feature Tests**: `php artisan test app/Modules/Invoice/Tests` -> 42 passed (245 assertions).
- **Architecture Integrity**: `php artisan test tests/Unit/Architecture` -> 12 passed (124 assertions).
- **Frontend Vitest Suites**:
  - `resources/js/modules/payment`: 5 test files, 11 tests passed (including `PaymentEntryMultiAgreement.test.tsx`).
  - `resources/js/modules/vehicle-rental`: 33 test files, 154 tests passed.
- **TypeScript Typecheck**: `node --max-old-space-size=1024 ./node_modules/typescript/bin/tsc --noEmit` -> 0 errors.
