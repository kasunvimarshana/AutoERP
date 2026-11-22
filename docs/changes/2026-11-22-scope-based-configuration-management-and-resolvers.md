# Scope-Based Configuration Management CRUD & Dynamic Resolvers Implementation

## Overview
Implemented comprehensive configuration management CRUD functionality and dynamic runtime resolvers. System configurations—including default email settings (`mail.*`), organizational branding (`branding.*`), localization (`localization.*`), and system preferences (`system.*`)—can now be independently created, viewed, updated, deleted, and resolved at tenant, organization-unit, and global scopes with automatic hierarchical inheritance, fallback resolution, optimistic locking, and tenant boundary enforcement.

## Changes

### 1. Canonical Configuration Constants & Definitions
- **`app/Modules/Configuration/Constants/ConfigurationKey.php`**: Added canonical constants for all system configurations:
  - Branding: `BRANDING_DISPLAY_NAME`, `BRANDING_COMPANY_NAME`, `BRANDING_SUPPORT_EMAIL`, `BRANDING_SUPPORT_PHONE`
  - Localization: `LOCALIZATION_TIMEZONE`, `LOCALIZATION_DATE_FORMAT`, `LOCALIZATION_TIME_FORMAT`, `LOCALIZATION_CURRENCY`
  - Mail: `MAIL_MAILER`, `MAIL_HOST`, `MAIL_PORT`, `MAIL_USERNAME`, `MAIL_PASSWORD`, `MAIL_ENCRYPTION`, `MAIL_FROM_ADDRESS`, `MAIL_FROM_NAME`
  - System Preferences: `SYSTEM_SESSION_TIMEOUT_MINUTES`, `SYSTEM_PASSWORD_EXPIRY_DAYS`, `SYSTEM_MAINTENANCE_NOTICE`
- **`app/Modules/Configuration/Config/configuration.php`**: Registered 19 comprehensive configuration definitions across global, tenant, and organization-unit scopes with validation rules, scalar options, boundaries, sensitivity markings, and hierarchy inheritance flags.

### 2. Domain Data Transfer Objects (DTOs) & Contracts
- **`app/Modules/Configuration/Data/MailConfiguration.php`**: Immutable DTO representing fully resolved mailer settings with `toMailerConfig()` array generation for Laravel mail transports.
- **`app/Modules/Configuration/Data/SystemPreferences.php`**: Immutable DTO representing resolved timezone, date/time formats, currency, session timeouts, and maintenance notices.
- **`app/Modules/Configuration/Contracts/MailConfigurationResolverInterface.php`**: Contract defining `resolve(int $tenantId, ?int $organizationUnitId = null): MailConfiguration` and `configureEnvironment(int $tenantId, ?int $organizationUnitId = null): MailConfiguration`.
- **`app/Modules/Configuration/Contracts/SystemPreferencesResolverInterface.php`**: Contract defining `resolve(int $tenantId, ?int $organizationUnitId = null): SystemPreferences`.

### 3. Resolver Services
- **`app/Modules/Configuration/Services/MailConfigurationResolver.php`**: Implements mail configuration resolution and runtime environment configuration for Laravel mailer drivers.
- **`app/Modules/Configuration/Services/SystemPreferencesResolver.php`**: Implements system preference resolution across tenant and organization hierarchies.
- **`app/Modules/Configuration/Providers/ConfigurationServiceProvider.php`**: Registered singletons and scoped service bindings for all contracts and services.

### 4. API Endpoints & Route Hardening
- **`app/Modules/Configuration/Routes/api.php`**:
  - Added `GET /api/v1/configuration/resolved` (`resolved.index`) to return all resolved settings for the current context.
  - Added `GET /api/v1/platform/configuration/tenants/{tenant}/resolved` (`platform.resolved.index`) for platform operator visibility.
  - Hardened update routes to accept both `PUT` and `PATCH` methods.
- **`app/Modules/Configuration/Http/Controllers/ConfigurationController.php`** & **`PlatformConfigurationController.php`**:
  - Implemented `resolvedIndex()` endpoints with search and owner filters.
  - Enabled `?scope=tenant` query parameter support in `ConfigurationController::resolved` to allow tenant-scoped resolution even when authenticated within an organization unit.
- **`app/Modules/Configuration/Services/ConfigurationEntryService.php`**:
  - Added `resolvedAllCurrent()` and `resolvedAllPlatform()` batch resolution services.

### 5. Architectural Tenant Isolation Compliance
- **`app/Modules/Configuration/Repositories/EloquentConfigurationValueRepository.php`**:
  - Injected `TenantExecutionContextInterface`.
  - Removed all `withoutGlobalScope(TenantScope::class)` calls.
  - Enforced tenant boundary execution via `$this->executionContext->runForTenant(...)` and `$this->executionContext->runAsControlPlane(...)`.
- **`app/Modules/OrganizationUnit/Services/OrganizationUnitOwnershipChecker.php`**:
  - Injected `TenantExecutionContextInterface`.
  - Wrapped ownership existence checks in `runForTenant($tenantId, ...)` to eliminate `withoutGlobalScope(TenantScope::class)`.

### 6. Frontend API Integration
- **`resources/js/modules/settings/settingsApi.ts`**: Added `listResolvedConfigurations(params, signal)` method.

### 7. Verification & Tests
- **`tests/Feature/Configuration/ConfigurationManagementCrudTest.php`**:
  - Created comprehensive feature test suite covering:
    - Full tenant CRUD lifecycle with optimistic locking (`expected_version` / `row_version`) and HTTP 409 Conflict handling.
    - Organization-unit scope override, inheritance, and immediate fallback to tenant/default on deletion.
    - Batch resolution endpoint `GET /api/v1/configuration/resolved` with query filtering.
    - `MailConfigurationResolverInterface` domain resolution and mailer configuration export.
    - `SystemPreferencesResolverInterface` domain resolution across scopes.
    - Sensitive setting masking (HTTP responses redact passwords) and permission step-up enforcement (`configuration.entries.manage.sensitive`).
- **`tests/Feature/Configuration/ConfigurationPermissionBoundaryTest.php`**: Updated with route expectations.
- **`tests/Support/TenantAuthenticationFixture.php`**: Prevented duplicate internal auth provider insert collisions during multi-user tenant testing.

## Verification Results
- `php artisan test tests/Feature/Configuration/ConfigurationManagementCrudTest.php`: 6 passed (113 assertions).
- `php artisan test app/Modules/Configuration/Tests tests/Feature/Configuration`: 20 passed (188 assertions).
- `php artisan test tests/Unit/Database/TenantIsolationArchitectureTest.php`: 8 passed (22 assertions), 0 global scope escape hatch violations.
- `npm run typecheck`: 0 errors.
- `npm test -- --run`: 174 test files passed, 727 tests passed.
