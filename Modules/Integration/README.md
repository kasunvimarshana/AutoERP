# Integration Module

## Overview

The **Integration** module manages all third-party integrations, webhook delivery, and external API connections. It provides the outbound event publishing pipeline and inbound connector framework.

---

## Responsibilities

- Webhook registration and delivery (signed, retryable)
- Outbound event publishing
- E-commerce platform sync (WooCommerce, Shopify-compatible)
- Payment gateway connectors
- Third-party ERP/CRM connector framework
- API rate limiting and circuit breakers
- Integration log and error tracking
- OpenAPI documentation endpoint

---

## Architecture Layer

```
Modules/Integration/
 ├── Application/       # Register webhook, publish event, sync e-commerce use cases
 ├── Domain/            # Webhook entity, IntegrationConnector contract, IntegrationLog entity
 ├── Infrastructure/    # IntegrationServiceProvider, webhook dispatcher, connector adapters
 ├── Interfaces/        # WebhookController, IntegrationController
 ├── module.json
 └── README.md
```

---

## Dependencies

- `core`
- `tenancy`

---

## Architecture Compliance

| Rule | Status |
|---|---|
| No business logic in controllers | ✅ Enforced |
| No query builder calls in controllers | ✅ Enforced |
| All webhooks signed and retryable | ✅ Required |
| Integration logs tenant-scoped | ✅ Enforced |
| All endpoints documented via OpenAPI | ✅ Required |
| No cross-module coupling (adapter pattern only) | ✅ Enforced |

---

## Status

🔴 **Planned** — See [IMPLEMENTATION_STATUS.md](../../IMPLEMENTATION_STATUS.md)
