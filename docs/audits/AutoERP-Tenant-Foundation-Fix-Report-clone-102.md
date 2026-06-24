# AutoERP Tenant Foundation End-to-End Fix Report — clone-102

## Executive verdict

The Tenant foundation was rebuilt at the root-cause level rather than protected with compatibility shims. The supported architecture is now explicit:

- **Tenancy model:** shared database, shared schema, mandatory tenant ownership.
- **Runtime resolution:** verified tenant domain or configured central platform host; fail closed elsewhere.
- **Configuration precedence:** Organization Unit → Tenant → Global → Definition Default.
- **Commercial entitlement source of truth:** immutable plan revision → historical subscription → canonical current-subscription pointer.
- **Primary domain source of truth:** one canonical `tenant_primary_domains` row per tenant.
- **Onboarding:** platform-owned, idempotent state machine coordinating owner-module provisioners.
- **Control plane:** central-host only, granular permissions, MFA, and recent-authentication step-up.

All Tenant-owned actionable defects identified by the audit were corrected. Remaining release gates are deliberately limited to application-wide feature-module isolation proof, owner-module platform administration/audit functions, and runtime database/E2E execution unavailable in the supplied snapshot.

## Change summary

### 1. Tenant resolution and isolation

- Preserved the correct fail-closed Eloquent tenant scope and immutable tenant ownership foundation.
- Restricted platform authentication and administration to configured central hosts.
- Removed production loopback/neutral-host bypass behavior.
- Kept host, selected tenant, token tenant, user membership and tenant lifecycle checks aligned.
- Added explicit control-plane entry at platform/CLI boundaries and explicit tenant entry for tenant work.
- Added tenant-aware background job context restore/clear middleware.
- Bound critical User/Role/Permission queries by tenant as well as record identity.

### 2. Onboarding and lifecycle

The previous draft-tenant deadlock was replaced by this guided workflow:

```text
Create draft tenant
→ provision root organization structure
→ provision permission catalogue and Super Admin role
→ provision authentication provider
→ issue initial administrator invitation
→ create and verify domain
→ assign immutable subscription
→ evaluate readiness
→ activate through audited lifecycle
```

Provisioning is idempotent, row-version protected, transactionally locked, and records durable onboarding state. One-time invitation secrets are returned only at issuance.

### 3. Domain ownership

- Removed duplicated `is_primary` / `primary_marker` representations.
- Added canonical `tenant_primary_domains` pointer with composite ownership foreign key.
- Database domain uniqueness is authoritative.
- DNS revalidation claims rows atomically and respects claim leases and row versions.
- A failed primary domain promotes a verified fallback; otherwise the tenant is suspended.
- Synthetic verification is local/testing only.

### 4. Plans, revisions and subscriptions

- Removed direct mutable plan/subscription fields from tenants.
- Added immutable plan revisions and historical subscriptions.
- Added canonical current-subscription pointer.
- Added usage contributors for users, organization units, warehouses and storage.
- Final readiness is recalculated while the tenant row is locked in the same transaction as assignment.
- Active tenants cannot silently lose enabled modules.
- Foundation modules and commercially controlled modules are explicit separate catalogues.

### 5. Configuration override model

Approved domain settings use typed definitions and the following precedence:

```text
Organization Unit → Tenant → Global → Definition Default
```

Definitions must explicitly declare owner, sensitivity and runtime mutability. Duplicate ownership, unknown keys and late mutation fail explicitly. Ambiguous settings were renamed:

- `app.name` → `branding.display_name`
- `app.timezone` → `localization.timezone`

The application intentionally does **not** expose arbitrary Laravel configuration to tenants. `APP_KEY`, guards, middleware, central hosts, the platform database and similar trust-boundary settings remain platform-owned.

The supported database strategy is explicitly `shared_schema`. Dedicated database, filesystem or mail credentials are not generic key/value overrides; they require dedicated typed provider profiles, connection testing, secret rotation and worker reset semantics. Those profiles were not added speculatively because no concrete provider/operations requirement exists in this snapshot.

### 6. Platform security

- Separate tenant/platform refresh cookie names and narrow paths.
- Granular platform permissions for tenants, onboarding, domains, subscriptions, plans, configuration and secrets.
- Platform MFA enrollment, TOTP verification and one-time backup codes.
- Recent-authentication step-up for destructive or security-sensitive actions.
- Refresh rotation preserves authentication age instead of silently refreshing step-up authority.
- Strong registration password policy and tenant-owned admission modes.

### 7. Durable side effects

- Document metadata and cleanup intent are committed atomically.
- Storage cleanup uses claim leases, bounded retries and dead-letter state.
- Tenant outbox uses claims, bounded retries, dead-letter state, replay and retention.
- Scheduled jobs use overlap protection and single-server semantics where appropriate.

### 8. Module boundaries

The Tenant module no longer participates in a circular dependency component. Its direct module dependencies are now one-way:

```text
Tenant → Core
Tenant → Audit
Tenant → ReferenceData
```

The permission-definition port was moved to Core, the platform permission vocabulary is owned by Tenant, and ReferenceData no longer mutates Tenant records from its seeder. Wider business-module cycles remain outside this Tenant-focused change and must be fixed by their owning modules.

## Finding disposition

| Status | Count |
|---|---:|
| Fixed | 23 |
| Residual application-wide release gate | 1 |
| Resolved by explicit architecture decision | 1 |
| Partially fixed / bounded by supported architecture | 1 |
| Partially fixed / owner-module release gate | 1 |
| Fixed in Tenant; feature closeout remains owner-specific | 1 |
| Fixed for Tenant boundary; wider ERP cycles remain separate work | 1 |
| Partially fixed | 1 |
| Partially fixed / runtime gate | 1 |

Total audit findings: **31**.

The detailed per-finding disposition, owner and remaining gate are available in `AutoERP-Tenant-Fix-Matrix-clone-102.csv`.

## Verification completed

| Check | Result |
|---|---|
| Changed/new PHP syntax | Passed — 146 files |
| Tenant trust-boundary PHP syntax | Passed — 604 files |
| Missing internal `Modules\...` symbols | 0 |
| `git diff --check` | Passed |
| Tenant dependency cycles | 0 |
| TypeScript typecheck | Passed — 0 diagnostics |
| ESLint | Passed — 0 errors / 0 warnings |
| Vitest | Passed — 31 files / 121 tests |
| Production frontend build | Passed — 616 modules transformed |
| Main JS bundle | 453.53 kB / 140.21 kB gzip |
| Production npm dependencies | 0 vulnerabilities |
| Full npm dependency audit | 1 low-severity development-only `esbuild` advisory |
| Tenant test files present | 8 |

## Runtime verification limitation

The uploaded snapshot did not contain `vendor/`, and Composer was not installed. Network name resolution also prevented downloading Composer. Therefore the following could not be executed in this environment:

- Laravel application boot and service-provider resolution;
- `artisan route:list`;
- fresh migrations and seeders against supported database engines;
- PHPUnit backend tests;
- queue/scheduler workers;
- real DNS, storage and mail integrations;
- browser/API Tenant-A/Tenant-B E2E tests.

The PHP source was linted and frontend runtime contracts were compiled/tested, but production release still requires the runtime gates below.

## Mandatory release gates

1. Run fresh migrations and seeders on every supported database engine. This is a clean development-stage schema correction and intentionally does not preserve the flawed legacy schema.
2. Run all backend tests, including the eight Tenant tests added/updated here.
3. Add migrated Tenant-A/Tenant-B adversarial tests for every feature module containing direct Query Builder or raw SQL.
4. Verify platform routes reject tenant domains and unconfigured hosts behind the production proxy/load balancer.
5. Exercise concurrent domain verification, subscription assignment, onboarding retries, outbox replay and storage cleanup workers.
6. Complete platform operator administration in User and platform audit browsing in Audit before production operations.
7. Verify the low-severity development `esbuild` upgrade separately before changing the lockfile.

## Migration note

This package is intended for the project’s current development stage. It corrects original create migrations and removes legacy design mistakes instead of adding compatibility migrations. Use a **fresh database migration and seed**. Do not apply these create-migration changes blindly to an existing production database.
