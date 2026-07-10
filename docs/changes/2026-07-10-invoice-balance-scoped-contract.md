# Invoice Balance Scoped Contract

## Summary

- Changed `InvoiceBalanceProviderInterface::validatePayableState` to require tenant, organization unit, party, and optional currency scope.
- Enforced the scope inside the Invoice module before returning a payable balance.
- Updated Payment, Purchase, Vehicle Service, and Vehicle Rental deposit callers to pass their owned scope into the Invoice provider.
- Added coverage that a payable invoice cannot be resolved through the provider with a different tenant scope.

## Root cause

Settlement callers previously asked Invoice for payable state by raw invoice ID and then validated tenant, organization, party, and currency after the Invoice provider returned data. That made callers compensate for a scope responsibility owned by the Invoice module.

## Design notes

- The Invoice module now owns scoped payable balance lookup.
- Business modules pass their already-owned scope facts instead of reading invoice scope first.
- The old unscoped `validatePayableState(int $invoiceId)` contract was removed rather than kept as a compatibility shortcut.
- Existing read-only balance helpers remain unchanged for their current direct balance use cases.

## Verification

- Source readback should confirm the provider scopes by invoice ID, tenant, organization unit, party type, party ID, and optional currency before settlement validation.
- Source readback should confirm Payment, Purchase, Vehicle Service, and Vehicle Rental deposit callers pass scope into the provider.
- Full local `php artisan test`, frontend typecheck, lint, build, and Vitest should be run before merging.
