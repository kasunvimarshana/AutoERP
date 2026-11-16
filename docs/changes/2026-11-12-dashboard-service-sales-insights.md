# Add service sales insights to business dashboard

The business dashboard now shows three service sales charts for its selected date range: jobs and revenue by invoice week, profit by job type, and top stock items sold with current available quantity and reorder warnings. The charts link to the existing Service Job Sales Summary and Low Stock reports.

The Reporting service uses the Service Job Sales Summary's invoice allocation, item ranking, cost, and commission calculations. It adds weekly invoice buckets and job type profit to that report's result, then combines the ranked stock items with current organization-scoped inventory balances and item reorder levels for the dashboard. Weeks without invoiced jobs remain visible as zeroes. The new dashboard section follows the existing Reporting view permission and organization scope.

Added backend assertions for weekly revenue, job type profit, and reorder risk, plus UI coverage for populated and empty charts. No database schema or historical records changed.
