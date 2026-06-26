# Tenant and Platform management correction

Date: 2026-06-26
Scope: Tenant subscription lifecycle, tenant setup workflow, plan assignment management, platform error observability, session recovery, ReferenceData currency ownership, and directly related Platform Administration UI.

## Why

Tenant plan assignment existed only inside the selected-tenant subscription step and was difficult to discover or audit from the plan catalogue. Subscription commands allowed contradictory trial input, exposed an invalid generic trial extension, and could replace an effective subscription with a future-dated revision. Archived-tenant read-only behavior was not enforced by the backend subscription owner. Tenant setup panels loaded eagerly, permission restrictions were silent, history was truncated, generic server errors lacked a support reference, and the authentication recovery screen could trap users behind a retry-only flow.

## Changes

- Rebuilt subscription command input around one explicit legal-state contract.
- Rejected future subscription starts until a real scheduled-activation model exists, preventing premature current-pointer replacement.
- Made trial and active periods mutually exclusive and removed generic trial extension; trial end changes use the explicit correction workflow.
- Enforced archived-tenant read-only rules inside the Tenant subscription mutation service.
- Separated lifecycle input validation and tenant mutation policy from transaction orchestration.
- Allowed plan deactivation to stop new assignments while preserving immutable existing subscriptions.
- Added a subscription-owned plan lookup so subscription managers do not require broad plan-management access.
- Added paginated plan-to-tenant assignment reads and plan-catalogue actions for assigning and reviewing tenants.
- Added effective subscription status and effective-expiry filtering, including trial expiry, and separated assigned-pointer, effective, and historical plan metrics.
- Split current-subscription and historical-revision frontend contracts and added paginated revision history.
- Lazy-loaded the active tenant setup step and added explicit completed, blocked, and action-required guidance.
- Required all five foundation steps before dependent domain work is unlocked.
- Added read-only permission guidance rather than silently removing management actions.
- Added a shared inline field-action layout for onboarding and domain forms.
- Added request correlation IDs to API errors and exposed error codes, guidance, and support references in the UI.
- Added retry, sign-out, and return-to-login recovery actions for temporary session bootstrap failures and stable terminal-session classification.
- Guarded direct tenant administration routes and applied numeric route constraints consistently.
- Moved currency directory ownership to ReferenceData and removed the Tenant/ReferenceData concrete dependency cycle.
- Split oversized tenant page and subscription responsibilities into cohesive presentation, history, directory, setup-navigation, command-input, and policy units.
- Removed per-request schema introspection and the cross-capability schema compatibility service.

## Verification performed

- Full PHP syntax validation: 2,562 files passed.
- TypeScript semantic typecheck: passed with zero diagnostics.
- ESLint: passed with zero errors and zero warnings.
- Vitest: 91 files and 157 tests passed.
- Vite production build: passed; 653 modules transformed.
- Internal PHP imports: 8,060 scanned, zero missing symbols.
- Frontend relative imports: 1,276 scanned, zero missing imports.
- Route/controller actions: 641 scanned, zero mismatches.
- Tenant numeric route constraints: passed.
- Migration baseline: unchanged from the uploaded source.
- Stale compatibility symbols: zero remaining references.
- Source and artifact ZIP integrity: verified during packaging.

## Runtime release gates

The uploaded snapshot does not include Composer `vendor/` or a migrated service/database environment. Laravel boot, `artisan route:list`, PHPUnit, real database transactions/concurrency, queue/mail delivery, DNS/TLS integration, and browser/API end-to-end execution were therefore not run here. The frontend semantic suite and production build were executed successfully. Run the backend runtime suite against a fresh reviewed development database before release.
