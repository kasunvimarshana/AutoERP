# Service invoice single-page layout and copy-state regression

## Summary

- Removed the `Total Amount in words` row from the invoice print layout.
- Reduced only the A5 compact layout's vertical spacing and minimum blank line count so paid Service Invoices, including settlement and adjustment rows, remain on one A5 landscape page.
- Preserved all company, customer, vehicle, line, total, payment-mode, signature, and print-audit information.

## Original and duplicate verification

- Added controller-level regression coverage for the exact signed-print endpoint used by both **Vehicle Service > Print bill** and the Invoice detail page's **Print** button.
- Verified that the first post-payment signed print URL contains `copy_type=original` and the next independently issued URL contains `copy_type=duplicate`.
- The copy state remains stored per invoice in `invoices.original_printed_at`; it is not shared across different Service Invoices.

## Verification

- The supplied `SERVICE-2026-000006` invoice was regenerated from its paid database record and visually checked as one A5 landscape page.
- The final render contains no amount-in-words row, clipping, overlap, or second-page signature/footer.
- The strengthened paid A5 regression passed with five assertions.
- Focused Invoice tests passed before strengthening the paid-layout fixture; the exact strengthened A5 test passed afterward.
- Invoice template Vitest coverage passed: 4 tests.
- PHP Pint and frontend ESLint passed.

## Preview

- `output/pdf/service-invoice-a5-preview.pdf`
