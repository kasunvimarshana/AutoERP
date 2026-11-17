# Use the service invoice layout for selling invoices

**Date:** 2026-11-18
**Modules:** Invoice, Selling

Sales and Service invoices now share the same outbound print template on A4 and 8.5 x 5.5 inch continuous paper. The Invoice print service selects that template for both outbound invoice types. The template and its outer layout selectors have neutral outbound names; the established Service layout, amounts, and paper sizing are unchanged. Sales invoices retain their own number, customer, item, discount, and payment data. Service-only job and mileage references appear only when present in an invoice snapshot. Continuous invoices continue to omit item codes.

Verification: InvoicePrintServiceTest passed (13 tests, 142 assertions), including one-page PDF checks for both Sales paper sizes. The Selling discount and partial-return workflow test passed (1 test, 15 assertions). Pint and PHP syntax checks passed. Local Sales invoice previews for both paper sizes were rendered and visually inspected. No migrations or existing invoice records were changed.
