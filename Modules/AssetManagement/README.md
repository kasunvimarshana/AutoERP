# Asset Management Module

## Overview

The Asset Management module tracks fixed assets throughout their lifecycle — from acquisition through depreciation to disposal. All monetary values use `DECIMAL(18,8)` with BCMath arithmetic, consistent with the platform's financial integrity rules.

## Features

- **Asset Categories**: Tenant-scoped categories (IT Equipment, Vehicles, Machinery, Furniture, etc.)
- **Asset Registration**: Record acquisition with purchase cost, salvage value, useful life, and depreciation method.
- **Depreciation Calculation**: Straight-line annual depreciation computed via BCMath on registration (`(purchase_cost − salvage_value) / useful_life_years`).
- **Asset Disposal**: Marks assets as disposed; records disposal value and notes; emits `AssetDisposed` domain event.
- **BCMath Financials**: All cost/value fields stored as `DECIMAL(18,8)`; `bcsub`/`bcdiv`/`bccomp` used throughout — no floating-point arithmetic.
- **Tenant Isolation**: Every record is scoped to `tenant_id` via the global `HasTenantScope` trait.

## API Endpoints

| Method | URI | Description |
|--------|-----|-------------|
| GET/POST | `api/v1/assets/categories` | List / create asset categories |
| GET/PUT/DELETE | `api/v1/assets/categories/{id}` | Read / update / soft-delete category |
| GET/POST | `api/v1/assets` | List / register new assets |
| GET/PUT/DELETE | `api/v1/assets/{id}` | Read / update / soft-delete asset |
| POST | `api/v1/assets/{id}/dispose` | Dispose of an active asset |

## Domain Events

| Event | Trigger |
|-------|---------|
| `AssetAcquired` | When a new asset is registered |
| `AssetDisposed` | When an asset is marked as disposed |

## Status Lifecycle

```
active → disposed
active → under_maintenance → active
```

## Depreciation Methods

| Method | Description |
|--------|-------------|
| `straight_line` | Annual depreciation = (purchase_cost − salvage_value) / useful_life_years |
| `declining_balance` | Stored for future implementation of period-end depreciation journal posting |

## Architecture

| Layer | Component |
|-------|-----------|
| Application | `CreateAssetCategoryUseCase`, `RegisterAssetUseCase`, `DisposeAssetUseCase` |
| Domain | `AssetStatus` / `DepreciationMethod` enums, `AssetAcquired` / `AssetDisposed` events, repository contracts |
| Infrastructure | `AssetCategoryModel`, `AssetModel`, `AssetCategoryRepository`, `AssetRepository`, migrations |
| Presentation | `AssetCategoryController`, `AssetController`, form request validators |
