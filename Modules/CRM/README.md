# CRM Module

Customer Relationship Management module for managing leads, opportunities, contacts, accounts, and activities.

## Features
- Lead management with pipeline tracking and conversion
- Opportunity management with stage-based pipeline
- Contact and account management
- Activity tracking (calls, emails, meetings, tasks, notes, demos)
- Domain events for integration with other modules

## API Endpoints
- `GET|POST /api/v1/crm/leads`
- `GET|PUT|DELETE /api/v1/crm/leads/{id}`
- `POST /api/v1/crm/leads/{id}/convert`
- `GET|POST /api/v1/crm/opportunities`
- `GET|PUT|DELETE /api/v1/crm/opportunities/{id}`
- `PATCH /api/v1/crm/opportunities/{id}/stage`
- `GET|POST /api/v1/crm/contacts`
- `GET|PUT|DELETE /api/v1/crm/contacts/{id}`
- `GET|POST /api/v1/crm/accounts`
- `GET|PUT|DELETE /api/v1/crm/accounts/{id}`
- `GET|POST /api/v1/crm/activities`
- `GET|PUT|DELETE /api/v1/crm/activities/{id}`
- `POST /api/v1/crm/activities/{id}/complete`

## Architecture
Follows Clean Architecture with Domain, Application, Infrastructure, and Presentation layers.
