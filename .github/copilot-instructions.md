# GitHub Copilot Instructions

This repository is a **production-grade, enterprise, multi-tenant, modular ERP/CRM SaaS platform** built with **Laravel (LTS)** on the backend and **React (LTS)** on the frontend.

For the full governance contract, see [`AGENT.md`](../AGENT.md).  
For the domain knowledge base, see [`KB.md`](../KB.md).

---

## Tech Stack

- **Backend:** Laravel (LTS only), PHP 8.x, BCMath for all financial arithmetic
- **Frontend:** React (LTS only), TailwindCSS / AdminLTE
- **Architecture:** Modular Monolith (Clean Architecture), API-first, stateless
- **Multi-tenancy:** Shared DB with strict row-level isolation (`tenant_id` on every business table)
- **Auth:** JWT per user × device × organisation, RBAC + ABAC via Laravel Policies

---

## Repository Structure

```
Modules/
 └── {ModuleName}/
     ├── Application/       # Use cases, commands, queries, DTOs
     ├── Domain/            # Entities, value objects, domain events, repository contracts
     ├── Infrastructure/    # Repository implementations, external adapters
     ├── Interfaces/        # HTTP controllers, API resources, form requests
     ├── module.json
     └── README.md
```

---

## Mandatory Conventions

### Architecture
- Controllers must contain **no business logic** and must **never** call the query builder directly.
- All cross-module communication goes through **contracts or domain events only** — no direct dependencies between modules.
- Domain logic must be isolated from infrastructure.

### Multi-Tenancy
- Every new database table must include a `tenant_id` column with a **global scope** applied.
- Tenant isolation failures are treated as critical violations.

### Financial Precision
- **All** monetary and quantity calculations must use `BCMath` (arbitrary precision).  
- Floating-point arithmetic is **strictly forbidden** for financial values.
- Rounding must be deterministic.

### Authorization
- Use **Policy classes only** — no permission checks inside controllers.
- No hardcoded role or ID checks.

### API
- All endpoints must be RESTful, versioned (`/api/v1`), idempotent, and documented with OpenAPI.
- Responses must follow the standard envelope format with structured errors and pagination.

### Security
- CSRF protection, XSS/SQL-injection prevention, rate limiting, token rotation, Argon2/bcrypt hashing, signed URLs, and audit logging are all mandatory.

### Concurrency
- Use pessimistic locking for stock deduction and accounting posts.
- All writes must be safe under parallel load; stock and accounting must never be inconsistent.

### Metadata-Driven Design
- Hardcoded business rules, workflow states, pricing/tax logic, and UI layouts are **prohibited**.  
- All such configuration must be database-driven and runtime-resolvable.

---

## Prohibited Practices

- Business logic in controllers
- Query builder usage in controllers
- Cross-module tight coupling
- Hardcoded IDs or role strings
- Floating-point arithmetic for financial math
- Partial implementations or `TODO` comments without a linked tracking issue

---

## Definition of Done

A feature or module is complete only when:

- [ ] Clean Architecture respected (correct layer placement)
- [ ] No business logic in any controller
- [ ] All new tables include `tenant_id` with global scope applied
- [ ] All financial calculations use BCMath (no float)
- [ ] Authorization enforced via Policy classes
- [ ] Concurrency handled (appropriate locking)
- [ ] API endpoint documented (OpenAPI)
- [ ] Unit, feature, authorization, and tenant-isolation tests pass
- [ ] Module `README.md` updated
- [ ] `IMPLEMENTATION_STATUS.md` updated
- [ ] No cross-module direct dependency introduced
- [ ] No technical debt introduced

---

## Key Domain Flows

| Domain | Flow |
|---|---|
| Sales | Quotation → Sales Order → Delivery → Invoice → Payment |
| Procurement | Purchase Request → RFQ → Vendor Selection → PO → Goods Receipt → Vendor Bill → Payment |
| CRM | Lead → Opportunity → Proposal → Closed Won / Closed Lost |
| Inventory | All stock changes via immutable ledger transactions (never direct edits) |
| Accounting | Double-entry only — every transaction debits one account and credits another; Total Debits = Total Credits |
