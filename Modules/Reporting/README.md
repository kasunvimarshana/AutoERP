# Reporting Module

## Overview

The Reporting module provides configurable dashboards, a custom report builder, and a standard reports library. All dashboards and reports are tenant-scoped and user-owned, with optional sharing.

---

## Features

- **Dashboards** – Create, configure, and share role-based dashboards with configurable widget layout and auto-refresh
- **Report builder** – Save custom reports with data source, fields, filters, group-by, sort-by, and sharing options
- **Report types** – `sales`, `purchase`, `inventory`, `accounting`, `hr`, `pos`, `crm`, `project`, `custom`
- **Domain events** – `DashboardCreated`, `ReportSaved`
- **Tenant isolation** – Global tenant scope via `HasTenantScope` trait

---

## API Endpoints

| Method | URI | Description |
|--------|-----|-------------|
| GET | `api/v1/reporting/dashboards` | List tenant dashboards |
| POST | `api/v1/reporting/dashboards` | Create dashboard |
| GET | `api/v1/reporting/dashboards/{id}` | Get dashboard |
| PUT | `api/v1/reporting/dashboards/{id}` | Update dashboard |
| DELETE | `api/v1/reporting/dashboards/{id}` | Delete dashboard |
| GET | `api/v1/reporting/reports` | List tenant reports |
| POST | `api/v1/reporting/reports` | Save report definition |
| GET | `api/v1/reporting/reports/{id}` | Get report |
| PUT | `api/v1/reporting/reports/{id}` | Update report |
| DELETE | `api/v1/reporting/reports/{id}` | Delete report |

---

## Domain Events

| Event | Payload | Trigger |
|-------|---------|---------|
| `DashboardCreated` | `dashboardId`, `tenantId`, `name` | New dashboard created |
| `ReportSaved` | `reportId`, `tenantId`, `name` | Report saved |

---

## Architecture

- **Domain**: `ReportType` enum, `WidgetType` enum, `DashboardRepositoryInterface`, `ReportRepositoryInterface`, domain events
- **Application**: `CreateDashboardUseCase`, `SaveReportUseCase`
- **Infrastructure**: `DashboardModel`, `ReportModel` (SoftDeletes + HasTenantScope), migrations, repositories
- **Presentation**: `DashboardController`, `ReportController`, `StoreDashboardRequest`, `StoreReportRequest`
