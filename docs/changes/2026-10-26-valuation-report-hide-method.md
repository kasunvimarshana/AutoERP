# Hide Method from Inventory Valuation Report

## Request

Hide the Method column in the existing Inventory Valuation Report.

## Change

- Removed the valuation method column from the `inventory.valuation` report definition. The `inventory.valuation-layer` report retains its Method column.

## Verification

- Confirmed the valuation report has no `valuation_method` column and the valuation-layer report still has it.
- PHP Pint passed for the changed catalog file.
