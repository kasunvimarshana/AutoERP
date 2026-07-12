# Frontend detail resource state rollout batch 1

Date: 2026-07-12

## Problem

Several frontend detail workflows still treated every successful mutation as a reason to reload the full page resource. That caused avoidable loading flashes and repeated API calls even when the mutation already returned the updated document or when a silent refresh of only the affected resource would have been enough.

## Change

- added a reusable frontend detail-resource state helper at `resources/js/shared/state/useDetailResourceStore.ts` using Zustand;
- kept the helper intentionally small so detail pages can share a consistent local resource pattern without forcing a global store architecture;
- applied the pattern to `PaymentDetailPage`:
  - submit, approve, post, and void now update local payment state from the mutation response;
  - refund and reversal now use a silent refresh of the main payment resource instead of reloading the whole page through `useApi`;
  - allocations and unapplied tabs only reload when their tab data is actually relevant;
- applied the pattern to `PurchaseReturnDetailPage`:
  - approve and cancel now update local detail state directly from the mutation response;
  - post now performs a silent document refresh instead of triggering the page loading state through `result.reload()`.

## Verification

- `npm run typecheck`

## Scope

This batch covers the shared helper plus the first adopted detail workflows in Payment and Purchase. It continues the frontend-only API recall reduction work started in Vehicle Service and establishes the reusable pattern for further module rollout.
