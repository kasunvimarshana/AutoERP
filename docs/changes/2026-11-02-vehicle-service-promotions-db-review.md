# Vehicle Service Promotions database review

Date: 2026-11-02

## Request

Review current code and identify which existing database tables can be reused for Offers and free Service Cards and which schema changes are required.

## Work completed

- Added `docs/architecture/vehicle-service-promotions-db-review-2026-11-02.md` with a table-by-table reuse assessment and a proposed Promotions-owned schema.
- Recommended nine explicit new tables for definitions, cards, assignment, pending line choice, immutable qualification/redemption events, and card lifecycle history, plus one Vehicle Service job-line promotional amount column. No new Voucher or Invoice table is required.
- Aligned the earlier Promotions plan with immutable published definitions rather than a separate revision framework. Historical activity remains tied to unchanged terms.
- No application code, migration, data, or previous change record was modified.

## Verification

- Read recent change records and inspected Item/price, Customer, Vehicle, Vehicle Service job/line/discount/invoice-link, Invoice line/source/status, and Voucher migrations and services.
- Documentation-only review; application tests and live database verification were not performed.
