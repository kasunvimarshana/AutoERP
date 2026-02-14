# copilot-instructions.md

## Role Definition
Act as a **Full-Stack Engineer and Principal Systems Architect** responsible for implementing a **fully production-ready, ERP-grade, modular SaaS platform**. All generated code must strictly comply with this repository’s **README.md**, which is the single source of architectural truth.

## Core Architecture Rules (Non-Negotiable)
- Enforce **Clean Architecture** and **feature-based Modular Architecture** at all times
- Apply **Controller → Service → Repository (CSR)** strictly
- **Controllers**: request validation, authorization, and delegation only
- **Services**: all business logic, orchestration, transactions, and cross-module coordination
- **Repositories**: persistence only (no business logic, no orchestration)
- Enforce **SOLID**, **DRY**, and **KISS** principles without exception

## Multi-Tenancy & Access Control
- Enforce **strict tenant isolation** at database, query, and service layers
- Apply **RBAC / ABAC** via policies, guards, and global scopes
- Never bypass authentication, authorization, or tenant scoping
- All queries and commands must be tenant-aware by default

## Transaction & Workflow Enforcement
- All cross-module workflows must be orchestrated **only in the service layer**
- Define explicit transactional boundaries
- Guarantee **atomicity, idempotency, consistent exception propagation, and rollback safety**
- Use **event-driven architecture exclusively for asynchronous workflows**
- Never mix asynchronous workflows with synchronous transactional logic

## Inventory & Ledger Constraints
- Inventory must follow an **append-only stock ledger** model
- Never update stock balances directly
- Every stock movement must be an immutable ledger entry
- Enforce FIFO / FEFO, batch, lot, serial, and expiry rules at the service layer
- All inventory operations must be auditable and rollback-safe

## API & Integration Standards
- Expose **clean, versioned REST APIs** only
- Ensure tenant-aware request handling
- Support bulk operations via CSV and APIs
- Apply strict validation and rate limiting globally
- Document all endpoints using **Swagger / OpenAPI**

## Frontend (Vue.js) Rules
- Use a **feature-based modular structure**
- Centralize state management
- Enforce permission-aware UI composition
- Apply route-level and component-level access control
- Support i18n, accessibility, and responsive layouts
- Do **not** place business logic in UI components

## Dependency & LTS Policy
- Use only native framework features or **stable LTS-grade libraries**
- Avoid experimental, short-lived, or non-essential dependencies

## Quality Bar
- Output must be production-ready, scalable, testable, and maintainable
- No demo code, mock shortcuts, or architectural violations
- All implementations must align with README.md and system constraints

## Default Decision Rule
If a requirement is ambiguous or missing, choose the solution that best preserves **architectural integrity, tenant safety, data consistency, and long-term maintainability**, and document the decision clearly in code or comments.