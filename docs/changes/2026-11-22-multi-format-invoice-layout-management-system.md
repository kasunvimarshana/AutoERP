# Multi-Format Invoice Layout Management System

## Overview
Implemented a robust, flexible, multi-format invoice layout management system supporting end-to-end CRUD operations, customizable Blade templates, format paper sizes (Classic Standard A4, Modern Clean A4, Compact Slip A5, Thermal POS 80mm Roll, Continuous Feed 8.5×5.5 in dot-matrix), hierarchical default scope resolution (organization-unit with tenant fallback), and an intuitive frontend catalog and management interface.

## Architecture & Implementation Details

### 1. Database Architecture & Migrations
- Created migration `2026_11_22_200001_create_invoice_layouts_table.php`:
  - `invoice_layouts` table with `id`, `tenant_id`, `code`, `name`, `description`, `paper_size`, `orientation`, `view_template`, `is_system`, `is_active`, `custom_css`, `header_content`, `footer_content`, `settings` (JSON), `row_version`, `created_by`, `updated_by`, timestamps, and soft deletes.
  - Declared candidate keys `['id', 'tenant_id']` and `['tenant_id', 'code']`.
  - Added same-tenant foreign keys with `restrictOnDelete()` obeying multi-tenant isolation axioms.
- Created migration `2026_11_22_200002_add_invoice_layout_id_to_invoices_table.php`:
  - Added `invoice_layout_id` to `invoices` table.
  - Added composite foreign key `['invoice_layout_id', 'tenant_id']` referencing `invoice_layouts` with `restrictOnDelete()`.

### 2. Domain Models & Enums
- Model `InvoiceLayout`: Tenant-scoped model (`UsesTenantScope`) with optimistic concurrency support (`row_version`), soft deletes, and helpers for CSS page sizing, margins, PDF paper sizes, and legacy layout mapping.
- Model `Invoice`: Added `invoice_layout_id` integer cast and `layout(): BelongsTo` relationship.
- Enums:
  - `InvoicePaperSize`: `a4`, `a5`, `thermal_80mm`, `continuous_8_5_by_5_5`, `letter`.
  - `InvoiceOrientation`: `portrait`, `landscape`.
  - `InvoiceLayoutTemplate`: canonical Blade view templates (`invoice.layouts.standard`, `invoice.layouts.modern`, `invoice.layouts.compact`, `invoice.layouts.thermal`, `invoice.layouts.continuous`).
- Permissions: `invoices.layouts.view` and `invoices.layouts.manage` in `InvoicePermission`.

### 3. Business Logic & Hierarchy Resolution
- `InvoiceLayoutProvisioner`: Deterministically provisions the 5 standard system layouts upon first access for any tenant.
- `InvoiceLayoutResolver`: Resolves the layout hierarchy:
  1. Explicit layout code override (e.g. from print preview or print dialog).
  2. Specific invoice layout assigned on the invoice (`invoice_layout_id`).
  3. Scoped configuration override: `invoice.default_layout` at organization unit level (with hierarchical OU tree inheritance).
  4. Tenant default configuration: `invoice.default_layout` at tenant level.
  5. Backward-compatible mapping from legacy `invoice.default_print_layout`.
  6. Final fallback: `standard_a4`.
- `InvoiceLayoutService`:
  - CRUD operations with optimistic locking conflict detection (HTTP 409).
  - Validation guards against deleting system layouts or layouts referenced by invoices.
  - Safe HTML preview generator rendering realistic mock invoice data or persisted invoice data.
  - `setDefault()` to store tenant- and OU-scoped layout configurations with cache invalidation.
- `InvoicePrintService`: Injects layout resolver, sets paper sizes, margins, and embeds resolved layout properties and custom CSS into printable views.

### 4. Blade Templates & Outbound Styling
- Modern Clean A4 (`resources/views/invoice/layouts/modern.blade.php`): Contemporary styled layout with accent card headers, party boxes, line items, payment stub, and signatures.
- Thermal POS 80mm (`resources/views/invoice/layouts/thermal.blade.php`): Compact monospaced receipt layout designed for 80mm thermal receipt printers.
- Master print template (`resources/views/invoice/print.blade.php`): Injects custom CSS rules, sheet dimensions, and conditionally includes custom or legacy outbound views while strictly preserving 100% backward compatibility.

### 5. HTTP Layer & API
- Controller `InvoiceLayoutController`: Implements `index`, `show`, `store`, `update`, `destroy`, `preview`, `setDefault`.
- Requests: `StoreInvoiceLayoutRequest`, `UpdateInvoiceLayoutRequest`, `SetDefaultInvoiceLayoutRequest` with validation rules and malicious input guards.
- Resource `InvoiceLayoutResource`: Structured API representation without exposing raw IDs in user-facing fields.
- Controller `InvoiceController`: Updated `signedPrintLink`, `publicPrint`, `publicPdf`, and `pdfResponse` to accept `layout` query parameter for instant layout switching.
- Routes registered under `/api/v1/invoice-layouts`.

### 6. React Frontend Implementation
- Types & API: `invoiceLayoutTypes.ts` and `invoiceLayoutApi.ts`.
- Components:
  - `InvoiceLayoutPreviewModal`: Sandboxed iframe modal for live visual preview of any layout template.
  - `InvoiceLayoutEditorDrawer`: Form to create or edit custom layouts with paper size, orientation, visual toggles, notes/terms, and custom CSS overrides.
  - `InvoiceLayoutsPage`: Responsive catalog displaying system presets and custom layouts with search, filters, format badges, "Tenant Default", "Branch Default", preview, edit, clone, and delete actions.
  - `OrganizationUnitDocumentSettingsPanel`: Enhanced to dynamically load active layouts and set the branch default layout.
- Router: Registered `/settings/invoice-layouts` protected by `invoicePermissions.layoutsView`.
- Navigation: Added Layout Templates button on `InvoiceListPage` for quick discoverability.

## Verification & Test Results
- **PHPUnit Backend Tests**:
  - `tests/Feature/Invoice/InvoiceLayoutManagementTest.php`: 7 passed (85 assertions).
  - `tests/Feature/Invoice/InvoiceEngineTest.php`: 11 passed (32 assertions).
  - `tests/Feature/Invoice/InvoiceSettlementEligibilityFilterTest.php`: 1 passed (10 assertions).
  - `app/Modules/Invoice/Tests/*`: 51 passed (311 assertions) including `InvoicePrintServiceTest` (14 passed) and `InvoicePrintRouteAuthorizationTest` (2 passed).
  - `tests/Unit/Database/TenantIsolationArchitectureTest.php`: 8 passed (22 assertions).
  - Total: **78 passed (460 assertions)** with 0 failures and 0 errors.
- **Frontend Vitest Tests**:
  - `resources/js/modules/invoice/pages/InvoiceLayoutsPage.test.tsx`: 4 passed (catalog, defaults, preview modal, editor drawer).
  - All 6 invoice test suites: **24 passed (24 assertions)**.
- **TypeScript Typecheck**:
  - `npm run typecheck`: Passed with 0 errors across the entire application codebase.
