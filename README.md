# Microservices CRUD Example

A reference implementation of a **multi-language, multi-database, event-driven microservices architecture** with full CRUD operations, transactional consistency, and cross-service data relationships.

---

## Architecture Overview

```
┌─────────────────────────────────────────────────────────────────────┐
│                         Client / API Consumer                        │
└────────────────────┬───────────────────────┬────────────────────────┘
                     │ HTTP                  │ HTTP
                     ▼                       ▼
┌────────────────────────────┐   ┌──────────────────────────────────┐
│   Product Service (A)      │   │   Inventory Service (B)          │
│   Laravel / PHP            │   │   Node.js / Express              │
│   MySQL (Relational DB)    │   │   MongoDB (Document DB)          │
│   Port: 8001               │   │   Port: 8002                     │
│                            │   │                                  │
│  - GET    /api/v1/products │   │  - GET    /api/v1/inventory      │
│  - POST   /api/v1/products │   │  - POST   /api/v1/inventory      │
│  - GET    /api/v1/products/│   │  - GET    /api/v1/inventory/:id  │
│            :id (+ inventory│   │  - PUT    /api/v1/inventory/:id  │
│            enrichment)     │   │  - DELETE /api/v1/inventory/:id  │
│  - PUT    /api/v1/products/│   │                                  │
│            :id             │   │  Subscribes to RabbitMQ:         │
│  - DELETE /api/v1/products/│   │  - product.created → init stock  │
│            :id             │   │  - product.updated → sync name   │
└────────────┬───────────────┘   │  - product.deleted → cascade del │
             │ Publish Events    └────────────┬─────────────────────┘
             │ (via Listeners)                │ Consume Events
             ▼                               ▲
┌───────────────────────────────────────────────────────────────────┐
│              RabbitMQ Message Broker (Topic Exchange)             │
│                                                                   │
│  Exchange: product_events (topic, durable)                        │
│  Routing Keys:                                                    │
│    product.created  ──► inventory_product_events queue            │
│    product.updated  ──► inventory_product_events queue            │
│    product.deleted  ──► inventory_product_events queue            │
└───────────────────────────────────────────────────────────────────┘
```

---

## Services

### Service A — Product Service (Laravel / PHP + MySQL)

- **Language**: PHP 8.2
- **Framework**: Laravel 10
- **Database**: MySQL 8.0 (relational, ACID-compliant)
- **Port**: `8001`

**Key Features:**
- Full CRUD with database transactions and automatic rollback on failure
- Domain Events (`ProductCreated`, `ProductUpdated`, `ProductDeleted`)
- Queued Listeners publish events to RabbitMQ after commit (async)
- Response enrichment: product endpoints return related inventory data fetched from Service B
- Soft-deletes for audit trail
- Request validation with form requests

---

### Service B — Inventory Service (Node.js / Express + MongoDB)

- **Language**: JavaScript (Node.js 20)
- **Framework**: Express 4
- **Database**: MongoDB 7 (document, schema-flexible)
- **Port**: `8002`

**Key Features:**
- Full CRUD for inventory records
- RabbitMQ consumer with exponential backoff reconnection
- Event handlers maintain cross-service data consistency:
  - `product.created` → creates initial inventory record
  - `product.updated` → syncs `product_name` / `product_sku` across all inventory records
  - `product.deleted` → cascade-deletes all related inventory records
- Idempotent event handling (safe to replay)
- Filtering by `product_name`, `product_id`, `warehouse_location`

---

## Cross-Service Relationship Handling

| Operation | Product Service | Inventory Service |
|-----------|----------------|-------------------|
| **Create Product** | `POST /api/v1/products` → DB transaction → `ProductCreated` event | Consumes `product.created` → creates inventory record |
| **Get Product with Inventory** | `GET /api/v1/products/:id` → HTTP call to Inventory Service | `GET /api/v1/inventory?product_name=X` returns related records |
| **Update Product Name** | `PUT /api/v1/products/:id` → DB transaction → `ProductUpdated` event (includes `previous_name`) | Consumes `product.updated` → `updateMany({product_id})` with new name |
| **Delete Product** | `DELETE /api/v1/products/:id` → soft-delete → `ProductDeleted` event | Consumes `product.deleted` → `deleteMany({product_id})` cascade |

### Data Consistency Strategy

1. **Within a service**: Full ACID database transactions (MySQL for Product Service).
2. **Across services**: Events are published to RabbitMQ **only after** the local transaction commits — ensuring other services only react to confirmed, consistent state.
3. **Failure handling**: If RabbitMQ publish fails, the queued listener retries. If the Inventory Service fails to process an event, it nacks the message (sends to Dead Letter Exchange for manual inspection).
4. **Idempotency**: Event handlers check for existing records before creating, preventing duplicates on message replay.

---

## Quick Start

### Prerequisites

- [Docker](https://docs.docker.com/get-docker/) and [Docker Compose](https://docs.docker.com/compose/)

### Start All Services

```bash
git clone https://github.com/kasunvimarshana/LaravelCRUDExample.git
cd LaravelCRUDExample

docker compose up --build
```

This starts:
- **MySQL** on port `3306`
- **MongoDB** on port `27017`
- **RabbitMQ** on port `5672` (Management UI: `http://localhost:15672`)
- **Product Service** on port `8001`
- **Inventory Service** on port `8002`

The Product Service will automatically run database migrations on startup.

---

## API Reference

### Product Service (`http://localhost:8001`)

#### List Products (with Inventory)
```http
GET /api/v1/products
```
Query params: `category`, `is_active`, `search`, `per_page`, `page`

**Response:**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "name": "Wireless Keyboard",
      "description": "Bluetooth wireless keyboard",
      "price": "49.99",
      "stock": 150,
      "sku": "WK-001",
      "category": "Electronics",
      "is_active": true,
      "inventory": [
        {
          "id": "...",
          "product_id": 1,
          "product_name": "Wireless Keyboard",
          "quantity": 150,
          "warehouse_location": "Main Warehouse",
          "available_quantity": 150,
          "needs_reorder": false
        }
      ]
    }
  ],
  "meta": { "current_page": 1, "per_page": 15, "total": 1, "last_page": 1 }
}
```

#### Create Product
```http
POST /api/v1/products
Content-Type: application/json

{
  "name": "USB-C Hub",
  "description": "7-in-1 USB-C hub",
  "price": 35.99,
  "stock": 200,
  "sku": "UCH-001",
  "category": "Electronics"
}
```

#### Get Product with Inventory
```http
GET /api/v1/products/1
```

#### Update Product
```http
PUT /api/v1/products/1
Content-Type: application/json

{
  "name": "USB-C Hub Pro",
  "price": 39.99
}
```
> This fires `ProductUpdated` → Inventory Service updates `product_name` in all inventory records.

#### Delete Product
```http
DELETE /api/v1/products/1
```
> This fires `ProductDeleted` → Inventory Service deletes all related inventory records.

---

### Inventory Service (`http://localhost:8002`)

#### List Inventory (Filter by Product Name)
```http
GET /api/v1/inventory?product_name=USB-C Hub
GET /api/v1/inventory?product_id=1
```

#### Create Inventory Record
```http
POST /api/v1/inventory
Content-Type: application/json

{
  "product_id": 1,
  "product_name": "USB-C Hub",
  "product_sku": "UCH-001",
  "quantity": 200,
  "warehouse_location": "Warehouse B",
  "reorder_threshold": 20
}
```

#### Update Inventory
```http
PUT /api/v1/inventory/:id
Content-Type: application/json

{
  "quantity": 150,
  "notes": "Partial stock used"
}
```

#### Delete Inventory Record
```http
DELETE /api/v1/inventory/:id
```

---

## RabbitMQ Event Payloads

### product.created
```json
{
  "event": "product.created",
  "product_id": 1,
  "name": "USB-C Hub",
  "sku": "UCH-001",
  "price": 35.99,
  "stock": 200,
  "category": "Electronics",
  "is_active": true,
  "timestamp": "2024-01-01T00:00:00+00:00"
}
```

### product.updated
```json
{
  "event": "product.updated",
  "product_id": 1,
  "name": "USB-C Hub Pro",
  "sku": "UCH-001",
  "price": 39.99,
  "previous_name": "USB-C Hub",
  "previous_sku": "UCH-001",
  "timestamp": "2024-01-01T00:00:00+00:00"
}
```

### product.deleted
```json
{
  "event": "product.deleted",
  "product_id": 1,
  "name": "USB-C Hub Pro",
  "sku": "UCH-001",
  "timestamp": "2024-01-01T00:00:00+00:00"
}
```

---

## Adding a New Service in Any Language

To add a third service (e.g., Python Analytics Service), simply:

1. Connect to RabbitMQ using any AMQP client library
2. Declare the same exchange: `product_events` (topic, durable)
3. Create a queue and bind with desired routing keys (e.g., `product.*`)
4. Process incoming events

**Python example (pika):**
```python
import pika, json

connection = pika.BlockingConnection(pika.URLParameters('amqp://guest:guest@localhost:5672/'))
channel = connection.channel()
channel.exchange_declare(exchange='product_events', exchange_type='topic', durable=True)
result = channel.queue_declare(queue='analytics_queue', durable=True)
channel.queue_bind(exchange='product_events', queue='analytics_queue', routing_key='product.*')

def callback(ch, method, properties, body):
    event = json.loads(body)
    print(f"Analytics: received {event['event']} for product {event['product_id']}")
    ch.basic_ack(delivery_tag=method.delivery_tag)

channel.basic_consume(queue='analytics_queue', on_message_callback=callback)
channel.start_consuming()
```

---

## Running Tests

### Product Service (PHP/PHPUnit)

```bash
cd product-service
composer install
php artisan test
```

### Inventory Service (Node.js/Jest)

```bash
cd inventory-service
npm install
npm test
```

---

## Project Structure

```
.
├── docker-compose.yml                 # Orchestrates all services
│
├── product-service/                   # Laravel / PHP / MySQL
│   ├── app/
│   │   ├── Events/
│   │   │   ├── ProductCreated.php
│   │   │   ├── ProductUpdated.php
│   │   │   └── ProductDeleted.php
│   │   ├── Listeners/
│   │   │   ├── PublishProductCreatedEvent.php
│   │   │   ├── PublishProductUpdatedEvent.php
│   │   │   └── PublishProductDeletedEvent.php
│   │   ├── Http/Controllers/
│   │   │   └── ProductController.php
│   │   ├── Http/Requests/
│   │   │   ├── StoreProductRequest.php
│   │   │   └── UpdateProductRequest.php
│   │   ├── Models/
│   │   │   └── Product.php
│   │   ├── Services/
│   │   │   ├── ProductService.php     # Business logic + DB transactions
│   │   │   └── RabbitMQService.php    # Message broker integration
│   │   └── Providers/
│   │       ├── AppServiceProvider.php
│   │       └── EventServiceProvider.php  # Event → Listener mapping
│   ├── database/
│   │   ├── migrations/
│   │   │   └── *_create_products_table.php
│   │   ├── factories/ProductFactory.php
│   │   └── seeders/DatabaseSeeder.php
│   ├── routes/api.php
│   ├── tests/Feature/ProductControllerTest.php
│   └── Dockerfile
│
└── inventory-service/                 # Node.js / Express / MongoDB
    ├── src/
    │   ├── app.js                     # Express application entry point
    │   ├── controllers/
    │   │   └── inventoryController.js  # CRUD handlers
    │   ├── models/
    │   │   └── Inventory.js           # Mongoose schema
    │   ├── routes/
    │   │   └── inventoryRoutes.js
    │   ├── events/
    │   │   └── productEventConsumer.js # RabbitMQ consumer
    │   └── middleware/
    │       └── logger.js
    ├── tests/
    │   ├── inventory.test.js           # API integration tests
    │   └── productEventConsumer.test.js # Event handler unit tests
    ├── package.json
    └── Dockerfile
```

---

## Design Principles

1. **Single Responsibility**: Each service owns its domain (products vs. inventory).
2. **Database per Service**: MySQL for relational product data; MongoDB for flexible inventory records.
3. **Event-Driven Communication**: Services communicate via RabbitMQ events, not direct calls.
4. **Eventual Consistency**: Inventory data is updated asynchronously after product events.
5. **Fault Tolerance**: Queued listeners with retries; consumer reconnects with backoff; graceful fallback in API responses when Inventory Service is unavailable.
6. **Idempotency**: Event handlers are safe to replay without creating duplicates.