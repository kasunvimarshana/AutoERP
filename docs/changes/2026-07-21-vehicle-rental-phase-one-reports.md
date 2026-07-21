# Vehicle Rental Phase 1 reports

## Source of truth

These reports read current Vehicle Rental transactions and do not recreate commercial rules inside the Reporting module:

```text
Finalized / current Running Charts
    → operational reports

Rental calculations
    → invoice_sources
    → posted Customer Invoices or Owner Payable Vouchers
    → financial registers

Customer-use assignments
    → vehicle, owner source, driver and replacement history
```

The implementation does not revive the removed legacy rental-usage model or copy legacy report-query formulas.

## Reports

### Daily Running Chart Report

Shows physical daily evidence with:

- chart date, number and status
- customer and customer agreement
- vehicle owner and owner agreement
- vehicle and driver
- start/end time and odometers
- total, garage and commercial kilometres
- normal, double and triple overtime
- night-outs, trip details, replacement chart and remarks

### Missing / Duplicate Running Chart Exceptions

Requires an explicit date range and checks customer-use assignments through the current business day. The period is limited to 366 calendar days.

Exceptions:

- assignment/date without a current Running Chart
- more than one current chart for the same assignment/date
- the same physical vehicle charted under multiple customer assignments on the same date

### Customer Invoice Register

Defaults to posted, partially paid, paid and reversed rental invoices. An explicit status filter can be used for draft/approved/cancelled/void investigation.

Every row traces:

```text
Customer Invoice
→ Rental Calculation
→ Calculation Sources
→ Running Charts
→ Vehicle and Agreement
```

### Owner Payable Voucher Register

Uses inbound rental invoices generated from owner-side calculations but presents the business document as an Owner Payable Voucher / self-billed owner settlement.

Customer and owner financial sides remain independent.

### Vehicle Rental History

Shows customer-use assignments with:

- effective period and status
- customer and owner agreements
- physical vehicle and driver/self-drive mode
- handover/return odometers
- replacement lineage and reason
- finalized chart count and kilometre totals

## Shared reporting capabilities

All five reports use the existing Reporting module infrastructure for:

- tenant and organization-unit scoping
- permission checks
- search, filters, sorting and pagination
- summary totals
- HTML preview and print
- PDF, Excel and CSV export
- export row limits

Vehicle Rental report endpoints additionally require the `vehicle-rental` tenant feature.

## Architecture

Responsibilities remain split:

- `VehicleRentalReportService` — report dispatch only
- `VehicleRentalReportDefinitions` — titles, columns and formats
- `VehicleRentalOperationalReportService` — Running Chart and assignment history projections
- `VehicleRentalFinancialReportService` — invoice/voucher projections
- `VehicleRentalChartExceptionReportService` — bounded synthetic exception detection
- `VehicleRentalReportValueFormatter` — shared display-safe value formatting

No report table stores independent balances or totals. Results are reproducible from authoritative source transactions.
