# Maintenance Module

## Overview

The Maintenance module manages equipment/machinery maintenance lifecycle for manufacturing and operations environments.

## Features

- **Equipment Registry** — Register and manage machinery/equipment with serial number, category, location, and assignment tracking (`active`, `under_maintenance`, `decommissioned` lifecycle)
- **Maintenance Requests** — Corrective maintenance requests raised by operators with priority levels (`low`, `medium`, `high`, `critical`)
- **Maintenance Orders** — Preventive and corrective maintenance work orders with reference numbers (`MO-YYYY-XXXXXX`), cost tracking, and status lifecycle (`draft → confirmed → in_progress → done`)
- **BCMath financials** — All cost fields (`labor_cost`, `parts_cost`) use `DECIMAL(18,8)` with BCMath normalisation
- **Domain Events** — `EquipmentRegistered`, `EquipmentDecommissioned`, `MaintenanceRequestCreated`, `MaintenanceOrderStarted`, `MaintenanceOrderCompleted`
- **Tenant isolation** — `tenant_id` on all tables, global scope enforced via `HasTenantScope` trait

## API Endpoints

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `api/v1/maintenance/equipment` | List all equipment |
| POST | `api/v1/maintenance/equipment` | Register new equipment |
| GET | `api/v1/maintenance/equipment/{id}` | Get equipment details |
| PUT | `api/v1/maintenance/equipment/{id}` | Update equipment |
| POST | `api/v1/maintenance/equipment/{id}/decommission` | Decommission equipment |
| DELETE | `api/v1/maintenance/equipment/{id}` | Delete equipment |
| GET | `api/v1/maintenance/requests` | List maintenance requests |
| POST | `api/v1/maintenance/requests` | Create maintenance request |
| GET | `api/v1/maintenance/requests/{id}` | Get request details |
| DELETE | `api/v1/maintenance/requests/{id}` | Delete request |
| GET | `api/v1/maintenance/orders` | List maintenance orders |
| POST | `api/v1/maintenance/orders` | Create maintenance order |
| GET | `api/v1/maintenance/orders/{id}` | Get order details |
| POST | `api/v1/maintenance/orders/{id}/start` | Start maintenance order |
| POST | `api/v1/maintenance/orders/{id}/complete` | Complete maintenance order |
| DELETE | `api/v1/maintenance/orders/{id}` | Delete order |

## Architecture

```
Maintenance/
├── Domain/
│   ├── Contracts/
│   │   ├── EquipmentRepositoryInterface.php
│   │   ├── MaintenanceRequestRepositoryInterface.php
│   │   └── MaintenanceOrderRepositoryInterface.php
│   ├── Enums/
│   │   ├── EquipmentStatus.php
│   │   ├── MaintenanceOrderStatus.php
│   │   ├── MaintenanceOrderType.php
│   │   └── MaintenanceRequestStatus.php
│   └── Events/
│       ├── EquipmentRegistered.php
│       ├── EquipmentDecommissioned.php
│       ├── MaintenanceRequestCreated.php
│       ├── MaintenanceOrderStarted.php
│       └── MaintenanceOrderCompleted.php
├── Application/
│   └── UseCases/
│       ├── RegisterEquipmentUseCase.php
│       ├── DecommissionEquipmentUseCase.php
│       ├── CreateMaintenanceRequestUseCase.php
│       ├── CreateMaintenanceOrderUseCase.php
│       ├── StartMaintenanceOrderUseCase.php
│       └── CompleteMaintenanceOrderUseCase.php
├── Infrastructure/
│   ├── Migrations/
│   ├── Models/
│   └── Repositories/
├── Presentation/
│   ├── Controllers/
│   └── Requests/
└── Providers/
    └── MaintenanceServiceProvider.php
```

## Integration Notes

- `MaintenanceOrderStarted` event transitions equipment status to `under_maintenance` — downstream listeners can update Manufacturing module work order availability
- `MaintenanceOrderCompleted` event transitions equipment status back to `active` — downstream listeners can resume Manufacturing scheduling
- `EquipmentDecommissioned` event can trigger Audit module logging and Asset Management write-off journal entries
- `MaintenanceRequestCreated` event can trigger Notification module dispatch (alert to maintenance team)
- The module is independent of Fleet Management — Fleet tracks vehicles, Maintenance tracks production equipment/machinery

## Guard Rules

| Guard | Condition |
|-------|-----------|
| Decommission guard | Cannot decommission an already decommissioned equipment |
| Request guard | Cannot raise a request for decommissioned equipment |
| Order guard | Cannot create an order for decommissioned equipment |
| Start guard | Cannot start a done, cancelled, or already in-progress order |
| Complete guard | Only `in_progress` orders can be completed |
