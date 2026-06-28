# Item pricing temporal foundation correction — 2026-06-28

## Context

The prior clone-118 architecture correction working tree had not been packaged and was not available in the active runtime. The correction was therefore recovered from the uploaded original clone-118 source rather than pretending that unpersisted changes were present.

This record covers only the Item pricing temporal boundary. It does not declare the complete 153-finding architecture programme closed.

## Root causes

- `items.standard_price` competed with `item_prices` as a second price source of truth.
- Item price rows were mutable and deleteable, so historical documents could resolve differently after master-data edits.
- Currency, UOM, and effective start were optional even though price meaning depends on them.
- Overlapping effective periods could be created without an item-level serialization boundary.
- The API and UI exposed CRUD semantics rather than governed correction history.
- Price relation reads did not apply the trusted organization-unit context for tenant-global items.
- Scope-key generation was duplicated between runtime and seeding code.
- Internal scope/lineage identifiers were exposed in the API resource.

## Decisions

- `item_prices` is the only item-price source of truth.
- A price is an immutable effective-dated revision with recorded-time history.
- Corrections use an explicit supersede command with a required reason and expected version.
- Creation and supersession lock the owning item and current price revisions before overlap validation.
- Currency, UOM, and effective start are mandatory.
- Price scope is derived from trusted request context; client-supplied tenant/OU identifiers are never authoritative.
- Tenant-global item price reads expose only tenant-default and current-OU revisions.
- The UI uses guided variant, currency, and UOM selectors and never displays internal scope hashes or lineage IDs.

## Changes

- Removed `items.standard_price` from schema, DTOs, services, resources, seed data, conversion workflows, and frontend forms.
- Rebuilt `item_prices` with row versions, effective periods, recorded periods, lineage, supersession, and correction reasons.
- Added the explicit `POST /api/v1/items/{item}/prices/{price}/supersede` command.
- Removed mutable price update/delete routes and request contracts.
- Added model-level update/delete guards.
- Added locked overlap validation and conflict-aware recorded-revision closure.
- Added a single `ItemPriceScopeKey` source of truth used by runtime and seeding.
- Added trusted OU filtering and human-readable Tenant/Organization Unit scope presentation.
- Replaced frontend Edit/Delete controls with Add Revision/Supersede workflows.
- Added searchable variant, currency, and UOM selectors.
- Added architecture regression checks and effective-history feature coverage.

## Verification

- Complete PHP token parse: 2,593 files, zero failures.
- Changed/new PHP class autoload check: 27 classes, zero missing.
- TypeScript semantic check: zero diagnostics.
- Full ESLint: zero errors and zero warnings.
- Item pricing static architecture checks: passed.
- Item pricing architecture methods: 5/5 passed through direct source-only execution.
- `git diff --check`: passed.

## Runtime gates

- Laravel route boot is blocked by the supplied PHP CLI missing `mbstring`.
- PHPUnit is blocked by missing DOM, mbstring, XML, and XMLWriter extensions.
- Vitest and Vite are blocked because the supplied npm dependency snapshot contains only Windows Rollup native optional packages and lacks `@rollup/rollup-linux-x64-gnu`.
- No polyfill, source bypass, forced test exit, or dependency compatibility patch was added.

## Remaining architecture programme

The current recovered source still contains the audited production dependency cycles:

- Customer / Inventory / Invoice / Item / Payment / Purchase / Sales / Supplier / Tax / Vehicle / VehicleService
- Configuration / OrganizationUnit / Tenant
- Extension / VehicleRental

Those boundaries remain release gates outside this milestone.
