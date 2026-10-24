# Post Selling returns and refund their available credit

## Request

Make the Selling return work and allow a customer refund against credit left after applying the return to the sales invoice.

## Changes

- Fixed the missing `SaleReturnPostingService` controller import that caused the return endpoint to fail before executing.
- Sales returns now post a Finance reversal of the original invoice revenue, tax, withholding, and customer receivable in the same transaction as the stock receipt and invoice credit. The customer credit excludes withholding because the original invoice balance excludes it.
- Added a return-linked outbound refund payment. It uses configured payment methods and the Payment module's normal create, submit, approve, post, and reversal records. Each refund checks the current available return credit under a row lock, accepts an optimistic expected balance, and uses an idempotency key. Partial refunds are supported; reversed refunds restore the refundable amount.
- Added a Refund action and concise payout form on the sale detail screen. Return lists and details show the current refundable balance and refunded total. Refund payments cannot themselves be allocated or refunded again.

## Verification

- PHP syntax checks, TypeScript typecheck, targeted ESLint, Selling route registration, and `git diff --check` passed. ESLint reported one existing React effect warning on the Selling page.
- Automated tests were not run.
