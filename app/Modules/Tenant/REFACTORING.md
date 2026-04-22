# Tenant Module Refactoring Status

**Date:** April 16, 2026
**Status:** Complete and aligned with current code

## Overview

Tenant module refactoring is now service-centric, with request validation and persistence contracts aligned to snake_case transport keys and explicit domain mapping.

## Architecture Outcome

- Application orchestration is under `Application/Services`
- Redundant, unused `Application/UseCases` classes were removed
- Read/list flow is routed via `FindTenantServiceInterface`
- Config updates use request-first validated payloads and domain mutation in `Tenant::updateConfig()`

## Key Functional Improvements

1. Tenant listing and read contract consistency
- Added `list(...)` to find service contract/implementation so controller index flow is contract-safe

2. DTO and request consistency
- Tenant DTO transport keys standardized to snake_case
- Create/update requests aligned with DTO constraints and date formats

3. Missing tenant field wiring completed
- End-to-end support for:
    - `settings`
    - `plan`
    - `tenant_plan_id`
    - `status`
    - `trial_ends_at`
    - `subscription_ends_at`
- Fields are mapped in domain entity, services, repository persistence/hydration, model casts/fillable, and resources

4. Config update semantics hardened
- Explicit `null` values can clear optional config blocks (`mail_config`, `cache_config`, `queue_config`, `settings`)
- Omitted keys preserve existing values

5. Duplication reduction
- Introduced `TenantConfigValueObjectFactory` to remove repeated value-object construction logic across services

## Migration and Schema Updates

- `2024_01_01_000001_create_tenants_table.php`
    - Retains plan/status/subscription fields and indexes
    - Defers tenant plan FK creation for correct migration order

- `2024_01_01_100001_create_tenant_plans_table.php`
    - Removed redundant standalone slug index where unique already provides indexing

- `2024_01_01_100002_add_tenant_plan_foreign_key_to_tenants_table.php`
    - Adds tenant plan foreign key after plans table exists

- `2024_01_01_000002_create_tenant_attachments_table.php`
    - Removed redundant standalone uuid index because unique key already indexes it

## Notes on Documentation Alignment

- `TenantConfigData` is intentionally removed as dead abstraction for config updates
- Config update flow is now request-first with a whitelisted payload in service layer
- Config PATCH contract details are documented in `CONFIG_UPDATE_SEMANTICS.md`

## Validation Status

- Modified Tenant files were repeatedly checked with `php -l` and are syntax-clean
- Editor-reported framework symbol warnings are environment/indexer related and not new syntax regressions

## Current State

The Tenant module is now materially cleaner with better contract alignment, clearer update semantics, reduced duplication, and corrected migration ordering.

For config behavior details, see `app/Modules/Tenant/CONFIG_UPDATE_SEMANTICS.md`.
