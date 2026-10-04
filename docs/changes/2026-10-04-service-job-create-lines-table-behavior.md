# Match service job line behavior during creation

## Request

Use the existing Job lines table behavior on the service job create screen, including item search, pricing, quantity controls, editing, removing, and totals.

## Changes

- Reuse the Vehicle Service line lookup and table for draft lines, preserving stock, service, labour, and combo/package search and the existing line table controls.
- Use the existing line editor for draft line edits and the existing confirmation dialog for removals.
- Make the item lookup optional on the create form so an empty search control does not prevent saving the job.
- Show stock availability in the preview without implying inventory has already been reserved.

## Verification

- TypeScript typecheck passed.
- `git diff --check` passed.
- The existing `VehicleServiceJobForm` test file ran 7 tests: 3 passed and 4 failed on mileage field decimal precision assertions (expected six decimal places, received two). These failures are outside the line table changes.
