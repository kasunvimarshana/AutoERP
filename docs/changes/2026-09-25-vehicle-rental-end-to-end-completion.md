# Vehicle Rental end-to-end completion

**Date:** 2026-09-25

**Authoritative base:** `worktree-0.0.8` at `16d1503bad25221c44ed5cff541c872e50c06dbb`

## Scope

Complete the fresh Vehicle Rental implementation without restoring, copying, reviving or depending on the removed legacy Rental runtime. Reconcile TACGL/video evidence, close stale policy TODOs with explicit safe production behavior, strengthen operational integrity, keep financial responsibilities in their owning modules, and prepare the result for direct merge to `worktree-0.0.8` without paid tools or GitHub Actions.

## Source and password investigation

- Reconfirmed TACGL/video source authority from the canonical knowledge base.
- The dated TACGL ZIP contains the same normalized 452 business files as the canonical ZIP.
- Located nested encrypted archive `DATABACKUP/!   CTACGLDATABACKUP202503271759.rar`.
- Parsed TACGL's explicit outer password table and tested only its five exact nonblank stored values with free local archive tooling.
- None unlocked the encrypted backup.
- A DBF field-name scan found no additional populated password/secret/key/backup-password source.
- No brute force, dictionary attack, generated variants or paid recovery tool was used.
- Backup unavailability is treated as a source-access limitation, not permission to invent a Rental rule.

## Production policy closure

The canonical `docs/knowledgebase.md` now defines deterministic production behavior for formerly unresolved rules without inventing rates or statutory values. The central rule is: **if no explicit named/configured monetary policy exists, Rental creates no automatic monetary effect**. Explicit exceptional recoveries/credits/deductions use the owning financial module with source evidence and authorization.

Examples:

- actual-calendar anniversary-cycle proration is the explicit shipped base-rent policy; no hidden fixed-30 divisor;
- replacement alone creates no duplicate base rent, surcharge or automatic credit;
- downtime/off-road state creates no automatic financial deduction;
- garage KM remains physical evidence while automatic excess-KM pricing uses commercial KM;
- no AC-rate fallback;
- OT category and night-out count are recorded facts, not inferred thresholds;
- no invented driver salary/recovery proration;
- accident/insurance/fuel/repair/meal/highway/parking/miscellaneous amounts require explicit governed adjustment;
- company-owned vehicles do not fabricate external owner payable/internal transfer cost;
- tax, withholding, statutory rounding, posting accounts and bank reconciliation remain owner-module concerns.

Supporting external research was used only for scope/boundary decisions: IFRS 15 contract-modification guidance supports preserving historical economics through prospective revisions, while current Sri Lankan IRD material demonstrates why tax-invoice/withholding behavior must remain effective-dated in Tax/Invoice/Payment instead of becoming Rental constants.

## Implemented integrity changes

### Authoritative driver identity

- Added optional Running Chart driver identity with explicit `employee` and `external` sources.
- Employee identity remains HR-owned; Rental stores tenant-safe employee reference plus immutable employee-number/name snapshots on the usage evidence.
- External driver requires a stable business reference and name snapshot.
- Narrative driver observation remains non-identity evidence.
- Finalized charts reject overlapping usage for the same authoritative driver across different vehicles; adjacent periods are valid.
- Finalized driver identity is immutable and follows normal Running Chart reversal/correction lineage.
- Running Chart register search includes driver snapshot identity.

### Least-privilege driver directory

- Rental exposes a small driver-selector façade that reuses `Hr\Services\EmployeeQueryService` instead of duplicating HR master/search logic.
- The endpoint uses Rental Running Chart authorization, so a Rental operator does not need broad HR UI permission merely to select a driver.
- Response is intentionally limited to `id`, `employee_number`, `code`, and display `name`; HR contact fields are not exposed.

### Successor agreements / effective commercial revisions

- Added one-way tenant-safe `successor -> predecessor` self relationship on customer and owner agreements with at most one direct successor per predecessor.
- Creating a successor produces a Draft and leaves the current predecessor Active while the revision is reviewed.
- Successor Draft keeps the same counterparty; an Owner successor also keeps the same supplied vehicle.
- The predecessor security-deposit requirement is not copied automatically because Payment receipts remain attached to the original agreement source.
- A future-dated successor cannot be activated before its effective civil date.
- On/after the effective date, activation atomically:
  - locks/revalidates the predecessor;
  - rejects any non-cancelled Vehicle Use crossing the cutover;
  - rejects retained base-rent charge periods crossing/reaching the cutover;
  - rejects retained usage/mileage commercial periods crossing/reaching the cutover;
  - permits an operational use ending exactly at the new half-open boundary;
  - closes the predecessor at the day-before boundary and records `Supersede` history;
  - activates the successor in the same transaction.

This avoids rewriting historical rates and avoids prematurely disabling the still-current agreement.

## Owner-module fixes

- Fresh Rental invoices now surface the correct Payment-owned handoff from Invoice detail:
  - outbound Rental document -> `Receive customer payment`;
  - inbound Owner settlement -> `Pay owner`.
- The change is intentionally in the Invoice frontend because Invoice owns document state and Payment owns settlement creation; Rental does not implement a duplicate receipt/payment ledger.
- Retired historical source-module invoices remain read-only.

## Relationship review

The completion pass intentionally preserves directional relationships:

- Customer Agreement and Owner Agreement stay separate because they represent independent obligations.
- Vehicle Use remains the single point that joins customer agreement, physical vehicle and optional owner source.
- Replacement stores one predecessor link; no redundant inverse pointer was introduced.
- Running Chart belongs to one Vehicle Use; customer/owner financial sides resolve their frozen agreement revisions from that use.
- Running Chart -> HR employee is optional and one-way; HR has no Rental back-reference.
- Successor Agreement -> predecessor is one-way; no redundant inverse state is stored.
- Invoice/Payment status is not copied into mutable Rental columns.

The new composite FKs are backed by existing `(id, tenant_id)` unique keys on HR employees and both agreement tables.

## Test assets added/expanded

Backend regression coverage was added for:

- employee driver tenant scoping and server snapshots;
- external driver reference normalization;
- foreign-tenant driver rejection;
- finalized driver-identity immutability;
- same-driver overlap across two vehicles;
- adjacent external-driver periods;
- Rental driver-directory availability filtering and minimal response projection;
- successor Draft preserving the Active predecessor;
- early future-successor activation rejection;
- atomic cutover on effective activation;
- Vehicle Use cut-through rejection;
- exact-boundary Vehicle Use acceptance;
- base-rent charge cut-through rejection;
- usage/mileage commercial-period cut-through rejection;
- customer-only deposit term boundary;
- successor deposit requirement non-duplication;
- closed-agreement immutability.

Frontend regression coverage was updated for agreement successor workflow, Running Chart driver data/register presentation and Invoice -> Payment Rental handoff.

## Verification performed in this environment

- Re-read current `worktree-0.0.8` before changes; target remained `16d1503bad25221c44ed5cff541c872e50c06dbb` during the final merge preparation.
- Compared the completion branch against the authoritative base and confirmed it was ahead with no base commits missing at the time of final review.
- Inspected the changed schema relationships and verified the referenced composite tenant keys already exist.
- Reviewed route ordering, resource contracts, authorization boundaries, immutable-model guards, cutover queries, owner-module handoff location and final changed-file set.
- Added focused regression tests for each new integrity rule rather than weakening existing contracts for compatibility.

## Executable verification limitation

This chat environment could access/mutate the repository through the GitHub connector, but direct local Git/GitHub checkout was DNS-blocked. GitHub Actions were deliberately not used because the project instruction explicitly forbids them. Consequently, the full Laravel SQLite/MySQL suites, Vitest, typecheck, lint, build, `migrate:fresh`, and upgrade rehearsal for this exact completion delta were **not executed here** and are not falsely reported as passed.

No paid tool or service was used. The code keeps the repository's normal test/migration assets intact so standard local verification can execute without a special dependency.

## Documentation

- Rebuilt `docs/knowledgebase.md` as the canonical production-policy/domain reference.
- Replaced the stale open TODO backlog with `docs/vehicle-rental/TODO.md` completion/acceptance ledger.
- No open speculative product-policy TODO is carried forward; unknown historical tariffs now have explicit safe runtime treatment rather than guessed formulas.
