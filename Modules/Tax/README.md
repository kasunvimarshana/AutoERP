# Tax Module

## Overview

The Tax module provides tenant-scoped tax rate management. It supports both percentage-based and fixed-amount tax rates, optional regional scoping, date-ranged applicability, and an active/inactive lifecycle — enabling the platform to apply the correct tax rules across Sales, Purchase, Accounting, and E-Commerce modules without hardcoded values.

---

## Features

- **Tax rate CRUD** – Create, read, update, delete rates per tenant
- **Type support** – `percentage` (e.g., 20% VAT) and `fixed` (e.g., $5 flat fee)
- **BCMath validation** – `rate` stored as `DECIMAL(18,8)`, validated via `bcadd` + `bccomp`
- **Region support** – Optional `region` field for country/state-level tax scoping
- **Date ranges** – `start_date` / `end_date` for compliance (tax rate changes over time)
- **Active/inactive lifecycle** – Only active rates surfaced to consumers
- **Dedicated active endpoint** – `GET api/v1/tax/rates/active` returns only active rates
- **Domain events** – `TaxRateCreated`, `TaxRateDeactivated`
- **Tenant isolation** – Global tenant scope via `HasTenantScope` trait

---

## API Endpoints

| Method | URI | Description |
|--------|-----|-------------|
| GET | `api/v1/tax/rates` | List all tenant tax rates |
| GET | `api/v1/tax/rates/active` | List active rates only |
| POST | `api/v1/tax/rates` | Create a new tax rate |
| GET | `api/v1/tax/rates/{id}` | Get a specific tax rate |
| PUT | `api/v1/tax/rates/{id}` | Update a tax rate |
| DELETE | `api/v1/tax/rates/{id}` | Soft-delete a tax rate |
| POST | `api/v1/tax/rates/{id}/deactivate` | Deactivate a tax rate |

---

## Domain Events

| Event | Payload | Trigger |
|-------|---------|---------|
| `TaxRateCreated` | `taxRateId`, `tenantId`, `name`, `rate` | New tax rate created |
| `TaxRateDeactivated` | `taxRateId`, `tenantId` | Tax rate deactivated |

---

## Architecture

- **Domain**: `TaxType` enum, `TaxRateRepositoryInterface`, domain events
- **Application**: `CreateTaxRateUseCase` (BCMath rate normalisation + negative guard), `DeactivateTaxRateUseCase` (already-inactive guard)
- **Infrastructure**: `TaxRateModel` (SoftDeletes + HasTenantScope), migration, `TaxRateRepository`
- **Presentation**: `TaxRateController`, `StoreTaxRateRequest`

---

## Integration Notes

- The `GET api/v1/tax/rates/active` endpoint is the primary consumer endpoint for Sales, Purchase, and E-Commerce modules when building order/invoice line tax selectors.
- `TaxRateCreated` can trigger Audit module logging.
- `TaxRateDeactivated` can trigger Notification dispatch to accounting admins.
- A future `ApplyTaxService` (in the Accounting module) can look up rates by region to compute line-level tax amounts.
