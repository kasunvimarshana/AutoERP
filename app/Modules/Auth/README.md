# Auth module

The Auth module owns authentication credentials, identities, sessions, access and refresh tokens, OAuth authorization codes, invitation acceptance, and authentication security history. It does not own tenant lifecycle, user profiles and permissions, organization hierarchy, or platform-operator profile governance.

## Trust boundaries

The following values are always derived on the server and must never be accepted as authoritative request input:

- tenant, tenant user, platform operator, identity, and session ownership;
- request IP address and user agent;
- access-token and refresh-token scopes, grant type, and lifetime;
- OAuth authorization subject and session;
- current organization-unit session context;
- credential, token, session, and authorization-code status timestamps.

Tenant context is resolved from the verified request host. The authenticated subject is resolved from the validated bearer token and its active owning session. Refresh tokens are read from secure HTTP-only cookies.

## Realm separation

Tenant and platform authentication are separate legal-state models:

```text
Tenant realm
User credential -> tenant session -> tenant access/refresh token family

Platform realm
Platform-operator credential -> platform session
-> platform access/refresh token family
```

A token prefix selects the realm-specific validator. A tenant token cannot authenticate a platform route, and a platform token cannot authenticate a tenant route. Tenant and platform login services, profile builders, and user directories are resolved independently; a platform-only dependency failure cannot block tenant login and vice versa.

## Login transaction boundary

A successful login is one atomic application outcome:

```text
credential verification
  -> locked organization access / platform credential verification
  -> session and token issuance
  -> complete profile and permission readiness
  -> successful login-attempt audit
  -> commit
```

If any persistence or profile-readiness step fails, the transaction rolls back and no active session/token pair is retained. Expected authentication failures use the normalized `AuthFailure` response contract; infrastructure failures remain observable through the global correlation-ID error handler.

Unauthenticated tenant login, refresh, and OAuth exchange use context-resolution middleware that validates the verified host without requiring an already authenticated user. Protected tenant routes additionally require token-to-tenant access matching.

## Session and token state machines

### Session

```text
active + unexpired
  -> revoked
  -> retained until the configured security-history purge boundary
```

Every access-token validation checks the token and its owning session, principal, tenant/client graph, status, and expiry. Revoking a session revokes its access and refresh tokens.

### Refresh family

```text
active refresh token
  -> rotated to one child token
  -> reused old token means family compromise
  -> revoke family and owning session
```

Plain tokens are returned once. Persistence stores a lookup key and application-key-derived HMAC digest, never the token secret.

### OAuth authorization code

```text
issued for current authenticated tenant subject/session
  -> exact client + redirect URI + S256 PKCE + registered scopes
  -> atomically consumed once
  -> expired/revoked codes cannot be exchanged
```

The exchange request cannot replace the approved subject, session, or scopes. Confidential-client secrets are accepted only at exchange.

## Platform Recovery

Platform account recovery is owned by the User module because it changes the platform-operator aggregate. The recovery command revokes Auth-owned sessions and credentials, moves the operator to the invited lifecycle, and issues a new recipient-owned invitation. It protects self-recovery and the last active platform manager.

## Module ownership

- **Auth**: credentials, identities, login, sessions, tokens, OAuth, invitation acceptance, Auth retention.
- **User**: tenant-user and platform-operator profile/lifecycle, roles, permissions, organization access, operator invitations and recovery orchestration.
- **Tenant**: verified tenant resolution, tenant lifecycle, tenant directory and provisioning policy.
- **OrganizationUnit**: organization-unit existence, hierarchy, lifecycle and membership directory.
- **Audit**: immutable audit persistence; it consumes owner directories instead of querying Tenant/OrganizationUnit schemas directly.
- **Core**: narrow cross-module contracts and shared clock/execution-context abstractions.

## Public API principles

Supported public entry points are deliberately small:

- tenant login and cookie-owned refresh;
- OAuth code exchange;
- initial tenant-administrator invitation inspect/accept;
- platform login and cookie-owned refresh;
- platform-operator invitation inspect/accept in the User module.

Generic token issuance, public token introspection, generic verification challenges, SSO aliases, and external identity mutation endpoints are not exposed.

## Persistence rules

- one table per migration file;
- portable Laravel schema APIs;
- mandatory expiry for active sessions/tokens/codes;
- composite tenant/principal/session foreign keys;
- `RESTRICT` for retained security history;
- explicit refresh lineage and processed-event idempotency;
- typed statuses in application code with service-owned legal transitions;
- no generic metadata mutation surface on security records.

## Operations

`auth:readiness` verifies key material, database connectivity, required schema, cache read/write capability, critical container bindings, and both realm login/profile graphs before login traffic is enabled.

`auth:incident {correlationId}` locates a support reference in local structured Laravel logs without exposing server logs through a browser endpoint.

`auth:purge-expired` removes expired authorization codes, tokens, sessions, login-attempt history, invitation delivery operations, and processed integration events in dependency-safe order using configured retention periods.

Auth configuration is validated during provider boot. Invalid TTL, rate-limit, password, OAuth-scope, cookie, or retention configuration must fail startup rather than silently use an unsafe fallback. Route-level throttling provides a coarse IP boundary; the service-level account/account-IP throttle is the authoritative credential-abuse policy. Both depend on the cache probe covered by `auth:readiness`.

Platform operators are invitation-first identities. `AUTOERP_PLATFORM_ADMIN_EMAIL` can seed an invitation when explicitly enabled; no administrator password is accepted from environment configuration. The recipient establishes the credential through the guided invitation flow.

## Verification expectations

A release environment must run:

1. Laravel boot and `artisan route:list`;
2. fresh migrations on every supported database driver;
3. PHPUnit Auth security and concurrency suites;
4. tenant/platform login-refresh-logout-session tests;
5. OAuth impersonation, scope, redirect, PKCE, and one-time-code tests;
6. refresh reuse/family compromise tests;
7. Platform account recovery tests;
8. Tenant-A/Tenant-B and OU-A/OU-B adversarial tests;
9. browser tests using verified tenant hosts and HTTP-only refresh cookies.
