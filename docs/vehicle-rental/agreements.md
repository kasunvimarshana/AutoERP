# Fresh Vehicle Rental agreement foundation

This release implements agreement capture and history. It is not the complete Vehicle Rental module. Baseline: `worktree-0.0.8` at `61ab2215019364d1b22ffb23daee1f814d3efd0d`; all new Rental code was written fresh without recovering removed code.

## Evidence and limits

Knowledge-base anchors V01/V03 establish customer agreement dates, Daily/Monthly basis, driver and AC contexts, rates and deposit facts. V05/V07 establish independent owner-side terms and payable context. E01 and VR-U25 establish why an explicit rate, unknown value and explicit zero must remain distinct. E13 supports strict structured calendar-date validation. These references identify demonstrated concepts; they do not authorize an inferred pricing formula.

Draft → Active → Closed is the implementation's integrity workflow for recording and freezing terms. It is not claimed as the exact legacy status vocabulary or a newly discovered approval policy. Activation records acceptance of the captured facts and freezes them. It does not reserve a vehicle, confirm supplier source coverage, calculate charges, create financial documents or establish that all commercial policies are known.

## Ownership and relationships

| Record | Relationships and rationale |
|---|---|
| Customer agreement | Required Customer, organization and currency references. No Supplier or direct vehicle-use foreign key: the fresh customer-use aggregate owns physical assignment |
| Owner agreement | Required Supplier, Vehicle, organization and currency references. The Supplier is the commercial payee; this does not replace Vehicle's legal ownership history |
| Customer history | Required customer-agreement and actor references, unique agreement/revision pair, full original attribute snapshot |
| Owner history | Required owner-agreement and actor references, unique agreement/revision pair, full original attribute snapshot |

Four explicit migrations create four tables. Composite foreign keys keep organization, party, vehicle, agreement and actor references within the same tenant. Independent customer and owner tables avoid a polymorphic party key or mutually nullable Customer/Supplier fields. Histories retain their side-specific foreign keys rather than a generic source ID without database referential integrity.

The shared abstract model and services contain only the common agreement workflow. They do not duplicate Customer, Supplier, Vehicle, currency, Tax, Invoice or Payment ownership. There are no reverse Rental collections added to shared owner models, and no Customer-to-Owner agreement dependency. Identical reference text may exist once on each commercial side; each side's reference is unique within its tenant.

Party code/name, currency code and owner-vehicle labels are captured from canonical records, never client labels. These deliberate snapshots preserve recorded meaning when a master-data label changes. They are historical evidence, not new master identities.

## Captured data and validation

Required: agreement reference, party selection, currency selection, agreement date, start date, Daily/Monthly basis, and self-drive/with-driver context. Owner agreements also require a vehicle selection. End date is nullable and must not precede start date. Blank reference and invalid dates such as 31 September are rejected.

`terms` is a flat, strictly allowlisted object. Supported keys are `base_rate`, `included_km`, `excess_km_rate`, `non_ac_rate`, `front_ac_rate`, `dual_ac_rate`, `driver_rate`, `normal_ot_rate`, `double_ot_rate`, `triple_ot_rate`, `night_out_rate`, and `deposit_requirement`. A small fixed object keeps one recorded set of terms together; it is not an arbitrary JSON policy engine or a dynamic formula language.

Values must be non-negative decimal **strings**, with at most 14 whole digits and six fractional places, or null. Normalization pads to six places with BCMath; it does not round financial calculations. Missing keys become null. Explicit zero remains zero. No rate, deposit, allowance or tax percentage is defaulted. Dates and modes have no silent UI defaults. Notes can record context that a future enabled calculation must resolve rather than guess.

A captured amount alone does not establish tariff selection, allowance pooling, proration, taxation, overtime qualification, deposit disposition or driver amount period. The unresolved-policy ledger in `knowledgebase.md` remains binding on future calculation work.

## API

Base: `/api/v1/vehicle-rental/{kind}/agreements`, where `{kind}` is `customer` or `owner`.

| Method/path | Behaviour |
|---|---|
| GET base | Paginated exact-organization register; optional `search` matches reference |
| POST base | Create a draft and its first history entry atomically |
| GET `/{agreement}` | Read agreement with structured party/currency/vehicle display objects |
| PUT `/{agreement}` | Replace draft input; requires `expected_version`; cannot edit activated terms |
| POST `/{agreement}/activate` | Freeze a Draft; requires `expected_version` |
| POST `/{agreement}/close` | Close an Active record; requires `expected_version` and nonblank reason |
| GET `/{agreement}/history` | Paginated immutable revisions with readable actor and original term values |

Write payload fields: `reference`, `party_id`, `currency_id`, optional owner-only `vehicle_id`, `agreed_on`, `starts_on`, nullable `ends_on`, `basis`, `driver_mode`, `terms`, and nullable `notes`. Client identity IDs must come from controlled owner-module selectors. The server derives tenant, organization and actor from trusted request attributes. Submitted lifecycle/version/actor fields cannot replace server state. State changes use explicit action endpoints.

Every mutation is a transaction. Existing records are locked and checked against the expected version before mutation. History insertion participates in the same transaction; failed writes do not create a revision. Stale versions and duplicate references return conflicts. History models reject updates/deletes, and agreement models reject edits to activated terms or deletion. Closed records cannot be reopened through the API.

These controls have SQLite coverage, including the authenticated agreement-to-custody-to-chart-to-closure journey. Active agreements now participate in the fresh physical-use workflow described in [operations.md](operations.md); closure is blocked by outstanding planned or in-custody uses. MySQL contention/lock-order verification remains required before production release.

## Access and UI

Routes require authenticated user, trusted tenant, selected organization, and the `vehicle-rental` tenant feature. Services independently enforce these permissions:

- `vehicle-rental.customer-agreements.view`
- `vehicle-rental.customer-agreements.manage`
- `vehicle-rental.owner-agreements.view`
- `vehicle-rental.owner-agreements.manage`

The current Tenant plan schema is version 4. New/current plans may explicitly enable Rental. Persisted schema versions 1, 2 and 3 discard retired Rental feature entries, so a historical plan entry cannot silently enable the rebuilt module. Historical Invoice/Payment retired-source guards remain unchanged.

Deployment must run the normal migrations, then refresh the permission catalogue using the existing User-owned `Modules\User\Database\Seeders\TenantPermissionSeeder` or `TenantAccessProvisionerInterface` workflow in an authorized tenant execution context. That existing seeder reconciles all non-archived tenants, including other registered permissions; use the normal deployment procedure. Configure the current plan and grant the required permissions. Customer/Supplier/Vehicle selector access still follows those modules' own feature and permission requirements.

Vehicle Rental navigation exposes Customer Agreements and Owner Agreements. Forms use searchable canonical selectors; raw foreign IDs are not operator inputs. Optional rates live in an expandable section. Review shows recorded terms, explicit unknown values and historical revisions. Activation explains that it freezes terms. Closure requires a reason. Failed/stale writes remain visible with inline errors and an explicit reload action.

## Verification and remaining work

Verified locally on 2026-09-08: the full backend suite (678 tests, 7,731 assertions), full frontend suite (77 files, 287 tests), TypeScript, changed frontend lint, PHP formatting and production build. See [integration verification](../changes/2026-09-08-rental-foundation-integration-verification.md) for reproduced defects, corrections and test limitations.

The fresh migrations now use the repository-required `_table.php` suffix and history tables have composite tenant identity keys. If the preceding short-named migrations were already applied, inspect the deployment schema and migration journal before applying this baseline; do not blindly rerun renamed create migrations.

Still outstanding: full source/audio review, protected backup contents, successor/effective rate versions, assignments/custody, source-coverage and overlap rules, Running Charts, independent calculations and consumption, financial handoffs, deposits/adjustments, reports, MySQL concurrency, production upgrade rehearsal and browser/UAT. No production database was accessed, and the new table names must be checked against the actual deployment schema before migration. This is a tested foundation, not a production-complete Rental system.
