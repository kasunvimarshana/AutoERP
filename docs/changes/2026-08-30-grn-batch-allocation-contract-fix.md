# GRN batch allocation contract fix

## Problem

Creating a goods receipt for a batch-tracked item returned `422 Validation failed` with `Batch or lot tracked receipt lines require batch allocations`, while the Edit receipt line drawer did not show any batch/lot controls.

## Root cause

The Purchase receivable-lines response manually projected the source item and omitted `tracking_type`. The GRN frontend therefore treated the line as non-tracked and hid the allocation editor, while the backend loaded the full item record and correctly enforced batch allocation validation.

## Changes

- Added `item.tracking_type` to the Purchase receivable-lines contract.
- Added client-side batch allocation completeness checks and prevented saving/submitting tracked lines until the accepted quantity is fully allocated.
- Kept the backend as the source of truth and mapped allocation validation errors to the exact `lines.{index}.batch_allocations` field.
- Added backend and frontend regression coverage for the tracked receipt flow.
- Updated the existing GRN source-flow test to complete its expected source-change confirmation before asserting cleared state.

## Verification

- Purchase backend regression: 1 test passed, 9 assertions.
- Purchase frontend source-flow suite: 11 tests passed.
- TypeScript typecheck passed.
- Focused ESLint checks passed.
- Production frontend build passed.
- `git diff --check` passed.
