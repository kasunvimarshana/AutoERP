# Enterprise Inventory Management System
### Multi-Tenant SaaS · Microservices · DDD · Saga Pattern · SSO

> **Production-grade** enterprise inventory platform built on an event-driven microservices architecture with multi-tenant data isolation, distributed Saga orchestration, SSO/OAuth2, and real-time notifications.

---

## Table of Contents

1. [Architecture Overview](#1-architecture-overview)
2. [Services Description](#2-services-description)
3. [Tech Stack](#3-tech-stack)
4. [Repository Structure](#4-repository-structure)
5. [Prerequisites](#5-prerequisites)
6. [Setup & Running](#6-setup--running)
7. [API Endpoints](#7-api-endpoints)
8. [Saga Pattern Flow](#8-saga-pattern-flow)
9. [Multi-Tenant Architecture](#9-multi-tenant-architecture)
10. [Event Catalog](#10-event-catalog)
11. [Environment Variables](#11-environment-variables)
12. [Development Guide](#12-development-guide)
13. [Security Considerations](#13-security-considerations)

---

## 1. Architecture Overview

```
┌──────────────────────────────────────────────────────────────────────┐
│                          Client Applications                          │
│              (Web SPA · Mobile App · Third-party Integrations)        │
└─────────────────────────────┬────────────────────────────────────────┘
                              │  HTTPS :443
                              ▼
┌──────────────────────────────────────────────────────────────────────┐
│                       Nginx Reverse Proxy                             │
│          Rate Limiting · TLS Termination · CORS · Routing             │
└───┬───────────────┬──────────────┬────────────────┬──────────────────┘
    │               │              │                │
    ▼               ▼              ▼                ▼
┌────────┐   ┌──────────┐  ┌──────────┐   ┌──────────────────┐
│  API   │   │  Auth    │  │Inventory │   │  Notification    │
│Gateway │   │ Service  │  │ Service  │   │    Service       │
│ :8000  │   │  :8001   │  │  :8002   │   │     :8004        │
└───┬────┘   └────┬─────┘  └────┬─────┘   └────────┬─────────┘
    │             │              │                   │
    │     ┌───────┘              │                   │
    │     │         ┌────────────┘                   │
    ▼     ▼         ▼                                │
┌─────────────────────────────────────────────────┐ │
│               Order Service :8003               │ │
│    (Saga Orchestrator · Order State Machine)    │ │
└──────────────────────────┬──────────────────────┘ │
                           │                        │
          ┌────────────────┼────────────────────────┘
          ▼                ▼
┌──────────────┐   ┌──────────────────────────────────┐
│   RabbitMQ   │   │            Apache Kafka           │
│  (Commands · │   │  (Domain Events · Event Sourcing) │
│   Replies)   │   │                                   │
└──────────────┘   └──────────────────────────────────┘
          │                ▼
          │    ┌───────────────────────┐
          │    │      Zookeeper        │
          │    └───────────────────────┘
          │
┌─────────┴──────────────────────────────────────────┐
│                  Data Layer                          │
│  ┌─────────────┐ ┌────────────────┐ ┌────────────┐ │
│  │ PostgreSQL  │ │  PostgreSQL    │ │ PostgreSQL │ │
│  │  auth_db    │ │ inventory_db   │ │  orders_db │ │
│  │   :5432     │ │    :5433       │ │   :5434    │ │
│  └─────────────┘ └────────────────┘ └────────────┘ │
│                                                      │
│  ┌──────────────────────────────────────────────┐   │
│  │               Redis :6379                    │   │
│  │   (Cache · Session Store · Rate Limiter)     │   │
│  └──────────────────────────────────────────────┘   │
└─────────────────────────────────────────────────────┘
```

### Core Architectural Principles

| Principle | Implementation |
|---|---|
| **Domain-Driven Design (DDD)** | Each service owns a bounded context: Auth, Inventory, Orders, Notifications |
| **CQRS** | Command and Query buses inside each Laravel service; read models via projections |
| **Event Sourcing** | Outbox pattern on every database write; events replayed via Kafka |
| **Saga Pattern** | Order service orchestrates distributed transactions across Inventory and Payment |
| **Multi-tenancy** | Row-level + schema-level isolation; `X-Tenant-ID` header propagation |
| **SSO / OAuth2** | Auth service issues JWT (RS256) + Refresh tokens; Passport-compliant |
| **Circuit Breaker** | Each service uses Guzzle retry middleware with exponential back-off |
| **Transactional Outbox** | DB write + outbox event in the same transaction; relay worker publishes to broker |

---

## 2. Services Description

### 🔀 API Gateway (`services/api-gateway`)
The single entry point for all external clients. Responsibilities:
- Request authentication via JWT validation (calls Auth Service)
- Tenant resolution from JWT claims → sets `X-Tenant-ID` header downstream
- Service routing and request aggregation
- Rate limiting per tenant and per API key
- API versioning (`/v1/`, `/v2/`)
- Request/response logging with correlation IDs
- Health aggregation endpoint

### 🔐 Auth Service (`services/auth-service`)
Handles all identity and access management:
- User registration, login, logout, password reset
- JWT (RS256) token issuance with configurable expiry
- Refresh token rotation with family tracking (detects reuse attacks)
- OAuth2 authorization server (PKCE flow for SPAs)
- Multi-factor authentication (TOTP via Google Authenticator)
- Role-Based Access Control (RBAC) with per-tenant role definitions
- Tenant provisioning and subscription management
- SSO bridge: SAML 2.0 and OIDC provider support
- **Database**: `postgres-auth` / `auth_db`

### 📦 Inventory Service (`services/inventory-service`)
Core inventory management bounded context:
- Product catalog management (SKUs, variants, bundles)
- Multi-warehouse stock tracking
- Real-time stock reservation (Saga participant)
- Reorder point alerts (published as domain events)
- Stock movement history and audit trail
- Barcode/QR scanning support
- Purchase order management
- **Database**: `postgres-inventory` / `inventory_db`
- **Kafka topics**: `inventory.item.*`, `inventory.stock.*`

### 🛒 Order Service (`services/order-service`)
Order lifecycle management and Saga orchestrator:
- Order creation, modification, and cancellation
- **Order Saga orchestration**: coordinates Inventory reservation → Payment → Fulfilment
- State machine: `PENDING → CONFIRMED → PROCESSING → SHIPPED → DELIVERED`
- Compensation transaction management for failed sagas
- Order history and reporting
- **Database**: `postgres-orders` / `orders_db`
- **Kafka topics**: `order.placed`, `order.saga.*`, `order.confirmed`, etc.

### 📣 Notification Service (`services/notification-service`)
Event-driven notification dispatcher (Node.js):
- Consumes domain events from RabbitMQ and Kafka
- Email delivery via SMTP / Amazon SES / SendGrid
- SMS delivery via Twilio / AWS SNS
- Push notifications via Firebase FCM
- In-app WebSocket notifications (Socket.io)
- Notification preferences and opt-out management
- Retry with exponential back-off and dead-letter queuing
- **Port**: 3000 (HTTP) + WebSocket

---

## 3. Tech Stack

| Component | Technology | Version |
|---|---|---|
| API Gateway | Laravel (PHP) | 10.x |
| Auth Service | Laravel (PHP) + Laravel Passport | 10.x |
| Inventory Service | Laravel (PHP) | 10.x |
| Order Service | Laravel (PHP) | 10.x |
| Notification Service | Node.js + Express + Socket.io | 20 LTS |
| Databases | PostgreSQL | 15 |
| Cache / Sessions | Redis | 7 |
| Message Broker (commands) | RabbitMQ | 3.12 |
| Message Broker (events) | Apache Kafka | 3.5 |
| Coordinator | Apache Zookeeper | 3.8 |
| Reverse Proxy | Nginx | latest |
| Containerisation | Docker + Docker Compose | v2 |
| PHP Runtime | PHP-FPM | 8.2 |
| Web Server (PHP) | Swoole / FrankenPHP | — |

---

## 4. Repository Structure

```
.
├── docker-compose.yml              # Full stack orchestration
├── .gitignore                      # Comprehensive ignore rules
├── README.md                       # This file
│
├── infrastructure/
│   ├── nginx/
│   │   ├── nginx.conf              # Main Nginx configuration
│   │   └── conf.d/
│   │       └── default.conf        # Virtual host / routing rules
│   ├── kafka/
│   │   └── kafka-config.properties # Kafka broker settings
│   ├── rabbitmq/
│   │   └── rabbitmq.conf           # RabbitMQ broker settings
│   ├── redis/                      # Redis configuration (optional)
│   ├── postgres/                   # PostgreSQL custom configs
│   └── scripts/
│       └── init-db.sh              # DB init: schemas, roles, seed data
│
├── services/
│   ├── api-gateway/                # Laravel API Gateway
│   ├── auth-service/               # Laravel Auth + Passport
│   ├── inventory-service/          # Laravel Inventory
│   ├── order-service/              # Laravel Orders + Saga
│   └── notification-service/       # Node.js Notification dispatcher
│       └── src/
│
└── shared/
    ├── contracts/
    │   └── MessageBrokerContract.json  # Universal message envelope schema
    ├── events/
    │   └── EventTypes.json             # Domain event catalog
    └── dtos/                           # Shared Data Transfer Object schemas
```

---

## 5. Prerequisites

| Requirement | Minimum Version | Notes |
|---|---|---|
| Docker | 24.0+ | `docker --version` |
| Docker Compose | v2.20+ | `docker compose version` |
| Git | 2.40+ | — |
| Make (optional) | 4.x | For convenience targets |
| OpenSSL | 3.x | For generating TLS certs locally |

> **Memory**: Recommended minimum 8 GB RAM for the full stack.  
> **Disk**: At least 10 GB free for images and volume data.

---

## 6. Setup & Running

### 6.1 Clone and Configure

```bash
git clone <repository-url>
cd EE_KV_Laravel_SAAS_MultiTenent_SSO_MicoService

# Copy root env example
cp .env.example .env

# Copy service-level env files
for svc in api-gateway auth-service inventory-service order-service; do
  cp services/$svc/.env.example services/$svc/.env
done
cp services/notification-service/.env.example services/notification-service/.env
```

### 6.2 Generate TLS Certificates (local dev)

```bash
mkdir -p infrastructure/nginx/certs
openssl req -x509 -nodes -days 365 -newkey rsa:2048 \
  -keyout infrastructure/nginx/certs/privkey.pem \
  -out infrastructure/nginx/certs/fullchain.pem \
  -subj "/CN=localhost/O=SaaS Dev/C=US"
```

> For production, replace with real certificates from Let's Encrypt or your CA.

### 6.3 Start the Full Stack

```bash
# Build all service images and start everything
docker compose up --build -d

# Watch logs for all services
docker compose logs -f

# Watch logs for a specific service
docker compose logs -f auth-service
```

### 6.4 Run Database Migrations

```bash
# Auth Service
docker compose exec auth-service php artisan migrate --force

# Inventory Service
docker compose exec inventory-service php artisan migrate --force

# Order Service
docker compose exec order-service php artisan migrate --force
```

### 6.5 Seed Development Data

```bash
docker compose exec auth-service       php artisan db:seed
docker compose exec inventory-service  php artisan db:seed
docker compose exec order-service      php artisan db:seed
```

### 6.6 Verify Services

```bash
# Check all containers are healthy
docker compose ps

# Test API Gateway
curl -sf http://localhost/health | jq .

# Test Auth Service directly
curl -sf http://localhost:8001/health | jq .

# Test Inventory Service
curl -sf http://localhost:8002/health | jq .

# Test Order Service
curl -sf http://localhost:8003/health | jq .

# Test Notification Service
curl -sf http://localhost:8004/health | jq .

# RabbitMQ Management UI
open http://localhost:15672   # user: rabbit_user / pass: rabbit_secret
```

### 6.7 Stop and Clean Up

```bash
# Stop all services
docker compose down

# Stop and remove volumes (WARNING: destroys all data)
docker compose down -v

# Remove built images
docker compose down --rmi all
```

---

## 7. API Endpoints

> All endpoints are prefixed with `/api` and require `Authorization: Bearer <JWT>` unless marked **[public]**.

### Auth Service — `/api/auth`

| Method | Path | Description | Auth |
|---|---|---|---|
| `POST` | `/api/auth/register` | Register new user + provision tenant | Public |
| `POST` | `/api/auth/login` | Authenticate, receive JWT + refresh token | Public |
| `POST` | `/api/auth/logout` | Revoke current session | Required |
| `POST` | `/api/auth/refresh` | Exchange refresh token for new JWT | Public |
| `POST` | `/api/auth/password/forgot` | Send password reset email | Public |
| `POST` | `/api/auth/password/reset` | Reset password with token | Public |
| `GET`  | `/api/auth/me` | Get authenticated user profile | Required |
| `PUT`  | `/api/auth/me` | Update profile | Required |
| `POST` | `/api/auth/mfa/enable` | Enable TOTP multi-factor auth | Required |
| `POST` | `/api/auth/mfa/verify` | Verify TOTP code | Required |
| `GET`  | `/api/auth/tenants` | List accessible tenants | Required |
| `POST` | `/api/auth/tenants` | Create new tenant (super-admin) | Admin |
| `GET`  | `/api/auth/tenants/{id}` | Get tenant details | Admin |
| `PUT`  | `/api/auth/tenants/{id}` | Update tenant | Admin |
| `GET`  | `/api/auth/users` | List users in tenant | Manager |
| `POST` | `/api/auth/users` | Invite user to tenant | Manager |
| `PUT`  | `/api/auth/users/{id}/roles` | Assign roles | Admin |
| `GET`  | `/api/auth/oauth/authorize` | OAuth2 authorization endpoint | Public |
| `POST` | `/api/auth/oauth/token` | OAuth2 token endpoint | Public |

### Inventory Service — `/api/inventory`

| Method | Path | Description | Auth |
|---|---|---|---|
| `GET`    | `/api/inventory/items` | List all inventory items (paginated) | Required |
| `POST`   | `/api/inventory/items` | Create new inventory item | Manager |
| `GET`    | `/api/inventory/items/{id}` | Get item details | Required |
| `PUT`    | `/api/inventory/items/{id}` | Update item | Manager |
| `DELETE` | `/api/inventory/items/{id}` | Soft-delete item | Admin |
| `GET`    | `/api/inventory/items/{id}/stock` | Get stock levels per warehouse | Required |
| `POST`   | `/api/inventory/items/{id}/stock/adjust` | Manual stock adjustment | Manager |
| `POST`   | `/api/inventory/stock/reserve` | Reserve stock (internal/Saga) | Service |
| `POST`   | `/api/inventory/stock/release` | Release reservation (internal/Saga) | Service |
| `GET`    | `/api/inventory/warehouses` | List warehouses | Required |
| `POST`   | `/api/inventory/warehouses` | Create warehouse | Admin |
| `GET`    | `/api/inventory/warehouses/{id}` | Get warehouse details | Required |
| `GET`    | `/api/inventory/categories` | List product categories | Required |
| `POST`   | `/api/inventory/categories` | Create category | Manager |
| `GET`    | `/api/inventory/reports/low-stock` | Items below reorder point | Required |
| `GET`    | `/api/inventory/reports/valuation` | Stock valuation report | Manager |

### Order Service — `/api/orders`

| Method | Path | Description | Auth |
|---|---|---|---|
| `GET`    | `/api/orders` | List orders (paginated, filterable) | Required |
| `POST`   | `/api/orders` | Place new order (starts Saga) | Required |
| `GET`    | `/api/orders/{id}` | Get order details + saga state | Required |
| `PUT`    | `/api/orders/{id}` | Update order (pre-confirmation) | Required |
| `DELETE` | `/api/orders/{id}` | Cancel order (triggers compensation) | Required |
| `GET`    | `/api/orders/{id}/saga` | Get Saga execution history | Manager |
| `GET`    | `/api/orders/{id}/timeline` | Get order timeline/events | Required |
| `POST`   | `/api/orders/{id}/confirm` | Manually confirm order | Manager |
| `POST`   | `/api/orders/{id}/ship` | Mark order as shipped | Manager |
| `POST`   | `/api/orders/{id}/deliver` | Mark order as delivered | Manager |
| `GET`    | `/api/orders/reports/summary` | Order summary statistics | Manager |
| `GET`    | `/api/orders/reports/revenue` | Revenue report by period | Manager |

### Notification Service — `/api/notifications`

| Method | Path | Description | Auth |
|---|---|---|---|
| `GET`  | `/api/notifications` | List notifications for current user | Required |
| `PUT`  | `/api/notifications/{id}/read` | Mark notification as read | Required |
| `PUT`  | `/api/notifications/read-all` | Mark all as read | Required |
| `GET`  | `/api/notifications/preferences` | Get notification preferences | Required |
| `PUT`  | `/api/notifications/preferences` | Update preferences | Required |
| `GET`  | `/api/notifications/ws` | WebSocket upgrade endpoint | Required |

### API Gateway — `/`

| Method | Path | Description | Auth |
|---|---|---|---|
| `GET`  | `/health` | Aggregate health status of all services | Public |
| `GET`  | `/v1/status` | Platform status and uptime | Public |
| `*`    | `/api/*` | Proxied to appropriate microservice | Varies |

---

## 8. Saga Pattern Flow

The **Order Saga** implements the Choreography-based distributed transaction pattern. The Order Service acts as the orchestrator using a local state machine and RabbitMQ command channels.

### Happy Path — Order Placement

```
Customer            Order Service          Inventory Service      Notification
    │                     │                       │                    │
    │  POST /api/orders   │                       │                    │
    │────────────────────▶│                       │                    │
    │                     │ INSERT order (PENDING)│                    │
    │                     │ INSERT outbox event   │                    │
    │                     │──────────────────────▶│                    │
    │                     │  order.saga.started   │                    │
    │                     │  (RabbitMQ command)   │                    │
    │                     │                       │ Reserve stock      │
    │                     │                       │ (check quantity)   │
    │                     │◀──────────────────────│                    │
    │                     │ inventory.stock.reserved                   │
    │                     │ Update order → CONFIRMED                   │
    │                     │ Publish order.confirmed (Kafka)            │
    │                     │                       │                    │
    │                     │─────────────────────────────────────────▶ │
    │                     │              order.confirmed               │
    │                     │                       │             Send email
    │◀────────────────────│                       │                    │
    │  201 Created        │                       │                    │
```

### Compensation Path — Insufficient Stock

```
Customer            Order Service          Inventory Service      Notification
    │  POST /api/orders   │                       │                    │
    │────────────────────▶│                       │                    │
    │                     │ INSERT order (PENDING)│                    │
    │                     │──────────────────────▶│                    │
    │                     │  inventory.stock.reserve (command)         │
    │                     │                       │ Stock check fails  │
    │                     │◀──────────────────────│                    │
    │                     │ inventory.stock.reservation_failed         │
    │                     │ Update order → CANCELLED                   │
    │                     │ Publish order.saga.failed                  │
    │                     │ Publish order.saga.compensated             │
    │                     │─────────────────────────────────────────▶ │
    │                     │         order.cancelled                    │
    │                     │                       │             Send failure email
    │◀────────────────────│                       │                    │
    │  422 Unprocessable  │                       │                    │
```

### Saga States

```
PENDING ──[inventory_reserved]──▶ INVENTORY_RESERVED
       ──[reservation_failed]───▶ CANCELLING ──▶ CANCELLED

INVENTORY_RESERVED ──[payment_captured]──▶ PAYMENT_CAPTURED
                   ──[payment_failed]────▶ CANCELLING
                                             └──[inventory_released]──▶ CANCELLED

PAYMENT_CAPTURED ──[fulfilment_started]──▶ PROCESSING
                                           └──[shipped]──▶ SHIPPED
                                                           └──[delivered]──▶ DELIVERED
```

### Idempotency

All Saga participants implement **idempotent consumers** by storing the `messageId` in a processed-events table. Duplicate deliveries (at-least-once) are safely discarded.

---

## 9. Multi-Tenant Architecture

The system supports **schema-level isolation** (strong isolation) with an optional **row-level isolation** mode (for resource efficiency at scale).

### Tenant Resolution Flow

```
Request arrives → Nginx → API Gateway
                               │
                               ▼
                    JWT validated (auth-service)
                               │
                    Extract tenant_id from JWT claims
                               │
                    Set X-Tenant-ID header
                               │
                    Downstream services read X-Tenant-ID
                               │
                    Resolve schema:  public.system.tenants → schema_name
                               │
                    Set search_path = tenant_{slug}, public
```

### Data Isolation Models

| Model | PostgreSQL Implementation | Use Case |
|---|---|---|
| **Schema-per-tenant** | `CREATE SCHEMA tenant_{slug}` | Default; strong isolation |
| **Row-level security** | `ENABLE ROW SECURITY; CREATE POLICY ...` | High-density shared tables |
| **Database-per-tenant** | Separate PostgreSQL instance | Enterprise / compliance |

### Tenant Provisioning (Saga)

When `auth.tenant.created` event is fired:
1. **Inventory Service** creates tenant's schema + tables + default categories
2. **Order Service** creates tenant's schema + tables + default settings
3. **Auth Service** creates default admin user and roles
4. **Notification Service** configures default notification templates

### Feature Flags

Tenant-level feature flags allow per-tenant toggling of features without code changes:

```bash
# Via API
curl -X PUT /api/auth/tenants/{id}/features \
  -H "Authorization: Bearer $TOKEN" \
  -d '{"saga_v2_enabled": true, "advanced_reporting": true}'
```

---

## 10. Event Catalog

All domain events are documented in [`shared/events/EventTypes.json`](shared/events/EventTypes.json). Every message must conform to [`shared/contracts/MessageBrokerContract.json`](shared/contracts/MessageBrokerContract.json) (CloudEvents 1.0 envelope).

### Key Topics Summary

| Kafka Topic | Producer | Consumers |
|---|---|---|
| `auth.user.registered` | auth-service | notification-service |
| `auth.tenant.created` | auth-service | all services |
| `inventory.item.created` | inventory-service | order-service |
| `inventory.stock.reserved` | inventory-service | order-service |
| `inventory.stock.reservation_failed` | inventory-service | order-service |
| `inventory.stock.depleted` | inventory-service | notification-service |
| `order.placed` | order-service | inventory-service, notification-service |
| `order.saga.started` | order-service | inventory-service |
| `order.saga.completed` | order-service | notification-service |
| `order.saga.failed` | order-service | notification-service, inventory-service |
| `order.confirmed` | order-service | notification-service |
| `order.cancelled` | order-service | inventory-service, notification-service |
| `notification.email.queued` | notification-service | — |

---

## 11. Environment Variables

### Root `.env`

```dotenv
# Application
APP_ENV=production          # local | staging | production
APP_DEBUG=false

# Database credentials (used by docker-compose.yml)
AUTH_DB_USER=auth_user
AUTH_DB_PASSWORD=change_me_in_production
INVENTORY_DB_USER=inventory_user
INVENTORY_DB_PASSWORD=change_me_in_production
ORDERS_DB_USER=orders_user
ORDERS_DB_PASSWORD=change_me_in_production

# Redis
REDIS_PASSWORD=change_me_in_production

# RabbitMQ
RABBITMQ_USER=rabbit_user
RABBITMQ_PASSWORD=change_me_in_production
RABBITMQ_VHOST=saas_vhost
```

### Auth Service (`services/auth-service/.env`)

```dotenv
APP_NAME="Auth Service"
APP_ENV=production
APP_KEY=base64:...                         # php artisan key:generate
APP_DEBUG=false
APP_URL=http://auth-service:9000

DB_CONNECTION=pgsql
DB_HOST=postgres-auth
DB_PORT=5432
DB_DATABASE=auth_db
DB_USERNAME=auth_user
DB_PASSWORD=change_me_in_production

CACHE_DRIVER=redis
SESSION_DRIVER=redis
QUEUE_CONNECTION=rabbitmq

REDIS_HOST=redis
REDIS_PASSWORD=change_me_in_production
REDIS_PORT=6379

RABBITMQ_HOST=rabbitmq
RABBITMQ_PORT=5672
RABBITMQ_USER=rabbit_user
RABBITMQ_PASSWORD=change_me_in_production
RABBITMQ_VHOST=saas_vhost

# JWT / Passport
PASSPORT_PRIVATE_KEY="-----BEGIN RSA PRIVATE KEY-----\n..."
PASSPORT_PUBLIC_KEY="-----BEGIN PUBLIC KEY-----\n..."
JWT_TTL=60                                 # minutes
REFRESH_TOKEN_TTL=20160                    # 14 days in minutes

# OAuth2
OAUTH_CLIENT_ID=1
OAUTH_CLIENT_SECRET=change_me_in_production

# MFA
MFA_ISSUER="SaaS Inventory"
```

### Inventory Service (`services/inventory-service/.env`)

```dotenv
APP_NAME="Inventory Service"
APP_ENV=production
APP_KEY=base64:...
APP_DEBUG=false

DB_CONNECTION=pgsql
DB_HOST=postgres-inventory
DB_PORT=5432
DB_DATABASE=inventory_db
DB_USERNAME=inventory_user
DB_PASSWORD=change_me_in_production

CACHE_DRIVER=redis
QUEUE_CONNECTION=kafka

REDIS_HOST=redis
REDIS_PASSWORD=change_me_in_production

KAFKA_BROKERS=kafka:9092
KAFKA_CONSUMER_GROUP_ID=inventory-service
KAFKA_AUTO_OFFSET_RESET=earliest

RABBITMQ_HOST=rabbitmq
RABBITMQ_PORT=5672
RABBITMQ_USER=rabbit_user
RABBITMQ_PASSWORD=change_me_in_production
RABBITMQ_VHOST=saas_vhost

AUTH_SERVICE_URL=http://auth-service:9000
AUTH_PUBLIC_KEY="-----BEGIN PUBLIC KEY-----\n..."
```

### Order Service (`services/order-service/.env`)

```dotenv
APP_NAME="Order Service"
APP_ENV=production
APP_KEY=base64:...
APP_DEBUG=false

DB_CONNECTION=pgsql
DB_HOST=postgres-orders
DB_PORT=5432
DB_DATABASE=orders_db
DB_USERNAME=orders_user
DB_PASSWORD=change_me_in_production

CACHE_DRIVER=redis
QUEUE_CONNECTION=kafka

REDIS_HOST=redis
REDIS_PASSWORD=change_me_in_production

KAFKA_BROKERS=kafka:9092
KAFKA_CONSUMER_GROUP_ID=order-service

RABBITMQ_HOST=rabbitmq
RABBITMQ_PORT=5672
RABBITMQ_USER=rabbit_user
RABBITMQ_PASSWORD=change_me_in_production
RABBITMQ_VHOST=saas_vhost

INVENTORY_SERVICE_URL=http://inventory-service:9000

# Saga configuration
SAGA_LOCK_TIMEOUT=300          # seconds
SAGA_MAX_RETRIES=3
SAGA_COMPENSATION_TIMEOUT=600  # seconds
```

### Notification Service (`services/notification-service/.env`)

```dotenv
NODE_ENV=production
PORT=3000

RABBITMQ_URL=amqp://rabbit_user:change_me@rabbitmq:5672/saas_vhost
KAFKA_BROKERS=kafka:9092
KAFKA_CONSUMER_GROUP_ID=notification-service

REDIS_HOST=redis
REDIS_PORT=6379
REDIS_PASSWORD=change_me_in_production

# Email (choose one provider)
MAIL_DRIVER=ses                # smtp | ses | sendgrid
SMTP_HOST=smtp.mailtrap.io
SMTP_PORT=587
SMTP_USER=your_user
SMTP_PASS=your_pass
AWS_SES_REGION=us-east-1
AWS_ACCESS_KEY_ID=...
AWS_SECRET_ACCESS_KEY=...

# SMS
TWILIO_ACCOUNT_SID=...
TWILIO_AUTH_TOKEN=...
TWILIO_FROM_NUMBER=+15005550006

# Push
FCM_SERVER_KEY=...

# WebSocket
WS_CORS_ORIGINS=https://app.yourdomain.com,http://localhost:3001
```

---

## 12. Development Guide

### Running a Single Service Locally (without Docker)

```bash
cd services/auth-service
composer install
cp .env.example .env
php artisan key:generate
php artisan passport:keys
php artisan migrate
php artisan serve --port=8001
```

### Running Tests

```bash
# Inside a service container
docker compose exec auth-service       php artisan test --parallel
docker compose exec inventory-service  php artisan test --parallel
docker compose exec order-service      php artisan test --parallel

# Notification service
docker compose exec notification-service npm test
```

### Code Style

```bash
# PHP — Laravel Pint
docker compose exec auth-service ./vendor/bin/pint

# JavaScript — ESLint + Prettier
docker compose exec notification-service npm run lint
docker compose exec notification-service npm run format
```

### Debugging

```bash
# Tail logs for a service
docker compose logs -f inventory-service

# Shell into a service container
docker compose exec auth-service bash

# Monitor RabbitMQ queues
curl -u rabbit_user:rabbit_secret http://localhost:15672/api/queues

# Kafka consumer group lag
docker compose exec kafka kafka-consumer-groups.sh \
  --bootstrap-server localhost:9092 --describe --all-groups
```

---

## 13. Security Considerations

- **Secrets Management**: Never commit `.env` files. Use Docker Secrets or HashiCorp Vault in production.
- **TLS**: All inter-service traffic is plain HTTP inside the Docker network. Enable mTLS via a service mesh (Istio/Linkerd) in production.
- **JWT**: Access tokens use RS256 (asymmetric). Private keys stay in Auth Service only. All other services validate using the public key.
- **SQL Injection**: All queries use Laravel's query builder / Eloquent parameterized queries. Raw queries are forbidden.
- **Tenant Isolation**: Row Level Security (RLS) policies enforced at the PostgreSQL level as a safety net even if application-level filtering is bypassed.
- **Rate Limiting**: Nginx rate limiting + Laravel throttle middleware per tenant.
- **Audit Log**: All mutating actions write an immutable record to the `audit.audit_log` table.
- **Message Replay Attacks**: All consumers store processed `message.id` values in Redis for deduplication (TTL: 24h).
- **Dependency Scanning**: Run `composer audit` and `npm audit` in CI pipelines.

---

> Built with ❤️ using Laravel, Node.js, PostgreSQL, Redis, RabbitMQ, Kafka, and Docker.
