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

## Status

🔴 **Planned** — See [IMPLEMENTATION_STATUS.md](../../IMPLEMENTATION_STATUS.md)
