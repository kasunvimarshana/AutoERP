# Manufacturing Module

## Overview

The Manufacturing module provides **Bill of Materials (BOM)** and **Production Order** management for the ERP platform.

Derived from the manufacturing concepts in the `stock-manager-pro-with-pos` reference, re-implemented following strict Clean Architecture and multi-tenant isolation.

## Domain Concepts

| Entity | Description |
|---|---|
| `Bom` | Bill of Materials — defines the component inputs required to produce a finished product |
| `BomLine` | A single component line in a BOM (product, quantity) |
| `ProductionOrder` | An instruction to manufacture a quantity of a finished product using a BOM |

## Status

| Layer | Status |
|---|---|
| Domain (Entities, Enums, Contracts) | ✅ Scaffolded |
| Application (Commands, Handlers) | ✅ Scaffolded |
| Infrastructure (Models, Repository, Migrations) | ✅ Scaffolded |
| Interfaces (Controller, Routes) | ✅ Scaffolded |

## API Endpoints

All endpoints require authentication (`auth:api`) and tenant middleware.

| Method | URI | Description |
|---|---|---|
| `GET` | `/api/v1/manufacturing/boms` | List bills of materials |
| `POST` | `/api/v1/manufacturing/boms` | Create a new BOM |
| `GET` | `/api/v1/manufacturing/boms/{id}` | Retrieve a specific BOM |
| `GET` | `/api/v1/manufacturing/orders` | List production orders |
| `POST` | `/api/v1/manufacturing/orders` | Create a production order |
| `GET` | `/api/v1/manufacturing/orders/{id}` | Retrieve a production order |
| `PATCH` | `/api/v1/manufacturing/orders/{id}/status` | Update production order status |

## Production Order Lifecycle

```
draft → confirmed → in_progress → completed
                 ↘ cancelled
```

## Database Tables

| Table | Description |
|---|---|
| `boms` | Bill of Materials headers (tenant-scoped) |
| `bom_lines` | BOM component lines |
| `production_orders` | Production order records (tenant-scoped) |

## Architecture

- `Bom.scaledComponents()` uses BCMath for precision when scaling component quantities
- All production calculations are BCMath-safe
- Tenant isolation enforced via global scopes on all models
- Production Orders are immutable once completed

## Pending Tasks

- [ ] Write unit tests for BOM domain entity
- [ ] Write feature tests for ManufacturingController
- [ ] Implement stock deduction on production completion (via Inventory module events)
- [ ] Add stock reservation for planned production
- [ ] Add cost calculation based on component unit costs
- [ ] Add OpenAPI documentation
