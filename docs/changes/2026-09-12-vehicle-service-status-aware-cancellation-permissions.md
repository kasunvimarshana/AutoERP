# Vehicle Service status-aware cancellation permissions and stock reversal reason

Date: 2026-09-12

## Requirement

- Allow Cashier-level users to cancel jobs only while they are `draft` or `inspected`.
- Restrict cancellation after work starts to administrator-level users through an assignable permission.
- When a started job is cancelled, reverse its issued inventory and identify the resulting stock movement as a job-cancellation reversal.
- Record the finalized design in a reusable Markdown guide.

## Changes

- Replaced cancellation access through the broad `vehicle_service.jobs.transition` permission with two module-owned permissions:
  - `vehicle_service.jobs.cancel` for `draft` and `inspected` cancellation;
  - `vehicle_service.jobs.cancel_after_start` as an additional elevated permission for `in_progress` and `completed` cancellation.
- Moved cancellation preview and execution routes under the ordinary cancellation permission. The backend service checks the elevated permission against the current locked status, preventing stale-status authorization bypasses.
- Updated frontend action visibility and billing-reversal guidance to mirror the backend rule.
- Extended Inventory's movement-specific reversal boundary to accept source-owned reason text. Vehicle Service now writes `Job cancellation: <reason>; Reversal of <movement number>` to each generated reversal movement description.
- Preserved the existing atomic stock, finance, reservation, vehicle-status, and job-history behavior.
- Added backend and frontend regression coverage for ordinary versus after-start permissions and for the inventory movement description.
- Added `docs/vehicle-service-job-cancellation-finalized.md` as the finalized business and implementation guide.

## Deployment

No database schema migration is required. Synchronize the tenant permission catalogue after deployment, refresh user sessions, grant `vehicle_service.jobs.cancel` to intended Cashier roles, and grant both cancellation permissions to intended administrator roles.

## Verification

- PHP formatter passed for all changed PHP files.
- Vehicle Service engine suite: **49 tests passed, 406 assertions**.
- Focused frontend cancellation suite: **2 files, 13 tests passed**.
- TypeScript typecheck passed.
- `git diff --check` passed.
