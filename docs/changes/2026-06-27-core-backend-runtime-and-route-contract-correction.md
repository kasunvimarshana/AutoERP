# Core backend runtime and route-contract correction

Date: 2026-06-27

## Context

The frontend/runtime verification package had passed source syntax and frontend checks, but the native PHP environment could not execute the complete Laravel/database suite. A framework boot attempt using the supplied Composer dependencies was therefore used to continue validating dependency resolution and the registered route graph without weakening application requirements.

## Root causes

- `TenantSubscriptionLifecycleService` referenced `TenantActorSnapshotFactory` without importing the Tenant-owned class. PHP resolved the constructor type relative to the `Subscriptions` namespace, so Laravel attempted to construct a nonexistent class.
- `EloquentUserOrganizationUnitRepository` relied on the global `DB` facade alias rather than an explicit framework import.
- Extension and Sequence route files exposed unversioned application API prefixes.
- Customer, Supplier, Vehicle, and HR route files registered unnamed or ambiguously named routes. Runtime route inspection exposed duplicate names, making URL generation and route caching nondeterministic.
- The Platform Operator invitation form used render-snapshot object spreads for text-field updates. Rapid or batched input events could overwrite sibling field state, allowing native form validation to prevent submission intermittently.

## Decisions

- Correct the missing dependency at the owner boundary; do not add an alias or compatibility class in the wrong namespace.
- Import the framework database facade explicitly.
- Make `/api/v1` the only application API namespace; do not retain unversioned aliases.
- Give every custom route a stable, responsibility-specific name while preserving the existing endpoint methods and URIs, except for the intentional Extension and Sequence API version correction.
- Use functional state updates for independently edited form fields so concurrent React updates merge against the latest state.
- Add architecture regression tests for the Tenant constructor dependency and module API versioning.

## Changes

- Imported `Modules\Tenant\Services\TenantActorSnapshotFactory` in the subscription lifecycle service.
- Imported `Illuminate\Support\Facades\DB` in the User organization-access repository.
- Moved Extension and Sequence APIs under `/api/v1` and aligned their route-name prefixes.
- Added explicit nested route names for Customer, Supplier, Vehicle, and HR lifecycle/relation endpoints.
- Added `ModuleApiVersioningTest`.
- Extended `TenantArchitectureTest` to verify the constructor dependency resolves to the Tenant-owned service.
- Updated the Sequence module API-prefix documentation.
- Changed Platform Operator invitation text fields to functional state updates, removing a reproducible aggregate-suite submission race.

## Verification

- Laravel diagnostic route boot loaded 915 routes using the real application service-provider graph. The diagnostic copy neutralized only the unavailable BCMath environment guard; the packaged source retains the guard unchanged.
- Unversioned application API routes: zero.
- Duplicate method/URI registrations: zero.
- Duplicate route names: zero.
- Missing route controller classes or actions: zero.
- Project-owned unresolved PHP class references: zero.
- Platform Operator invitation tests passed in five consecutive isolated runs after the state-ownership correction.
- Frontend TypeScript and ESLint passed with zero findings.
- Aggregate Vitest passed: 47 files and 161 tests.
- Production build passed: 654 modules; main entry 464.00 kB, 142.84 kB gzip.

## Environment boundary

The available PHP CLI still lacks native `mbstring`, `bcmath`, DOM/XML/XMLWriter, and PDO database drivers. Binary packages could not be provisioned because the execution environment blocks the required package downloads. Consequently, fresh database migrations, database-backed PHPUnit/concurrency tests, and Tenant/OU adversarial runtime tests remain deployment-environment gates. No source polyfill, arithmetic bypass, or database-integrity workaround was introduced.
