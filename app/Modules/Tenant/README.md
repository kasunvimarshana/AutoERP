# Tenant module

The Tenant module owns the SaaS tenant boundary: tenant identity, lifecycle, verified domains, private tenant documents, subscription-plan assignment, and the trusted current-tenant context.

Runtime settings are not stored here. `Modules/Configuration` owns validated global, tenant, and organization-unit overrides. Business history remains with its owning module: exchange rates are not configuration values, mail delivery belongs to Communication, and database credentials belong to the platform control plane.

## Trust boundaries

- A tenant context is selected only by a verified tenant host or explicit tenant-selection header on a configured central SaaS host.
- The login endpoint may accept a human-readable `tenant_code`; Auth translates it into the same validated tenant-selection header before credentials are checked. Raw tenant IDs are not accepted from the public login payload.
- Request body, query parameters, and normal resource route identifiers never select tenant context outside that explicit pre-authentication login contract.
- Local/testing environments may resolve the configured bootstrap tenant automatically on `localhost`; production never uses this fallback.
- Every authenticated tenant request must use a token whose tenant matches the active tenant-owned user record. Organization-unit access is managed separately through `user_organization_units`.
- `status` is the only tenant lifecycle source of truth.
- Cross-tenant platform endpoints additionally require an explicitly flagged platform operator; a tenant Super Admin role alone is insufficient.

## Tables

- `tenant_plans`: SaaS plan catalogue with optimistic concurrency.
- `tenants`: tenant identity, lifecycle, plan, and base-accounting-currency invariant.
- `tenant_domains`: globally unique domains with DNS ownership verification and one primary domain per tenant.
- `tenant_documents`: private server-managed files with checksums and tenant-scoped access.

## Runtime configuration inheritance

Effective configuration is resolved by `Modules/Configuration`:

```text
Organization unit override
→ Tenant override
→ Global override
→ Module-owned default
```

The base accounting currency is deliberately not a generic override. It is a tenant invariant and becomes immutable after first activation. Document currencies and effective-dated exchange rates remain in their owning business modules.

## Lifecycle

```text
Draft → Active → Suspended / Inactive → Active or Archived
```

Activation requires:

- active base accounting currency;
- verified primary domain;
- non-expired trial or subscription.

Suspension, deactivation, and archival enqueue `TenantStatusChanged` in `tenant_event_outbox` inside the same database transaction as the tenant update. The scheduled publisher delivers events with retry/backoff semantics. Auth owns an idempotent listener that revokes tenant sessions and tokens.

## API surfaces

- `/api/v1/platform/auth/*`: tenant-independent platform-operator authentication.
- `/api/v1/platform/tenants` and `/api/v1/platform/tenant-plans`: SaaS control-plane administration using platform-scoped tokens only.
- `/api/v1/tenant/profile`: current-tenant self-service profile.
- `/api/v1/tenant/domains`: current-tenant verified-domain workflow.
- `/api/v1/tenant/documents`: current-tenant private document workflow.

All mutations use `row_version` compare-and-swap semantics.

## Scheduled maintenance

Run Laravel's scheduler continuously. It executes:

- `tenant:domains:revalidate` hourly;
- `tenant:expire` hourly;
- `tenant:storage:cleanup` every ten minutes;
- `tenant:events:publish` every minute.

## Required production configuration

- `TENANT_CENTRAL_HOSTS`: comma-separated trusted control-plane hosts.
- `TRUSTED_PROXIES`: only proxies that are allowed to supply forwarded host data.
- `TENANT_DOCUMENT_DISK`: a private, non-served filesystem disk.
- `AUTOERP_SEED_PLATFORM_OPERATOR=true`, `AUTOERP_PLATFORM_ADMIN_EMAIL`, and `AUTOERP_PLATFORM_ADMIN_PASSWORD`: explicit bootstrap platform credentials.
- `TENANT_LOCAL_FALLBACK_ENABLED=false`: keep automatic tenant fallback disabled outside local/testing environments.
