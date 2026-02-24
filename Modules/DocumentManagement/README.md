# Document Management Module

Tenant-scoped document vault with category organisation, metadata storage, and draft/published/archived lifecycle management.

## Features

- **Document Categories** — organise documents into named categories per tenant
- **Documents** — store document metadata (title, description, file path, MIME type, size) linked to optional categories
- **Lifecycle** — `draft → published → archived`; archived documents cannot be re-published
- **Publish Guard** — already-published documents are rejected; archived documents cannot be published
- **Archive Guard** — already-archived documents cannot be archived again
- Tenant-isolated; all queries scoped via `HasTenantScope`
- Owner tracking via `owner_id` and `published_at` timestamp

## Status Lifecycles

### Document
`draft` → `published`
`draft` → `archived`
`published` → `archived`

## API Endpoints

| Method | URI | Description |
|--------|-----|-------------|
| GET/POST | `api/v1/documents/categories` | List / create document categories |
| GET/PUT/DELETE | `api/v1/documents/categories/{id}` | Read / update / soft-delete category |
| GET/POST | `api/v1/documents` | List / create documents |
| GET/PUT/DELETE | `api/v1/documents/{id}` | Read / update / soft-delete document |
| POST | `api/v1/documents/{id}/publish` | Publish a draft document |
| POST | `api/v1/documents/{id}/archive` | Archive a document |

## Domain Events

| Event | Trigger |
|-------|---------|
| `DocumentPublished` | Document status transitions to published |
| `DocumentArchived` | Document status transitions to archived |

## Dependencies

- Tenant, User, Media
