# Vehicle Service History default loading

Date: 2026-09-14

## What changed

- Changed vehicle selection from a required prerequisite into an optional report filter.
- The report now loads recent Vehicle Service jobs across all vehicles when opened.
- Selecting a vehicle filters the same history; clearing it returns to all vehicles.
- Added the vehicle registration label to every history card and retained the selected-vehicle summary when a filter is active.
- Made vehicle_id optional in the Reporting request and kept tenant and organization-unit scoping on the underlying Vehicle Service job query.

## Why

The original requirement described vehicle-number search as a filter. Requiring a selection left the report empty on navigation and hid all useful content until the user took an extra action.
