# Auth Module

## Overview

The **Auth** module provides stateless JWT-based authentication with multi-guard support, role-based access control (RBAC), attribute-based access control (ABAC) via Laravel Policies, and tenant-scoped API key management.

---

## Responsibilities

- JWT token issuance, refresh, and rotation per user × device × organisation
- Multi-guard authentication (web, api, tenant-api)
- Role and permission management (RBAC via Spatie Laravel Permission)
- Policy classes for ABAC (no hardcoded role checks in controllers)
- Tenant-level feature flags
- Feature-level gating
- Scoped API key management
- Suspicious activity detection
- Rate limiting per tenant/user

---

## Authorization Rules

- **Policy classes only** — no permission logic in controllers
- No hardcoded role checks anywhere in the codebase
- All policies are tenant-scoped

---

## Architecture Compliance

| Rule | Status |
|---|---|
| No business logic in controllers | ✅ Enforced |
| No query builder calls in controllers | ✅ Enforced |
| Policy classes only (no hardcoded role checks) | ✅ Enforced |
| JWT per user × device × organisation | ✅ Required |
| Tenant-scoped permissions | ✅ Enforced |
| No cross-module coupling | ✅ Enforced |

---

## Architecture Layer

```
Modules/Auth/
 ├── Application/       # Login/logout/refresh use cases, API key issuance
 ├── Domain/            # User entity, Role/Permission value objects, AuthRepository contract
 ├── Infrastructure/    # AuthServiceProvider, JWT guards, AuthRepository implementation
 ├── Interfaces/        # AuthController, LoginRequest, TokenResource
 ├── module.json
 └── README.md
```

---

## Dependencies

- `core`
- `tenancy`

---

## Status

🔴 **Planned** — See [IMPLEMENTATION_STATUS.md](../../IMPLEMENTATION_STATUS.md)
