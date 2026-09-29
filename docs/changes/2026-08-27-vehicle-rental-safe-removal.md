# Vehicle Rental safe removal — 2026-08-27

## Decision

Vehicle Rental is removed from the active AutoERP runtime end to end from `worktree-0.0.8`.

## Removed active surface

- the complete `app/Modules/VehicleRental` backend implementation and its source migrations;
- the complete `resources/js/modules/vehicle-rental` frontend implementation;
- Vehicle Rental provider registration, tenant feature/catalogue entries, routes, navigation, and route entitlements;
- Rental-specific Reporting controllers, requests, services, routes, and tests;
- Rental-specific Finance seed accounts and posting profiles for new tenants;
- Vehicle Rental feature/unit tests and the three Rental-only root migrations added after the fresh implementation.

## Historical financial safety

`InvoiceType::Rental` and the owning Finance/Payment vocabulary required to interpret already-posted history are retained. Rental invoices are treated as retired-source documents for source-dependent lifecycle actions while generic historical read/settlement behavior remains owned by Invoice and Payment.

No destructive migration is introduced to drop already-deployed Rental tables. Fresh installations no longer create the Rental schema because its source migrations are removed. Physical teardown of existing production tables requires a separately approved archival, backup, restore, retention, reconciliation, and purge operation.

## Tenant plans

Plan feature schema version advances to 3. Persisted schema-version 1 and 2 snapshots silently filter the retired `vehicle-rental` code on read; new plan writes reject it because it is no longer part of the supported commercial-module catalogue.

## Preserved shared functionality

Vehicle and Vehicle Service remain active. Shared Vehicle availability/odometer behavior and the later Summary Report route are intentionally preserved; they are not owned by Vehicle Rental.

## Verification boundary

The repository change is constructed as one atomic tree update and is reviewed for residual active Vehicle Rental provider, route, tenant-feature, navigation, Reporting, Finance-seeding, and fresh-migration references before the target branch is advanced.
