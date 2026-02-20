# AGENT.md

## Architectural Mandate

This ERP SaaS is strictly modular.
Every functional capability must exist inside a Module.
No feature may exist in the root application.

---

## Clean Architecture Enforcement

Controllers:
- Validation only
- Authorization only
- Call Application Services

Application Services:
- Business logic only
- Dispatch events

Repositories:
- Data access only
- No business logic

Domain:
- Entities
- Value Objects
- Enums
- Domain Events

---

## Module Isolation Rules

- No cross-module model imports.
- No direct DB access outside repositories.
- No shared mutable state.
- No circular dependencies.
- Modules communicate via Events or Contracts only.

---

## Tenancy Rules

Every persistent entity MUST contain:
- tenant_id
- organization_id (if applicable)

All queries MUST be tenant-scoped.

---

## Security Rules

- Stateless JWT.
- Token per user × device × organization.
- Refresh token rotation.
- Token version invalidation.
- RBAC + ABAC via Policies and Middleware.
- Idempotency keys for critical APIs.

---

## Financial Integrity

- Use DECIMAL(18,8).
- No floats.
- Use BCMath.
- All writes in DB transactions.
- Use pessimistic locking for stock deduction.
- Optimistic locking via version column.
- Maintain audit logs.

---

## Metadata Rules

- Pricing rules stored in DB.
- Workflows defined in DB.
- UI rendered from metadata.
- No hardcoded pricing/workflow logic.

---

## Event-Driven Rules

- Modules emit events.
- Listeners handle side effects.
- No direct module-to-module invocation.

---

## Testing Requirements

Each module must include:
- Unit tests
- Feature tests
- Policy tests
- Concurrency tests

---

## Prohibited

- Business logic in controllers.
- Direct SQL in controllers.
- Hardcoded business rules.
- Cross-layer leakage.
- Global state.

---

## Required

- Clean Architecture.
- DDD.
- SOLID.
- DRY.
- KISS.
- API-first.
- Fully stateless.
- Fully modular.
- Production-ready.

## REFERENCES

These references provide guidance on modular design, Laravel best practices, multi-tenancy, ERP/CRM design, and other principles relevant to this repository:

- https://blog.cleancoder.com/atom.xml 
- https://en.wikipedia.org/wiki/Modular_design 
- https://en.wikipedia.org/wiki/Plug-in_(computing) 
- https://laravel.com/blog/building-a-multi-tenant-architecture-platform-to-scale-the-emmys 
- https://en.wikipedia.org/wiki/Enterprise_resource_planning 
- https://dev.to/rafaelogic/building-a-polymorphic-translatable-model-in-laravel-with-autoloaded-translations-3d99 
- https://sevalla.com/blog/building-modular-systems-laravel 
- https://github.com/laravel/laravel 
- https://laravel.com/docs/12.x/packages 
- https://swagger.io 
- https://en.wikipedia.org/wiki/SOLID 
- https://laravel.com/docs/12.x/filesystem 
- https://laravel-news.com/uploading-files-laravel 
- https://adminlte.io 
- https://tailwindcss.com 
- https://dev.to/bhaidar/understanding-database-locking-and-concurrency-in-laravel-a-deep-dive-2k4m 
- https://laravel-news.com/managing-data-races-with-pessimistic-locking-in-laravel 
- https://dev.to/tegos/pessimistic-optimistic-locking-in-laravel-23dk 
- https://dev.to/takeshiyu/handling-decimal-calculations-in-php-84-with-the-new-bcmath-object-api-442j 
- https://dev.to/keljtanoski/modular-laravel-3dkf 
- https://laravel.com/docs/12.x/processes 
- https://laravel.com/docs/12.x/helpers#pipeline 
- https://dev.to/preciousaang/multi-guard-authentication-with-laravel-12-1jg3 
- https://laravel.com/docs/12.x/authentication 
- https://laravel.com/docs/12.x/localization 
- https://sevalla.com/blog/building-modular-systems-laravel 
- https://www.laravelpackage.com 
- https://laravel-news.com/building-your-own-laravel-packages 
- https://dev.to/rafaelogic/building-a-polymorphic-translatable-model-in-laravel-with-autoloaded-translations-3d99 
- https://freek.dev/1567-pragmatically-testing-multi-guard-authentication-in-laravel 
- https://laravel-news.com/laravel-gates-policies-guards-explained 
- https://dev.to/codeanddeploy/how-to-create-a-custom-dynamic-middleware-for-spatie-laravel-permission-2a08 
- https://laravel.com/docs/12.x/authorization
