# Vehicle service job creation field simplification

Date: 2026-07-31

## Purpose

Simplified the new vehicle service job form while preserving the existing job defaults and edit workflow. Added optional service follow-up and manual card references to the Vehicle Service job record.

## Changes

- Hid the following controls only while creating a job:
  - Bill-to customer
  - Job date
  - Expected delivery
  - Fuel level
  - Priority
  - Supervisor commission
  - Commission value
- Preserved their existing creation behavior:
  - bill-to customer continues to default to the selected vehicle customer;
  - job date continues to use the current business date;
  - priority continues to default to `normal`;
  - the backend continues to resolve the organization supervisor commission default;
  - nullable hidden values remain empty.
- Kept all hidden creation controls available when editing an existing draft job.
- Added optional `Next Service Mileage` and `Manual Job Card` inputs.
- Added nullable `next_service_mileage` and `manual_job_card` columns to `vehicle_service_jobs`.
- Added validation, DTO mapping, service persistence, model casting, API resource fields, frontend types, and job summary display for both values.

## Data integrity

- Next service mileage accepts only non-negative decimal values.
- Manual job card references are limited to 100 characters.
- Both new values remain optional.
- Existing atomic job creation/update transactions and row-version conflict checks are unchanged.

## Verification

- PHP syntax checks passed for all changed backend files.
- `php artisan test --filter=test_optional_job_fields_persist_and_nullable_commission_fields_default_to_none` passed with 10 assertions.
- `npm run typecheck` passed.
- Targeted ESLint passed.
- The focused Vitest command reached the existing repository test-runner startup hang and did not execute a test body.
