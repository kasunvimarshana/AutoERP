# ADR-003: Stateless JWT Authentication (tymon/jwt-auth)

**Date:** 2026-02-19
**Status:** Accepted

## Context

The platform needs authentication that:
- Is fully stateless (no server-side sessions) to support horizontal scaling.
- Embeds claims (tenant_id, organization_id, device) so each request is self-contained.
- Supports multiple guards for different user classes.
- Integrates cleanly with Laravel Policies and Spatie Permissions.

Laravel Sanctum (token-based, database-backed) was considered but requires a sessions/tokens table and server-side state.

## Decision

Use **`tymon/jwt-auth`** with the `api` guard configured in `config/auth.php`. JWTs carry:
- `sub` — user UUID
- `tenant_id` — tenant UUID
- `org_id` — active organization UUID (optional)

Token lifecycle:
- **Login** (`POST /api/v1/auth/login`) — issues a fresh token; brute-force limited to 10 req/min per IP.
- **Refresh** (`POST /api/v1/auth/refresh`) — rotates the token (old token invalidated via blacklist).
- **Logout** (`POST /api/v1/auth/logout`) — blacklists the current token.

The `JWT_SECRET` is loaded from `.env`; it must be at least 32 characters.

## Consequences

- **Pro**: Stateless; scales horizontally without sticky sessions.
- **Pro**: Claims are self-describing; downstream services can validate without a DB round-trip.
- **Con**: Token revocation requires a blacklist (Redis recommended in production); the in-memory/cache driver is used in development.
- **Con**: Token size is larger than opaque Sanctum tokens.
