# Clarify mobile purchase order actions and line items

## Request

Align purchase order actions on mobile and make the purchase order detail and line items easier to read on phones.

## Changes

- Removed the duplicate View action because the purchase order number already opens its detail page.
- Kept Approve or Submit as the mobile quick action and moved other list actions, including Cancel, into a More menu.
- Made purchase order detail header actions wrap into a two-column mobile layout and added a compact status, supplier, and total summary.
- Opened the Lines tab by default and added mobile line cards that emphasize item, ordered quantity, unit price, line total, and receipt/invoice progress; less-used progress appears under More progress.
- Kept the desktop purchase order and line tables intact.

## Verification

- `npm run typecheck` passed.
- ESLint passed for the changed TypeScript files.
- `git diff --check` passed.
- Automated tests were not run.
