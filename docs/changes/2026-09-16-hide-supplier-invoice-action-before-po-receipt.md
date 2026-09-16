# Hide supplier invoice action before PO receipt

## Summary

- Changed the Purchase-owned purchase-order capability so an approved PO with no received quantity cannot expose the `Create Supplier Invoice` action.
- The existing PO detail page already renders this action from the backend `can_invoice` capability, so no duplicate frontend business rule was added.
- Partially received and fully received POs retain supplier-invoice availability when they still have invoiceable quantity and the user has permission.

## Capability response

- An approved, unreceived PO now returns `capabilities.can_invoice = false`.
- The capability detail code is `not_received` with the reason `Purchase order must have received quantity before supplier invoicing.`
- Non-approved and fully invoiced reasons remain unchanged.

## Verification

- Approved/unreceived capability regression passed.
- Partially received purchase-order aggregate and capability regression passed.
- Focused result: 2 tests passed, 24 assertions.
- PHP Pint passed after formatting the modified Purchase files.
