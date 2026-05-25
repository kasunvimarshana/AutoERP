# Tenant Module

Tenant module provides production-ready multi-tenant management based on the six-table
tenant schema and Core-driven contracts.

## Migration Source of Truth

Tenant aggregate boundaries are defined by these migrations:

- tenants
- tenant_plans
- tenant_setting_groups
- tenant_settings
- tenant_documents
- tenant_domains

## Capabilities

- Tenant lifecycle management (create, update, activate, suspend, deactivate)
- Tenant isolation key and configuration scope handling
- Tenant plan catalog management
- Tenant setting group hierarchy and settings management
- Tenant document metadata and file storage integration
- Tenant domain management with primary-domain consistency

## Architecture

- Domain: entities and invariant helpers for all six tenant tables
- Application: contract-driven services and use-cases
- Infrastructure: Eloquent models/repositories per table and provider bindings
- Presentation: REST controllers, validation requests, resources, and tenant CLI commands

## API Surface

Prefix: api/tenant

- tenants (+ activate, suspend, deactivate actions)
- plans
- setting-groups
- settings
- documents
- domains
