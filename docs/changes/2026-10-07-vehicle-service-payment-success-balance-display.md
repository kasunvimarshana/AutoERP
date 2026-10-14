# Fix service payment success balance summary

## Request

Correct the Vehicle Service payment summary after a payment is successfully posted.

## Changes

- Show the backend-confirmed allocated amount in the payment total after submission, so refreshed invoice data cannot reset the displayed total.
- Calculate the post-payment balance from the invoice balance captured before submission and the confirmed allocation.
- Hide the pre-submit overpayment warning after the payment is successfully created; retain the existing pre-submit validation behavior.

## Verification

- `npm run typecheck` passed.
- `git diff --check` passed.
- Automated tests were not run.
