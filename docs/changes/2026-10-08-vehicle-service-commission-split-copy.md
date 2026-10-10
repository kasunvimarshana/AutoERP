# Clarify pending vehicle service commission splits

## Request

Make the pending multi-employee commission behavior clear in one line.

## Changes

- Replaced the vague pending recalculation message with a selected-employee count and an explanation that commission is split across assigned employees when saved.
- Hid the empty assignment message while employees are pending, avoiding a contradiction with the selected employee chips.
- Left final commission amounts to the existing saved assignment rows, which display the backend-calculated amount per employee.

## Verification

- `npm run typecheck` passed.
- `git diff --check` passed.
- Automated tests were not run.
