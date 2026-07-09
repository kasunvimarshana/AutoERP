# Lessor and Lessee List Route-State Fix

Date: 2026-07-09

## Context

Fixed the confirmed defect where client-side navigation from the dedicated lessee agreement list to the lessor agreement list retained the lessee API filter and displayed lessee rows under the lessor page presentation.

## Changes

- Made the dedicated route mode the authoritative agreement kind on every render.
- Kept mutable agreement-kind selection only for the generic agreement list.
- Scoped pagination state to the effective agreement kind so route-kind changes load page one.
- Added a router-level regression test that navigates from lessee page two to the lessor route and verifies an `owner_supply` page-one request, lessor data rendering, and removal of lessee rows.

## Verification

- Focused agreement-page suite: 1 file and 9 tests passed.
- TypeScript type checking passed.
- ESLint passed.
- Full frontend suite: 59 files and 217 tests passed.
- Production frontend build passed.
- `git diff --check` passed.

