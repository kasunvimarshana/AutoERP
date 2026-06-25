# Tenant foundation correction

Date: 2026-06-26
Scope: `app/Modules/Tenant` and directly owned trust-boundary contracts in Auth, Configuration, OrganizationUnit, User, Reporting, queue middleware, migrations, and Platform Administration UI.

## Why

The Tenant foundation could persist contradictory onboarding state, validate a different root organization than the stored pointer, collapse subscription/schema failures into generic server errors, apply production-only domain readiness to local development, and use ambiguous MFA semantics. Tenant-owned history and queued work also had inconsistent isolation guarantees. Configuration and infrastructure capabilities were not clearly separated, and hard tenant deletion could cascade into retained operational history.

## Changes

- Added a transactional tenant foundation completion policy that validates all required steps and exact resource pointers before entering `awaiting_administrator`.
- Reconciled onboarding against the protected root organization and exact Super Admin role; fixed the stale frontend root-step key and the access provisioner role lookup defect.
- Separated invitation issuance, acceptance, and operational-administrator readiness.
- Split platform MFA capability, enrollment, login challenge, and step-up policies into explicit settings and one policy service.
- Added safe subscription query/mutation errors, correlation IDs, schema compatibility checks, and readiness blockers for malformed persisted subscription data.
- Separated production verified-domain readiness from explicit local/testing tenant-code fallback without persisting fake localhost domains.
- Kept tenant business settings as typed, namespaced, consumed definitions; exposed the deliberate shared-schema/private-disk/platform-mail infrastructure capabilities instead of allowing arbitrary Laravel configuration overrides.
- Replaced configuration revision user FKs with immutable actor snapshots that correctly represent system, platform-operator, and tenant-user actors.
- Tenant-scoped lifecycle, subscription-event, and configuration-revision history models; enforced tenant context restoration for every tenant-aware queue job.
- Unified tenant branding assets with private object-key storage and removed raw logo path persistence.
- Changed all direct tenant foreign keys in the fresh migration baseline to restrict hard deletion; Tenant lifecycle remains archive-first.
- Removed duplicate legacy tenant-admin/domain seed paths and delegated root provisioning to the owning OrganizationUnit provisioner.
- Added/updated source tests for MFA policy, tenant routing/infrastructure capabilities, access provisioning, schema compatibility, tenant isolation, queue context, and migration architecture.

## Verification performed

- Changed/new PHP syntax validation: passed.
- Changed/new TypeScript/TSX syntax parsing: passed.
- Internal `Modules\\...` import/symbol scan: passed.
- Migration baseline scan: 242 migrations, 242 unique tables, one table per file, no patch migrations, no duplicate timestamps, no early explicit FK targets, no raw driver-specific branches.
- Direct `tenant_id` foreign keys: all use `RESTRICT` for hard tenant deletion.
- Tenant-owned Tenant module models: fail-closed scope foundation verified.
- Tenant-aware jobs: all restore tenant context through queue middleware.
- Deleted legacy symbol/reference scan and `git diff --check`: passed.

## Runtime release gates

The uploaded snapshot does not include `vendor/` or `node_modules/`. Laravel boot, `migrate:fresh`, route enumeration, PHPUnit, database concurrency, queue/mail/DNS/TLS integration, semantic TypeScript checking, ESLint, Vitest, Vite build, and browser/API Tenant-A/Tenant-B adversarial tests were not executable in this environment. Run them against a fresh reviewed development database before release. Do not apply corrected create migrations blindly to an existing production schema; use an explicit reviewed data-migration plan.
