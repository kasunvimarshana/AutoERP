# Promotion merge and production dashboard incident

Compared `worktree-0.0.8` (`87bca6a3d`) with `promotion-card-module` (`392bf2fa8`) after the workspace switched to `worktree-0.0.8`. Neither branch contains `ServiceSalesCharts` or the `service_sales` dashboard response field. The promotion branch changes only two dashboard color classes relative to the current worktree branch. Merging it cannot supply the field expected by the currently deployed frontend bundle and therefore does not directly repair the production `by_week` render error.

The prior incident diagnosis inspected a different workspace source snapshot that did contain the new service sales frontend and backend code. To resolve production, identify the deployed frontend/backend revisions and deploy a verified matching pair. No merge, application code, production deployment, schema, or data change was made for this assessment.
