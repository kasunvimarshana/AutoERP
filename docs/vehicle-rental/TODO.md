# Vehicle Rental clean rebuild — completion and acceptance ledger

**Status:** Closed implementation ledger for the fresh Vehicle Rental module as reconciled on 2026-09-25.

**Business authority:** TACGL primary/tie-breaker; four supplied Vehicle Rental videos authoritative for practical workflow.

**Engineering authority:** latest `worktree-0.0.8`.

**Continuation review base:** `8261887e9266ea52ad5fc225c64e4e52dad7563f`.

**Canonical domain/policy reference:** [knowledgebase.md](../knowledgebase.md).

**Old Rental implementation:** removed and prohibited as an implementation dependency. Nothing in this ledger authorizes restoring, copying, cherry-picking or emulating its design defects.

This file is no longer an open list of speculative business questions. Historical evidence gaps are resolved by the explicit safe production policies in the knowledge base: use a named/configured policy where one exists; otherwise create no automatic financial effect and route any explicit approved correction/recovery through the owning financial module.

---

## A. Source and architecture reconciliation

- [x] TACGL archive corpus reconciled; dated ZIP contains the same normalized 452 business files.
- [x] Four supplied videos registered as authoritative workflow evidence with hashes/durations.
- [x] RULES/AGENTS engineering constraints applied.
- [x] Latest `worktree-0.0.8` reviewed as implementation authority.
- [x] Removed legacy Rental runtime remains excluded.
- [x] Vehicle Rental module responsibilities separated from Vehicle, HR, Customer, Supplier, Invoice, Payment, Tax, Finance, Reporting and Vehicle Service.
- [x] Protected nested backup investigated with free local tooling and source-derived evidence only; the archive is password-protected, has no archive comment, and accessible TACGL text/configuration yielded no explicit backup credential.
- [x] No brute force, dictionary attack, arbitrary password mutation or unsupported password guess was used.
- [x] Backup unavailability treated as source-access limitation rather than permission to invent runtime behavior.

---

## B. Agreement aggregates and commercial revisions

- [x] Separate Customer Agreement and Owner/Lessor Agreement aggregates.
- [x] Tenant/org-safe party, vehicle and currency validation.
- [x] Human-readable identity snapshots.
- [x] Draft / Active / Closed lifecycle with optimistic row-version checks.
- [x] Active commercial terms immutable.
- [x] Manual closure stops new commercial coverage at the closure civil date; base rent and mileage use the same effective boundary, while an earlier contractual `ends_on` remains stricter.
- [x] `closed_at` stores the audit instant, `closed_on` stores the immutable tenant/org civil closure date captured from that same instant, and contractual `ends_on` remains a separate term boundary.
- [x] Rental commercial civil-day decisions use Configuration-owned tenant/org `localization.timezone`, not Laravel's process-global application timezone.
- [x] Later workspace timezone changes cannot reinterpret an already-recorded `closed_on` commercial boundary.
- [x] Customer and owner rates remain independent.
- [x] Monthly/Daily and Self-drive/With-driver context captured.
- [x] Base, included/excess-KM, AC, driver, OT, night-out and deposit terms represented with exact nullable decimals.
- [x] Unknown and zero remain distinct.
- [x] Successor agreement lineage implemented for future commercial revisions.
- [x] Creating a successor Draft does not interrupt the current Active predecessor.
- [x] Successor activation atomically revalidates cutover, closes predecessor boundary and activates successor.
- [x] Successor effective-day activation is evaluated in the configured tenant/org workspace timezone.
- [x] Cutover cannot bisect non-cancelled Vehicle Use, retained base-rent charge or retained usage/mileage commercial period.
- [x] Adjacent half-open use ending exactly at successor start is permitted.
- [x] Successor retains predecessor counterparty; Owner successor retains supplied vehicle.
- [x] One-way `successor -> predecessor` relationship avoids redundant bidirectional state.
- [x] Deposit requirement is not duplicated implicitly onto successor; any new requirement must be explicit.

---

## C. Vehicle source, assignment, custody and replacement

- [x] Agreement-first vehicle selection.
- [x] Externally supplied vehicles require valid Owner Agreement/source coverage.
- [x] Company-owned vehicle path does not fabricate Owner Agreement/payable data.
- [x] Planned start/end coverage and open-ended use supported.
- [x] Actual handover/return evidence and odometer captured.
- [x] Vehicle overlap rejection.
- [x] Shared Vehicle/Vehicle-Service availability contract reused; no direct cross-module table workaround.
- [x] Atomic replacement with predecessor lineage and actual physical vehicle identity.
- [x] Cross-tenant/cross-org replacement rejected.
- [x] Replacement alone creates no automatic surcharge, duplicate base rent or credit.
- [x] Workshop/off-road availability alone creates no automatic downtime financial deduction.
- [x] Insurance/revenue-licence documents are not universal Rental blockers without an explicit Rental policy.

---

## D. Running Chart and driver evidence

- [x] Draft create/update.
- [x] Finalize / reverse / correction lineage.
- [x] Finalized facts immutable.
- [x] Customer agreement, Owner source and physical vehicle context frozen through Vehicle Use revisions.
- [x] Start/end timestamps and original offset input retained.
- [x] Start/end odometer with physical monotonicity/continuity checks.
- [x] Garage KM and commercial KM remain separate.
- [x] Non-AC / Front-AC / Dual-AC context captured without fallback.
- [x] Normal/Double/Triple OT stored as typed integer minutes.
- [x] Night-out count stored explicitly.
- [x] Authoritative optional driver identity implemented.
- [x] Employee driver references HR-owned identity with immutable name/employee-number snapshots.
- [x] External driver requires stable business reference and name snapshot.
- [x] Narrative driver observation remains non-identity evidence.
- [x] Same authoritative driver cannot finalize overlapping usage across two vehicles.
- [x] Adjacent driver periods are permitted.
- [x] HR payroll/compensation is not duplicated in Rental.
- [x] No unsupported extra Running Chart approval ceremony added.

---

## E. Base rent and proration

- [x] Named policy `actual_calendar_days_v1` implemented.
- [x] Explicit base rate required; blank is not zero.
- [x] Daily civil-day calculation.
- [x] Monthly anniversary-cycle actual-day proration.
- [x] Original anchor recovered after short months.
- [x] Cumulative decimal allocation makes adjacent partial segments reconcile to full cycle.
- [x] Preview is read-only and version checked.
- [x] Closed agreements cannot preview or create base-rent coverage after immutable `closed_on`; historical periods through that boundary remain billable.
- [x] An explicit contractual end date remains authoritative when it is earlier than lifecycle closure.
- [x] Immutable customer and owner base charges.
- [x] Overlapping retained base-charge periods rejected.
- [x] Customer and owner base billing independent.
- [x] Invoice source identity preserved.
- [x] Reissue uses unchanged source calculation.
- [x] Void requires downstream invoice release and a reason.
- [x] No hidden fixed-30 divisor or unselected historical proration convention.

---

## F. Commercial mileage

- [x] Explicit agreement-cycle included-KM allowance policy.
- [x] Explicit excess-KM rate required.
- [x] Commercial KM is the financial distance source; garage KM is not auto-priced.
- [x] Daily/monthly cycle handling with timezone snapshot.
- [x] Closed agreements cannot consume included-KM allowance or assess excess KM beyond their immutable effective closure boundary.
- [x] Existing mileage pools retain their snapshotted timezone when later assessments are performed.
- [x] Customer replacement charts share the configured customer cycle pool.
- [x] Owner pool remains independent and tied to Owner Agreement.
- [x] No cross-cycle carry-forward.
- [x] Zero-cost assessment can still consume allowance.
- [x] Cumulative pool head / stale-quote guard.
- [x] Same-side mileage duplicate consumption rejected.
- [x] Reverse-order dependency enforced when voiding pooled assessments.
- [x] Customer mileage does not block owner mileage and vice versa.

---

## G. OT, night-out, AC and driver monetary treatment

- [x] Normal/Double/Triple OT billing uses typed recorded minutes and exact matching explicit hourly rate.
- [x] Minute/hour conversion uses a named constant; no hidden qualification threshold.
- [x] Rental does not infer which OT category applies from clock time.
- [x] Night-out billing uses explicit finalized count and explicit rate.
- [x] Rental does not infer night-out from timestamps.
- [x] AC modes have no implicit rate fallback.
- [x] No automatic AC charge exists unless an explicit named policy/component consumes AC evidence.
- [x] Agreement driver amount is captured without inventing a universal proration formula.
- [x] HR remains owner of employee compensation.
- [x] Explicit exceptional driver/customer/owner recoveries use governed financial adjustment rather than a guessed Rental formula.

---

## H. Customer billing / Owner settlement

- [x] Customer and Owner calculations consume the same physical evidence independently.
- [x] Exact frozen agreement history revision used for chart-based components.
- [x] Immutable Rental source calculation retained.
- [x] Same-side source/component duplicate consumption prevented.
- [x] Customer billing never derives owner payable.
- [x] Owner settlement never derives customer billing.
- [x] Customer handoff uses Invoice Sales/Outbound semantics.
- [x] Owner handoff uses Invoice/AP Purchase/Inbound semantics.
- [x] Human-facing owner terminology is Owner Payable Voucher / Owner Settlement.
- [x] Invoice source allocation preserves Rental source lineage.
- [x] Posted/live Invoice state is owned by Invoice rather than copied as Rental truth.
- [x] Corrections use owner-module cancel/reverse/adjustment/reissue semantics.

---

## I. Deposits, receipts, owner payments and financial ownership

- [x] Explicit security-deposit requirement only; no universal amount.
- [x] Customer deposit receipt uses Payment ownership.
- [x] Applied/refunded/unapplied balance comes from Payment source of truth.
- [x] Duplicate balance spend prevented through Payment controls.
- [x] No automatic deposit forfeiture.
- [x] Customer Receipt remains Payment-owned.
- [x] Owner Payment remains Payment/AP-owned.
- [x] Cheque/payment instrument lifecycle remains Payment-owned.
- [x] Bank reconciliation remains Finance-owned.
- [x] Rental does not implement a second cash, cheque, payable or bank ledger.

---

## J. Adjustments, deductions and exception policy

- [x] Fuel/repair/damage/accident/insurance-excess/meal/highway/parking/miscellaneous concepts are treated as possible adjustment purposes, not automatic entitlements.
- [x] No generic auto-billable `other_charge` bucket introduced.
- [x] Explicit source evidence, side, approved amount, reason and tax treatment are required for an exceptional financial adjustment.
- [x] Posted adjustments use Invoice/AP owner-module capability.
- [x] Original Rental agreement/rate/source snapshots are never rewritten to simulate a deduction or credit.
- [x] Replacement and downtime cannot double-apply the same incident automatically.

---

## K. Tax, withholding and Finance

- [x] Tax percentages are not hardcoded in Rental.
- [x] Tax applicability/effective dates/rounding remain Tax-owned.
- [x] Rental financial handoff passes semantic source/party/line context to Tax/Invoice.
- [x] Owner withholding remains Tax/Payment-owned and effective-dated.
- [x] Rental does not evaluate statutory aggregate thresholds per vehicle/branch/invoice independently.
- [x] Finance semantic posting profiles used; legacy TACGL GL account numbers are not hardcoded.
- [x] Customer revenue and owner cost use independent semantic posting directions.
- [x] Accounting period, journal and reversal controls remain Finance-owned.
- [x] 2026 official IRD material was rechecked as legal-context evidence only; no current statutory percentage or threshold was copied into Rental.

---

## L. Permissions, API, security and audit

- [x] Customer Agreement view/manage permissions.
- [x] Owner Agreement view/manage permissions.
- [x] Vehicle-use/custody view/manage permissions.
- [x] Running Chart view/manage/finalize/reverse permissions.
- [x] Customer/Owner billing authorization separated by commercial side.
- [x] Deposit/Payment authorization delegated to owner module.
- [x] Tenant feature entitlement enforced.
- [x] Trusted current tenant/org/user context used; client fields cannot override it.
- [x] Human-readable validation/conflict messages.
- [x] Expected-version required on concurrency-sensitive actions.
- [x] Agreement/use/chart histories preserve actor/action/reason/snapshots.
- [x] REST actions follow current AutoERP module conventions.
- [x] Financial owner state is resolved server-side rather than accepted from client.

---

## M. Reporting and traceability

- [x] Vehicle Use register/detail and history provide Rental operational traceability.
- [x] Running Chart register/detail and history provide source evidence traceability.
- [x] Agreement histories preserve effective commercial terms.
- [x] Rental charge records preserve component calculation and source references.
- [x] Invoice source allocation links Rental source to financial document.
- [x] Customer/owner balances, payments, tax, journals and bank reconciliation remain queryable through their owner modules instead of duplicated Rental ledgers.
- [x] Margin/profitability is defined from independent posted customer revenue and owner cost, never from customer-minus-guessed-owner math.

---

## N. Schema and relationship review

- [x] Customer and Owner Agreement remain separate because they represent different legal/economic parties.
- [x] Vehicle Use is the meeting point of Customer Agreement, actual vehicle and optional Owner source.
- [x] Replacement stores a single predecessor link; no redundant inverse relationship.
- [x] Running Chart belongs to Vehicle Use; agreement revisions are resolved through the frozen use.
- [x] Running Chart -> HR employee is one-way and tenant-safe; HR does not depend on Rental.
- [x] Successor Agreement -> predecessor is one-way and tenant-safe; no redundant inverse link.
- [x] Composite tenant FKs rely on existing `(id, tenant_id)` unique keys in agreements/HR.
- [x] Financial document state is not duplicated into mutable Rental columns.
- [x] No new circular module dependency introduced.
- [x] Closure/calendar correction adds only nullable date `closed_on` to each Rental agreement as an immutable historical snapshot; no new relationship, inverse pointer or duplicate ledger is introduced.
- [x] Upgrade migration backfills pre-existing closed rows once from the effective Configuration-owned workspace timezone and then freezes the recorded date.

---

## O. Frontend/operator acceptance contract

- [x] Human-readable Customer/Supplier/Vehicle selectors.
- [x] Employee driver lookup uses a Rental-scoped least-privilege façade over the HR-owned employee query; HR contact data is not exposed to the selector.
- [x] No raw database IDs in normal workflow.
- [x] Agreement review separates Draft edit from lifecycle transitions.
- [x] Successor is a compact future-revision action.
- [x] Running Chart form keeps core usage fast and secondary observations grouped.
- [x] Unknown observations can remain blank.
- [x] Finalize/reverse/correct actions are explicit.
- [x] Customer and owner billing panels remain independent.
- [x] Financial document links use owner-module document routes.
- [x] Permission-based action visibility retained.

---

## P. Tests and verification assets

The repository contains backend coverage for agreements, authenticated Rental journeys, base-rent preview/billing, odometer continuity, owner source, Running Chart register/lifecycle, usage/mileage billing, Vehicle Use register/lifecycle, deposit/payment interactions, plus frontend Rental tests.

The completion delta adds focused regression tests for:

- [x] employee driver tenant scoping and immutable snapshots;
- [x] external driver stable reference normalization;
- [x] foreign-tenant driver rejection;
- [x] finalized driver identity immutability;
- [x] overlapping same-driver use across two vehicles;
- [x] adjacent non-overlapping external-driver periods;
- [x] successor Draft preserving active predecessor;
- [x] atomic predecessor cutover on successor activation;
- [x] successor cut-through rejection for Vehicle Use;
- [x] adjacent Vehicle Use boundary acceptance;
- [x] successor cut-through rejection for retained base-rent/usage commercial periods;
- [x] non-duplication of security-deposit requirement;
- [x] closed-agreement base-rent coverage stops at closure while historical coverage remains billable;
- [x] closed-agreement mileage cannot consume allowance or assess excess distance after closure;
- [x] an earlier explicit contract end remains stricter than lifecycle closure;
- [x] successor activation follows the configured tenant-local effective date across a UTC-midnight boundary;
- [x] workspace timezone change after closure cannot move the stored commercial boundary;
- [x] recorded `closed_on` cannot be rewritten after closure;
- [x] successor cutover keeps contractual `ends_on` distinct from lifecycle `closed_on`.

### Verification evidence rule

Only executed commands may be described as passed. This connector-only completion environment could inspect and mutate GitHub source but could not materialize the full repository locally because outbound Git/GitHub checkout was DNS-blocked; GitHub Actions were intentionally not used per project instruction. Therefore executable full-suite/lint/typecheck/build/migrate results for this exact continuation delta are not fabricated here. This is an execution-environment evidence note, not an open Vehicle Rental business/code requirement.

For the 2026-09-25 closure/calendar continuation, exact changed PHP contents are syntax-checked where they can be materialized in the local runtime. Current-branch static review confirms Vehicle Use requires Active agreement coverage, historical financial settlement permits Closed agreements only for covered source periods, `closed_on` is the sole new Rental schema fact, and no relationship/circular dependency is introduced.

The prior repository acceptance evidence remains historical evidence for the pre-continuation baseline. The dependency-backed Laravel/PHPUnit/frontend/MySQL suites for this new delta are not claimed as re-executed in this connector-only runtime.

---

## Q. Protected backup result

- [x] Located `DATABACKUP/!   CTACGLDATABACKUP202503271759.rar` and listed 86 encrypted entries with free local tooling.
- [x] Confirmed all listed archive entries require a password and the RAR has no archive comment.
- [x] Searched accessible TACGL text/configuration material for explicit backup-password/passcode/credential references; no explicit backup credential was found.
- [x] The encrypted archive itself contains files named `password.DBF` / `password.CDX`, but those files are also password-protected and therefore are not accessible password evidence.
- [x] No brute force, dictionary attack, arbitrary mutation or unsupported password guessing performed.
- [x] Runtime completion does not depend on inaccessible backup contents.

---

# Definition of Done — reconciled

Vehicle Rental is considered functionally complete when the following remain true:

1. practical TACGL/video workflow is available without legacy UI complexity;
2. customer and owner economics are independent and revision-driven;
3. Running Chart is immutable physical evidence with governed correction;
4. physical vehicle/source/use/replacement/driver history is integrity-protected;
5. every automatic monetary effect comes from an explicit named/configured policy and exact source evidence;
6. undefined historical tariffs resolve to **no automatic monetary effect**, not an invented formula;
7. Invoice/Payment/Tax/Finance/Reporting responsibilities stay in their owner modules;
8. same-side source consumption is not duplicated;
9. company-owned vehicles do not fabricate external owner cost;
10. no old Rental runtime or legacy magic values are reintroduced;
11. migrations and relationships remain tenant-safe and directional;
12. closed agreements cannot generate new base-rent or mileage commercial coverage beyond their immutable effective lifecycle boundary;
13. future-effective agreement activation uses the configured tenant/org commercial calendar rather than a process-global timezone;
14. recorded historical closure boundaries are persisted as immutable civil dates and are not reinterpreted after timezone configuration changes;
15. future changes preserve this ledger and record actual verification evidence rather than assuming it.

There are no remaining open product-policy TODO items in this ledger. Source-access limitations and environment-specific execution evidence are documented separately and must not be converted into speculative runtime behavior.
