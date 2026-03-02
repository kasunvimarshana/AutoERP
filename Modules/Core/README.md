# Core Module

## Overview

The **Core** module is the foundational layer of the ERP/CRM SaaS platform. It provides shared infrastructure, base abstractions, and cross-cutting concerns used by all other modules.

**No business logic resides in this module.** It is infrastructure only.

---

## Responsibilities

- Base Repository contract and abstract implementation
- Base Service contract
- Pipeline (Handler) base class
- BCMath decimal precision helpers (financial-safe arithmetic)
- Standard API response envelope
- Standard error response format
- Pagination helpers
- Global query scope traits (tenant isolation enforcement)
- Domain event base class
- Value Object base class
- DTO (Data Transfer Object) base class
- Idempotency key handling

---

## Architecture Layer

```
Modules/Core/
 ├── Application/       # Base DTOs, base commands/queries
 ├── Domain/            # Base entities, value objects, repository contracts, domain events
 ├── Infrastructure/    # Base repository implementations, service providers
 ├── Interfaces/        # Base API resources, base form requests
 ├── module.json
 └── README.md
```

---

## Dependencies

None. The Core module must have **zero dependencies** on other modules.

---

## Architecture Compliance

| Rule | Status |
|---|---|
| No business logic in controllers | ✅ Enforced |
| No query builder in controllers | ✅ Enforced |
| BCMath for all financial calculations | ✅ Enforced |
| Tenant isolation via global scope | ✅ Enforced |
| No cross-module coupling | ✅ N/A (Core has no module dependencies) |

---

## Status

🟢 **Complete** — Core foundation, BCMath helper, tenant scope, base repository, contracts, and value objects all implemented (~80% test coverage). See [IMPLEMENTATION_STATUS.md](../../IMPLEMENTATION_STATUS.md)

### Implemented

| Component | File | Description |
|---|---|---|
| `RepositoryContract` | `Domain/Contracts/RepositoryContract.php` | Base repository interface (findById, findOrFail, all, paginate, create, update, delete) |
| `ServiceContract` | `Domain/Contracts/ServiceContract.php` | Marker interface for all module services |
| `DomainEvent` | `Domain/Events/DomainEvent.php` | Base domain event with `$occurredAt` timestamp |
| `ValueObject` | `Domain/ValueObjects/ValueObject.php` | Immutable value object base with `equals()` and `toArray()` |
| `HasTenant` | `Domain/Traits/HasTenant.php` | Eloquent trait — registers TenantScope + auto-assigns `tenant_id` on create |
| `DataTransferObject` | `Application/DTOs/DataTransferObject.php` | Abstract DTO base with `fromArray()` / `toArray()` |
| `PipelineHandler` | `Application/Handlers/PipelineHandler.php` | Abstract pipeline stage for use with Laravel Pipeline |
| `DecimalHelper` | `Application/Helpers/DecimalHelper.php` | BCMath wrapper — add, sub, mul, div, round, compare, abs, pow. 26 unit tests, all passing. |
| `ApiResponse` | `Interfaces/Http/Resources/ApiResponse.php` | Standard JSON response envelope (success, paginated, created, noContent, error, validationError, etc.) |
| `TenantScope` | `Infrastructure/Scopes/TenantScope.php` | Eloquent global scope — filters all queries by `tenant_id` |
| `AbstractRepository` | `Infrastructure/Repositories/AbstractRepository.php` | Base repository implementation wrapping the Eloquent model |
| `CoreServiceProvider` | `Infrastructure/Providers/CoreServiceProvider.php` | Registers and bootstraps Core module |
| `core.php` | `config/core.php` | Core module configuration (API version, decimal precision constants) |
