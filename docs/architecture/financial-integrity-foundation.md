# Financial Integrity Foundation

## Status

Accepted for staged implementation.

## Problem

The former public Invoice contract accepted caller-supplied invoice numbers, calculated tax and totals, source-document facts, previous allocation facts, and system-generated flags. The generic Invoice calculation also reconstructed grand totals from tax components, which is incorrect for inclusive tax because the tax component is already contained in the line total. Invoice routes had tenant and feature isolation but no module-owned granular permission contract. Invoice creation could also persist a requested posted state directly, bypassing approval/posting transitions and actor/version metadata.

## Decisions

1. Public Invoice endpoints create manual invoice drafts only. Source-generated invoices remain internal owner-module workflows.
2. Public callers provide business inputs and selected references only. Invoice numbers, line numbers, tax amounts, line totals, source facts, lifecycle state, metadata, and system adjustments are server-owned.
3. Tax owns tax determination and calculation. Invoice persists the returned tax breakdown and uses Tax-calculated line totals.
4. Invoice grand total is the sum of authoritative line totals plus signed header-adjustment effects. Tax summary fields remain reporting components and are not blindly added to totals.
5. Invoice adjustment type and effect must be semantically consistent.
6. Manual invoice creation uses the shared Idempotency capability inside the same database transaction.
7. Invoice creation always persists a draft first. Approved and posted targets are reached only through legal Invoice lifecycle transitions.
8. Mutable master identities are copied into immutable invoice header and line snapshots at creation time. Invoice presentation reads those snapshots rather than current master rows.
9. Invoice mutations use optimistic concurrency through `row_version` and require the caller's expected version.
10. Approved or posted invoices cannot be deleted. Posted invoices cannot be cancelled through the draft/approval cancellation command.
11. Invoice owns and registers its permission catalogue. Backend route middleware is authoritative; frontend entitlements mirror it only for navigation clarity.
12. Source-owning modules lock source aggregates. Invoice owns allocation history and calculates prior allocation only from persisted non-cancelled invoice allocations; caller-supplied prior totals are never authoritative.

## Public manual-invoice input

- direction
- invoice date and optional due date
- customer for outbound invoices or supplier for inbound invoices
- currency and exchange rate
- optional document/line tax-group references
- user-entered description, quantity, unit price, discount, and charge
- idempotency key for creation

## Server-owned facts

- invoice type and lifecycle status
- invoice and line numbers
- party discriminator
- tax determination and amounts
- line and document totals
- source documents and source allocations
- calculated/system adjustments
- approval, posting, and cancellation actor/time metadata
- reference snapshots and persistence metadata

## Historical snapshots

Invoice header snapshots retain party number, code, name, legal name, tax registration, contact details, and currency identity. Invoice lines retain item and UOM identity plus the detailed Tax calculation result. Foreign keys remain useful operational references, but historical rendering never depends on mutable current master descriptions.

## Follow-up boundaries

This slice does not replace mutable Payment lifecycle state, introduce Finance account roles, or make ledger projections authoritative. Complete source-provider registry hardening and concurrent owner-source adversarial execution remain part of the following milestone batches and must not be implemented through compatibility code in Invoice.
