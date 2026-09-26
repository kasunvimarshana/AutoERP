# Deziutge POS history-only display recommendation

Date: 2026-09-25

## Request

Reviewed `C:\Users\Sadheera\Downloads\deziutge_pos (16).sql` to determine how its vehicle service history should be made visible in the current system without recreating old operational or financial transactions.

## Verified source shape

- The source SHA-256 is `D06065D44432F08EAC3CE287AE4F936374CF7473AB3AD0657A27F3894DA35DA2`.
- Historical vehicle activity is stored in `sales`, with vehicle, customer, item, and payment context in `vehicles`, `customers`, `product_sales`, `products`, and `payments`.
- The source `jobs`, `job_items`, and `job_item_assignments` tables contain no history rows.
- The current importer expects `vehicle_service_jobs` and `vehicle_service_job_lines`, so it cannot correctly import this dump unchanged.
- The existing immutable Vehicle Service legacy-history tables and the unified Reporting history view are the correct target boundary.

## Recommended approach

- Add a dedicated Deziutge POS source adapter within the Vehicle Service import layer.
- Parse only an explicit allowlist of required source tables; never execute the dump.
- Convert accepted `sales` rows into immutable legacy history headers and `product_sales` rows into immutable history items, using `products` only for readable item snapshots.
- Match vehicles deterministically using normalized registration numbers and block ambiguous or conflicting matches.
- Keep old invoice and payment values as optional display snapshots only; do not create current invoices, payments, stock movements, finance entries, or live service jobs.
- Preserve source identifiers, source hashes, payload hashes, dry-run output, locking, atomic apply, and idempotency.
- Continue displaying imported rows through the existing Vehicle Service History report with an Old System badge and no operational edit/detail action.

## Open business decision

The source contains `service`, `wash`, and untyped sales. Import scope must be explicitly approved before implementation: service-only is the safest default; including wash history is reasonable only if the business considers washes part of the vehicle service log. Untyped sales should not be imported automatically.

## Changes made

No application code, schema, or database data was changed. This record documents the verified recommendation only.
