# Notification Module
Multi-channel notification system with template management and delivery records.
## Features
- In-app, email, SMS, push, webhook channels
- Template management per event type and channel
- Mark as read / mark all as read / bulk mark all read
- Unread count endpoint for notification bell badge
- Delete individual notifications
- Queued delivery via SendNotificationJob
## Routes
- GET /api/v1/notifications/unread-count
- GET /api/v1/notifications
- GET /api/v1/notifications/{id}
- PUT /api/v1/notifications/{id}/read
- PUT /api/v1/notifications/read-all
- DELETE /api/v1/notifications/{id}
- CRUD /api/v1/notification-templates
