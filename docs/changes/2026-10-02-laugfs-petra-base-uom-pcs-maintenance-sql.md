# LAUGFS PETRA 4L Base UOM maintenance SQL

## Context

The supplied MySQL dump showed item 683 (`LAUGFS SUPREME PETRA 5W 30 4L`) had no Base UOM despite completed service usage. PCS UOM 1 and an active PCS service item-unit mapping already existed. The stock balance was 0.500000 with no UOM, and the current service price was recorded as LTR.

## Change

- Added a guarded MySQL/MariaDB maintenance script to set PCS as the item Base UOM, create the matching Base item-unit row, and tag the current stock balance as PCS without changing its quantity.
- The script creates a new PCS service-price revision using the existing current LTR service-price amount, and closes the prior revision through its recorded-time fields so the earlier price remains in history.
- The script leaves completed job lines, invoices, inventory movements, and valuation layers untouched. It locks and validates the target records, checks the item/UOM scope, rejects conflicting current Base UOMs or overlapping PCS service prices, and applies changes in one transaction.

## Verification and limitations

- Inspected the attached dump's item, UOM, item-unit, price, stock-balance, inventory-movement, and vehicle-service-line records against the current module schema and services.
- The dump's four completed service-job lines for this item have null UOM IDs; the script intentionally does not rewrite those historical records.
- The script was not run against a live database. Back up the database and execute the SQL in the intended MySQL/MariaDB database.
