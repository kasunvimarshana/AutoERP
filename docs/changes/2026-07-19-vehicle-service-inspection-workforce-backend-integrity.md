# Vehicle Service inspection workforce backend integrity

Date: 2026-07-19

## Problem

The Vehicle Service job detail page blocked the `draft` to `inspected` transition when labour-assignable lines had no active workforce assignment. The backend status owner service did not enforce the same invariant, so a direct API request could bypass the frontend validation.

## Change

- enforced workforce readiness inside `VehicleServiceStatusService`, which owns job status transitions;
- when a job has non-cancelled employee-assignable lines, inspection now requires at least one non-cancelled employee assignment;
- jobs without employee-assignable labour remain unaffected;
- retained the existing transition, row-version, tenant, vehicle-timeline and status-history behavior;
- added a focused contract test preventing the backend ownership rule from being removed while the frontend guard remains.

## Scope

Vehicle Service status-transition integrity only. No schema, API payload, permission, commission, Invoice, Payment, Inventory or unrelated module behavior changed.

## Verification

The production service and focused contract were reviewed for syntax and ownership consistency. Full Laravel and MySQL runtime suites were not available in the connector environment and must be run from a normal project checkout.
