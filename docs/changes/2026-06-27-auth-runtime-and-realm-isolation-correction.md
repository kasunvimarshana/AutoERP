# Auth runtime and realm-isolation correction

Date: 2026-06-27

## Context

Tenant and platform sign-in both returned `UNEXPECTED_ERROR`. The supplied Laravel log proved that the application failed while resolving `PlatformPermissionChecker`: its concrete method was named `allows`, while the interface required `hasPermission`. Because tenant and platform authentication shared one multi-realm directory graph, the platform-only contract defect also stopped tenant login before credential validation.

## Decisions

- Use `allows(int $operatorId, string $permission): bool` as the single platform permission-check contract.
- Keep one User-owned platform access resolver as the authoritative active-operator and permission source.
- Separate tenant-user, platform-operator, and principal lookup directories.
- Separate tenant and platform profile builders and inject realm-specific token services into realm-specific use cases.
- Keep only a small access-token prefix router where cross-realm dispatch is required.
- Treat a successful login as one atomic outcome: session, tokens, profile readiness, and successful login audit must all complete in the same transaction.
- Resolve tenant context before authentication without requiring user access; enforce authenticated tenant access only on protected routes.
- Bind production tenant selection to a verified tenant host. Header selection remains available only for local/testing workflows.
- Use the login response as the frontend source of truth. `/me` remains a later bootstrap/refresh operation, not a second login completion step.
- Preserve typed Auth failures through one response factory and the shared API error contract.

## Backend changes

- Replaced the invalid `PlatformPermissionChecker` with `PlatformOperatorAccessResolver` bindings for narrow checker and directory contracts.
- Split the shared authentication directory into tenant, platform, and principal-provider implementations.
- Split the shared profile service and broad Auth controller responsibilities by realm/use case.
- Added `ResolveCurrentTenantMiddleware` and `RequireCurrentTenantAccessMiddleware`; retained `CurrentTenantMiddleware` as their protected-route composition.
- Locked user and organization-unit membership while selecting the login organization unit.
- Added explicit tenant criteria to provider and identity lookup.
- Added `AuthResponseFactory`, typed configuration failures, and non-cacheable Auth responses.
- Added `auth:readiness`, a protected readiness endpoint, and `auth:incident {correlationId}` diagnostics.
- Added container/interface conformance, error-contract, and database-backed login flow tests.
- Removed the misleading platform administrator password environment setting; platform operators remain invitation-first.

## Frontend changes

- Removed the second `/me` request after a successful login.
- Scoped `X-Tenant-Id` to authenticated tenant endpoints only.
- Aligned terminal refresh codes with the backend contract.
- Cleared credentials and MFA state when switching authentication realms.
- Guided tenant users away from the central platform host to their verified workspace.
- Added support-reference copy UX.

## Verification

- Final changed/new PHP lint: 68 files, 0 failures.
- TypeScript: 0 diagnostics.
- ESLint: 640 TypeScript/TSX files, 0 errors and 0 warnings.
- Frontend tests: 47 files, 161 tests passed. One pre-existing non-failing React `act(...)` warning remains in a Vehicle test outside this change boundary.
- Production build: passed; 654 modules transformed; main bundle 464.00 kB / 142.87 kB gzip.
- Project imports: 8,079 scanned, 0 missing.
- Direct interface implementations: 107 scanned, 0 mismatches.
- Route actions: 655 scanned, 0 controller/method mismatches.
- Frontend relative imports: 4,797 scanned, 0 missing.
- Migrations: 251 files, unchanged.

## Runtime boundary

The uploaded snapshot does not contain Composer `vendor/`, a migrated database, or the service environment required to boot Laravel. Therefore Artisan route boot, PHPUnit, MySQL transaction/concurrency behavior, queue/cache integration, and browser/API end-to-end login were not executed here. The new `auth:readiness` command and database-backed feature tests are the required runtime gates in a fresh reviewed environment.
