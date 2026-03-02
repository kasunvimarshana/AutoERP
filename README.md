# KV Enterprise Dynamic SaaS CRM ERP

**Production-grade, enterprise, multi-tenant, modular ERP/CRM SaaS platform**

| Attribute | Value |
|---|---|
| Backend | Laravel (LTS) |
| Frontend | React (LTS) |
| Architecture | Modular Monolith (plugin-ready) |
| Multi-tenancy | Shared DB + Row-Level Isolation (optional DB-per-tenant) |
| API | RESTful, versioned at `/api/v1`, OpenAPI documented |
| Auth | JWT, stateless, multi-guard |
| Authorization | RBAC + ABAC (Policy classes) |
| Financial Precision | BCMath — arbitrary precision, no floating-point |
| Governance | [AGENT.md](AGENT.md) |
| Knowledge Base | [KB.md](KB.md) |
| Implementation Status | [IMPLEMENTATION_STATUS.md](IMPLEMENTATION_STATUS.md) |
| Claude AI Agent Guide | [CLAUDE.md](CLAUDE.md) |

---

## Platform Modules

| Module | Description | Status |
|---|---|---|
| [Core](Modules/Core/README.md) | Foundation, base repositories, BCMath helpers | 🔴 Planned |
| [Tenancy](Modules/Tenancy/README.md) | Multi-tenant isolation, global scopes | 🔴 Planned |
| [Auth](Modules/Auth/README.md) | JWT auth, RBAC, ABAC, API keys | 🔴 Planned |
| [Organisation](Modules/Organisation/README.md) | Tenant → Organisation → Branch → Location → Department | 🔴 Planned |
| [Metadata](Modules/Metadata/README.md) | Custom fields, dynamic forms, feature toggles | 🔴 Planned |
| [Workflow](Modules/Workflow/README.md) | State machine engine, approvals, SLA | 🔴 Planned |
| [Product](Modules/Product/README.md) | Product catalog, UOM, variants, pricing | 🔴 Planned |
| [Pricing](Modules/Pricing/README.md) | Rule-based pricing & discount engine | 🔴 Planned |
| [Inventory](Modules/Inventory/README.md) | Ledger-driven IMS, FIFO/LIFO/WA, concurrency; includes pharmaceutical compliance mode (FEFO, lot/expiry, FDA/DEA/DSCSA) | 🔴 Planned |
| [Warehouse](Modules/Warehouse/README.md) | WMS: bin tracking, putaway, picking, reverse logistics | 🔴 Planned |
| [Sales](Modules/Sales/README.md) | Quotation → Order → Delivery → Invoice → Payment | 🔴 Planned |
| [POS](Modules/POS/README.md) | Offline-first POS terminal, sync reconciliation | 🔴 Planned |
| [Accounting](Modules/Accounting/README.md) | Double-entry bookkeeping, journal entries, statements | 🔴 Planned |
| [CRM](Modules/CRM/README.md) | Lead → Opportunity → Proposal → Closed | 🔴 Planned |
| [Procurement](Modules/Procurement/README.md) | PO → Goods Receipt → Vendor Bill, three-way match | 🔴 Planned |
| [Reporting](Modules/Reporting/README.md) | Financial statements, inventory reports, custom builder | 🔴 Planned |
| [Notification](Modules/Notification/README.md) | Multi-channel notification engine, templates | 🔴 Planned |
| [Integration](Modules/Integration/README.md) | Webhooks, e-commerce sync, payment gateways | 🔴 Planned |
| [Plugin](Modules/Plugin/README.md) | Plugin marketplace, dependency resolution | 🔴 Planned |

---

## Architecture

### Mandatory Application Flow

```
Controller → Service → Handler (Pipeline) → Repository → Entity
```

### Module Structure

```
Modules/
 └── {ModuleName}/
     ├── Application/       # Use cases, commands, queries, DTOs, service orchestration
     ├── Domain/            # Entities, value objects, domain events, repository contracts
     ├── Infrastructure/    # Repository implementations, external service adapters
     ├── Interfaces/        # HTTP controllers, API resources, form requests, console commands
     ├── module.json
     └── README.md
```

### Multi-Tenancy Hierarchy

```
Tenant
 └── Organisation
      └── Branch
           └── Location
                └── Department
```

---

## Governance

- All contributors and AI agents are bound by [AGENT.md](AGENT.md)
- Domain knowledge base: [KB.md](KB.md)
- Implementation progress: [IMPLEMENTATION_STATUS.md](IMPLEMENTATION_STATUS.md)
- Claude AI agent guide: [CLAUDE.md](CLAUDE.md)

---

## Key Architectural Constraints

- ❌ No business logic in controllers
- ❌ No query builder calls in controllers
- ❌ No floating-point financial arithmetic (BCMath only)
- ❌ No circular dependencies
- ❌ No hardcoded tenant IDs or business rules
- ✅ `tenant_id` on every business table with global scope
- ✅ All financial calculations: BCMath, minimum 4 decimal places
- ✅ All stock mutations inside database transactions with pessimistic locking
- ✅ All journal entries immutable (double-entry, debits = credits)
- ✅ Full audit trail on all modules (non-optional)
