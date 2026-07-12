# Global toast notifications foundation

Date: 2026-07-12

## Problem

Frontend request feedback was primarily rendered as inline `ErrorAlert` and `SuccessAlert` blocks inside pages and panels. That made errors feel sticky, visually noisy, and inconsistent across modules because messages stayed on screen until the user navigated away or refreshed.

## Change

- installed `react-toastify` and mounted a single shared toast container at the app root;
- added a reusable shared notification module at `resources/js/shared/notifications/appToast.tsx`;
- converted shared `ErrorAlert` and `SuccessAlert` to emit bottom-right toasts with a 5-second lifetime by default;
- kept optional inline rendering available through an explicit `inline` prop for places that still need a rendered alert block;
- added reusable `notifySuccess`, `notifyError`, `useSuccessToast`, and `useErrorToast` helpers for shared usage;
- integrated success toasts into shared CRUD flows and recently optimized list/master-data actions:
  - item relation CRUD
  - customer relation CRUD
  - supplier relation CRUD
  - customer list activate/status actions
  - vehicle list activate/deactivate
  - item list activate/deactivate
  - item brand/category list activate/delete flows
  - vehicle master-data create/update/activate flows

## Verification

- `npm run typecheck`

## Scope

This change establishes the reusable global toast foundation and applies it to the shared alert components plus a first batch of shared/frontend mutation flows. It improves feedback consistency without changing backend contracts or business logic.
