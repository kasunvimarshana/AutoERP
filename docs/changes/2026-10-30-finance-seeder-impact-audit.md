# Finance seeder impact audit

Date: 2026-10-30

## Reason

The local Finance seeder was run to provision missing Consumable Usage posting configuration. The user asked whether that run affected existing Finance paths.

## Findings

- The seeder uses `updateOrCreate` across default Finance types, categories, accounts, roles, assignments, posting profiles, and rules, so a full run has a broader possible write scope than the new usage profile alone.
- In the local AUTOERP tenant, all pre-existing rows in those tables still have their original `updated_at` timestamps equal to `created_at`; the seeder did not change their stored values during this run.
- New rows created by the run were the `OPERATING_EXPENSE` category, account `5400`, `operating_expense` role and assignment, and two posting profiles: `internal_consumption` and `expense_payment`. The new profiles have five rules in total. The existing Inventory role remains assigned to account `1200`.
- No application code or historical transactions were changed by this audit.

The new `expense_payment` profile can make the existing paid-expense posting flow available where it was previously missing, but no existing posting profile or account mapping was changed. No live financial transaction was submitted as part of verification.
