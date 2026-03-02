# Product Module

## Overview

The Product Module is the foundational catalog module for the ERP system. It manages product definitions including SKU, variants, multiple Units of Measure (UOM), costing methods, multiple product images, multiple dynamic extensible attributes, and GS1/barcode support.

## Architecture

This module follows the strict **Controller → Service → Handler (with Pipeline) → Repository → Entity** pattern.

| Layer | Location | Responsibility |
|---|---|---|
| Domain | `Domain/` | Entities, contracts, enums, value objects |
| Application | `Application/` | Commands, handlers, services, pipeline |
| Infrastructure | `Infrastructure/` | Eloquent models, repositories, migrations |
| Interfaces | `Interfaces/` | HTTP controllers, requests, resources, routes |

## API Endpoints

### Products

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/v1/products?tenant_id={id}` | List all products for a tenant |
| POST | `/api/v1/products` | Create a new product |
| GET | `/api/v1/products/{id}?tenant_id={id}` | Get a product by ID |
| PUT | `/api/v1/products/{id}?tenant_id={id}` | Update a product |
| DELETE | `/api/v1/products/{id}?tenant_id={id}` | Soft-delete a product |

### Units of Measure (UOM) Conversions

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/v1/products/{id}/uom-conversions?tenant_id={id}` | List UOM conversions |
| POST | `/api/v1/products/{id}/uom-conversions` | Set/replace UOM conversions |

### Product Images

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/v1/products/{id}/images?tenant_id={id}` | List product images |
| POST | `/api/v1/products/{id}/images` | Set URL-sourced images (JSON) |
| POST | `/api/v1/products/{id}/images/upload` | Upload a single image file |
| DELETE | `/api/v1/products/{id}/images/{imageId}` | Delete a product image |

### Product Attributes

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/v1/products/{id}/attributes?tenant_id={id}` | List product attributes |
| POST | `/api/v1/products/{id}/attributes` | Set/replace all attributes |

## Product Types

- `stockable` — Physical product tracked in inventory
- `consumable` — Consumed product, not tracked in inventory
- `service` — Service product, no physical stock
- `digital` — Digital product (downloads, licenses)
- `bundle` — Kit/bundle of multiple products
- `composite` — Manufactured product from BOM
- `variant` — Configurable product template; purchasable/saleable units are distinct variants (e.g. a T-shirt in multiple sizes/colours). Variant management infrastructure is planned; this type is registered now so API consumers can classify products correctly.

## Costing Methods

- `fifo` — First In, First Out
- `lifo` — Last In, First Out
- `weighted_average` — Weighted Average Cost

## Units of Measure (Multi-UOM)

Each product supports three independent UOM fields:

| Field | Description | Nullable |
|---|---|---|
| `uom` | Inventory / stock UOM (e.g. `pcs`) | No |
| `buying_uom` | UOM used when purchasing (e.g. `box`) | Yes (falls back to `uom`) |
| `selling_uom` | UOM used when selling (e.g. `pack`) | Yes (falls back to `uom`) |

UOM conversion factors are stored per-product in the `uom_conversions` table. The repository resolves conversions bidirectionally: first by a direct row match (`from_uom → to_uom`), then by an inverse row match (`to_uom → from_uom`) with the factor automatically inverted via `bcdiv`. All arithmetic uses BCMath.

## Product Images

Images support two sources controlled by the `image_source_type` discriminator:

| Source Type | Description |
|---|---|
| `url` | External image URL; set via JSON batch (`POST /images`) |
| `upload` | Platform-stored file; uploaded via multipart (`POST /images/upload`) |

Files are stored on the private `local` disk under `tenants/{tenantId}/products/`. Uploaded images are served via 30-minute temporary signed URLs.

## Product Attributes

Dynamic, extensible key-value attributes support rich metadata per product:

| Field | Type | Description |
|---|---|---|
| `attribute_key` | string | Unique key per product (e.g. `material`) |
| `attribute_label` | string | Human-readable label (e.g. `Material`) |
| `attribute_value` | string | The value (e.g. `Cotton`) |
| `attribute_type` | string | Data type hint: `text`, `number`, `boolean`, `date`, `url` (driven by `ProductAttributeType` enum) |
| `sort_order` | int | Display order |

## Key Design Decisions

- SKU is immutable after creation (stored as value object, normalised to uppercase)
- Pipeline pattern applied in all write handlers (`ValidateCommandPipe → AuditLogPipe → persistence`)
- Tenant isolation enforced via `BelongsToTenant` trait on all product-related Eloquent models
- Monetary values stored as `DECIMAL(20,4)` using BCMath for precision
- Unique constraint on `(tenant_id, sku)` at the database level
- `buying_uom` / `selling_uom` nullable; helper methods `effectiveBuyingUom()` / `effectiveSellingUom()` on domain entity fall back to `uom` when null
- `replaceForProduct()` pattern used for UOM conversions, images, and attributes (atomic delete + re-insert)
- Inverse UOM conversion is computed at **read time** (`convert()` call) — only one direction needs to be stored per conversion pair; the inverse factor is derived via `bcdiv` on-demand and is never persisted separately
