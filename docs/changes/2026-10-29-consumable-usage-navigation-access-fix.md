# Consumable Usage navigation access fix

Date: 2026-10-29

## Problem

The `Consumable Usage` link existed in the Expenses navigation config and the page route existed, but `/expenses/consumable-usage` was omitted from the central tenant route entitlement list. Navigation filtering drops links without an entitlement, and the route guard rejects direct visits. The screenshot also showed `Expenses` as a collapsed sibling of the expanded `Finance` workspace, which is expected sidebar behavior.

The active local tenant database had none of the three new `inventory.consumable_usage.*` permissions. The browser's Super Admin role can satisfy frontend permission checks, but the backend resolves Super Admin permissions from the active database catalogue, so the API would reject usage requests until the catalogue was synchronized.

## Changes

- Added the Consumable Usage route entitlement under Finance with Finance and Inventory module requirements and the usage-view permission.
- Added a navigation regression test covering a Super Admin with both modules enabled and the missing-Inventory case.
- Ran the existing `TenantPermissionSeeder` against the one active local tenant. Verified that the usage view, post, and reverse permissions are active afterward. No permission rules were bypassed.

## Verification

- TypeScript typecheck passed.
- The new Consumable Usage navigation test passed in the navigation test file. Three older assertions in that file fail because they expect previous Reporting and Inventory menu child counts; those assertions are unrelated to this change.
- The local permission catalogue contains all three active usage permissions.

## Separate configuration

The current local database still has no `internal_consumption` Finance posting profile or `operating_expense` account role. Menu visibility is fixed, but posting usage needs the Finance profile/account setup described in the implementation record. This was not changed as part of the navigation and permission fix.
