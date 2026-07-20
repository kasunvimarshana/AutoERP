# Vehicle Rental CRUD and lifecycle interfaces

## Problem

The Vehicle Rental tenant workspace exposed read-only agreement, assignment, running-chart, and calculation tables. Tenant-plan enablement and navigation worked, but users could not execute the backend-owned workflows from the React application.

## Root cause

The frontend-entry batch intentionally stopped at read visibility. Mutation interfaces were deferred, so the backend lifecycle existed without a corresponding operational UI.

## Change

- Added agreement create and draft edit forms with customer/owner party selection, dates, currency, tax group, deposit, terms, and rate lines.
- Added a Vehicle Rental-owned agreement-form lookup endpoint. Active tax-group visibility and agreement validation now share one query source of truth.
- Added agreement activation, closure, and effective successor-rate actions with optimistic row-version protection.
- Added assignment creation, handover, return, replacement, and planned-assignment cancellation.
- Added workflow-owned agreement and assignment lookup endpoints guarded by the relevant Vehicle Rental manage permission, avoiding hidden dependencies on unrelated Vehicle Rental view permissions.
- Added scalable assignment list filtering with owned search and status filters in the Vehicle Rental backend.
- Added running-chart draft creation/editing, finalization, and reversal with the complete operational evidence fields.
- Added customer/owner calculation creation and cancellation. Calculation records remain immutable; cancellation releases only that side's source locks.
- Added permission-aware management controls. Tenant-plan enablement still controls module availability, while `vehicle_rental.*.manage` permissions control mutations.
- Added focused frontend workflow contract tests and backend route/filter boundary tests.

## Lifecycle boundary

This UI follows the domain lifecycle rather than inventing generic delete operations:

- Agreements: create → edit draft → activate → successor rates → close.
- Assignments: create planned → handover → return or replace; only planned assignments can be cancelled.
- Running charts: create/edit draft → finalize → reverse; finalized charts are immutable.
- Calculations: create immutable snapshot → cancel; customer and owner sides remain independent.

## Excluded

This change does not add Invoice, Payment, Tax calculation, Finance posting, Reporting, or legacy Vehicle Rental behavior.
