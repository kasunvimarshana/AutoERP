# Customer outstanding inline invoice details

The Customer Outstanding report now expands the selected customer's invoices directly beneath that customer row on desktop and inside its card on mobile. The separate invoice panel below the location table was removed so users can read each customer's balance and invoices together. Only one customer row is open at a time.

The customer table no longer has separate Code or Currency columns. Each amount still carries its currency label, and existing location and currency totals remain separate. The invoice list now shows invoice number, invoice date, age, due days, and balance. A service job number, when present, appears beneath its invoice number; the invoice link still opens the invoice detail. Invoice pagination, loading, and error states stay with the expanded customer.

This change is limited to the reporting UI. Historical as-at calculations, API responses, filters, and CSV export were not changed. A focused UI test covers inline placement, concise columns, the service job label, the invoice link, and changing the open customer.
