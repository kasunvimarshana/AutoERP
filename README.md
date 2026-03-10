# KV MultiTenant SaaS – Order Processing Microservices

A production-ready microservices architecture demonstrating a multi-tenant SaaS order processing
system with distributed transactions managed via the **Choreography-based Saga pattern**.

> **Original project:** Multi-tenant SaaS Inventory Management System with Laravel microservices
> and React frontend, featuring Laravel Passport SSO, RBAC/ABAC authorization, modular
> architecture, and event-driven communication.

## Architecture at a Glance

```
Client ──► API Gateway (8080) ──► Order Service (8001)       MySQL
                              ──► Payment Service (8002)     PostgreSQL
                              ──► Inventory Service (8003)   MongoDB
                              ──► Notification Service (8004) Redis

                    All services ──► RabbitMQ (5672/15672) for async events
```

| Service              | Language       | Database   | Port |
|----------------------|----------------|------------|------|
| API Gateway          | Node.js        | —          | 8080 |
| Order Service        | PHP/Laravel    | MySQL      | 8001 |
| Payment Service      | PHP/Laravel    | PostgreSQL | 8002 |
| Inventory Service    | Python/FastAPI | MongoDB    | 8003 |
| Notification Service | Node.js        | Redis      | 8004 |

## Quick Start

### Prerequisites

- [Docker](https://www.docker.com/) 24+
- [Docker Compose](https://docs.docker.com/compose/) v2.20+

### 1. Clone and configure

```bash
git clone https://github.com/your-org/KV_MultiTenent_SAAS.git
cd KV_MultiTenent_SAAS

# Copy environment examples (edit values as needed)
cp services/api-gateway/.env.example services/api-gateway/.env
cp services/order-service/.env.example services/order-service/.env
cp services/payment-service/.env.example services/payment-service/.env
cp services/inventory-service/.env.example services/inventory-service/.env
cp services/notification-service/.env.example services/notification-service/.env
```

Generate the required Laravel APP_KEY values (each must be unique and kept secret):

```bash
# Generate keys for each Laravel service
docker run --rm php:8.2-cli php -r "echo 'base64:' . base64_encode(random_bytes(32)) . PHP_EOL;"
# Run twice – once for ORDER_APP_KEY, once for PAYMENT_APP_KEY
# Then export them:
export ORDER_APP_KEY="base64:<generated-value-1>"
export PAYMENT_APP_KEY="base64:<generated-value-2>"
export JWT_SECRET="$(openssl rand -hex 32)"
```

### 2. Start all services

```bash
docker compose up --build -d
```

### 3. Verify services are running

```bash
curl http://localhost:8080/health      # API Gateway
curl http://localhost:8001/api/health  # Order Service
curl http://localhost:8002/api/health  # Payment Service
curl http://localhost:8003/health      # Inventory Service
curl http://localhost:8004/health      # Notification Service

# RabbitMQ Management UI: http://localhost:15672  (admin / admin_password)
```

### 4. Run database migrations

```bash
docker compose exec order-service   php artisan migrate --force
docker compose exec payment-service php artisan migrate --force
```

### 5. Create a test order

```bash
# Generate a demo JWT (requires jsonwebtoken npm package)
TOKEN=$(node -e "
  const jwt = require('jsonwebtoken');
  console.log(jwt.sign(
    { sub: 'usr-001', tenantId: 'tenant-abc', role: 'admin' },
    'change-me-in-production',
    { expiresIn: '1h' }
  ));
")

# Create an order (triggers the full Saga flow)
curl -X POST http://localhost:8080/api/orders \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "customer_id": "cust-001",
    "items": [
      { "sku": "LAPTOP-001",  "quantity": 1, "price": 1499.99 },
      { "sku": "KEYBOARD-01", "quantity": 1, "price": 89.99  }
    ]
  }'
```

## Project Structure

```
KV_MultiTenent_SAAS/
├── docker-compose.yml              # Full stack orchestration
├── .gitignore
├── README.md
│
├── services/
│   ├── api-gateway/                # Node.js/Express – JWT auth, routing, rate limiting
│   ├── order-service/              # Laravel/PHP    – Order management + Saga coordinator
│   ├── payment-service/            # Laravel/PHP    – Payment processing + refunds
│   ├── inventory-service/          # Python/FastAPI – Stock management
│   └── notification-service/       # Node.js        – Email notifications via RabbitMQ
│
├── shared/
│   └── events/README.md            # RabbitMQ event schema documentation
│
└── docs/
    ├── architecture.md             # System diagrams and design principles
    ├── api-contracts.md            # Complete API reference with examples
    └── saga-pattern.md             # Saga pattern flows and compensation logic
```

## Saga Flow

The **Order Processing Saga** (orchestration-based) coordinates a distributed transaction:

```
1. Order Service  → Create order (status: pending)
2. Order Service  → POST /inventory/reserve        → Inventory Service  (status: inventory_reserving)
3. Order Service  → POST /payments/charge          → Payment Service    (status: payment_processing)
4. Order Service  → POST /notifications/send       → Notification Service
```

On failure, compensation rolls back completed steps automatically.  
See [docs/saga-pattern.md](docs/saga-pattern.md) for detailed flow diagrams.

## Key Features

- **Multi-tenancy**: Every resource scoped by `tenant_id`; propagated via `X-Tenant-ID` header
- **JWT Authentication**: Verified at the API Gateway; tenant/user info forwarded downstream
- **Rate Limiting**: 100 req / 15 min per IP on all `/api/*` routes
- **Idempotency**: Payment charges and notifications deduplicated to prevent double-processing
- **Correlation IDs**: `X-Correlation-ID` propagated through all services for distributed tracing
- **Health Checks**: Every service exposes `/health`; Docker Compose monitors liveness
- **Seed Data**: Inventory service auto-seeds 5 demo products on first startup

## Documentation

| Document | Description |
|----------|-------------|
| [Architecture](docs/architecture.md) | System diagrams, service responsibilities, network layout |
| [API Contracts](docs/api-contracts.md) | Full endpoint reference with request/response schemas |
| [Saga Pattern](docs/saga-pattern.md) | Distributed transaction flows, state machine, compensation |
| [Event Schemas](shared/events/README.md) | RabbitMQ exchange/queue config and message formats |

## Infrastructure Ports

| Service           | Host Port |
|-------------------|-----------|
| API Gateway       | 8080      |
| Order Service     | 8001      |
| Payment Service   | 8002      |
| Inventory Service | 8003      |
| Notification Svc  | 8004      |
| MySQL             | 3306      |
| PostgreSQL        | 5432      |
| MongoDB           | 27017     |
| RabbitMQ AMQP     | 5672      |
| RabbitMQ UI       | 15672     |
| Redis             | 6379      |

## Stopping the Stack

```bash
docker compose down       # Stop containers (data preserved)
docker compose down -v    # Stop and remove all volumes (clears all data)
```

## License

MIT
