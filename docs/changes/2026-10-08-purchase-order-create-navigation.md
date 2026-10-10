# Add purchase order creation to navigation

## Request

Add a Purchase navigation item that opens the purchase order creation form, while keeping the list page button.

## Changes

- Added a “Create Purchase Order” link under Purchase that opens `/purchase/orders/create`.
- Restricted the link to users with purchase order create permission.

## Verification

- Confirmed the link target matches the existing list page button destination.
- `git diff --check` passed.
- Automated tests were not run.
