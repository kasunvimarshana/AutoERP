# Vehicle Rental calculation review before financial actions

Date: 2026-07-12

## Evidence

The Vehicle Rental calculation API already returns governed source evidence and detailed calculation lines, including measured, allowed, and chargeable quantities, rates, tax, withholding, and totals. The Rental Billing page previously showed only a summary row while exposing submit, approve, and financial-document creation actions directly from that row.

The legacy-video-derived workflow requires users to review the running-chart evidence and the independent lessee-revenue or lessor-cost calculation before creating a customer invoice or owner payable. The interface must guide that review without duplicating backend calculation or lifecycle rules.

## Correction

- Added a focused calculation-review panel inside the Vehicle Rental billing workspace.
- A newly calculated run opens in review immediately, and persisted runs expose one explicit `Review` action.
- The review displays:
  - agreement and billing period;
  - lessee-revenue or owner-cost context;
  - source evidence such as running-chart, rental-expense, or custody references;
  - measured, allowed, and chargeable quantities;
  - rate, tax, withholding, line total, net total, and grand total in the run currency.
- Submit, approve, and create-document actions are now presented only within the calculation review.
- Existing permission checks, row-version-aware API commands, backend status transitions, and Invoice-module ownership remain authoritative.
- Added a frontend behavioral test proving approval is unavailable before review and uses the current calculation row version after the source evidence is displayed.

## Scope

No database schema, calculation formula, tax rule, rate rule, source-consumption rule, status machine, permission, API request, API response, invoice creation, owner-payable creation, or Finance posting behavior changed.

## Verification

- The authoritative billing-page blob was updated through its current Git SHA.
- The resulting page and behavioral test pass standalone TypeScript/TSX syntax bundling checks.
- Remote files were re-read after each write and contain the intended scoped changes.
- The complete TypeScript, ESLint, Vite, frontend, SQLite, and MySQL/MariaDB gates must be rerun from the latest `worktree-0.0.8` head before release approval.
