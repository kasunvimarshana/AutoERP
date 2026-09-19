# Vehicle Service completion inventory guard

Date: 2026-08-13

## Purpose

Prevent a Vehicle Service job from being completed while it still contains stock lines that have not been issued from inventory.

## Changes

- Added an authoritative backend completion guard to the Vehicle Service status transition service.
- Reused the existing inventory-line eligibility rule so ordinary inventory lines and stockable combo children require an inventory movement, while cancelled, customer-supplied, external, service, and labour lines do not block completion.
- Kept the validation inside the existing transaction and job-row locking flow so concurrent writes remain version-checked and atomic.
- Returns the existing `DOMAIN_RULE_FAILED` API response with a clear instruction to issue required stock first.

## Verification

- Added API coverage confirming an unissued stock line blocks completion without changing the job state.
- Added coverage confirming the same job completes successfully after its stock line is issued.
