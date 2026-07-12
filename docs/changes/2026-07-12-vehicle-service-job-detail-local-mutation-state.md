# Vehicle service job detail local mutation state

Date: 2026-07-12

## Problem

The vehicle service job detail workflow reloaded the full job page after many child mutations. Actions such as saving inspection data, assigning employees, issuing inventory, and managing documents triggered parent `reload()` calls that cleared the detail screen into a loading state before restoring the same page.

This caused unnecessary API recalls, visible loading flashes, and made the job-detail experience feel heavier than the actual mutations required.

## Change

- moved the job detail page to a local `job` state model instead of treating the parent `useApi` resource as the mutation response path;
- replaced generic parent `reload()` calls with targeted callbacks from child tabs;
- kept status-history refresh separate from full-job refresh;
- updated inspection saves to persist only the inspection subresource and bump the local job version without reloading the page;
- updated line mutations to keep the line list local, notify the parent with the changed line collection, and recalculate displayed job totals from frontend line state;
- updated workforce mutations to mutate only the assignable-line state locally and bump the parent job version without refetching the full job;
- updated inventory issue mutations to remove issued lines from the inventory issue tab locally and bump the parent job version without parent reload;
- updated document create/delete mutations to keep the document list local and bump the parent job version without parent reload;
- kept status transitions consistent by updating local job state directly for start, complete, and cancel, while using a silent full-job refresh for inspect because that endpoint returns only the inspection resource.

## Verification

- `npm run typecheck`

## Scope

This change is limited to the vehicle service job detail frontend workflow and its child tabs. It reduces full-page loading flashes and unnecessary parent API recalls while preserving current backend contracts and module boundaries.
