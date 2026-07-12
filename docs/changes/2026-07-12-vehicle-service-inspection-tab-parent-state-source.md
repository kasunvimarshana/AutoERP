# Vehicle service inspection tab parent-state source

Date: 2026-07-12

## Problem

The vehicle service job detail page already loaded inspection data as part of the main job resource, but the inspection tab still issued its own `GET /api/v1/vehicle-service/jobs/{id}/inspection` request. That created an unnecessary duplicate API call inside the same screen and made inspection-tab switching appear heavier than needed.

## Change

- removed the inspection tab's dedicated `useApi` fetch for `GET /vehicle-service/jobs/{id}/inspection`;
- made the inspection tab use the parent job detail's `inspection` payload as its source of truth for initial display;
- kept the existing save behavior intact by continuing to submit through the inspection update endpoint and then pushing the saved inspection back into the parent job state.

## Verification

- `npm run typecheck`

## Scope

This change only affects the vehicle service inspection tab inside the frontend job detail page. It reduces duplicate inspection requests without changing backend contracts or the existing mutation flow.
