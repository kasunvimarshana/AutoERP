# Show sales price and available batches in Selling item lookup

## Change

- Enriched Selling item lookup options with the effective item sales price for the selected sale date, using the same Item pricing resolver used when posting a sale.
- Added active, unexpired batch/lot numbers and their available quantities for the selected warehouse to each relevant item option.
- Kept batch selection in the existing optional inventory dimensions control and left sale posting as the authoritative price and stock validation path.
- Added API and Inventory coverage for effective sales price and warehouse-scoped batch availability.

## Reason

Selling's picker showed stock quantity but did not show the sale price or identify available batches/lots, so staff lacked useful context before selecting an item.

## Notes

No schema changes or migrations were needed. Existing change records were not modified.
