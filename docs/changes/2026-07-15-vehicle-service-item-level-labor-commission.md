# Vehicle Service item-level labor commission

Date: 2026-07-15

## Problem

The first commission-default implementation made labor-item commission policy depend on a workforce role. That created multiple defaults for the same labor item even though the confirmed business rule is one reusable commission default per labor item.

The role dimension also made the item-creation form ask for an operational assignment role that does not belong to Item setup.

## Correction

Vehicle Service now owns one labor commission rule for each tenant, organization unit, and labor item:

- `tenant_id`
- `organization_unit_id`
- `item_id`
- `commission_type`
- `commission_value`
- `is_active`
- optimistic `row_version`

The rule no longer stores or resolves `role_type`.

The canonical labor-policy endpoints are now:

```text
GET /api/v1/vehicle-service/commission-policies/labor-items/{item}
PUT /api/v1/vehicle-service/commission-policies/labor-items/{item}
```

Assignable job lines expose one `commission_default` instead of a role-keyed `commission_defaults` map. Selecting a labor line applies that item default. Changing the employee's operational role does not replace the commission.

Existing assignment commission values remain snapshots. Editing an assignment without explicitly changing commission preserves its stored commission even when the operational role changes.

## UOM ownership

Commission policy does not own or restrict UOM. Item and UOM remain the single source of truth. A labor item may use Hour, Unit, Job, Service, Panel, or any other valid controlled UOM. Percentage commission is calculated from the resulting labor line total.

## Assignment fields intentionally preserved

The following assignment fields were reviewed and retained:

- `role_type` identifies the employee's responsibility on the specific job and supports role filtering and reporting;
- `assigned_hours` supports workload and productivity totals;
- `rate` combines with assigned hours to produce the labor-cost amount in Technician Work reporting.

These fields are not part of labor-item commission policy. Removing them would break existing operational and reporting contracts, so no assignment schema or relationship was changed.

## Relationship review

Removed relationship dimension:

```text
labor commission rule -> workforce role
```

Retained justified relationships:

```text
labor commission rule -> labor item
employee assignment -> employee
employee assignment -> job line
employee assignment -> operational role metadata
```

No reverse Vehicle Service relationship was added to Item. Item does not own commission behavior, and historical employee assignments continue to own their actual commission snapshots.

## Concurrency and history

Policy writes continue to lock the organization unit and policy row, require `expected_version` for updates, and reject stale writes. Changing an item default does not recalculate existing assignments.

## Existing databases

The repository uses fresh-baseline create migrations. The authoritative labor-rule create migration now omits `role_type` and enforces one unique rule for `tenant_id`, `organization_unit_id`, and `item_id`.

A disposable development or test database should be rebuilt through the normal fresh-migration workflow after pulling this change.

A persistent database must not collapse multiple role-specific rows automatically. First inspect whether more than one rule exists for any item. If duplicates exist, the business owner must select the single intended item default before the column and old unique key are removed. The application does not guess which historical role rule should win.

## Verification

```bash
php artisan test --filter=VehicleServiceCommissionPolicyTest
php artisan test --filter=TenantIsolationArchitectureTest
npx vitest run resources/js/modules/vehicle-service/components/employee-assignment/assignmentForm.test.ts --reporter=dot --silent
php artisan test
composer test:mysql
npm run typecheck -- --pretty false
npm run lint
npm run test
npm run build
```
