# POS Module

Point of Sale module for the AutoERP. Manages physical terminals, cashier sessions, customer orders, and payment processing.

## Features

- **Terminals** — Register and manage POS terminals with configurable opening balances and location assignments
- **Sessions** — Open/close cashier sessions per terminal with opening/closing cash tracking and automatic reconciliation support
- **Orders** — Full order lifecycle: place, pay, cancel, and refund with per-line tax and discount calculations
- **Cash Management** — Automatic change calculation for cash payments; session-level sales totals maintained in real time
- **Multi-Payment Methods** — Cash, card, digital wallet, and credit payment types
- **Refunds** — Single-step refund workflow for paid orders with domain event emission

## API Endpoints

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/v1/pos/terminals` | List terminals (paginated) |
| POST | `/api/v1/pos/terminals` | Create terminal |
| GET | `/api/v1/pos/terminals/{id}` | Get terminal |
| PUT | `/api/v1/pos/terminals/{id}` | Update terminal |
| DELETE | `/api/v1/pos/terminals/{id}` | Delete terminal |
| GET | `/api/v1/pos/sessions` | List sessions (paginated) |
| POST | `/api/v1/pos/sessions` | Open session |
| GET | `/api/v1/pos/sessions/{id}` | Get session |
| POST | `/api/v1/pos/sessions/{id}/close` | Close session |
| GET | `/api/v1/pos/orders` | List orders (paginated) |
| POST | `/api/v1/pos/orders` | Place order |
| GET | `/api/v1/pos/orders/{id}` | Get order |
| POST | `/api/v1/pos/orders/{id}/refund` | Refund order |

## Architecture Notes

- Follows strict Clean Architecture: Presentation → Application → Domain → Infrastructure
- All monetary values stored as `DECIMAL(18,8)`; arithmetic performed exclusively via BCMath
- Every table carries `tenant_id` with global scope enforcement via `HasTenantScope`
- Domain events dispatched on session open/close and order placed/refunded
- All write operations wrapped in `DB::transaction()`
- No cross-module direct calls — communicates via Events/Contracts only
