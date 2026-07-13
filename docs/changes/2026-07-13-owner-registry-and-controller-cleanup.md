# Owner Registry and Controller Cleanup

**Date:** 2026-07-13  
**Branch:** `worktree-0.0.8`

## Context

The current-source audit found three deterministic ownership defects after the Finance source/profile hardening batch:

1. Finance posting-profile configuration was already routed to `FinanceConfigurationController`, but obsolete duplicate lookup/profile methods remained in `FinanceController`;
2. frontend tenant route access was split between feature-owned registries and a complete duplicated legacy fallback registry;
3. Vehicle Finance installment Invoices retained their installment link after a terminal Invoice lifecycle transition, and the payable request accepted statuses that cannot be governed without a Vehicle Finance GL policy.

## Changes

### Finance controller ownership

- removed obsolete Finance configuration lookup and posting-profile mutation methods from `FinanceController`;
- removed the corresponding dead imports and service dependency;
- retained all account, journal, ledger, statement, aging, tax, revaluation, bank-reconciliation, and budget methods unchanged;
- strengthened the Finance architecture test so configuration routes and mutation logic have one owner only.

### Frontend route entitlement ownership

- removed the duplicated `routeEntitlements.ts` legacy registry;
- changed `resolvedRouteEntitlements.ts` to resolve from feature-owned administration, commerce, Finance, Inventory, and Invoice registries only;
- added coverage for Finance, HR, Tax, Payment, Customer, Purchase, Vehicle Service, Inventory, and unknown routes.

### Vehicle Finance Invoice relationship

- introduced canonical Vehicle Rental Invoice source constants;
- added a Vehicle Rental-owned restoration handler for finance installment Invoice links;
- registered the handler through the existing Invoice source-restoration tag;
- the handler locks the installment, confirms that the link belongs to the terminal Invoice, clears only that link, and increments `row_version`;
- restricted finance-installment payable creation to Draft status until a governed principal/interest/fee GL policy is defined.

### Documentation source of truth

- added `docs/README.md` as the canonical current architecture and release-evidence index;
- historical change records remain append-only evidence but no longer act as independent current-state claims.

## Relationship decisions

- **Removed:** `FinanceController` to posting-profile configuration services. That relationship was dead and duplicated the routed configuration owner.
- **Removed:** resolved route access policy to the legacy fallback registry. Every registered rule is now owned by a feature registry.
- **Added:** Vehicle Finance installment to Invoice terminal-state restoration handler. This relationship is owned by Vehicle Rental and invoked through Invoice's restoration contract.
- **Preserved:** valid cross-module reporting and Finance journal relationships. No unrelated controller or schema refactor was introduced.

## Deferred items

The following were intentionally not changed without stronger evidence or product policy:

- Invoice `customer()` / `supplier()` relationships were not converted to a polymorphic relationship because the global morph-map and all consumers have not yet been proven; a blind conversion could break eager loading or unrelated polymorphic models.
- Vehicle Finance principal, interest, fee, initial-deposit, asset-recognition, liability, tax, and reversal postings remain policy-dependent.
- source-to-GL exception reporting remains a separate cross-module Reporting feature and should be implemented only after the exact governed source catalogue and exception ownership are confirmed.

## Verification status

The authoritative branch was re-read after the large Finance controller replacement and targeted architecture tests were added. This connector environment did not execute migrations, PHP tests, MySQL tests, TypeScript, ESLint, Vite, or Vitest. All gates in `docs/README.md` must pass before deployment or before the next remediation batch.
