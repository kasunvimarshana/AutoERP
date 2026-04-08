# Microservices CRUD – Multi-Language, Event-Driven Reference Implementation

> **Service A** – Product Service (Laravel 11 · PHP 8.3 · MySQL 8)  
> **Service B** – Inventory Service (Node.js 20 · Express · MongoDB 7)  
> **Message Broker** – RabbitMQ 3.13 (fanout exchange, durable queues)

---

## Table of Contents

1. [Architecture Overview](#architecture-overview)
2. [Directory Structure](#directory-structure)
3. [How Cross-Service Communication Works](#how-cross-service-communication-works)
4. [Transaction & Rollback Strategy](#transaction--rollback-strategy)
5. [Quick Start](#quick-start)
6. [API Reference – Product Service](#api-reference--product-service-port-8000)
7. [API Reference – Inventory Service](#api-reference--inventory-service-port-3000)
8. [Event Contracts (RabbitMQ)](#event-contracts-rabbitmq)
9. [Adding a New Service (Java / Python / Go)](#adding-a-new-service)
10. [Design Decisions](#design-decisions)

---

## Architecture Overview

```
┌──────────────────────────────────────────────────────────────────────┐
│                          API Consumers                               │
│                     (mobile, web, other services)                    │
└───────────────────────┬───────────────────────┬──────────────────────┘
                        │ HTTP                  │ HTTP
             ┌──────────▼──────────┐   ┌────────▼────────────┐
             │   Product Service   │   │  Inventory Service  │
             │  Laravel 11 / PHP   │◄──│  Node.js / Express  │
             │  MySQL 8 (InnoDB)   │   │  MongoDB 7          │
             └──────────┬──────────┘   └────────┬────────────┘
                        │ publish               │ subscribe
                        │                       │
             ┌──────────▼───────────────────────▼────────────┐
             │              RabbitMQ 3.13                     │
             │  Exchange: product_events (fanout, durable)    │
             │                                                │
             │  Queues:                                       │
             │   • inventory_service_queue   (Node.js)        │
             │   • [your_service]_queue      (Java/Python/Go) │
             └────────────────────────────────────────────────┘
```

### Data Flow – Create Product

```
Client
  │ POST /api/products
  ▼
ProductController
  │ validates request
  ▼
ProductService::create()
  │ DB::transaction { Product::create() }   ← MySQL commit
  │ event(new ProductCreated($product))
  ▼
PublishProductCreated (queued listener)
  │ RabbitMQPublisher::publish("product.created", payload)
  ▼
RabbitMQ fanout exchange
  ├──► inventory_service_queue
  │      RabbitMQConsumer::onProductCreated()
  │      Inventory::save()  ← MongoDB insert (zero-stock record)
  │
  └──► any_other_service_queue  (future services)
```

---

## Directory Structure

```
microservices-demo/
├── docker-compose.yml            # Orchestrates all services + infrastructure
│
├── product-service/              # Service A – Laravel (PHP/MySQL)
│   ├── app/
│   │   ├── Events/
│   │   │   ├── ProductCreated.php
│   │   │   ├── ProductUpdated.php
│   │   │   └── ProductDeleted.php
│   │   ├── Listeners/
│   │   │   └── ProductEventListeners.php  (3 queued listeners)
│   │   ├── Http/Controllers/
│   │   │   └── ProductController.php
│   │   ├── Http/Requests/
│   │   │   ├── StoreProductRequest.php
│   │   │   └── UpdateProductRequest.php
│   │   ├── Models/
│   │   │   └── Product.php
│   │   ├── Services/
│   │   │   ├── ProductService.php          ← business logic + transactions
│   │   │   ├── RabbitMQPublisher.php       ← AMQP publisher
│   │   │   └── InventoryServiceClient.php  ← HTTP client for reads
│   │   └── Providers/
│   │       └── EventServiceProvider.php    ← event→listener wiring
│   ├── database/migrations/
│   ├── routes/api.php
│   └── Dockerfile
│
├── inventory-service/            # Service B – Node.js/Express/MongoDB
│   ├── src/
│   │   ├── server.js             ← bootstrap (Express + Mongoose + Consumer)
│   │   ├── models/
│   │   │   └── inventory.model.js
│   │   ├── controllers/
│   │   │   └── inventory.controller.js
│   │   ├── routes/
│   │   │   └── inventory.routes.js
│   │   ├── events/
│   │   │   └── rabbitmq.consumer.js  ← event-driven handler
│   │   └── config/
│   │       └── logger.js
│   └── Dockerfile
│
└── shared/
    └── contracts/                # Event schema docs (language-agnostic)
```

---

## How Cross-Service Communication Works

### Writes – Event-Driven (Async via RabbitMQ)

| Operation | Publisher | Event | Subscriber Action |
|-----------|-----------|-------|-------------------|
| Create product | Product Service | `product.created` | Inventory creates zero-stock record |
| Update product | Product Service | `product.updated` | Inventory syncs `productName`; migrates SKU if renamed |
| Delete product | Product Service | `product.deleted` | Inventory soft-deletes all records for that SKU |

**Why async?** Services are deployed independently. A synchronous HTTP call would couple availability – if Inventory is down, creating a product would fail. With RabbitMQ, the message is durable and delivered when the consumer recovers.

### Reads – Synchronous HTTP (Composite Response)

When `GET /api/products` is called, Product Service:
1. Fetches all products from MySQL in one query.
2. Calls `POST /inventory/batch` on Inventory Service with all SKUs.
3. Merges inventory records into each product response.

This avoids N+1 HTTP calls.

---

## Transaction & Rollback Strategy

### Local Transactions (MySQL / MongoDB)

- **Product Service**: Every write uses `DB::transaction()`. If any step fails, MySQL rolls back the entire operation. The RabbitMQ publish happens **after** the transaction commits, so no phantom events are ever published.

- **Inventory Service**: MongoDB sessions provide atomic single-document operations. Multi-document updates use `updateMany` which MongoDB executes as a bulk operation.

### Cross-Service Saga (Compensating Transactions)

Because there is no distributed 2PC (two-phase commit), we use the **Saga pattern**:

```
1. Product Service commits to MySQL ✓
2. Publishes product.created to RabbitMQ ✓
3. Inventory Service receives event, inserts MongoDB record ✓

IF step 3 fails:
   • Message is nack'd → routed to dead-letter queue
   • Ops team retries or replays from DLQ
   • A reconciliation job can compare MySQL ↔ MongoDB and re-emit events

IF step 2 fails (broker down):
   • Product record is committed but event not sent
   • Outbox Pattern (recommended for production): store event in a DB table
     inside the same transaction, then a poller sends it to the broker
```

### Dead-Letter Queue

Failed inventory messages go to `inventory_dead_letter` queue bound to the `product_events_dlx` exchange. Inspect via RabbitMQ Management UI at `http://localhost:15672`.

---

## Quick Start

### Prerequisites

- Docker & Docker Compose v2+

### Run

```bash
git clone <repo>
cd microservices-demo

# Copy env files
cp product-service/.env.example product-service/.env
cp inventory-service/.env.example inventory-service/.env

# Start everything
docker compose up --build
```

Services will be available at:

| Service | URL |
|---------|-----|
| Product Service API | http://localhost:8000/api |
| Inventory Service API | http://localhost:3000/inventory |
| RabbitMQ Management | http://localhost:15672 (guest/guest) |

---

## API Reference – Product Service (port 8000)

All endpoints return:
```json
{ "success": true, "message": "...", "data": { ... } }
```

### `GET /api/products`

List all products with inventory.

**Query params:** `name`, `category`, `is_active`

**Response:**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "name": "Wireless Keyboard",
      "sku": "KB-001",
      "price": "49.99",
      "category": "Electronics",
      "is_active": true,
      "inventory": [
        {
          "_id": "...",
          "sku": "KB-001",
          "productName": "Wireless Keyboard",
          "quantity": 150,
          "warehouseId": "WH-EAST",
          "status": "in_stock"
        }
      ]
    }
  ]
}
```

### `POST /api/products`

Create a product (triggers `product.created` event).

**Body:**
```json
{
  "name": "Gaming Mouse",
  "sku": "GM-010",
  "description": "High-DPI gaming mouse",
  "price": 59.99,
  "category": "Electronics",
  "is_active": true
}
```

### `GET /api/products/{id}`

Fetch a single product with inventory.

### `PUT /api/products/{id}`

Update a product (triggers `product.updated` event → Inventory syncs).

**Body:** (any subset of fields)
```json
{ "price": 54.99, "name": "Gaming Mouse Pro" }
```

### `DELETE /api/products/{id}`

Soft-delete product (triggers `product.deleted` event → Inventory soft-deletes all records for SKU).

---

## API Reference – Inventory Service (port 3000)

### `GET /inventory`

**Query params:** `sku`, `productName`, `status`, `warehouseId`, `page`, `limit`

Filter by product name example:
```
GET /inventory?productName=Keyboard
```

### `POST /inventory`

Manually create a stock record (usually created automatically via event).

```json
{
  "sku": "KB-001",
  "productName": "Wireless Keyboard",
  "quantity": 100,
  "warehouseId": "WH-WEST",
  "location": "A-12-3"
}
```

### `GET /inventory/:id`        Single record
### `PUT /inventory/:id`        Update by document ID
### `DELETE /inventory/:id`     Soft-delete by document ID

### `POST /inventory/batch`     (Internal – called by Product Service)

```json
{ "skus": ["KB-001", "HB-002", "DS-003"] }
```

### `PUT /inventory/by-sku/:sku`

Update all stock records for a SKU (e.g. after Product Service renames a product).

```json
{ "quantity": 200, "warehouseId": "WH-EAST" }
```

---

## Event Contracts (RabbitMQ)

All messages are JSON with this envelope:

```json
{
  "event_type": "product.created",
  "occurred_at": "2024-06-01T12:00:00.000Z",
  "payload": { ... }
}
```

### `product.created`
```json
{
  "id": 1, "name": "Gaming Mouse", "sku": "GM-010",
  "description": "...", "price": 59.99,
  "category": "Electronics", "is_active": true
}
```

### `product.updated`
```json
{
  "current":  { "id": 1, "name": "Gaming Mouse Pro", "sku": "GM-010", ... },
  "previous": { "id": 1, "name": "Gaming Mouse",     "sku": "GM-010", ... }
}
```

### `product.deleted`
```json
{ "id": 1, "name": "Gaming Mouse", "sku": "GM-010", ... }
```

---

## Adding a New Service

To add a Python, Java, or Go service:

### 1. Bind a queue to the fanout exchange

**Python (pika):**
```python
channel.exchange_declare('product_events', 'fanout', durable=True)
result = channel.queue_declare('my_python_service_queue', durable=True)
channel.queue_bind(result.method.queue, 'product_events', '')
```

**Java (Spring AMQP):**
```java
@Bean
FanoutExchange productEvents() { return new FanoutExchange("product_events", true, false); }

@Bean
Queue myQueue() { return new Queue("my_java_service_queue", true); }

@Bean
Binding binding(Queue myQueue, FanoutExchange productEvents) {
    return BindingBuilder.bind(myQueue).to(productEvents);
}
```

### 2. Handle events

Parse the JSON envelope, switch on `event_type`, and react accordingly. The schema is documented in [Event Contracts](#event-contracts-rabbitmq).

### 3. Add your service to `docker-compose.yml`

```yaml
my-python-service:
  build: ./my-python-service
  environment:
    RABBITMQ_URL: amqp://guest:guest@rabbitmq:5672
    RABBITMQ_EXCHANGE: product_events
  depends_on:
    rabbitmq:
      condition: service_healthy
```

---

## Design Decisions

| Decision | Choice | Rationale |
|----------|--------|-----------|
| Exchange type | Fanout | All services get all events; filter client-side. Adding a new subscriber requires zero topology changes. |
| Event dispatch timing | After DB commit | Prevents ghost events if the transaction rolls back. |
| Listener queue mode | `ShouldQueue` (async) | Broker latency never slows HTTP responses. |
| Cross-service reads | HTTP (synchronous) | Reads need real-time data; eventual consistency acceptable for reads from other services, not for compositing the response. |
| Soft-delete | Both services | Allows data recovery, audit trails, and re-event replay without re-seeding. |
| Denormalised `productName` in MongoDB | Yes | Avoids cross-service joins for inventory filtering by name. Kept in sync via `product.updated` event. |
| SKU as cross-service key | Yes | SKU is a business-meaningful, stable identifier. Numeric IDs are internal implementation details. |
