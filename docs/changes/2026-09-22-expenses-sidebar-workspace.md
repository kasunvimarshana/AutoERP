# Focused Expenses sidebar workspace

Date: 2026-09-22

## Why

Expenses are a frequent operational workflow. Keeping expense entry and setup mixed into the accounting-focused Finance menu made the actions harder to discover, and the missing route-entitlement declarations caused the links to be removed by the fail-closed navigation filter.

## What changed

- Moved the existing expense links into a dedicated top-level `Expenses` workspace in the Finance sidebar section.
- Grouped `All Expenses`, `Add Expense`, and `Expense Types` under that workspace without duplicating them under Finance.
- Registered all three Expense paths in the Finance-owned route-entitlement policy.
- Preserved the Finance tenant feature, selected organization-unit requirement, and individual Expense permissions for every link.
- Added a regression test for workspace visibility, child order, permissions, and route matching.

## Data and schema impact

- No database, permission assignment, or accounting data changes were made.
- Expense posting continues to be owned by the Expense module and recorded in the Finance ledger.

## Verification

- Focused navigation tests passed: 19 tests.
- Targeted ESLint passed.
- Production frontend build passed.
