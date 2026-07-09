# Vehicle Service lifecycle boundary

## Context

Vehicle Service used one job `status` to represent operational progress, billing progress, and payment progress. That mixed lifecycle dimensions and made states like completed + partially billed + unpaid impossible to represent truthfully.

## Change

- Added separate Vehicle Service lifecycle enums for operational, billing, and payment state.
- Replaced the service job table's single status source of truth with explicit `operational_status`, `billing_status`, and `payment_status` columns in the owning creation migration.
- Added lifecycle dimension tracking to Vehicle Service status history.
- Updated Vehicle Service model casts, job resources, list filters, operational transitions, invoice billing updates, and payment settlement updates.
- Updated main Vehicle Service frontend job list/detail/summary/invoice/history views to display separate lifecycle dimensions.

## Verification

- Compared the branch against `vehicle-service-version-hardening-20260709`; the diff is limited to Vehicle Service lifecycle boundary files and frontend Vehicle Service views.
- No runtime Laravel/MySQL, TypeScript, or production-like UAT suite was available in this connector session.

## Open gate before merge

Reporting still needs a dedicated pass to replace report-level references to the removed job `status` column with `operational_status` / `billing_status` / `payment_status` as appropriate. Do not merge until Reporting and full runtime checks pass.
