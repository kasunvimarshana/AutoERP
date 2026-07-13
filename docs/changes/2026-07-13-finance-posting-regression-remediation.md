# Finance Posting Regression Remediation

**Date:** 2026-07-13  
**Branch:** `worktree-0.0.8`

## Context

The first local verification run after the Invoice, Purchase/GRNI, and accounting-period integration exposed a small number of root causes behind a large number of cascading failures. The failures included a MySQL composite foreign-key error, stale architecture assertions, incomplete or duplicated Finance test fixtures, direct Posted-invoice tests without posting plans, and a Vehicle Service owner service attempting to write server-calculated totals through mass assignment.

## Changes

- Added the required `(id, tenant_id)` candidate keys to:
  - `finance_accounting_periods`
  - `finance_accounting_period_events`
  - `invoice_posting_plans`
- Preserved the strict same-tenant composite foreign keys instead of weakening or removing tenant isolation.
- Updated the Invoice restoration architecture assertion to the current `InvoiceSourceRestorationRegistry` contract.
- Updated the accounting-period test tenant fixture to the current Tenant schema without broad model mass assignment.
- Updated the Finance seeder expectation to include the canonical GRNI role on purchase invoices.
- Corrected the Vehicle Service calculation owner to persist server-calculated totals through explicit `forceFill()` in the owning service. The model remains protected from broad mass assignment.
- Expanded the shared Finance posting test fixture to seed complete customer and supplier financial paths, including Invoice profiles, GRNI, payments, advances, deposits, and rental profiles.
- Normalized duplicate active account-role assignments created by legacy test setup while leaving the production resolver fail-closed for ambiguous assignments.
- Updated Purchase ownership tests to inspect the current GRN Finance service and Purchase Invoice posting-plan factory instead of the retired Fast Purchase posting implementation.
- Updated the governed Posted-invoice test to provide a real semantic posting plan and canonical Finance profile rather than bypassing the posting contract.

## Design boundaries

- No production fallback creates missing Finance configuration at runtime.
- No account IDs or account codes were moved into business-module authority.
- Missing Invoice posting plans and missing/inactive posting profiles continue to fail closed.
- Financial models remain guarded; server-owned totals are written only by their owning services.
- The Finance account-role resolver remains strict when production configuration is ambiguous.

## Verification status

The changes were reviewed against the uploaded MySQL migration and Laravel test output and re-read from the authoritative branch through the repository connector. This environment did not execute PHP, MySQL, or frontend tests. The branch must be pulled and the migration plus targeted/full suites rerun before the remediation is considered verified.
