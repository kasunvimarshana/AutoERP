# Improve mobile purchase order list and approval visibility

## Request

Make the purchase order list easier to use on phones and show administrators the number of purchase orders awaiting approval in the Purchase navigation.

## Changes

- Kept search visible on mobile and made the other filters collapsible, with active-filter count and a clear action.
- Used compact mobile purchase order cards with the order number, workflow status, supplier, total, date, expandable receipt and invoice details, and touch-sized actions. Desktop table columns remain available.
- Added a tenant and organization-unit scoped pending-approval count endpoint, protected by purchase-order approval permission.
- Showed the count badge only to Super Admin users, refresh it after purchase order actions and periodically, and show an unavailable indicator if the count request fails.

## Verification

- `npm run typecheck` passed.
- PHP syntax checks passed for the purchase order controller and API routes.
- `git diff --check` passed.
- Automated tests were not run.
