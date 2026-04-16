# Tenant Module DTOs Refactoring Summary

**Date:** April 16, 2026  
**Status:** ✅ Complete (Updated)

## Overview

Tenant DTOs were standardized to match the module's request and persistence payload style.

Current convention is **snake_case DTO properties** so that the same keys are used across:

- HTTP request validation payloads
- DTO instances
- Domain `updateConfig()` data contract
- Repository persistence arrays

This avoids repetitive key translation and prevents update bugs caused by mixed naming styles.

---

## Current DTO Design

### TenantData

Key points:

- Includes required identity fields: `name`, `slug`
- Supports optional operational fields: `domain`, `logo_path`, `settings`
- Supports plan/subscription metadata: `plan`, `tenant_plan_id`, `status`, `trial_ends_at`, `subscription_ends_at`
- Uses nested snake_case config keys: `database_config`, `mail_config`, `cache_config`, `queue_config`, `feature_flags`, `api_keys`
- Exposes `toPersistenceArray()` for explicit repository payload shaping

### Configuration Update Flow

Key points:

- Config updates now use a **request-first validated payload** in `UpdateTenantConfigRequest`
- `UpdateTenantConfigService` receives the validated array directly and whitelists supported config keys
- Explicit `null` values are preserved for clear-value semantics on optional blocks (`mail_config`, `cache_config`, `queue_config`, `settings`)
- Domain mutation remains centralized in `Tenant::updateConfig()`
- Canonical semantics are documented in `app/Modules/Tenant/CONFIG_UPDATE_SEMANTICS.md`

### TenantAttachmentData

Attachment DTO conventions remain aligned to tenant payload style and validation-first transport.

---

## Validation Strategy

Validation is explicit and transport-oriented:

- strict field typing
- enum-style restrictions for known driver/status values
- bounded ranges for ports and numeric fields
- uniqueness checks for tenant identity fields (`slug`, `domain`) on create/update paths

---

## Naming Convention Decision

Final decision in this codebase:

- DTO properties: **snake_case**
- Domain entity API: **camelCase methods** (for example `getDatabaseConfig()`)
- Repository persistence payloads: **snake_case**

This split keeps domain objects idiomatic while preserving transport/persistence key consistency.

---

## Architecture Notes

- Tenant module orchestration is now service-centric (`Application/Services`)
- Legacy duplicated `Application/UseCases` classes were removed to reduce maintenance overhead

---

## Affected Files

- `app/Modules/Tenant/Application/DTOs/TenantData.php`
- `app/Modules/Tenant/Application/DTOs/TenantAttachmentData.php`
- `app/Modules/Tenant/Infrastructure/Http/Requests/UpdateTenantConfigRequest.php`
- `app/Modules/Tenant/Application/Services/UpdateTenantConfigService.php`

---

## Outcome

Refactored DTOs now provide:

- consistent key naming end-to-end
- safer partial updates
- clearer contracts between HTTP, application, domain, and persistence layers
- lower cognitive overhead for future module changes
