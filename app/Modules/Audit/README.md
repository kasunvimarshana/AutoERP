# Audit module

The Audit module owns immutable audit-event storage, validation, sanitization, authorization, scoped reads, and the read-only API/UI projection. It does not infer business events from arbitrary model changes.

## Writing an event

Feature modules depend only on `AuditRecorderInterface` and emit an `AuditEventData` after the owning business transaction succeeds. The active tenant, organization unit, user, impersonator, and request context are resolved by the Audit module and cannot be supplied by normal callers.
Trusted background jobs and integrations use `SystemAuditEventData`; they must provide a valid tenant scope, while the Audit module validates any organization-unit ownership before append. Authenticated events snapshot the guard, provider, application, impersonator, and request context from trusted accessors.

Use `recordSystem()` only for trusted jobs or integrations without an authenticated user. System scope identifiers are validated before append.

`producerKey` is an optional producer deduplication key. The Audit module derives a tenant-and-module-scoped SHA-256 fingerprint and rejects duplicate producer events, while identical keys in different tenants remain independent.

## Data rules

- Audit rows are append-only; application update/delete APIs do not exist.
- Historical rows intentionally have no foreign keys so actor/scope snapshots survive source-record deletion.
- Payload keys configured as sensitive are redacted recursively before persistence.
- Payload depth, item count, string length, tag count, and encoded size are bounded.
- `occurred_at` records the business event time; `recorded_at` records append time.
- Read access is tenant-scoped and organization-scoped unless the actor has `audit.logs.view_tenant`.
- Changes, metadata, producer keys, and request context require `audit.logs.view_sensitive`.

## Event categories

Use the canonical values from `AuditEventCategory`; feature modules must not invent category strings.

- `authentication`: sign-in, sign-out, credential, and session events
- `authorization`: role, permission, and access-decision events
- `administration`: platform or tenant control-plane administration
- `configuration`: application and reference configuration changes
- `data`: material business-data changes that do not fit a narrower category
- `financial`: accounting, billing, settlement, and payment events
- `inventory`: stock and inventory-control events
- `security`: security posture, domain enforcement, and lifecycle enforcement events
- `workflow`: business process transitions
- `system`: infrastructure and system-operation events

## Current event producers

- Purchase: `purchase.fast_purchase.completed`

Additional modules must emit events from their own application services at confirmed business transition points. Do not add generic model observers or Audit-owned business inference.
