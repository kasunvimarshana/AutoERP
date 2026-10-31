# Invoice payment action and settlement badges

## Why

Supplier invoice detail showed Create Payment only when reached through the Purchase route. The Finance invoice list opens the same detail component through `/invoices/{id}`, so the action disappeared there. The shared status badge also colored `Posted` green even when the invoice had an unpaid balance, and `Paid` could be reached through credits without a full payment.

## Changes

- Supplier Create Payment now depends on invoice type, posted or partially paid status, positive balance, and `purchase.payments.execute` permission. It no longer depends on the current URL. The purchase back link remains contextual.
- Invoice list, detail summary, and balance tab use an invoice-specific badge. Posted/unpaid is amber, partial payment and credit settlement are blue, and green is reserved for a zero balance settled by recorded payments without credit allocations. Credit-settled invoices are labelled `Settled with credit`.
- The shared badge remains unchanged for other modules. No schema or transaction data changed.

## Verification

- Invoice detail tests cover both routes, full settlement, and missing payment permission. Badge tests cover posted, partial, cash-settled, credit-settled, and shared posted styling.
- Focused Vitest run: 2 files, 6 tests passed. TypeScript typecheck, targeted ESLint, and diff check passed.
