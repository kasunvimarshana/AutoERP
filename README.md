# Laravel Multi-Tenant SAAS — Saga Pattern for Distributed Transactions

A production-ready microservice system implementing the **Saga Orchestration Pattern** for distributed transactions across heterogeneous services.

---

## Architecture Diagram

```
┌─────────────────────────────────────────────────────────────────────────────┐
│                              CLIENT / BROWSER                               │
└─────────────────────────────────────┬───────────────────────────────────────┘
                                      │ HTTP
                                      ▼
┌─────────────────────────────────────────────────────────────────────────────┐
│                         API GATEWAY  (Nginx :80)                            │
│  /api/orders* → Order Svc  │  /api/products* → Inventory Svc               │
│  /api/payments* → Pay Svc  │  /api/notifications* → Notification Svc       │
└──────────┬────────────────┬──────────────────┬───────────────┬──────────────┘
           │                │                  │               │
           ▼                ▼                  ▼               ▼
  ┌────────────────┐  ┌─────────────┐  ┌────────────┐  ┌──────────────────┐
  │  Order Service │  │  Inventory  │  │  Payment   │  │  Notification    │
  │  Laravel 10    │  │  Service    │  │  Service   │  │  Service         │
  │  PHP 8.2       │  │  Laravel 10 │  │  Go 1.21   │  │  Node.js 20      │
  │  MySQL  :8000  │  │  PHP 8.2    │  │  :8002     │  │  Express :8003   │
  │  [ORCHESTRATOR]│  │  PgSQL:8001 │  │            │  │  MongoDB         │
  └───────┬────────┘  └──────┬──────┘  └─────┬──────┘  └────────┬─────────┘
          │                  │               │                   │
          │      ┌───────────┴───────────────┴───────────────────┘
          │      │           RABBITMQ  (AMQP :5672)
          │      │   Exchange: saga.commands (direct)
          │      │   Exchange: saga.replies  (direct)
          │      │
          │  Queues:
          │  ├── reserve-inventory  ──► Inventory Service
          │  ├── release-inventory  ──► Inventory Service (compensation)
          │  ├── process-payment    ──► Payment Service
          │  ├── refund-payment     ──► Payment Service (compensation)
          │  ├── send-notification  ──► Notification Service
          │  └── saga-replies       ──► Order Service (orchestrator)
          │
          ▼
  ┌───────────────┐
  │     REDIS     │  ← Saga state fast-access store
  │    :6379      │    Key: saga:{sagaId} → JSON state
  └───────────────┘
```

---

## Services

| Service | Language | DB | Port | Role |
|---|---|---|---|---|
| **Order Service** | Laravel 10 / PHP 8.2 | MySQL 8.0 | 8000 | Saga Orchestrator |
| **Inventory Service** | Laravel 10 / PHP 8.2 | PostgreSQL 16 | 8001 | Stock reservation |
| **Payment Service** | Go 1.21 | Redis 7 | 8002 | Payment processing |
| **Notification Service** | Node.js 20 / Express | MongoDB 7 | 8003 | Email notifications |
| **API Gateway** | Nginx 1.25 | — | 80 | Routing, CORS, rate-limiting |
| **RabbitMQ** | AMQP | — | 5672/15672 | Message broker |
| **Redis** | — | — | 6379 | Saga state store |

---

## Technology Stack

| Layer | Technology |
|---|---|
| Orchestration Pattern | Saga (Orchestrator-based) |
| Message Broker | RabbitMQ 3.12 |
| State Store | Redis 7.2 |
| HTTP Framework (PHP) | Laravel 10 |
| HTTP Framework (Go) | Gin 1.9 |
| HTTP Framework (Node) | Express 4.18 |
| Container Runtime | Docker + Docker Compose |
| Reverse Proxy | Nginx 1.25 |
| CI/CD Ready | Yes (Dockerfiles + health checks) |

---

## Saga Flow

### Happy Path (Order Created Successfully)

```
Client              Order Service        Inventory           Payment          Notification
  │                  (Orchestrator)        Service            Service           Service
  │
  │─── POST /api/orders ──────────────►│
  │                                    │
  │                                    │─ Create Order (DB) ─►
  │                                    │─ Create SagaState ──►
  │                                    │─ Store in Redis ────►
  │                                    │
  │                                    │──── [1] RESERVE_INVENTORY ──────────►│
  │◄── 201 Created (saga started) ─────│                                      │
  │                                                                            │
  │                                               (DB transaction, lock rows) │
  │                                    │◄─── [2] INVENTORY_RESERVED ──────────│
  │                                    │
  │                                    │──── [3] PROCESS_PAYMENT ───────────────────────►│
  │                                    │                                                  │
  │                                    │◄─── [4] PAYMENT_PROCESSED ─────────────────────│
  │                                    │
  │                                    │──── [5] SEND_NOTIFICATION ──────────────────────────────►│
  │                                    │                                                           │
  │                                    │◄─── [6] NOTIFICATION_SENT ──────────────────────────────│
  │                                    │
  │                                    │─ Update Order → confirmed ─►
  │                                    │─ Update SagaState → COMPLETED ►
```

### Compensation Flow (Payment Fails)

```
  │──── [1] RESERVE_INVENTORY ──────────►│
  │◄─── [2] INVENTORY_RESERVED ──────────│
  │──── [3] PROCESS_PAYMENT ───────────────────────►│
  │◄─── [4] PAYMENT_FAILED ────────────────────────│  ← triggers compensation
  │
  │──── [C1] RELEASE_INVENTORY ─────────►│          ← compensating transaction
  │◄─── [C2] INVENTORY_RELEASED ─────────│
  │
  │─ Update Order → failed ─────────────►
```

### Saga States

```
STARTED → INVENTORY_RESERVED → PAYMENT_PROCESSED → COMPLETED
                │                      │
                ▼                      ▼
             FAILED          COMPENSATION_STARTED → COMPENSATION_COMPLETED
```

---

## Quick Start

### Prerequisites

- Docker ≥ 24.0
- Docker Compose v2

### 1. Clone & Configure

```bash
git clone https://github.com/your-org/Laravel_MultiTenent_SAAS_SAGA.git
cd Laravel_MultiTenent_SAAS_SAGA
cp .env.example .env
# Edit .env with your settings (generate APP_KEY values)
```

### 2. Start All Services

```bash
docker compose up --build -d
```

### 3. Run Database Migrations

```bash
# Order Service
docker compose exec order-service php artisan migrate --force

# Inventory Service (with seed data)
docker compose exec inventory-service php artisan migrate --force
docker compose exec inventory-service php artisan db:seed --force
```

### 4. Generate App Keys

```bash
docker compose exec order-service     php artisan key:generate
docker compose exec inventory-service php artisan key:generate
```

### 5. Verify All Services

```bash
curl http://localhost/api/orders       # → order service health through gateway
curl http://localhost/api/products     # → inventory service
curl http://localhost:8002/health      # → payment service (direct)
curl http://localhost:8003/health      # → notification service (direct)
curl http://localhost:15672            # → RabbitMQ management UI (guest/guest)
```

---

## API Documentation

### Order Service — `http://localhost/api/orders`

#### Create Order (Start Saga)

```http
POST /api/orders
Content-Type: application/json

{
  "customer_id": "550e8400-e29b-41d4-a716-446655440000",
  "customer_email": "customer@example.com",
  "items": [
    {
      "product_id": "660e8400-e29b-41d4-a716-446655440001",
      "product_name": "Pro Laptop 15\"",
      "quantity": 1,
      "price": 1299.99
    }
  ]
}
```

**Response 201:**
```json
{
  "message": "Order created and saga started",
  "data": {
    "order_id": "uuid",
    "saga_id":  "uuid",
    "status":   "processing"
  }
}
```

#### List Orders

```http
GET /api/orders?per_page=10
```

#### Get Order with Saga State

```http
GET /api/orders/{order_id}
```

**Response 200:**
```json
{
  "data": {
    "id": "uuid",
    "customer_id": "uuid",
    "status": "confirmed",
    "total_amount": "1299.99",
    "items": [...],
    "saga": {
      "id": "uuid",
      "current_step": "COMPLETED",
      "status": "COMPLETED",
      "error": null
    }
  }
}
```

#### Cancel Order

```http
PUT /api/orders/{order_id}/cancel
```

---

### Inventory Service — `http://localhost/api/products`

#### List Products with Stock

```http
GET /api/products?per_page=10
```

#### Create Product

```http
POST /api/products
Content-Type: application/json

{
  "sku": "WIDGET-001",
  "name": "Premium Widget",
  "description": "A high-quality widget",
  "price": 49.99,
  "stock_quantity": 100
}
```

#### Adjust Stock

```http
PUT /api/products/{id}/stock
Content-Type: application/json

{
  "adjustment": 50,
  "reason": "Restocked from supplier"
}
```

#### List Reservations

```http
GET /api/inventory/reservations?order_id={uuid}
```

---

### Payment Service — `http://localhost:8002`

#### Create Payment (manual trigger)

```http
POST /payments
Content-Type: application/json

{
  "saga_id":     "uuid",
  "order_id":    "uuid",
  "customer_id": "uuid",
  "amount":      1299.99
}
```

#### Get Payment

```http
GET /payments/{payment_id}
```

#### Refund Payment

```http
POST /payments/{saga_id}/refund
Content-Type: application/json

{ "order_id": "uuid" }
```

---

### Notification Service — `http://localhost:8003`

#### Get Notification

```http
GET /notifications/{notification_id}
```

#### Get Notifications by Order

```http
GET /notifications/order/{order_id}
```

---

## Message Format Reference

### Command Message (Orchestrator → Services)

```json
{
  "saga_id":   "550e8400-e29b-41d4-a716-446655440000",
  "order_id":  "660e8400-e29b-41d4-a716-446655440001",
  "type":      "RESERVE_INVENTORY",
  "payload":   { "items": [...] },
  "timestamp": "2024-01-15T10:30:00Z"
}
```

### Reply Message (Services → Orchestrator)

```json
{
  "saga_id":   "550e8400-e29b-41d4-a716-446655440000",
  "order_id":  "660e8400-e29b-41d4-a716-446655440001",
  "type":      "INVENTORY_RESERVED",
  "success":   true,
  "data":      { "items_count": 2 },
  "error":     "",
  "timestamp": "2024-01-15T10:30:01Z"
}
```

### Message Types

| Type | Direction | Queue |
|---|---|---|
| `RESERVE_INVENTORY` | Orchestrator → Inventory | `reserve-inventory` |
| `INVENTORY_RESERVED` | Inventory → Orchestrator | `saga-replies` |
| `INVENTORY_RESERVATION_FAILED` | Inventory → Orchestrator | `saga-replies` |
| `RELEASE_INVENTORY` | Orchestrator → Inventory | `release-inventory` |
| `INVENTORY_RELEASED` | Inventory → Orchestrator | `saga-replies` |
| `PROCESS_PAYMENT` | Orchestrator → Payment | `process-payment` |
| `PAYMENT_PROCESSED` | Payment → Orchestrator | `saga-replies` |
| `PAYMENT_FAILED` | Payment → Orchestrator | `saga-replies` |
| `REFUND_PAYMENT` | Orchestrator → Payment | `refund-payment` |
| `PAYMENT_REFUNDED` | Payment → Orchestrator | `saga-replies` |
| `SEND_NOTIFICATION` | Orchestrator → Notification | `send-notification` |
| `NOTIFICATION_SENT` | Notification → Orchestrator | `saga-replies` |
| `NOTIFICATION_FAILED` | Notification → Orchestrator | `saga-replies` |

---

## Environment Variables

| Variable | Service | Default | Description |
|---|---|---|---|
| `APP_KEY` | Order, Inventory | — | Laravel encryption key (generate with `artisan key:generate`) |
| `DB_HOST` | Order | `mysql` | MySQL hostname |
| `DB_HOST` | Inventory | `postgres` | PostgreSQL hostname |
| `REDIS_HOST` | Order, Payment | `redis` | Redis hostname |
| `RABBITMQ_HOST` | Order, Inventory | `rabbitmq` | RabbitMQ hostname |
| `RABBITMQ_URL` | Payment, Notification | `amqp://guest:guest@rabbitmq:5672/` | RabbitMQ AMQP URL |
| `MONGODB_URI` | Notification | `mongodb://mongo:27017/notifications_db` | MongoDB URI |
| `SAGA_TIMEOUT` | Order | `300` | Saga state TTL in Redis (seconds) |
| `GIN_MODE` | Payment | `debug` | Gin mode (`debug`/`release`) |
| `EMAIL_HOST` | Notification | `smtp.ethereal.email` | SMTP hostname |
| `EMAIL_USER` | Notification | — | SMTP username |
| `EMAIL_PASS` | Notification | — | SMTP password |

---

## Testing

### Order Service (PHPUnit)

```bash
cd order-service

# Install dependencies
composer install

# Run all tests
./vendor/bin/phpunit

# Run specific suite
./vendor/bin/phpunit --testsuite Unit
./vendor/bin/phpunit --testsuite Feature

# With coverage
./vendor/bin/phpunit --coverage-text
```

### Inventory Service (PHPUnit)

```bash
cd inventory-service
composer install
./vendor/bin/phpunit
```

### Payment Service (Go)

```bash
cd payment-service

# Download dependencies
go mod tidy

# Run all tests
go test ./... -v

# Run with race detector
go test -race ./... -v

# Run specific test
go test -run TestRefundPayment -v
```

### Notification Service (Jest)

```bash
cd notification-service
npm install
npm test
```

---

## Troubleshooting

### Services fail to start

**Symptom:** Order/Inventory service exits immediately.
**Cause:** RabbitMQ or database not ready.
**Fix:** Services have healthcheck dependencies; wait for `docker compose up` to complete. Check logs:
```bash
docker compose logs order-service
docker compose logs rabbitmq
```

### RabbitMQ connection refused

```bash
# Check RabbitMQ is healthy
docker compose exec rabbitmq rabbitmq-diagnostics check_port_connectivity

# View management UI
open http://localhost:15672  # guest / guest
```

### Database migration errors

```bash
# Reset and re-migrate Order Service
docker compose exec order-service php artisan migrate:fresh --force

# Reset and re-migrate Inventory Service  
docker compose exec inventory-service php artisan migrate:fresh --seed --force
```

### Saga stuck in STARTED state

This can happen if the consumer crashes after publishing a command. Check:
```bash
docker compose logs order-service | grep saga:consume
docker compose logs inventory-service | grep inventory:consume
```

Redis state (for debugging):
```bash
docker compose exec redis redis-cli KEYS "saga:*"
docker compose exec redis redis-cli GET "saga:{saga_id}"
```

### Payment always failing

The payment service has a simulated 10% failure rate. This is intentional to demonstrate compensating transactions. If you want 100% success for testing, modify `internal/service/payment_service.go` and change `0.90` to `1.0`.

### Viewing all container logs

```bash
docker compose logs -f                    # all services
docker compose logs -f order-service      # single service
docker compose logs --tail=100 rabbitmq   # last 100 lines
```

---

## Project Structure

```
├── docker-compose.yml
├── .env.example
├── .gitignore
│
├── order-service/                  # Laravel 10 - Saga Orchestrator
│   ├── app/
│   │   ├── Http/Controllers/       # OrderController
│   │   ├── Http/Requests/          # CreateOrderRequest
│   │   ├── Models/                 # Order, SagaState
│   │   ├── Saga/
│   │   │   ├── SagaOrchestrator.php
│   │   │   └── Steps/              # ReserveInventory, ProcessPayment, etc.
│   │   ├── Messaging/              # RabbitMQPublisher, RabbitMQConsumer
│   │   └── Console/Commands/       # SagaEventConsumer (saga:consume)
│   ├── database/migrations/
│   ├── tests/
│   └── Dockerfile
│
├── inventory-service/              # Laravel 10 - Inventory Management
│   ├── app/
│   │   ├── Http/Controllers/       # ProductController, InventoryController
│   │   ├── Models/                 # Product, InventoryReservation
│   │   ├── Services/               # InventoryService
│   │   ├── Messaging/              # RabbitMQPublisher, RabbitMQConsumer
│   │   └── Console/Commands/       # InventoryEventConsumer
│   ├── database/
│   │   ├── migrations/
│   │   └── seeders/                # ProductSeeder (10 sample products)
│   ├── tests/
│   └── Dockerfile
│
├── payment-service/                # Go 1.21 - Payment Processing
│   ├── main.go
│   ├── internal/
│   │   ├── model/                  # Payment struct
│   │   ├── store/                  # RedisStore
│   │   ├── service/                # PaymentService
│   │   ├── handler/                # Gin HTTP handlers
│   │   └── messaging/              # RabbitMQ consumer + publisher
│   ├── payment_service_test.go
│   └── Dockerfile
│
├── notification-service/           # Node.js 20 - Email Notifications
│   ├── src/
│   │   ├── index.js                # Express app entry point
│   │   ├── config/                 # Environment configuration
│   │   ├── models/                 # Mongoose Notification model
│   │   ├── services/               # NotificationService
│   │   ├── messaging/              # RabbitMQ consumer + publisher
│   │   ├── routes/                 # Express routes
│   │   └── __tests__/             # Jest tests
│   └── Dockerfile
│
└── api-gateway/
    └── nginx.conf                  # Nginx reverse proxy configuration
```

---

## License

MIT License — see [LICENSE](LICENSE) for details.
