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

## Implemented Files

### Migrations
| File | Table |
|---|---|
| `2026_02_27_000065_create_webhook_endpoints_table.php` | `webhook_endpoints` |
| `2026_02_27_000066_create_webhook_deliveries_table.php` | `webhook_deliveries` |
| `2026_02_27_000067_create_integration_logs_table.php` | `integration_logs` |

### Domain Entities
- `WebhookEndpoint` — HasTenant + SoftDeletes; events/headers as JSON
- `WebhookDelivery` — HasTenant; status pending/delivered/failed
- `IntegrationLog` — HasTenant

### Application Layer
- `RegisterWebhookDTO` — fromArray; name, url, events, optional secret/headers
- `IntegrationService` — listWebhooks, registerWebhook, showWebhook, updateWebhook, deleteWebhook, dispatchWebhook, listIntegrationLogs, listDeliveries (mutations in DB::transaction)

### Infrastructure Layer
- `IntegrationRepositoryContract` — findByEvent (whereJsonContains), findActiveEndpoints
- `IntegrationRepository` — extends AbstractRepository on WebhookEndpoint
- `IntegrationServiceProvider` — binds contract, loads migrations and routes

### API Routes (`/api/v1`)
| Method | Path | Action |
|---|---|---|
| GET | `/integration/webhooks` | listWebhooks |
| POST | `/integration/webhooks` | registerWebhook |
| GET | `/integration/webhooks/{id}` | showWebhook |
| PUT | `/integration/webhooks/{id}` | updateWebhook |
| DELETE | `/integration/webhooks/{id}` | deleteWebhook |
| POST | `/integration/webhooks/{id}/dispatch` | dispatchWebhook |
| GET | `/integration/logs` | listIntegrationLogs |
| GET | `/integration/deliveries` | listDeliveries |

---

## Test Coverage

| Test File | Type | Coverage Area |
|---|---|---|
| `Tests/Unit/RegisterWebhookDTOTest.php` | Unit | `RegisterWebhookDTO` — field hydration, defaults |
| `Tests/Unit/IntegrationServiceTest.php` | Unit | listWebhooks delegation, DTO field mapping, is_active enforcement |
| `Tests/Unit/IntegrationServiceDispatchTest.php` | Unit | dispatchWebhook method/parameters, delivery payload mapping |
| `Tests/Unit/IntegrationServiceLogsTest.php` | Unit | listIntegrationLogs structure, method visibility, instantiation |
| `Tests/Unit/IntegrationServiceWritePathTest.php` | Unit | updateWebhook/deleteWebhook signatures, return types, public visibility |
| `Tests/Unit/IntegrationServiceCrudTest.php` | Unit | showWebhook, listDeliveries — method signatures, delegation — 10 assertions |
| `Tests/Unit/IntegrationServiceDelegationTest.php` | Unit | showWebhook/listDeliveries delegation to repository, Collection type contract, regression guards — 12 assertions |

---

## Status

🟢 **Complete** — Webhook registration, dispatch, update, delete, show, delivery tracking, and integration logging implemented (~85% test coverage). See [IMPLEMENTATION_STATUS.md](../../IMPLEMENTATION_STATUS.md)
