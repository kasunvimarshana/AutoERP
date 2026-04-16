# Tenant Config Update Semantics

**Date:** April 16, 2026
**Scope:** `PATCH /tenants/{id}/config` flow

## Purpose

Document the expected behavior for partial tenant configuration updates so future refactors preserve current API semantics.

## Rules

1. Payload is request-first and validated in `UpdateTenantConfigRequest`.
2. `UpdateTenantConfigService` whitelists supported config keys before domain mutation.
3. Domain updates are applied only through `Tenant::updateConfig()`.

## Key Presence Semantics

- If a key is omitted: keep existing value.
- If a key is present with `null`: clear value for nullable optional config blocks.
- If a key is present with a valid object/array: replace with provided value.

This behavior is required for these optional blocks:

- `mail_config`
- `cache_config`
- `queue_config`
- `settings`

## Non-null Config Block

- `database_config` is validated as an object when provided.
- Omission preserves current database configuration.
- Clearing `database_config` with `null` is not supported.

## Related Files

- `app/Modules/Tenant/Infrastructure/Http/Requests/UpdateTenantConfigRequest.php`
- `app/Modules/Tenant/Application/Services/UpdateTenantConfigService.php`
- `app/Modules/Tenant/Domain/Entities/Tenant.php`
- `app/Modules/Tenant/Infrastructure/Http/Resources/TenantConfigResource.php`
