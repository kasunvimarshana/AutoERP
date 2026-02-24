# Communication Module

## Overview

The Communication module provides internal team messaging via typed channels (direct, group, public channel). Messages are tenant-scoped and immutable once sent.

---

## Features

- **Channels** – Create and manage direct, group, and topic channels per tenant
- **Messages** – Send text/file/image messages within channels; paginated retrieval (newest first)
- **Tenant isolation** – Global tenant scope via `HasTenantScope` trait
- **Domain events** – `ChannelCreated`, `MessageSent`

---

## API Endpoints

| Method | URI | Description |
|--------|-----|-------------|
| GET | `api/v1/communication/channels` | List tenant channels |
| POST | `api/v1/communication/channels` | Create channel |
| GET | `api/v1/communication/channels/{id}` | Get channel |
| DELETE | `api/v1/communication/channels/{id}` | Delete channel |
| GET | `api/v1/communication/channels/{channelId}/messages` | List messages in channel |
| POST | `api/v1/communication/channels/{channelId}/messages` | Send message to channel |

---

## Domain Events

| Event | Payload | Trigger |
|-------|---------|---------|
| `ChannelCreated` | `channelId`, `tenantId`, `name`, `type` | New channel created |
| `MessageSent` | `messageId`, `tenantId`, `channelId`, `senderId` | Message sent |

---

## Architecture

- **Domain**: `ChannelType` enum, `ChannelRepositoryInterface`, `MessageRepositoryInterface`, domain events
- **Application**: `CreateChannelUseCase`, `SendMessageUseCase`
- **Infrastructure**: `ChannelModel` (SoftDeletes + HasTenantScope), `MessageModel` (HasTenantScope), migrations, repositories
- **Presentation**: `CommunicationController`, `StoreChannelRequest`, `SendMessageRequest`
