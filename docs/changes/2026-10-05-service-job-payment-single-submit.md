# Finalize service job payments in one submit

## Request

Remove the extra Review payment click to reduce the number of actions needed to collect a direct service job payment.

## Changes

- Submit the validated payment form directly to the create endpoint from Finalize payment.
- Remove the preflight-only prepare endpoint from the Vehicle Service API route and controller, along with its unused frontend client and response type.
- Keep payment method, reference, card brand, invoice balance, expected-version, and posting checks in the transactional create flow.
- Keep the amount and remaining-balance summary visible before finalization.

## Verification

- TypeScript typecheck, PHP syntax checks, and `git diff --check` passed.
- ESLint reported one existing invoice auto-selection hook warning and no errors.
- Automated tests were not run.
