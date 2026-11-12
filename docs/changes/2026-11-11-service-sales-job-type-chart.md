# Show service sales revenue by job type

Added a revenue-by-job-type pie chart immediately below the Service Job Sales Summary filters. It shows each job type's share of invoiced revenue, the LKR amount, and the number of invoiced jobs, with an empty state when there are no sales.

The Reporting service groups the same filtered job revenue used by the report totals, so the chart follows the existing invoice-date, organization-unit, and item filters without introducing a separate calculation. Added backend and UI assertions for the breakdown and empty state. The existing tables and exports remain unchanged.
