# POS Module

Point of Sale module for the KV Enterprise ERP/CRM SaaS platform.

## Features

- **Session Management**: Open and close POS shifts/sessions with opening/closing float tracking
- **Order Processing**: Create POS orders with multiple line items
- **Multi-Payment Support**: Accept cash, card, digital, or mixed payments
- **Refunds**: Full and partial refund support
- **BCMath Precision**: All financial calculations use BCMath for accuracy
- **Tenant Isolation**: All data is scoped to tenants

## Endpoints

### Sessions
- `GET /api/v1/pos/sessions` — List sessions
- `POST /api/v1/pos/sessions` — Open a new session
- `GET /api/v1/pos/sessions/{id}` — Get session details
- `PUT /api/v1/pos/sessions/{id}/close` — Close a session
- `DELETE /api/v1/pos/sessions/{id}` — Delete a session

### Orders
- `GET /api/v1/pos/orders` — List orders
- `POST /api/v1/pos/orders` — Create a new order
- `GET /api/v1/pos/orders/{id}` — Get order details
- `POST /api/v1/pos/orders/{id}/pay` — Process payment
- `POST /api/v1/pos/orders/{id}/cancel` — Cancel a draft order
- `POST /api/v1/pos/orders/{id}/refund` — Refund a paid order
- `GET /api/v1/pos/orders/{id}/lines` — Get order line items
- `GET /api/v1/pos/orders/{id}/payments` — Get order payments
- `DELETE /api/v1/pos/orders/{id}` — Delete an order
