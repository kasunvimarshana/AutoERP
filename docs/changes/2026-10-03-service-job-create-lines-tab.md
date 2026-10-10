# Service job lines during creation

## Request

Let users review service item prices and add job lines while creating a service job, without first saving an empty draft.

## Changes

- Add Job details and Job lines tabs to the create form. Selected items and quantities stay in the form until Save draft is used.
- Extend the Vehicle Service job creation request to validate line data and the job service to create the job and lines in the same database transaction. Existing create requests without lines keep their current behavior.
- Leave the existing post-creation Job lines flow unchanged.

## Verification

- PHP syntax checks passed for the changed request, service, and controller.
- TypeScript typecheck passed (`npm run typecheck`).
- `git diff --check` passed.
- Automated tests were not run.
