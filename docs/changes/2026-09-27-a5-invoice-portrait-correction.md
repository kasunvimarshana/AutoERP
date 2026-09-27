# A5 invoice portrait correction

## Reason

The printed invoice was landscape on the user's portrait A5 paper. The previous print-sizing change assumed landscape paper. The selected `compact_a5` layout therefore generated A5 landscape in both browser print and PDF output, and its screen preview used landscape dimensions.

## Changes

- Keep the existing `compact_a5` configuration value and make its orientation portrait for all invoice types.
- Set the compact screen preview to 148mm wide and 210mm high; retain the print-specific rule that fits content inside the A5 page margins.
- Correct the organization-unit setting label and focused invoice regression expectations to portrait.

## Verification

- Invoice print suite passed: 9 tests, 92 assertions, including one-page A5 portrait PDF output and A4 output.
- Physical printer output and driver settings remain to be checked on the user's printer. The print dialog should select A5 portrait at actual size.
