# Tenant context and financial boundary audit

Date: 2026-07-01

## Purpose

Complete the backend audit that followed the finance/payment/tax trust-boundary corrections and the tenant execution-context hardening. The work fixes root causes instead of restoring removed legacy contracts or weakening tenant isolation.

## Corrections

- Tax posting profiles now use semantic posting keys instead of Finance account IDs.
- Invoice and Payment tax withholding integration now uses an Invoice-owned tax document provider contract instead of Payment importing Invoice models directly.
- Finance, Payment, Invoice, Purchase, Sales, Supplier, Vehicle, Inventory, HR, Customer, Warehouse, and Voucher direct engine tests now execute tenant-owned reads and writes inside the tenant execution boundary.
- Sales, Supplier, Vehicle, Inventory, HR, Customer, Warehouse, Item, UOM, and related validators now distinguish hidden cross-tenant references from genuinely missing records and return module-owned domain validation errors where appropriate.
- Purchase order validation now maps lifecycle and duplicate-number failures to field-aware validation responses, and exchange-rate checks use the tenant base currency source of truth.
- Payment instrument state resolution now avoids enum array-key coercion.
- Vehicle API tests now provide trusted tenant request context, an authenticated user, and tenant-scoped HTTP execution when middleware is disabled.
- Stale test assumptions were removed where they contradicted current contracts, including empty payment receipt lines, removed supplier-owned vehicle ownership enum cases, and Vehicle seeder ownership rows that are not created by the current seeder.

## Verification

- `php artisan test app/Modules/Sales/Tests/SalesEngineTest.php --stop-on-failure`
- `php artisan test app/Modules/Supplier/Tests/SupplierEngineTest.php --stop-on-failure`
- `php artisan test app/Modules/Vehicle/Tests/VehicleEngineTest.php --stop-on-failure`
- `php artisan test app/Modules/Voucher/Tests/VoucherWorkspaceTest.php --stop-on-failure`
- `php artisan test --stop-on-failure`

The full PHPUnit backend suite passes after these corrections.
