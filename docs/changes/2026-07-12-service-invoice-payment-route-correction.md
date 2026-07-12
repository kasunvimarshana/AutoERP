# Service invoice payment route correction

Date: 2026-07-12

## Problem

The `Pay this invoice` action on service invoices was pointing to the generic invoice settlement route at `/payments/create?invoice_id={id}`. For the vehicle service workflow, the expected handoff is the job-owned payment route at `/vehicle-service/jobs/{jobId}/payment`.

## Change

- updated the vehicle service invoice-post redirect to include vehicle-service context in the invoice URL as `?from=vehicle-service&job_id={jobId}`;
- updated the invoice detail page to detect that vehicle-service context and route `Pay this invoice` to `/vehicle-service/jobs/{jobId}/payment`;
- kept the service-invoice payment action hidden unless the invoice is payable and a valid vehicle-service job id is present in the route context;
- updated the lifecycle handoff test coverage to assert the corrected route.

## Verification

- `npm run typecheck`
- `npx vitest run resources/js/modules/invoice/pages/InvoiceLifecycleHandoff.test.tsx --reporter=dot`
- direct run of `resources/js/modules/vehicle-service/pages/VehicleServiceInvoiceCreatePage.test.tsx` is still blocked by the existing Vitest `react-router` ESM loading issue in the current test environment

## Scope

This is a frontend-only correction for the vehicle service invoice-to-payment handoff. It restores the expected service job payment route without changing backend contracts or unrelated invoice flows.
