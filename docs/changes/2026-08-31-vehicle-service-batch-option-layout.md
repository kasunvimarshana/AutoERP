# Vehicle Service batch option layout

## Problem

Batch name, available stock, and service price were displayed as separate vertical lines in the Vehicle Service job-line item search result, making each result unnecessarily tall.

## Changes

- Aligned batch/lot reference, available stock, and resolved service price in one responsive horizontal row.
- Kept wrapping enabled for narrow screens so the values remain readable on mobile layouts.
- Preserved the existing single stock notice for non-batch items.
- Updated the item-search unit test mocks to use the current untracked-item and service-batch lookup contract.

## Verification

- Vehicle Service line-item focused test suite passed: 9 tests.
- Focused ESLint checks passed.
- TypeScript typecheck passed.
- Production frontend build passed.
- `git diff --check` passed.
