# Fresh Vehicle Rental domain foundation

## Why

The previous Vehicle Rental runtime was intentionally removed. This change starts a clean implementation from that removal baseline and uses the audited videos as the business source of truth without restoring the retired module.

## Scope

This first batch intentionally contains only the operational foundation:

- separate customer and owner agreements;
- immutable, effective-dated rate versions and rate lines;
- effective-dated customer-use and owner-supply vehicle assignments;
- handover, return, cancellation, and atomic replacement custody history;
- vehicle, driver, agreement-period, ownership, legal-document, and Vehicle Service availability validation;
- deterministic row locking and optimistic version checks;
- tenant feature and permission registration;
- agreement and assignment API endpoints.

Running Charts, calculations, deposits, Invoice/Payment/Tax/Finance integration, reports, and React workflow are deliberately excluded until this foundation passes complete project gates.

## Business rules preserved

- Customer and owner agreements are independent.
- Owner-supply assignments and customer-use assignments have distinct meanings.
- A customer-use assignment may reference an owner-supply assignment covering the same vehicle and complete period.
- A company-owned customer assignment does not require an owner agreement.
- Daily customer agreements use either a standard daily base rate or AC-mode daily rates, never both.
- Owner and monthly agreements require one standard base rate matching their billing basis.
- Draft agreements are editable; activated commercial history is immutable.

## Relationship decisions

- Assignment source and replacement lineage use tenant-composite foreign keys to prevent cross-tenant links.
- Vehicle owns the shared availability contract. Vehicle Service and Vehicle Rental contribute independent blockers without querying each other’s tables.
- Currency remains a global Reference Data relationship and therefore uses its actual single-column foreign key. Tenant-owned relationships remain composite.

## Entitlement safety

Plan feature schema version 1 continues to treat the retired `vehicle-rental` value as disabled. A new plan snapshot written with schema version 2 may explicitly enable the fresh module. This prevents stale historical plan JSON from silently activating the new runtime.

## Verification available in this environment

- All changed PHP files pass `php -l` on PHP 8.4.
- Rate-code assertions and source-level dependency checks pass.
- Internal Vehicle Rental class imports have no missing files.
- The full Laravel SQLite, MySQL/MariaDB, TypeScript, ESLint, Vitest, migration, seed, and production-build gates still require execution from a complete checkout.

## Release boundary

This change must remain a draft until the complete project gates and a focused agreement/assignment workflow review pass. It must not be merged as a substitute for the later Running Chart and financial lifecycle batches.
