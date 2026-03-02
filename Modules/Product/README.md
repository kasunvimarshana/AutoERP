# Product Module

## Overview

The **Product** module manages the enterprise product catalog. It supports multiple product types, SKU management, multi-UOM with conversion matrix, product variants, multi-image management, and multi-currency/location pricing.

---

## Supported Product Types

| Type | Description |
|---|---|
| Physical (Stockable) | Tracked inventory item |
| Consumable | Consumed on use, not tracked in stock |
| Service | Non-physical service offering |
| Digital | Downloadable digital goods |
| Bundle (Kit) | Pre-packaged set of individual products |
| Composite (Manufactured) | Product assembled from components |
| Variant-based | Products with attribute variants (size, color, etc.) |

---

## Responsibilities

- Product CRUD (tenant-scoped)
- SKU management and uniqueness enforcement
- UOM (Unit of Measure) configuration:
  - Base UOM (`uom`) — required
  - Buying UOM (`buying_uom`) — optional, fallback to base
  - Selling UOM (`selling_uom`) — optional, fallback to base
- UOM conversion matrix (`uom_conversions` table)
- Product variant management
- Multi-image management (0..n images per product)
- Barcode / QR code support
- GS1 compatibility (optional enterprise feature)
- Costing method configuration (FIFO / LIFO / Weighted Average)
- Traceability configuration (Serial / Batch / Lot — optional)

## Financial Rules

- All price and cost calculations use **BCMath only**
- Minimum **4 decimal places** precision
- Intermediate calculations (further divided or multiplied before final rounding): **8+ decimal places**
- Final monetary values: rounded to the **currency's standard precision (typically 2 decimal places)**
- No floating-point arithmetic

---

## Architecture Layer

```
Modules/Product/
 ├── Application/       # Product CRUD, variant management, image upload use cases
 ├── Domain/            # Product entity, UOM value objects, ProductRepository contract
 ├── Infrastructure/    # ProductRepository, ProductServiceProvider, image storage
 ├── Interfaces/        # ProductController, ProductResource, StoreProductRequest
 ├── module.json
 └── README.md
```

---

## Architecture Compliance

| Rule | Status |
|---|---|
| No business logic in controllers | ✅ Enforced |
| No query builder calls in controllers | ✅ Enforced |
| Tenant isolation enforced (`tenant_id` + global scope) | ✅ Enforced |
| All financial calculations use BCMath (no float) | ✅ Enforced |
| Base UOM (`uom`) is required on every product | ✅ Required |
| UOM conversions use explicit product-specific factors only (no implicit conversion) | ✅ Enforced |
| No cross-module coupling (communicates via contracts/events) | ✅ Enforced |

---

## Dependencies

- `core`
- `tenancy`
- `metadata`

---

## Status

🔴 **Planned** — See [IMPLEMENTATION_STATUS.md](../../IMPLEMENTATION_STATUS.md)
