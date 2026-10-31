# Customer outstanding inline invoices design plan

Reviewed the R8 report, the supplied screenshot, the Customer Outstanding frontend, and the invoice detail query. No application code was changed.

Plan: retain the historical as-at calculation, filters, customer and location totals, currency separation, and CSV export. Remove the standalone Code and Currency columns from the visible customer table while keeping currency labels on monetary values. Expand a customer directly beneath its row and show its paginated invoices there, with inline loading, errors, and mobile layout. Use a report-owned table rather than adding expansion behavior to the shared DataTable. Make the default invoice columns follow the R8 reading order: invoice, invoice date, age, due days, and outstanding balance. Move the service job number into small secondary text under the invoice number when present; sales invoices have no service job number. Invoice totals, payments, credits, and due date remain available in the invoice detail view.

The existing invoice endpoint already provides the required data. No backend calculation or schema change is planned.
