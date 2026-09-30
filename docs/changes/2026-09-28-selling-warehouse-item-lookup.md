# Warehouse-aware item picker for Selling

## Change

- Replaced Selling's generic item selector with the shared searchable lookup control, styled with item code/name, available base quantity, and an Inventory item badge to match the Vehicle Service job-line picker.
- Added a Selling item lookup endpoint that only returns active stockable items and retains Item view authorization.
- Added an Inventory-owned warehouse availability lookup so the picker displays current available quantity for the selected warehouse.
- Kept sale posting validation and stock issue behavior unchanged; added an API regression test for warehouse-specific availability.

## Reason

The prior Selling selector did not show stock context, and the existing item lookup's availability quantity was aggregated across warehouses. Showing the selected warehouse's balance helps prevent users from choosing an item based on stock located elsewhere.

## Notes

No schema changes or migrations were needed. Existing change records were not modified.
