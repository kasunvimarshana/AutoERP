# Event Schemas – KV MultiTenant SaaS

This directory documents the event contracts used for asynchronous messaging via RabbitMQ.

## Exchange Configuration

| Exchange      | Type  | Durable | Description                    |
|---------------|-------|---------|--------------------------------|
| `order.events`| topic | yes     | All order lifecycle events     |

## Routing Keys

| Routing Key            | Publisher         | Consumer(s)          |
|------------------------|-------------------|----------------------|
| `order.confirmed`      | Order Service     | Notification Service |
| `order.failed`         | Order Service     | Notification Service |
| `order.cancelled`      | Order Service     | Notification Service |
| `payment.processed`    | Payment Service   | Order Service        |
| `payment.failed`       | Payment Service   | Order Service        |
| `inventory.reserved`   | Inventory Service | Order Service        |
| `inventory.released`   | Inventory Service | —                    |

## Event Schemas

### `order.confirmed`

```json
{
  "order_id":    "550e8400-e29b-41d4-a716-446655440000",
  "saga_id":     "saga-7f3e4a2b-...",
  "tenant_id":   "tenant-abc",
  "customer_id": "cust-001",
  "amount":      1589.98,
  "currency":    "USD",
  "items": [
    { "sku": "LAPTOP-001", "quantity": 1, "price": 1499.99 }
  ],
  "timestamp": "2024-01-15T09:30:05Z"
}
```

### `order.failed`

```json
{
  "order_id":    "550e8400-e29b-41d4-a716-446655440000",
  "saga_id":     "saga-7f3e4a2b-...",
  "tenant_id":   "tenant-abc",
  "customer_id": "cust-001",
  "reason":      "payment_failed",
  "timestamp":   "2024-01-15T09:30:05Z"
}
```

### `payment.processed`

```json
{
  "payment_id":          "9a1b2c3d-e5f6-7890-abcd-ef1234567890",
  "order_id":            "550e8400-...",
  "saga_id":             "saga-7f3e4a2b-...",
  "amount":              1589.98,
  "currency":            "USD",
  "provider_reference":  "PAY-ABCDEF123456",
  "timestamp":           "2024-01-15T09:30:03Z"
}
```

### `payment.failed`

```json
{
  "payment_id": "9a1b2c3d-...",
  "order_id":   "550e8400-...",
  "saga_id":    "saga-7f3e4a2b-...",
  "reason":     "insufficient_funds",
  "timestamp":  "2024-01-15T09:30:03Z"
}
```

### `inventory.reserved`

```json
{
  "order_id":  "550e8400-...",
  "saga_id":   "saga-7f3e4a2b-...",
  "tenant_id": "tenant-abc",
  "items": [
    { "sku": "LAPTOP-001", "quantity": 1 }
  ],
  "timestamp": "2024-01-15T09:30:01Z"
}
```

### `inventory.released`

```json
{
  "order_id":  "550e8400-...",
  "saga_id":   "saga-7f3e4a2b-...",
  "tenant_id": "tenant-abc",
  "reason":    "payment_failed",
  "timestamp": "2024-01-15T09:30:04Z"
}
```

## Queue Bindings

| Queue                  | Exchange       | Binding Key  | Consumer             |
|------------------------|----------------|--------------|----------------------|
| `notification.queue`   | `order.events` | `order.*`    | Notification Service |
| `order.payment.queue`  | `order.events` | `payment.*`  | Order Service        |
| `order.inventory.queue`| `order.events` | `inventory.*`| Order Service        |

## Message Durability

All queues and exchanges are declared with `durable: true`. Messages are published as
persistent to survive broker restarts. Consumers must `ack` only after successful processing.
On failure, messages are `nack`'d without requeue, routing to the Dead Letter Exchange (DLX)
for manual inspection.
