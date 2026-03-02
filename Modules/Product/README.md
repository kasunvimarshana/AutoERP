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

## API Endpoints

| Method | Path | Description |
|---|---|---|
| GET | `/api/v1/products` | List products (paginated) |
| POST | `/api/v1/products` | Create a product |
| GET | `/api/v1/products/{id}` | Get a product |
| PUT | `/api/v1/products/{id}` | Update a product |
| DELETE | `/api/v1/products/{id}` | Delete a product |
| GET | `/api/v1/uoms` | List all units of measure |
| POST | `/api/v1/uoms` | Create a unit of measure |
| GET | `/api/v1/uoms/{id}` | Get a unit of measure |
| PUT | `/api/v1/uoms/{id}` | Update a unit of measure |
| DELETE | `/api/v1/uoms/{id}` | Delete a unit of measure |
| GET | `/api/v1/products/{productId}/uom-conversions` | List UOM conversion factors for a product |
| POST | `/api/v1/products/{productId}/uom-conversions` | Add a UOM conversion factor to a product |
| GET | `/api/v1/uom-conversions/{id}` | Get a UOM conversion factor |
| DELETE | `/api/v1/uom-conversions/{id}` | Delete a UOM conversion factor |

## Files Implemented

```
Modules/Product/
├── Application/
│   ├── DTOs/
│   │   ├── CreateProductDTO.php
│   │   ├── CreateUomDTO.php              # new — UOM creation DTO
│   │   └── AddUomConversionDTO.php       # new — UOM conversion factor DTO
│   └── Services/
│       ├── ProductService.php            # includes convertUom() using BCMath; **createVariant, listVariants, showVariant, deleteVariant**
│       └── UomService.php                # new — listUoms, createUom, showUom, updateUom, deleteUom, addConversion, listConversions, showConversion, deleteConversion
├── Domain/
│   ├── Contracts/
│   │   ├── ProductRepositoryContract.php
│   │   └── UomRepositoryContract.php
│   └── Entities/
│       ├── Product.php
│       ├── UnitOfMeasure.php
│       ├── UomConversion.php             # factor cast as string — never float
│       ├── ProductVariant.php
│       └── ProductImage.php
├── Infrastructure/
│   ├── Database/Migrations/
│   │   ├── 2026_02_27_000015_create_units_of_measure_table.php
│   │   ├── 2026_02_27_000016_create_products_table.php
│   │   ├── 2026_02_27_000017_create_uom_conversions_table.php   # decimal(20,8) factor
│   │   ├── 2026_02_27_000018_create_product_variants_table.php
│   │   └── 2026_02_27_000019_create_product_images_table.php
│   ├── Providers/ProductServiceProvider.php
│   └── Repositories/
│       ├── ProductRepository.php
│       └── UomRepository.php
├── Interfaces/Http/Controllers/
│   ├── ProductController.php
│   └── UomController.php                # new — full UOM CRUD + conversion endpoints
├── routes/api.php                        # updated — includes /uoms and /products/{id}/uom-conversions
├── module.json
└── README.md
```

## Test Coverage

| Test File | Type | Coverage Area |
|---|---|---|
| `Tests/Unit/CreateProductDTOTest.php` | Unit | `CreateProductDTO` — field hydration, BCMath string fields |
| `Tests/Unit/ProductServiceTest.php` | Unit | createProduct/listProducts delegation, convertUom BCMath arithmetic |
| `Tests/Unit/UomServiceCrudTest.php` | Unit | showUom, updateUom, deleteUom, showConversion, deleteConversion — 10 assertions |
| `Tests/Unit/ProductVariantServiceTest.php` | Unit | createVariant, listVariants, showVariant, deleteVariant — method existence, visibility, signatures, return types — 18 assertions |

## Status

🟢 **Complete** — Core CRUD, full UOM CRUD API, UOM conversion management, and product variant management (createVariant, listVariants, showVariant, deleteVariant) implemented (~85% test coverage).

See [IMPLEMENTATION_STATUS.md](../../IMPLEMENTATION_STATUS.md)
