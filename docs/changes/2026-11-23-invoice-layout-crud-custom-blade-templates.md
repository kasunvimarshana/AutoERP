# Invoice Layout CRUD & Custom Blade Template Engine Integration

**Date:** 2026-11-23  
**Module:** Invoice (`app/Modules/Invoice`, `resources/js/modules/invoice`, `resources/views/invoice`)  
**Author & Maintainer:** Kasun Vimarshana (`kasunvmail@gmail.com`)  

---

## 1. Context & Objectives

Following a comprehensive end-to-end audit of the Invoice Layout CRUD functionality, the system was upgraded to support custom Blade templates (`.blade.php` files). This empowers tenants and administrators to author, upload, preview, validate, export, and render tailor-made invoice designs alongside built-in preset layouts (A4, A5, and Continuous 80mm).

### Key Deficiencies Identified & Addressed:
1. **Lack of Blade Template Storage & Type Distinction**:
   - Layouts previously only referenced static view template strings (e.g., `invoice.print`). Custom user-defined HTML/Blade code could not be persisted or distinguished from preset layouts.
2. **Preview Route Contract & Method Inconsistencies**:
   - The backend route `/api/v1/invoice-layouts/{layout}/preview` strictly required `POST` and integer IDs (`whereNumber('layout')`), whereas frontend callers and external consumers attempted `GET` requests using layout string codes (e.g., `'standard_a4'`).
3. **Absence of a Secure Template Sandboxing Pipeline**:
   - User-supplied Blade templates require strict validation to prevent Remote Code Execution (RCE), filesystem access, arbitrary PHP execution, database tampering, and template injection.
4. **Missing File Upload & Export Capabilities**:
   - Users had no mechanism to upload existing `.blade.php` files or export their customized layouts as standalone template files.
5. **Incomplete Layout Management UX**:
   - The editor drawer lacked a code editor, variable reference documentation, template type switching, and boilerplate starter code.

---

## 2. Architecture & Design Implementation

### A. Template Type Classification (`InvoiceLayoutType`)
- Introduced `Modules\Invoice\Enums\InvoiceLayoutType` backed enum:
  - `Preset = 'preset'`: System-provided built-in layouts (`standard_a4`, `service_a5`, `thermal_80mm`).
  - `Blade = 'blade'`: Tenant-customized Blade templates with editable `blade_content`.

### B. Database Schema & Migration Hygiene
- Updated baseline migration `app/Modules/Invoice/Database/Migrations/2026_06_12_150000_create_invoice_layouts_table.php`:
  - Added `template_type` (`string(32)`, default `'preset'`).
  - Added nullable `blade_content` (`mediumText`).
  - Made `view_template` nullable with default `'invoice.print'`.
- Preserved 1:1 table baseline migration architecture in compliance with AutoERP `ModuleMigrationBaselineTest` (no `Schema::table()` patch migrations).

### C. Backend Security Validator (`InvoiceBladeSecurityValidator`)
Before any custom Blade template is stored or compiled, it passes through multi-layer AST and token security validation:
1. **Raw PHP Tag Ban**: Rejects `<?php`, `<?=`, `<script language="php">`.
2. **Dangerous Blade Directives**: Blocks `@php`, `@include`, `@includeIf`, `@includeWhen`, `@extends`, `@inject`, `@component`, `@each`, `@yield`, and `@section`.
3. **Dangerous Functions**: Blocks process execution (`exec`, `shell_exec`, `system`, `passthru`, `proc_open`), dynamic evaluation (`eval`, `assert`), filesystem access (`file_get_contents`, `file_put_contents`, `unlink`), and dangerous callables.
4. **Superglobals & Database Access**: Blocks `$_GET`, `$_POST`, `$_SERVER`, `$_ENV`, `$GLOBALS`, and `DB::` query executions.
5. **Compilation Syntax Verification**: Compiles the template in a dry-run via `Blade::compileString()` within a `try-catch` block to detect syntax errors before saving.

### D. Sandboxed Blade Renderer (`InvoiceCustomBladeRenderer`)
- Safely evaluates validated templates using Laravel's `Blade::render()`.
- Automatically wraps custom templates with necessary print CSS resets (`@page`, media styles) and isolated container styling.
- Injects a complete set of standard invoice rendering variables: `$invoice`, `$company`, `$items`, `$payments`, `$currency_symbol`, `$barcode_svg`, and mock data for layout design previews.
- Provides fallback error rendering during previews to show clear contextual debugging info without crashing the application.

### E. Print & Lifecycle Integration (`InvoicePrintService`)
- Updated `InvoicePrintService` and `resources/views/invoice/print.blade.php`:
  - For preset layouts, the system continues rendering standard Blade components (`service_a4`, `service_a5`, `continuous`).
  - For `blade` layouts, `InvoiceCustomBladeRenderer` evaluates the custom template and injects `{!! $custom_blade_html !!}` into the verified print shell.

### F. API Endpoints
- `GET|POST /api/v1/invoice-layouts/{layout}/preview`: Resolves layout by integer ID or string code. Supports dual content negotiation (returns structured JSON `{ data: { html: ... } }` or raw HTML based on `Accept: text/html`).
- `POST /api/v1/invoice-layouts/upload-template`: Validates uploaded `.blade.php` files (up to 2MB) for security and syntax, returning sanitized content and suggested name.
- `GET /api/v1/invoice-layouts/{layout}/export`: Streams the layout's `blade_content` as a downloadable `.blade.php` file with proper `Content-Disposition`.

### G. Modern Frontend UI/UX
- **Editor Drawer (`InvoiceLayoutEditorDrawer.tsx`)**:
  - Template type selector (Preset vs Custom Blade).
  - Drag-and-drop / click-to-upload `.blade.php` file importer with real-time backend validation.
  - Monospace code editor with line numbers and syntax formatting.
  - "Load Starter Boilerplate" button providing an instant, accessible invoice template structure.
  - Interactive 1-click variable reference cheat sheet listing available fields (`$invoice->invoice_number`, `$company['name']`, `$items`, etc.).
  - "Download .blade.php" local backup action.
- **Preview Modal (`InvoiceLayoutPreviewModal.tsx`)**:
  - Displays layout type badge, resolution mode, and responsive iframe preview.
  - Direct template export button for custom Blade layouts.
- **Layouts Index (`InvoiceLayoutsPage.tsx`)**:
  - Filter pills: All, Preset Layouts, Custom Blade.
  - Visual badges differentiating built-in presets from custom Blade designs.
  - Card-level template export action.

---

## 3. Modified & Created Files Summary

| File | Status | Description |
|---|---|---|
| [`app/Modules/Invoice/Enums/InvoiceLayoutType.php`](file:///d:/project-new/AutoERP/app/Modules/Invoice/Enums/InvoiceLayoutType.php) | **Created** | Enum defining `preset` and `blade` template types. |
| [`app/Modules/Invoice/Validators/InvoiceBladeSecurityValidator.php`](file:///d:/project-new/AutoERP/app/Modules/Invoice/Validators/InvoiceBladeSecurityValidator.php) | **Created** | Security validator enforcing AST/regex bans on PHP execution, dangerous directives, and syntax compile errors. |
| [`app/Modules/Invoice/Services/InvoiceCustomBladeRenderer.php`](file:///d:/project-new/AutoERP/app/Modules/Invoice/Services/InvoiceCustomBladeRenderer.php) | **Created** | Sandboxed Blade template renderer with CSS print wrappers and mock context generation. |
| [`app/Modules/Invoice/Http/Requests/UploadInvoiceBladeTemplateRequest.php`](file:///d:/project-new/AutoERP/app/Modules/Invoice/Http/Requests/UploadInvoiceBladeTemplateRequest.php) | **Created** | Form request validating uploaded `.blade.php` files. |
| [`app/Modules/Invoice/Database/Migrations/2026_06_12_150000_create_invoice_layouts_table.php`](file:///d:/project-new/AutoERP/app/Modules/Invoice/Database/Migrations/2026_06_12_150000_create_invoice_layouts_table.php) | Modified | Added `template_type` and `blade_content` to baseline migration. |
| [`app/Modules/Invoice/Models/InvoiceLayout.php`](file:///d:/project-new/AutoERP/app/Modules/Invoice/Models/InvoiceLayout.php) | Modified | Added fillable attributes, casts, `isBlade()`, and `isPreset()`. |
| [`app/Modules/Invoice/DTOs/InvoiceLayoutData.php`](file:///d:/project-new/AutoERP/app/Modules/Invoice/DTOs/InvoiceLayoutData.php) | Modified | Updated DTO with `templateType` and `bladeContent`. |
| [`app/Modules/Invoice/Http/Resources/InvoiceLayoutResource.php`](file:///d:/project-new/AutoERP/app/Modules/Invoice/Http/Resources/InvoiceLayoutResource.php) | Modified | Serialized `template_type` and `blade_content`. |
| [`app/Modules/Invoice/Http/Requests/StoreInvoiceLayoutRequest.php`](file:///d:/project-new/AutoERP/app/Modules/Invoice/Http/Requests/StoreInvoiceLayoutRequest.php) | Modified | Added validation rules and security inspection for `template_type` and `blade_content`. |
| [`app/Modules/Invoice/Http/Requests/UpdateInvoiceLayoutRequest.php`](file:///d:/project-new/AutoERP/app/Modules/Invoice/Http/Requests/UpdateInvoiceLayoutRequest.php) | Modified | Added validation rules and security inspection for `template_type` and `blade_content`. |
| [`app/Modules/Invoice/Http/Controllers/InvoiceLayoutController.php`](file:///d:/project-new/AutoERP/app/Modules/Invoice/Http/Controllers/InvoiceLayoutController.php) | Modified | Added multi-format `preview()`, `uploadTemplate()`, and `export()`. |
| [`app/Modules/Invoice/Routes/api.php`](file:///d:/project-new/AutoERP/app/Modules/Invoice/Routes/api.php) | Modified | Registered preview, upload, and export endpoints. |
| [`app/Modules/Invoice/Providers/InvoiceServiceProvider.php`](file:///d:/project-new/AutoERP/app/Modules/Invoice/Providers/InvoiceServiceProvider.php) | Modified | Registered `InvoiceBladeSecurityValidator` and `InvoiceCustomBladeRenderer` singletons. |
| [`app/Modules/Invoice/Services/InvoiceLayoutProvisioner.php`](file:///d:/project-new/AutoERP/app/Modules/Invoice/Services/InvoiceLayoutProvisioner.php) | Modified | Seeded default system layouts with `InvoiceLayoutType::Preset`. |
| [`app/Modules/Invoice/Services/InvoiceLayoutService.php`](file:///d:/project-new/AutoERP/app/Modules/Invoice/Services/InvoiceLayoutService.php) | Modified | Integrated preview generation with custom Blade renderer. |
| [`app/Modules/Invoice/Services/InvoicePrintService.php`](file:///d:/project-new/AutoERP/app/Modules/Invoice/Services/InvoicePrintService.php) | Modified | Integrated custom Blade template rendering in invoice print pipeline. |
| [`resources/views/invoice/print.blade.php`](file:///d:/project-new/AutoERP/resources/views/invoice/print.blade.php) | Modified | Added conditional `{!! $custom_blade_html !!}` rendering. |
| [`tests/Feature/Invoice/InvoiceLayoutManagementTest.php`](file:///d:/project-new/AutoERP/tests/Feature/Invoice/InvoiceLayoutManagementTest.php) | Modified | Added comprehensive feature tests for custom Blade layout CRUD, security rejection, upload, and export. |
| [`resources/js/modules/invoice/invoiceLayoutTypes.ts`](file:///d:/project-new/AutoERP/resources/js/modules/invoice/invoiceLayoutTypes.ts) | Modified | Added `InvoiceLayoutType`, `template_type`, `blade_content`, and upload response types. |
| [`resources/js/modules/invoice/invoiceLayoutApi.ts`](file:///d:/project-new/AutoERP/resources/js/modules/invoice/invoiceLayoutApi.ts) | Modified | Added `uploadInvoiceBladeTemplate`, `getInvoiceBladeExportUrl`, and flexible preview client. |
| [`resources/js/modules/invoice/components/InvoiceLayoutEditorDrawer.tsx`](file:///d:/project-new/AutoERP/resources/js/modules/invoice/components/InvoiceLayoutEditorDrawer.tsx) | Modified | Added template type switcher, file uploader, code editor, boilerplate loader, and variable cheat sheet. |
| [`resources/js/modules/invoice/components/InvoiceLayoutPreviewModal.tsx`](file:///d:/project-new/AutoERP/resources/js/modules/invoice/components/InvoiceLayoutPreviewModal.tsx) | Modified | Added template type badge and template export button. |
| [`resources/js/modules/invoice/pages/InvoiceLayoutsPage.tsx`](file:///d:/project-new/AutoERP/resources/js/modules/invoice/pages/InvoiceLayoutsPage.tsx) | Modified | Added template type filter tabs, badges, and export action. |
| [`resources/js/modules/invoice/pages/InvoiceLayoutsPage.test.tsx`](file:///d:/project-new/AutoERP/resources/js/modules/invoice/pages/InvoiceLayoutsPage.test.tsx) | Modified | Added unit tests covering custom Blade layout interactions. |

---

## 4. Verification & Quality Gate Results

All gates passed with 0 errors across the entire codebase:

1. **Invoice Layout Management Feature Tests:**
   ```bash
   php artisan test --filter InvoiceLayoutManagementTest
   ```
   *Result:* **12 passed (173 assertions)**.
   - Auto provisions system default layouts.
   - Create, read, and filter custom invoice layouts.
   - Update custom layout with optimistic locking and conflict rejection.
   - Rejection of system layout deletion and referenced layout deletion.
   - Preview layout rendering with mock and actual invoice data.
   - Default layout resolution and printer scope hierarchy.
   - Tenant isolation boundary enforcement.
   - Create and preview custom Blade template.
   - Security validator blocking malicious Blade code (`<?php`, `@php`, `system()`, etc.).
   - Upload and export `.blade.php` template files.

2. **Invoice Print Service Tests:**
   ```bash
   php artisan test --filter InvoicePrint
   ```
   *Result:* **16 passed (163 assertions)** across all print formats and authorizations.

3. **Module Migration Baseline Integrity:**
   ```bash
   php artisan test --filter ModuleMigrationBaselineTest
   ```
   *Result:* **1 passed (3 assertions)** (1:1 table baseline preserved).

4. **Backend Code Style (Laravel Pint PSR-12):**
   ```bash
   php vendor/bin/pint --dirty --test
   ```
   *Result:* **Passed (`{"tool":"pint","result":"passed"}`)**.

5. **Frontend Vitest Suite:**
   ```bash
   npx vitest run
   ```
   *Result:* **176 passed / 176 test files (737 passed / 737 tests)**.

6. **TypeScript Strict Typecheck:**
   ```bash
   npm run typecheck
   ```
   *Result:* **Passed with 0 errors (`tsc --noEmit` exited with code 0)**.

7. **ESLint Static Analysis:**
   ```bash
   npm run lint
   ```
   *Result:* **Passed with 0 errors**.

---

## Author & Maintainer

- **Lead Architect & Developer:** Kasun Vimarshana
- **Email:** [kasunvmail@gmail.com](mailto:kasunvmail@gmail.com)
- **Phone:** [+94713525253](tel:+94713525253)
- **Repository:** [kasunvimarshana/AutoERP](https://github.com/kasunvimarshana/AutoERP)
