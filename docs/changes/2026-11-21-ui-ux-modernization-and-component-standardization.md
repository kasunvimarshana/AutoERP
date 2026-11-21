# UI/UX Modernization, Accessibility Enhancements, and Component Standardization

**Date:** 2026-11-21  
**Modules:** Shared UI Components, Layout & Shell, Invoice, Payment, Selling, Warehouse, HR, Promotion, Reporting, Vehicle Rental

### Summary of Changes

1. **Core Shared Component Standardization & Bug Fixes:**
   - **`resources/js/shared/components/GenericLookupSelect.tsx`**:
     * Fixed active item indexing and keyboard arrow navigation bounds when exclusions are active by referencing `visibleOptions.length - 1` instead of `options.length - 1`.
     * Fixed active item selection to map `visibleOptions[activeIndex]?.id` instead of `options[activeIndex]?.id`.
     * Added an inline clear (`×`) suffix button on the input when a value is selected and the input is not disabled, allowing fast one-click clearing without manual backspacing, while honoring rejection if `onChange` returns `false`.
   - **`resources/js/shared/components/Input.tsx`**:
     * Added support for an optional `suffix?: ReactNode` prop with relative container wrapping and responsive padding adjustment (`pr-9`).
   - **`resources/js/shared/components/Modal.tsx` & `Drawer.tsx`**:
     * Explicitly passed `size="sm"` to modal and drawer header close buttons to prevent the default `size="md"` `min-h-10` from overriding compact `h-9` sizing.
   - **`resources/js/shared/components/Pagination.tsx`**:
     * Added `size="sm"` to Previous and Next buttons to ensure proper vertical baseline alignment with the page status badge.
   - **`resources/js/shared/components/Tabs.tsx`**:
     * Added optional `variant?: 'underline' | 'pills'` prop (defaulting to `'underline'`).
     * Implemented rounded segmented pills styling (`inline-flex p-1 bg-slate-100 rounded-lg gap-1`, active: `bg-white text-slate-900 shadow-xs font-semibold`, inactive: `text-slate-600 hover:text-slate-900 hover:bg-slate-200/50`) while preserving full WAI-ARIA tab semantics and keyboard arrow navigation.
   - **`resources/js/modules/invoice/components/InvoiceStatusBadge.tsx`**:
     * Standardized styling tokens to align with `StatusBadge.tsx` (`px-2.5 py-0.5 text-xs font-medium rounded-full shadow-2xs border`) using `50` background, `200` border, and `800` text color tokens across status variants.

2. **Layout, Shell & Global Styling:**
   - **`resources/views/welcome.blade.php`**:
     * Removed `selection:bg-blue-600 selection:text-white` from `<body>` so global enterprise selection colors from `app.css` (`#dbeafe` / `#1e3a8a`) apply cleanly.
   - **`resources/js/app/layout/AppHeader.tsx`**:
     * Replaced `focus:ring-2` with `focus-visible:ring-2` on hamburger menu and sidebar collapse buttons to eliminate persistent focus rings following mouse clicks.
   - **`resources/js/app/layout/Sidebar.tsx`, `OrganizationUnitSwitcher.tsx` & `AuthProvider.tsx`**:
     * Integrated `OrganizationUnitSwitcher` into the mobile drawer view of `Sidebar.tsx`, enabling organization unit switching on mobile/tablet viewports rather than displaying locked static text.
     * Exported `useOptionalAuth()` from `AuthProvider.tsx` and consumed it cleanly in `OrganizationUnitSwitcher.tsx`, eliminating conditional hook calls and preserving strict Rules of Hooks compliance.

3. **Elimination of Raw Database IDs & Relational Clarity:**
   - **`resources/js/modules/payment/pages/PaymentEntryPage.tsx`**:
     * Replaced raw numeric fallback `{doc.invoice_number ?? `Inv #${doc.id}`}` with `{doc.invoice_number ?? 'Invoice (unassigned)'}`.
     * Replaced raw HTML `<select>` with standard design system `<Select>`.
   - **`resources/js/modules/warehouse/WarehouseListPage.tsx` & `WarehouseLocationListPage.tsx`**:
     * Replaced fallback string `auth.organizationUnit.name ?? `Organization ${auth.organizationUnit.id}`` with human-readable `auth.organizationUnit.name ?? 'Current organization unit'`.
   - **`resources/js/modules/hr/components/EmployeeStatusHistoryTab.tsx`**:
     * Eliminated raw numeric database user ID from the UI display.

4. **Replacement of Browser Dialogs with Design System Modals:**
   - **`resources/js/modules/invoice/pages/InvoiceDetailPage.tsx`**:
     * Replaced raw `window.prompt()` cancellation flow with a dedicated accessible `<Modal>` dialog containing reason input, validation, form submission on Enter, inline error alerts, and cancel/confirm actions.
   - **`resources/js/modules/payment/pages/PaymentDetailPage.tsx`**:
     * Replaced raw `window.confirm()` unallocation prompt with standard `<ConfirmDialog>`, including safe dialog dismiss on API error.
   - **`resources/js/modules/promotion/pages/PromotionWorkspacePage.tsx`**:
     * Replaced raw `window.confirm()` offer deletion prompt with standard `<ConfirmDialog>`.

5. **Button Ordering, Alignment, and Table Formatting:**
   - **Action Button Hierarchy:**
     * `resources/js/modules/vehicle-rental/AgreementEditor.tsx`: Standardized button order with secondary Cancel on left and primary Save draft on right.
     * `resources/js/modules/selling/pages/SellingWorkspacePage.tsx`: Standardized refund modal buttons with Cancel on left and Post refund on right. Also replaced raw `<select>` with `<Select>` and migrated custom pagination to standard `<Pagination>`.
   - **Monetary & Decimal Column Alignment:**
     * Added `text-right` alignment to currency/numeric columns in `ReportDataGrid.tsx`, `InvoiceListPage.tsx`, `PaymentListPage.tsx`, and `SellingWorkspacePage.tsx`.
   - **Accessible Search Inputs:**
     * Added descriptive `aria-label="Search invoices"` and `aria-label="Search payments"` to list page search fields.
   - **Pill Tabs Migration:**
     * Migrated bespoke tab buttons in `AgreementsPage.tsx` and `BillingCycleWizardModal.tsx` to use `<Tabs variant="pills">`.

### Verification Record

- **TypeScript Typecheck:**
  - Ran `npm run typecheck` (`tsc --noEmit`): passed with 0 errors.
- **Frontend Test Suite:**
  - Ran `npm test -- --run` (`vitest run`): 169 test files passed (169 total), 692 tests passed (692 total).
  - Explicitly verified unit tests for:
    * `GenericLookupSelect.test.tsx` (exclusions, arrow navigation, clear button, change prevention).
    * `InvoiceDetailPageCancel.test.tsx` (cancellation modal dialog, form submission, reason prompt, inline error alert).
    * `PaymentDetailVersionFlow.test.tsx` (optimistic versioning, ConfirmDialog for draft deletion).
    * `PromotionWorkspacePage.test.tsx` (ConfirmDialog for offer deletion).
    * `Tabs.test.tsx` (underline and pills variants, keyboard navigation, badge styling).
    * `InvoiceStatusBadge.test.tsx` (standardized design tokens).
    * `BillingCycleWizardModal.test.tsx` (pill tabs integration).
    * `AppHeader.test.tsx`, `Sidebar.test.tsx`, `WorkspaceLayout.test.tsx`.
- **ESLint Check:**
  - Ran `npm run lint`: 0 errors.
- **Backend Test Suite:**
  - Ran `php artisan test`: 1,247 tests passed (13,094 assertions), 0 failures.
- **Laravel Pint:**
  - Ran `vendor/bin/pint resources/views/welcome.blade.php --test`: passed.
