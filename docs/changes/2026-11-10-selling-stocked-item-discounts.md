# Selling stocked-item search and discounts

**Date:** 2026-11-10

The Selling item lookup now uses the compact Vehicle Service result layout with available stock and sales price. The lookup filters out items with no available stock in the selected warehouse and location before pagination. The posting service continues to enforce stock availability atomically when a sale is created.

The Selling item table now has quantity step controls, line discount inputs, a total bill discount input, and a before-tax summary. Both discount inputs support fixed amounts and percentages. The Selling backend validates and calculates the discounts, stores immutable sale and sale-line snapshots, passes line discounts into tax calculation and invoice lines, and records the bill discount as a separate invoice adjustment. Invoice totals, balances, finance postings, and invoice print reflect the discounted amounts.

Bill discount is allocated across sale lines at posting. The Selling return service uses those stored allocations when calculating partial and full return credits, preventing a refund of the original undiscounted price. No relationships were changed. The new fields use separate, single-table migrations for sales and sale lines; existing sale history receives zero discount defaults.

Verification: Selling workflow PHPUnit suite passed (8 tests, 108 assertions), including zero-stock lookup, excessive-discount rollback, invoice and print totals, and partial returns. Selling page Vitest suite passed (3 tests). TypeScript typecheck, focused ESLint, Pint, and `git diff --check` passed.
