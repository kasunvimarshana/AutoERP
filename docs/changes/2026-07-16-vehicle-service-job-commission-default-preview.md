# Vehicle Service job commission default preview

Date: 2026-07-16

## Problem

The New Vehicle Service Job form offered `Use organization default`, but it did not load or display the resolved commission type and value. A user could therefore save a financially relevant choice without seeing what the current organization default meant.

The create API client also removed supervisor commission fields from every request. This preserved the organization-default path, but it unintentionally discarded explicit Fixed and Percentage overrides selected by the user.

## Correction

Vehicle Service now exposes a read-only `jobs/create-defaults` endpoint for the create workflow. It:

- is authorized by `vehicle_service.jobs.create` rather than the administrative commission-policy permission;
- reuses `VehicleServiceCommissionPolicyService::resolveSupervisorDefault()` as the single source of truth;
- returns only the resolved commission type and value needed by the job form.

The New Job form now:

- loads and displays the resolved organization default;
- shows the resolved value in the disabled commission-value field while the default option is selected;
- explains that the default applies only when a supervisor is selected;
- blocks default-based submission while the preview is loading or has failed when a supervisor is selected;
- provides Retry and allows an explicit None, Fixed, or Percentage override after a preview failure;
- continues to omit commission fields when the organization default is selected, so the backend confirms the active default again inside job creation.

The create API client now forwards the payload unchanged. Conditional omission remains owned by `VehicleServiceJobForm`, so explicit overrides reach the backend while the organization-default selection still sends no override fields.

Edit Job behavior is unchanged and continues to display the stored historical commission snapshot without loading the current organization default.

## Ownership and relationships

No database schema, model relationship, or cross-module dependency changed.

Vehicle Service remains the owner of both organization commission policies and job commission snapshots. The new endpoint is a job-create presentation contract over the existing policy service; it does not duplicate policy calculation or persistence logic. Customer, vehicle, bill-to customer, and supervisor relationships remain unchanged and continue to use controlled human-readable selectors.

## Verification

Focused coverage verifies:

- the job-create endpoint returns the normalized resolved default under the job-create permission;
- the frontend API calls the job-create default endpoint;
- explicit create-time commission overrides are not stripped;
- the New Job form displays the resolved default value;
- organization-default submission still omits override fields;
- a failed default load blocks blind default submission for a selected supervisor but permits an explicit override or a job without a supervisor;
- Edit Job does not load current defaults and continues to show its stored snapshot.

Run:

```bash
php artisan test --filter=VehicleServiceJobCreateDefaultsTest
php artisan test --filter=VehicleServiceCommissionPolicyTest
npx vitest run resources/js/modules/vehicle-service/api/jobs.test.ts resources/js/modules/vehicle-service/components/VehicleServiceJobForm.test.tsx --reporter=dot --silent
php artisan test
composer test:mysql
npm run typecheck -- --pretty false
npm run lint
npm run test
npm run build
```
