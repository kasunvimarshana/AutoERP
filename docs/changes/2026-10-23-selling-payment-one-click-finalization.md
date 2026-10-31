# Finalize Selling payments in one action

## Request

Cash, card, and bank transfer payments taken from the Selling invoice payment page should be applied immediately without separate Submit, Approve, and Post clicks.

## Changes

- Added a Selling settlement endpoint in the Payment module, restricted to users who can create payments and sales.
- The endpoint accepts one customer receipt against one sales invoice and only cash, card, or bank transfer methods. It creates the receipt, records automatic Submit and Approve lifecycle events, posts it, and realizes the invoice allocation inside one database transaction. A failure rolls the operation back.
- Reused the existing payment creation idempotency key flow and separated keys by endpoint.
- The Selling payment page now uses this endpoint and labels the action Finalize payment. The standard Payment entry flow remains draft based.
- Limited the Selling bank transfer selector to actual bank transfer methods so direct debit remains in the standard method flow.

## Verification

- PHP syntax checks, TypeScript typecheck, ESLint, route registration, and `git diff --check` passed.
- Automated tests were not run.
