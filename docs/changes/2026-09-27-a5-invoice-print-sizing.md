# A5 invoice print sizing

## Cause

The organization-unit setting labels `compact_a5` as A5 landscape, but InvoicePrintService replaced that layout with A5 portrait for focused Service and Purchase invoices. Both the HTML `@page` rule and PDF renderer consumed the replacement orientation. When printed on landscape A5 paper, fitting a portrait document can shrink the invoice. The compact sheet also retained its 210mm screen width during browser printing because its selector was more specific than the print override, exceeding the 198mm printable width after the 6mm page margins.

## Changes

- Use the configured A5 landscape layout for all invoice types; remove the unused portrait-only layout and CSS.
- Give the compact sheet the same `width: auto` and zero-padding print override as the standard sheet so it fills the available print area.
- Update the existing focused invoice regression expectations to A5 landscape.

## Verification

- The focused Service invoice rendered on one A5 landscape PDF page; the Purchase layout and payment-method regression also passed (2 tests, 30 assertions).
- PHP syntax checks and `git diff --check` passed. No references to the removed portrait layout remain in application code.
- The physical printer dialog and driver settings were not available for inspection; they should use A5 landscape and actual-size scaling when the print is repeated.
