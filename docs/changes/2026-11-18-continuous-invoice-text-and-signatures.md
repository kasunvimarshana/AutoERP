# Improve readability of the continuous invoice

**Date:** 2026-11-18
**Module:** Invoice

The 8.5 x 5.5 inch outbound invoice now uses 12px main text. Vehicle No references use 12.5px and remain on one line. The quantity heading and values are centered. The supplier name in the party box is displayed in uppercase without changing its stored snapshot. The continuous content minimum height is 108mm, moving the Customer and Cashier signature fields about 4mm higher on short invoices. A small reduction in line and totals row padding preserves room for the larger text while keeping the existing table and section layout. A4 and other paper layouts are unchanged.

Verification: InvoicePrintServiceTest passed (14 tests, 153 assertions). Pint, PHP syntax, and diff checks passed. A short and a four-item continuous invoice were rendered on one page; the four-item case includes supplier and customer tax identifiers, mileage, and a print trace. A denser five-item case with the same identifiers may require a second page at the requested font size.
