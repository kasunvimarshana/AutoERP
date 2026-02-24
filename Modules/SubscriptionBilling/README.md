# Subscription Billing Module

Recurring subscription plan management, subscription lifecycle, and automated renewal processing.

## Features

- **Plans** — define subscription plans with billing cycle (`monthly`, `quarterly`, `annually`), BCMath price, trial days, and feature flags
- **Subscriptions** — subscribe tenants or customers to a plan; supports trial periods, period start/end tracking
- **Lifecycle** — `trial → active → paused → cancelled → expired`; `renew` and `cancel` use cases with guard conditions
- **Automated Renewals** — `ProcessSubscriptionRenewalsCommand` (artisan `subscriptions:process-renewals`) chunks due subscriptions and dispatches `RenewSubscriptionJob` per subscription to prevent execution timeout on large datasets
- Tenant-isolated; all queries scoped via `HasTenantScope`
- All `price` / `amount` values stored as `DECIMAL(18,8)` with BCMath arithmetic

## Status Lifecycles

### Subscription
`trial` → `active` → `paused` → `cancelled` / `expired`

## API Endpoints

| Method | URI | Description |
|--------|-----|-------------|
| GET/POST | `api/v1/subscriptions/plans` | List / create plans |
| GET/DELETE | `api/v1/subscriptions/plans/{id}` | Read / delete plan |
| GET/POST | `api/v1/subscriptions` | List / create subscriptions |
| GET | `api/v1/subscriptions/{id}` | Read subscription |
| POST | `api/v1/subscriptions/{id}/renew` | Renew a subscription |
| POST | `api/v1/subscriptions/{id}/cancel` | Cancel a subscription |

## Artisan Commands

| Command | Description |
|---------|-------------|
| `subscriptions:process-renewals` | Chunk-process subscriptions due for renewal (async, timeout-safe) |

## Domain Events

| Event | Trigger |
|-------|---------|
| `SubscriptionCreated` | New subscription created |
| `SubscriptionRenewed` | Subscription successfully renewed |
| `SubscriptionCancelled` | Subscription cancelled |

## Dependencies

- Tenant, User, Notification
