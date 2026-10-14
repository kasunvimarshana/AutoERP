# Clarify vehicle service workforce assignment rows

## Request

Make the labor-item assignment area clearer and faster to scan when a service job contains many labor items.

## Changes

- Kept every labor item and its employee picker visible without adding expand/collapse steps.
- Put the commission default beside the labor item name and formatted its type and value for easier reading.
- Made the employee picker action explicit about selecting or adding employees.
- Reduced the assigned employee details to a compact row while keeping hours, commission, Edit, and Remove available.
- Preserved the employee selection panel and batch assignment behavior.

## Verification

- `npm run typecheck` passed.
- `git diff --check` passed.
- Automated tests were not run.
