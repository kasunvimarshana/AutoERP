# ADR-006: HTTP Idempotency Keys for Mutating API Endpoints

**Status**: Accepted  
**Date**: 2026-02-20  
**Deciders**: Platform Engineering Team

---

## Context

In a distributed, multi-tenant ERP/CRM SaaS environment, network partitions, client retries,
and duplicate submissions are unavoidable realities. Without explicit idempotency support,
retried POST/PUT/PATCH requests can create duplicate orders, payments, invoices, or inventory
movements — causing financial discrepancies and data corruption that are extremely hard to
reverse in an audit-sensitive system.

The problem is particularly acute for:
- **POS transactions** — a cashier's terminal may retry after a network timeout
- **Payment recording** — a payment must never be recorded twice for the same transaction
- **Order creation** — duplicate orders corrupt inventory reservations and financial totals

HTTP GET is naturally idempotent. The challenge is making mutating methods (POST, PUT, PATCH)
safe to retry.

---

## Decision

Implement an **`Idempotency-Key` HTTP header** mechanism as a Laravel middleware
(`IdempotencyMiddleware`) applied globally to all `api` group routes.

### Protocol (client-side)

1. For every mutating request, the client generates a cryptographically unique key
   (UUID v4 is recommended) and sends it as:
   ```
   Idempotency-Key: 550e8400-e29b-41d4-a716-446655440000
   ```
2. On a successful response the server echoes the key back:
   ```
   Idempotency-Key: 550e8400-e29b-41d4-a716-446655440000
   ```
3. If the client needs to retry, it resends the **exact same key**. The server will
   replay the original response without re-executing any business logic.
4. A replayed response is identified by the additional header:
   ```
   X-Idempotent-Replayed: true
   ```

### Server-side behaviour

| Scenario | HTTP Status | Description |
|----------|-------------|-------------|
| No `Idempotency-Key` header | (unchanged) | Request processed normally |
| Key too long (>255 chars) | 422 | Rejected before any processing |
| First occurrence of key | (handler status) | Processed; result stored |
| Duplicate of a completed key | (original status) | Response replayed |
| Duplicate of an in-flight key | 409 | Concurrent duplicate rejected |
| Duplicate of an expired key | (handler status) | Treated as a fresh request |

### Storage

Idempotency records are stored in the `idempotency_keys` table with:

| Column | Type | Notes |
|--------|------|-------|
| `id` | ULID | Primary key |
| `tenant_id` | string | Multi-tenant isolation |
| `user_id` | bigint | Owner of the key |
| `idempotency_key` | varchar(255) | Client-supplied value |
| `request_method` | varchar(10) | e.g. POST |
| `request_path` | varchar(500) | e.g. api/v1/orders |
| `response_status` | smallint | HTTP status code |
| `response_body` | mediumtext | Serialised JSON response |
| `processed_at` | timestamp | Set when handler completes |
| `expires_at` | timestamp | Default: 24 hours; configurable via `IDEMPOTENCY_TTL_HOURS` |

The unique index `(user_id, idempotency_key)` prevents concurrent duplicate processing
using `lockForUpdate()` inside a DB transaction.

Response bodies exceeding 1 MiB are not cached (the status code is stored, but the body
field is left null); subsequent replays for such responses return an empty 200. This is an
acceptable trade-off — these cases should be rare in an API context.

---

## Consequences

### Positive

- **Duplicate-free mutations**: Clients can safely retry any POST/PUT/PATCH without
  risk of double-processing.
- **Transparent to existing handlers**: The middleware wraps the Symfony Response,
  requiring zero changes to controllers or services.
- **Auditable**: Every idempotency record is queryable for debugging and compliance.
- **Configurable TTL**: Operators can tune expiry via `.env` without code changes.

### Negative / Trade-offs

- **Additional DB write per mutating request**: One extra row per API call.
  Mitigation: the table is append-only and rows expire; a scheduled job can prune
  old records (`expires_at < NOW()`).
- **Race condition edge case**: Two identical requests arriving within the same DB
  transaction window may both see `wasRecentlyCreated = false` briefly, returning 409.
  This is the correct behaviour and expected by the protocol.
- **1 MiB body cap**: Very large response bodies are not replayed. This should be
  reviewed if streaming endpoints are added in the future.

---

## Alternatives Considered

| Alternative | Reason Rejected |
|-------------|-----------------|
| Client-side deduplication only | Unreliable; does not protect against concurrent requests from different devices |
| Database-level unique constraints per resource | Domain-specific; does not generalise; requires per-table changes |
| Redis-based idempotency store | Adds an infrastructure dependency; SQLite/MySQL/PostgreSQL are already in use; Redis is optional |
| No idempotency support | Violates the "idempotent APIs" requirement from the platform specification |

---

## References

- [Stripe Idempotency Keys](https://stripe.com/docs/api/idempotent_requests)
- [IETF draft-ietf-httpapi-idempotency-key-header](https://datatracker.ietf.org/doc/draft-ietf-httpapi-idempotency-key-header/)
- `app/Http/Middleware/IdempotencyMiddleware.php`
- `app/Models/IdempotencyKey.php`
- `database/migrations/2026_02_20_000001_create_idempotency_keys_table.php`
