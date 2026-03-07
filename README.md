# Microservice-Driven Inventory Management System

This project is a reference implementation demonstrating an enterprise-grade, event-driven microservice architecture utilizing Laravel and React, secured by Keycloak and orchestrated with RabbitMQ.

## Architecture Highlights
- **Framework**: Laravel 11.x for Microservices, React 18 for Frontend.
- **Pattern**: Controller → Service → Repository pattern. Layered Clean Architecture.
- **Security**: Keycloak Identity and Access Management (JWT validation + RBAC).
- **Communication**: Asynchronous Event-Driven messaging via RabbitMQ.
- **Transactions**: Distributed Transactions implementing the **Saga Pattern** with compensating actions explicitly demonstrated between Order creation and Inventory reservation.
- **Data consistency**: Append-only Inventory Ledger for robust stock tracing.

## Directory Structure
- `docker-compose.yml`: Spins up RabbitMQ, Keycloak, and PostgreSQL.
- `product-service/`: A Laravel microservice responsible for Products modeling. Implements filtering, sorting, pagination via Repository traits, and broadcasts `ProductCreated` events.
- `inventory-service/`: A Laravel microservice consuming RabbitMQ events via Listeners to create Inventory Ledgers and process Saga reservations.
- `frontend/`: React + Vite Frontend emphasizing a modern, glassmorphic UI. Uses Keycloak for SSO and intercepts Axios requests to inject JWTs.

## Quick Start
1. **Infrastructure**: Run `docker-compose up -d`. Access Keycloak at `http://localhost:8080` (admin/admin), set up a Realm named `inventory-system`, create a client `react-frontend`, and add roles `admin`, `user`.
2. **Microservices (Product & Inventory)**:
    - Run migrations: `php artisan migrate`
    - Run queue worker to listen for RabbitMQ events: `php artisan queue:work`
    - Run standalone servers: `php artisan serve --port=8000` (Product), `php artisan serve --port=8001` (Inventory)
3. **Frontend**:
    - Inside `/frontend`, install dependencies via `npm install`.
    - Run UI via `npm run dev`.

*Note: For a fully functioning local setup, a Keycloak realm configuration export and proper environment variable bindings for JWT keys must be established inside `.env` of each Laravel service.*
