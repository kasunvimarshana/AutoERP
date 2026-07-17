# Vehicle Rental UI/UX verification fixes

**Date:** 2026-07-17

## Scope

Resolved the verification failures found after the Vehicle Rental agreement UI/UX simplification without weakening tests or introducing compatibility workarounds.

## Root-cause fixes

### Shared input accessibility

- Replaced the wrapping-label structure in the shared `Input` component with an explicit `label[for]` and `input[id]` relationship.
- Kept hint and error text outside the accessible label while preserving `aria-describedby`.
- Added focused regression coverage for accessible labels, hints, and validation errors.

### Printable agreement projection typing

- Added an explicit printable snapshot projection type for read-time Tax enrichment.
- Preserved the immutable stored snapshot contract while allowing historical snapshots to omit Tax treatment.
- Removed the unsafe component cast and typed `is_taxable` as an optional read projection field.
- Updated the print regression fixture to use the actual printable projection contract.

### Agreement-list pagination state

- Removed synchronous pagination state writes from the agreement-kind effect.
- Page reset is now derived from the authoritative kind-tagged pagination state.
- Existing route-change and page-reset behavior remains covered by the agreement-page tests.

### Targeted Vitest execution

- Changed the test script from ambiguous `--silent` to explicit `--silent=true` so file paths passed after `npm run test --` are parsed as test filters.

## Ownership and impact

- Accessibility logic remains in the shared Input component.
- Printable Tax projection remains in the Vehicle Rental agreement print boundary.
- Pagination ownership remains in the agreement list page.
- Test-runner configuration remains in `package.json`.
- No database, API payload, financial calculation, Tax calculation, allocation, or agreement lifecycle behavior changed.

## Verification commands

```bash
npm run test -- resources/js/shared/components/Input.test.tsx resources/js/modules/vehicle-rental/components/RentalAgreementRateBuilder.test.tsx resources/js/modules/vehicle-rental/components/RentalAgreementPrintDocument.test.tsx resources/js/modules/vehicle-rental/pages/RentalAgreementActivationReview.test.tsx resources/js/modules/vehicle-rental/pages/RentalAgreementPages.test.tsx
npm run typecheck -- --pretty false
npm run lint
npm run test
npm run build
php artisan test
composer test:mysql
git diff --check
```

GitHub-hosted jobs currently terminate before exposing executable steps or logs, so local SQLite/MySQL and frontend command results remain the authoritative verification evidence for this branch.
