# Fleet Management Module

Vehicle registry with maintenance record tracking, fuel type classification, and an active/maintenance/retired lifecycle.

## Features

- **Vehicles** — register fleet vehicles with plate number, make/model/year, VIN, fuel type, and optional driver assignment
- **Maintenance Records** — log service events (oil change, tire rotation, inspection, repair) with BCMath cost tracking and odometer readings
- **Retire Lifecycle** — vehicles transition through `active → maintenance → retired`; retired vehicles cannot receive new maintenance records
- Tenant-isolated; all queries scoped via `HasTenantScope`
- All `cost` values stored as `DECIMAL(18,8)`; BCMath `bcadd` normalisation on record creation

## Status Lifecycles

### Vehicle
`active` → `maintenance` → `retired`

## API Endpoints

| Method | URI | Description |
|--------|-----|-------------|
| GET/POST | `api/v1/fleet/vehicles` | List / register vehicles |
| GET/PUT/DELETE | `api/v1/fleet/vehicles/{id}` | Read / update / soft-delete vehicle |
| POST | `api/v1/fleet/vehicles/{id}/retire` | Retire an active vehicle |
| GET/POST | `api/v1/fleet/vehicles/{vehicleId}/maintenance` | List / log maintenance records |
| GET/DELETE | `api/v1/fleet/vehicles/{vehicleId}/maintenance/{id}` | Read / delete maintenance record |

## Domain Events

| Event | Trigger |
|-------|---------|
| `VehicleRegistered` | New vehicle registered in the fleet |
| `MaintenanceLogged` | Maintenance record added to a vehicle |
| `VehicleRetired` | Vehicle status set to `retired` |

## Dependencies

- Tenant, User, Audit
