# AutoERP Tenant and Platform Foundation — Root-Cause Fix Report

## Scope

This remediation was applied to `app/Modules/Tenant` and the directly responsible trust-boundary modules required to complete the tenant lifecycle safely:

- `OrganizationUnit`
- `Auth`
- `User`
- `Configuration`
- `Audit`
- `ReferenceData`
- `Core`
- directly affected platform React routes, pages, contracts, and tests

The implementation intentionally removes defective legacy structures instead of preserving them through compatibility patches.

## Executive verdict

The audited Tenant and Platform Administration source foundation has been substantially rebuilt around authoritative ownership and explicit lifecycle contracts.

- **41 of the 43 original Tenant findings are closed in source.**
- **2 findings remain release-verification gates rather than unresolved compatibility patches:**
  1. the deployed runtime must prove that its release/commit/schema match this package;
  2. complete ERP-wide Tenant-A/Tenant-B isolation must be executed against a migrated Laravel runtime module by module.

This package is suitable for continued development and runtime verification. It must not be labelled production-ready until the backend runtime gates listed below pass.

## Corrected foundations

### 1. Organization hierarchy

The conflicting nested-set representation was removed.

```text
parent_id = authoritative relationship
path + depth = backend-derived
_lft + _rgt = removed
```

The OrganizationUnit-owned hierarchy service now enforces:

- one protected root organization per tenant;
- same-tenant parent ownership;
- cycle and descendant-parent prevention;
- server-derived path and depth;
- transactional subtree rebasing;
- optimistic row-version checks;
- onboarding through the same authoritative hierarchy workflow.

### 2. Durable tenant foundation provisioning

Tenant onboarding is now an idempotent, owner-module orchestration with normalized per-step state:

- root organization;
- permission catalogue;
- protected Super Admin role;
- authentication provider;
- initial administrator invitation.

Each step records status, owner, attempt count, timestamps, safe error code/message, operation ID, and correlation ID. Completed owner steps are not recreated during retry.

Successful finalization, tenant versioning, progress completion, and platform audit are committed atomically. A later readiness read failure cannot rewrite a successfully committed foundation as failed.

### 3. Access and initial administrator lifecycle

- Tenant permission definitions are exact-synchronized at runtime.
- The protected Super Admin role receives the exact required permission set atomically.
- Readiness validates the assigned permission names, not only record counts.
- Pending invitations no longer count as operational administrators.
- Activation requires an active administrator, accepted invitation, tenant access, protected role assignment, and root-organization assignment.
- Invitation inspection, delivery, resend, revoke, replacement, expiry, and public guided acceptance are Auth-owned workflows.
- Raw reusable invitation secrets are not exposed as the normal platform workflow.
- The administrator email is locked only when a durable invitation actually exists.

### 4. Authoritative readiness and activation

The backend readiness contract is the single source of truth. Stable blocker codes replace duplicated frontend business rules.

Activation:

1. locks the tenant;
2. locks/revalidates readiness contributors using deterministic owner contracts;
3. rejects any current blocker;
4. applies the lifecycle transition;
5. writes lifecycle history, audit, and durable outbox intent inside the transaction.

Lifecycle timestamp history is preserved and a dedicated lifecycle event table retains actor, reason, source status, target status, and occurrence time.

### 5. Domain ownership versus operational readiness

Domain ownership is no longer treated as proof that the application is usable.

```text
DNS ownership
→ routing verification
→ TLS verification
→ application reachability
→ operational ready
```

The source now includes:

- queued ownership verification;
- queued operational verification;
- normalized safe diagnostics;
- retry/backoff metadata;
- dedicated throttles;
- one operationally ready primary domain requirement;
- platform health and audited recovery operations.

### 6. Immutable subscriptions and plan governance

Subscription changes create immutable revisions through explicit operations:

- assign;
- renew;
- extend;
- correct;
- cancel;
- expire.

The implementation provides:

- a separate current-subscription pointer and state;
- commercial plan snapshots on each subscription revision;
- exact temporal validation;
- optimistic tenant/pointer concurrency;
- history and events;
- centralized subscription usability policy;
- inactive-plan/currency checks;
- plan deactivation protection while current assignments exist;
- separate latest-created and current-effective plan revision concepts.

### 7. Typed configuration inheritance

The resolver follows one precedence contract:

```text
Organization Unit override
→ Tenant override
→ Platform global value
→ Definition default
```

Implemented safeguards include:

- registered typed definitions owned by modules;
- allowed-scope validation;
- archived-tenant write protection;
- encrypted sensitive values;
- redacted secret history;
- immutable configuration revisions;
- optimistic rollback as a new revision;
- affected-tenant impact preview;
- governed export/import;
- separate view, manage, and secret permissions;
- human-readable tenant and organization selectors.

Arbitrary Laravel `database.*`, `filesystem.*`, `mail.*`, `auth.*`, `queue.*`, or `cache.*` mutation is not exposed. The declared database strategy is shared-schema tenancy. A database-per-tenant strategy would require a separate connection/migration/backup subsystem.

### 8. Tenant documents and private storage

- Canonical tenant-owned private paths are resolved server-side.
- Cleanup accepts only canonical paths under `tenants/{tenantId}/...` on the configured private disk.
- Replacement and deletion use durable cleanup jobs with claiming, retry, dead state, safe errors, and platform recovery.
- Production document uploads require the configured malware scanner.
- Document collections are paginated.
- Generic ungoverned document metadata was removed.
- On-demand storage reconciliation compares document records with the actual private object prefix and reports missing, orphaned, mismatched, unreadable, and invalid-path records.

### 9. Platform Administration

Implemented platform surfaces and backend contracts:

- SaaS Tenants;
- Tenant Plans and revision history;
- Platform Defaults across global/tenant/organization scopes;
- Operators & Permissions;
- Sessions & MFA recovery;
- Platform Audit;
- Platform Health;
- domain/subscription/storage/outbox/onboarding diagnostics;
- audited recovery actions.

Operator permission/status mutations revoke active platform sessions and protect against self-lockout and removal of the last platform manager.

### 10. Platform/tenant trust-boundary separation

- Dedicated central-host platform guard and platform-scoped sessions/tokens.
- Platform and tenant execution contexts are mutually separated.
- Tenant host, selected tenant, token tenant, and active user ownership must agree.
- Client-supplied ownership identifiers are not authoritative.
- Critical Tenant/Auth/User/OrganizationUnit relationships use explicit tenant predicates and composite tenant foreign keys where applicable.
- Runtime release ID and commit SHA are visible in Platform Health to detect stale/mismatched deployments.

## Original finding disposition

| Finding | Disposition |
|---|---|
| TNT-C01 runtime/source mismatch | Source diagnostics completed; deployment parity remains a release gate. |
| TNT-C02 contradictory hierarchy | Closed: authoritative adjacency hierarchy and derived path/depth. |
| TNT-C03 empty Super Admin role | Closed: exact runtime permission synchronization. |
| TNT-C04 readiness without administrator | Closed: accepted/active administrator and assignments required. |
| TNT-C05 committed onboarding marked failed | Closed: finalization/audit atomic; post-read failures non-authoritative. |
| TNT-C06 raw exception exposure | Closed: safe codes/messages and correlation IDs; full errors only in logs. |
| TNT-C07 incorrect administrator email lock | Closed: durable invitation state owns locking and recovery. |
| TNT-C08 stale activation readiness | Closed: locked final readiness revalidation. |
| TNT-H01 no per-step recovery | Closed: normalized durable onboarding steps. |
| TNT-H02 manual invitation token | Closed: queued Auth-owned delivery and lifecycle. |
| TNT-H03 seeder-owned platform permissions | Closed: runtime sync command and User-owned catalogue. |
| TNT-H04 subscription commits then reports audit failure | Closed: audit inside lifecycle transaction. |
| TNT-H05 suspended/inactive subscriptions never expire | Closed: current pointer expires independently of tenant status. |
| TNT-H06 mutable subscription revision | Closed in application contract: immutable model/repository revision API. |
| TNT-H07 incomplete lifecycle/temporal integrity | Closed: explicit commands and validated periods. |
| TNT-H08 plan deactivation race | Closed: plan/current-assignment locking and conflict rejection. |
| TNT-H09 future revision treated as current | Closed: latest-created and current-effective are separate. |
| TNT-H10 plan revision mutation | Closed in application contract: immutable revisions and no mutation command. |
| TNT-H11 DNS ownership equals readiness | Closed: separate operational checks. |
| TNT-H12 synchronous DNS/weak retries | Closed: queued checks, throttles, safe diagnostics, retry metadata. |
| TNT-H13 arbitrary storage deletion | Closed: canonical tenant path/private disk policy. |
| TNT-H14 inactive base currency accepted | Closed: ReferenceData-owned active-currency readiness. |
| TNT-H15 duplicated subscription rules | Closed: centralized subscription policy. |
| TNT-H16 non-authoritative capability catalogue | Closed for current plan-controlled modules: one backend schema feeds validation, readiness, entitlements, middleware codes, and frontend catalogue. |
| TNT-H17 application-wide isolation unproven | Tenant/direct boundaries hardened; migrated ERP-wide adversarial tests remain a release gate. |
| TNT-H18 ambient-context repository commands | Closed for Tenant mutations/readiness: explicit tenant IDs and narrow control-plane paths. |
| TNT-H19 missing state invariants | Closed with portable enums, unsigned values, uniqueness, tenant-aware FKs, optimistic versions, and backend temporal validation; migrated concurrency tests remain required. |
| TNT-M01 timestamp history erased | Closed: timestamps preserved and lifecycle history added. |
| TNT-M02 only latest lifecycle reason retained | Closed: immutable lifecycle events. |
| TNT-M03 best-effort logo cleanup | Closed: canonical storage plus durable cleanup. |
| TNT-M04 ungoverned metadata | Closed in Tenant records: unused generic metadata removed. |
| TNT-M05 no malware scanning | Closed: scanner abstraction and production scanner requirement. |
| TNT-M06 storage metadata drift | Closed: on-demand private-storage reconciliation and health reporting. |
| TNT-M07 unpaginated domains/documents | Closed: bounded pagination and ordering. |
| TNT-M08 unreachable document metadata | Closed: dead generic metadata path removed. |
| TNT-M09 no consumer idempotency contract | Closed for Auth consumer: processed integration-event deduplication. |
| TNT-M10 health console-only | Closed: permission-scoped Platform Health and audited retries. |
| TNT-M11 inconsistent clocks | Closed in domain workflows through `ClockInterface`; framework seed/model-time helpers remain outside authoritative mutations. |
| TNT-M12 inconsistent unknown-tenant semantics | Closed through platform target resolution and standardized not-found handling. |
| TNT-M13 step-up on readiness GET | Closed: step-up reserved for sensitive mutations/recovery. |
| TNT-M14 no DNS throttle | Closed: dedicated verification/probe limits. |
| TNT-M15 broad workflow classes | Closed without arbitrary fragmentation: verification, retries, storage, invitation, subscriptions, readiness, and platform queries extracted by ownership. |
| TNT-M16 historical plan name changes | Closed: immutable commercial identity snapshots on subscriptions. |

## Verification completed

| Check | Result |
|---|---:|
| Changed/new PHP files linted | **266** |
| PHP syntax failures | **0** |
| Changed/new TypeScript files linted | **42** |
| ESLint errors/warnings | **0 / 0** |
| TypeScript typecheck | **Passed — 0 diagnostics** |
| Frontend test inventory | **40 files / 142 tests passed** |
| Additional Platform Health focused test after reconciliation | **Passed** |
| Production build | **Passed** |
| Vite transformed modules | **632** |
| Main JavaScript bundle | **456.67 kB / 140.85 kB gzip** |
| Affected migration files | **53** |
| Affected tables created | **53** |
| Migration structural/order issues | **0** |
| Missing internal imports | **0** |
| Deleted legacy symbol references | **0** |
| `git diff --check` | **Passed** |

The test inventory emitted one pre-existing non-failing React `act(...)` warning from a Vehicle page test. It did not fail any test and is outside this Tenant/Platform change boundary.

## Migration verification

The affected module migrations were statically verified for:

- one table per migration file;
- no later `Schema::table()` patch migration in the corrected foundation;
- no raw driver-specific SQL/driver checks;
- no duplicate migration filenames;
- no foreign-key reference to a table created later within the audited dependency set.

An Auth migration ordering defect was corrected so `auth_platform_sessions` is created before access/refresh tokens reference it.

## Runtime verification gates

The supplied source package does not contain `vendor/`, so these checks could not be executed honestly:

- Laravel application boot;
- fresh migrations and seeders;
- `artisan route:list`;
- backend PHPUnit;
- real database locking/concurrency behavior;
- queue/outbox workers;
- SMTP invitation delivery;
- real DNS, reverse-proxy, TLS, and reachability checks;
- browser onboarding and Tenant-A/Tenant-B E2E tests.

A fresh development database is required because the corrected source consolidates schema changes into original create migrations. Do not apply this package blindly to an existing production schema. Production data requires an explicit reviewed migration plan.

## Mandatory release sequence

1. Set `APP_RELEASE` and `APP_COMMIT_SHA` in the immutable deployment artifact.
2. Install Composer dependencies from `composer.lock`.
3. Boot Laravel and validate service-provider bindings.
4. Run a fresh migration/seed cycle in a disposable database.
5. Run platform permission synchronization.
6. Run the complete backend test suite and concurrency tests.
7. Execute onboarding success/failure/retry/acceptance/activation E2E.
8. Execute Tenant-A/Tenant-B and OU access adversarial tests across every feature module.
9. Verify queue, mail, DNS, TLS, storage, malware scanner, and outbox integrations.
10. Package and promote the exact tested release/commit without rebuilding source between environments.

## Release conclusion

The previous legacy hierarchy, false readiness, incomplete administrator provisioning, unsafe domain readiness, mutable commercial lifecycle, weak Platform Administration, and unsafe storage contracts have been corrected at their owning modules.

The source package is **statically verified and frontend-regression verified**, but final production readiness remains conditional on the listed Laravel/infrastructure runtime gates.
