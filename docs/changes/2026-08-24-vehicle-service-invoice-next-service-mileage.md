# Vehicle Service invoice next service mileage

Date: 2026-08-24

## Purpose

Add the job's next service mileage to the immutable Vehicle Service references already shown inside the invoice Purchaser box.

## Changes

- Added `Next Service Mileage` immediately below the current `Mileage` reference when the job has a next-service value.
- Reused the vehicle's snapshotted odometer unit and the existing exact decimal display formatting.
- Kept the field absent when no next service mileage was recorded.
- Preserved the existing invoice borders, dimensions, and all non-service invoice behavior.

## Data integrity

The next service mileage is copied into the invoice document snapshot when the invoice is created. Later changes to the service job cannot alter the issued invoice.

## Verification

- Focused Vehicle Service snapshot and print test passed: 1 test, 9 assertions.
- The one-page A4 preview was visually inspected with Job No, Vehicle No, Mileage, and Next Service Mileage inside the existing Purchaser box.
- No text clipping, overlap, border movement, or additional page was introduced.
