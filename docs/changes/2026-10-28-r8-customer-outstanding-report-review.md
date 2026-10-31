# R8 customer outstanding report review

Reviewed the supplied R8 Invoice Wise Debtors Balance PDF against the existing Customer Outstanding report without changing application code. Both report customer invoices with an unpaid balance at a selected historical date. AutoERP exposes it at Reports > Customer Outstanding (`/reports/customer-outstanding`), grouped by location and customer with invoice drilldown and CSV export. Its backend reconstructs balances as at the selected date from dated settlements and credits. The UI summarizes customers first instead of listing every invoice on the initial screen, and excludes zero or negative balances.

This was a read-only comparison; no tests were run.
