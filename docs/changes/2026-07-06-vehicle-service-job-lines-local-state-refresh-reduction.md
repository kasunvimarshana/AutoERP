# Vehicle service job lines local state refresh reduction

Date: 2026-07-06

## Problem

Adding or removing a job line in the vehicle service job detail flow triggered repeated data reloads:

- the line editor reloaded the line list;
- the parent job detail page also reloaded the whole job through the `onChanged` callback.

This made each line mutation feel noisy and visually disruptive even though the user only changed one part of the job.

## Correction

Improved the vehicle service job lines flow with local state updates in the line editor:

- line add/edit/remove now updates the line list immediately in frontend state;
- the full line-list refetch after each mutation was removed;
- a lightweight toast-style success notice now confirms add, edit, and remove actions;
- the parent job detail page now refreshes silently in the background with `clearOnLoad = false` so totals and row version stay aligned without blanking the screen;
- the parent job `row_version` is bumped locally after line mutations so the next line action can continue without waiting for a visible full-page reload.

## Architectural note

This fix intentionally uses the existing local API state foundation (`useApi` + `setData`) instead of introducing a new Zustand store for a single tab interaction. The change stays scoped, reusable, and low-risk while removing the repeated full refresh behavior. A wider vehicle-service shared store can still be added later if broader cross-tab synchronization needs grow.

## Verification

- `npm run typecheck`
- `npm run lint -- resources/js/modules/vehicle-service/components/VehicleServiceLineEditor.tsx resources/js/modules/vehicle-service/pages/VehicleServiceJobDetailPage.tsx`
