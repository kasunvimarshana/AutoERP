# Notification Module

Tenant-scoped notification system with template management, multi-channel support (in-app, email, SMS), and per-user notification tracking.

## Architecture

Follows the standard module architecture: **Controller → Service → Handler (Pipeline) → Repository → Entity**

## Features

- **Notification Templates**: Manage reusable templates per channel and event type
- **Multi-channel support**: `email`, `sms`, `in_app`
- **Per-user notification tracking**: Delivered, read, unread notifications
- **Tenant-scoped**: All data is isolated per tenant

## API Endpoints

### Templates

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/v1/notifications/templates` | List templates |
| POST | `/api/v1/notifications/templates` | Create template |
| GET | `/api/v1/notifications/templates/{id}` | Get template |
| PUT | `/api/v1/notifications/templates/{id}` | Update template |
| DELETE | `/api/v1/notifications/templates/{id}` | Delete template |

### Notifications

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/v1/notifications/` | List notifications for user |
| POST | `/api/v1/notifications/send` | Send notification |
| GET | `/api/v1/notifications/unread` | Get unread notifications |
| GET | `/api/v1/notifications/{id}` | Get notification |
| PUT | `/api/v1/notifications/{id}/read` | Mark as read |
| DELETE | `/api/v1/notifications/{id}` | Delete notification |

## Enums

- `NotificationChannel`: `email`, `sms`, `in_app`
- `NotificationStatus`: `pending`, `sent`, `failed`, `read`

## Database Tables

- `notification_templates`: Templates per tenant/channel/event_type (unique constraint)
- `notifications`: Per-user notifications with status tracking (indexed by tenant/user/status)
