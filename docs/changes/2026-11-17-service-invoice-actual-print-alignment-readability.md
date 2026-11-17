# Align and clarify the continuous service invoice on the physical print

**Date:** 2026-11-17  
**Module:** Invoice  
**Scope:** Existing 8.5 x 5.5 inch Service Invoice print layout only.

The physical continuous-feed sample placed the invoice content to the right of the paper's printable center and rendered the blue, small text faintly. The service layout now shifts the print area 5 mm left through named page-margin constants, uses dark text with larger body, headings, contact, and footer fonts, and omits the item code beneath each description. The A4 service layout still uses its existing item-code presentation.

The larger type initially moved the footer to a second page for a four-line invoice. Reduced only the continuous layout's unused vertical space in the party and totals boxes and adjusted its content minimum height so the four-line invoice, signatures, and print trace remain on one 612 x 396 point page. A ten-line diagnostic kept all item rows on the first 5.5-inch page and placed totals and footer on a second page without clipping. Exact physical centering still depends on the printer driver and feed alignment, so the next physical print should be checked.

Verification: `InvoicePrintServiceTest.php` passed (12 tests, 122 assertions); Pint and `git diff --check` passed. The final four-line PDF preview was visually inspected at `output/pdf/service-invoice-actual-print-preview.pdf`. No invoice data, calculations, database schema, or relationships changed.
