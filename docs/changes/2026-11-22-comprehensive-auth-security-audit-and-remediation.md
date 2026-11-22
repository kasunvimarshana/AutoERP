# Comprehensive Authentication & Authorization Security Audit & Defense-in-Depth Remediation

**Date:** 2026-11-22  
**Directives:** `/compact` `/learn` `/plan` `/boost` `/goal`  
**Scope:** Whole-repository Security Audit of Authentication, Authorization, Permissions, Roles, Session/Token Management, Routes, Middleware, Policies, and Tenant Isolation across all 31 Modules.

---

### 1. Executive Summary & Audit Findings

Pursuant to the `/boost`, `/plan`, and `/goal` directives, an exhaustive end-to-end security audit of Authentication and Authorization was conducted across all 31 modules of the AutoERP platform.

#### Architectural Strengths Confirmed:
1. **Tenant Isolation Invariant (Axioms 1, 4, 6):**
   - Tenant isolation is structurally enforced by `TenantOwnedModel` via the `TenantScope` global Eloquent scope, which strictly binds queries to `current_tenant_id` or fails closed with `1 = 0` whenever tenant context is absent. Cross-tenant IDOR access is prevented at the database query abstraction layer.
2. **Dual-Plane Authentication & Token Security (Axioms 5, 11):**
   - Distinct authentication guards are strictly enforced: `platform-api` for platform operators and `auth-api` for tenant users.
   - Opaque bearer tokens are hashed using SHA-256 before persistence (`OpaqueTokenCodec`), preventing token exposure in database snapshots. Token comparisons utilize constant-time verification (`hash_equals`).
   - Session lifecycle, revocation (`revokeToken`, `revokeSession`), step-up authentication, and rate limiting (`RateLimiter::attempt`) operate correctly.
3. **Canonical Permission Registry:**
   - 28 modules already register their permissions into the central `PermissionDefinitionRegistryInterface`, and `UserAccessResolver` evaluates cached role-permission sets per tenant context.

#### Security Gaps & Broken Authorization Boundaries Identified:
1. **Single-Permission Route Middleware Limitation (`RequireTenantPermissionMiddleware`):**
   - Route middleware `tenant.permission` only accepted a single permission string. Any controller endpoint requiring disjunctive access (e.g. allowing `VIEW` OR `MANAGE`) had to omit route middleware and rely solely on controller assertions.
2. **`ReferenceData` Module Route Middleware Missing:**
   - Global reference lookups are intentionally public/platform-accessible, but tenant administration routes (`countries`, `currencies`, `languages`, `timezones`) lacked `tenant.permission` middleware.
3. **`Sequence` Module Missing Permissions:**
   - The Sequence module had zero permissions defined, zero registration in `PermissionDefinitionRegistryInterface`, and no permission middleware on `api/v1/sequences/*`.
4. **`Configuration` Module Route Defaults & Form Request Authorization:**
   - Tenant and organization configuration route groups defaulted to empty middleware arrays `[]`.
   - FormRequests (`CreateConfigurationEntryRequest`, `UpdateConfigurationEntryRequest`, `DeleteConfigurationEntryRequest`, `RollbackConfigurationEntryRequest`) only verified `canViewScopeCurrent` rather than `canManageScopeCurrent`.
5. **`Item` Module Route-Level Defense-in-Depth Gap:**
   - All Item endpoints asserted permissions inside controller methods, but the route definitions in `Item/Routes/api.php` lacked `tenant.permission` route middleware.
6. **`OrganizationUnit` Module Route-Level Defense-in-Depth Gap:**
   - OrganizationUnit controllers asserted granular permissions, but route definitions in `OrganizationUnit/Routes/api.php` lacked `tenant.permission` route middleware.
7. **Disjunctive Lookups in `Purchase`, `Promotion`, and `VehicleService`:**
   - Lookups supporting multiple workflows (such as fast purchase lookups, promotion job-item searches, and service line item lookups) lacked route-level enforcement due to the single-permission middleware limitation.

---

### 2. Remediation Details

#### Step 1: Upgraded `RequireTenantPermissionMiddleware` to Variadic Multi-Permission
- **File:** `app/Modules/User/Http/Middleware/RequireTenantPermissionMiddleware.php`
- **Change:** Updated `handle($request, Closure $next, string ...$permissions)` to accept variadic permissions. Uses `allowsAny($userId, $tenantId, $permissions)` when multiple permissions are specified, and returns consistent, backwards-compatible 403 Forbidden payload details (`permission` and `permissions`).
- **Tests:** `tests/Unit/User/RequireTenantPermissionMiddlewareTest.php` (4 passed, 18 assertions).

#### Step 2: `ReferenceData` Route Authorization Wired
- **File:** `app/Modules/ReferenceData/Routes/api.php`
- **Change:** Wired `$requires(ReferenceDataPermission::VIEW)` to index/show routes, and `$requires(ReferenceDataPermission::MANAGE)` to store, update, and status routes across all 4 catalogs (`countries`, `currencies`, `languages`, `timezones`).
- **Tests:** `tests/Feature/ReferenceData/ReferenceDataPermissionBoundaryTest.php` (2 passed, 46 assertions).

#### Step 3: `Sequence` Module Permissions & Route Middleware
- **Files Created/Modified:**
  - `app/Modules/Sequence/Constants/SequencePermission.php` (defines `VIEW = 'sequences.view'`, `MANAGE = 'sequences.manage'`).
  - `app/Modules/Sequence/Providers/SequenceServiceProvider.php` (registers permissions in registry).
  - `app/Modules/Sequence/Routes/api.php` (attaches `$requires(...)` to sequence index, store, show, update, reset, preview routes).
  - `resources/js/modules/sequence/sequencePermissions.ts` (mirrored in frontend).
- **Tests:** `tests/Feature/Sequence/SequencePermissionBoundaryTest.php` (2 passed, 20 assertions).

#### Step 4: `Configuration` Route & Request Hardening
- **Files Created/Modified:**
  - `app/Modules/Configuration/Routes/api.php` (wired `ENTRIES_VIEW`, `ENTRIES_MANAGE_TENANT`, `ENTRIES_MANAGE_ORGANIZATION` to tenant and organization scopes).
  - `app/Modules/Configuration/Http/Requests/ManageConfigurationEntryRequest.php` (enforces `canManageScopeCurrent($scope)`).
  - Extended in `CreateConfigurationEntryRequest`, `UpdateConfigurationEntryRequest`, `DeleteConfigurationEntryRequest`, `RollbackConfigurationEntryRequest`.
- **Tests:** `tests/Feature/Configuration/ConfigurationPermissionBoundaryTest.php` (1 passed, 32 assertions).

#### Step 5: `Item` Module Route-Level Middleware
- **Files Created/Modified:**
  - `app/Modules/Item/Routes/api.php` (wired route-level `$requires(...)` for items, units, variants, bundles, prices, codes, usage-rules, categories, and brands).
  - `tests/Feature/Item/ItemPermissionBoundaryTest.php` (verifies all 49 routes have exact canonical permission middleware).
- **Tests:** `ItemPermissionBoundaryTest` (1 passed, 98 assertions), `ItemApiTest` (13 passed, 179 assertions).

#### Step 6: `OrganizationUnit` Module Route-Level Middleware
- **Files Created/Modified:**
  - `app/Modules/OrganizationUnit/Routes/api.php` (wired route-level `$requires(...)` for units, legal profile, activation, deactivation, retirement, logo, documents, and unit types).
  - `tests/Feature/OrganizationUnit/OrganizationUnitPermissionBoundaryTest.php` (verifies all 22 routes have exact canonical permission middleware).
- **Tests:** `OrganizationUnitPermissionBoundaryTest` (1 passed, 44 assertions), `app/Modules/OrganizationUnit/Tests/` (9 passed, 61 assertions).

#### Step 7: Multi-Permission Lookups in `Purchase`, `Promotion`, and `VehicleService`
- **Files Created/Modified:**
  - `app/Modules/Purchase/Routes/api.php` (wired `ORDERS_VIEW,FAST_PURCHASE_LOOKUPS,FAST_PURCHASE_VIEW,FAST_PURCHASE_EXECUTE` to 5 lookup routes).
  - `app/Modules/Promotion/Routes/api.php` (wired `VIEW,MANAGE,REDEEM` to `job-items/lookup` and `available`, and `MANAGE,REDEEM` to `offers/{offer}/assignments`).
  - `app/Modules/VehicleService/Routes/api.php` (wired `JOB_TYPES_VIEW,JOBS_VIEW,JOBS_CREATE` to `job-types/lookup` and `JOBS_CREATE,LINES_MANAGE` to `job-line-items/lookup`).
  - `tests/Feature/Auth/MultiPermissionRouteBoundaryTest.php` (verifies all variadic multi-permission routes).
- **Tests:** `MultiPermissionRouteBoundaryTest` (1 passed, 20 assertions).

---

### 3. Verification & Compliance Matrix

| Component / Test Suite | Target Scope | Result | Assertions |
| :--- | :--- | :--- | :--- |
| `RequireTenantPermissionMiddlewareTest` | Variadic middleware logic & fallbacks | **PASSED** | 18 |
| `ReferenceDataPermissionBoundaryTest` | Catalog route permission boundary | **PASSED** | 46 |
| `SequencePermissionBoundaryTest` | Sequence catalogue & route middleware | **PASSED** | 20 |
| `ConfigurationPermissionBoundaryTest` | Tenant & org config routes | **PASSED** | 32 |
| `ItemPermissionBoundaryTest` | All item & relation routes | **PASSED** | 98 |
| `OrganizationUnitPermissionBoundaryTest` | All organization unit & type routes | **PASSED** | 44 |
| `MultiPermissionRouteBoundaryTest` | Purchase, Promotion, VehicleService lookups | **PASSED** | 20 |
| `PermissionBoundaryTest` (All modules) | System-wide permission boundaries | **PASSED** | 1,338 |
| `platform:permissions:sync` | Platform permission sync command | **PASSED** | 0 errors |
| `ItemApiTest` | Full item functional suite | **PASSED** | 179 |
| `OrganizationUnit` suite | Hierarchy & profile tests | **PASSED** | 61 |

All 12 Core Engineering Axioms were strictly preserved:
- **Backend Authority (Axiom 5):** Validations and permission boundaries are enforced at the HTTP route boundary and backend services.
- **Zero Magic Values (Axiom 9):** Canonical permission class constants used throughout.
- **Root Cause over Compatibility Workarounds (Axiom 2):** Upgraded core middleware to support variadic arguments cleanly rather than inventing multiple route aliases.
- **Shared Project Memory (Axiom 12):** Change recorded in append-only log.
