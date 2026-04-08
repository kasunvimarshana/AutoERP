# Laravel Microservices — Distributed Transaction CRUD

A production-ready reference implementation demonstrating **cross-service CRUD operations with compensating transactions (Saga pattern)** using two independent Laravel services.

---

## Architecture Overview

```
┌─────────────────────────────────────────────────────────────────┐
│                        Client (HTTP)                            │
└───────────────────────────┬─────────────────────────────────────┘
                            │
                            ▼
┌─────────────────────────────────────────────────────────────────┐
│                     ORDER SERVICE  :8001                        │
│                                                                 │
│  OrderController                                                │
│   ├── store()   → begins DB txn → calls Inventory → commits    │
│   ├── update()  → begins DB txn → calls Inventory → commits    │
│   └── destroy() → begins DB txn → calls Inventory → commits    │
│                                                                 │
│  InventoryServiceClient  (HTTP adapter with idempotency keys)   │
│                                                                 │
│  PostgreSQL: orders, order_items                                │
└───────────────────────────┬─────────────────────────────────────┘
                            │  HTTP  (Idempotency-Key header)
                            ▼
┌─────────────────────────────────────────────────────────────────┐
│                  INVENTORY SERVICE  :8002                       │
│                                                                 │
│  ReservationController                                          │
│   ├── store()   → SELECT…FOR UPDATE → decrement stock          │
│   ├── update()  → release old stock → reserve new stock        │
│   └── destroy() → increment stock (release)                    │
│                                                                 │
│  InventoryService  (pessimistic locking, deadlock-safe order)   │
│                                                                 │
│  PostgreSQL: products, reservations, reservation_items,         │
│             idempotency_keys                                    │
└─────────────────────────────────────────────────────────────────┘
```

---

## Distributed Transaction Pattern — Saga (Choreography)

Because two independent databases are involved, a classic ACID transaction is impossible. This project implements the **Saga pattern** with **compensating transactions**:

```
CREATE ORDER — Happy path
─────────────────────────
[Order DB txn begins]
  1. INSERT orders (status=pending)
  2. → POST /inventory/reservations       ← remote call
  3. UPDATE orders SET status=confirmed
[Order DB txn commits]

CREATE ORDER — Inventory failure
─────────────────────────────────
[Order DB txn begins]
  1. INSERT orders (status=pending)
  2. → POST /inventory/reservations  ✗ FAILS
[Order DB txn ROLLED BACK]            ← no order in DB
  (no compensation needed — reservation was never created)

CREATE ORDER — Confirm step failure
─────────────────────────────────────
[Order DB txn begins]
  1. INSERT orders (status=pending)
  2. → POST /inventory/reservations  ✓ OK  (reservation_id = R1)
  3. UPDATE orders …                 ✗ FAILS
[Order DB txn ROLLED BACK]
  4. → DELETE /inventory/reservations/R1   ← COMPENSATING call
```

The same pattern applies to **update** (restore original items) and **delete** (re-create the reservation).

---

## Key Design Decisions

| Concern | Solution |
|---|---|
| Concurrent over-reservation | `SELECT … FOR UPDATE` on product rows |
| Deadlocks between concurrent reservations | Rows locked in ascending `id` order |
| Duplicate HTTP retries | `Idempotency-Key` header + `idempotency_keys` table |
| Cascading compensation failure | `safeCompensate()` logs critical alert without masking original error |
| Network transient errors | Linear retry (3×, 200 ms) only on 5xx/connection errors |

---

## Project Structure

```
laravel-microservices/
├── docker-compose.yml
│
├── order-service/
│   ├── app/
│   │   ├── Http/Controllers/OrderController.php   # CRUD + Saga orchestration
│   │   ├── Models/Order.php
│   │   ├── Models/OrderItem.php
│   │   ├── Services/OrderService.php              # Local business logic
│   │   ├── Services/InventoryServiceClient.php    # HTTP adapter
│   │   └── Exceptions/
│   │       ├── DistributedTransactionException.php
│   │       └── Handler.php
│   ├── config/services.php
│   ├── database/migrations/
│   ├── routes/api.php
│   └── tests/Feature/OrderDistributedTransactionTest.php
│
└── inventory-service/
    ├── app/
    │   ├── Http/Controllers/ReservationController.php  # Stock management
    │   ├── Models/{Product,Reservation,ReservationItem}.php
    │   ├── Services/InventoryService.php               # Pessimistic locking
    │   └── Exceptions/
    │       ├── InsufficientStockException.php
    │       └── Handler.php
    ├── database/migrations/
    ├── database/seeders/ProductSeeder.php
    ├── routes/api.php
    └── tests/Feature/ReservationTest.php
```

---

## Quick Start

```bash
# 1. Clone and start all services
git clone <repo>
cd laravel-microservices
docker-compose up --build -d

# 2. Run migrations and seed inventory
docker-compose exec inventory-service php artisan migrate --seed
docker-compose exec order-service     php artisan migrate

# 3. Run tests
docker-compose exec order-service     php artisan test
docker-compose exec inventory-service php artisan test
```

---

## API Reference

### Order Service  (`http://localhost:8001`)

| Method | Endpoint | Description |
|--------|----------|-------------|
| `GET`  | `/api/orders` | List all orders (paginated) |
| `GET`  | `/api/orders/{id}` | Get a single order |
| `POST` | `/api/orders` | Create order + reserve inventory |
| `PUT`  | `/api/orders/{id}` | Update order items + adjust reservation |
| `DELETE` | `/api/orders/{id}` | Cancel order + release stock |

#### POST /api/orders — example request
```json
{
  "customer_id": 42,
  "items": [
    { "product_id": 1, "quantity": 2 },
    { "product_id": 3, "quantity": 1 }
  ]
}
```

#### POST /api/orders — success response `201`
```json
{
  "success": true,
  "message": "Order created and inventory reserved.",
  "order_id": 7,
  "reservation_id": "550e8400-e29b-41d4-a716-446655440000"
}
```

#### POST /api/orders — failure response `422`
```json
{
  "success": false,
  "error": "distributed_transaction_failed",
  "message": "Order creation failed: Insufficient stock for product #1 ..."
}
```

### Inventory Service  (`http://localhost:8002`)

| Method | Endpoint | Description |
|--------|----------|-------------|
| `GET`  | `/api/inventory/reservations/{id}` | Get reservation |
| `POST` | `/api/inventory/reservations` | Reserve stock |
| `PATCH`| `/api/inventory/reservations/{id}` | Adjust reservation |
| `DELETE`| `/api/inventory/reservations/{id}` | Cancel reservation |

All mutating endpoints accept an optional `Idempotency-Key` header for safe retries.

---

## Error Scenarios Covered by Tests

| Scenario | Expected Behaviour |
|---|---|
| Inventory has enough stock | Order confirmed, stock decremented |
| Insufficient stock | Order rolled back, no DB entry, `422` returned |
| Inventory service returns 5xx | 3 retries, then rollback + compensation |
| Local confirm step fails after remote reserve | Local rollback + `DELETE /reservations/{id}` compensation |
| Compensation call also fails | Critical log entry for manual remediation, original error re-thrown |
| Same `Idempotency-Key` sent twice | Second call returns cached response, no duplicate side-effects |
| Order update with insufficient new stock | Original items restored in both DBs |
| Order delete with inventory unavailable | Order stays active, re-reserve compensation attempted |

---

## Production Considerations

1. **Outbox Pattern** — Replace `safeCompensate()` with an _outbox_ table and a background worker for guaranteed-delivery compensations.
2. **Distributed Tracing** — Add `X-Trace-Id` / `X-Correlation-Id` headers and integrate with OpenTelemetry.
3. **Circuit Breaker** — Wrap `InventoryServiceClient` with a circuit breaker (e.g., `ganesha/php-circuit-breaker`) to fail fast when the Inventory Service is degraded.
4. **Redis Idempotency** — Replace the DB idempotency table with Redis (`SET NX EX 86400`) for lower latency.
5. **Event Sourcing** — For complex multi-step Sagas, consider an event-sourced approach where each step emits a domain event consumed by the next service.
