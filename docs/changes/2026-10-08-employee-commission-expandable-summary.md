# Add expandable employee summaries to commission report

## Request

Show one employee summary at a time with job-level commission details available by expanding that employee.

## Changes

- Added an employee-first summary list showing designation, total commission, job count, and earned/pending totals.
- Loads the selected employee's detailed commission entries using the report's existing employee filter, preserving all current report filters and detail pagination.
- Shows job number links, date, customer, vehicle, work, hours, commission, and status in the expanded view, with responsive mobile cards.
- Added employee designation to report group summaries from the reporting query.
- Kept the existing flat detail table for alternate group-by dimensions and retained report export behavior.

## Verification

- `npm run typecheck` passed.
- PHP syntax check passed for `EmployeeCommissionReportService.php`.
- `git diff --check` passed.
- Automated tests were not run.
