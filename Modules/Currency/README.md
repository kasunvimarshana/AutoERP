# Currency Module

## Features

- **ISO 4217 currency registry** — 3-letter code, name, symbol, decimal places, active/inactive lifecycle
- **Exchange rate management** — per-currency-pair rates with effective date, manual or automatic source
- **BCMath amount conversion** — `ConvertAmountUseCase` performs `bcmul(amount, rate, 8)` with latest-rate lookup; same-currency short-circuit
- **Tenant isolation** — all data scoped via `tenant_id` with global scope trait
- **Domain events** — `CurrencyCreated`, `CurrencyDeactivated`, `ExchangeRateRecorded`

## Architecture

```
Currency/
├── Domain/
│   ├── Contracts/
│   │   ├── CurrencyRepositoryInterface.php
│   │   └── ExchangeRateRepositoryInterface.php
│   ├── Entities/
│   │   ├── Currency.php
│   │   └── ExchangeRate.php
│   ├── Enums/
│   │   └── RateSource.php            (manual / automatic)
│   └── Events/
│       ├── CurrencyCreated.php
│       ├── CurrencyDeactivated.php
│       └── ExchangeRateRecorded.php
├── Application/
│   └── UseCases/
│       ├── CreateCurrencyUseCase.php       (ISO code guard, duplicate guard, decimal_places 0-8)
│       ├── DeactivateCurrencyUseCase.php   (not-found + already-inactive guards)
│       ├── RecordExchangeRateUseCase.php   (rate > 0 guard, both currencies must exist)
│       └── ConvertAmountUseCase.php        (BCMath, latest-rate lookup, same-currency pass-through)
├── Infrastructure/
│   ├── Migrations/
│   │   ├── 2024_01_01_000210_create_currencies_table.php
│   │   └── 2024_01_01_000211_create_exchange_rates_table.php
│   ├── Models/
│   │   ├── CurrencyModel.php
│   │   └── ExchangeRateModel.php
│   └── Repositories/
│       ├── CurrencyRepository.php
│       └── ExchangeRateRepository.php
├── Presentation/
│   ├── Controllers/
│   │   ├── CurrencyController.php
│   │   └── ExchangeRateController.php
│   └── Requests/
│       ├── StoreCurrencyRequest.php
│       └── StoreExchangeRateRequest.php
├── Providers/
│   └── CurrencyServiceProvider.php
├── routes.php
├── config.php
├── module.json
└── README.md
```

## API Endpoints

| Method | URI | Description |
|--------|-----|-------------|
| GET | `/api/v1/currencies` | List currencies (paginated) |
| GET | `/api/v1/currencies/active` | List active currencies |
| POST | `/api/v1/currencies` | Create currency |
| GET | `/api/v1/currencies/{id}` | Get currency |
| PUT | `/api/v1/currencies/{id}` | Update currency |
| DELETE | `/api/v1/currencies/{id}` | Delete currency |
| POST | `/api/v1/currencies/{id}/deactivate` | Deactivate currency |
| GET | `/api/v1/exchange-rates` | List exchange rates (paginated) |
| POST | `/api/v1/exchange-rates` | Record exchange rate |
| GET | `/api/v1/exchange-rates/{id}` | Get exchange rate |
| DELETE | `/api/v1/exchange-rates/{id}` | Delete exchange rate |

## Database Schema

### `currencies`
| Column | Type | Notes |
|--------|------|-------|
| id | UUID PK | |
| tenant_id | UUID | FK, indexed, global scope |
| code | CHAR(3) | ISO 4217 (e.g. USD, EUR, GBP) |
| name | VARCHAR | e.g. "US Dollar" |
| symbol | VARCHAR(10) | e.g. "$", "€" |
| decimal_places | TINYINT | 0–8, default 2 |
| is_active | BOOLEAN | default true |

### `exchange_rates`
| Column | Type | Notes |
|--------|------|-------|
| id | UUID PK | |
| tenant_id | UUID | FK, indexed, global scope |
| from_currency_code | CHAR(3) | ISO 4217 |
| to_currency_code | CHAR(3) | ISO 4217 |
| rate | DECIMAL(18,8) | BCMath scale 8 |
| source | VARCHAR(20) | `manual` \| `automatic` |
| effective_date | DATE | Indexed for latest-rate lookup |

## Guards & Validations

- `CreateCurrencyUseCase`: ISO 4217 code must be exactly 3 alpha chars; decimal_places 0–8; duplicate code per tenant rejected
- `DeactivateCurrencyUseCase`: not-found guard; already-inactive guard
- `RecordExchangeRateUseCase`: `from ≠ to`; rate > 0; both currency codes must exist for tenant
- `ConvertAmountUseCase`: same-currency short-circuit; not-found rate raises `DomainException`
