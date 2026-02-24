# Tenant Module
Manages multi-tenant isolation, organisation hierarchy, and tenant lifecycle.
## Features
- Tenant CRUD and lifecycle (active, suspended, archived)
- Per-request tenant resolution via header, subdomain, or domain
## Routes
- GET/POST /api/v1/tenants
- GET/PUT /api/v1/tenants/{id}
- POST /api/v1/tenants/{id}/suspend
