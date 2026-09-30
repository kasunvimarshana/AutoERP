# Vehicle Service Automatic Stock Reservation and Issue

Date: 2026-09-12

## Requirement

Reduce job-processing clicks without losing stock accuracy:

- reserve stock as soon as a stock item is added to a vehicle-service job;
- show other users that part of the on-hand stock is already reserved;
- issue every reserved job item automatically when the job is started;
- keep item reorder levels and make low-stock results visually clear;
- remove the normal line-by-line and bulk `Issue stock` actions from the job UI.

## Final design

### 1. Reservation at line creation

A non-customer-supplied inventory line creates an Inventory-owned reservation in the same locked database transaction as the job-line write. The reservation records:

- tenant and organization unit;
- item, variant, UOM, batch, warehouse, and location;
- entered and base quantities;
- vehicle-service job and job-line source references;
- actor and immutable reservation history.

The line is not saved if there is insufficient available stock or a safe automatic warehouse/location cannot be resolved. This prevents another overlapping job from consuming the same available quantity.

### 2. Automatic source selection

The source is resolved in this order:

1. active default warehouse in the job organization unit;
2. active tenant/global default warehouse;
3. the only active eligible warehouse, when exactly one exists.

For the selected warehouse, its active default location is used. If no default is configured, the only active location is used when exactly one exists.

When multiple choices exist and no default identifies one safely, line creation stops with a clear configuration error. The system does not guess a source and does not open a warehouse/location drawer during the normal job flow.

### 3. Line changes

All reservation changes remain inside the locked job transaction:

- create: persist line, expand combo children, then reserve every inventory line;
- update: release the previous reservation, update/rescale the line tree, then create the replacement reservation;
- delete: release the line and child reservations before deleting the line;
- cancel: release every still-active reservation belonging to the job.

Released reservations remain in inventory history; they are not rewritten or deleted.

### 4. Automatic issue on Start

Changing a job to `in_progress` locks the job/vehicle timeline and all pending inventory lines. For each line, the service:

1. finds its single active reservation;
2. releases the reserved quantity;
3. posts the stock issue from that reservation's exact warehouse/location/batch;
4. posts the related finance entry;
5. links the inventory movement and marks the line as issued;
6. saves the job status transition.

These operations are one transaction. Any missing reservation, stock error, or finance error rolls back the complete Start action, including the job status.

The existing issue endpoint remains available as a backend recovery operation, but the normal job-line UI no longer exposes manual or bulk issue buttons.

## Reorder level and stock-search UX

A nullable decimal `items.reorder_level` field was added for stockable items and is available in create, edit, read, and lookup APIs. Changing an item to non-stockable clears the reorder level.

Vehicle-service item search now shows:

- available quantity;
- reserved quantity, when greater than zero;
- red stock text when available quantity is zero or negative;
- amber stock text when available quantity is at or below the reorder level;
- green stock text when stock is above the reorder level.

Pending inventory lines are labelled `Reserved`; after Start they are labelled `Issued`.

## Concurrency and integrity guarantees

- Job writes use row locking and expected-version checks.
- Inventory balances are locked by the Inventory module during reserve, release, and issue.
- Overbooking is rejected against `quantity_available`.
- Vehicle Service stores source references but does not duplicate Inventory balance logic.
- Combo inventory children follow the same reservation lifecycle.
- Batch/lot items require a selected batch before a reservation can be created.
- Customer-supplied items do not reserve or issue company stock.

## Deployment

Run:

```bash
php artisan migrate
npm run build
```

A default warehouse and default location should be configured for each organization unit that creates service jobs. A single active warehouse/location also works without an explicit default.

## Verification completed

- PHP syntax checks for the changed services, migration, and tests.
- Full Vehicle Service engine suite: 48 tests, 402 assertions.
- Item API suite: 13 tests, 179 assertions.
- Frontend TypeScript typecheck.
- Focused Vehicle Service inventory-flow frontend test.
