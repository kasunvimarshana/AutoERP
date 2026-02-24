# Copilot Instructions

This repository implements a production-grade, enterprise, multi-tenant, modular ERP/CRM SaaS platform. The full governance contract is defined in [`AGENT.md`](../AGENT.md) at the repository root — all contributors and AI agents must follow it.

The key points summarised below are enforced at all times.

---

## Tech Stack

- **Backend**: Laravel (latest stable LTS release, e.g. 10.x or 11.x)
- **Frontend**: Vue (latest stable LTS release, e.g. 3.x)
- Native framework features only — no unstable or experimental dependencies

---

## Architecture

Strict Clean Architecture with four layers (top → bottom):

| Layer | Responsibilities |
|-------|-----------------|
| **Presentation** | Controllers, API routes, request validation, response formatting — no business logic |
| **Application** | Use cases, services, orchestrators, transaction management, domain event emission |
| **Domain** | Entities, Value Objects, Aggregates, Enums, Contracts — no framework dependency |
| **Infrastructure** | Repositories, DB models, external integrations, file systems, queue adapters |

---

## Module Structure

Every feature must be an isolated module under `Modules/`:

```
Modules/
 └── {ModuleName}/
     ├── Domain/
     ├── Application/
     ├── Infrastructure/
     ├── Presentation/
     ├── Providers/
     ├── routes.php
     ├── config.php
     ├── module.json
     └── README.md
```

- No direct cross-module calls — communicate only via Contracts, Events, or API
- No shared state, no circular dependencies
- Each module must be capable of being independently enabled or disabled

---

## Multi-Tenancy

- `tenant_id` is mandatory in every persistent table
- All queries must be tenant-scoped via global scope
- JWT is issued per user × device × organization
- No cross-tenant queries — violations are critical

---

## Authorization

- RBAC + ABAC using Laravel Policies only
- Authorization enforced via middleware, never inside controllers

---

## Financial Integrity

- Use `DECIMAL(18,8)` — **floating-point arithmetic is forbidden** for financial operations
- Use BCMath for all monetary calculations
- All write operations must be wrapped in DB transactions
- Use pessimistic locking for stock deductions, optimistic locking via `version` column

---

## Metadata-Driven Design

The following must **never** be hardcoded — store them in the database:

- Pricing rules
- Workflow states
- UI component definitions
- Permissions
- Tax logic
- Calculation strategies

---

## Event-Driven Communication

- Modules communicate via Laravel Events, Queues, Pipelines, and Jobs
- Direct synchronous cross-module invocation is **forbidden**

---

## Security

- CSRF protection, XSS prevention, SQL injection prevention
- Rate limiting, token rotation, secure password hashing
- Strict validation on every request
- Stateless JWT — refresh token rotation and token version invalidation required

---

## API Design

- RESTful, versioned endpoints
- Consistent response format and structured error responses
- Idempotency keys for critical write operations
- Pagination enforced on all list endpoints

---

## Testing

Every module must include:

- Unit tests
- Feature tests
- Authorization / policy tests
- Tenant isolation tests
- Concurrency tests

No module is considered complete without full test coverage.

---

## Prohibited Practices

- Business logic inside controllers
- Query builder usage in controllers
- Global or shared mutable state
- Static service calls across modules
- Hardcoded pricing or workflow logic
- Direct model coupling between modules
- Bypassing policies
- Partial or incomplete implementations
- Floating-point arithmetic for financial calculations

---

## Definition of Done

A module is complete only when:

1. Architecture layer boundaries are respected
2. No code duplication exists
3. Tenant isolation is enforced
4. Authorization is enforced
5. Domain events are emitted correctly
6. Concurrency is handled safely
7. All tests pass
8. Module documentation is updated
9. CI pipeline passes
10. No technical debt introduced

---

## Agent Workflow

Before modifying any code:

1. Analyse the entire affected module
2. Refactor any existing violations before adding new code
3. Preserve tenant isolation at all times
4. Update documentation alongside implementation
5. Avoid introducing coupling between modules
6. Maintain strict Clean Architecture and modular independence
