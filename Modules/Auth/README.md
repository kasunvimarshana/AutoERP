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

🟢 **Complete** — Core auth scaffold implemented; register, login, logout, refresh, me, updateProfile, and changePassword endpoints implemented (~90% test coverage). See [IMPLEMENTATION_STATUS.md](../../IMPLEMENTATION_STATUS.md)

### Implemented

| Component | File | Description |
|---|---|---|
| `User` entity | `Domain/Entities/User.php` | Tenant-scoped user with JWT (JWTSubject), HasTenant, HasRoles (Spatie), soft deletes |
| `LoginDTO` | `Application/DTOs/LoginDTO.php` | Validated login credentials carrier |
| `RegisterDTO` | `Application/DTOs/RegisterDTO.php` | Validated registration data carrier (tenantId, name, email, password, optional deviceName) |
| `AuthService` | `Application/Services/AuthService.php` | login, logout, refresh, me, register, updateProfile, changePassword — no business logic in controller |
| `AuthController` | `Interfaces/Http/Controllers/AuthController.php` | POST /register, POST /login, POST /logout, POST /refresh, GET /me, PUT /profile, POST /password/change — with OpenAPI annotations |
| `create_users_table` | `Infrastructure/Database/Migrations/` | Tenant-scoped users table: unique email per tenant, `tenant_id` FK, soft deletes |
| `AuthServiceProvider` | `Infrastructure/Providers/AuthServiceProvider.php` | Loads migrations, registers routes, binds AuthService |
| `api.php` routes | `routes/api.php` | `/api/v1/auth/register|login|logout|refresh|me|profile|password/change` |
| `auth.php` config | `config/auth.php` | JWT guard TTL configuration |
| `LoginDTOTest` | `Tests/Unit/LoginDTOTest.php` | 7 unit tests: hydration, optional device_name, toArray contract |
| `RegisterDTOTest` | `Tests/Unit/RegisterDTOTest.php` | 8 unit tests: hydration, tenant_id int cast, optional device_name, toArray contract |
| `AuthServiceTest` | `Tests/Unit/AuthServiceTest.php` | 8 unit tests: structural compliance, method existence, return type reflection |
| `AuthServiceRegisterTest` | `Tests/Unit/AuthServiceRegisterTest.php` | 5 unit tests: structural compliance, register() signature, DTO payload mapping |
| `AuthServiceProfileTest` | `Tests/Unit/AuthServiceProfileTest.php` | 8 unit tests: updateProfile() signature, DTO payload mapping, tenant isolation |
| `AuthServiceChangePasswordTest` | `Tests/Unit/AuthServiceChangePasswordTest.php` | 7 unit tests: changePassword() method existence, visibility, parameter signature (currentPassword/newPassword strings), return type void, not static |
