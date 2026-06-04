# Database Seeding Strategy

This project uses layered seeders so the database can be rebuilt repeatedly for local development, QA, and demos without truncating user data outside `migrate:fresh`.

## Execution Order

`Database\Seeders\DatabaseSeeder` runs seeders in dependency order:

1. `CoreBootstrapSeeder`: tenant, organization unit, currencies, admin user, roles, permissions, chart basics, UOM/payment/warehouse/item bootstrap records.
2. Authentication and reference modules: auth providers, UOM, finance, warehouse, item, customer, supplier, HR, vehicle.
3. Operational foundations: inventory, pricing, payment, document, purchase, sales, vehicle service, vehicle rental, voucher, invoice type catalogs.
4. `AutoErpScenarioSeeder`: cross-module development and QA scenarios.

Every seeder uses stable business keys with `updateOrInsert`, so running `php artisan db:seed` repeatedly updates the same records instead of duplicating them.

## Dataset Controls

Reference data is safe to run anywhere after migrations.

Scenario/demo data is controlled by `SEED_AUTOERP_SCENARIOS`:

- Unset: enabled only in `local` and `testing`.
- `true`: force scenario data on.
- `false`: force scenario data off.

Useful commands:

```bash
php artisan db:seed
SEED_AUTOERP_SCENARIOS=false php artisan db:seed
php artisan migrate:fresh --seed
```

## Covered Scenarios

The seeded graph includes:

- Active, inactive, blocked, and credit-hold customers and suppliers.
- Hierarchical customer categories, departments, and warehouse locations.
- Roles, permissions, admin user access, and auth providers.
- UOM conversions, item categories, item types, items, variants, identifiers, bundles, and supplier item links.
- Finance chart basics, fiscal year/periods, bank account, journal, budget, AP/AR transactions.
- Warehouse, inventory stock levels, batches, serials, reservations, transfers, adjustments, and trace logs.
- Price lists, price list items, tiers, discounts, and pricing rules.
- Payment methods, cash register, payments, payment allocations, and invoice allocations.
- Purchase order to GRN to payable invoice to outbound payment.
- Sales order to GDN to receivable invoice to inbound payment.
- Invoice status histories, notes, source documents, line sources, charges, discounts, credit link, overdue invoice, and cancelled invoice.
- Vehicles, ownerships, customer vehicle links, and supplier/provider vehicle links.
- HR departments, designations, employment types, active/probation/terminated employees, contacts, contracts, shifts, leave, and salary structures.
- Extension records for attachments, comments, entity attributes, plus audit log evidence.

## Seeder Conventions

When adding a new module seeder:

- Seed master/reference rows inside the owning module.
- Put cross-module transactional scenarios in `AutoErpScenarioSeeder`.
- Use tenant-scoped stable keys such as `code`, `number`, `sku`, or `employee_code`.
- Use `updateOrInsert`; do not truncate tables in module seeders.
- Guard optional tables with `Schema::hasTable`.
- Prefer explicit historical dates for repeatability.
- Keep negative cases realistic: blocked, inactive, cancelled, overdue, rejected, reversed, and partial states.
