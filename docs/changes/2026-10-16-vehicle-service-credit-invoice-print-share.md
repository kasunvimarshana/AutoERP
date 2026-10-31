# Print and share credit-held Vehicle Service invoices

## Request

Provide print and WhatsApp sharing actions after a Vehicle Service invoice is successfully kept on customer credit.

## Changes

- Kept the user on the payment preparation page after credit confirmation and displayed the confirmed invoice and its outstanding balance.
- Added the existing invoice print and WhatsApp share actions to the credit success state, reusing the signed print link, WhatsApp share flow, and unverified-number confirmation.
- Disabled payment-entry controls after credit confirmation to prevent duplicate submission while preserving the direct payment flow.

## Verification

- `npm run typecheck` passed.
- `git diff --check` passed.
- Automated tests were not run.
