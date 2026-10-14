# Use stock items in vehicle service combos

## Request

Allow vehicle service combo definitions to include stock components such as wash liquid, then consume and cost those components through the existing Vehicle Service and Inventory flows. Combo selling in the Selling module is out of scope.

## Changes

- Added a dedicated item lookup for bundle children that returns stockable stock/consumable items and supported service, labour, and non-stock items.
- Allowed consumable items as combo bundle children and required stockable bundle children to use the stock line type.
- Updated the bundle editor to select stock components, derive their line type from the selected item, and explain that configured quantities are consumed when a service job starts and costed from inventory valuation.
- Confirmed Vehicle Service already expands combo children, reserves stock on job-line save, issues stock and records inventory valuation/COGS when the job starts, and reverses issues/releases reservations on cancellation. No Vehicle Service or schema changes were needed.
- Left the Selling module unchanged.

## Verification

- TypeScript typecheck passed.
- PHP syntax checks passed for the changed PHP files.
- `git diff --check` passed.
- Automated tests were not run.
