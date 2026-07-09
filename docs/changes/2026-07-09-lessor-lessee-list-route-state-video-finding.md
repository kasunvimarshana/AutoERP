# Lessor and Lessee List Route-State Video Finding

Date: 2026-07-09

## Context

Reviewed `Recording 2026-07-09 020600.mp4`, a six-second recording that repeatedly navigates between the dedicated lessee and lessor agreement list routes.

No runtime code was changed in this pass.

## Confirmed Finding

The lessor list page displays lessee records after client-side navigation:

- The page title, breadcrumb, navigation state, action, and party-column heading all change to the lessor presentation.
- The table still shows `Lessee agreement` rows belonging to `Walk-in Customer`.
- Navigating back to the lessee page and then to the lessor page reproduces the same result.

## Root Cause

`RentalAgreementListPage` derives the initial agreement kind from its `mode` prop, then stores that value in `useState`. React Router reuses the same component instance when navigating between the sibling lessee and lessor list routes, so the `kind` state retains `customer_rental` even after `mode` changes to `lessor`.

The visible lessor presentation is derived directly from the new `mode`, while the API filter is derived from the stale `kind` state. This creates the mixed page shown in the recording.

The existing frontend tests mount each mode independently and therefore do not cover route-to-route component reuse.

## Recommended Fix

- Treat the dedicated route mode as the authoritative agreement kind on every render instead of copying it into one-time component state.
- Keep mutable kind selection only for the generic agreement list.
- Reset pagination when the effective kind changes.
- Add one router-level regression test that navigates from lessee to lessor and asserts the second API call uses `owner_supply` and cannot render cached lessee rows.

The backend agreement-kind request validation and service query filter remain correctly separated; this is a frontend route-state ownership defect.
