# Customer list local row mutation state

Date: 2026-07-12

## Problem

The customer list page reloaded the full customer collection after row-level actions such as activate/deactivate and status changes. That caused unnecessary list API recalls and visible loading resets even though those mutations already returned the updated customer resource.

## Change

- added local collection state to the customer list page so the current paginated dataset remains mounted while row mutations complete;
- replaced full `result.reload()` calls after activate/deactivate and status changes with targeted row updates from the mutation responses;
- when a status change causes a row to no longer match the current filters, the row is removed locally instead of forcing a whole-list refetch;
- kept filter, pagination, and server-driven list loading behavior unchanged for actual query changes.

## Verification

- `npm run typecheck`

## Scope

This change is limited to the frontend customer list workflow. It reduces unnecessary list reloads for row-level customer mutations while preserving existing filters, pagination, and backend contracts.
