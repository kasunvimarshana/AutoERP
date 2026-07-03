# Sales module end-to-end removal

Date: 2026-07-03

## Problem

The Sales module was no longer required, but it was still registered as a backend module, exposed through frontend routes and navigation, listed as a tenant-plan module, and referenced by cross-module usage/blocker checks and schema documentation. Keeping those references would leave a removed module partially enabled and create dead routes, stale migrations, and invalid table checks.

## Correction

Removed the Sales backend and frontend module trees, including Sales migrations, routes, provider registration, services, models, requests, resources, tests, and React pages/API helpers.

Removed Sales from tenant module catalogues, frontend entitlements, navigation, shared API endpoints, and route registration. Customer receipt entry now uses the Payment-owned payment entry route, while customer invoice listing no longer links to a removed Sales-owned creation flow.

Cleaned cross-module references that pointed to Sales-owned tables or classes from Customer, Warehouse, Item, Tax, Inventory tests, schema docs, audit docs, and frontend layout/access test fixtures. Business terms owned by other modules, such as sales invoice type, sales tax group, and sales price/unit roles, were intentionally preserved.

## Verification

- `composer dump-autoload -q`
- `php artisan route:list --path=api/v1/sales --except-vendor` reported no matching routes
- `php artisan route:list --except-vendor | Select-String -Pattern 'sales'`
- Sales hard-reference scans for deleted module paths, `/sales/` routes, `/api/v1/sales`, Sales provider imports, and Sales table names
- `php artisan test tests/Unit/Architecture/CoreModuleArchitectureTest.php app/Modules/Tenant/Tests/TenantPlanSchemaTest.php app/Modules/Inventory/Tests/InventoryIntegrityTest.php app/Modules/Tax/Tests/TaxEngineTest.php tests/Feature/Item/ItemApiTest.php --stop-on-failure`
- `php artisan migrate:fresh --seed --force` with in-memory SQLite testing environment variables
- `npx vitest run resources/js/app/layout/Sidebar.test.tsx resources/js/app/layout/WorkspaceLayout.test.tsx resources/js/app/navigation/navigationUtils.test.ts resources/js/modules/auth/accessControl.test.ts --reporter=dot --silent=true`
- `npm run typecheck`
- `npm run build`
- `git diff --check`

Full `php artisan test --stop-on-failure` was also attempted. It stops in `Tests\Unit\Database\ModuleMigrationBaselineTest` on pre-existing, unrelated migration-baseline violations in untouched July 2 migration files and `2026_06_12_200002_create_rental_agreements_table.php`.
