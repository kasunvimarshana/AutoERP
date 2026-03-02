# Tenant Module

Manages multi-tenant provisioning and tenant resolution.

## Architecture

Follows the **Controller → Service → Handler (with Pipeline) → Repository → Entity** pattern.

- **Domain Layer**: `Tenant` entity, `TenantRepositoryInterface`, `TenantStatus` enum
- **Application Layer**: `CreateTenantCommand`, `CreateTenantHandler` (Pipeline: ValidateCommandPipe → AuditLogPipe), `TenantService`
- **Infrastructure Layer**: `TenantModel` (Eloquent), `TenantRepository`
- **Interface Layer**: `TenantController` (injects `TenantService` only), `CreateTenantRequest`, `TenantResource`

## API Endpoints

| Method | URI | Description |
|--------|-----|-------------|
| GET | `/api/v1/tenants` | List all tenants |
| POST | `/api/v1/tenants` | Create a new tenant |
| GET | `/api/v1/tenants/{id}` | Get a specific tenant |

## Currency

- Default currency: **LKR** (Sri Lankan Rupee)
- Configurable via `DEFAULT_CURRENCY` env variable or `config/currency.php`
- Per-tenant currency override supported; validated against `config('currency.supported')` list
- Currency exposed in `TenantResource` API response

## Tenant Resolution

Tenants are resolved via middleware from:
1. JWT `tenant_id` claim
2. `X-Tenant-ID` request header
3. Subdomain extraction
