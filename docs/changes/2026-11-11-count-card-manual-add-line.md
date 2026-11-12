# Manual paid line action for assigned count cards

Removed the experimental automatic card-and-job-line application flow before deployment. Count card assignment again uses the existing promotion issue action and does not change job lines.

An assigned count card now offers an explicit **Add line** action for its qualifying paid item. On a new job, the item is added to the unsaved draft lines; on an editable existing job, the action uses the existing Vehicle Service line API, then refreshes and opens Job lines. The action is hidden when the same paid item is already present. Existing free redemption and standalone offer flows are unchanged.

No schema or accounting changes were needed. Verified with the promotion component test, TypeScript typecheck, ESLint, and diff check.
