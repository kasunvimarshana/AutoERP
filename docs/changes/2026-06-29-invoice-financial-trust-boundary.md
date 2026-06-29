# Invoice Financial Trust Boundary

Date: 2026-06-29

## Scope

First implementation slice of the Financial Integrity Foundation milestone. The batch corrects the public Invoice trust boundary, lifecycle entry path, historical snapshots, inclusive-tax total calculation, idempotent manual creation, optimistic concurrency, and Invoice-owned authorization.

## Changes

- Added an Invoice-owned granular permission catalogue and enforced it on every Invoice API route.
- Added frontend Invoice permission constants, feature-owned route entitlements, and permission-aware detail tabs.
- Replaced the public broad `CreateInvoiceData` mapping with a manual-invoice input contract.
- Prohibited caller-supplied invoice numbers, lifecycle status, calculated tax/totals, source facts, system adjustments, metadata, and line numbers.
- Added server-side Tax determination for manual invoice lines.
- Persisted detailed Tax calculation snapshots on Invoice lines.
- Added immutable party, currency, item, and UOM identity snapshots and changed Invoice resources to render those snapshots.
- Added shared Idempotency-backed manual invoice creation.
- Corrected Invoice grand-total calculation to sum authoritative line totals and signed adjustment effects, preserving inclusive-tax semantics.
- Enforced semantically valid adjustment type/effect combinations.
- Made creation persist Draft first and route Approved/Posted targets through governed lifecycle transitions.
- Added approval, posting, cancellation actor/time metadata and canonical `row_version` optimistic concurrency.
- Blocked deletion of non-draft invoices and blocked cancellation of posted/settled invoices.
- Removed caller-provided previous allocation quantity from authority; Invoice now calculates it from persisted active allocation history.
- Added a tenant-composite item foreign key and unique line-number constraint to the canonical Invoice line migration.
- Removed balance/source/adjustment data leakage from the general Invoice detail permission path.

## Ownership

- Tax owns tax determination and calculation.
- Invoice owns manual invoice orchestration, persistence, totals, lifecycle, historical snapshots, permissions, and its public contract.
- Idempotency owns retry identity and conflict handling.
- Source modules own source-generated invoice preparation and source-aggregate locking; the public Invoice endpoint cannot submit source facts.

## Verification performed

- PHP syntax validation for every changed/new PHP file.
- TypeScript syntax/transpile validation for every changed/new TypeScript/TSX file.
- Static search of the changed request confirms prohibited server-owned fields.
- Static review confirms Invoice permission middleware covers list, detail, preview, create, approve, post, cancel, balance, source, and adjustment endpoints.
- Static review confirms Invoice resources render snapshots and no longer expose current master IDs as relationship labels.
- Static review confirms requested posted creation is applied through Draft → Approved → Posted transitions.

## Runtime gates still required

- MySQL `migrate:fresh --seed`.
- Laravel/PHPUnit Invoice, Tax, permission, snapshot, lifecycle, and idempotency suites.
- Concurrent duplicate-create and owner-source allocation tests using independent database connections.
- Browser verification after the manual Invoice entry UI is implemented or updated to the new contract.

## Remaining milestone work

- Complete source-provider registry and database-backed concurrent allocation verification.
- Payment lifecycle redesign.
- Finance account roles and effective assignments.
- Removal of operational-module direct Finance account ownership.
- Ledger-authoritative balance projections and reconciliation commands.
