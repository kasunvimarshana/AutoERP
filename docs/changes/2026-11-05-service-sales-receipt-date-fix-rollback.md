# Roll back service sales receipt date report fix

Date: 2026-11-05

## Request

The user asked to roll back the report fix from commit `7bdad27c0` for now and revisit it later.

## Changes

- Restored all Reporting PHP, TypeScript, and test files changed by that commit to their exact state before the fix.
- Left the earlier consumable-item merge and read-only report review intact.
- Retained the original fix change record as append-only history. This record supersedes its implementation status; the report fix is currently not active.
- No database records or migrations were changed.

## Verification

- `git diff 7bdad27c0^` shows no differences in the restored application and test paths.
- `git diff --check` passed. No application tests were needed because the restored files exactly match the previously tested pre-fix commit.
