# Vehicle Rental safe decommission after reactivation

**Date:** 2026-07-19

## Decision

The product owner explicitly requires the restored Vehicle Rental implementation to be removed end to end. The reviewed rental videos remain business evidence for a future clean implementation, but the restored code, schema design, workflows, and relationships are not an implementation source of truth and must not be preserved through compatibility patches.

## Audit scope

The audit covered the active module provider, commands, routes, controllers, requests, resources, models, services, enums, 26 source migrations, configuration, permissions, tenant entitlements, frontend pages and API clients, navigation, scheduler registration, Reporting definitions, Finance provisioning, Invoice and Payment handoffs, Customer and Supplier deletion blockers, Item usage catalogue, Vehicle availability lookup, generated runtime registrations, tests, and documentation.

The current target was verified to be the exact Vehicle Rental reactivation tree on top of the previously audited clean decommission baseline. Therefore the safe correction is to re-establish that clean runtime tree rather than manually deleting hundreds of files or carrying forward stale decommission exceptions.

## Active runtime removal

- removed the complete `app/Modules/VehicleRental` backend tree;
- removed all 26 Vehicle Rental source migrations from the fresh schema baseline;
- removed `config/vehicle_rental.php`, provider registration, scheduler command registration, API routes, permissions, tenant feature registration, and module catalogue entries;
- removed the complete `resources/js/modules/vehicle-rental` frontend tree, routes, navigation, icons, access mappings, and module tests;
- removed Vehicle Rental operational reports and Reporting registry integration;
- removed Rental-specific Finance seed accounts and posting profiles for new tenants;
- removed Rental-specific active Invoice, Payment, Customer, Supplier, Item, and Vehicle runtime handoffs at the owning modules;
- removed active implementation documents that described the retired design.

## Historical evidence and financial safety

The decommission intentionally preserves the vocabulary and owning-module records required to read immutable history:

- existing Invoice documents and balances;
- Payment documents, allocations, refunds, reversals, and unapplied balances;
- Tax documents and transactions;
- Finance journals and ledger entries;
- Voucher projections backed by Payment or Finance;
- historical Rental and Vehicle Finance discriminator values required to hydrate existing records;
- append-only audit and change records, including the earlier removal and reactivation decisions.

New Rental payment creation is rejected. Historical Rental and Vehicle Finance invoices remain readable and settleable where generic settlement is valid, but source-dependent lifecycle actions remain blocked because the source module no longer exists.

## Schema and production-data boundary

No destructive table-drop migration is introduced. Fresh installations no longer create Vehicle Rental tables because the source migrations are removed. Existing deployed databases may retain old Rental tables and rows for audit or archival purposes.

Any physical production-table teardown requires a separate explicitly approved archival operation with:

1. verified backup and restore rehearsal;
2. retention/legal approval;
3. reconciliation of Invoice, Payment, Tax, Finance, and Voucher references;
4. independently verified operational-data export;
5. a reviewed child-first dependency and purge plan.

## Relationship review

The following active cross-module relationships are removed because their owning source module is retired:

- Customer and Supplier deletion blockers that queried Rental-owned tables;
- Vehicle Rental-specific availability lookup and query variants;
- Item usage-module catalogue registration for Vehicle Rental;
- Reporting definitions that queried Rental-owned tables;
- Finance provisioning and Invoice/Payment UI handoffs that enabled new Rental operations.

Historical financial relationships remain owned by Invoice, Payment, Tax, Finance, and Voucher and are not deleted or rewritten. No unrelated relationship is modified. Vehicle Finance is not relocated through a compatibility alias; a future implementation requires its own approved owner and data migration.

## Future implementation boundary

The reviewed videos remain business source material for a future clean Vehicle Rental implementation. Future work must start from a new domain and schema design and must not restore or copy the removed module as a foundation.

## Verification

Connector-level verification includes:

- the target head was identical to the Vehicle Rental reactivation merge before this decommission;
- the resulting runtime tree is based on the previously audited clean decommission baseline;
- the prior reactivation record is preserved as append-only history;
- only a new decommission record is added to that clean tree;
- the branch is expected to contain no active Vehicle Rental provider, route, migration, navigation, report, permission, or frontend module registration.

The connector environment does not provide a runnable checkout. Before deployment, run the full Laravel SQLite/MySQL suites, route listing, Composer autoload generation, `migrate:fresh --seed`, TypeScript, ESLint, Vitest, production build, and exact runtime-reference scans on a normal checkout.
