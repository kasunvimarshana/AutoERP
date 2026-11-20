# Compact Invoice Layout at 12px

**Date:** 2026-11-20  
**Module:** Invoice

Updated the existing 8.5 x 5.5 inch outbound Service and Sales invoice print layout to use 12px body and item-table text. Compact header, party, line, payment, policy, and signature spacing gives the item table more room. The continuous layout now shows paid amount and balance in the payment column, avoids repeating them in the totals column, and puts the print trace beside the signatures. Supplier addresses flow naturally instead of being split at the last comma. A4 and A5 presentation remains on its existing paths.

The invoice data, persisted totals, legal party fields, page size, and print margins were not changed. Item rows have no new application limit; longer details can still require another printed page. An 18-row compact reference-style fixture renders with its totals, signatures, and trace on one 612 x 396 point page at 12px. A four-row fixture with tax identifiers and mileage also remains on one page. The final 18-row PDF render was visually inspected for clipping, overlap, and duplicate trace text.

Verification: `InvoicePrintServiceTest` passed (14 tests, 151 assertions); Pint and `git diff --check` passed.
