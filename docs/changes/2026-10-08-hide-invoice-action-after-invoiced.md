# Hide invoice creation actions after invoicing

## Request

Do not offer Create & post invoice on vehicle service jobs already marked Invoiced.

## Changes

- Restricted both the workflow-header primary action and lower Quick actions link to Completed jobs.
- Kept the invoice action available for Completed jobs and hidden for Invoiced jobs.

## Verification

- `npm run typecheck` passed.
- `git diff --check` passed.
- Automated tests were not run.
