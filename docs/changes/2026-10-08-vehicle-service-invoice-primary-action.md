# Surface invoice creation as the completed job primary action

## Request

Make invoice creation easier to find after a vehicle service job is completed while retaining the existing quick action.

## Changes

- Showed the blue `Create & post invoice` primary action in the job workflow header for completed and invoiced jobs, beside History and More actions.
- Retained the existing secondary action in the lower Quick actions section.
- Kept the existing status conditions and invoice route permission enforcement.

## Verification

- `npm run typecheck` passed.
- `git diff --check` passed.
- Automated tests were not run.
