# Complete the Selling workflows

## Request

Complete Selling with separate create, sales list, and returns navigation, and provide invoice payment entry from a sale.

## Changes

- Split the Selling navigation into Create Selling, Selling List, and Selling Return routes with route entitlements for their responsibilities.
- Added searchable, paginated sales and return history views. Added a tenant and organization scoped return list endpoint and bounded list pagination inputs.
- Kept return posting on the sale workflow, showed remaining returnable quantities, disabled fully returned lines, and blocked quantities above the remaining balance before submission. Backend posting retains its version and idempotency checks.
- Added a Receive payment action for invoices with an outstanding balance. It opens the shared Payment invoice settlement page, which creates the customer receipt and invoice allocation through the existing Payment module.
- Updated route entitlement regression expectations for the separate Selling workflows.

## Verification

- `npm run typecheck` passed.
- PHP syntax checks passed for the Selling controller, return resource, and sale query request.
- `git diff --check` passed.
- Automated tests were not run.
