# Helpdesk Module

Support ticket management with categories, SLA tracking, and an assign/resolve/close workflow.

## Features

- **Ticket Categories** — classify tickets by type (billing, technical, general, etc.)
- **Support Tickets** — raise, assign, resolve, and close customer support tickets
- **Priority Levels** — low / medium / high / critical
- **SLA Tracking** — optional `sla_due_at` timestamp per ticket
- Tenant-isolated; all queries scoped via `HasTenantScope`

## Status Lifecycle

`new` → `open` → `in_progress` → `resolved` → `closed`

## API Endpoints

| Method | URI | Description |
|--------|-----|-------------|
| GET/POST | `api/v1/helpdesk/categories` | List / create ticket categories |
| GET/PUT/DELETE | `api/v1/helpdesk/categories/{id}` | Read / update / soft-delete category |
| GET/POST | `api/v1/helpdesk/tickets` | List / create support tickets |
| GET/DELETE | `api/v1/helpdesk/tickets/{id}` | Read / soft-delete ticket |
| POST | `api/v1/helpdesk/tickets/{id}/assign` | Assign ticket to an agent |
| POST | `api/v1/helpdesk/tickets/{id}/resolve` | Mark ticket resolved |
| POST | `api/v1/helpdesk/tickets/{id}/close` | Close a ticket |

## Domain Events

| Event | Trigger |
|-------|---------|
| `TicketCreated` | New support ticket submitted |
| `TicketAssigned` | Ticket assigned to an agent |
| `TicketResolved` | Ticket marked as resolved |
| `TicketClosed` | Ticket closed |

## Dependencies

- Tenant, User, CRM, Notification, Audit
