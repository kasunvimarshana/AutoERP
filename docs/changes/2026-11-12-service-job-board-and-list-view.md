# Add service job board beside the existing list

The Service Job List now opens in a four-column board view modeled on the supplied design. Pending groups draft and inspected jobs; the other columns show in-progress, completed, and billing or closed jobs. Each tile opens the existing job detail and shows vehicle, job number, job type, customer, status, job date, and the current job total. The List switch retains the existing table and mobile list design.

The Vehicle Service module owns a board read endpoint under the existing jobs-view permission. It applies the same tenant, organization-unit, search, status, and job-date filters as the list, returns accurate per-column totals, and loads additional jobs independently per column. The page adds Today, This week, Last week, All dates, and custom date controls for the board; search and status filters apply to both views. Customer display names are included in the shared job search.

Added focused backend and UI tests for grouping, totals, filters, view switching, date presets, and per-column loading. No database schema or relationships changed.
