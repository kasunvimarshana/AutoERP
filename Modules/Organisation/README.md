# Organisation Module

## Overview

Optional nested hierarchical organisational structure following Clean Architecture and **Controller → Service → Handler (with Pipeline) → Repository → Entity** pattern.

Supports the multi-tenancy hierarchy defined in AGENT.md:

```
Tenant
 └── Organisation
      └── Branch
           └── Location
                └── Department
```

All levels are optional and self-referentially modelled via a single `organisations` table with a `parent_id` column and a `type` discriminator.

## Architecture

| Layer | Location | Responsibility |
|---|---|---|
| Domain | `Domain/` | Entities, contracts, enums |
| Application | `Application/` | Commands, handlers, pipeline |
| Infrastructure | `Infrastructure/` | Eloquent model, repository, migration |
| Interfaces | `Interfaces/` | HTTP controllers, requests, resources, routes |

## Key Design Decisions

- **Self-referential hierarchy**: A single `organisations` table with a nullable `parent_id` allows any depth of nesting without schema changes.
- **Type discriminator**: The `type` column (`organisation` / `branch` / `location` / `department`) signals the semantic level of a node. This is configurable and extensible without migration.
- **Fully optional**: `parent_id` is nullable — root organisations have no parent. The hierarchy is never enforced by schema constraints beyond the tenant boundary.
- **Code normalised to uppercase**: `code` is stored and queried in uppercase for case-insensitive uniqueness.
- **Unique per tenant**: `(tenant_id, code)` composite unique constraint prevents duplicate codes across the same tenant.
- **Tenant isolation**: `OrganisationModel` uses `BelongsToTenant` trait; all repository queries also filter explicitly by `tenant_id`.
- **Pipeline pattern**: `CreateOrganisationHandler` uses Laravel Pipeline chaining `ValidateCommandPipe → AuditLogPipe` before persistence.
- **Soft deletes**: Deleted nodes are archived rather than hard-deleted for audit safety.

## API Endpoints

| Method | URL | Description |
|---|---|---|
| GET | `/api/v1/organisations` | List all nodes for a tenant |
| POST | `/api/v1/organisations` | Create a new node |
| GET | `/api/v1/organisations/{id}` | Get a node by ID |
| PUT | `/api/v1/organisations/{id}` | Update a node |
| DELETE | `/api/v1/organisations/{id}` | Soft-delete a node |
| GET | `/api/v1/organisations/{id}/children` | List direct children of a node |

## Node Types

| Type | Description |
|---|---|
| `organisation` | Top-level organisational entity under a tenant |
| `branch` | Branch under an organisation |
| `location` | Physical or logical location under a branch |
| `department` | Department under a location |

## Status Values

| Status | Description |
|---|---|
| `active` | Node is operational |
| `inactive` | Node is temporarily disabled |
| `archived` | Node is permanently retired |
