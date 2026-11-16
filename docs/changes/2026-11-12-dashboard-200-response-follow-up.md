# Dashboard 200 response follow-up

The user reported that the dashboard briefly displays before the recovery page and that network requests return HTTP 200. Inspection of `useApi`, `DashboardPage`, `dashboardApi`, and `ServiceSalesCharts` confirms that the dashboard is rendered after the successful summary request updates state. The frontend accepts the JSON body as `DashboardSummary` without runtime shape validation; `DashboardPage` then supplies `data.service_sales` to a chart that immediately reads `sales.by_week`. A successful HTTP status therefore does not rule out a response missing `data.service_sales`.

The current Reporting controller returns `DashboardSummaryService::run`, which includes `service_sales`. The deployed response body and PHP revision remain unverified, so the precise production deployment or response-shaping fault cannot yet be named. No application code or data was changed.
