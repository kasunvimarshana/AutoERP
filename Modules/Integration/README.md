# Integration Module

## Overview

The Integration module provides outbound webhook management and API key management. Webhooks deliver event notifications to external systems; API keys enable M2M (machine-to-machine) integrations with scoped access and revocation.

---

## Features

- **Webhooks** – Create, update, and soft-delete outbound webhook endpoints; per-webhook event filter list; HMAC signing secret
- **API keys** – Create scoped API keys (hashed at rest, prefix for display); revoke lifecycle; `expires_at` support
- **Security** – Key stored as SHA-256 hash; plaintext returned only at creation time
- **Domain events** – `WebhookCreated`, `ApiKeyCreated`
- **Tenant isolation** – Global tenant scope via `HasTenantScope` trait

---

## API Endpoints

| Method | URI | Description |
|--------|-----|-------------|
| GET | `api/v1/integration/webhooks` | List tenant webhooks |
| POST | `api/v1/integration/webhooks` | Create webhook |
| GET | `api/v1/integration/webhooks/{id}` | Get webhook |
| PUT | `api/v1/integration/webhooks/{id}` | Update webhook |
| DELETE | `api/v1/integration/webhooks/{id}` | Delete webhook |
| GET | `api/v1/integration/api-keys` | List tenant API keys |
| POST | `api/v1/integration/api-keys` | Create API key |
| GET | `api/v1/integration/api-keys/{id}` | Get API key |
| POST | `api/v1/integration/api-keys/{id}/revoke` | Revoke API key |

---

## Domain Events

| Event | Payload | Trigger |
|-------|---------|---------|
| `WebhookCreated` | `webhookId`, `tenantId`, `name`, `url` | New webhook created |
| `ApiKeyCreated` | `apiKeyId`, `tenantId`, `name` | New API key created |

---

## Architecture

- **Domain**: `WebhookStatus` enum, `WebhookRepositoryInterface`, `ApiKeyRepositoryInterface`, domain events
- **Application**: `CreateWebhookUseCase`, `CreateApiKeyUseCase` (generates secure key, hashes at rest)
- **Infrastructure**: `WebhookModel`, `ApiKeyModel` (SoftDeletes + HasTenantScope), migrations, repositories
- **Presentation**: `WebhookController`, `ApiKeyController`, `StoreWebhookRequest`, `StoreApiKeyRequest`
