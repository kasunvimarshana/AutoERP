# Backdate missed vehicle service jobs

**Date:** 2026-11-18  
**Modules:** Vehicle Service, Finance

Service job creation now exposes the existing `job_date` field, defaults it to the workspace business date, and accepts a selected past date. The job summary displays that date. Quick completion uses it for the invoice and invoice finance posting; the manual service invoice and payment forms default their dates from the job. Stock reservations and issues created for a service job use the job date, and the issue's finance posting follows the movement date. Actual creation, status, and posting timestamps remain unchanged. The job date cannot be edited after a stock reservation, stock issue, or billing link exists, preventing inconsistent historical records.

Closed accounting periods still block postings. Finance now exposes its existing version-checked, audited reopen and close operations through permission-protected routes and an Accounting Periods page. The page shows branch and tenant-wide periods, requires a reason for each transition, and allows an authorized user to reopen a period before entering missed postings and close it afterward. No schema or relationship was changed; the period listing includes tenant-wide periods because they can govern the current branch's postings.

Verification: focused Vehicle Service PHP tests, Finance accounting-period tests, frontend form/invoice/payment and period-page tests, route-entitlement tests, ESLint, Pint, and diff checks passed. TypeScript typecheck completed separately.
