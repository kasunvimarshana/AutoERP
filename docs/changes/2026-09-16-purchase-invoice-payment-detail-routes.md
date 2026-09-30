# Purchase Invoice and Payment Detail Routes

Date: 2026-09-16

## Summary

Moved supplier invoice and supplier payment detail navigation to Purchase-owned routes so the workspace sidebar and breadcrumbs remain in the Purchase module.

## Root cause

Purchase lists previously opened shared detail pages at `/invoices/:id?from=purchase` and `/payments/:id?from=purchase`. The Vehicle Service sidebar intentionally matches canonical `/invoices/*` and `/payments/*` paths, so the correct supplier record loaded while the UI incorrectly selected the Service Invoices or Customer Receipts context.

## Changes

- Added `/purchase/invoices/:id` and `/purchase/payments/:id` frontend routes that reuse the canonical invoice and payment detail components.
- Added explicit Purchase route entitlements for both detail routes.
- Updated supplier invoice creation, invoice lists, payment preparation, payment creation, and supplier payment lists to use the Purchase-owned detail paths.
- Derived Purchase detail-page actions from the owning route instead of a `from=purchase` query parameter.
- Preserved the canonical `/invoices/:id` route and vehicle-service job query context for service invoices.
- Added regression coverage for sidebar ownership, route entitlements, canonical service-invoice context, and updated Purchase links.

## Verification

- Focused navigation, access, Purchase, and invoice suites passed: 6 files, 52 tests.
- ESLint passed for all changed frontend files.
- Production Vite build passed with 667 modules transformed.
- git diff --check passed before this change record was added.
- Full TypeScript verification reports only the pre-existing optional `result.meta` issue in `VehicleServiceHistoryReportPage.tsx`.
