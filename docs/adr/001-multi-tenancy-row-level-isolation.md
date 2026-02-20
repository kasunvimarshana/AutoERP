# ADR-001: Multi-Tenancy via Row-Level Isolation

**Date:** 2026-02-19
**Status:** Accepted

## Context

The platform must serve multiple independent tenants (businesses) from a single database while keeping data completely isolated.

Three common approaches exist:
1. **Separate database per tenant** — strongest isolation; high operational cost; complex schema migrations.
2. **Separate schema per tenant** — moderate isolation; not supported natively by SQLite/MySQL in the same way.
3. **Shared database with `tenant_id` column (row-level isolation)** — lowest cost; isolation enforced at application layer.

## Decision

Use **row-level isolation**: every entity table carries a `tenant_id` (UUID, FK → `tenants`) column. All queries in services and repositories are scoped to the authenticated user's `tenant_id`.

The `TenantMiddleware` validates that the JWT token's `tenant_id` claim matches an active tenant and sets `request->tenant` for downstream use.

## Consequences

- **Pro**: Simple schema; easy cross-tenant analytics (super-admin); cheap migration.
- **Pro**: Works with any SQL engine (SQLite for tests, MySQL/PostgreSQL for production).
- **Con**: Application code must remember to scope every query — mitigated by code reviews and base repository scoping.
- **Con**: No physical isolation between tenants — acceptable for the target market (SMB SaaS).
