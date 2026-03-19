# Copilot Instructions for KV_SSO

## Project Overview

**KV_SSO** is a production-ready, enterprise-grade **Single Sign-On (SSO)** and distributed authentication/authorization system designed for a multi-tenant ERP/CRM SaaS platform. It is the **Auth Service** microservice within a broader ecosystem of loosely coupled services (Product, Inventory, Warehouse, User, Order, Finance, CRM, Procurement, Workflow, Reporting, Configuration).

The platform is primarily built with **Laravel (LTS)** and follows a microservices architecture where each service communicates only through **versioned REST APIs (`/api/v1`)**, **gRPC**, or **asynchronous messaging (Kafka / RabbitMQ)**. Direct database access between services is strictly prohibited.

---

## Architecture & Engineering Principles

- **Domain-Driven Design (DDD)** with bounded contexts per microservice.
- **Clean Architecture**: Controller → Service → Repository pipeline; thin controllers only.
- **SOLID, DRY, KISS** principles enforced throughout.
- **API-first design**: Every feature is exposed via a versioned REST endpoint with an OpenAPI 3.1 contract before implementation.
- **Modular, plugin-style architecture**: Strict module boundaries, no circular dependencies.
- Use **Request classes** for input validation, **Resource classes** for API response shaping, and **Interfaces/Contracts** for dependency inversion.

---

## Multi-Tenancy Model

The platform enforces a strict hierarchical tenant isolation model:

```
Tenant → Organisation → Branch → Location → Department
```

- All database queries, cache keys, queues, storage paths, and configurations **must be tenant-scoped**.
- JWT tokens carry contextual claims: `user_id`, `tenant_id`, `organization_id`, `branch_id`, `roles`, `permissions`, `device_id`, `token_version`, `issuer`, `exp`.
- **Never** allow cross-tenant data leakage.

---

## Authentication & Authorization

- **Auth Service** uses **Laravel Passport** to issue stateless **JWT tokens** (asymmetric RS256 signing).
- All other microservices verify tokens **locally** using the Auth Service's **public key** — no round-trip to Auth Service per request.
- Support **SSO**, **multi-guard authentication** (per user/device/organization), **token rotation**, **revocation via Redis distributed revocation lists**, and **multi-device session management**.
- Authorization combines **RBAC + ABAC** enforced through **Laravel Policies and Gates**.
- Tokens are **short-lived** (access tokens ≤15 min); refresh tokens are revocable and stored securely.

### Security Requirements (always enforce)
- Password hashing: **Argon2id** (preferred) or bcrypt.
- CSRF protection on all stateful endpoints.
- Rate limiting on all authentication endpoints.
- Immutable, append-only **audit logs** for all auth events.
- Replay protection via `jti` (JWT ID) stored in Redis.
- Suspicious activity detection (brute-force, anomalous IP/device).

---

## Coding Conventions

### PHP / Laravel
- **PHP 8.2+** with strict types (`declare(strict_types=1);`) in every file.
- Follow **PSR-12** coding standard.
- All classes must be **final** unless explicitly designed for extension.
- Use **constructor property promotion** where applicable.
- Use **readonly** properties for value objects and DTOs.
- No raw SQL — use Eloquent ORM or Query Builder with parameterized bindings only.
- All public methods on Service and Repository classes must have return types declared.

### Naming Conventions
| Layer | Convention | Example |
|---|---|---|
| Controllers | `{Entity}Controller` | `AuthController` |
| Form Requests | `{Action}{Entity}Request` | `LoginUserRequest` |
| Resources | `{Entity}Resource` | `UserResource` |
| Services | `{Entity}Service` | `TokenService` |
| Repositories | `{Entity}Repository` | `UserRepository` |
| Interfaces | `{Entity}RepositoryInterface` | `UserRepositoryInterface` |
| Events | `{Entity}{Action}Event` | `UserLoggedInEvent` |
| Listeners | `{Action}{Entity}Listener` | `LogAuthEventListener` |
| Jobs | `{Action}{Entity}Job` | `RevokeUserTokensJob` |
| Policies | `{Entity}Policy` | `TokenPolicy` |

### Directory Structure (per module)
```
app/
  Http/
    Controllers/Api/V1/
    Requests/
    Resources/
    Middleware/
  Domain/
    Models/
    Events/
    ValueObjects/
  Services/
  Repositories/
    Contracts/
    Eloquent/
  Policies/
  Jobs/
  Listeners/
```

---

## API Design Standards

- All APIs are versioned under `/api/v1/`.
- Every response uses a **standard envelope**:
  ```json
  {
    "success": true,
    "data": { ... },
    "message": "Operation completed.",
    "meta": { "pagination": { ... } }
  }
  ```
- Error responses:
  ```json
  {
    "success": false,
    "error": { "code": "AUTH_001", "message": "Token expired." },
    "trace_id": "uuid-v4"
  }
  ```
- Use **idempotency keys** (`Idempotency-Key` header) on all mutating endpoints.
- Pagination: use cursor-based pagination for large datasets.
- All endpoints must be documented with **OpenAPI 3.1 annotations**.

---

## Testing Requirements

Run tests with:
```bash
php artisan test
# or
./vendor/bin/phpunit
```

Test categories to maintain:
- **Unit tests**: Service layer, value objects, domain logic.
- **Feature tests**: Full HTTP request/response cycle per endpoint.
- **Tenant isolation tests**: Verify no cross-tenant data leakage.
- **Authorization tests**: RBAC/ABAC enforcement per role/permission.
- **Concurrency tests**: Pessimistic/optimistic locking correctness.

Test file naming: `{ClassName}Test.php` in `tests/Unit/` or `tests/Feature/`.

---

## Static Analysis

Run PHPStan at level 9 before committing:
```bash
./vendor/bin/phpstan analyse --level=9
```

All code must pass PHPStan level 9 with zero errors.

---

## Infrastructure & Environment

- **Docker** + **Kubernetes** (Helm charts) for deployment.
- **Redis**: Cache, session, revocation lists, rate limiting, queues.
- **MySQL / PostgreSQL**: Primary relational database (tenant-scoped).
- **Kafka / RabbitMQ**: Async event messaging between microservices.
- Use `.env` for all environment-specific values; **never hardcode secrets**.
- Health check endpoint: `GET /health` (returns `200 OK` with service status).
- Metrics endpoint: `GET /metrics` (Prometheus-compatible).
- **Laravel Horizon** for queue monitoring; **Laravel Telescope** for local debugging (disabled in production).

---

## Distributed Systems Patterns

- **Outbox Pattern**: All domain events are written to an outbox table within the same DB transaction before being published to the message broker.
- **Saga Pattern**: For distributed transactions (e.g., Order → Inventory → Payment), use a reusable Saga orchestrator with compensating rollback actions.
- **Idempotent APIs**: All endpoints must be safe to retry without side effects.
- **Optimistic locking**: Use `version` column for concurrent update protection.
- **Pessimistic locking**: Use `lockForUpdate()` for stock deduction and financial operations.

---

## Performance Targets

- **p95 latency** for CRUD operations: ≤ 200 ms.
- Authentication token verification: ≤ 10 ms (local JWT validation, no Auth Service round-trip).
- Avoid N+1 queries — always eager-load relationships.

---

## Key Files & Entry Points

| File | Purpose |
|---|---|
| `README.md` | High-level project description |
| `AGENT.md` | Agent-specific instructions |
| `COPILOT.md` | Extended Copilot context |
| `CLAUDE.md` | Extended context for Claude agent |
| `.github/copilot-instructions.md` | This file — Copilot coding agent instructions |
| `app/Http/Controllers/Api/V1/` | All API controllers |
| `app/Services/` | Business logic layer |
| `app/Repositories/` | Data access layer |
| `routes/api.php` | API route definitions |
| `config/` | Application configuration |
| `database/migrations/` | Database schema |

---

## Common Tasks

### Adding a new API endpoint
1. Create a Form Request in `app/Http/Requests/`.
2. Create or update a Resource in `app/Http/Resources/`.
3. Add the method to the Service interface and implementation.
4. Add a thin controller method calling the service.
5. Register the route in `routes/api.php` under the `v1` prefix.
6. Write Feature test covering happy path and error cases.
7. Add OpenAPI annotation to the controller method.

### Adding a new domain event
1. Create the Event class in `app/Domain/Events/`.
2. Create a Listener in `app/Listeners/`.
3. Write to the outbox table in the same DB transaction.
4. Register in `EventServiceProvider`.
5. Write a unit test for the event dispatch.
