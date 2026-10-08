# Fix service sales summary server error

## Request

Investigate the server error shown after applying a date range in the Vehicle Service Sales Summary.

## Cause and change

- The report assembler removed its internal category totals before the response summary finished reading the other service revenue total.
- Kept those totals until summary assembly completes, then removed them from each job response.

## Verification

- The correlation ID in the screenshot matched the Laravel log entry for `Undefined array key "categories"`.
- PHP syntax check passed for the report service.
- `git diff --check` passed.
