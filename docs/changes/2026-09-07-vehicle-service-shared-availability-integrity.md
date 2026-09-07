# Vehicle Service shared vehicle availability integrity — 2026-09-07

## Purpose and baseline

Continue the evidence-led fresh Vehicle Rental rebuild from `worktree-0.0.8` commit `135ac5af10f220e5823ac4f87a01ae2b0dda3749`. Fix proven integration defects at their Vehicle Service owner before Rental depends on the shared availability contract. No removed Rental code was restored or reused.

## Defects reproduced and fixed

Three database-backed regression tests failed against the baseline:

1. An open-ended availability request beginning before an inspected workshop job missed that future job because the null end was replaced with the request start date.
2. A tenant-wide vehicle's inspected job was ignored when availability was requested from a different organization context.
3. Completing one branch's job changed the physical vehicle to Active even though another branch still had an InProgress job.

The blocker now treats a null requested end as unbounded and checks the selected vehicle's jobs within its tenant, across organization contexts. The status service locks that same vehicle's tenant-scoped job collection in ID order before deciding whether it can release the vehicle. Existing blocking statuses and inclusive workshop date overlap remain unchanged; this does not establish a Rental billing day-count rule.

## Relationship and ownership reasoning

One physical Vehicle is referenced by multiple Vehicle Service jobs. Organization context controls access to a job, but it does not create a second physical vehicle. Filtering the resource timeline to one branch was therefore incorrect for availability and release decisions.

No tables, foreign keys, or model relationships were added, removed or repurposed. Tenant scope remains enforced. Public job lookup/list authorization is unchanged. Cross-branch availability returns only the existing generic reason, without disclosing job, customer or branch details. Both fixes remain in Vehicle Service; Rental acquires no direct dependency on workshop tables. Vehicle-first locking is retained.

A named date-prefix length replaces the blocker’s raw substring length. No commercial rates, calendar policy or financial defaults were introduced.

## Source audit update

A free 7-Zip reader successfully listed the nested TACGL backup: 86 entries, 150,606,473 uncompressed bytes. Extraction then requested a password and stopped without extracting readable business contents. No password was provided or guessed. The knowledge base now distinguishes this password requirement from the earlier reader's unsupported solid-RAR limitation.

Full narration/continuous video review and the dedicated rental dataset remain outstanding. This patch does not claim an end-to-end source audit or a complete Vehicle Rental implementation.

## Verification

A free user-space PHP 8.3 runtime with SQLite and required extensions was established, and dependencies were installed from the existing Composer lock file without changing it.

- New regressions before fix: 3 failures.
- New regressions after fix: 3 tests, 7 assertions passed.
- Vehicle Service and Vehicle suites: 55 tests, 411 assertions passed.
- Laravel Pint on changed files and `git diff --check`: passed.

No MySQL concurrency, browser/UAT or production upgrade tests were run. The fresh Rental module, reciprocal workshop/Rental admission checks, pricing, financial handoffs and UI remain on the TODO list. No paid tools or GitHub Actions were used; the commit includes `[skip ci]`.
