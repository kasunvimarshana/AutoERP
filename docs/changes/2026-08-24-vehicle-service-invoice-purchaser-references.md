# Vehicle Service invoice purchaser references

Date: 2026-08-24

## Purpose

Show the completed service job, vehicle, and mileage references inside the existing Purchaser box on Vehicle Service invoice printouts without changing the invoice form layout.

## Changes

- Added immutable purchaser reference fields to the invoice document snapshot.
- Vehicle Service invoice creation now snapshots:
  - service job number;
  - vehicle registration number, falling back to the vehicle number when registration is unavailable;
  - job odometer reading with the vehicle's configured odometer unit when mileage is available.
- Rendered the reference fields below the purchaser telephone number inside the existing Purchaser box.
- Kept non-service invoices unchanged because their purchaser reference field snapshot is empty.
- Added a portable migration for the nullable JSON snapshot field; existing historical invoices are not backfilled from current job or vehicle data.

## Data integrity

The printed values come from the invoice-owned immutable document snapshot. Later changes to a Vehicle Service job number, vehicle registration, odometer reading, or odometer unit cannot change an already-created invoice.

## Verification

- Focused Vehicle Service snapshot and print test passed: 1 test, 7 assertions.
- Invoice print and legal snapshot suites passed: 6 tests, 81 assertions.
- PHP syntax checks passed for all changed PHP files.
- Pint formatting checks passed after formatting the touched PHP files.
- A Chrome-rendered A4 preview remained one page and preserved the supplied invoice layout with no clipping, overlap, or additional borders.
