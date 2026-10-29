# Vehicle service history pricing and invoice links

Date: 2026-10-29

The Vehicle Service History report now includes current-system job line unit price, line discount, tax, charges, line total, billable state, and line status in its response. It also returns the job-level discount, job total, and every linked invoice's number, status, and internal navigation key. These are read-only values from existing Vehicle Service and Invoice records; no financial transaction, schema, or relationship was changed.

Expanding a current-system job now presents work and parts in clear pricing tables, with tax and charges shown only where present. The separate job-level discount and job/invoiced/paid/balance totals explain partial billing. Linked invoice numbers navigate to the existing invoice detail screen, and multiple invoices appear separately. Jobs without invoices display "Not invoiced". Imported old-system records continue to show description and quantity only, as explicitly requested; they do not show item prices or current-system invoice links.

Verification: focused VehicleServiceHistoryReportPage UI test passed, covering line amounts, two invoice links, and absence of old-system price columns. TypeScript typecheck passed. PHP syntax and Pint checks passed for the report service. Targeted ESLint found zero errors and only the page's existing synchronous-effect warning. `git diff --check` passed for changed application files.
