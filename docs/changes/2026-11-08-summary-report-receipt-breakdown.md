# Summary report receipt breakdown

Date: 2026-11-08

## Request

Make payment-date receipts visible by payment method near the current settlement of selected sales, and explain the parts of cash received without mixing invoice-date and payment-date totals.

## Changes

- Moved the existing payment-method breakdown for payments received directly below the invoice-date settlement panel. The separate payments-sent panel remains later in the report. The duplicate one-line receipt total was removed from the settlement panel.
- Added an expandable cash receipt breakdown: selected-period invoices, earlier invoices, other invoices, and cash not allocated to an invoice. It uses active payment allocations, the recorded invoice date snapshot, and the payment-method snapshot. Split-method receipts are apportioned by their persisted payment-line amounts. The remainder reconciles to the cash receipt total.
- Added concise labels explaining that current settlement follows invoice date while receipts follow payment date. No transaction records or schema were changed.

## Verification

- A focused Reporting test covers a split cash/card receipt, earlier and selected-period invoices, an unapplied balance, exchange rate, and a reversed allocation.
- The focused report-page test and TypeScript typecheck passed. PHP syntax and diff whitespace checks passed.
