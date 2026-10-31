# Inventory Valuation item age

## Request

Show item age in the existing Inventory Valuation Report and explain its Search and Status filters.

## Changes

- Added `Item Age (days)` to each valuation layer row, calculated from its creation date to the current date using whole days.
- Shared the age calculation with the existing Inventory Aging Report so both reports agree. The calculation copies the timestamp before normalizing it to the start of the day.
- Left Search and Status behavior unchanged.

## Verification

- Evaluated the Valuation Report age column against a layer created three days ago; it returned 3.
- PHP Pint passed for the changed catalog file, and `git diff --check` passed.
- `ReportingFrameworkTest`: 8 passed, 1 failed in the PDF export expectation test for other report families. The failing test does not include Inventory Valuation.
