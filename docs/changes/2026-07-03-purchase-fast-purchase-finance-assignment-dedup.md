# Purchase fast purchase Finance assignment deduplication

Date: 2026-07-03

## Problem

Fast Purchase creation failed with "Unexpected server error" while posting the goods receipt Finance journal. The Finance account role resolver refused to resolve the `inventory` role because the local database had multiple active default assignments for the same tenant, organization unit, role, account, and effective period.

## Correction

Kept the fix in the owning Finance module. The Finance seeder now collapses exact duplicate active default account assignments by keeping the earliest assignment active and marking later exact duplicates inactive. It does not choose between conflicting account mappings.

Added regression coverage proving that rerunning the Finance seeder repairs exact duplicate seeded assignments. Reran the Finance seeder through the control-plane tenant execution context so the current local database no longer has ambiguous active assignments for the failing role.

## Verification

- `php artisan test app/Modules/Finance/Tests/FinanceSeederTest.php --stop-on-failure`
- `php artisan test app/Modules/Purchase/Tests --stop-on-failure`
- `php artisan route:list --path=api/v1/purchase --except-vendor`
- Finance active-assignment duplicate scan for `2026-07-03` returned `[]`
- Finance `inventory` role resolver now returns account `1200 / Inventory`
- `npm run typecheck`
- `npm run build`
- `git diff --check`
