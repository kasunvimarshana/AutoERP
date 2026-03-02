# Sales Module

## Overview

The Sales module implements the core sales workflow for the ERP platform:

```
Quotation (Draft) → Confirmed → Delivered → Invoiced
                                          ↓
                                       Cancelled (any stage before Invoiced)
```

## Architecture

Follows the strict **Controller → Service → Handler (with Pipeline) → Repository → Entity** pattern with Laravel Pipeline for command processing.

## API Endpoints

| Method | Endpoint | Description |
|--------|----------|-------------|
| `GET` | `/api/v1/sales/orders` | List sales orders (paginated) |
| `POST` | `/api/v1/sales/orders` | Create a new sales order (draft) |
| `GET` | `/api/v1/sales/orders/{id}` | Get sales order by ID |
| `POST` | `/api/v1/sales/orders/{id}/confirm` | Confirm a draft order |
| `POST` | `/api/v1/sales/orders/{id}/cancel` | Cancel an order |
| `DELETE` | `/api/v1/sales/orders/{id}` | Soft-delete a sales order |

## Status Transitions

| From | To | Trigger |
|------|----|---------|
| `draft` | `confirmed` | `POST /confirm` |
| `confirmed` | `delivered` | Future: Delivery module |
| `delivered` | `invoiced` | Future: Accounting module |
| any (except `invoiced`) | `cancelled` | `POST /cancel` |

## Financial Precision

All monetary calculations use BCMath (4 decimal places). Floating-point arithmetic is strictly forbidden.

Line total formula:
```
gross        = quantity × unit_price
discount_amt = gross × (discount_rate / 100)
after_disc   = gross − discount_amt
tax_amt      = after_disc × (tax_rate / 100)
line_total   = after_disc + tax_amt
```

Order totals:
```
subtotal       = SUM(after_disc) for all lines
tax_amount     = SUM(tax_amt) for all lines
discount_amount= SUM(discount_amt) for all lines
total_amount   = subtotal + tax_amount
```

## Tenant Isolation

All sales orders are scoped by `tenant_id` via the `BelongsToTenant` trait on `SalesOrderModel`.

## Dependencies

- **Core** — `BelongsToTenant` trait, `TenantScope`, pipeline pipes
- **Tenant** — Tenant resolution
- **Product** — `product_id` referenced in order lines

## Key Design Decisions

- Order number format: `SO-{tenantId}-{sequence}` (e.g. `SO-1-000001`)
- Lines are replaced on every `save()` call to ensure consistency
- `withTrashed()` used in `nextOrderNumber()` to prevent sequence gaps
