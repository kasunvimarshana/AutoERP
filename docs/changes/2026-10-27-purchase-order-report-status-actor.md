# Purchase Order Detail Report status actor

## Request

Show the person responsible for the purchase order's current status in the report: Created by, Submitted by, Approved by, Closed by, or Cancelled by.

## Changes

- Added `cancelled_by` and `cancelled_at` to purchase orders with a tenant-matched user foreign key. The Purchase cancellation service now records the authenticated actor and time in the same version-checked transaction as the status change.
- Exposed the cancellation actor and time through the Purchase API, along with the previously recorded submitter details.
- The report now selects the actor for each PO's current status, resolves names within the tenant, and uses one status action object for the page and exports. A cancelled PO retains its historical approval data but displays `Cancelled by`. Older records without a recorded actor display `Not recorded`.

## Verification

- Purchase order API workflow test: 1 passed, 19 assertions, including cancellation of an approved PO.
- Detailed Purchase Report tests: 2 passed, 50 assertions, covering all five statuses and export text.
- Report page tests: 3 passed. TypeScript typecheck passed.
