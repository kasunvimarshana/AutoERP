# Inventory submenu navigation

## Summary

- Added permission-aware `Adjustments`, `Batch / Serial`, and `Stock Movements` links beneath the Inventory sidebar module.
- Made Inventory tabs URL-driven so direct links such as `/inventory?tab=adjustments` and `/inventory?tab=tracking` open the requested workflow and remain synchronized with sidebar highlighting.
- Linked `Stock Movements` to the existing Reporting-owned `/reports/inventory.stock-movement` report instead of duplicating report functionality inside Inventory.
- Preserved child-specific permission rules when tenant workspace navigation applies the Inventory route permissions.
- Updated navigation coverage for the current Reporting links and the new Inventory shortcuts.

## Verification

- `npm run build` passed.
- `npx vitest run resources/js/app/navigation/navigationUtils.test.ts --pool=forks --maxWorkers=1 --no-file-parallelism --reporter=dot` passed: 15 tests.
- `git diff --check` passed before the change record was added.
