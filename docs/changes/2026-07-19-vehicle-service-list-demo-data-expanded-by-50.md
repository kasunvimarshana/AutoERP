# Vehicle service list demo data expanded by 50

Date: 2026-07-19

## Problem

The first demo seed batch improved the vehicle service job list, but it still did not provide enough volume to properly test longer lists, status distribution, and pagination-style UI behavior.

## Change

- inserted 50 more local demo vehicle service job rows using the same existing tenant customer and vehicle data;
- extended the demo dataset with sequential job numbers from `VSJ-000014` through `VSJ-000063`;
- spread the new rows across `draft`, `inspected`, `completed`, and `cancelled` statuses with varied dates, priorities, odometer readings, and totals;
- removed the temporary batch runner after the database update so no helper files remain in the repo.

## Verification

- confirmed local database totals after insertion:
  - `vehicle_service_jobs`: `63`
  - all demo rows with `codex-demo:vehicle-service-list` marker: `56`
  - original demo batch rows: `6`
  - new batch rows with `codex-demo:vehicle-service-list:batch-2` marker: `50`

## Scope

This change affected only the local database contents for vehicle service job list testing.
