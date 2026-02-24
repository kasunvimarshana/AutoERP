# Manufacturing Module

Provides Bill of Materials (BOM) management, Work Order creation, and production tracking for the ERP platform.

## Features

- **Bill of Materials (BOM)**: Define multi-level BOMs with component lines, quantities, units, and scrap rates. Supports draft/active/obsolete lifecycle.
- **Work Orders**: Create work orders from active BOMs. Component requirements are automatically calculated using BCMath with scrap rate adjustments.
- **Production Tracking**: Start and complete work orders, recording actual start/end timestamps and quantity produced.

## Architecture

Follows strict Clean Architecture with four layers:

- **Domain**: Entities (`BillOfMaterials`, `BomLine`, `WorkOrder`, `WorkOrderLine`), Enums (`BomStatus`, `WorkOrderStatus`), Events (`WorkOrderStarted`, `WorkOrderCompleted`), and Repository Contracts.
- **Infrastructure**: Eloquent models with `HasTenantScope` and UUID primary keys, migrations (`mfg_boms`, `mfg_bom_lines`, `mfg_work_orders`, `mfg_work_order_lines`), and repository implementations.
- **Application**: Use cases — `CreateBomUseCase`, `CreateWorkOrderUseCase`, `StartWorkOrderUseCase`, `CompleteWorkOrderUseCase`.
- **Presentation**: Form request validation, `BomController`, `WorkOrderController`.

## API Endpoints

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/v1/manufacturing/boms` | List BOMs (paginated) |
| POST | `/api/v1/manufacturing/boms` | Create BOM with lines |
| GET | `/api/v1/manufacturing/boms/{id}` | Get BOM detail |
| PUT | `/api/v1/manufacturing/boms/{id}` | Update BOM |
| DELETE | `/api/v1/manufacturing/boms/{id}` | Delete BOM |
| GET | `/api/v1/manufacturing/work-orders` | List work orders (paginated) |
| POST | `/api/v1/manufacturing/work-orders` | Create work order from BOM |
| GET | `/api/v1/manufacturing/work-orders/{id}` | Get work order detail |
| PUT | `/api/v1/manufacturing/work-orders/{id}` | Update work order |
| DELETE | `/api/v1/manufacturing/work-orders/{id}` | Delete work order |
| POST | `/api/v1/manufacturing/work-orders/{id}/start` | Start production |
| POST | `/api/v1/manufacturing/work-orders/{id}/complete` | Complete production |

## Key Design Decisions

- All monetary/quantity calculations use BCMath (scale 8) — no floating-point arithmetic.
- Reference numbers follow the format `WO-YYYY-XXXXXX` (zero-padded 6-digit sequence per tenant per year).
- All write operations are wrapped in `DB::transaction()`.
- Tenant isolation enforced via `HasTenantScope` global scope on all models.
- Domain events (`WorkOrderStarted`, `WorkOrderCompleted`) emitted on state transitions.
