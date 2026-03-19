# Enterprise ERP/CRM SaaS Platform - High-Level Architecture

## 1. Overview
The platform follows a **Microservices Architecture** with a **Shared-Kernel approach** for common enterprise concerns (Multi-tenancy, Auth context, Distributed tracing). It is designed to be **Industry-Agnostic**, **Metadata-Driven**, and **Highly Scalable**.

### Core Architecture Patterns:
- **Service Isolation**: Each service has its own database. No cross-service joins.
- **Communication**: REST for synchronous calls, gRPC for high-performance internal communication, and Kafka/RabbitMQ for asynchronous events.
- **Data Consistency**: Saga Pattern for distributed transactions, Outbox Pattern for reliable event publishing.
- **Tenancy**: Multi-tenant with hierarchical isolation (Tenant -> Org -> Branch -> Location).
- **Domain-Driven Design (DDD)**: Each service is a Bounded Context.

## 2. Microservices Breakdown

| Service | Responsibility | Technology Stack |
|---------|----------------|------------------|
| **Auth Service** | SSO, JWT, Passport, RBAC/ABAC, Multi-guard | Laravel, PostgreSQL, Redis |
| **User Service** | User profiles, Organizations, Branches, Hierarchy | Laravel, PostgreSQL |
| **Product Service** | SKU, Variants, UOM, GS1, Attributes, Images | Laravel, MongoDB/PostgreSQL |
| **Inventory Service** | Ledger, Stock movements, Reservations, Traceability | Laravel, PostgreSQL (Immutable) |
| **Warehouse Service** | Bins, Locations, FEFO/FIFO/LIFO logic | Laravel, PostgreSQL |
| **Order Service** | Sales Orders, POS, Quotations, Returns | Laravel, PostgreSQL |
| **Finance Service** | Accounting, Ledger, P&L, Tax Engine, BCMath | Laravel, PostgreSQL |
| **CRM Service** | Leads, Opportunities, Pipelines | Laravel, PostgreSQL |
| **Procurement Service** | RFQ, PO, Vendor Bills, Sourcing | Laravel, PostgreSQL |
| **Workflow Service** | Dynamic State Machines, Approval Chains | Laravel, Redis, PostgreSQL |
| **Config Service** | Metadata, Dynamic Forms, Rule Engine, Feature Flags | Laravel, Redis, MongoDB |
| **Reporting Service** | Analytics, OLAP, Dashboards | Node.js/Python, ClickHouse |

## 3. Communication Diagram (Simplified)
[Client] -> [API Gateway (Kong/Nginx)] -> [Microservices]
                                         |
                                         V
                                [Message Broker (Kafka)]
                                         |
                                         V
                                [Consumer Services]

## 4. Multi-Tenant Isolation
Each request carries a `X-Tenant-ID` and `X-Org-ID`. Middleware injects this context into the database connection and cache/queue prefixes.
- **Database**: Database-per-tenant or Shared-database with Tenant-ID filtering (configurable).
- **Cache/Queue**: Prefixed by Tenant-ID.
- **Storage**: S3 buckets with tenant-prefixed keys.
