# Service cards and repeat offers implementation

Implemented the approved Vehicle Service promotion flow as a separate Promotion module. Voucher remains responsible for financial vouchers, and the existing manual line and whole-job discount entry flows remain in Vehicle Service.

## What changed

- Added explicit, tenant-scoped tables for repeat offers, card designs and benefits, issued cards, offer assignments, draft job-line choices, immutable qualification and redemption events, and card lifecycle events. Added one Vehicle Service line column for the separate automatic promotion amount.
- Added offer and card APIs for setup, assignment, issue, pause/reactivation, controlled vehicle transfer, eligibility and progress, and service-line selection. Promotion writes use transactions and version checks on mutable jobs and cards. Card dates, branch, customer/vehicle ownership, use limits, and offer scope are enforced on the server.
- Count progress advances only from posted, positively charged service invoice lines. Fully free lines do not count; partly discounted paid lines do. Every completed threshold creates another reward. Invoice reversal appends compensating events so progress and remaining uses are restored without altering historical events.
- Added the Service Cards & Offers workspace and a compact job strip. After selecting a customer and vehicle, staff can see card benefits and buy-N progress, add the eligible service item to the job draft at its ordinary service price, and apply the best eligible automatic saving when saving. The manual discount fields remain separate.
- Enabled posting and reversing zero-value invoices in the Invoice module without creating a zero-value finance journal. This is required for a fully free service line and keeps the document, tax, and promotion lifecycles in one transaction.

## Relationship review

The immutable issued vehicle and current vehicle on an issued card are both necessary to audit a manager-controlled transfer. An assignment stores the customer, optional vehicle, and optional card to distinguish standalone customer-wide offers from card-bound vehicle-only counts. Job-line choices reference the selected promotion; posted invoice lines anchor immutable count and redemption events. All new foreign keys include tenant scope. No Voucher relationship or circular promotion relationship was added.

## Verification

- SQLite migration and route boot: passed through focused Laravel tests and `route:list`.
- Promotion integration: paid discounted qualification, vehicle isolation, repeating 5-to-1 rewards, best card benefit, limited uses, expiry, free invoice posting, and reversal: passed.
- Existing whole-job/line discount and partial-invoice regression tests: passed.
- Frontend production build and TypeScript typecheck: passed.

The application database was not migrated during this work; deployment must run the new migrations before enabling the UI.
