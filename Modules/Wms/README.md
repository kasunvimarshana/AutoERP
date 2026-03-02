# WMS — Warehouse Management System Module

Extends the Inventory module with physical warehouse location management (zones → aisles → bins) and cycle counting (periodic stock verification).

## Architecture

Follows the standard ERP module architecture:

- **Controller** → **Service** → **Handler** (Pipeline) → **Repository** → **Entity**
- Domain entities are pure PHP objects (no Eloquent)
- Eloquent models are only in the Infrastructure layer
- All queries are scoped by `tenant_id`

## Domain Concepts

| Concept | Description |
|---|---|
| **Zone** | Physical zone within a warehouse (e.g., Cold Storage, Bulk Storage, Picking Zone) |
| **Aisle** | Aisle within a zone (e.g., A, B, C) |
| **Bin** | Individual storage location within an aisle (e.g., A-01-01) |
| **CycleCount** | Scheduled stock verification of selected bins/products |
| **CycleCountLine** | Individual product count entry within a cycle count |

## API Endpoints

### Zones

| Method | Path | Description |
|---|---|---|
| `GET` | `/api/v1/wms/zones?tenant_id=&warehouse_id=` | List zones |
| `POST` | `/api/v1/wms/zones` | Create zone |
| `GET` | `/api/v1/wms/zones/{id}?tenant_id=` | Get zone |
| `PUT` | `/api/v1/wms/zones/{id}` | Update zone |
| `DELETE` | `/api/v1/wms/zones/{id}?tenant_id=` | Delete zone |
| `GET` | `/api/v1/wms/zones/{id}/aisles?tenant_id=` | List aisles in zone |

### Aisles

| Method | Path | Description |
|---|---|---|
| `POST` | `/api/v1/wms/aisles` | Create aisle |
| `GET` | `/api/v1/wms/aisles/{id}?tenant_id=` | Get aisle |
| `PUT` | `/api/v1/wms/aisles/{id}` | Update aisle |
| `DELETE` | `/api/v1/wms/aisles/{id}?tenant_id=` | Delete aisle |
| `GET` | `/api/v1/wms/aisles/{id}/bins?tenant_id=` | List bins in aisle |

### Bins

| Method | Path | Description |
|---|---|---|
| `POST` | `/api/v1/wms/bins` | Create bin |
| `GET` | `/api/v1/wms/bins/{id}?tenant_id=` | Get bin |
| `PUT` | `/api/v1/wms/bins/{id}` | Update bin |
| `DELETE` | `/api/v1/wms/bins/{id}?tenant_id=` | Delete bin |

### Cycle Counts

| Method | Path | Description |
|---|---|---|
| `GET` | `/api/v1/wms/cycle-counts?tenant_id=&warehouse_id=` | List cycle counts |
| `POST` | `/api/v1/wms/cycle-counts` | Create cycle count (draft) |
| `GET` | `/api/v1/wms/cycle-counts/{id}?tenant_id=` | Get cycle count |
| `DELETE` | `/api/v1/wms/cycle-counts/{id}?tenant_id=` | Delete cycle count (draft only) |
| `POST` | `/api/v1/wms/cycle-counts/{id}/start` | Advance draft → in_progress |
| `POST` | `/api/v1/wms/cycle-counts/{id}/lines` | Record a count line |
| `GET` | `/api/v1/wms/cycle-counts/{id}/lines?tenant_id=` | Get count lines |
| `POST` | `/api/v1/wms/cycle-counts/{id}/complete` | Complete cycle count |

## Cycle Count Statuses

```
draft → in_progress → completed
              └→ cancelled
```

## Database Tables

- `wms_zones` — Warehouse zones with unique code per (tenant, warehouse)
- `wms_aisles` — Aisles within zones with unique code per (tenant, zone)
- `wms_bins` — Bin locations within aisles with unique code per (tenant, aisle)
- `wms_cycle_counts` — Cycle count sessions
- `wms_cycle_count_lines` — Individual product count records with variance calculation
