# Field Service Module

## Overview

The Field Service module manages on-site service operations — creating service orders, assigning technicians, tracking visits, and completing field jobs with cost recording.

## Features

- **Service Teams**: Group technicians into teams for workload management.
- **Service Orders**: Full lifecycle management from `new` → `assigned` → `in_progress` → `done` → `invoiced`.
- **Technician Assignment**: Assign a technician to an order, triggering `ServiceOrderAssigned` event.
- **Completion Reporting**: Record `duration_hours`, `labor_cost`, `parts_cost`, and `resolution_notes` on completion.

## Architecture

| Layer | Components |
|-------|-----------|
| Domain | `ServiceTeamRepositoryInterface`, `ServiceOrderRepositoryInterface`, `ServiceOrderStatus` enum, `ServiceOrderAssigned`, `ServiceOrderCompleted` events |
| Application | `CreateServiceOrderUseCase`, `AssignTechnicianUseCase`, `CompleteServiceOrderUseCase` |
| Infrastructure | `ServiceTeamModel`, `ServiceOrderModel`, repositories, migrations (`fs_service_teams`, `fs_service_orders`) |
| Presentation | `ServiceTeamController`, `ServiceOrderController`, form requests |

## API Endpoints

| Method | URI | Description |
|--------|-----|-------------|
| GET/POST | `api/v1/field-service/teams` | List / create service teams |
| GET/PUT/DELETE | `api/v1/field-service/teams/{id}` | Read / update / soft-delete team |
| GET/POST | `api/v1/field-service/orders` | List / create service orders |
| GET/DELETE | `api/v1/field-service/orders/{id}` | Read / soft-delete service order |
| POST | `api/v1/field-service/orders/{id}/assign` | Assign technician |
| POST | `api/v1/field-service/orders/{id}/complete` | Mark order as done |

## Service Order Lifecycle

```
new → assigned → in_progress → done → invoiced
                             → cancelled
```

## Domain Events

| Event | Trigger |
|-------|---------|
| `ServiceOrderAssigned` | Technician assigned to order |
| `ServiceOrderCompleted` | Order transitions to `done` |

## Integration Notes

- `ServiceOrderAssigned` can trigger a push notification to the assigned technician via Notification module.
- `ServiceOrderCompleted` can trigger invoice creation in the Accounting module.
- `labor_cost` and `parts_cost` are `DECIMAL(18,8)` — BCMath used for any aggregations.
- Reference numbers follow the `FSO-YYYY-XXXXXX` pattern per tenant per year.
