# Vehicle service history detail UI plan

Date: 2026-10-28

The user requested a design for expanding Vehicle Service History records with item prices, discounts, totals, and clickable invoice numbers. This was a review and plan only; no application code was changed.

The current report UI renders only description and quantity in its expanded line lists. The report service already loads current job lines and invoice links but omits line monetary fields and invoice identifiers from its response. Current job lines store unit price, discount amount, tax amount, charge amount, and line total. Job-level discount is separate, and invoice creation may issue multiple invoices for a job with selected quantities. The history expansion should group work and parts in compact line tables, show quantity, unit price, line discount, and line total, and make any tax or charge detail available without overloading the primary table. It should show a separate job-level discount and the existing invoice totals, paid, and balance information. Each linked invoice should appear by human-readable invoice number with a link to its invoice detail; multiple invoices must all appear. Never infer an invoice link from a job number or expose raw IDs.

Imported old-system history items have description and quantity in typed columns but no typed monetary fields or current-system invoice relationship. Their source payload is retained. Before exposing legacy monetary values, verify the source fields and map trustworthy values as read-only snapshots in the Vehicle Service import owner; otherwise display "Not recorded" rather than zero or a fabricated current-system invoice link.

Implementation should update the reporting response and frontend types together, then the expansion UI. Verify current jobs with line and job discounts, tax/charge, multiple or no invoices, partial invoicing, and old-system rows. No schema relationship change is justified by this review.
