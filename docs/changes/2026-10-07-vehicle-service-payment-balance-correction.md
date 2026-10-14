# Correct service payment balance before and after submission

## Request

Ensure the Vehicle Service payment screen shows the actual remaining invoice balance when payment is split across methods.

## Changes

- Always calculate “Balance after payment” as the outstanding balance minus applied payments, clamped at zero; an overpayment amount is not a remaining balance.
- Only show the overpayment warning when a payable invoice is selected.
- Disable payment entry and ignore amounts until a payable invoice is selected, with a message when no payable invoice exists.
- Keep the successful-payment summary based on the pre-submit invoice balance and backend-confirmed allocation.

## Verification

- `npm run typecheck` passed.
- `git diff --check` passed.
- Automated tests were not run.
