# Match Selling payment UI to the supplied reference

## Request

Update the existing Selling invoice payment page to match the supplied Vehicle Service payment layout without changing the customer receipt lifecycle.

## Changes

- Reworked the Selling view of `/payments/create` with invoice number, total, and outstanding cards; separate Cash, Card, and Bank Transfer entry sections; and a sticky payment summary.
- Cash tender now calculates the applied amount and change before receipt creation. The Payment module validates and saves the original tender and computed change on the cash line.
- The Payment module accepts and validates a card brand for card lines and exposes the saved brand and cash tender facts on payment details.
- Configured active methods remain the source of truth. Additional method types remain available through the standard invoice settlement form.
- Receipt creation still uses the existing Payment API, pending invoice allocation, idempotency, and draft/submit/approve/post lifecycle. The existing credit, invoice print, and WhatsApp actions remain available.

## Verification

- TypeScript typecheck and lint passed for changed payment files.
- PHP syntax checks and `git diff --check` passed.
- The local in-app browser redirected to sign-in, so the authenticated page could not be visually inspected there.
- Automated tests were not run.
