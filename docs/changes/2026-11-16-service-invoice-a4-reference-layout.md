# Match the approved A4 service invoice design

Reworked only the standard A4 Service Invoice print presentation to follow the supplied A4 reference: compact company and invoice header, supplier and purchaser details, a separate immutable service-job reference strip, grouped labour and spare-parts lines, payment details beside persisted totals, signatures, and print trace. The Invoice module owns the new view partial and groups invoice lines from their saved line types. Other invoice types, A5, and continuous layouts retain their existing template path.

The layout uses persisted invoice totals, saved item codes, party snapshots, and the service-job references captured when the invoice was issued. It does not invent a next-service date or hardcode the reference document's refund and VAT policy statements. No database schema or historical invoice values changed.

The representative four-line Service Invoice rendered to one A4 PDF page with its print trace on that page; its preview is at `output/pdf/service-invoice-a4-preview.pdf`. Focused A4, A5, purchase, and immutable vehicle-service snapshot tests passed (5 backend tests, 51 assertions), as did the invoice print frontend checks, Pint, PHP syntax checks, and `git diff --check`.
