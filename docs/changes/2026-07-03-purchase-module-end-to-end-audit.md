# Purchase module end-to-end audit

Date: 2026-07-03

## Scope

Reviewed the Purchase module backend and frontend surfaces end to end, including routes, requests, services, resources, migrations, tests, and React API/page contracts. Also checked recent Purchase change records before auditing.

## Findings

- Fast Purchase records supplier payment line `instrument_direction` as `outbound`, while the Payment module accepts instrument statuses such as `issued` and `received`. Normal Purchase payment creation uses `issued`.
- Purchase-owned document mutations use transactions and row locks, but Purchase documents do not have an expected-version contract, so stale user actions cannot be rejected consistently.
- Purchase still exposes a generic inventory adjustment request endpoint and frontend API even though Inventory owns stock adjustments.
- Manual purchase debit notes allow arbitrary `source_type` and `source_id` values, which can create unverifiable source references.
- Supplier invoice creation through Purchase returns a raw Invoice model / broad frontend record contract instead of the Invoice module resource contract.
- Purchase lifecycle actions mostly update status and timestamps without an append-only Purchase audit event trail; Fast Purchase is the only Purchase flow currently emitting an Audit module event.
- Goods receipt list status filtering still uses free text, unlike the fixed selector recently added to purchase returns.
- Some Purchase resources still call services or load relationships during serialization, which hides query and business logic work inside resource classes.

## Verification

- `php artisan route:list --path=api/v1/purchase --except-vendor`
- `php artisan test app/Modules/Purchase/Tests --stop-on-failure`
- `npm run typecheck`
- `git diff --check`
