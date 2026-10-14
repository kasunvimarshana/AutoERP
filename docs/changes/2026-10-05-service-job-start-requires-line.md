# Require a job line before starting service work

## Request

Prevent a service job from starting when it has no job lines.

## Changes

- Validate in the Vehicle Service status service that an `in_progress` transition has at least one non-cancelled job line.
- Keep the check inside the status transaction, where the job is locked, so concurrent line edits cannot bypass the start rule.
- Return a clear validation error when no active job line exists.

## Verification

- PHP syntax check and `git diff --check` passed.
- Automated tests were not run.
