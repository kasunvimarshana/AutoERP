# Tenant module

The Tenant module owns the SaaS tenant boundary: tenant identity, lifecycle, subscription revisions, verified domains, tenant-private assets, onboarding coordination, and the trusted current-tenant context.

It does not own user access, organization hierarchy, authentication, business configuration, reference data, or mail transport. Tenant onboarding coordinates those owner modules through contracts and stores only authoritative resource references and progress.

## Isolation model

The supported database strategy is **shared schema with mandatory tenant ownership**.

- Tenant context is resolved from a verified tenant host or an explicit tenant selection on a trusted central host.
- Public login may accept a human-readable tenant code; raw tenant IDs are not trusted from public payloads.
- Authenticated tenant tokens, active tenant context, user ownership, and selected tenant must match.
- Tenant-owned Eloquent models fail closed when no tenant context is active.
- Queue jobs that access tenant-owned data must implement `TenantAwareJobInterface` and restore context through `RestoreTenantJobContext`.
- Direct tenant foreign keys use `RESTRICT`. Tenants are archived and cleaned through explicit lifecycle workflows; a raw hard delete must never erase business or audit history.
- Local/testing fallback is an explicit routing mode. It never creates a fake `localhost` or IP-based production domain.

A production release still requires migrated-database and Tenant-A/Tenant-B adversarial tests for every feature module, queue, report, export, cache key, and file workflow.

## Module ownership

- `OrganizationUnit` owns the protected root hierarchy and root invariants.
- `User` owns tenant permission catalogues, roles, and administrator assignments.
- `Auth` owns providers, invitations, authentication, sessions, and tokens.
- `Configuration` owns typed global, tenant, and organization-unit overrides.
- `ReferenceData` owns currencies and other shared catalogues.
- `Communication` and platform infrastructure own transport-level mail capabilities.

The Tenant module coordinates these capabilities through contracts. It must not reproduce owner-module tables or business rules.

## Onboarding invariant

A foundation can move to `awaiting_administrator` only when all exact referenced resources are valid:

1. protected root organization;
2. complete tenant permission catalogue;
3. exact fully granted Super Admin role;
4. active internal authentication provider;
5. unexpired pending or accepted initial-administrator invitation targeting that exact root, role, and recipient.

Stored step statuses are progress records, not substitutes for owner-resource verification. Re-running provisioning reconciles pending resources idempotently. An accepted invitation that targets obsolete foundation resources is not silently replaced.

Activation is evaluated from current authoritative data under lock. It requires:

- compatible deployed schema;
- complete foundation resources;
- accepted and operational administrator assigned to the protected root and Super Admin role;
- active base accounting currency;
- active plan revision;
- usable current subscription revision;
- production-ready primary domain, or the explicit local/testing routing fallback.

Readiness responses contain owner, action, and safe blocker details. Invalid subscription data returns a stable error with a correlation ID instead of a raw server exception.

## Domain and routing policy

Production custom domains require a public hostname, ownership verification, application routing, TLS readiness, and reachability before becoming operational.

`localhost`, loopback addresses, protocols, ports, paths, queries, and fragments are not stored as production tenant domains. Local/testing can use:

```dotenv
TENANT_LOCAL_FALLBACK_ENABLED=true
TENANT_LOCAL_FALLBACK_TENANT_CODE=AUTOERP
```

The resolver still rejects this fallback outside local/testing environments.

## Configuration model

Business configuration precedence is:

```text
Organization unit override
→ Tenant override
→ Global override
→ Module-owned default
```

Only canonical, typed, versioned, allowlisted definitions with real runtime consumers are exposed. Stable namespaced keys such as `localization.timezone` are the API contract; numeric definition IDs and arbitrary Laravel `config()` overrides are invalid.

Infrastructure settings are not generic key/value configuration:

- database strategy: shared schema;
- storage strategy: shared private disk with tenant-owned object-key prefixes;
- onboarding mail: platform-managed mailer.

Dedicated tenant database, storage-provider, or mail-provider profiles are not claimed as supported. Adding them requires dedicated encrypted profile models, lifecycle management, connection/provider factories, queue-worker context restoration, health checks, secret rotation, and migration/rollback workflows.

## Platform Step-Up Policy

Sensitive platform actions require recent platform authentication. The step-up window is configured through Auth:

```dotenv
AUTH_PLATFORM_STEP_UP_TTL_SECONDS=900
```

After environment changes, clear cached configuration and restart long-lived PHP/queue workers.

## Tenant-owned tables

- plan catalogue and immutable revisions;
- tenants;
- immutable subscription revisions, current pointers, and subscription events;
- domains and primary-domain assignments;
- private tenant documents;
- storage cleanup jobs;
- lifecycle events and durable event outbox;
- onboarding state and durable step progress.

All module migrations use one table per file and portable Laravel Schema Builder APIs.

## Private assets

Tenant logos and documents use a configured private disk. The database stores tenant-relative object keys and validated metadata, not public or server-absolute paths. Reporting reads branding through `TenantBrandingAssetReaderInterface`.

## Scheduled maintenance

Run Laravel's scheduler continuously:

- `tenant:domains:revalidate` hourly;
- `tenant:expire` hourly;
- `tenant:storage:cleanup` every ten minutes;
- `tenant:events:publish` every minute.

## Required deployment checks

Before release:

1. deploy matching backend and frontend revisions;
2. run reviewed migrations against a fresh verification database;
3. clear Laravel caches and restart long-lived workers;
4. run Laravel boot, route, PHPUnit, migration, queue, mail, DNS/TLS, and browser E2E checks;
5. execute Tenant-A/Tenant-B and organization-unit isolation tests;
6. configure database permissions that prevent direct mutation of append-only audit/history tables.

Corrected create migrations are intended for a fresh development schema. Existing deployed databases require a reviewed data/schema migration plan; do not apply them blindly.
