# ADR-005: Event-Driven Architecture with Webhooks

**Date:** 2026-02-19
**Status:** Accepted

## Context

Integrators and downstream systems need to react to business events (order created, invoice sent, payment recorded, stock adjusted) without polling. The system should also maintain an immutable audit trail for compliance.

## Decision

Adopt **native Laravel events** as the backbone of the event-driven architecture:

1. **Domain Events** (`app/Events/`) — plain PHP objects implementing `DomainEventInterface` — are dispatched via `Event::dispatch()` at the end of each service transaction.

2. **AuditEventSubscriber** — listens to all domain events and writes immutable rows to `audit_logs` (action, subject type/id, old/new values, user, IP, timestamp). The subscriber is registered in `AppServiceProvider::boot()`.

3. **WebhookEventSubscriber** — listens to the same events and, for each active `Webhook` whose `events` array includes the event name, dispatches a `DeliverWebhookJob` onto the queue. The job performs the HTTP POST with retry/back-off.

4. **Queued Jobs** — `DeliverWebhookJob` is a standard Laravel job; queue workers process it asynchronously, preventing webhook latency from blocking API responses.

## Consequences

- **Pro**: Decoupled — adding a new listener (e.g., send email on `OrderCreated`) requires no changes to `OrderService`.
- **Pro**: The audit trail is produced automatically without service code changes.
- **Pro**: Webhook delivery is reliable (queue + retry) and non-blocking.
- **Con**: Debugging requires inspecting queue worker logs in addition to application logs.
- **Con**: Failed webhook deliveries accumulate in the `webhook_deliveries` table and must be monitored.
