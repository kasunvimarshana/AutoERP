# Purchase module audit fixes

## Why
- The Purchase end-to-end audit found contract drift and incomplete ownership boundaries around Payment instruments, Purchase write concurrency, inventory adjustments, debit note sources, supplier invoice responses, audit events, and resource serialization.

## Changed
- Added `row_version` to mutable Purchase document headers and required `expected_version` for Purchase Order updates/deletes plus Purchase Order, GRN, Purchase Return, and Purchase Debit Note actions.
- Corrected Fast Purchase payment instruments to use the Payment-owned `issued` instrument status enum.
- Removed the duplicate Purchase `inventory-adjustment-requests` route/request/frontend helper; stock adjustments remain Inventory-owned.
- Prohibited arbitrary manual purchase debit note source fields and restricted return-generated debit notes to their owning purchase return.
- Returned supplier invoices through the Invoice resource and made Purchase invoice previews explicit snake-case API contracts.
- Moved Purchase document progress/capability/related-document presentation out of resources into a Purchase presentation service.
- Added Purchase-owned audit events for document lifecycle changes.
- Updated Purchase UI actions to send document row versions and replaced the GRN status free-text filter with a controlled select.

## Verified
- `php artisan test app/Modules/Purchase/Tests --stop-on-failure`
- `php artisan route:list --path=api/v1/purchase --except-vendor`
- `npm run typecheck`
- `npm run build`
- `git diff --check`
