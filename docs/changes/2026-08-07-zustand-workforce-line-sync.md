# Zustand workforce synchronization for job-line mutations

## Why

The Vehicle Service detail page keeps opened tabs mounted. Workforce therefore loaded its assignable lines only on first mount and showed stale data after a user added, edited, or removed a Job Line in the same page session.

## What changed

- Added a job-scoped Zustand store for the Workforce snapshot (assignable lines, job row version, and supervisor context).
- Job Line create, update, and delete responses now include the authoritative job `row_version` and full `workforce_lines` projection in response metadata.
- Job Lines replace the shared Workforce cache directly from that mutation response, without calling the employee-assignable-lines endpoint again for the local change.
- Workforce subscribes to the shared cache, keeps cached rows visible during refresh, and revalidates in the background whenever the user returns to the tab so changes from concurrent actors are detected.
- Moved assignable-line projection and commission-default decoration into `VehicleServiceAssignableLineService`, shared by the Workforce endpoint and Job Line mutation responses.
- Job Line deletion now returns HTTP 200 with `data: null` and the same mutation metadata instead of an empty HTTP 204 response.

## Verification

- Vehicle Service backend mutation contract test passes for create, update, and delete snapshots.
- Focused React tests pass (14 tests), including cached tab re-entry revalidation.
- TypeScript typecheck, focused ESLint, PHP Pint, and the production Vite build pass.
