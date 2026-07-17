# Frontend state and bundle hardening

Date: 2026-07-14

## Problem

The frontend verification baseline was functionally green, but two maintainability and performance issues remained:

- fifteen `react-hooks/set-state-in-effect` warnings were caused by copying API data or parent-owned props into duplicate local state, and by resetting forms synchronously from effects;
- the production application entry chunk exceeded the configured 500 kB warning threshold even though route pages were already lazy loaded.

The duplicated vehicle-service `expectedVersion` state was also a concurrency ownership concern because child tabs mirrored the parent job version bidirectionally.

## Correction

- Extended the shared `useApi` state owner with typed functional `setData` updates.
- Removed duplicated list and relation collection state from customer, item, supplier, and vehicle master screens.
- Kept the vehicle-service job resource and optimistic concurrency version in the parent API state; document, workforce, inventory, inspection, and line tabs now report successful version changes back to that owner instead of maintaining mirrored versions.
- Replaced inspection and reversal form reset effects with explicit component lifecycle boundaries.
- Added focused coverage for functional `useApi` updates.
- Added a named Vite vendor chunk strategy that separates third-party dependencies from application code without increasing or hiding the warning threshold.
- Replaced newly encountered non-obvious numeric literals with descriptive constants in modified files.

## Relationship review

No database, model, API relationship, or module dependency relationship was changed. The reported issues were frontend state ownership and bundling concerns. Existing business relationships remain valid, and changing them would have been unrelated and unsafe.

The only ownership simplification is within React state: API resources and the vehicle-service job version now have one authoritative owner rather than duplicated bidirectional state.

## Verification

Run the following from the authoritative `worktree-0.0.8` branch:

- `git diff --check`
- `npm run typecheck -- --pretty false`
- `npm run lint`
- `npm run test`
- `npm run build`
- `php artisan test`
- `composer test:mysql`

Expected frontend result: no `set-state-in-effect` warnings, all tests pass, and the application entry chunk remains below the warning threshold after vendor extraction.
