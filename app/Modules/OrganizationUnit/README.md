# OrganizationUnit module

`OrganizationUnit` owns tenant organization identity, hierarchy, lifecycle, type rules, private branding/documents, ownership checks, and hierarchy reads required by other capabilities. User membership is owned by `Modules/User`; authentication-session scope switching is owned by `Modules/Auth`; typed runtime settings are owned by `Modules/Configuration`.

## Authoritative invariants

- Every organization unit belongs to exactly one tenant.
- Each tenant has exactly one protected root.
- `parent_id` is the authoritative hierarchy relationship.
- `path`, `path_hash`, and `depth` are server-derived projections.
- A unit type must be active and its `level` must equal the unit depth.
- Codes are tenant-unique, canonical, and immutable after creation.
- The root cannot be moved, deactivated, or retired.
- Non-root units require an active, non-retired parent.
- Hierarchy moves are transactional, cycle-safe, and validate every descendant type/depth.
- Organization units are retained aggregates: they are deactivated and retired, not deleted.
- Retired units are read-only. Historical documents remain readable.

## Trusted current scope

The current organization unit is never inferred from ordinary request payload, route parameters, names, paths, or arbitrary client headers.

```text
Authenticated tenant session/token organization_unit_id
→ active tenant/user validation
→ active user membership validation
→ active, non-retired organization unit
→ current request context
```

A user changes scope only through the Auth-owned organization-unit switch command. The command locks and validates the membership and updates the active session/access/refresh-token scope atomically. Membership is exact: access to a parent does not silently grant descendant access. Descendant access requires explicit assignments until an owner-approved hierarchical grant model is introduced. Operational feature routes require a resolved organization unit. Administrative and tenant-global routes opt into optional scope explicitly.

## Data-scope contract for feature modules

Each feature table/query must deliberately declare one of these semantics in its owning module:

```text
OU_REQUIRED
OU_OPTIONAL_WITH_TENANT_GLOBAL_FALLBACK
TENANT_GLOBAL_ONLY
```

`organization_unit_id = null` must never receive an implicit module-specific meaning. `OrganizationUnit` provides the trusted context foundation; each feature owner remains responsible for enforcing its own query/write scope and for adversarial OU-A/OU-B tests.

## Configuration overrides

Configuration values are owned by `Modules/Configuration`. Definitions are typed and module-owned. Resolution is:

```text
Exact current organization unit
→ nearest active parent organization unit (only when the definition enables hierarchy inheritance)
→ remaining active parent chain
→ tenant
→ global
→ definition default
```

Only settings explicitly declared by their owning module may be overridden. Arbitrary Laravel configuration keys are not exposed. Absence of a scope row means inherit; a stored `null` is an explicit value only when the definition declares `nullable = true`. Any incompatible definition-version change must ship with an owner-provided data migration before the new definition is enabled.

Appropriate OU settings include timezone, branch branding, document presentation, default warehouse, numbering policy, working hours, and module business policies. Infrastructure such as database connections, encryption keys, auth guards, cache/session/queue/log drivers is platform-managed. A genuine per-OU SMTP, storage-provider, external integration, or database requirement must be implemented as a dedicated encrypted and validated provisioning profile/capability—not as a generic key/value override.

Settings affecting financial/legal history must be snapshotted by the owning transaction/document module.

## Private assets

Logos and documents use server-created tenant/OU object keys on configured private storage. Raw paths, object keys, MIME types, sizes, and checksums are never accepted from clients. The backend derives metadata, enforces size/MIME allowlists, scans uploads, records checksums, and schedules durable cleanup for replacement/deletion.

## Authorization and audit

Organization-unit, type, and document actions have module-owned permissions and backend enforcement. Mutations and authenticated scope switches write audit events in the same database transaction as the state change.

## Lifecycle

Deactivation and retirement run registered lifecycle blockers. User assignments are owned by User; active sessions are owned by Auth. Feature modules that maintain active OU-dependent workflows may register `OrganizationUnitLifecycleBlockerInterface` contributors in their own service provider.

## API

Tenant APIs are versioned under `/api/v1` and never expose raw storage paths or require users to type foreign keys. The frontend uses paginated human-readable parent/type selectors, impact confirmations, optimistic concurrency, and an authenticated scope switcher.
