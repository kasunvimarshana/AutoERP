# Vehicle Service create and edit job form consistency

Date: 2026-07-15

## Problem

The New Vehicle Service Job and Edit Vehicle Service Job pages used the same shared form, but the supervisor commission controls were rendered only while editing. The create screen instead displayed a separate informational notice.

The create form also initialized commission as `none` and submitted `none` with a zero value. That explicit override bypassed the backend path that resolves and snapshots the active organization supervisor commission default, contradicting the guidance shown to the user.

## Correction

The shared `VehicleServiceJobForm` now presents the supervisor commission controls in the same location for both create and edit workflows.

Create mode offers:

- `Use organization default` as the initial selection;
- explicit `None`, `Fixed`, and `Percentage of whole job` overrides;
- a disabled commission-value input when the organization default or no commission is selected.

When `Use organization default` is selected, the create payload omits the commission type and value. The existing Vehicle Service backend therefore remains the single source of truth and resolves the active organization policy atomically while creating the job snapshot.

Edit mode continues to show and update the stored commission snapshot. It does not present the organization-default option because omitting commission fields during an update intentionally preserves the existing historical snapshot rather than recalculating it from a later policy.

## Intentional lifecycle differences retained

The pages still differ only where the record lifecycle requires it:

- create saves a new draft and uses the `Save draft` action;
- edit loads an existing version-checked job snapshot and uses `Save job`;
- edit remains restricted to mutable job statuses;
- page titles identify whether the user is creating a job or editing an existing job number.

## Ownership and relationships

No schema, model, API route, backend relationship, or cross-module dependency changed.

Vehicle Service continues to own supervisor commission defaults and job commission snapshots. The frontend only controls whether an explicit override is sent. Customer, bill-to customer, vehicle, and supervisor relationships remain human-readable controlled selections and were not modified because they are valid business relationships.

## Verification

Focused tests cover:

- the same supervisor commission controls appearing in create and edit modes;
- create mode defaulting to the organization policy selection;
- organization-default payload omission so the backend can resolve the policy;
- explicit create-time commission override submission;
- stored edit-time commission snapshot presentation.

Run:

```bash
npx vitest run resources/js/modules/vehicle-service/components/VehicleServiceJobForm.test.tsx --reporter=dot --silent
npm run typecheck -- --pretty false
npm run lint
npm run test
npm run build
php artisan test --filter=VehicleServiceCommissionPolicyTest
php artisan test
composer test:mysql
```
