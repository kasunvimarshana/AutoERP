# Check Vehicle Service job type data sources

## Request

Check whether the Vehicle Service job Type field is populated from an API or seeder, referring to the screenshot showing Full Service selected by default.

## Findings

- There is no Vehicle Service job-type seeder, and the application DatabaseSeeder does not call one.
- The four selectable job types are defined in the frontend form and backend `VehicleServiceJobType` enum. The create-defaults API returns supervisor commission defaults only.
- The current create form initializes Type as empty; the checked production build also contains the updated form. The screenshot shows the earlier Full Service preselection, so its behavior does not match the current source/build and may be from a page loaded before the update.
- The original table migration has a Full Service database default. Normal job API writes require an explicit Type, so that database default does not supply the form value or bypass API validation; it could still affect direct database inserts that omit Type.

## Changes

- No application code changed. Recorded the source and data-path findings.
