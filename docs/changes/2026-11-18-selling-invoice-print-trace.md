# Show the print trace on selling invoices

**Date:** 2026-11-18
**Module:** Invoice

Selling invoices used the shared outbound bill layout, but print issuance returned no context for Sales invoices. Their footer therefore omitted the printed timestamp, printer name, and copy label. Print issuance now supports both Sales and Service invoices through the same transaction and existing `original_printed_at` state. The first issued Sales print is labeled Original and subsequent prints are labeled Duplicate on A4 and 8.5 x 5.5 inch paper. No invoice totals, historical records, or schema were changed.

Verification: InvoicePrintServiceTest passed (14 tests, 149 assertions), including a Sales signed-print flow that checks Original on A4 and Duplicate on continuous paper. Pint and PHP syntax checks passed.
