# Job supervisor prefill and combo correction

## Summary

- Changed supervisor-controlled Workforce labour lines to show the Job Card supervisor in a locked Employee field with a single Add action.
- Kept ordinary labour lines, including Technician, on the searchable non-supervisor employee selector.
- Preserved the Job Card supervisor as the backend source of truth; an alternate supervisor cannot be selected only for an individual labour line.

## Data correction

- Corrected the explicit `uses_job_supervisor` setting on the Supervisor component of the `ACID RAIN REMOVER L` combo item.
- Rebuilt the unworked draft combo snapshot on Job 6 through the owning Vehicle Service services so its Supervisor child now carries the corrected flag.
- Job 6 had no workforce assignments or inventory movements on the affected combo. Its optimistic-lock row version advanced from 14 to 16 during the service operations.
- No database schema or migration changes were required.

## Verification

- Confirmed Job 6 resolves supervisor employee 5 (`su2`) and its regenerated Supervisor child has `uses_job_supervisor = true`.
- Focused Vitest suite: 10 tests passed.
- TypeScript typecheck passed.
- Targeted ESLint passed.
- Production frontend build passed.
- Focused backend combo expansion test passed with 18 assertions.

