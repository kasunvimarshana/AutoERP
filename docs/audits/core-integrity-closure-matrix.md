# Core Integrity Audit Closure Matrix

Authoritative base reviewed: `worktree` commit `71c09566e47306a972b1f16efa2268fe133c6d9f`.

Status meanings:

- **Source-complete** — the owning module contains the fix and a focused regression contract, but the full runtime gate is still pending.
- **Closed** — source-complete and all required runtime gates passed.
- **Verified existing** — the audited source already contains the required foundation.
- **Open** — confirmed issue remains and must be fixed in its owning module.
- **Runtime gate** — source work alone is insufficient; an executable environment is required.

| Area | Finding | Status | Owner / next action |
|---|---|---:|---|
| Vehicle Rental | Draft agreement update was non-transactional and did not lock the aggregate | **Source-complete** | `VehicleRental/RentalAgreementService` now locks and updates inside one transaction; run the full Laravel/MySQL suite before closure. |
| Vehicle Rental | Agreement update and lifecycle requests accepted `expected_version` without enforcing it | **Source-complete** | Update and transition commands validate the locked row version and increment it; concurrency runtime coverage remains required. |
| Vehicle Rental | Customer/supplier exclusivity existed only in application code | **Source-complete** | `rental_agreements` installs a database party-kind invariant for MySQL/PostgreSQL/SQL Server and SQLite; MySQL migration verification remains required. |
| Vehicle Rental | `terminated_by` lacked a tenant-scoped foreign key | **Source-complete** | Added the composite tenant foreign key in the owning migration; verify through `migrate:fresh` on MySQL. |
| Vehicle Rental | Billing timezone was hardcoded to `Asia/Colombo` | **Source-complete** | Configuration now owns the default through `config/vehicle_rental.php`; the database stores the explicit value. |
| Vehicle Rental | Rate activation rewrote prior active effective periods | **Source-complete** | Activation rejects overlaps and never mutates an existing active version; runtime overlap tests remain required. |
| Vehicle Rental | Rate activation lacked optimistic concurrency and terminal-agreement checks | **Source-complete** | Activation requires `expected_version`, locks both records, increments the version, and rejects terminal agreements. |
| Vehicle Rental | Destructive agreement-term replacement has no stable term command model | **Open** | Introduce explicit term create/update/archive commands only when term-level editing requirements are confirmed. |
| Vehicle Rental | Full recorded-time correction lineage for rate versions is absent | **Open** | Add a dedicated supersede/correction command with lineage and recorded-time columns; do not overload activation. |
| Tax | Legal rates and profiles are mutable current rows | **Open** | Implement immutable revisions with effective and recorded time in Tax. |
| Tax | Snapshot recalculation delete/recreate path lacks aggregate serialization | **Open** | Lock a stable source aggregate/idempotency guard before recalculation. |
| HR | Employee rates are mutable and replace-all | **Open** | Move rate history to immutable effective-dated revisions owned by HR. |
| Finance | Account assignments have an empty-set temporal concurrency gap | **Open** | Lock a stable assignment scope aggregate and add optimistic versioning. |
| Finance | Balance authority must remain ledger-derived | **Open verification** | Re-audit account, ledger and reporting paths together before any schema change. |
| Customer / Supplier | Credit policy and tax facts are duplicated across master/profile fields | **Open** | Define one owning aggregate for each rule and migrate callers to it. |
| Invoice | Empty-set source allocation concurrency | **Open verification** | Verify the latest source-allocation guard before marking closed. |
| Inventory | Balance projection has no deterministic rebuild/reconciliation command | **Open** | Add an Inventory-owned rebuild and reconciliation service with tests. |
| Audit | Append-only behavior is enforced only through application models | **Open** | Add database-role/permission enforcement and operational verification. |
| Architecture | Regex-only dependency tests do not prove module ownership | **Open** | Add AST/runtime dependency checks without replacing focused source contracts. |
| Runtime | MySQL migration, concurrency, tenant A/B, queue, file, report and browser E2E gates | **Runtime gate** | Run in a provisioned local/CI environment; no GitHub Actions dependency is required. |

## Verified existing foundations

- Tenant-owned models use fail-closed tenant scoping.
- Item Price provides an immutable revision pattern suitable as a reference for Tax, HR and Rental correction designs.
- Invoice has a draft-first lifecycle.
- Audit events preserve before/after snapshots at the application layer.

## Closure rule

A row moves to **Closed** only when the owning module contains the fix, focused regression coverage exists, and all required runtime gates have passed. Documentation or compatibility aliases alone never close a finding.
