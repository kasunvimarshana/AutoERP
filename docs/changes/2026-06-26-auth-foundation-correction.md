# Auth foundation correction

Date: 2026-06-26
Scope: `app/Modules/Auth` and the minimum required Auth-owned integration contracts and flows in Core, User, Tenant, OrganizationUnit, Audit, and the authentication frontend.

## Why

The previous Auth implementation exposed generic token, validation, verification, identity, and SSO surfaces; trusted client-authored subject, session, scope, TTL, IP, and user-agent values; and managed tenant/platform authentication through generic repositories, mixed identifiers, nullable expiry, and parallel middleware/provider stacks. Session revocation did not reliably invalidate access tokens, OAuth authorization could impersonate another tenant user or escalate scopes, refresh rotation lacked reuse detection, platform MFA enrollment was password-only, and several related modules duplicated Auth or OrganizationUnit schema knowledge.

## What changed

- Removed client-controlled network attribution and derive request context only from the trusted server/proxy request.
- Removed generic token issuance, public token introspection, generic verification challenges, identity link/unlink, incomplete SSO aliases, and password-only MFA enrollment APIs.
- Split tenant and platform authentication into separate sessions, access tokens, refresh tokens, login audits, credentials, guards, and realm-specific lifecycle services.
- Added opaque high-entropy tokens with lookup-key/secret separation and application-key-derived HMAC digests; plaintext token secrets are never persisted.
- Made access-token validation enforce the owning session status, expiry, principal, tenant/client graph, and current user/operator activity on every request.
- Added refresh-token family lineage, one-time rotation, reuse detection, family compromise handling, and session-wide revocation.
- Made all scopes, grants, and TTL values server-owned through typed registries and validated configuration.
- Rebuilt OAuth authorization around the current authenticated tenant principal/session, exact redirect URIs, registered scopes, active clients, mandatory S256 PKCE, confidential-client secret verification at exchange, and atomic one-time code consumption.
- Reworked tenant and platform login with uniform external credential failures, dummy hash verification, layered rate limiting, trusted device context, and append-only identifier-hash login audits.
- Replaced password-only platform MFA enrollment with a short-lived one-time proof bound to recipient-owned invitation acceptance.
- Added persisted TOTP counter replay prevention and hashed, atomically consumed backup codes.
- Moved platform account recovery orchestration to the User-owned platform-operator aggregate; recovery revokes Auth sessions, credentials, and MFA before issuing a new recipient-owned invitation.
- Replaced caller-supplied numeric session identifiers with server-owned UUID session resources and current-principal self-service authorization.
- Made refresh cookies HTTP-only, realm-specific, and expire according to the issued refresh token rather than a duplicated browser TTL.
- Removed the legacy Auth god workflow, generic DataRecord repositories, provider registry, generic session/token providers, thin pass-through services, duplicate middleware, fail-open client policy, and dead DTOs/constants.
- Split the oversized token lifecycle into cohesive tenant and platform services behind a small realm-dispatch facade.
- Added typed statuses for tokens, sessions, credentials, identities, providers, clients, grants, scopes, and authorization codes.
- Rebuilt Auth create migrations as one table per file with mandatory expiries, composite graph foreign keys, refresh lineage, MFA replay state, tenant-qualified processed-event idempotency, retained security history, and portable Laravel APIs.
- Added explicit retention configuration, scheduled purge command, and dependency-safe expired-data cleanup.
- Removed Auth concrete reads of User/Tenant/OrganizationUnit models from authentication paths in favor of owner-provided narrow contracts.
- Removed Tenant health direct Auth model access through an Auth-owned health-reader contract.
- Fixed Audit ownership validation at its source: Audit now consumes Tenant and OrganizationUnit owner directories instead of querying stale `organization_units.deleted_at` schema knowledge.
- Updated the frontend to use verified tenant host context, in-memory access tokens, HTTP-only refresh cookies, payload-free logout, no raw session IDs, and guided invitation-bound password plus MFA setup.
- Replaced stale Auth tests with trust-boundary, opaque-token, refresh-cookie, invitation/MFA, and browser-session-storage regression coverage.

## Deliberate design decisions

- OAuth `state` remains an opaque client correlation value that is round-tripped unchanged. This service implements OAuth authorization code, not OpenID Connect; no server-side OIDC nonce contract is claimed.
- Database-native enums and driver-specific CHECK constraints are not used. Portable string columns are governed by typed enums, mandatory state timestamps, row locks, composite keys, and service-owned legal transitions.
- Historical authentication identifiers and security records are retained and not silently recycled through soft-delete compatibility behavior. Reuse requires an explicit, reviewed lifecycle rather than automatic key reclamation.

## Ownership boundaries

- Auth owns authentication secrets, identities, login, MFA, sessions, tokens, OAuth, invitation acceptance, and Auth retention.
- User owns tenant-user and platform-operator profiles/lifecycle, access governance, operator invitations, and account-recovery orchestration.
- Tenant and OrganizationUnit own their authoritative directories and lifecycle semantics.
- Audit owns immutable audit persistence and validates ownership through owner contracts.
- Core contains only narrow cross-module contracts and shared clock/execution abstractions.

## Source verification completed

- Full project PHP syntax lint excluding dependencies: 2,567 files, zero failures.
- TypeScript/TSX syntax parse: 627 files, zero failures.
- Frontend relative-import scan: zero missing imports.
- Internal PHP symbol/import scan: zero missing symbols.
- Auth route/controller action scan: zero mismatches.
- Auth migrations/tables: 18/18, one table per migration, no duplicate creation, patch migration, native enum, raw SQL, or model/fillable schema findings.
- Auth outbound dependency graph: Audit, Configuration, Core, Tenant, and User; no dependency cycle containing Auth.
- Unsafe generic Auth routes, public token validation, SSO/verification surfaces, client-authored IP/user-agent/TTL fields, legacy browser session storage, DataRecord repositories, and stale god-service symbols: zero remaining source references.
- Source delta from clone-109 baseline: 46 added, 92 modified, and 88 deleted entries (226 total changed paths).

## Runtime and deployment gates

The uploaded snapshot does not include Composer dependencies, frontend dependencies, or a migrated service environment. Laravel boot, `route:list`, `migrate:fresh`, PHPUnit execution, database concurrency, queues/mail, semantic TypeScript, ESLint, Vitest, Vite build, and browser/API adversarial E2E were not executable here.

The corrected create migrations target a fresh development database. Existing environments require a reviewed data migration for credentials, identities, sessions, token families, authorization codes, MFA methods, login audits, invitation deliveries, and processed events. Do not apply the source package blindly to an existing production schema.
