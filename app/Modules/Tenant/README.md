# Tenant module

The Tenant module owns the SaaS tenant boundary: tenant identity, lifecycle, verified domains, private tenant documents, subscription-plan assignment, and the trusted current-tenant context.

Runtime settings are not stored here. `Modules/Configuration` owns validated global, tenant, and organization-unit overrides. Business history remains with its owning module: exchange rates are not configuration values, mail delivery belongs to Communication, and database credentials belong to the platform control plane.

## Trust boundaries

- A tenant context is selected only by a verified tenant host or explicit tenant-selection header on a configured central SaaS host.
- Request body, query parameters, and normal resource route identifiers never select tenant context.
- Every authenticated request must have a matching active `user_tenants` membership.
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

Suspension, deactivation, and archival dispatch `TenantStatusChanged`. Auth owns the listener that revokes tenant sessions and tokens.

## API surfaces

- `/api/v1/platform/tenants` and `/api/v1/platform/tenant-plans`: SaaS control-plane administration.
- `/api/v1/tenant/profile`: current-tenant self-service profile.
- `/api/v1/tenant/domains`: current-tenant verified-domain workflow.
- `/api/v1/tenant/documents`: current-tenant private document workflow.

All mutations use `row_version` compare-and-swap semantics.
