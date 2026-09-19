# Vehicle service workforce panel and atomic bulk assignment

Date: 2026-09-13

## Why

Workforce assignment required a separate employee search and Add click on every service or labour line. Supervisor-designated lines also allowed an alternate supervisor even though the line represents the Job Card supervisor. This made the workflow slow and allowed the UI and domain rule to diverge.

## What changed

- Replaced each non-supervisor inline dropdown with a field that opens an accessible right-side employee picker.
- Added employee search, pagination, readable employee rows, assigned-employee exclusion, and click-to-select behavior to the picker.
- Changed employee choices to pending selections and added one Workforce-header `Assign selected employees` action.
- Removed all line-level Add buttons.
- Sorted supervisor-designated workforce lines before ordinary employee lines, including supervisor-containing groups before other groups.
- Made the Job Card supervisor a read-only automatic selection for supervisor-designated lines. Missing Job Card supervisors are explained and cannot be submitted.
- Locked supervisor assignments in the advanced edit drawer as well as the main assignment flow.
- Added `POST /api/v1/vehicle-service/jobs/{job}/employees/batch` under the existing workforce-manage permission.
- Added a dedicated batch request and DTO. The request limits batch size, requires unique line IDs, and uses the existing Job Card version contract.
- Added one transaction that locks the Job Card and selected lines, validates every assignment before any insert, recalculates affected lines and the Job Card, and increments the Job Card version once.
- Enforced in the backend that a supervisor-designated line must use the current Job Card supervisor and that the employee still has the Supervisor designation.
- Preserved pending UI choices after a stale-version rejection so the user can review and retry after the authoritative snapshot reloads.

## Data and schema impact

- No database migration was required.
- Existing unique assignment constraints remain the final database integrity guard.
- Existing single create, update, and delete endpoints remain available for advanced editing and removal.

## Verification

- TypeScript compiler: passed.
- Focused frontend workforce tests: 9 passed across the tab and employee picker.
- Full `VehicleServiceEngineTest`: 50 passed, 409 assertions.
- Batch route registration: verified.
- Production Vite build: passed (665 modules transformed).
- PHP Pint was applied to the changed backend files.

## Finalized design reference

See `docs/vehicle-service-workforce-assignment-panel-finalized.md`.
