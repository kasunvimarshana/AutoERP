# Vehicle Service commission tenant identity correction

Date: 2026-07-14

## Failure

The full SQLite and MySQL suites exposed the same tenant-isolation architecture violation for the two new Vehicle Service commission policy tables:

- `vehicle_service_supervisor_commission_policies` lacked a unique `['id', 'tenant_id']` candidate key;
- `vehicle_service_labor_item_commission_rules` lacked a unique `['id', 'tenant_id']` candidate key.

All feature-specific commission tests, frontend tests, type checking, linting, and production build passed. The only failing contract was the shared tenant schema identity rule.

## Root cause

Both tables were tenant-owned and already used same-tenant composite foreign keys, but their create migrations omitted the project-wide composite identity candidate key required for every tenant-owned parent table.

## Correction

The authoritative create migrations now define:

- `vs_supervisor_commission_id_tenant_uk` on `['id', 'tenant_id']`;
- `vs_labor_commission_id_tenant_uk` on `['id', 'tenant_id']`.

No model, service, API, permission, relationship, or business calculation changed.

## Existing databases

This repository intentionally uses fresh-baseline create migrations and rejects incremental `Schema::table` migrations. A database that already ran the two original migrations will not receive the new indexes from `php artisan migrate` alone.

For a disposable local database, rebuild it through the normal fresh migration workflow.

For a database whose data must be preserved, add the two indexes once before rerunning the suites:

```sql
ALTER TABLE vehicle_service_supervisor_commission_policies
    ADD UNIQUE KEY vs_supervisor_commission_id_tenant_uk (id, tenant_id);

ALTER TABLE vehicle_service_labor_item_commission_rules
    ADD UNIQUE KEY vs_labor_commission_id_tenant_uk (id, tenant_id);
```

These statements are additive and do not modify or delete commission policy rows.

## Verification

```bash
php artisan test --filter=TenantIsolationArchitectureTest
php artisan test --filter=VehicleServiceCommissionPolicyTest
php artisan test
composer test:mysql
npm run typecheck -- --pretty false
npm run lint
npm run test
npm run build
```
