# Exclude cancelled vehicle service commissions from payable reporting

## Request

Remove technician and supervisor commissions from payable totals when a vehicle service job is cancelled, including jobs cancelled before this change.

## Changes

- Exclude cancelled jobs' technician and supervisor commissions from Technician Work report rows and payable totals.
- Exclude cancelled job incentives from Detailed Vehicle Service report rows and summaries.
- Show zero commission for cancelled jobs in generic labour assignment reports and include the job status for context.
- Keep saved commission amounts unchanged for historical audit; the dedicated Employee Commission report continues to expose cancelled history separately.
- Correct the after-start cancellation permission description to explain the payable reporting behavior.
- Apply the rule from job status, so existing cancelled jobs are covered without data migration.

## Verification

- `vendor/bin/phpunit app/Modules/Reporting/Tests/TechnicianWorkReportTest.php` passed (8 tests, 208 assertions).
- PHP syntax checks passed for all changed PHP files.
- `git diff --check` passed.
