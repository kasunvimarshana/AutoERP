# Vehicle Service job commission default prefill

Date: 2026-07-16

## Problem

The New Vehicle Service Job form represented the organization supervisor commission policy as a synthetic `Use organization default` option and repeated the resolved value in a separate information panel.

That presentation mixed a value source with the real business choices (`None`, `Fixed`, and `Percentage`). A user could not understand the actual commission type from the selected option alone, and the separate panel duplicated information already associated with the commission fields.

The create payload also omitted commission fields while the synthetic option was selected. The backend then resolved the policy again at save time, which could store a value different from the one the user had reviewed if the organization policy changed after the form loaded.

## Correction

The New Job form now treats the organization policy as an initial-value source rather than a commission type.

- The commission selector contains only the canonical business values: `None`, `Fixed`, and `Percentage of whole job`.
- The resolved organization type and value are displayed directly in the existing commission fields.
- The separate organization-default information panel was removed.
- A small field hint identifies whether the values were loaded from the organization policy or customized for this job.
- Changing the commission type resets the value to the named zero-amount constant so a percentage is not accidentally reused as a fixed amount, or vice versa.
- Editing the prefilled value converts it into an explicit job value while preserving the visible commission type.

## Save semantics

When a supervisor is selected, the form submits the exact commission type and value currently visible to the user. The Vehicle Service backend validates, normalizes, and stores those values as the job commission snapshot.

This establishes the transparent contract:

```text
value reviewed by the user
→ value submitted by the form
→ value stored on the job snapshot
```

The backend policy resolver remains available as a safe fallback for non-UI API clients that omit commission fields. No policy calculation or persistence logic was duplicated in the frontend.

When no supervisor is selected, commission fields are omitted and the existing backend rule stores no supervisor commission.

## Loading failure

If the organization policy cannot be loaded:

- a compact inline error and retry action are shown;
- a job without a supervisor may still be saved;
- a supervised job is blocked until the default loads or the user selects an actual commission type;
- selecting `None`, `Fixed`, or `Percentage` creates an explicit job value and removes the block.

## Edit Job behavior

Edit Job remains unchanged in business meaning. It does not load the current organization policy and continues to display and update the stored historical job commission snapshot.

## Ownership and relationships

No database schema, model relationship, route, permission, or backend service changed.

Vehicle Service remains the owner of:

- organization supervisor commission policy resolution;
- job-level commission validation and snapshot persistence;
- the New/Edit Job presentation contract.

Customer, bill-to customer, vehicle, supervisor, organization policy, and job snapshot relationships were reviewed and retained because each represents a valid business responsibility. No redundant, circular, or bidirectional relationship was introduced.

## Verification

Focused coverage verifies:

- the synthetic organization-default option and repeated panel are absent;
- actual resolved type and value are prefilled;
- unchanged visible defaults are submitted explicitly;
- type and value overrides are submitted correctly;
- editing a loaded value preserves its visible type;
- load failure blocks only supervised jobs without an actual commission selection;
- Edit Job does not load current defaults and preserves its stored snapshot.

Run:

```bash
git diff --check
npx vitest run resources/js/modules/vehicle-service/api/jobs.test.ts resources/js/modules/vehicle-service/components/VehicleServiceJobForm.test.tsx --reporter=dot --silent
npm run typecheck -- --pretty false
npm run lint
npm run test
npm run build
php artisan test --filter=VehicleServiceJobCreateDefaultsTest
php artisan test --filter=VehicleServiceCommissionPolicyTest
php artisan test
composer test:mysql
```
