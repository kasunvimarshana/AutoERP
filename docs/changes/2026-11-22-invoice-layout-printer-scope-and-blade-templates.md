# Invoice Layout Printer Scope Assignment & Modular Blade Templates

## Context & Objectives
To fulfill all requirements of the multi-format invoice layout management system:
1. Reusable, separate Blade templates for all layout formats (`modern`, `thermal`, `standard`, `compact`, `continuous`).
2. Printer-scope layout default assignment (`invoice.printer_layouts` mapping) with hierarchical resolution at tenant, branch/OU, and printer scopes.
3. User-friendly printer assignment management in the frontend and automatic layout selection during printing and PDF generation.

## Key Changes

### 1. Configuration & Resolution Layer
- **`app/Modules/Invoice/Config/configuration-definitions.php`**: Registered `invoice.printer_layouts` definition with type `json`, runtime mutable, organization-hierarchy inherited, tenant and OU scoped.
- **`app/Modules/Invoice/Contracts/InvoiceLayoutResolverInterface.php`**: Added `PRINTER_LAYOUTS_KEY` constant and updated `resolve()` and `resolveForScope()` signatures with optional `$printer` parameter.
- **`app/Modules/Invoice/Services/InvoiceLayoutResolver.php`**: Added printer layout resolution tier. When `$printer` is passed, checks `invoice.printer_layouts` mapping for active layout before falling back to invoice, branch, or tenant defaults.
- **`app/Modules/Invoice/Services/InvoiceLayoutService.php`**:
  - Enhanced `setDefault()` to support `scope: 'printer'`, loading, updating, encoding, and invalidating the cached `invoice.printer_layouts` mapping.
  - Added `getPrinterAssignments()` and `removePrinterAssignment()`.

### 2. HTTP & Print Controllers
- **`app/Modules/Invoice/Http/Requests/SetDefaultInvoiceLayoutRequest.php`**: Validates `scope` (`tenant`, `organization_unit`, `printer`) and `printer` (alphanumeric, underscores, hyphens, dots).
- **`app/Modules/Invoice/Http/Controllers/InvoiceLayoutController.php`**:
  - Handles printer-scoped defaults.
  - Added `printerAssignments` GET endpoint and `deletePrinterAssignment` DELETE endpoint.
- **`app/Modules/Invoice/Http/Controllers/InvoiceController.php`**: Extracts `printer` query param or `X-Printer-Identifier`/`X-Printer-Name` header across `printView`, `pdf`, `signedPrintLink`, `publicPrint`, and `publicPdf`.
- **`app/Modules/Invoice/Services/InvoicePrintService.php`**: Accepts `$printer` in `resolveLayout()` and `viewData()`.

### 3. Dedicated Modular Blade Templates
- **`resources/views/invoice/layouts/standard.blade.php`**: Modular classic A4 layout with title-box, formal two-party details table, detailed tabular items, summary card, and footer.
- **`resources/views/invoice/layouts/compact.blade.php`**: Modular compact slip A5 layout with condensed header and tight line spacing.
- **`resources/views/invoice/layouts/continuous.blade.php`**: Modular continuous feed 8.5" × 5.5" dot-matrix form layout.

### 4. Frontend Components & Types
- **`resources/js/modules/invoice/invoiceLayoutTypes.ts`**: Updated `SetDefaultInvoiceLayoutPayload` with `scope: 'printer'` and `printer?: string`. Defined `PrinterAssignmentsResponse`.
- **`resources/js/modules/invoice/invoiceLayoutApi.ts`**: Added `listPrinterAssignments()` and `deletePrinterAssignment()`.
- **`resources/js/modules/invoice/components/InvoicePrinterAssignmentsModal.tsx`**: Modal allowing operators to assign and remove printer-to-layout mappings for tenant or branch scopes.
- **`resources/js/modules/invoice/pages/InvoiceLayoutsPage.tsx`**: Added "Printer Defaults" action button and integrated the printer assignments modal.

## Verification
- **PHPUnit Tests**:
  - `Tests\Feature\Invoice\InvoiceLayoutManagementTest`: 8/8 passed (101 assertions), including new `test_printer_scope_layout_assignment_and_resolution`.
  - `Tests\Unit\Database\TenantIsolationArchitectureTest`: 8/8 passed (22 assertions).
  - `Modules\Invoice\Tests\*`: 51/51 passed (311 assertions).
- **TypeScript**: `npm run typecheck` passed with 0 errors.
- **Vitest**: `npx vitest run resources/js/modules/invoice` passed 6/6 test files (24 tests).
