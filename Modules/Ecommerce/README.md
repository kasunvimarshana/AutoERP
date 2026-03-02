# Ecommerce Module

Headless e-commerce storefront module for the KV Enterprise SaaS platform.

## Overview

This module provides a complete headless storefront solution including product catalog management, shopping cart, checkout flow, and order management — all scoped per tenant.

## Features

- **Product Catalog**: Expose core products as storefront listings with slugs, pricing, featured flags, and sort ordering.
- **Shopping Cart**: Create guest or user-linked carts, add/remove items with BCMath-accurate line totals, automatic subtotal recalculation.
- **Checkout**: Convert an active cart to a `StorefrontOrder` with billing/shipping details, shipping and discount amounts applied via BCMath.
- **Order Management**: Track orders through their lifecycle (`pending → confirmed → processing → shipped → delivered`), cancel or refund.

## Architecture

Follows the project-wide **Controller → Service → Handler (Pipeline) → Repository → Entity** pattern:

- `Domain/` — Pure PHP entities, enums, and repository contracts.
- `Application/` — Commands, Handlers (with `ValidateCommandPipe → AuditLogPipe` pipeline), and Services.
- `Infrastructure/` — Eloquent models, repositories, and migrations.
- `Interfaces/Http/` — Controllers, Form Requests, API Resources, and routes.

## API Endpoints

| Method | URI | Description |
|--------|-----|-------------|
| GET | `/api/v1/ecommerce/products` | List all storefront products |
| POST | `/api/v1/ecommerce/products` | Create a storefront product |
| GET | `/api/v1/ecommerce/products/featured` | List featured products |
| GET | `/api/v1/ecommerce/products/{id}` | Get product by ID |
| PUT | `/api/v1/ecommerce/products/{id}` | Update a product |
| DELETE | `/api/v1/ecommerce/products/{id}` | Delete a product |
| POST | `/api/v1/ecommerce/carts` | Create a new cart |
| GET | `/api/v1/ecommerce/carts/{token}` | Get cart (with items) by UUID token |
| POST | `/api/v1/ecommerce/carts/{token}/items` | Add item to cart |
| DELETE | `/api/v1/ecommerce/carts/{token}/items/{itemId}` | Remove item from cart |
| POST | `/api/v1/ecommerce/carts/{token}/checkout` | Checkout cart → creates order |
| GET | `/api/v1/ecommerce/orders` | List orders |
| GET | `/api/v1/ecommerce/orders/{id}` | Get order by ID |
| PUT | `/api/v1/ecommerce/orders/{id}/status` | Update order status |
| POST | `/api/v1/ecommerce/orders/{id}/cancel` | Cancel an order |
| GET | `/api/v1/ecommerce/orders/{id}/lines` | Get order lines |
| DELETE | `/api/v1/ecommerce/orders/{id}` | Delete an order |

## Notes

- All financial calculations use **BCMath** (no floats).
- All tables include `tenant_id` for multi-tenant isolation.
- Cart tokens are UUIDs generated via `Str::uuid()`.
- Order references follow the pattern `ECO-ORD-YYYYMMDD-NNNNNN`.
