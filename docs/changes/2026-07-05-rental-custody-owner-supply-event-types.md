# Rental Custody Owner Supply Event Types

## Why

Recording custody for an owner supply allocation could fail with `Custody event type is not valid for an owner supply allocation.` The backend rule was correct: owner supply allocations use owner/company custody events, while customer rental allocations use company/customer custody events. The custody page only kept a lookup label for the selected allocation and defaulted every new event to `company_to_customer`.

## What Changed

- Loaded the full selected allocation before enabling custody event submission.
- Derived the available custody event types from the allocation agreement kind.
- Defaulted owner supply allocations to `owner_to_company`.
- Derived and locked custody roles from the selected event type so users cannot submit mismatched from/to roles.
- Added frontend regression coverage for the owner supply custody event payload.

## Verification

- `npx vitest run resources/js/modules/vehicle-rental/pages/RentalCustodyPage.test.tsx --reporter=dot`
- `npm run typecheck`
- `npm run build`
- `git diff --check`
