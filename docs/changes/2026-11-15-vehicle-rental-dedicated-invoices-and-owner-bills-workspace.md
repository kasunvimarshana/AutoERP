# Dedicated Vehicle Rental Invoices and Owner Bills Management Workspace

**Date:** 2026-11-15  
**Scope:** `VehicleRental`, `Invoice`, `Payment`, `Frontend / Vehicle Rental`  
**Focus:** Dedicated management page for Customer Invoices (AR) and Vehicle Owner Bills (AP), separate tabbed workflows, full CRUD and lifecycle management, financial adjustments, payment allocation links, optimistic locking, and accounting immutability.

---

## 1. Problem Statement & Objectives
Previously, the Vehicle Rental module orchestrated customer agreements, running charts, billing cycles, vehicle uses, and owner settlements, but lacked a dedicated, unified billing cockpit:
1. Users had to navigate into individual rental agreements or consult the generic invoice register, which lacked rental domain context (e.g., vehicle registration, driver employee, running chart counts, excess km, driver overtime, night allowances, and statutory 10% Withholding Tax).
2. Owner settlement bills in `OwnerSettlementBillingService` were incorrectly initialized with `InvoiceType::Rental` (which belongs to a retired legacy module and is enforced as read-only by `InvoiceStatusService`), preventing standard invoice approval and posting lifecycles.
3. Users could not easily search, filter, track outstanding balances, view itemized charges/deductions, or void draft invoices with mandatory audit reasons from a single workspace.

---

## 2. Key Changes & Implementation Details

### A. Backend (`app/Modules/VehicleRental`)
1. **`Services/OwnerSettlementBillingService.php`**:
   - Corrected invoice type at line 276 from `InvoiceType::Rental` to `InvoiceType::Purchase`.
   - Vehicle owner settlement bills now represent canonical Accounts Payable supplier bills, allowing standard approval, posting, cancellation, and disbursement workflows.
2. **`Http/Requests/RentalBillingDocumentRequest.php`**:
   - Extends `TenantScopedRequest` to enforce tenant and organization unit scoping.
   - Validates query filters (`direction`, `search`, `status`, `balance_status`, `date_from`, `date_to`, `agreement_id`, `party_id`, `page`, `per_page`).
   - Validates draft update (`due_date`, `notes`, `expected_version`) and cancellation (`reason` min 5 chars, `expected_version`).
3. **`Services/RentalBillingDocumentService.php`**:
   - `listDocuments`: Scopes queries strictly to rental-linked invoices (`invoice_sources` matching `vehicle_rental_%` and `vehicle_rental_billing_cycles`), applies status and date filters, and bulk-loads agreement, vehicle, and driver context without N+1 queries.
   - `getSummary`: Aggregates key financial KPI metrics across Customer AR (Total Invoiced, Total Collected, Total Outstanding, Overdue Invoices) and Owner AP (Total Gross Billed, Statutory 10% WHT Withheld, Net Paid to Owners, Total Outstanding Payables) using `DecimalMath::compare`, `add`, and `sub`.
   - `getDocumentDetail`: Enriches invoice entity with itemized lines, statutory Withholding Tax adjustments, fleet maintenance deductions, payment allocation history with official receipt links, and contextual actions (`can_edit`, `can_cancel`, `can_record_payment`, `can_print`).
   - `updateDocument`: Enforces optimistic concurrency (`expected_version === row_version`) and updates draft metadata.
   - `cancelDocument`: Enforces optimistic concurrency, executes governed cancellation via `InvoiceStatusService::transitionIfVersion` recording audit reason and actor, cancels invoice balance, and restores linked charge sources.
4. **`Http/Controllers/RentalBillingDocumentController.php` & `Routes/api.php`**:
   - Implemented REST controller endpoints: `index`, `summary`, `show`, `update`, `cancel`.
   - Registered endpoints under `/api/v1/vehicle-rental/billing` protected by `tenant.feature:invoice` and authenticated tenant middleware.
5. **`Tests/RentalBillingDocumentTest.php`**:
   - Comprehensive test suite covering tenant and org unit scoping, Outbound AR vs Inbound AP filtering, WHT deduction calculations, search by party and agreement reference, KPI summary calculations, draft updating, audit cancellation, and rejection of direct cancellation on posted invoices.

### B. Frontend (`resources/js/`)
1. **`modules/vehicle-rental/rentalBillingApi.ts`**:
   - Type definitions for `RentalBillingDocument`, `RentalBillingDetail`, `RentalBillingLine`, `RentalBillingAdjustment`, `RentalBillingPayment`, and `RentalBillingSummary`.
   - Client API methods: `listRentalBillingDocuments`, `getRentalBillingSummary`, `getRentalBillingDetail`, `updateRentalBillingDocument`, and `cancelRentalBillingDocument`.
2. **`modules/vehicle-rental/RentalInvoicesAndBillsPage.tsx`**:
   - Tabbed financial cockpit:
     - **Tab 1: Customer Invoices (AR)**: 4 KPI metric cards, filters (search, status, balance status, date range), and data table with customer, agreement, vehicle, totals, and action links.
     - **Tab 2: Vehicle Owner Bills (AP)**: 4 KPI metric cards including 10% WHT deduction, filters, and data table displaying gross rent, WHT, net payable, and balances.
   - Context-sensitive creation actions: "Generate Billing Cycle Invoice", "Issue Base Rental Invoice", "Generate Owner Settlement Bill".
3. **`modules/vehicle-rental/RentalBillingDocumentDetailModal.tsx`**:
   - Full document drawer presenting invoice header, party and vehicle context, itemized lines, statutory adjustments, and payment allocation history with "Print Receipt" actions.
   - Integrated actions for editing draft terms, cancelling unposted drafts with mandatory audit reason, recording payments via `AgreementPaymentModal`, and printing official invoice documents.
4. **`modules/vehicle-rental/RentalInvoiceCreateModal.tsx`**:
   - Controlled launcher allowing users to select between Mode A (Consolidated Billing Cycle Invoice) and Mode B (Provisional Advance Base Rent Invoice).
   - Uses `LookupSelect` for searching active customer agreements without exposing raw IDs.
5. **`modules/vehicle-rental/OwnerSettlementModal.tsx`**:
   - Enhanced to support optional `ownerAgreementId` with controlled `LookupSelect` autocomplete for selecting active owner agreements.
6. **`app/navigation/rentalNavigation.ts` & `rentalNavigation.test.ts`**:
   - Registered "Invoices & Owner Bills" menu item under the Vehicle Rental module pointing to `/vehicle-rental/billing`.
7. **`app/router.tsx`**:
   - Registered lazy route `/vehicle-rental/billing` protected by `PermissionRoute` verifying customer or owner agreement view permissions.
8. **`modules/vehicle-rental/RentalInvoicesAndBillsPage.test.tsx`**:
   - Vitest test suite verifying page rendering, AR/AP tab switching, filtering, detail drawer inspection, and creation modal launchers.

---

## 3. Verification Record
- **Unit & Feature Tests**:
  - `php artisan test app/Modules/VehicleRental/Tests/RentalBillingDocumentTest.php`: 6 passed (45 assertions).
  - `php artisan test app/Modules/VehicleRental/Tests`: 280 passed (2315 assertions).
  - `php artisan test tests/Unit/Architecture`: 12 passed (124 assertions).
  - `npx vitest run resources/js/modules/vehicle-rental/RentalInvoicesAndBillsPage.test.tsx resources/js/app/navigation/rentalNavigation.test.ts`: 7 passed.
  - `npx vitest run resources/js/modules/vehicle-rental`: 33 files passed (152 assertions).
  - `npm run typecheck`: 0 errors.
