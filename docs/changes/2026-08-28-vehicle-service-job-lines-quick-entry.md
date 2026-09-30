# Vehicle service job-lines quick entry

Date: 2026-08-28

## Why

Adding a service-job line required opening a drawer before the user could search for an item. The primary Job Lines workspace also separated quantity changes and whole-job discount entry from the table, increasing clicks during workshop entry.

## Changes

- Replaced the Add line button with the same searchable Item lookup used by the existing line drawer.
- Added the selected inventory, service, labour, combo, or package item immediately through the existing version-checked line API with quantity `1` and the lookup-provided UOM, cost, and price defaults.
- Prevented overlapping line mutations while quick-add, quantity update, edit, or removal requests are running.
- Kept inventory quick-add lines pending for the explicit, warehouse-aware stock-issue workflow.
- Retained the drawer for detailed edits and removed its unused create-only path.
- Focused the table on Item, Quantity, Unit price, Discount, Subtotal, and Actions, with inline quantity controls and stock state shown with the item.
- Moved the existing whole-job discount editor from Overview to a `Job Discount Value` section below the Job Lines table while preserving immutable revisions, reasons, permissions, and version checks.
- Allowed expanded combo quantities to change safely by rescaling snapshotted child quantities and combo-labour commission pools atomically. Combo item replacement and edits after inventory issue remain blocked.
- Updated related frontend assertions to the current backend-authoritative totals and explicit stock-issue design.

## Verification

- Vehicle Service combo quantity test passed: 1 test, 24 assertions.
- Vehicle Service quick-add, inline quantity, and inventory-flow tests passed: 13 tests.
- Vehicle Service line-editor rerun passed: 7 tests.
- `.\node_modules\.bin\tsc --noEmit`
- Focused ESLint checks passed.
- `npm run build`
- PHP syntax checks passed for the changed Vehicle Service files.
- Laravel Pint passed for the changed PHP files.
- `git diff --check`
