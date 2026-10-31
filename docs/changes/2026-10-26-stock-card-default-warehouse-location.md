# Stock Card default warehouse and location

## Request

Preselect Main Warehouse and its location on the Inventory Stock Card.

## Changes

- The Stock Card now loads the configured default warehouse on opening. The seeded Main Warehouse is marked as the default.
- Its active default location is selected from the warehouse response. When a user chooses another warehouse, the page resolves that warehouse's default location.
- Manual warehouse or location choices take precedence over pending default lookup responses. View waits for the default location lookup to finish, so an early submission does not silently include all locations.

## Verification

- TypeScript typecheck and targeted ESLint passed.
- `git diff --check` passed.
