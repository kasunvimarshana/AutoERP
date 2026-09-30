# GRN without supplier invoice payable-report verification

## Scenario

Verify whether a posted goods receipt note that has no generated supplier invoice appears as an amount payable in an existing report.

## Findings

- Posting a goods receipt records inventory against the Goods Received Not Invoiced (GRNI) liability; it does not create a supplier invoice or invoice balance.
- The `GRNs` report displays each GRN's stored `grand_total`, but it does not expose invoice progress or calculate a remaining uninvoiced/payable amount.
- The `Accounts Payable Aging` report is sourced only from inbound invoice balances, so a GRN with no supplier invoice is absent from that report.
- The purchase GRN list and detail API expose invoice progress and remaining invoiceable quantity, but there is no dedicated GRN-level uninvoiced amount report.
- Finance ledger/account reports can expose the GRNI accounting entries or aggregate account balance, but this is a provisional receipt liability rather than a supplier invoice payable with a due date and settlement balance.

No application code or database changes were made.

## Verification

- `FastPurchaseTest::test_grn_only_receipt_creates_inventory_and_finance_without_invoice_or_payment` passed: 1 test, 13 assertions.
- `ReportingFrameworkTest::test_registry_contains_the_initial_enterprise_reports` passed: 1 test, 18 assertions.
