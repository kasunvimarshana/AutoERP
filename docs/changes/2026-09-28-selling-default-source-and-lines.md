# Selling defaults and item entry

## Change

- Added a Warehouse-owned automatic source resolver and API for the default warehouse/location, with a single active warehouse or location used only when that choice is unambiguous.
- Reused the resolver in Vehicle Service to keep its existing source-selection policy centralized in the Warehouse module.
- Updated Selling to load those defaults, allow choosing a location, show availability and batches for that exact location, and persist the selected location with the sale.
- Replaced blank item rows with the Service Job-style searchable picker that immediately appends a chosen stock item to the sale table; quantity remains a decimal input.
- Kept stock reduction in Selling's existing atomic sale/invoice posting flow through Inventory's stock movement service.
- Added coverage for location-scoped picker availability, default source resolution, and recording the issued location.

## Reason

Selling needed a quicker stock issue workflow with sensible warehouse/location defaults and location-specific availability, while retaining the existing module boundaries and transactional inventory issue behavior.

## Notes

No schema changes or migrations were needed. Existing change records were not modified.
