# Service invoice pay button source resolution

Date: 2026-07-12

## Problem

The `Pay this invoice` action for vehicle service invoices disappeared for existing unpaid invoices unless the page URL still contained `?from=vehicle-service&job_id=...`. The invoice detail screen was relying on transient route context instead of the invoice's own source relationship to resolve the owning service job.

## Change

- updated the invoice detail backend response to load invoice `sources` together with `lines`;
- extended the frontend invoice types to include `source_id` and the invoice-level `sources` collection;
- updated the invoice detail page to derive the vehicle service job id from the invoice's own `vehicle_service_job` source record, with the URL query kept only as a fallback;
- kept the `Pay this invoice` action routed to `/vehicle-service/jobs/{jobId}/payment`.

## Verification

- `npm run typecheck`
- `npx vitest run resources/js/modules/invoice/pages/InvoiceLifecycleHandoff.test.tsx --reporter=dot`

## Scope

This change fixes the service invoice payment handoff at its data source. It preserves the intended vehicle-service payment route while restoring button visibility for unpaid service invoices opened outside the immediate invoice-post redirect flow.
