# Vehicle Service promotions and Service Cards plan

Date: 2026-11-02

## Request

Plan reusable buy-N-get-M service offers and vehicle-bound physical Service Cards for AutoERP as a sellable product feature.

## Changes

- Added `docs/architecture/vehicle-service-promotions-and-service-cards-plan-2026-11-02.md` with confirmed business rules, module ownership, data integrity and concurrency requirements, UI placement, phased delivery, and acceptance criteria.
- Confirmed that the existing Voucher module presents financial vouchers, while Item, Vehicle Service, Invoice, Customer, and Vehicle have distinct responsibilities in the proposed flow.
- Recorded unresolved commercial policy decisions for approval before implementation. No application code, schema, data, or prior change records were changed.

## Verification

- Read recent change records and inspected Voucher routes/types, Vehicle Service job-line and discount/invoice flows, invoice restoration, and Customer/Vehicle identity references.
- Documentation review only; no application tests apply.
