# Core Module

Shared kernel providing infrastructure cross-cutting concerns used by all other modules.

## Components

### Scopes
- `TenantScope` — Global Eloquent scope for automatic tenant isolation

### Traits
- `BelongsToTenant` — Apply to any Eloquent model needing tenant isolation

### Middleware
- `ResolveTenantMiddleware` — Resolves current tenant from JWT/header/subdomain
- `EnforceJsonMiddleware` — Forces `Accept: application/json` on all requests

### Pipeline Pipes
- `ValidateCommandPipe` — Validates command objects before handler execution
- `AuditLogPipe` — Logs command execution for audit trails

### Value Objects
- `Email` — Validated email value object

### Enums
- `Status` — Generic status enum (active/inactive/archived)
