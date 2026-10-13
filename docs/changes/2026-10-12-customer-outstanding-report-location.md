# Move customer outstanding page into Reports

## Request

Place the Customer Outstanding report in the Reports module alongside the other report pages.

## Changes

- Moved the report page into the Reporting frontend module and routed it at `/reports/customer-outstanding`.
- Added Customer Outstanding to the Reports sidebar and the All Reports Finance group.
- Applied reporting and finance report-view access requirements to match the page and its Finance-owned data endpoint.
- Removed the report link and route from Finance Reports navigation.

## Verification

- `npm run typecheck` passed.
- `git diff --check` passed.
- Automated tests were not run.
