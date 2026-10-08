# Make vehicle service payment entry keyboard friendly

## Request

Reduce clicks and make payment amounts quick to enter and navigate by keyboard.

## Changes

- Kept Cash, Card, and Bank transfer amount fields visible together instead of requiring method-selection clicks.
- Selected the current amount text on focus so users can replace the displayed zero directly.
- Made Enter move to the next visible form field without submitting the payment; Tab and Shift+Tab keep native navigation.
- Showed the missing transaction-details prompt only after an amount is entered for a method that requires details.
- Tightened spacing in payment method sections while preserving cash applied amount, change, reference, and validation behavior.

## Verification

- `npm run typecheck` passed.
- `git diff --check` passed.
- Automated tests were not run.
