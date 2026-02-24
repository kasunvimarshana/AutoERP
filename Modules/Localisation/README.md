# Localisation Module

## Overview

The Localisation module provides multi-language support, per-user locale preferences, and regional formatting. Language packs are stored in the database per tenant, supporting LTR and RTL text directions. Per-user preferences override tenant defaults.

---

## Features

- **Language packs** – DB-stored translation string maps per tenant and locale; LTR/RTL direction
- **Locale preferences** – Per-user locale, timezone, date format, and number format (upsert pattern)
- **Domain events** – `LanguagePackCreated`, `LocalePreferenceUpdated`
- **Tenant isolation** – Language packs scoped per tenant; unique constraint on (tenant_id, locale)

---

## API Endpoints

| Method | URI | Description |
|--------|-----|-------------|
| GET | `api/v1/localisation/language-packs` | List tenant language packs |
| POST | `api/v1/localisation/language-packs` | Create language pack |
| GET | `api/v1/localisation/language-packs/{id}` | Get language pack |
| PUT | `api/v1/localisation/language-packs/{id}` | Update language pack |
| DELETE | `api/v1/localisation/language-packs/{id}` | Delete language pack |
| GET | `api/v1/localisation/preferences` | Get current user's locale preference |
| PUT | `api/v1/localisation/preferences` | Update current user's locale preference |

---

## Domain Events

| Event | Payload | Trigger |
|-------|---------|---------|
| `LanguagePackCreated` | `languagePackId`, `tenantId`, `locale` | New language pack created |
| `LocalePreferenceUpdated` | `userId`, `tenantId`, `locale`, `timezone` | User locale preference updated |

---

## Architecture

- **Domain**: `TextDirection` enum, `LanguagePackRepositoryInterface`, `LocalePreferenceRepositoryInterface`, domain events
- **Application**: `CreateLanguagePackUseCase`, `UpdateLocalePreferenceUseCase` (upsert pattern)
- **Infrastructure**: `LanguagePackModel` (SoftDeletes + HasTenantScope), `LocalePreferenceModel`, migrations, repositories
- **Presentation**: `LanguagePackController`, `LocalePreferenceController`, `StoreLanguagePackRequest`, `UpdateLocalePreferenceRequest`
