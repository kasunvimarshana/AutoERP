# Fresh Vehicle Rental agreement foundation — 2026-09-07

## Purpose

Begin the authorized fresh Rental implementation from `worktree-0.0.8` commit `61ab2215019364d1b22ffb23daee1f814d3efd0d`. The existing knowledge base proves independent customer/owner agreement concepts and term fields but leaves several material calculation policies unresolved. This batch implements recording, review and immutable history without inventing those policies or restoring removed Rental code.

An intermediate workspace reset discarded unpublished work. The files in this commit were reconstructed and all reported checks rerun against the recovered files. Earlier transient results are not used as verification of this commit.

## Implemented

- Fresh `VehicleRental` provider, routes, semantic permissions, enums/constants and backend services.
- Separate customer and owner agreements with Draft → Active → Closed integrity workflow. Activation freezes recorded terms; it does not reserve vehicles, establish source coverage or authorize financial processing.
- Strict date, decimal, reference and context validation. Unknown amounts remain null; explicit zero is preserved. No default commercial rates, taxes, account codes or financial formulas.
- Atomic version-checked mutations, unique references, immutable activated terms, append-only original revision snapshots and readable history API.
- Canonical party/currency/vehicle label snapshots to prevent later master-data changes from rewriting recorded identity evidence.
- Customer/owner agreement UI with controlled searchable selectors, draft entry/edit, readable review, activation/closure confirmation, closure reasons, historical revisions and inline conflict/error feedback.
- Tenant plan schema 4 supports an explicit fresh Rental opt-in. Older schema 1–3 snapshots still discard retired Rental feature entries. Historical Invoice/Payment source guards are untouched.
- Frontend navigation/entitlements and backend side-specific authorization remain separate from owner-module lookup permissions.

## Relationship decisions

Customer agreements reference Customer; owner agreements reference Supplier and Vehicle. There is no polymorphic party ID, mutually nullable Customer/Supplier pair, owner-to-customer agreement dependency or duplicated party/vehicle master. Each history table has a concrete agreement FK and actor FK, with unique agreement/revision pairs. Composite tenant FKs prevent cross-tenant relationships.

The owner Vehicle reference records commercial agreement context; Vehicle continues owning physical identity and legal ownership history. Customer vehicle assignment is intentionally not misrepresented as a permanent FK on the customer agreement. The future use/custody aggregate will own it.

Snapshots duplicate labels and original term values deliberately for historical evidence; they are not competing mutable master records. No unrelated existing schema relationship was modified. Shared agreement behaviour is reused in small model/service abstractions, while history tables retain concrete referential integrity. Navigation imports pure agreement vocabulary; it does not import network services.

## Documentation and rollout

`docs/vehicle-rental/agreements.md` documents API input, state meaning, relationships, validation, access, deployment prerequisites and remaining work. `docs/knowledgebase.md` and the TODO now describe the limited implemented foundation accurately.

Existing deployments must inspect their actual schema for collisions before running new migrations, refresh permissions through the existing User-owned provisioning/seeder workflow, and explicitly configure the current Tenant plan and role permissions. No production database was accessed or changed. No migration, provider or source class from removed Rental code was restored.

## Verification

- PHP 8.3 and SQLite, locked Composer dependencies: **70 tests, 483 assertions** across VehicleRental, Vehicle, VehicleService and TenantPlanSchema.
- Coverage includes independent commercial sides, null versus zero, strict invalid-date/amount rejection, cross-tenant party rejection, organization scoping, stale edits, duplicate references, immutable activated terms/history, label preservation, real side-permission mapping, trusted controller context, version-required actions, readable history and unauthenticated access rejection.
- Controller payload tests inject trusted request attributes and bypass middleware to isolate controller validation. A separate request test checks unauthenticated route rejection. This is not full authenticated browser/UAT acceptance.
- Vitest: **43 tests** across agreement review, route access and navigation, including view-only actions, stale-write feedback and required closure reason.
- TypeScript, changed frontend ESLint, PHP Pint and production Vite build passed.
- `git diff --check` passed.

Only free local tools were used. No GitHub Actions were used; the commit message includes `[skip ci]`.

## Not complete

This is an agreement foundation, not a production-complete Vehicle Rental module. Effective successor versions, supply/use/custody, Running Charts, reciprocal availability admission, independent calculation/consumption, Invoice/Payment/Tax/Finance handoffs, deposits, reports, MySQL concurrency, production upgrades and browser/UAT remain outstanding. Full continuous audiovisual review and password-protected backup content review also remain open. No unresolved business rule is marked confirmed by this implementation.
