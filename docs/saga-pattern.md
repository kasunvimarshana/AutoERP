# Saga Pattern – Order Processing

## Overview

This project implements the **Orchestration-based Saga pattern** to manage distributed transactions
across the Order, Inventory, Payment, and Notification microservices. Because each service owns
its own database, a single ACID transaction spanning all services is impossible; the Saga pattern
provides atomicity through a sequence of local transactions with **compensating transactions** for
rollback.

---

## What Is a Saga?

A Saga is a sequence of local transactions where each step publishes events or makes synchronous
calls that trigger the next step. If any step fails, previously completed steps are *compensated*
(undone) in reverse order.

```
Saga = T1 → T2 → T3 → ... → Tn
Compensation = C(n-1) → ... → C2 → C1   (if Tk fails)
```

---

## Choreography vs. Orchestration

| Approach        | Description                                                     |
|-----------------|-----------------------------------------------------------------|
| **Orchestration** | A central coordinator (orchestrator) directs each service.    |
| **Choreography**  | Each service reacts to events independently; no central brain. |

This project uses **orchestration**: the `OrderSagaOrchestrator` inside the Order Service is the
central coordinator. It calls downstream services (Inventory, Payment, Notification) in sequence
via synchronous HTTP, handles failures, and issues compensation calls directly. This makes the
transaction flow explicit and easy to reason about.

---

## Order Processing Saga Steps

### Happy Path

```
┌─────────────┐    ┌──────────────────┐    ┌─────────────────┐    ┌──────────────────────┐
│ Order Svc   │    │  Inventory Svc   │    │  Payment Svc    │    │  Notification Svc    │
└──────┬──────┘    └────────┬─────────┘    └────────┬────────┘    └──────────┬───────────┘
       │                    │                       │                        │
       │ 1. Create Order    │                       │                        │
       │ (status=pending)   │                       │                        │
       │                    │                       │                        │
       │ 2. POST /reserve   │                       │                        │
       │───────────────────►│                       │                        │
       │                    │ Decrement stock        │                        │
       │                    │ Store reservation      │                        │
       │◄── 200 OK ─────────│                       │                        │
       │ (status=inventory_reserved)                 │                        │
       │                    │                       │                        │
       │ 3. POST /charge    │                       │                        │
       │───────────────────────────────────────────►│                        │
       │                    │                       │ Process payment         │
       │                    │                       │ Store payment record    │
       │◄────────────────────────── 200 OK ─────────│                        │
       │ (status=confirmed, saga_status=completed)   │                        │
       │                    │                       │                        │
       │ 4. POST /send      │                       │                        │
       │──────────────────────────────────────────────────────────────────►  │
       │                    │                       │                        │ Check Redis
       │                    │                       │                        │ Send email
       │                    │                       │                        │ Mark sent
       │◄─────────────────────────────────────────────────────── 200 OK ────│
```

### Failure: Inventory Unavailable

```
┌─────────────┐    ┌──────────────────┐
│ Order Svc   │    │  Inventory Svc   │
└──────┬──────┘    └────────┬─────────┘
       │                    │
       │ POST /reserve       │
       │───────────────────►│
       │                    │ Insufficient stock
       │◄── 409 Conflict ───│
       │                    │
       │ failOrder()
       │ status = 'cancelled'
       │ saga_status = 'compensated'
       │
       │ POST /notifications/send (event=order_failed)
```

### Failure: Payment Declined

```
┌─────────────┐    ┌──────────────────┐    ┌─────────────────┐
│ Order Svc   │    │  Inventory Svc   │    │  Payment Svc    │
└──────┬──────┘    └────────┬─────────┘    └────────┬────────┘
       │                    │                       │
       │ POST /reserve ─────►                       │
       │◄── 200 OK ──────────                       │
       │                    │                       │
       │ POST /charge ──────────────────────────────►
       │                    │                       │ Payment declined (amount % 10 == 7)
       │◄───────────────────────────── 402 ─────────│
       │                    │                       │
       │ compensateInventory()
       │ POST /release ──────►
       │                    │ Re-add stock
       │◄── 200 OK ──────────│
       │
       │ failOrder()
       │ status = 'payment_failed'
       │ saga_status = 'compensated'
       │
       │ POST /notifications/send (event=order_failed, reason=payment_failed)
```

### Customer Cancellation (Post-Confirmation)

```
┌─────────────┐    ┌──────────────────┐    ┌─────────────────┐
│ Order Svc   │    │  Inventory Svc   │    │  Payment Svc    │
└──────┬──────┘    └────────┬─────────┘    └────────┬────────┘
       │                    │                       │
       │ compensateOrderSaga()
       │ status = 'compensating'
       │                    │                       │
       │ POST /release ──────►                       │
       │◄── 200 OK ──────────│                       │
       │                    │                       │
       │ POST /{id}/refund ──────────────────────────►
       │◄────────────────────────── 200 OK ──────────│
       │
       │ failOrder(reason=customer_cancellation)
```

---

## Saga State Machine

```
                  ┌─────────┐
                  │ PENDING │
                  └────┬────┘
                       │ startOrderSaga()
                       │ reserveInventory() called
                       ▼
           ┌────────────────────────┐
           │  INVENTORY_RESERVING   │
           └──────────┬─────────────┘
                      │ POST /inventory/reserve → 200 OK
                      ▼
           ┌───────────────────────┐
           │  INVENTORY_RESERVED   │
           └──────────┬────────────┘
                      │ processPayment() called
                      ▼
           ┌───────────────────────┐
           │  PAYMENT_PROCESSING   │
           └──────────┬────────────┘
                      │
              ┌───────┴────────┐
              ▼                ▼
        ┌──────────┐    ┌─────────────┐
        │CONFIRMED │    │COMPENSATING │
        └────┬─────┘    └──────┬──────┘
             │                 │
             │ (customer       │ compensateInventory()
             │  cancellation)  │ failOrder()
             └────────►────────┘
                               ▼
                    ┌──────────────────┐
                    │ PAYMENT_FAILED   │
                    │ or CANCELLED     │
                    └──────────────────┘
```

---

## Saga Status vs Order Status

| `saga_status`  | Meaning                                           |
|---------------|---------------------------------------------------|
| `started`     | Saga initiated, steps in progress                 |
| `completed`   | All steps succeeded, order confirmed              |
| `failed`      | A step failed, compensation in progress           |
| `compensated` | Compensation complete, saga rolled back           |

| `status`               | Meaning                                       |
|------------------------|-----------------------------------------------|
| `pending`              | Order created, saga not yet started           |
| `payment_processing`   | Inventory reservation in progress             |
| `inventory_reserved`   | Inventory reserved, payment about to process  |
| `confirmed`            | All steps succeeded                           |
| `compensating`         | Rollback in progress                          |
| `payment_failed`       | Payment was declined (compensation complete)  |
| `cancelled`            | Order cancelled (inventory fail or manual)    |
| `compensated`          | Fully rolled back                             |

---

## Idempotency

Each saga step is designed to be **idempotent**:

- **Payment Charge**: Checks `order_id` before creating a new payment record. Returns existing
  result if already processed.
- **Inventory Reserve**: Checks existing reservations before decrementing stock.
- **Notifications**: Redis key `notif:{order_id}:{event}` with 24-hour TTL prevents duplicate emails.

---

## Implementation Reference

### Order Service – `OrderSagaOrchestrator`

```php
// Start the saga (Step 1)
public function startOrderSaga(array $data): Order
{
    $order = Order::create([...$data, 'status' => 'pending', 'saga_status' => 'started']);
    $this->reserveInventory($order);  // Step 2
    return $order->fresh();
}

// Step 2 – Reserve inventory
private function reserveInventory(Order $order): void
{
    try {
        $this->http->post('.../api/inventory/reserve', [...]);
        $order->update(['status' => 'inventory_reserved']);
        $this->processPayment($order);  // Step 3
    } catch (GuzzleException $e) {
        $this->failOrder($order, 'inventory_reservation_failed');  // Compensate
    }
}

// Step 3 – Process payment
private function processPayment(Order $order): void
{
    try {
        $response = $this->http->post('.../api/payments/charge', [...]);
        $order->update(['status' => 'confirmed', 'saga_status' => 'completed']);
        $this->sendNotification($order, 'order_confirmed');  // Step 4
    } catch (GuzzleException $e) {
        $this->compensateInventory($order, 'payment_failed');  // Compensation
    }
}

// Compensation – Release inventory
private function compensateInventory(Order $order, string $reason): void
{
    $order->update(['status' => 'compensating', 'saga_status' => 'failed']);
    $this->http->post('.../api/inventory/release', [...]);
    $this->failOrder($order, $reason);
}
```

### Inventory Service – Optimistic Locking

```python
# Atomic decrement using MongoDB conditional update
await db.products.update_one(
    {"sku": item.sku, "quantity": {"$gte": item.quantity}},  # Only if sufficient stock
    {"$inc": {"quantity": -item.quantity}},
)
```

### Notification Service – Redis Deduplication

```javascript
const idempotencyKey = `notif:${data.order_id}:${data.event}`;
const alreadySent = await redisClient.get(idempotencyKey);
if (alreadySent) return { status: 'deduplicated' };
// ... send email ...
await redisClient.setEx(idempotencyKey, 86400, 'sent');
```

---

## Failure Scenarios Summary

| Failure Point            | Compensation Actions                                | Final Status         |
|--------------------------|-----------------------------------------------------|----------------------|
| Inventory unavailable    | None (no state changed yet)                         | `cancelled`          |
| Payment declined         | Release inventory reservation                       | `payment_failed`     |
| Customer cancellation    | Release inventory + refund payment (if charged)     | `cancelled`          |
| Notification failure     | Log warning, continue (non-critical)                | `confirmed` / failed |
| Inventory release fails  | Log error (manual intervention required)            | `compensating`       |

---

## Production Considerations

1. **Outbox Pattern**: In production, use the Transactional Outbox pattern to reliably publish
   events after local DB commit, preventing lost messages on crash.

2. **Timeouts & Retries**: Configure exponential backoff with jitter for HTTP calls between
   services. Consider circuit breakers (e.g., `php-circuit-breaker`).

3. **Dead Letter Queue**: Configure RabbitMQ DLQ for messages that fail processing after
   max retries, enabling manual inspection and reprocessing.

4. **Saga Log**: Store a dedicated `saga_events` table/collection for full audit trail of
   each saga step, enabling replay and debugging.

5. **Distributed Tracing**: Propagate `X-Correlation-ID` through all services and integrate
   with OpenTelemetry / Jaeger for end-to-end request tracing.
