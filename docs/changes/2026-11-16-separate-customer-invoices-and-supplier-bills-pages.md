# Change Record: Separate Customer Invoices and Supplier Bills Pages with Quotation & Settlement Previews

**Date:** 2026-11-16  
**Author:** Antigravity  
**Module:** Vehicle Rental (`app/Modules/VehicleRental`, `resources/js/modules/vehicle-rental`, `resources/js/app`)

---

## 1. Overview and Intent

To enhance user experience, operational speed, and navigation clarity, dedicated top-level pages have been established for Customer Invoices (Accounts Receivable) and Supplier / Vehicle Owner Bills (Accounts Payable). These separate pages provide direct, un-nested CRUD workflows—enabling operators to view, create, edit drafts, and void unposted documents without navigating multi-level tabs or nested steps.

In addition, zero-persistence calculation endpoints and interactive preview modals ("Quotation Preview / Pro-Forma" for customer invoices and "Owner Settlement Preview" for supplier bills) have been introduced. These allow operators to simulate and verify full multi-tier telematics pricing (excess km, 1.5x/2.0x/3.0x overtime, night outs, tolls, additions, statutory 10% WHT to :3305-000, and maintenance deductions) prior to committing any records to the database.

---

## 2. Key Changes

### A. Dedicated Top-Level Pages & Direct CRUD Workflows
- **`resources/js/modules/vehicle-rental/CustomerInvoicesPage.tsx`**:
  - Dedicated route `/vehicle-rental/customer-invoices` for customer invoice operations.
  - KPI summary cards: Total Invoiced (AR), Unpaid Balance, Overdue Invoices, and Draft Invoices.
  - Quick filtering: text search, customer LookupSelect, status badges, balance status, and date range.
  - Direct DataTable listing with human-readable customer name, agreement reference, vehicle details, invoice number, totals, balance due, and direct view/edit/void actions.
  - Integrated "Preview Quotation / Pro-Forma" workflow with one-click transition to draft invoice creation.
  - In-place draft editing modal and safe draft void modal with mandatory audit reason.

- **`resources/js/modules/vehicle-rental/SupplierBillsPage.tsx`**:
  - Dedicated routes `/vehicle-rental/owner-bills` and `/vehicle-rental/supplier-bills` for vehicle owner settlements and supplier bills.
  - KPI metric cards: Total Billed (Gross AP), Unsettled Payable (Balance Due), Paid / Settled, and Pending Draft Bills.
  - Quick filtering: text search, owner/supplier LookupSelect, status badges, balance status, and date range.
  - Direct DataTable showing owner name, vehicle registration, agreement reference, bill number, gross lease, statutory 10% WHT, maintenance deductions, net payable, balance due, and actions.
  - Integrated "Settlement Preview" simulation modal with one-click bill generation.
  - In-place draft editing modal and safe draft void modal with mandatory audit reason.

### B. Quotation & Settlement Preview Modals & API Integration
- **`resources/js/modules/vehicle-rental/RentalQuotationPreviewModal.tsx`**:
  - Pro-forma quotation simulator accepting agreement ID, billing period, custody days proration, excess km, overtime tiers (normal, double, triple), night outs, tolls, additions, and discount deductions.
  - Connects to `POST /api/v1/vehicle-rental/billing/quotation-preview`.
  - Supports converting validated preview directly into a new draft invoice.
- **`resources/js/modules/vehicle-rental/RentalOwnerBillPreviewModal.tsx`**:
  - Owner lease settlement previewer calculating gross lease charges, telematics additions, 10% statutory WHT to :3305-000, and maintenance chargebacks without persisting any rows.
  - Connects to `POST /api/v1/vehicle-rental/billing/owner-bill-preview`.
- **`resources/js/modules/vehicle-rental/RentalBillingDocumentDetailModal.tsx`**:
  - Upgraded financial breakdown to explicitly highlight statutory 10% WHT credited to Account `:3305-000`.

### C. Backend Preview Endpoints (Zero Database Persistence)
- **`app/Modules/VehicleRental/Http/Controllers/RentalBillingDocumentController.php`**:
  - Registered `quotationPreview` (`POST billing/quotation-preview`) and `ownerBillPreview` (`POST billing/owner-bill-preview`).
  - Added alias controller methods `previewCustomerQuotation` and `previewOwnerBill`.
- **`app/Modules/VehicleRental/Services/RentalBillingDocumentService.php`**:
  - Implemented `previewQuotation`: parses period, proration, telematics overtime/km rates, additions, discounts, and taxes without writing to `invoices`, `invoice_lines`, or `billing_cycles`.
  - Implemented `previewOwnerBill`: delegates to `OwnerSettlementBillingService::previewOwnerSettlementBill` to calculate gross payables, statutory 10% WHT, and maintenance chargebacks without saving records.
  - Updated `getSummary`: computes `draft_count` and `draft_amount` for both `customer_ar` and `owner_ap`.
  - Updated `formatDocumentItem`: includes `notes` and `maintenance_deductions` on document list objects.
- **`app/Modules/VehicleRental/Routes/api.php`**:
  - Registered routes `POST billing/quotation-preview` and `POST billing/owner-bill-preview`.

### D. Navigation & Routing
- **`resources/js/app/router.tsx`**:
  - Registered lazy-loaded routes `/vehicle-rental/customer-invoices`, `/vehicle-rental/owner-bills`, and `/vehicle-rental/supplier-bills`.
- **`resources/js/app/navigation/rentalNavigation.ts`**:
  - Added direct links "Customer Invoices" and "Vehicle Owner Bills" in sidebar navigation under Vehicle Rental.
- **`resources/js/modules/vehicle-rental/RentalInvoicesAndBillsPage.tsx`**:
  - Added top action buttons to easily navigate between Customer Invoices and Vehicle Owner Bills.

---

## 3. Verification Record

- **TypeScript Typecheck (`npm run typecheck` / `tsc --noEmit`):**
  - Result: **0 errors**.
- **Frontend Vitest Suites (`npx vitest run resources/js/modules/vehicle-rental/`):**
  - Result: **37 test files passed, 168 tests passed, 0 failed**.
- **Backend PHPUnit Tests (`php artisan test`):**
  - `RentalQuotationPreviewTest.php`: **Passed (2 tests, 35 assertions, 0 DB rows created)**.
  - `VehicleRentalQuotationPreviewTest.php`: **Passed (2 tests, 35 assertions, 0 DB rows created)**.
  - `RentalBillingDocumentTest.php`: **Passed (16 tests, 162 assertions)**.
