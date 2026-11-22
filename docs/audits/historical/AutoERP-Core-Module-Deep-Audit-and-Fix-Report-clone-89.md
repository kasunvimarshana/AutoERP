# AutoERP Core Module — End-to-End Deep Audit and Root-Cause Fix Report

## Baseline

- Repository: `AutoERP-2026051623-refactor-core-modules-clone-89-ui`
- Primary scope: `app/Modules/Core`
- Related owner modules changed only where required by the corrected Core contracts.
- Compatibility policy: breaking changes are accepted when needed to remove a flawed foundation.

## Audit scope

The audit covered:

- contracts and dependency direction;
- decimal arithmetic;
- request identity, tenant and organization contexts;
- mass-assignment defaults;
- API error responses;
- file storage;
- idempotency;
- repositories and transactions;
- shared DTOs/results/entities;
- configuration ownership;
- seed helpers;
- sequence-number safety;
- dead abstractions;
- migrations and deletion rules;
- dependent imports and call sites;
- unit/feature test coverage.

---

# Executive result

The old Core module mixed technical primitives with implicit context fallbacks and permissive defaults. The redesign makes Core a small platform foundation with explicit contracts and fail-closed behavior.

The most important corrected rules are:

```text
Exact decimal values
→ DECIMAL in the database
→ decimal strings in APIs and PHP
→ BCMath for arithmetic
→ explicit module-owned rounding
```

```text
Authenticated user
≠ tenant context
≠ organization-unit context
```

```text
Core
→ technical contracts and primitives only

Feature modules
→ own their business rules
```

---

# Root findings and fixes

## 1. Unsafe decimal arithmetic — fixed

### Problem

The old `DecimalMath` implementation contained an integer fallback when BCMath was unavailable. Large `DECIMAL(20,6)` operations could overflow a 64-bit integer. Binary floating-point was not acceptable for finance, tax, inventory, purchase, sales, payment or rental values.

### Fix

- Added `ext-bcmath` as a required Composer platform dependency.
- Removed unsafe integer arithmetic fallback.
- Accept only `int|string`; floats are rejected by the type contract.
- Reject scientific notation.
- Reject unsupported precision instead of silently truncating non-zero digits in `normalize()`.
- Allow up to 18 fractional digits for intermediate exact calculations.
- Return fixed-scale decimal strings.
- Keep rounding rules outside Core because rounding is a business rule owned by Tax, Invoice, Payment, Inventory or the relevant feature module.

### Breaking behavior

Values such as `"1.2345678"` cannot be normalized directly to scale 6. The owning module must apply an explicit rounding rule first.

---

## 2. Permissive Core model mass assignment — fixed

### Problem

`CoreModel` guarded only `id`. Scope, status, approval, totals and source fields could become mass assignable by default.

### Fix

- `CoreModel` now uses deny-by-default mass assignment.
- Concrete models must declare writable fields explicitly.
- Core boot enables `Model::preventSilentlyDiscardingAttributes()`.
- Core-owned `IdempotencyRecord` remains fully guarded and is written through controlled service code.
- Auth models directly affected by the new base rule received explicit writable-field lists.

### Remaining module-owner work

Many feature models override the Core default with `guarded = ['id']`. Those must be corrected within their owning modules during the module-wise refactor. Core no longer makes permissive behavior the default.

---

## 3. Identity, tenant and organization context were conflated — fixed

### Problem

The authenticated user context also carried tenant and organization identifiers. Resolvers could fall back to `users.tenant_id` and `users.organization_unit_id`, creating competing sources of truth with membership tables.

### Fix

- `CurrentUserContext` now contains authenticated identity only:
  - user;
  - user ID;
  - guard;
  - provider;
  - application ID;
  - token payload.
- Tenant resolution no longer falls back to a field on the user record.
- Tenant resolution order is explicit metadata, host/domain, then local/testing fallback.
- Tenant access always requires a `user_tenants` membership.
- Organization resolution uses the resolved tenant and an explicit organization signal or the user’s default tenant membership.
- Multiple default organization memberships raise an integrity error.
- Auth profile generation no longer derives tenant or organization from legacy user columns.
- User authorization uses the resolved tenant context only.
- Organization context is optional by default; tenant-level operations are not forced into a branch context.
- Tenant-scoped form requests fail authorization when the current authenticated user context is absent.

### Security result

Missing or ambiguous scope no longer silently becomes the user record’s historical tenant or organization value.

---

## 4. Request contexts were serialized arrays — fixed

### Problem

Middleware stored loosely structured arrays in request attributes. Consumers depended on magic keys and repeated conversions.

### Fix

- Added immutable `CurrentUserContext`, `CurrentTenantContext` and `CurrentOrganizationUnitContext` objects.
- Request accessors return typed contexts.
- Middleware stores the typed object and explicit scalar attributes.
- DTO constructors validate identifiers and record consistency.
- Missing required contexts produce normalized errors.

---

## 5. API error contracts were duplicated — fixed

### Problem

Middleware, controllers and exception handlers built similar but inconsistent JSON error payloads.

### Fix

- Added one Core-owned `ApiErrorResponseFactory`.
- Added `EnsureApiErrorResponseMiddleware` for non-normalized JSON error responses.
- Centralized Laravel exception rendering in `bootstrap/app.php`.
- Authentication, authorization, validation, not-found, domain and infrastructure errors now share one payload structure.
- Auth controller failures now use the same factory.
- Validation field errors are retained.
- Internal 5xx details are not exposed in production responses.

---

## 6. File-storage service was broad and unsafe — fixed

### Problem

The previous service exposed unused operations and supported full-content reads. Mutable disk behavior and insufficient path validation increased complexity and risk.

### Fix

The contract now contains only proven operations:

- store uploaded/source file;
- delete;
- existence check;
- MIME type;
- streamed read.

Additional fixes:

- immutable default disk;
- safe relative paths only;
- path traversal rejection;
- safe basename validation;
- stream-based writes and reads;
- explicit read failure;
- Vehicle document download updated to stream without loading the whole file into memory.

---

## 7. Idempotency lifecycle was under-specified — fixed

### Problem

Idempotency operations could be used without a transaction and status handling was stringly typed. Payload reuse and lifecycle transitions were insufficiently guarded.

### Fix

- Added `IdempotencyStatus` enum.
- Acquisition and completion require an active database transaction.
- Scope, operation, SHA-256 hashes, reference length and actor IDs are validated.
- Record acquisition uses unique scope hash, row locking and `insertOrIgnore()`.
- Reusing a key with a different payload is rejected.
- Only an in-progress record can be completed.
- Completion is row-locked.
- Tenant and organization deletion are restricted instead of cascading or nulling the idempotency scope.

---

## 8. Repository abstraction owned transactions — fixed

### Problem

The generic repository interface exposed transaction orchestration even though transactions belong to application use cases.

### Fix

- Removed transaction methods from repository contracts.
- Use `TransactionManagerInterface` in application services.
- `EloquentRepository::restore()` now explicitly rejects models without `SoftDeletes`.
- Criteria and identifier validation were hardened.
- Pagination uses integer-safe calculations.

---

## 9. Unsafe sequence rollback — removed

### Problem

The sequence rollback endpoint decremented `next_number` without proving that the number was never persisted. This could reissue a business document number.

### Fix

Removed:

- rollback request;
- rollback service;
- rollback route/controller action.

Consumed document numbers are no longer reusable through a generic rollback operation.

---

## 10. Core seeding depended on feature models — fixed

### Problem

A Core seeder concern imported Tenant, OrganizationUnit and User feature models. This reversed dependency ownership.

### Fix

- Moved the reusable seed-context concern to `database/seeders/Concerns`.
- Updated feature seeders to import the application-level concern.
- Removed the empty Core seeder.
- Removed Core-to-feature seeder imports.

Core now imports no other AutoERP feature module.

---

## 11. No-op and unused abstractions — removed

Removed because they had no owned behavior or active use:

- `ModuleResource`;
- `TenantId`, `OrganizationUnitId` and `Uuid` wrappers;
- `InvalidValueObjectException`;
- unused model scope traits;
- empty Core API route file;
- empty Core seeder;
- speculative file-storage methods.

Feature resources now extend Laravel `JsonResource` directly. This removes a dependency without losing behavior.

---

## 12. Shared primitives were ambiguous — fixed

### `DataRecord`

- Distinguishes missing keys from explicit null values.
- Adds required-field access with explicit failure.
- Validates identifier values.

### `Result`

- Enforces valid success/failure states.
- Replaces nullable ambiguous access with `valueOrFail()` and `errorOrFail()`.

### `Error`

- Requires non-empty code and message.
- Validates context keys.

### `PagedResult`

- Validates totals/pages/page size.
- Requires list-shaped items.
- Uses integer page calculations.
- Produces consistent pagination metadata.

### `Entity`

- Requires a non-empty identity.
- Identity equality requires the same concrete entity class and ID.

---

# Module ownership after the change

## Core owns

- exact decimal primitive;
- clock, UUID, password hash and slug contracts;
- transaction abstraction;
- generic repository base;
- result/error/data-record primitives;
- request context contracts and middleware;
- API error contract;
- file-storage contract;
- idempotency primitive.

## Auth owns

- authenticated identity resolution;
- guard/provider/token context;
- authentication workflows.

## Tenant owns

- tenant resolution;
- domain lookup;
- membership access validation.

## OrganizationUnit owns

- organization resolution;
- organization membership validation.

## User owns

- user records;
- memberships;
- role and permission resolution.

No rental, purchase, sales, invoice, tax or inventory business rule was added to Core.

---

# Intentional breaking changes

- BCMath is mandatory.
- Decimal inputs are exact strings or integers, not floats.
- Decimal normalization rejects silent precision loss.
- `CurrentUserContext` no longer exposes tenant or organization IDs.
- Tenant and organization are not inferred from legacy user columns.
- Request contexts are objects, not arrays.
- Core models deny mass assignment by default.
- Generic repository transaction methods are removed.
- Sequence rollback API is removed.
- `ModuleResource` is removed.
- Unused file-storage operations are removed.
- Idempotency acquire/complete must execute inside a transaction.
- Organization-unit context is optional unless the owning use case requires it.

These are foundation corrections, not compatibility shims.

---

# Verification completed

- All changed and added PHP files passed `php -l`.
- Static AutoERP class-import resolution found no missing imported class.
- Core has zero imports from another AutoERP feature module.
- Removed symbols have no runtime references.
- Core contains no float/double arithmetic fallback.
- Core contains no TODO/FIXME/debug termination calls.
- Composer JSON and lock JSON parse successfully.
- `ext-bcmath` exists in Composer requirements and the lock platform.
- Migration scan found unique migration timestamps.
- Migration scan found no duplicate table creation.
- Every created table has a matching `dropIfExists()`.
- Added tests cover decimal math, contexts, file storage, API errors, records, result, paging and entity identity.

## Runtime verification limitation

The uploaded archive does not include:

- `vendor/`;
- `node_modules/`;
- a Composer executable.

The sandbox PHP runtime also does not have BCMath. Therefore Artisan, PHPUnit and frontend build commands could not be executed here. This is explicitly not claimed as a runtime test pass.

Run in the normal development environment:

```bash
composer install
php artisan optimize:clear
php artisan migrate:fresh --seed
php artisan migrate:rollback
php artisan migrate
php artisan test

npm ci
npm run typecheck
npm run lint
npm run test
npm run build
```

---

# Remaining work owned by later module phases

These issues were identified but intentionally not patched inside Core:

1. User/Tenant/Organization membership schema still has competing legacy columns and `role_id` placement. Redesign this in the foundation-cluster phase.
2. Feature models that override Core’s guarded default need explicit writable fields within their own modules.
3. Granular backend permissions remain inconsistent in several feature modules.
4. Parent-child tenant composite integrity must be added aggregate by aggregate in owning modules.
5. Feature-specific rounding policies must be defined by Finance, Tax, Invoice, Payment, Inventory and other owners.
6. Module-specific business services and large controllers require their own focused refactors.

No workaround for these issues was added to Core.

---

# Final conclusion

`app/Modules/Core` is now a smaller and stricter technical foundation. It no longer provides unsafe decimal fallback, permissive model defaults, implicit user-scope inference, reusable sequence rollback, feature-aware seed logic or no-op abstractions.

The next recommended step is the coordinated `Tenant + OrganizationUnit + User/Auth + Authorization` schema and service refactor, using these corrected Core contracts.
