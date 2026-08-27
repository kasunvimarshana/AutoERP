# Purchase Order PDF export

Date: 2026-08-26

## Purpose

Allow authorized users to download a clear, printable A4 PDF from the Purchase Order detail page.

## Changes

- Added a tenant- and organization-unit-scoped Purchase Order PDF endpoint protected by the existing Purchase Order view permission.
- Added a dedicated Purchase module print service and Blade template containing organization, supplier, delivery, order, line, totals, notes, and approval information.
- Draft Purchase Orders receive a visible `DRAFT` watermark; approved and closed orders retain their workflow status without that watermark.
- Added an authenticated `Download PDF` action to the Purchase Order detail page.
- Added API coverage for successful downloads and cross-tenant isolation.

## Design

- PDF generation remains owned by the Purchase module.
- Persisted PO quantities, prices, adjustments, and totals are rendered directly without recalculation in the browser.
- The existing `orders.view` permission is the single authorization rule for viewing and exporting the same Purchase Order.

## Verification

- Focused Purchase Order PDF API tests passed: 2 tests, 12 assertions.
- Frontend TypeScript checking and the production Vite build passed.
- PHP syntax and formatting checks passed for the changed backend files.
- Approved and draft A4 PDFs were rendered to PNG and visually inspected; both remained on one page with aligned columns, legible totals, and no clipping or overlap.
