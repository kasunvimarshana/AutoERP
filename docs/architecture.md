# System Architecture

## Overview

KV MultiTenant SaaS – Order Processing Microservices

```
┌─────────────────────────────────────────────────────────────────────────┐
│                           CLIENT LAYER                                  │
│                   (Web App / Mobile / Third-Party)                      │
└──────────────────────────────┬──────────────────────────────────────────┘
                               │ HTTPS
                               ▼
┌─────────────────────────────────────────────────────────────────────────┐
│                         API GATEWAY (Node.js)                           │
│  • JWT Authentication      • Rate Limiting      • Request Routing       │
│  • Correlation ID          • CORS / Helmet      • Service Registry      │
│           Host Port: 8080  (container internal: 3000)                   │
└───────┬──────────────────┬──────────────────┬───────────────────────────┘
        │                  │                  │
        ▼                  ▼                  ▼
┌──────────────┐  ┌──────────────┐  ┌──────────────────┐
│ Order Service│  │Payment Service│  │Inventory Service │
│  (Laravel)   │  │  (Laravel)   │  │  (Python/FastAPI)│
│  Port: 8001  │  │  Port: 8002  │  │   Port: 8003     │
│  MySQL DB    │  │ PostgreSQL DB│  │   MongoDB DB     │
└──────┬───────┘  └──────┬───────┘  └────────┬─────────┘
       │                 │                   │
       └─────────────────┴───────────────────┘
                         │
                         ▼
              ┌─────────────────────┐
              │  RabbitMQ (Events)  │
              │   Exchange: topic   │
              │  Port: 5672/15672   │
              └──────────┬──────────┘
                         │
                         ▼
              ┌─────────────────────┐
              │ Notification Service│
              │     (Node.js)       │
              │    Port: 8004       │
              │    Redis Cache      │
              └─────────────────────┘
```

## Service Responsibilities

| Service              | Language     | Database   | Host Port |
|----------------------|-------------|------------|-----------|
| API Gateway          | Node.js      | —          | 8080      |
| Order Service        | PHP/Laravel  | MySQL      | 8001      |
| Payment Service      | PHP/Laravel  | PostgreSQL | 8002      |
| Inventory Service    | Python       | MongoDB    | 8003      |
| Notification Service | Node.js      | Redis      | 8004      |

## Infrastructure Components

| Component  | Image                    | Host Port(s)     | Purpose                      |
|------------|--------------------------|------------------|------------------------------|
| MySQL      | mysql:8.0                | 3306             | Order data persistence       |
| PostgreSQL | postgres:15              | 5432             | Payment data persistence     |
| MongoDB    | mongo:6.0                | 27017            | Inventory data persistence   |
| RabbitMQ   | rabbitmq:3.12-management | 5672, 15672      | Async event messaging        |
| Redis      | redis:7.2-alpine         | 6379             | Notification deduplication   |

## Saga Flow (Order Processing)

```
Client           API Gateway    Order Svc    Inventory Svc   Payment Svc   Notification Svc
  │                  │              │               │              │               │
  │─── POST /orders ──►             │               │              │               │
  │                  │──[route]────►│               │              │               │
  │                  │              │─Create Order──│              │               │
  │                  │              │  (pending)    │              │               │
  │                  │              │               │              │               │
  │                  │              │──Reserve Inventory──────────►│               │
  │                  │              │  (inventory_reserving)       │               │
  │                  │              │               │◄─200 OK──────│               │
  │                  │              │  (inventory_reserved)        │               │
  │                  │              │               │              │               │
  │                  │              │──────────────────Process Payment────────────►│
  │                  │              │  (payment_processing)        │               │
  │                  │              │◄──────────────────────────200 OK─────────────│
  │                  │              │  (confirmed)                 │               │
  │                  │              │               │              │               │
  │                  │              │──────────────────────────────────────────────► Send Confirmation
  │◄─── 201 Created ─┤◄──[201]──────│               │              │               │
```

## Compensation (Rollback) Flow

```
                     [Payment FAILS]
                           │
Order Svc ──────────────────────────────────────────────────────────────────
  │                                                                        │
  │  1. Set order.status = 'compensating'                                 │
  │  2. POST /inventory/release  ◄── re-add stock quantities              │
  │  3. Set order.status = 'payment_failed'                               │
  │  4. POST /notifications/send ◄── notify customer of failure           │
  │                                                                        │
  │  Final State: order.saga_status = 'compensated'                       │
─────────────────────────────────────────────────────────────────────────────

                  [Inventory Reservation FAILS]
                           │
Order Svc ──────────────────────────────────────────────────────────────────
  │                                                                        │
  │  1. Set order.status = 'cancelled'                                    │
  │  2. POST /notifications/send ◄── notify customer                      │
  │                                                                        │
  │  Final State: order.saga_status = 'compensated'                       │
─────────────────────────────────────────────────────────────────────────────
```

## Network Architecture

All services communicate within the `microservices_network` Docker bridge network.
Service-to-service calls use Docker internal DNS (e.g., `http://order-service:8000`).
Only the API Gateway is exposed to external traffic via port 8080.

```
External Traffic
      │
      │ :8080
      ▼
┌─────────────────────────────────────────────────────┐
│              microservices_network                   │
│                                                     │
│  api-gateway ──► order-service ──► inventory-service│
│                        │                            │
│                        └──────► payment-service     │
│                        └──────► notification-service│
│                                                     │
│  [mysql] [postgres] [mongodb] [rabbitmq] [redis]    │
└─────────────────────────────────────────────────────┘
```

## Design Principles

1. **Loose Coupling** – Services communicate via REST/events; no shared databases.
2. **Independent Deployability** – Each service has its own Dockerfile.
3. **Technology Heterogeneity** – PHP, Python, Node.js, MySQL, PostgreSQL, MongoDB.
4. **Saga Pattern** – Distributed transaction management with compensation logic.
5. **Idempotency** – Redis deduplication prevents duplicate notifications; payment charge checks for existing records.
6. **Observability** – Correlation IDs (`X-Correlation-ID`) propagated across all services.
7. **Multi-Tenancy** – `tenant_id` scoped on every data model and propagated via `X-Tenant-ID` header.
8. **Security** – JWT authentication at the gateway; Helmet + CORS middleware; internal-only endpoints protected by `X-Internal-Service` header.
9. **Saga Pattern** – Orchestration-based: `OrderSagaOrchestrator` is the central coordinator that calls Inventory, Payment, and Notification services in sequence with compensation logic on failure.
