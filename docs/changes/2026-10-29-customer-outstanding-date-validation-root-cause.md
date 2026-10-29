# Customer outstanding date validation root cause

Date: 2026-10-29

Reviewed the reported 422 response for `GET /api/v1/finance/customer-outstanding?as_of_date=2026-10-29`. The response explicitly identifies the `as_of_date` `before_or_equal:today` validation rule. The Customer Outstanding UI defaults the filter from the browser's local calendar date. Laravel's application timezone is UTC, so at the screenshot's response time of 2026-10-28 23:34 UTC, the backend still considered 2026-10-28 to be today while the user's browser showed 2026-10-29 in Sri Lanka. The request is rejected before the report service calculates results.

No application code or data was changed. A future fix should use the same configured business timezone for the report's default/max date and its authoritative backend date validation, while preserving the no-future-as-at-date rule. The backend service's omitted-date fallback and CSV filename date should follow the same calendar rule.
