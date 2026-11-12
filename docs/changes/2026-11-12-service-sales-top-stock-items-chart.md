# Show top selling stock items on service sales summary

Added a horizontal bar chart for the five stock items with the most invoiced units in the Service Job Sales Summary. Each bar shows the item name, quantity, and revenue after discounts. It uses the report's existing ranked stock item data, so the current invoice-date and item filters apply without another query or calculation.

The stock item chart sits beside the revenue-by-job-type chart on wider screens and stacks below it on smaller screens. The report page test now checks both populated and empty chart states. This is labeled "Top selling" because the report measures invoiced units, not inventory turnover.
