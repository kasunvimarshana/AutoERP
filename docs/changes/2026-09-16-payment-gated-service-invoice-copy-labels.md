# Payment-gated service invoice copy labels

## Summary

- Changed Service Invoice print-copy labelling so pre-payment prints do not consume the Original copy state.
- Pre-payment prints retain the `Printed` timestamp and `By` user audit footer, but omit both `Original` and `Duplicate`.
- Once a payment amount has been applied to the invoice balance, the first subsequent print is labelled `Original` and later prints are labelled `Duplicate`.

## Implementation

- The Invoice module checks the locked authoritative `invoice_balances.paid_amount` value while issuing print context.
- The invoice row and its balance row are locked in the same order used by payment settlement, keeping payment and print operations conflict-safe.
- Signed print context supports an absent copy type for pre-payment prints without trusting unsigned query metadata.
- Copy state remains limited to Service Invoices; other invoice types retain their existing behaviour.

## Verification

- Focused Invoice print and legal-document tests passed: 10 tests, 104 assertions.
- PHP Pint passed for the modified Invoice PHP files.
