# Summary report payment source clarity

Date: 2026-11-09

## Change

- Updated the local `codex/merge-consumable-item-worktree-0.0.8` branch to the latest `origin/worktree-0.0.8` commit before editing. The remote update did not change reporting code.
- Gave the selected-sales total greater visual weight and displayed Bank Transfer as its own settlement tile alongside Cash, Card, and Outstanding. Other payment methods remain in the supporting summary.
- Reused the existing cash allocation calculation for every received payment method. Each method now shows its invoice-date source breakdown inline. Zero source rows are hidden; received money not yet applied to an invoice appears only when positive.
- Kept the selected-sales settlement tied to invoice dates and the received-payment breakdown tied to payment dates. Split-method payment amounts are apportioned from persisted payment lines and active invoice allocations.

## Verification

- Updated backend allocation and sales-settlement tests and the Summary Report UI test.
- Targeted PHP tests passed: 3 tests, 10 assertions. The targeted UI test and TypeScript typecheck passed. PHP syntax checks and `git diff --check` passed.

The unrelated, pre-existing untracked data-import change record was left untouched.
