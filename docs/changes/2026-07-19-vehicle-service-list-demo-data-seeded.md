# Vehicle service list demo data seeded

Date: 2026-07-19

## Problem

The vehicle service job list did not have enough local rows to meaningfully test the list UI, filters, and mixed status presentation.

## Change

- inserted 6 additional demo vehicle service job rows into the local database using the current tenant's existing customers and vehicles;
- added a small spread of realistic statuses for list testing: `draft`, `inspected`, `completed`, and `cancelled`;
- kept the repo clean by removing the one-off helper files after the data was inserted;
- removed the temporary persistence-check row used during verification.

## Seeded rows

- `VSJ-000008` `draft` for customer `2` / vehicle `2`
- `VSJ-000009` `draft` for customer `3` / vehicle `3`
- `VSJ-000010` `inspected` for customer `2` / vehicle `2`
- `VSJ-000011` `inspected` for customer `3` / vehicle `3`
- `VSJ-000012` `completed` for customer `2` / vehicle `2`
- `VSJ-000013` `cancelled` for customer `3` / vehicle `3`

## Verification

- confirmed local database totals after insertion:
  - `vehicle_service_jobs`: `13`
  - demo rows with `codex-demo:vehicle-service-list` marker: `6`
  - temporary `VSJ-TEST-CLI` row removed: `0`

## Scope

This change affected only the local database contents for vehicle service job list testing.
