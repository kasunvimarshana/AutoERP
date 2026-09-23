# Supplier settlement profit and cash impact review

Date: 2026-09-09

## Request

Reviewed how normal and pay-on-sale supplier arrangements should affect revenue, final profit, supplier payments, and management reports.

## Accounting conclusion

- Supplier payment is not a second expense. It settles an existing supplier liability by reducing Payables and Cash/Bank.
- Profit is recognized from income-statement activity: revenue less cost of goods sold and other expenses.
- For company-owned normal purchases, a posted GRN records Inventory and GRNI for all accepted units. Only the cost of units actually sold/issued should move from Inventory to Cost of Goods Sold. Supplier invoicing transfers GRNI to Payables, and supplier payment transfers Payables to Cash/Bank; neither transfer changes profit.
- For supplier-owned pay-on-sale stock, physical receipt should not create company-owned Inventory or an unconditional Payable. When a unit is sold, its supplier cost must be recognized in the same reporting period as the related revenue. The clean posting is Cost of Goods Sold against an accrued supplier-settlement liability, followed by a later supplier invoice that clears the accrual into Payables and a payment that clears Payables against Cash/Bank.
- Consequently, paying early or late affects cash position and outstanding payables, not the gross profit already earned on the sale.

## Example

For 10 received units, 5 units sold at 1,500 each, and a supplier cost of 1,000 each:

- revenue: 7,500;
- cost of goods sold: 5,000;
- gross profit: 2,500;
- supplier settlement due for sold units: 5,000; and
- paying the 5,000 changes Payables and Cash but leaves the 2,500 gross profit unchanged.

The remaining five units are company Inventory under normal procurement, but supplier-owned unsold custody stock under a true consignment arrangement.

## Current-system observation

- The ledger-backed Profit and Loss service totals income-statement revenue and expenses and calculates net profit as revenue minus expenses.
- Supplier-payment posting uses Payable and Cash/Bank roles, so a correctly configured supplier payment does not enter Profit and Loss.
- Vehicle Service inventory issues already post Cost of Goods Sold against Inventory.
- Generic manual sales invoices currently post revenue without an authoritative inventory issue, so a general item-sales flow would overstate profit unless a Sales-owned stock issue and COGS posting are added.

## Reporting recommendation

Keep separate but reconcilable views:

- Profit and Loss: revenue, COGS, other expenses, and net profit;
- Supplier Sell-through: received, sold, settlement-eligible, settled, paid, returned, and remaining quantity/value;
- Accounts Payable: supplier invoice and outstanding liability;
- Cash Flow/Payment Register: actual supplier cash outflows.

## Changes

- No application behavior, schema, transactional data, permissions, or reports were changed.
- Added only this review record to the append-only change history.

