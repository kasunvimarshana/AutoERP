# Credit check click feedback

## Request

Show a clear validation message when the user clicks “Keep invoice on credit” and the customer is over the credit limit.

## Changes

- Display the fresh server-side credit-check result beside the confirmation button as an accessible alert.
- Keep blocked results visible and direct the user to Direct Payment. For an allowed warning, ask for a second confirmation and recheck the server before proceeding.
- Clear click feedback when the user changes payment mode or invoice.

## Verification

- TypeScript typecheck passed.
- `git diff --check` passed.
- Tests were not run.
