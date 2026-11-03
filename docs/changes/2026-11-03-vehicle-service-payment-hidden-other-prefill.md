# Correct hidden payment-method prefill in Vehicle Service

## Request

The service invoice had an outstanding balance of LKR 1,900 and staff entered LKR 2,000 cash, with the other visible method amounts at zero. The page showed zero cash applied and LKR 2,000 change instead of applying LKR 1,900 and returning LKR 100.

## Cause and change

The invoice auto-prefill effect called `paymentMethodKind` with an undefined method when several methods were available. That helper maps an unknown method to `other`, so the effect silently filled the hidden Other row with the invoice balance. The cash allocation then treated the invoice as already covered by that hidden amount.

The effect now uses the existing `soleNonCashMethodKind` rule, which returns a method kind only when exactly one non-cash direct method is available. This retains the intentional single-method prefill while leaving multiple-method cash entry clear. No payment data, API, or database relationships changed.

## Verification

- Added a regression case with LKR 1,900 outstanding, LKR 2,000 cash received, and zero Card/Bank amounts. It verifies LKR 1,900 cash applied, LKR 100 change, LKR 1,900 payment total, and only LKR 1,900 sent to the payment API.
- Focused payment-page suite: 12 tests passed.
- TypeScript typecheck, focused ESLint, and `git diff --check` passed.
