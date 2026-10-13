# Show revenue totals for service sales categories

## Request

Show stock item and combo item revenue totals in the Service Sales Summary where each section already shows its profit total.

## Changes

- Displayed each category's existing backend-calculated revenue total beside its profit in the Stock item performance and Combo item performance summaries.
- Kept the totals aligned with the category sales amounts and existing report filters; component costs remain part of profit calculations only.

## Verification

- `npm run typecheck` passed.
- `git diff --check` passed.
- Automated tests were not run.
