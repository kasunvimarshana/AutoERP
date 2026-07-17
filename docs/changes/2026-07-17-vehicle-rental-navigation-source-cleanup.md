# Vehicle Rental navigation source cleanup

**Date:** 2026-07-17

## Problem

The Vehicle Rental navigation cleanup was represented in two conflicting places. `navigationConfig.ts` still declared the generic Agreements entry and Invoice/Payment-owned shortcuts, while `tenantWorkspaceNavigation.ts` removed those entries and renamed two labels at runtime.

The rendered menu was correct, but the base configuration remained an incorrect source of truth and required a compatibility-style transformer.

## Correction

- Removed the redundant generic Agreements, Owner Payables, Customer Invoices, and Settlements children directly from the Vehicle Rental navigation definition.
- Defined `Handover & Return Queue` and `Billing & Settlement` directly in the owning navigation configuration.
- Removed the Vehicle Rental-specific filter/rename transformer from the tenant workspace composition.
- Strengthened the focused navigation test to prove the base configuration is canonical and passes through unchanged.

## Relationship review

No database, API, model, route, or module relationship changed.

Invoice and Payment records remain owned by their modules. Lessee and Lessor agreement workspaces remain separate because they represent independent customer-revenue and owner-cost contracts. This focused correction changes only navigation ownership and presentation.

## Verification

```bash
npm run test -- resources/js/app/navigation/tenantWorkspaceNavigation.test.ts
npm run typecheck -- --pretty false
npm run lint
npm run build
git diff --check
git status --short
```
