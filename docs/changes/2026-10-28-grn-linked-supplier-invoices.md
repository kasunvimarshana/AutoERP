# GRN linked supplier invoices

The Goods Receipt detail page previously showed only a create action under Linked documents. A fully invoiced GRN therefore displayed a dash even when supplier invoices were linked.

The Purchase related-document service now retrieves all supplier invoices linked to a GRN through `purchase_invoice_links`. Single-GRN API responses expose invoice number, status, and the Purchase invoice detail URL. The Linked documents tab shows each invoice as a link and keeps the create action only while the GRN can accept more invoiced quantity. List responses do not perform the extra relationship query.

No migration was needed. The existing invoice and GRN records were not changed.

Verification: the Purchase engine test covers multiple invoices linked to one GRN; Purchase navigation tests cover the fully invoiced view. TypeScript typecheck, targeted PHP formatting, and diff checks passed.
