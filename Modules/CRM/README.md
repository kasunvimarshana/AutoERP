# CRM Module

Manages leads, opportunities, contacts and activities for the sales pipeline.

## Responsibilities
- Contact management (shared with Sales module as customer records)
- Lead lifecycle management (New to Won/Lost)
- Opportunity pipeline with weighted value calculation (BCMath)
- Activity tracking (calls, emails, meetings)
- Lead-to-Opportunity conversion

## API Endpoints

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | /api/v1/leads | List leads |
| POST | /api/v1/leads | Create lead |
| GET | /api/v1/leads/{id} | Get lead |
| PUT | /api/v1/leads/{id} | Update lead |
| DELETE | /api/v1/leads/{id} | Delete lead |
| POST | /api/v1/crm/leads/{id}/convert | Convert lead to opportunity |
