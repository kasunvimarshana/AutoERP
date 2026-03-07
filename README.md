# LaravelCRUD – Microservices CRUD with Distributed Transactions

A practical example of **Laravel microservices** demonstrating how to perform CRUD operations across two independent services while maintaining data consistency using the **Saga pattern** (compensating transactions).

---

## Architecture Overview

```
┌──────────────────────────────────────────────────────┐
│                   Client / API Consumer               │
└──────────────────┬───────────────────────────────────┘
                   │ HTTP
                   ▼
┌─────────────────────────────────────┐
│          Order Service (port 8001)  │
│  ─────────────────────────────────  │
│  • Orders CRUD                      │
│  • Distributed transaction          │
│    orchestration (Saga)             │
└────────────────┬────────────────────┘
                 │ HTTP (internal)
                 ▼
┌─────────────────────────────────────┐
│       Inventory Service (port 8000) │
│  ─────────────────────────────────  │
│  • Inventory CRUD                   │
│  • Reserve / Release / Fulfill API  │
└─────────────────────────────────────┘
```

### Services

| Service | Port | Database | Responsibility |
|---------|------|----------|----------------|
| `inventory-service` | 8000 | SQLite | Manages products, stock levels, reservations |
| `order-service` | 8001 | SQLite | Manages customer orders, coordinates inventory |

---

## Distributed Transaction Strategy: Saga Pattern

This project demonstrates the **Saga pattern** for distributed transactions. True ACID transactions are impossible across microservice boundaries, so each mutating operation uses **compensating transactions** to maintain consistency.

### Create Order Flow

```
Order Service                       Inventory Service
     │                                     │
     │  1. BEGIN local DB transaction      │
     │──── POST /api/inventories/reserve ──►│
     │                                     │  2. Reserve stock
     │◄── 200 OK ──────────────────────────│
     │  3. INSERT order record             │
     │  4. COMMIT local transaction        │
     │                                     │
     │         ── If step 2 fails ──       │
     │◄── 409/503 ─────────────────────────│
     │  ROLLBACK local transaction         │
     │  Return error to client             │
     │                                     │
     │      ── If step 3/4 fails ──        │
     │  ROLLBACK local transaction         │
     │──── POST /api/inventories/release ──►│  (compensating transaction)
     │◄── 200 OK ──────────────────────────│  5. Release reserved stock
     │  Return error to client             │
```

### Delete Order Flow (Compensating Transaction)

```
Order Service                       Inventory Service
     │                                     │
     │  1. BEGIN local DB transaction      │
     │──── POST /api/inventories/release ──►│
     │                                     │  2. Release reservation
     │◄── 200 OK ──────────────────────────│
     │  3. DELETE order record             │
     │  4. COMMIT local transaction        │
```

---

## Getting Started

### Prerequisites

- PHP 8.2+
- Composer 2.x
- Docker & Docker Compose (optional, for containerized setup)

### Option 1: Run with Docker Compose

```bash
docker-compose up --build
```

Both services will start automatically:
- Inventory Service: http://localhost:8000
- Order Service: http://localhost:8001

### Option 2: Run Locally

**Set up Inventory Service:**

```bash
cd inventory-service
cp .env.example .env
php artisan key:generate
php artisan migrate
php artisan serve --port=8000
```

**Set up Order Service (in a new terminal):**

```bash
cd order-service
cp .env.example .env
# Set INVENTORY_SERVICE_URL=http://localhost:8000 in .env
php artisan key:generate
php artisan migrate
php artisan serve --port=8001
```

---

## API Reference

### Inventory Service (port 8000)

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/inventories` | List all inventory items |
| POST | `/api/inventories` | Create inventory item |
| GET | `/api/inventories/{id}` | Get inventory item |
| PUT | `/api/inventories/{id}` | Update inventory item |
| DELETE | `/api/inventories/{id}` | Delete inventory item |
| POST | `/api/inventories/reserve` | Reserve stock for an order |
| POST | `/api/inventories/release` | Release reservation (compensating transaction) |
| POST | `/api/inventories/fulfill` | Fulfill stock for completed order |

### Order Service (port 8001)

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/orders` | List all orders |
| POST | `/api/orders` | Create order (reserves inventory) |
| GET | `/api/orders/{id}` | Get order |
| PUT | `/api/orders/{id}` | Update order (adjusts inventory reservation) |
| DELETE | `/api/orders/{id}` | Delete order (releases inventory reservation) |
| POST | `/api/orders/{id}/confirm` | Confirm order (fulfills inventory stock) |

---

## Example Requests

### 1. Create an inventory item

```bash
curl -X POST http://localhost:8000/api/inventories \
  -H "Content-Type: application/json" \
  -d '{
    "product_name": "Laptop Pro 15",
    "sku": "LAP-PRO-15",
    "quantity": 100,
    "unit_price": 1299.99
  }'
```

### 2. Create an order (triggers inventory reservation)

```bash
curl -X POST http://localhost:8001/api/orders \
  -H "Content-Type: application/json" \
  -d '{
    "customer_name": "Jane Doe",
    "customer_email": "jane@example.com",
    "product_sku": "LAP-PRO-15",
    "product_name": "Laptop Pro 15",
    "quantity": 2,
    "unit_price": 1299.99
  }'
```

**On success**, inventory is reserved and the order is created in a single coordinated operation.

**On failure** (e.g., insufficient stock), the order is NOT created and inventory is NOT affected.

### 3. Confirm an order (fulfills inventory)

```bash
curl -X POST http://localhost:8001/api/orders/1/confirm
```

### 4. Delete an order (releases inventory reservation)

```bash
curl -X DELETE http://localhost:8001/api/orders/1
```

---

## Running Tests

### Inventory Service

```bash
cd inventory-service
php artisan test
```

### Order Service

```bash
cd order-service
php artisan test
```

Tests cover:
- Full CRUD operations for both services
- Distributed transaction rollback when Inventory Service fails
- Compensating transaction execution on local failure
- Inventory adjustment on order quantity changes
- Order lifecycle state transitions (pending → confirmed)

---

## Project Structure

```
LaravelCRUD/
├── docker-compose.yml
├── inventory-service/                # Inventory microservice
│   ├── app/
│   │   ├── Http/Controllers/
│   │   │   └── InventoryController.php   # CRUD + reserve/release/fulfill
│   │   └── Models/
│   │       └── Inventory.php
│   ├── database/migrations/
│   │   └── *_create_inventories_table.php
│   ├── routes/api.php
│   └── tests/Feature/
│       └── InventoryCrudTest.php
└── order-service/                    # Order microservice
    ├── app/
    │   ├── Exceptions/
    │   │   └── ServiceException.php      # Cross-service error propagation
    │   ├── Http/Controllers/
    │   │   └── OrderController.php       # CRUD with Saga transaction handling
    │   ├── Models/
    │   │   └── Order.php
    │   └── Services/
    │       └── InventoryServiceClient.php  # HTTP client for Inventory Service
    ├── database/migrations/
    │   └── *_create_orders_table.php
    ├── routes/api.php
    └── tests/Feature/
        └── OrderCrudTest.php
```
