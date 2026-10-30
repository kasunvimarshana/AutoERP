# Navigation assertion audit

Date: 2026-10-30

## Request

Investigate three failing navigation assertions after adding the Consumable Usage Report.

## Findings and changes

- The Payment Method Wise Summary and Service Sales Summary links have registered pages and APIs. The Reporting test expected an older menu list, so its expected labels and route matches were updated.
- Stock Card is an existing Inventory feature guarded by `inventory.stock.view`. The two Inventory tests expected older child lists, so they now include Stock Card when that permission is present.
- The general `/inventory/*` route entitlement accepted any Inventory route permission and could show the Stock Card link to a user without `inventory.stock.view`, even though the page guard would deny entry. Added a specific Stock Card entitlement requiring the same permission as the page and tested the adjustments-only case.

## Verification

- Navigation test file passed: 21 tests.
- `git diff --check` passed.
- TypeScript typecheck passed.
