# Multi-Format Invoice Layout Management & Customization

## Context & Objectives
To implement a flexible, multi-format invoice layout management system with complete CRUD operations, allowing tenants to create, customize, manage, and assign separate reusable Blade templates for different invoice formats:
- **Reusable Blade Layouts:** Dedicated Blade templates for each layout format (`standard`, `modern`, `compact`, `thermal`, `continuous`) supporting independent configurable structures, styles, brand colors, fonts, headers, footers, terms, and printing requirements.
- **Scope-Based Default Assignment:** Hierarchical default resolution supporting explicit query override, printer assignments (`invoice.printer_layouts`), invoice-level layout assignment (`invoice_layout_id`), organization unit default, tenant default, and system standard A4 fallback.
- **Dynamic Layout Selection:** Automatic selection and application of the correct Blade template whenever generating or printing invoices (browser print, Dompdf download, signed public share links, and WhatsApp preview).
- **Interactive UI Customization:** Intuitive configuration drawer for brand colors, font families, custom headers/footers, and visibility toggles, along with active default badges (`[Tenant Default]`, `[Branch Default]`) on the layout catalog and quick layout assignment directly on the invoice detail page.
- **Clean Architecture & Zero Magic Values:** Strict Enums (`InvoicePaperSize`, `InvoiceOrientation`, `InvoiceLayoutTemplate`, `InvoicePermission`), optimistic concurrency locking (`row_version`), multi-tenant data isolation, and professional author signatures.

---

## Key Changes

### 1. Reusable Blade Templates (`resources/views/invoice/layouts`)
- **`resources/views/invoice/layouts/standard.blade.php`**:
  - Enhanced standard A4 layout to dynamically apply primary brand colors (`$primaryColor`), custom announcement banners (`$layout['header_content']`), footer terms/notes (`$layout['footer_content']`), and display toggles (`$showLogo`, `$showTax`, `$showPayment`, `$showNotes`, `$showWords`).
  - Tuned vertical metrics and margins so the layout reliably fits on a single A4 page in Dompdf.
- **`resources/views/invoice/layouts/modern.blade.php`**:
  - Implemented contemporary corporate invoice layout with brand color accents, status badges, customer snapshot card, and customizable notes and terms.
- **`resources/views/invoice/layouts/compact.blade.php`**:
  - Designed space-efficient 1-page layout with high-density line items, condensed customer summary, compact signatures, and brand accenting.
- **`resources/views/invoice/layouts/thermal.blade.php`**:
  - Engineered 80mm/58mm POS thermal receipt layout featuring store header, purchaser reference metadata (Vehicle No, Job No, Mileage), custom policy notes, terms, and optional signature line.
- **`resources/views/invoice/layouts/continuous.blade.php`**:
  - Developed genuine dot-matrix / tractor-feed pre-printed multipart layout with boxed party details, vehicle/job references, compact line table, payment totals box, policy note, and dual authorization signatures.
- **`resources/views/invoice/print.blade.php`**:
  - Upgraded master print dispatcher to delegate cleanly to modular layout templates while preserving static legal fixtures and compliance attributes.

### 2. Backend Architecture & Domain Engine (`app/Modules/Invoice`)
- **`app/Modules/Invoice/DTOs/CreateInvoiceData.php` & `ManualInvoiceData.php`**:
  - Added `public ?int $invoiceLayoutId = null` to support layout specification during invoice creation.
- **`app/Modules/Invoice/Http/Requests/StoreInvoiceRequest.php`**:
  - Validated tenant-scoped `invoice_layout_id` and mapped it in `toData()`.
- **`app/Modules/Invoice/Services/InvoiceCreationService.php`**:
  - Persists `invoice_layout_id` explicitly within the guarded creation `forceFill`.
- **`app/Modules/Invoice/Services/ManualInvoiceService.php`**:
  - Passes `invoiceLayoutId` from `ManualInvoiceData` through to `CreateInvoiceData`.
- **`app/Modules/Invoice/Services/InvoiceLayoutService.php`**:
  - Implemented `getActiveDefaults(int $tenantId, ?int $organizationUnitId = null)` returning `tenant_default` and `branch_default` layout codes.
  - Implemented hierarchical scope resolution: Explicit query override -> Printer assignment -> Invoice layout assignment -> OU default -> Tenant default -> Standard fallback.
- **`app/Modules/Invoice/Http/Resources/InvoiceLayoutResource.php`**:
  - Enriched resource payload with `is_tenant_default: bool` and `is_branch_default: bool`.
- **`app/Modules/Invoice/Http/Controllers/InvoiceLayoutController.php`**:
  - Computes active default flags for index items and attaches `meta.defaults` to response.
- **`app/Modules/Invoice/Http/Controllers/InvoiceController.php`**:
  - Added `assignLayout(Request $request, int $invoice)` endpoint (`PATCH /api/v1/invoices/{id}/layout`) validating layout tenant ownership and updating `invoice_layout_id`.
  - Added `$model->loadMissing('layout')` in `show()` while preserving exact controller signatures.
- **`app/Modules/Invoice/Http/Resources/InvoiceResource.php`**:
  - Returns `invoice_layout_id` and loaded `layout` object.
- **`app/Modules/Invoice/Routes/api.php`**:
  - Registered `PATCH api/v1/invoices/{invoice}/layout` protected by `InvoicePermission::LAYOUTS_MANAGE`.
- **`app/Modules/Invoice/Tests/InvoiceMassAssignmentBoundaryTest.php`**:
  - Updated assertion to accommodate both Pint-formatted and parenthesized class instantiation.

### 3. Frontend User Interface (`resources/js/modules/invoice`)
- **`resources/js/modules/invoice/invoiceLayoutTypes.ts`**:
  - Added typed layout settings (`primary_color`, `font_family`, `show_company_logo`, `show_tax_summary`, `show_payment_details`, `show_notes_policy`, `show_amount_in_words`, `custom_notes`, `custom_terms`), `is_tenant_default`, `is_branch_default`, and `AssignInvoiceLayoutPayload`.
- **`resources/js/modules/invoice/invoiceLayoutApi.ts`**:
  - Added `assignInvoiceLayout(invoiceId, layoutId)` client method.
- **`resources/js/modules/invoice/invoiceTypes.ts`**:
  - Added `invoice_layout_id` and `layout?: InvoiceLayoutSummary` to the `Invoice` domain type.
- **`resources/js/modules/invoice/components/InvoiceLayoutEditorDrawer.tsx`**:
  - Added brand primary color picker with hex preview.
  - Added font family selector (Sans-Serif, Serif, Monospace).
  - Added toggles for Amount in Words and Notes & Policy.
  - Added textareas for Header Announcement Banner and Footer Notice / Terms.
  - Bidirectional normalization between internal settings keys.
- **`resources/js/modules/invoice/pages/InvoiceLayoutsPage.tsx`**:
  - Added visual badges: `[Tenant Default]` (emerald) and `[Branch Default]` (sky) on layout cards.
  - Added immediate local state reflection on setting default layouts (`handleSetDefault`).
  - Added dynamic "Default ✓" indicator on default action buttons.
- **`resources/js/modules/invoice/pages/InvoiceDetailPage.tsx`**:
  - Displays human-readable `Print layout` badge in the summary `DetailGrid`.
  - Enhanced "Layouts" dropdown with "Assigned Template" management allowing operators to set or reset the layout directly on the invoice, alongside instant printing with any layout.

---

## Author Signature
- **Author:** Kasun Vimarshana
- **Email:** kasunvmail@gmail.com
- **Phone:** +94713525253

---

## Verification & Results
- **PHPUnit Feature Tests:**
  `php artisan test tests/Feature/Invoice/InvoiceLayoutManagementTest.php`
  -> 9 passed (117 assertions), covering CRUD, defaults provisioning, optimistic locking, preview, printer scopes, and invoice layout assignment.
- **Invoice Module Tests:**
  `php artisan test app/Modules/Invoice/Tests`
  -> 51 passed (311 assertions). Zero regressions.
- **Frontend Vitest Tests:**
  `npx vitest run resources/js/modules/invoice`
  -> 6 test files passed, 24 tests passed (100%).
- **TypeScript Typecheck:**
  `npm run typecheck`
  -> 0 errors.
- **Pint PHP Code Styling:**
  `vendor/bin/pint --test`
  -> Passed cleanly across all modified files.
- **Production Build:**
  `npm run build`
  -> Vite production bundle compiled cleanly in 11.01s.
