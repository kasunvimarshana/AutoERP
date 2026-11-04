# Correct Customer Outstanding location lookup URL

The report page loads location options for its Location filter when it opens. The frontend requested `/api/v1/finance/customer-outstanding/locations`, but the route belongs to Reporting at `/api/v1/reports/customer-outstanding/locations`, causing a 404. Updated the API client to use the registered Reporting endpoint. No backend routes, data, or relationships changed.

Verification: the focused Customer Outstanding report page test passed (1 test), the frontend typecheck passed, and `git diff --check` passed.
