# Service invoice 8.5 x 5.5 inch reference layout

**Date:** 2026-11-17  
**Module:** Invoice  
**Scope:** Service invoices using the existing `continuous_8_5_by_5_5` print option.

The previously generic continuous-paper service invoice now uses the approved attached invoice design. It presents a company and invoice header, date and number strip, supplier and purchaser details with service-job references in the purchaser panel, itemized lines with saved item codes, payment and persisted totals, policy text, customer/cashier signatures, and the print trace. A4 service invoices retain their existing presentation; A5 and non-service invoices retain their existing template paths.

The Invoice module owns the layout selection and Blade presentation. Service continuous print margins are named in `InvoicePrintLayout`, and the service partial is shared by the two focused service layouts with a layout-specific reference placement. No invoice calculation, historical data, schema, relationship, or configuration choice changed. The existing organization-unit document setting still selects the continuous-paper option.

Verification: `InvoicePrintServiceTest.php` passed (11 tests, 117 assertions), followed by the focused test after its browser-print size assertion was added (1 test, 13 assertions). Pint and `git diff --check` passed. The representative four-line preview rendered visually on one PDF page measuring 612 x 396 points (8.5 x 5.5 inches) at `output/pdf/service-invoice-8.5x5.5-preview.pdf`. The fixture uses test-only party data; values on a real invoice come from persisted invoice and document snapshots.
