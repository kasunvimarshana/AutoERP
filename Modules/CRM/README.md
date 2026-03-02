# CRM Module

Manages **Contacts**, **Leads**, and **Activities** for the enterprise ERP/CRM SaaS platform.

## Features

- Contact management (create, update, delete, list)
- Lead/opportunity tracking with status workflow
- Activity logging (calls, emails, meetings, notes, tasks)
- Full tenant isolation

## Architecture

Follows Clean Architecture with Domain → Application → Infrastructure → Interfaces layering.

## API Endpoints

### Contacts
- `GET    /api/v1/crm/contacts`
- `POST   /api/v1/crm/contacts`
- `GET    /api/v1/crm/contacts/{id}`
- `PUT    /api/v1/crm/contacts/{id}`
- `DELETE /api/v1/crm/contacts/{id}`
- `GET    /api/v1/crm/contacts/{id}/leads`
- `GET    /api/v1/crm/contacts/{id}/activities`

### Leads
- `GET    /api/v1/crm/leads`
- `POST   /api/v1/crm/leads`
- `GET    /api/v1/crm/leads/{id}`
- `PUT    /api/v1/crm/leads/{id}`
- `DELETE /api/v1/crm/leads/{id}`
- `GET    /api/v1/crm/leads/{id}/activities`

### Activities
- `GET    /api/v1/crm/activities`
- `POST   /api/v1/crm/activities`
- `GET    /api/v1/crm/activities/{id}`
- `DELETE /api/v1/crm/activities/{id}`
