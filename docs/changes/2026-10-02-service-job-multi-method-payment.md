# Service job multi-method payment

## Request

Make service job payments easier to enter and allow one invoice payment to be split across multiple payment methods before finalization.

## Changes

- Rework the service job payment page to add and remove method rows, enter method-specific details, and review the payment total and remaining invoice balance before finalizing.
- Update the Vehicle Service payment request and integration to validate and create multiple Payment module lines in one payment document, then allocate their total to the selected invoice.
- Keep invoice balance and job version checks in the backend so partial payments remain supported, overpayments are rejected, and concurrent updates are detected.
- Update existing Vehicle Service payment data callers and page expectations for the line-based request shape.

## Verification

- PHP syntax checks passed for the changed DTO, request, integration service, and existing payment test fixtures.
- TypeScript typecheck passed (`npm run typecheck`).
- `git diff --check` passed.
- Automated tests were not run.
