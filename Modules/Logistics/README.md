# Logistics Module

Manages carriers, delivery orders, delivery lines, and shipment tracking events for multi-tenant ERP operations.

## Features

- **Carrier Management** — CRUD for shipping carriers with tenant-scoped unique codes
- **Delivery Orders** — Create delivery orders with auto-generated reference numbers (`DO-YYYY-XXXXXX`), manage order lines, and track status transitions
- **Status Lifecycle** — `pending` → `dispatched` → `in_transit` → `delivered` / `failed` / `cancelled`
- **Tracking Events** — Append audit-trail tracking events (`picked_up`, `in_transit`, `out_for_delivery`, `delivered`, `failed_attempt`, `returned`)
- **Domain Events** — `DeliveryDispatched` and `DeliveryCompleted` events emitted for inter-module communication

## API Endpoints

| Method | Path | Description |
|--------|------|-------------|
| GET    | `/api/v1/logistics/carriers` | List carriers (paginated) |
| POST   | `/api/v1/logistics/carriers` | Create carrier |
| GET    | `/api/v1/logistics/carriers/{id}` | Get carrier |
| PUT    | `/api/v1/logistics/carriers/{id}` | Update carrier |
| DELETE | `/api/v1/logistics/carriers/{id}` | Delete carrier |
| GET    | `/api/v1/logistics/delivery-orders` | List delivery orders (paginated) |
| POST   | `/api/v1/logistics/delivery-orders` | Create delivery order |
| GET    | `/api/v1/logistics/delivery-orders/{id}` | Get delivery order |
| PUT    | `/api/v1/logistics/delivery-orders/{id}` | Update delivery order |
| DELETE | `/api/v1/logistics/delivery-orders/{id}` | Delete delivery order |
| POST   | `/api/v1/logistics/delivery-orders/{id}/dispatch` | Dispatch order |
| POST   | `/api/v1/logistics/delivery-orders/{id}/complete` | Complete (deliver) order |
| GET    | `/api/v1/logistics/delivery-orders/{id}/tracking` | Get tracking events |
| POST   | `/api/v1/logistics/tracking-events` | Add tracking event |

## Architecture

Follows Clean Architecture with strict layer boundaries:

- **Domain** — Entities, Enums, Events, Contracts (no framework dependency)
- **Infrastructure** — Eloquent models, migrations, repository implementations
- **Application** — Use cases with `DB::transaction()` wrapping all writes
- **Presentation** — Controllers, Form Requests (no business logic)

## Financial Integrity

All monetary and quantity values use BCMath with 8 decimal places. No floating-point arithmetic.

## Multi-Tenancy

All models use `HasTenantScope`. All queries are automatically scoped to the current tenant. The `tenant_id` column is present and indexed in every table.
