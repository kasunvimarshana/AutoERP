# E-Commerce Module

## Overview

The E-Commerce module provides a tenant-scoped online storefront, product catalog management, and customer order placement and fulfilment lifecycle.

## Features

- **Product Listings**: Publish Inventory products to the storefront, with pricing, SKU, stock quantity, and tags.
- **Catalog**: Public-facing endpoint returning only published (`is_published = true`) listings.
- **Orders**: Customer-facing order placement with BCMath line-level totals (unit price × quantity − discount + tax).
- **Order Confirmation**: Merchant confirms a pending order, triggering `ECommerceOrderConfirmed` event.

## Architecture

| Layer | Components |
|-------|-----------|
| Domain | `ProductListingRepositoryInterface`, `ECommerceOrderRepositoryInterface`, `ECommerceOrderLineRepositoryInterface`, `ECommerceOrderStatus` enum, `ECommerceOrderPlaced`, `ECommerceOrderConfirmed` events |
| Application | `PlaceECommerceOrderUseCase`, `ConfirmECommerceOrderUseCase` |
| Infrastructure | `ProductListingModel`, `ECommerceOrderModel`, `ECommerceOrderLineModel`, repositories, migrations (`ec_product_listings`, `ec_orders`, `ec_order_lines`) |
| Presentation | `ProductListingController`, `ECommerceOrderController`, form requests |

## API Endpoints

| Method | URI | Description |
|--------|-----|-------------|
| GET | `api/v1/ecommerce/catalog` | List published product listings (storefront) |
| GET/POST | `api/v1/ecommerce/products` | Manage all product listings (admin) |
| GET/PUT/DELETE | `api/v1/ecommerce/products/{id}` | Read / update / soft-delete listing |
| GET/POST | `api/v1/ecommerce/orders` | List / place new e-commerce orders |
| GET/DELETE | `api/v1/ecommerce/orders/{id}` | Read / soft-delete order |
| POST | `api/v1/ecommerce/orders/{id}/confirm` | Confirm a pending order |

## Order Lifecycle

```
pending → confirmed → processing → shipped → delivered
                                           → cancelled / refunded
```

## BCMath Totals

Line totals: `(unit_price × quantity) − discount + (after_discount × tax_rate / 100)`  
Order total: `subtotal + tax_amount + shipping_cost`

All amounts stored as `DECIMAL(18,8)`.

## Domain Events

| Event | Trigger |
|-------|---------|
| `ECommerceOrderPlaced` | New order placed (status = pending) |
| `ECommerceOrderConfirmed` | Order confirmed by merchant |

## Integration Notes

- `inventory_product_id` links a product listing to the Inventory module product record.
- `ECommerceOrderConfirmed` can trigger a Sales order creation via event listener.
- `ECommerceOrderPlaced` can trigger order confirmation email via Notification module.
- Reference numbers follow the `ECO-YYYY-XXXXXX` pattern per tenant per year.
