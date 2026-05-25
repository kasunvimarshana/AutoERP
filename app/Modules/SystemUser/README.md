# SystemUser Module

SystemUser module provides tenant-scoped system user management derived from the system_users migration schema.

## Migration Source of Truth

SystemUser aggregate boundary is defined by:

- system_users

## Capabilities

- System user lifecycle management (create, update, list, get, delete)
- Tenant and optional organization-unit scoped ownership
- Optional linkage to portal user account
- Status and notes management

## Architecture

- Domain: system user entity and normalization rules
- Application: contract-driven CRUD use-cases and repository port
- Infrastructure: Eloquent model/repository and provider bindings
- Presentation: REST controller, validation requests, and resources

## API Surface

Prefix: api/system-user

- system-users
