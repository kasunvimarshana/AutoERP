# Tenant module

The Tenant module owns tenant identity, lifecycle, plans, documents, and domains. Runtime settings are not stored in this module; they are owned by `Modules/Configuration`.

## Migration source of truth

- `tenant_plans`
- `tenants`
- `tenant_documents`
- `tenant_domains`

## Capabilities

- Tenant lifecycle management
- Tenant plan catalog management
- Tenant document metadata and file storage integration
- Tenant domain management with primary-domain consistency
- Base-currency relationship to the ReferenceData module

Tenant-scoped configuration overrides are stored in `tenant_configuration_values` and accessed only through `ConfigurationResolverInterface`.
