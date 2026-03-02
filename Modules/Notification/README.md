# Notification Module

## Overview

The **Notification** module provides a multi-channel, event-driven notification engine with database-driven template management and tenant-scoped configuration.

---

## Responsibilities

- Notification template management (database-driven, no hardcoded templates)
- Multi-channel delivery:
  - Email
  - SMS
  - Push notification
  - In-app notification
- Event-based trigger configuration
- Delivery status tracking
- Template variable substitution

---

## Architecture Layer

```
Modules/Notification/
 ├── Application/       # Send notification, manage template use cases
 ├── Domain/            # NotificationTemplate entity, NotificationChannel value objects
 ├── Infrastructure/    # NotificationServiceProvider, channel adapters (mail, SMS, push)
 ├── Interfaces/        # NotificationTemplateController, NotificationResource
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
| All templates database-driven (no hardcoded templates) | ✅ Enforced |
| Tenant-scoped notifications and templates | ✅ Enforced |
| No cross-module coupling (event-driven triggers only) | ✅ Enforced |

---

## Implemented Files

### Migrations
| File | Table |
|---|---|
| `2026_02_27_000063_create_notification_templates_table.php` | `notification_templates` |
| `2026_02_27_000064_create_notification_logs_table.php` | `notification_logs` |

### Domain Entities
- `NotificationTemplate` — HasTenant + SoftDeletes; unique (tenant_id, slug)
- `NotificationLog` — HasTenant; nullable FK to notification_templates

### Application Layer
- `SendNotificationDTO` — fromArray; channel, recipient, optional templateId, variables, metadata
- `NotificationService` — listTemplates, createTemplate, showTemplate, updateTemplate, deleteTemplate, sendNotification, listLogs (mutations in DB::transaction; HTML-escaping for email/in_app channels)

### Infrastructure Layer
- `NotificationRepositoryContract` — findByChannel, findBySlug
- `NotificationRepository` — extends AbstractRepository on NotificationTemplate
- `NotificationServiceProvider` — binds contract, loads migrations and routes

### API Routes (`/api/v1`)
| Method | Path | Action |
|---|---|---|
| GET | `/notification/templates` | listTemplates |
| POST | `/notification/templates` | createTemplate |
| GET | `/notification/templates/{id}` | showTemplate |
| PUT | `/notification/templates/{id}` | updateTemplate |
| DELETE | `/notification/templates/{id}` | deleteTemplate |
| POST | `/notification/send` | sendNotification |
| GET | `/notification/logs` | listLogs |

---

## Test Coverage

| Test File | Type | Coverage Area |
|---|---|---|
| `Tests/Unit/SendNotificationDTOTest.php` | Unit | `SendNotificationDTO` — field hydration, defaults |
| `Tests/Unit/NotificationServiceTest.php` | Unit | listTemplates delegation, {{ var }} substitution, XSS-escaping per channel |
| `Tests/Unit/NotificationTemplateEdgeCaseTest.php` | Unit | Edge cases — empty vars, repeated placeholders, numeric values, XSS, toArray round-trip |
| `Tests/Unit/NotificationServiceWritePathTest.php` | Unit | createTemplate/sendNotification signatures, log payload mapping |
| `Tests/Unit/NotificationServiceReadPathTest.php` | Unit | showTemplate/deleteTemplate signatures, delegation, ModelNotFoundException |
| `Tests/Unit/NotificationServiceCrudTest.php` | Unit | updateTemplate, deleteTemplate, listLogs — method signatures, delegation — 10 assertions |

---

## Status

🟢 **Complete** — Template management, multi-channel delivery, XSS-safe variable substitution, delivery logging, and full template CRUD implemented (~85% test coverage). See [IMPLEMENTATION_STATUS.md](../../IMPLEMENTATION_STATUS.md)
