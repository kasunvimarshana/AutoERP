# Seeder configuration cache hardening

Date: 2026-07-12

## Problem

`TenantSeeder` and `PlatformOperatorSeeder` read deployment values with direct `env()` calls. Laravel environment reads outside configuration are not a reliable runtime source after configuration caching, so seeded tenant identity and optional platform-operator provisioning could diverge between uncached local execution and cached deployment execution.

## Root cause

Environment-specific values were owned by the seeders instead of the Tenant and User module configuration sources.

## Correction

- Added Tenant-owned seeding configuration for the initial tenant code and name.
- Added User-owned seeding configuration for optional platform-operator enablement, email, and password.
- Updated both seeders to read their owning module configuration through `config()`.
- Kept fixed seeder identifiers and initial-state text behind descriptive class constants.
- Added a Tenant behavioral test that runs the seeder with overridden module configuration and verifies the persisted tenant values.
- Added a User regression test that verifies the platform-operator seeder resolves its enablement and credentials from module configuration.

## Scope

This change is intentionally limited to the confirmed configuration-cache defect. It does not alter tenant onboarding, platform permissions, credential hashing, database schema, or unrelated business workflows.

## Verification

- Re-fetched every modified file from `worktree-0.0.8` and confirmed the complete final contents.
- PHP syntax validation passed for both modified seeders and both new tests.
- The closure binding used by the User regression test was executed independently and confirmed to access the seeder's private configuration readers correctly.
- Repository comparison confirmed only the Tenant/User configuration, seeders, tests, and this append-only change record are in scope.

A full Laravel suite was not executed in the connector-only environment because a local repository checkout and installed dependencies were unavailable. The added regression tests must be included in the next normal project verification run.