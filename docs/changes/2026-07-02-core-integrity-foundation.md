# Core integrity foundation

Date: 2026-07-02
Base commit: `71c09566e47306a972b1f16efa2268fe133c6d9f`

## Purpose

This change corrects release-blocking ownership, security, lifecycle, balance-authority, and concurrency defects at their source. It deliberately does not retain the removed designs through aliases, mirrored columns, dual writes, or compatibility adapters.

## Decisions

### Finance

- Finance accounts are master-data identities and classifications, not balance stores.
- Opening balances are posted opening journals.
- Ledger entries are immutable debit/credit facts; stored running balances were removed.
- Account balances are derived from the ledger for the requested date range.
- The unscoped mutable `finance_account_balances` projection was removed.
- Journal creation always creates Draft; posting remains a separate governed command.
- Account normal balance must match account type, and account hierarchy cycles are rejected.

### Customer and Supplier

- Creation always starts in `pending_approval`.
- Activation uses the lifecycle command and its permission boundary.
- Customer opening balance was removed; Finance owns it through journals.

### Module ownership

- Tax transactions no longer persist a concrete Finance account ID.
- Purchase adjustment allocations no longer persist Finance posting-profile or account IDs.
- Semantic posting facts remain in source modules; Finance resolves actual accounts.

### Invoice concurrency

- Invoice owns a stable allocation-guard row per source line.
- The guard is locked before reading prior allocations, including the first-allocation case where no allocation row exists.
- Guard rows are locked in deterministic key order to reduce deadlock risk.

### Tenant security

- Cross-tenant IDs are indistinguishable from missing IDs.
- Raw global ownership probes were removed from Warehouse, Customer, Supplier, Sales, Vehicle, HR, and Inventory validation paths covered by this batch.

## Intentional schema break

This is a corrected development/refactor baseline. It removes flawed columns and one flawed table rather than preserving them. An existing deployed database requires a reviewed data migration that converts account and customer opening balances into balanced Finance opening journals before dropping legacy storage.

## Verification boundary

Static PHP and TypeScript syntax checks were run on the overlay. Full Laravel, MySQL concurrency, browser, and deployment verification must run after applying the overlay to the exact base commit in a clean worktree.
