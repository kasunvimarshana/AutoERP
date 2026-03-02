# Reporting Module

## Overview

The **Reporting** module provides enterprise-grade reporting capabilities across all platform modules. All reports are tenant-scoped, filterable, exportable, and must never break transactional integrity.

---

## Supported Reports

- Aggregated financial statements (P&L, Balance Sheet, Trial Balance)
- Inventory valuation reports (by costing method)
- Aging reports (accounts receivable / payable)
- Tax summaries
- Inventory turnover analysis
- Sales performance reports
- Procurement spend analysis
- Custom report builder

## Export Formats

- CSV
- PDF

---

## Architecture Layer

```
Modules/Reporting/
 ├── Application/       # Generate report, schedule report, export report use cases
 ├── Domain/            # ReportDefinition entity, ReportFilter value objects
 ├── Infrastructure/    # ReportingRepository, ReportingServiceProvider, PDF/CSV generators
 ├── Interfaces/        # ReportController, ReportExportController
 ├── module.json
 └── README.md
```

---

## Dependencies

- `core`
- `tenancy`

---

## Architecture Compliance

| Rule | Status |
|---|---|
| No business logic in controllers | ✅ Enforced |
| No query builder calls in controllers | ✅ Enforced |
| All reports tenant-scoped | ✅ Enforced |
| Reports never break transactional integrity | ✅ Required |
| Reports filterable, exportable, and auditable | ✅ Required |
| No cross-module coupling (reads via published contracts/read models) | ✅ Enforced |

---

## Implemented Files

### Migrations
| File | Table |
|---|---|
| `2026_02_27_000060_create_report_definitions_table.php` | `report_definitions` |
| `2026_02_27_000061_create_report_schedules_table.php` | `report_schedules` |
| `2026_02_27_000062_create_report_exports_table.php` | `report_exports` |

### Domain Entities
- `ReportDefinition` — HasTenant + SoftDeletes; filters/columns/sort_config as array
- `ReportSchedule` — HasTenant
- `ReportExport` — HasTenant; status pending/processing/completed/failed

### Application Layer
- `GenerateReportDTO` — fromArray/toArray; exportFormat defaults to `csv`, filters default to `[]`
- `ReportingService` — listDefinitions, createDefinition, generateReport, scheduleReport, showDefinition, updateDefinition, deleteDefinition, listSchedules, showSchedule, listExports, showExport, updateSchedule, deleteSchedule (all mutations in DB::transaction)

### Infrastructure Layer
- `ReportingRepositoryContract` — findByType, findBySlug
- `ReportingRepository` — extends AbstractRepository on ReportDefinition
- `ReportingServiceProvider` — binds contract, loads migrations and routes

### API Routes (`/api/v1`)
| Method | Path | Action |
|---|---|---|
| GET | `/reporting/definitions` | listDefinitions |
| POST | `/reporting/definitions` | createDefinition |
| GET | `/reporting/definitions/{id}` | showDefinition |
| PUT | `/reporting/definitions/{id}` | updateDefinition |
| DELETE | `/reporting/definitions/{id}` | deleteDefinition |
| POST | `/reporting/generate` | generateReport |
| POST | `/reporting/schedules` | scheduleReport |
| GET | `/reporting/schedules` | listSchedules |
| GET | `/reporting/schedules/{id}` | showSchedule |
| PUT | `/reporting/schedules/{id}` | updateSchedule |
| DELETE | `/reporting/schedules/{id}` | deleteSchedule |
| GET | `/reporting/exports` | listExports |
| GET | `/reporting/exports/{id}` | showExport |

---

## Test Coverage

| Test File | Type | Coverage Area |
|---|---|---|
| `Tests/Unit/GenerateReportDTOTest.php` | Unit | `GenerateReportDTO` — hydration, defaults, toArray round-trip |
| `Tests/Unit/ReportingServiceTest.php` | Unit | listDefinitions delegation, GenerateReportDTO field mapping |
| `Tests/Unit/ReportingServiceScheduleTest.php` | Unit | scheduleReport/createDefinition field mapping, export format variants |
| `Tests/Unit/ReportingServiceExportTest.php` | Unit | generateReport payload, DTO defaults, format variants |
| `Tests/Unit/ReportingServiceReadPathTest.php` | Unit | showDefinition delegation, ModelNotFoundException, method signature |
| `Tests/Unit/ReportingServiceCrudTest.php` | Unit | updateDefinition, deleteDefinition, listSchedules, listExports, showExport, updateSchedule, deleteSchedule — 25 assertions |
| `Tests/Unit/ReportingServiceShowScheduleTest.php` | Unit | `showSchedule` — method existence, public visibility, int parameter, ReportSchedule return type, not static — 6 assertions |
| `Tests/Unit/ReportingServiceDelegationTest.php` | Unit | listDefinitions/showDefinition delegation, Collection type contracts, updateDefinition signature, regression guards — 14 assertions |

---

## Status

🟢 **Complete** — Core reporting flow implemented; full definition, generation, scheduling, export, and schedule management endpoints implemented; showSchedule endpoint implemented; delegation test coverage complete (~85% test coverage). See [IMPLEMENTATION_STATUS.md](../../IMPLEMENTATION_STATUS.md)
