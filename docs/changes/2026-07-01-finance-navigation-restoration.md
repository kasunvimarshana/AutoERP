# Finance navigation restoration

Date: 2026-07-01

## Problem

Finance-owned UI routes existed for accounts, journals, ledger reports, posting profiles, bank reconciliation, budgets, and tax pages, but the tenant sidebar did not register those pages. Users with the Finance module enabled could reach some routes directly while the workspace navigation hid the Finance UI surface.

## Correction

Added Finance and Tax navigation groups under the tenant Finance section. Finance links use the module-owned Finance permission constants, and tax links remain governed by the existing Finance module entitlement until Tax has its own granular permission catalogue.

Navigation filtering now resolves access through the feature-owned route entitlement resolver so sidebar visibility follows the same route policy source as tenant route guarding. Route entitlements are merged with navigation-specific module constraints so shared paths, such as invoice links inside vehicle-rental workflows, do not leak across module boundaries.

## Verification

- `npx vitest run resources/js/app/navigation/navigationUtils.test.ts resources/js/app/access/resolvedRouteEntitlements.test.ts --reporter=dot --silent=true`
- `npm run typecheck`
