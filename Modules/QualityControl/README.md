# Quality Control Module

## Overview

The Quality Control module provides product quality inspection management, non-conformance tracking, and quality alert workflows — following the same Clean Architecture patterns as all other modules in this platform.

## Features

- **Quality Control Points**: Configurable rules defining when quality checks should be triggered (per product, operation type, or team).
- **Inspections**: Actual inspection instances with pass/fail lifecycle and BCMath quantity tracking.
- **Quality Alerts**: Non-conformance notifications with priority levels and assignment workflow.

## Architecture

| Layer | Components |
|-------|-----------|
| Domain | `QualityPointRepositoryInterface`, `InspectionRepositoryInterface`, `QualityAlertRepositoryInterface`, enums (`InspectionStatus`, `AlertStatus`, `AlertPriority`), events (`InspectionPassed`, `InspectionFailed`, `QualityAlertRaised`) |
| Application | `CreateInspectionUseCase`, `PassInspectionUseCase`, `FailInspectionUseCase`, `CreateQualityAlertUseCase` |
| Infrastructure | `QualityPointModel`, `InspectionModel`, `QualityAlertModel`, corresponding repositories, migrations |
| Presentation | `QualityPointController`, `InspectionController`, `QualityAlertController`, form requests |

## API Endpoints

| Method | URI | Description |
|--------|-----|-------------|
| GET/POST | `api/v1/qc/quality-points` | List / create quality control points |
| GET/PUT/DELETE | `api/v1/qc/quality-points/{id}` | Read / update / soft-delete QC point |
| GET/POST | `api/v1/qc/inspections` | List / create inspections |
| GET/DELETE | `api/v1/qc/inspections/{id}` | Read / soft-delete inspection |
| POST | `api/v1/qc/inspections/{id}/pass` | Mark inspection as passed |
| POST | `api/v1/qc/inspections/{id}/fail` | Mark inspection as failed |
| GET/POST | `api/v1/qc/alerts` | List / create quality alerts |
| GET/DELETE | `api/v1/qc/alerts/{id}` | Read / soft-delete alert |

## Inspection Lifecycle

```
draft → in_progress → passed
                   → failed → (triggers QualityAlertRaised event)
```

## Domain Events

| Event | Trigger |
|-------|---------|
| `InspectionPassed` | Inspection transitions to `passed` |
| `InspectionFailed` | Inspection transitions to `failed` |
| `QualityAlertRaised` | New quality alert created |

## Integration Notes

- `InspectionFailed` event can trigger automatic `QualityAlert` creation via listener.
- `QualityAlertRaised` can notify QC team via Notification module.
- `inspection_id` foreign key links alerts back to the triggering inspection.
- All quantities use `DECIMAL(18,8)` — no floating-point arithmetic.
- `qty_failed` is validated to never exceed `qty_inspected` at the use-case boundary.
