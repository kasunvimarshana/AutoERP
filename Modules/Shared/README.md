# Shared Module

Cross-cutting utilities, base contracts, value objects, and infrastructure traits shared across all modules.

## Purpose

The Shared module provides the foundational building blocks that every other module depends on. It does **not** expose any HTTP routes — it is a pure utility module.

## Components

### Domain Layer

| Component | Description |
|-----------|-------------|
| `TenantId` | Value Object wrapping a non-empty UUID string for tenant identification |
| `UserId` | Value Object wrapping a non-empty UUID string for user identification |
| `DomainEvent` | Base class for all domain events; auto-generates a unique `eventId` (UUID v4) and captures `occurredAt` as a `DateTimeImmutable` |
| `RepositoryInterface` | Marker contract that all repository interfaces must implement |
| `UseCaseInterface` | Marker contract that all use-case classes must implement |

### Application Layer

| Component | Description |
|-----------|-------------|
| `ResponseFormatter` | Static JSON response envelope helper: `success(data, message, status, meta)` and `error(message, errors, status)` — enforces the consistent `{ status, message, data, meta, errors }` response structure across the platform |

### Infrastructure Layer

| Component | Description |
|-----------|-------------|
| `HasTenantScope` | Eloquent trait that adds a global query scope filtering by `tenant_id`; used by every module model to enforce tenant isolation |
| `HasAuditLog` | Eloquent trait that fires `created`/`updated`/`deleted` model events recorded in the Audit module |
| `TenantScope` | Standalone global scope class applied by `HasTenantScope` |
| `BaseEloquentRepository` | Abstract Eloquent repository with common helpers (find, paginate, create, update, delete) |

## Dependencies

- (none — this is the foundation layer)
