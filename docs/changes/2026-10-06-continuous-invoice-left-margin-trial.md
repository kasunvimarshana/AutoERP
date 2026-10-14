# Reduce continuous invoice left margin slightly

## Request

Reduce the left-side whitespace on the 8.5 × 5.5 inch continuous invoice printout for a trial print.

## Changes

- Reduced only the continuous layout's CSS left page margin from 13 mm to 12 mm.
- Kept the right-side and end margins, A4/A5 layouts, and paper dimensions unchanged.

## Verification

- PHP syntax check passed for `InvoicePrintLayout`.
- `git diff --check` passed.
- Physical printing was not available in this environment; confirm the test print does not clip the left border or text.
