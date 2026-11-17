# Refine the continuous service invoice layout

**Date:** 2026-11-18
**Modules:** Invoice, Vehicle Service

The 8.5 x 5.5 inch service invoice now uses 11px main text. Its company header omits the duplicate address and phone line; the supplier details remain in the party box. In that box, a comma-separated supplier locality starts on the next line when present.

Job and vehicle references remain on the first row. Current Mileage and Next Service Mileage display together on the next row. New service invoice snapshots use the Current Mileage label; the print template presents the same label for older snapshots that stored Mileage, without changing historical data.

The notice below the settlement table has more top space. Other section gaps and party-detail spacing were tightened enough to keep a four-line invoice with tax details and a five-line invoice with a print trace on one page. Ten-line invoices continue onto a second page with complete rows and an unclipped footer. A4 and other invoice layouts retain their existing styling.

Verification: InvoicePrintServiceTest passed (12 tests, 126 assertions); the immutable Vehicle Service invoice reference test passed (1 test, 9 assertions). Pint and PHP syntax checks passed. PDF previews of a five-line and ten-line local invoice were rendered and visually inspected. No invoice amounts, database records, migrations, or relationships were changed.
