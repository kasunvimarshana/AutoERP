# Expense amount decimal validation fix

Date: 2026-09-23

## Why

The expense amount field combined `min="0.000001"` with `step="0.01"`. Browsers use the minimum as the number input's step base, so ordinary amounts such as `100.9` failed native validation even though the frontend business rule and backend both accepted positive decimals with up to six decimal places.

## What changed

- Aligned the Expense form's native number-input step with its six-decimal minimum and the backend's six-decimal precision.
- Removed the incompatible cent-only step grid while preserving native positive-number validation and authoritative backend validation.
- Added a focused regression test proving that `100.9` can be entered and submitted unchanged.

## Data and schema impact

- No database, schema, API, posting, or historical expense data changed.

## Verification

- Focused frontend test, lint, typecheck, and production build were run for this change.
