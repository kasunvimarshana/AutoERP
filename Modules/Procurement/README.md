# Procurement Module

## Overview

The Procurement module manages the supplier master data and the complete purchase order lifecycle within the ERP platform.

## Workflow

```
Draft → Confirmed → Partially Received → Received → Billed → Cancelled
```

- **Draft**: PO created but not yet confirmed.
- **Confirmed**: PO sent to supplier. Goods receipt is now allowed.
- **Partially Received**: At least one line has been partially received.
- **Received**: All line quantities have been fully received; stock ledger entries created.
- **Billed**: Supplier invoice (bill) has been matched to the PO (future Accounting module).
- **Cancelled**: PO cancelled at draft, confirmed, or partially-received stage.

## API Endpoints

### Suppliers

| Method | Endpoint | Description |
|---|---|---|
| GET | `/api/v1/suppliers` | List all suppliers for a tenant |
| POST | `/api/v1/suppliers` | Create a new supplier |
| GET | `/api/v1/suppliers/{id}` | Get a supplier by ID |
| PUT | `/api/v1/suppliers/{id}` | Update a supplier |
| DELETE | `/api/v1/suppliers/{id}` | Soft-delete a supplier |

### Purchase Orders

| Method | Endpoint | Description |
|---|---|---|
| GET | `/api/v1/procurement/orders` | List all POs for a tenant |
| POST | `/api/v1/procurement/orders` | Create a new PO in draft |
| GET | `/api/v1/procurement/orders/{id}` | Get a PO by ID |
| POST | `/api/v1/procurement/orders/{id}/confirm` | Confirm a draft PO |
| POST | `/api/v1/procurement/orders/{id}/receive` | Record goods receipt |
| POST | `/api/v1/procurement/orders/{id}/cancel` | Cancel a PO |
| DELETE | `/api/v1/procurement/orders/{id}` | Soft-delete a PO |

## Goods Receipt

Receiving goods against a PO (`POST /procurement/orders/{id}/receive`) requires:

- `warehouse_id` — target warehouse for stock
- `received_lines` — array of `{ line_id, quantity_received }`
- `notes` — optional receipt notes

Each received line triggers a `receipt` stock ledger entry via the Inventory module's `ReceiveStockHandler`. The PO status transitions to `partially_received` or `received` depending on whether all quantities are fulfilled.

## Financial Precision

All monetary values use BCMath with 4 decimal places. The line total formula is:

```
gross        = quantity × unit_cost
discount_amt = gross × (discount_rate / 100)
after_disc   = gross − discount_amt
tax_amt      = after_disc × (tax_rate / 100)
line_total   = after_disc + tax_amt
```

## Order Number Format

`PO-{tenantId}-{6-digit-sequence}` (e.g. `PO-1-000001`)

## Dependencies

- **Core** — TenantScope, BelongsToTenant, Pipeline pipes
- **Tenant** — Tenant entity and resolution
- **Product** — Product IDs on PO lines
- **Inventory** — ReceiveStockHandler for stock ledger entries on goods receipt
