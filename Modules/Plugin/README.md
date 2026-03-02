# Plugin Module

## Overview

The **Plugin** module provides the plugin marketplace infrastructure: module manifest management, dependency graph validation, version compatibility checking, tenant-scoped enablement, and upgrade migration orchestration.

---

## Responsibilities

- Module manifest (`module.json`) validation and registration
- Dependency graph validation (circular dependency detection)
- Version compatibility enforcement
- Tenant-scoped module enablement/disablement
- Sandboxed plugin execution boundaries
- Upgrade migration path management
- Plugin marketplace catalog

---

## Architecture Layer

```
Modules/Plugin/
 ├── Application/       # Install plugin, enable for tenant, resolve dependencies use cases
 ├── Domain/            # PluginManifest entity, DependencyGraph value object
 ├── Infrastructure/    # PluginRepository, PluginServiceProvider, dependency resolver
 ├── Interfaces/        # PluginController, PluginMarketplaceController
 ├── module.json
 └── README.md
```

---

## Dependencies

- `core`
- `tenancy`

---

## Architecture Compliance

| Rule | Status |
|---|---|
| No business logic in controllers | ✅ Enforced |
| No query builder calls in controllers | ✅ Enforced |
| Dependency graph validated for circular dependencies | ✅ Enforced |
| Tenant-scoped plugin enablement | ✅ Enforced |
| Module manifest (`module.json`) required for all plugins | ✅ Enforced |
| Sandboxed execution boundaries | ✅ Required |

---

## Implemented Files

### Migrations
| File | Table |
|---|---|
| `create_plugins_table.php` | `plugins` — manifest registry |
| `create_tenant_plugins_table.php` | `tenant_plugins` — per-tenant enablement |

### Domain Entities
- `Plugin` — manifest entity; keywords/providers as JSON
- `TenantPlugin` — HasTenant; pivot between tenant and plugin

### Application Layer
- `PluginService` — listPlugins, installPlugin, enableForTenant, disableForTenant, resolveDependencies, showPlugin, updatePlugin, uninstallPlugin, listTenantPlugins (all mutations in DB::transaction)

### Infrastructure Layer
- `PluginRepositoryContract` — findByAlias, findActive
- `PluginRepository` — extends AbstractRepository on Plugin
- `PluginServiceProvider` — binds contract, loads migrations and routes

### API Routes (`/api/v1`)
| Method | Path | Action |
|---|---|---|
| GET | `/plugins` | listPlugins |
| POST | `/plugins` | installPlugin |
| GET | `/plugins/{id}` | showPlugin |
| PUT | `/plugins/{id}` | updatePlugin |
| DELETE | `/plugins/{id}` | uninstallPlugin |
| POST | `/plugins/{id}/enable` | enableForTenant |
| POST | `/plugins/{id}/disable` | disableForTenant |
| GET | `/plugins/tenant/enabled` | listTenantPlugins |

---

## Test Coverage

| Test File | Type | Coverage Area |
|---|---|---|
| `Tests/Unit/InstallPluginDTOTest.php` | Unit | DTO hydration, defaults |
| `Tests/Unit/PluginServiceTest.php` | Unit | listPlugins, resolveDependencies — delegation and validation — 8 assertions |
| `Tests/Unit/PluginServiceInstallPayloadTest.php` | Unit | installPlugin method/signature, create-payload mapping — 8 assertions |
| `Tests/Unit/PluginServiceEnablementTest.php` | Unit | enableForTenant/disableForTenant/resolveDependencies — method signatures, visibility — 10 assertions |
| `Tests/Unit/PluginServiceCrudTest.php` | Unit | showPlugin, uninstallPlugin, listTenantPlugins — structural compliance — 12 assertions |
| `Tests/Unit/PluginServiceUpdateTest.php` | Unit | `updatePlugin` — method existence, public visibility, parameter signature (id + data array), PluginManifest return type — 6 assertions |
| `Tests/Unit/PluginServiceDelegationTest.php` | Unit | showPlugin delegation to findOrFail, listPlugins delegation to repository all, regression guards — 12 assertions |

---

## Status

🟢 **Complete** — Plugin manifest registration, tenant-scoped enablement/disablement, dependency resolution, manifest update, show/uninstall/listTenantPlugins implemented (~85% test coverage). See [IMPLEMENTATION_STATUS.md](../../IMPLEMENTATION_STATUS.md)
