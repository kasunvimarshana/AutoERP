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

## Status

🔴 **Planned** — See [IMPLEMENTATION_STATUS.md](../../IMPLEMENTATION_STATUS.md)
