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

🔴 **Planned** — See [IMPLEMENTATION_STATUS.md](../../IMPLEMENTATION_STATUS.md)
