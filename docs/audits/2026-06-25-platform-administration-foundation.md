# Platform Administration Foundation Audit

**Date:** 2026-06-25  
**Source baseline:** `AutoERP-2026051623-refactor-core-modules-clone-95-ui.zip` (`515d0e6`)  
**Scope:** Platform Defaults governance, tenant onboarding/readiness, control-plane authorization, and OrganizationUnit hierarchy integrity.

## Source provenance

The later `clone-99` snapshot referenced by the preceding work was not available in the current conversation or accessible file library. The newest complete accessible source snapshot was `clone-95`; all changes in this package are based on that verified source. No assumptions were made about unseen `clone-99` code.

## Completed foundation

### 1. Explicit SaaS control-plane boundary

- Added central-host middleware for every `/api/v1/platform/*` endpoint.
- Platform routes fail closed when no central host is configured in production.
- Platform privileges require both:
  - an active user explicitly marked as a platform operator; and
  - the exact tenant-scoped platform permission.
- Tenant roles alone can no longer expose platform permissions to non-platform operators.
- Replaced permission-prefix literals with named sources of truth.

### 2. Governed Platform Defaults

- Removed platform-default mutation from ordinary tenant configuration routes.
- Added dedicated central-host-only Platform Defaults API and UI.
- Added exact platform permissions for view, manage, and sensitive-value management.
- Added immutable configuration revision history for create, update, and remove operations.
- Revision records preserve explicit-null semantics through `before_exists` and `after_exists`.
- Protected values are never persisted in revision payloads, audit changes, or API responses.
- Existing protected values open blank for rotation, preventing accidental replacement with a displayed/default value.
- Added optimistic concurrency and conflict responses for configuration mutations.
- Removed overrides remain discoverable through history browsing.

### 3. Transactional tenant onboarding

- Tenant creation now requires human-readable root organization details.
- Tenant and root OrganizationUnit creation execute in one transaction.
- Uploaded tenant logos are cleaned up when provisioning fails.
- Create/read/update tenant responses now use the same eager-loaded plan and currency shape.
- The UI guides operators through tenant identity, plan, base currency, lifecycle dates, and root organization creation without exposing database identifiers as free-text fields.

### 4. Backend-enforced activation readiness

- Added a reusable readiness evaluator shared by the API and activation service.
- Activation now requires:
  - an active tenant plan;
  - an active base currency;
  - an active root OrganizationUnit with a valid materialized path;
  - an active, verified primary domain; and
  - a valid trial or subscription window.
- The API returns structured checks and actionable guidance rather than a generic failure.
- The platform tenant UI shows every readiness condition, disables premature activation, and provides explicit refresh after related domain or organization changes.
- Subscription-window evaluation is a single clock-based policy shared by activation and runtime tenant resolution.
- Lifecycle update, audit write, and access-revocation event dispatch now execute atomically.

### 5. OrganizationUnit hierarchy integrity

- Removed client-writable `_lft`, `_rgt`, `path`, and `depth` fields.
- `parent_id` is the only hierarchy input; path and depth are backend-derived.
- Added ID-based materialized paths, descendant path/depth updates, cycle prevention, tenant ownership checks, and single-root enforcement in the domain service.
- Parent deletion is restricted and service deletion blocks units that still own children.
- Removed stale nested-set fields from seeders and affected fixtures.
- Added tenant-to-OrganizationUnit integration through an explicit gateway contract rather than direct misplaced model access.

### 6. Frontend navigation and UX

- Added Platform Defaults navigation and routing guarded by its exact permission.
- Added configuration history modal with protected-value redaction and readable actor fallback.
- Updated stale navigation tests to the current consolidated Vehicle Rental workspaces and made hierarchy assertions less brittle.

## Verification evidence

| Check | Result |
|---|---|
| PHP syntax for all changed/new PHP files | Passed: 50 files |
| Changed/new TypeScript ESLint | Passed |
| TypeScript typecheck | Passed |
| Vite production build | Passed: 589 modules |
| Navigation utility tests | Passed: 13/13 |
| Sidebar tests | Passed: 2/2 |
| Platform readiness/authorization manual smoke checks | Passed |
| Platform route registration | Passed: 21 routes |
| Central-host middleware ordering | Passed on all 21 platform routes |
| Stale `_lft`, `_rgt`, legacy global permission, and legacy revision-key scan | Passed |
| `git diff --check` | Passed |
| Unrelated `DecimalMath` source remained unchanged | Confirmed |

## Environment-limited checks

The supplied PHP CLI is PHP 8.4.16 and does not include `dom`, `mbstring`, `xml`, `xmlwriter`, `bcmath`, or a PDO database driver such as `pdo_sqlite`.

Consequently:

- PHPUnit cannot start because required PHPUnit extensions are unavailable.
- Database-backed migration/integration tests cannot run because no PDO database driver is installed.
- Pint cannot run in this container because its required XML/mbstring extensions are absent.
- Route inspection required a temporary verification-only bypass of the `DecimalMath` BCMath constructor guard; the source file was restored and has no diff.

Unit test source was added for tenant readiness and platform-default authorization and passes PHP syntax validation, but execution requires a normal project PHP runtime with the extensions declared by the application.

## Deployment requirements

1. Configure `TENANT_CENTRAL_HOSTS` with the trusted SaaS control-plane hostnames.
2. Ensure authorized operators have `users.is_platform_operator = true` through the trusted provisioning/seeding path.
3. Seed/synchronize the new platform permissions.
4. Run migrations, including `configuration_value_revisions` and the cleaned OrganizationUnit creation migration, in the intended development database lifecycle.
5. Execute the complete PHPUnit and migration suite in an environment containing the required PHP extensions and database driver before production deployment.
