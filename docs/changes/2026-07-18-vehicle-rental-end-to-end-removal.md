# Vehicle Rental end-to-end removal

**Date:** 2026-07-18

## Problem

The implemented Vehicle Rental module did not provide a safe or maintainable foundation. Its agreement, allocation, custody, usage, calculation, deposit, vehicle-finance, Invoice, Payment, Finance, Reporting, and frontend workflows were tightly coupled across a large active surface. The required correction was a complete runtime decommission, not another compatibility patch.

## Audit scope and findings

The end-to-end audit covered the module provider, configuration, scheduler, API routes, permissions, services, models, 26 source migrations, tests, the 51-file frontend feature tree, navigation, tenant entitlements, shared lookups, Reporting, Invoice, Payment, Finance, Customer, Supplier, Item, Vehicle, generated Laravel/Composer metadata, and compiled Vite assets.

The relationship review confirmed that no surviving module migration has an inbound foreign key to a Vehicle Rental table. The active module could therefore be removed from the fresh schema baseline without rewriting another module's schema.

Invoice, Payment, Finance, and Voucher still contain persisted Rental and Vehicle Finance discriminator values. Those values are historical data vocabulary used to hydrate and describe existing immutable records; deleting them would make retained financial history unreadable. They are not an active module registration.

## Changes

### Active module removed

- Deleted the complete `app/Modules/VehicleRental` backend tree, including its provider, routes, permissions, commands, services, models, resources, requests, enums, tests, and 26 table-creation migrations.
- Deleted `config/vehicle_rental.php` and removed provider and scheduler registration.
- Deleted the complete `resources/js/modules/vehicle-rental` frontend tree.
- Removed Vehicle Rental routes, lazy imports, navigation, icons, entitlements, tenant module catalogue entries, API endpoints, and module-specific frontend tests.
- Removed the dedicated Vehicle Rental feature and unit test trees.

### Cross-module integrations removed at their owners

- Removed Rental-specific Customer and Supplier deletion blockers.
- Removed the Vehicle `rental-available` lookup/query/API variant and the Vehicle Service Rental availability integration test.
- Removed Vehicle Rental from Item's usage-module catalogue while retaining Item-owned rental price/unit semantics, which are independent Item capabilities.
- Removed Vehicle Rental and Vehicle Finance operational reports plus the dedicated Reporting definition service.
- Removed Rental posting-profile, revenue, expense, and security-deposit provisioning from fresh Finance seeds and test fixtures.
- Removed Rental-specific Invoice and Payment list/detail/create handoffs and generalized the shared Payment entry wording.
- Removed current implementation guidance while retaining historical audit and product-evidence documents under the authority defined by `docs/AUDIT_AUTHORITY.md`.

### Historical and new-write safety boundaries

- Tenant plans no longer advertise or accept `vehicle-rental`. Immutable historical plan snapshots may still contain that code, so `TenantPlanSchema` now has an explicit retired-module registry. Persisted snapshots are validated and filtered before entitlement/readiness use; unknown non-retired codes still fail closed.
- Tenant plan UI counts, comparisons, and edits use only currently supported module codes. A hidden retired snapshot value cannot be resubmitted or accidentally create a new revision.
- The public Payment creation request rejects `PaymentType::RentalReceipt`. The enum and historical policy branches remain so existing rows can still hydrate and render.
- Historical Rental and Vehicle Finance invoices are read-only for approve, post, cancel, void, and invoice reversal operations. This prevents terminal Invoice actions from committing after the deleted source-restoration handlers can no longer restore Rental calculation or finance-installment links.
- Existing posted historical invoices may still receive or reverse generic settlement allocations. This preserves collection and payment correction without reactivating the source module.

## Data and deployment safety

No destructive drop migration was added. Deleting the source migrations means fresh installations do not create Vehicle Rental tables, while deployed databases retain their existing tables and historical rows. Automatically dropping those tables would destroy audit and financial evidence and is intentionally outside this change.

If physical production-table teardown is required later, it must be a separate, explicitly approved archival operation with a verified backup, retention/legal sign-off, reconciliation of historical Invoice/Payment/Finance references, and a reviewed dependency plan.

Normal deployment must regenerate Composer autoload metadata, clear Laravel caches, rebuild frontend assets, and rerun tenant access provisioning. The provisioning owner deactivates permissions that are no longer registered instead of deleting permission history.

## Verification

- `composer dump-autoload --no-interaction` completed; Composer classmaps and Laravel service caches contain no deleted Vehicle Rental classes.
- Laravel boots successfully and both `php artisan route:list --path=vehicle-rental --except-vendor` and `--path=rental --except-vendor` report no matching routes.
- Exact runtime scans found no provider, config, scheduler, route, permission, endpoint, navigation, migration, report, deleted-class import, or table dependency. The only active exact code reference is the intentional retired tenant-plan marker; its regression test contains the matching fixture value.
- All modified surviving PHP files pass `php -l`.
- All 41 removal-focused backend tests pass, including tenant snapshot filtering, Payment creation rejection, Invoice read-only integrity, Finance seeding, Vehicle lookups, module API versioning, migration retention, and settlement eligibility.
- The complete backend suite reports 643 passing tests and one unrelated pre-existing failure: `2026_07_18_200003_add_type_to_vehicle_service_jobs_table.php` violates the fresh-baseline migration contract. That file is unchanged by this work.
- An isolated SQLite `migrate:fresh --seed --force` completes successfully without any Vehicle Rental migration or seeder.
- Frontend TypeScript validation passes. The full frontend suite passed 67 files / 239 tests, and the final focused suite passed 4 files / 23 tests after the historical-invoice guard was added.
- ESLint passes for every modified surviving frontend file.
- Full-project `npm run lint` still reports one unrelated existing error in unchanged `resources/js/modules/vehicle-service/components/VehicleServiceLineEditor.tsx:225` (`line` is unused).
- `npm run build` succeeds, clears the old compiled chunks, and the regenerated `public/build` contains no Vehicle Rental module reference.
- `git diff --check` passes.

## Result

Vehicle Rental is no longer an active AutoERP module in backend runtime, fresh schema, API, permissions catalogue, tenant catalogue, navigation, frontend runtime, Reporting, or new Finance provisioning. Historical financial discriminators, immutable audit evidence, and deployed data remain readable without allowing new Vehicle Rental operations.
