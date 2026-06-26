# User foundation correction

Date: 2026-06-26
Scope: `app/Modules/User` and the minimum required User-owned integration contracts in Auth, Core, Tenant, OrganizationUnit, Audit, and the User Administration frontend.

## Why

The previous User implementation mixed profile editing, account lifecycle, credential provisioning, role and permission governance, organization access, documents, devices, platform operators, and authentication revocation behind broad CRUD requests and one oversized service. That design allowed privilege escalation, cross-user document/device access, fail-open policy behavior, partial commits, last-administrator races, client-authored storage metadata, plaintext device-token exposure, stale soft-delete pivots, and concrete dependency cycles with Auth, Tenant, OrganizationUnit, and Audit.

## What changed

- Replaced the combined user mutation request with explicit, versioned commands for profile, status, roles, direct permissions, organization access, invitation resend, and archival.
- Added module-owned permissions and fail-closed backend authorization for every tenant-user, role, permission-catalogue, document, and device action.
- Made tenant-user onboarding invitation-first; administrators no longer choose or know user passwords.
- Moved password credentials and session/token revocation to Auth-owned credential and revocation services.
- Split tenant users and platform operators into separate aggregates, tables, credentials, sessions, tokens, invitation flows, and permission models.
- Replaced the mutable `Super Admin` display-name invariant with an immutable role `system_key`.
- Serialized last-active-Super-Admin checks and role/access mutations with deterministic tenant/user/role locking.
- Made `expected_version` mandatory for update, lifecycle, role, direct-permission, organization-access, document, device, and platform-operator mutations.
- Normalized external identity ownership into Auth identities instead of User metadata JSON.
- Removed arbitrary metadata/preferences and unrelated HR demographic fields from the User account aggregate.
- Replaced raw document paths and client-authored MIME/size/checksum fields with private tenant object keys, server-derived metadata, scanning, checksums, safe downloads, and durable cleanup.
- Replaced readable device tokens with a hash for lookup and encrypted token storage; registration/activity are owner-only, while authorized managers can review and revoke.
- Made user documents and devices tenant-global parts of the user aggregate, protected by same-tenant subject policies rather than unrelated OU IDs.
- Removed generic assignment CRUD controllers, requests, repositories, interfaces, and services that duplicated or bypassed authoritative application services.
- Split the former oversized User service into cohesive account, read, role, permission, organization-access, document, device, platform-operator, provisioning, and audit services.
- Reworked list/read projections to eager-load access relations and batch organization-unit labels, avoiding per-row access and login-history queries.
- Added immutable audit events for account, profile, lifecycle, role, direct-permission, organization-access, document, device, invitation, and platform-operator mutations.
- Replaced concrete User dependencies on Auth, Tenant, and OrganizationUnit implementations with narrow Core contracts implemented by the owning modules.
- Added guided frontend sections for profile, roles, direct permissions, organization access, lifecycle, documents, and devices; removed raw IDs, raw paths, password fields, and blind combined edits.
- Added source regression tests for invitation-first creation, exact permissions, cross-tenant assignments, last-administrator protection, session-versus-credential revocation, archival, protected roles, and cross-user device registration.

## Deliberate design decisions

- Archived user email and username values remain globally reserved within the tenant. Reusing a historical login identity could transfer ownership or make retained audit/history ambiguous. A separately reviewed identity-reclamation workflow is required if legal retention rules ever permit reuse.
- User documents and devices do not carry organization-unit ownership. They belong to the tenant user aggregate and are governed by same-tenant self-or-explicit-manager policies.
- Status values remain portable constrained strings rather than database-native enums or driver-specific CHECK constraints. Legal transitions are enforced by typed constants, model casts/defaults, service invariants, composite keys, and mandatory runtime migration/concurrency tests.

## Ownership boundaries

- User: tenant account profile/lifecycle, tenant roles and direct permissions, user-to-OU access assignments, user documents/devices, platform-operator identity and invitation aggregates.
- Auth: password credentials, authentication identities, sessions, access/refresh tokens, login/MFA, invitation acceptance credential setup, and access revocation.
- OrganizationUnit: authoritative OU existence, hierarchy, lifecycle, and directory reads.
- Tenant: tenant aggregate locks, plan entitlements/limits, and private tenant storage implementation.
- Audit: append-only audit persistence and platform audit authorization.
- Core: narrow cross-module contracts and the platform permission catalogue contract.

## Verification performed

- Full project PHP syntax lint excluding dependencies: 2,618 files, zero failures.
- Final changed/new PHP syntax lint: 200 files, zero failures.
- TypeScript/TSX syntax parse: 625 files, zero failures.
- Relative frontend import scan: zero missing imports.
- Internal PHP symbol/import scan: zero missing symbols.
- Route/controller action scan: zero mismatches.
- User/Auth affected migration scan: 32 migrations/tables, one table per migration, no duplicate creation, patch migration, native enum, runtime-domain import, FK-order, or explicit-name-length findings.
- User production dependency graph: dependencies limited to Audit and Core; no dependency cycle containing User.
- Changed-code placeholder and merge-conflict scans: zero findings.
- Source regression inventory: 12 User access feature-test methods added/updated.

## Runtime and deployment gates

The uploaded source snapshot does not include `vendor/`, `node_modules`, or a migrated database runtime. Laravel boot, `route:list`, `migrate:fresh`, PHPUnit, real database locking/concurrency, queues/mail/storage, semantic TypeScript, ESLint, Vitest, Vite build, and browser/API Tenant-A/Tenant-B and OU-A/OU-B adversarial tests were not executable here.

The corrected create migrations are for a fresh development database. Do not apply them blindly to an existing production schema. Existing installations require a reviewed data-migration and backfill plan for credentials, platform operators, active uniqueness keys, role system keys, access pivots, private object metadata, encrypted device tokens, invitations, and row versions.
