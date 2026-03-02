# AGENT.md  
Enterprise-Grade Inventory, Pharmaceutical & WMS SaaS Platform  
Version: 2.0  
Status: Enforced  
Scope: Entire Repository  

---

# 1. SYSTEM PURPOSE

This repository implements a production-grade, modular, multi-tenant SaaS platform for:

- Inventory Management
- Pharmaceutical Inventory Management
- Warehouse Management (WMS)
- ERP/CRM Integration

The system must remain:

- Fully modular
- Multi-tenant aware
- Financially precise (decimal-safe)
- Horizontally scalable
- Regulatory-compliant (pharmaceutical context)
- Audit-safe and concurrency-safe
- API-first and integration-ready

---

# 2. ARCHITECTURAL GOVERNING PRINCIPLES

All implementations MUST adhere to:

## 2.1 Core Engineering Principles

- SOLID principles
- DRY (no duplication of business logic)
- KISS (minimal complexity)
- Explicit domain boundaries
- Clear separation of concerns
- Immutable financial calculations
- Deterministic behavior

---

## 2.2 Application Flow (MANDATORY)

Every feature must follow:

Controller  
→ Service  
→ Handler (Pipeline)  
→ Repository  
→ Entity  

### Responsibilities

**Controller**
- Input validation
- Authorization
- Response formatting
- No business logic

**Service**
- Orchestrates use cases
- Transaction boundaries
- Calls pipelines

**Handler (Pipeline)**
- Single-responsibility processing steps
- Transformations
- Domain rules
- Reusable logic units

**Repository**
- Data access only
- No domain logic
- Tenant-aware queries

**Entity**
- Pure domain model
- Relationships
- Attribute casting
- No orchestration logic

---

# 3. DOMAIN MODULES

The system is modular. Each module must be independently testable and extensible.

## 3.1 Inventory Module

Capabilities:

- Real-time stock tracking
- Multi-location support
- Multi-channel synchronization
- ABC analysis
- Cycle counting
- Lot/serial traceability
- Automated reordering
- Demand forecasting
- Supplier management
- Reporting & KPIs

Must support:

- Inventory valuation
- Turnover metrics
- Carrying cost analysis
- Audit trails

---

## 3.2 Pharmaceutical Extension Module

Extends Inventory module with:

- FEFO (First-Expired-First-Out)
- Expiry-based prioritization
- Batch/lot mandatory tracking
- Drug serial tracking
- High-risk medication flagging
- Controlled substance audit enforcement
- Regulatory logging (FDA/DEA/DSCSA aligned)
- Expiry alerts
- Quarantine workflows

Compliance is NOT optional.

---

## 3.3 Warehouse Management System (WMS)

Capabilities:

- Bin/location-level tracking
- Receiving & automated putaway suggestions
- Picking strategies:
  - Batch
  - Wave
  - Zone
- Route optimization
- Packing validation
- Reverse logistics (returns)
- Labor performance tracking
- Warehouse layout optimization
- Operational KPI reporting

WMS must integrate seamlessly with Inventory module.

---

## 3.4 ERP/CRM Integration

Must support integration with:

- Accounting (double-entry bookkeeping)
- POS systems
- E-commerce platforms
- Billing systems
- External ERP systems
- EHR/EMR (pharmaceutical context)

System must expose:

- REST APIs
- OpenAPI/Swagger documentation
- Versioned endpoints

---

# 4. MULTI-UOM DESIGN (MANDATORY STANDARD)

Each product must support:

- `uom` (base inventory unit) — REQUIRED
- `buying_uom` — OPTIONAL (fallback to base)
- `selling_uom` — OPTIONAL (fallback to base)

## 4.1 UOM Conversion Rules

- Product-specific conversion table
- Direct path conversion
- Inverse path (reciprocal)
- No global assumptions
- No implicit conversion

## 4.2 Decimal Arithmetic Standard

ALL quantity and financial calculations:

- MUST use BCMath
- MUST use 4 decimal places minimum
- MUST NEVER use floating-point arithmetic
- MUST be deterministic and reversible

Violation of this rule is critical.

---

# 5. SAAS ARCHITECTURE GOVERNANCE

## 5.1 Multi-Tenancy Models

Supported:

- Shared database with TenantID
- Schema-per-tenant
- Database-per-tenant

Each repository MUST be tenant-aware.

Tenant isolation is mandatory.

---

## 5.2 Identity & Access Management

System must support:

- Multi-guard authentication
- Role-based access control (RBAC)
- Policy-based authorization
- Dynamic middleware
- Tenant-scoped permissions

Unauthorized cross-tenant access is strictly prohibited.

---

## 5.3 Scalability

System must:

- Be stateless at application level
- Support horizontal scaling
- Avoid tenant-specific code branches
- Use centralized configuration
- Support microservice extraction if required

---

# 6. CONCURRENCY & DATA INTEGRITY

## 6.1 Transactions

All stock mutations must:

- Execute inside database transactions
- Guarantee atomicity
- Prevent partial writes

## 6.2 Locking

Use:

- Pessimistic locking for stock deduction
- Optimistic locking where applicable
- Deadlock-aware retry mechanisms

Stock integrity is critical.

---

# 7. ORGANIZATIONAL HIERARCHY MODEL

System must support nested hierarchical units:

Company  
→ Division  
→ Region  
→ Warehouse  
→ Department  
→ Sub-unit  

Rules:

- Parent-child relationship enforced
- Recursive querying supported
- Tenant-bound hierarchy
- No circular relationships allowed

---

# 8. API & DOCUMENTATION STANDARD

Every public endpoint must:

- Be documented using OpenAPI
- Include request validation schemas
- Include response schemas
- Be versioned

Internal modules must expose clean service contracts.

---

# 9. FRONTEND ARCHITECTURE (IF APPLICABLE)

If React frontend exists:

- Micro-frontend ready
- Module federation compatible
- Feature-based architecture
- No business logic duplication from backend
- Strict API contract adherence

Dashboards may use Tailwind-based admin templates.

---

# 10. REPORTING & ANALYTICS STANDARD

System must generate:

- Inventory turnover
- Order cycle time
- Labor efficiency
- Expiry risk analysis
- Profitability per product
- Carrying cost metrics

Reports must be:

- Tenant-scoped
- Filterable
- Exportable
- Auditable

---

# 11. SECURITY REQUIREMENTS

- Full audit trail of stock mutations
- User action logging
- Tamper-resistant records
- Expiry override logging
- High-risk medication access logging
- Strict input validation
- Rate limiting (API layer)

---

# 12. EXTENSIBILITY RULES

Modules must:

- Be open for extension
- Closed for modification
- Avoid cross-module direct database access
- Communicate through services or events

Plugin-style architecture is encouraged.

---

# 13. PROHIBITED PRACTICES

The following are strictly disallowed:

- Business logic inside controllers
- Floating-point stock calculations
- Hardcoded tenant conditions
- Cross-tenant data queries
- Direct database queries in services
- Silent exception swallowing
- Implicit UOM conversion
- Duplicate stock deduction logic
- Skipping transactions for inventory mutation

---

# 14. BUSINESS OBJECTIVES

The platform must:

- Reduce operational cost
- Increase stock accuracy
- Prevent stockouts
- Minimize expiry waste
- Ensure pharmaceutical compliance
- Improve warehouse efficiency
- Support ERP-level integration
- Maintain high scalability
- Preserve financial integrity

---

# 15. COMPLIANCE MODE (PHARMACEUTICAL DEPLOYMENT)

When pharmaceutical mode is enabled:

- Lot tracking is mandatory
- Expiry date is mandatory
- FEFO is enforced
- Serial tracking required where applicable
- Audit trail cannot be disabled
- Regulatory reports must be available

---

# 16. SYSTEM INTEGRITY GUARANTEE

Inventory consistency, financial correctness, tenant isolation, and regulatory compliance are non-negotiable.

All contributors and AI agents must treat:

- Stock data
- Financial data
- Expiry data
- Tenant data

as critical system assets.

---
