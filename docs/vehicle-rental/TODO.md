# Vehicle Rental clean rebuild — authoritative production TODO

**Status:** Prioritized implementation backlog for a fresh Vehicle Rental module  
**Business authority:** TACGL is primary/tie-breaker; the four supplied videos are authoritative workflow evidence  
**Engineering authority:** latest `worktree-0.0.8`  
**Engineering baseline audited before this update:** `e8edc66fb7a82bf97176cfa2303c7add1c683952`  
**Old Rental implementation:** must not be restored, copied, revived, cherry-picked, or used as an implementation dependency  
**Canonical domain reference:** `docs/knowledgebase.md`

---

## 0. Purpose and implementation rule

This TODO is the execution plan for rebuilding Vehicle Rental from a clean foundation while preserving the business meaning proven by TACGL and the supplied videos.

The target operator flow is intentionally simple:

```text
Owner Agreement — only when externally supplied
Customer Agreement
        -> Select Vehicle
        -> Daily Running Chart
        -> Customer Invoice / Owner Payable Voucher
        -> Customer Receipt / Owner Payment
        -> Reports
```

Customer billing and owner settlement are parallel commercial consumers of the same physical usage evidence:

```text
One finalized Running Chart
    |-- Customer calculation — Customer Agreement rates
    `-- Owner calculation    — Owner Agreement rates
```

Do not make one side depend on the amount or completion state of the other side.

### Hard engineering constraints

- Never invent a financially material rule that TACGL/videos do not prove.
- Never restore the removed Vehicle Rental implementation.
- Do not duplicate Customer, Supplier, Vehicle, HR, Invoice, Payment, Tax, Finance, Reporting, or Vehicle Service responsibilities.
- Keep one source of truth for each business fact.
- Make finalized/posted history immutable and correct through governed reversal/replacement/versioning.
- Keep the user-facing workflow no more complex than required by the business evidence.
- Use named enums/constants/configuration instead of legacy magic codes or account numbers.
- Every change requires an append-only `docs/changes` record and regression verification appropriate to its owning modules.

---

# P0 — Source authority and policy gates

## 1. Freeze the source registry

- [x] Register `TACGL.zip` as the primary Vehicle Rental business/accounting corpus.
- [x] Register all four supplied videos as authoritative workflow evidence.
- [x] Record the current source hashes in `docs/knowledgebase.md`.
- [x] Record `worktree-0.0.8` as implementation source of truth.
- [x] Record that the previous Vehicle Rental runtime must not be restored.
- [ ] For every new business rule discovered later, record its evidence class: `Explicit-TACGL`, `Explicit-Video`, `Cross-source`, `Integrity-derived`, or `Unresolved`.
- [ ] Add source/timestamp/record references to tests or change records for financially material rules.

## 2. Resolve the financially material open-policy gates before production calculation

These are **not optional guesses**. Implement only after business evidence/configuration is authoritative.

- [ ] **VR-U01:** Confirm partial-month monthly-rental proration formula.
- [ ] **VR-U02:** Confirm monthly day-count convention if proration exists.
- [ ] **VR-U03:** Confirm included/free-KM pooling/reset policy across days, months, and replacements.
- [ ] **VR-U04:** Confirm replacement-day/period charging rule.
- [ ] **VR-U05:** Confirm downtime/off-road financial deduction rule.
- [ ] **VR-U06:** Confirm garage-mileage customer/owner treatment.
- [ ] **VR-U07:** Confirm accident/insurance-excess responsibility rules.
- [ ] **VR-U08:** Confirm security-deposit requirement/default policy.
- [ ] **VR-U09:** Confirm deposit application/refund/forfeiture priority.
- [ ] **VR-U10:** Confirm tax applicability by Rental component through Tax configuration.
- [ ] **VR-U11:** Confirm/consume Tax-owner rounding policy; Rental must not invent one.
- [ ] **VR-U12:** Confirm withholding applicability to owner settlements.
- [ ] **VR-U13:** Confirm exact AC-rate fallback/selection behavior where agreement data is incomplete.
- [ ] **VR-U14:** Confirm normal/double/triple OT qualification thresholds unless explicitly stored in the agreement.
- [ ] **VR-U15:** Confirm night-out qualification rule.
- [ ] **VR-U16:** Confirm driver salary/recovery proration where relevant.
- [ ] **VR-U17:** Confirm whether business requires extra Running Chart approval stages beyond Draft -> Finalized.
- [ ] **VR-U18/19:** Do not make Insurance/Revenue Licence Rental blockers without explicit business evidence.
- [ ] **VR-U20:** Confirm any real commercial difference between individual owner and leasing-company settlement; otherwise use one Lessor/Supplier engine.
- [ ] **VR-U21:** Confirm whether company-owned vehicles need internal transfer-cost accounting; default is no artificial owner payable.

### Gate behavior

- [ ] Any production path that requires an unresolved formula must fail explicitly or require configured policy; it must not use a silent fallback.
- [ ] UI must explain which business policy is missing rather than showing a generic 500/validation error.
- [ ] Tests must prove that unresolved-policy paths cannot accidentally post financial documents.

---

# P0 — Architecture and module ownership

## 3. Fresh Vehicle Rental module boundary

- [ ] Create a new `VehicleRental` backend module only from the current clean branch.
- [ ] Do not copy files/classes/migrations/routes/tests from the removed Rental implementation.
- [ ] Give the module only Rental-owned responsibilities:
  - agreements/rate versions;
  - Rental vehicle source/use relationship and custody orchestration;
  - Running Chart physical usage evidence;
  - customer/owner calculation snapshots;
  - same-side source-consumption protection;
  - Rental-specific workflow/report semantics.
- [ ] Integrate Customer identity through Customer APIs/models/contracts.
- [ ] Integrate Lessor/Supplier identity through the proper Supplier/Lessor owner module.
- [ ] Integrate physical Vehicle identity/status through Vehicle.
- [ ] Integrate driver identity through HR.
- [ ] Integrate workshop/off-road availability through Vehicle/Vehicle Service shared availability contracts.
- [ ] Integrate Customer Invoice / owner payable documents through the existing financial-document owner module.
- [ ] Integrate receipts/payments/allocations/refunds/reversals through Payment.
- [ ] Integrate tax through Tax.
- [ ] Integrate journals/accounts/periods/bank reconciliation through Finance.
- [ ] Integrate cross-module reports through Reporting infrastructure.
- [ ] Add module dependency tests to keep the dependency graph acyclic.

## 4. Historical-data boundary

- [ ] Audit persistent production databases for archived/removed Rental tables before introducing new schema names.
- [ ] Define a non-colliding schema/migration strategy.
- [ ] Do not reinterpret historical Invoice/Payment/Tax/Finance records through a new incompatible source type.
- [ ] Preserve historical financial vocabulary that is still required by owner modules to hydrate old records, without exposing it as active Rental workflow.
- [ ] Document any explicit data migration separately; never silently repurpose old columns/tables.

---

# P0 — Core domain model

## 5. Physical Vehicle identity

- [ ] Reuse Vehicle's canonical physical Vehicle ID.
- [ ] Ensure normalized registration formatting cannot create duplicate physical Vehicle identities.
- [ ] Never create duplicate Vehicle rows to represent customer, owner, branch, or agreement context.
- [ ] Keep ownership/source history outside the display registration value.
- [ ] Verify Rental references always remain within tenant/organization boundaries.

## 6. Customer / Lessee Agreement aggregate

Implement source-backed fields/semantics without turning every legacy field into modern configuration.

- [ ] Customer/Lessee reference.
- [ ] Agreement number/reference.
- [ ] Agreement/executing/start/end dates.
- [ ] Draft/Active/Closed lifecycle consistent with project conventions.
- [ ] Monthly/Daily basis.
- [ ] With Driver / self-drive context.
- [ ] Included/max-KM context.
- [ ] Excess-KM rate component.
- [ ] Non-AC / Front-AC / Dual-AC rate components where applicable.
- [ ] Driver salary/recovery component where applicable.
- [ ] Normal/double/triple OT components where applicable.
- [ ] Night-out component where applicable.
- [ ] Supported other/parking/recovery components only where evidence/policy exists.
- [ ] Security-deposit fact/requirement only through explicit policy/value.
- [ ] Tax context references, never hardcoded percentages/accounts.
- [ ] Effective rate/version records.
- [ ] Freeze consumed historical agreement/rate versions.
- [ ] Successor-version flow for future rate changes.
- [ ] Agreement closure that does not mutate already-consumed history.
- [ ] Expected-version/concurrency check on mutable aggregate actions.

## 7. Owner / Lessor Agreement aggregate

- [ ] Lessor/Supplier reference.
- [ ] Agreement number/reference.
- [ ] Vehicle/source coverage context.
- [ ] Agreement/executing/start/end dates.
- [ ] Draft/Active/Closed lifecycle.
- [ ] Monthly/Daily basis.
- [ ] With Driver context where applicable.
- [ ] Included/max-KM context.
- [ ] Owner excess-KM payable rate.
- [ ] Non-AC / Front-AC / Dual-AC owner rate components where applicable.
- [ ] Driver salary/OT/night-out reimbursement components where applicable.
- [ ] Supported owner credits/deductions.
- [ ] Tax/withholding references only through confirmed Tax/Finance policy.
- [ ] Effective rate/version records.
- [ ] Freeze consumed historical versions.
- [ ] Successor-version flow.
- [ ] Closure/concurrency rules.

## 8. Agreement component/rate model

- [ ] Use semantic component enums rather than legacy code strings as business logic.
- [ ] Preserve source legacy code in migration/audit metadata only when useful.
- [ ] Store exact decimal quantities/rates/amounts using project decimal policy.
- [ ] Prohibit invalid component combinations when source evidence makes them mutually exclusive.
- [ ] Do not silently fall back between AC modes or Monthly/Daily units.
- [ ] Do not store raw GL account IDs on normal agreement forms; Finance owns semantic posting mapping.

---

# P0 — Vehicle supply/use, custody, and replacement

## 9. Effective vehicle relationship model

Backend must be able to prove historical supply/use even though the UI stays simple.

- [ ] Represent owner-supply coverage for externally sourced vehicles.
- [ ] Represent customer-use/custody coverage.
- [ ] Link customer use to the valid owner source when externally supplied.
- [ ] Support company-owned vehicle use without manufacturing owner-source/payable data.
- [ ] Store planned/effective start/end facts.
- [ ] Store actual handover/return timestamps where operationally relevant.
- [ ] Preserve row/version/history identity.
- [ ] Prevent physically impossible overlapping active customer use.
- [ ] Prevent invalid owner-source coverage gaps.
- [ ] Validate tenant/organization/vehicle identity consistently.

## 10. Simple agreement-first UI

The normal operator should not manage technical relationship records directly.

- [ ] Active Customer Agreement provides `Select vehicle` / `Assign vehicle` action.
- [ ] Active Owner Agreement provides source-vehicle association where externally supplied.
- [ ] Context pre-fills the agreement/side; do not ask the operator to re-select technical side values.
- [ ] Use searchable human-readable Vehicle selectors.
- [ ] Filter unavailable/conflicting vehicles before save where possible.
- [ ] Backend remains authoritative and revalidates on save.
- [ ] Keep a compact assignment/history view for handover, return, replacement, cancellation, and audit.
- [ ] Do not require a separate universal allocation wizard for normal flow.

## 11. Handover and return

- [ ] Record actual handover timestamp/odometer/custody evidence where required.
- [ ] Record return timestamp/odometer/evidence.
- [ ] Revalidate vehicle/driver availability at operational transition time.
- [ ] Keep planning period policy separate from actual operational timestamps.
- [ ] Do not use handover timestamp as a hidden pricing formula unless the agreement policy explicitly proves it.

## 12. Replacement

- [ ] Record original assignment/vehicle.
- [ ] Record replacement vehicle and effective timestamp/period.
- [ ] Preserve replacement lineage instead of rewriting the original assignment.
- [ ] Revalidate replacement Vehicle availability/source coverage.
- [ ] Prevent cross-tenant/cross-organization replacement lineage.
- [ ] Use deterministic lock order where multiple Vehicle rows/resources are mutated.
- [ ] Ensure Running Charts identify the actual physical vehicle used.
- [ ] Keep replacement charging blocked/configuration-driven until VR-U04 is resolved.

---

# P0 — Daily Running Chart

## 13. Running Chart aggregate

- [ ] Draft creation/editing.
- [ ] Customer/agreement/use context.
- [ ] Vehicle identity.
- [ ] Owner/source context where applicable.
- [ ] Driver identity where applicable.
- [ ] Operational date/period.
- [ ] Start/end timestamps where present.
- [ ] Start/end odometer.
- [ ] Total/commercial/garage-distance facts as separate fields, not one ambiguous number.
- [ ] AC mode/context.
- [ ] Normal/double/triple OT facts.
- [ ] Night-out fact.
- [ ] Remarks/other supported evidence.
- [ ] Original/replacement lineage where applicable.
- [ ] Minimal `Draft -> Finalized -> Reversed/Corrected` lifecycle unless business confirms more stages.
- [ ] Expected-version/concurrency validation.

## 14. Running Chart physical integrity

- [ ] End odometer cannot be lower than start odometer without governed correction.
- [ ] Prevent overlapping physical usage for the same Vehicle.
- [ ] Prevent conflicting Driver usage when Driver is assigned.
- [ ] Validate usage falls within valid customer-use/source coverage.
- [ ] Finalization freezes evidence.
- [ ] Corrections create reversal/replacement lineage; never edit finalized truth in place.
- [ ] Advance shared Vehicle odometer only through the Vehicle owner contract and only when evidence is newer/valid.
- [ ] Do not automatically bill garage mileage until VR-U06 is resolved.

## 15. Running Chart operator UX

- [ ] Fast table/form workflow; avoid a large wizard.
- [ ] Show Date, Vehicle, Driver, start/end KM, derived total KM, time, OT, night-out, AC mode, and remarks as applicable.
- [ ] Keep derived values readable and editable only where business permits.
- [ ] Provide `Save Draft` and `Finalize` as primary actions.
- [ ] Add approval stages only after explicit business confirmation.
- [ ] Clearly show replacement/original context without making operators manually reconcile hidden IDs.

---

# P0 — Independent commercial calculations

## 16. Customer calculation engine

- [ ] Accept only finalized eligible Running Chart/source evidence.
- [ ] Resolve effective Customer Agreement/version for each usage scope.
- [ ] Snapshot agreement/rate identity.
- [ ] Calculate only policy-backed components.
- [ ] Preserve exact quantity, rate, amount, and rounding source for every line.
- [ ] Support component structure for:
  - base rental;
  - excess distance;
  - driver recovery;
  - normal/double/triple OT;
  - night-out;
  - supported other recoveries;
  - discount/credit;
  - tax through Tax owner module.
- [ ] Fail explicitly when a required unresolved rule/configuration is missing.
- [ ] Create immutable Customer Calculation snapshot.
- [ ] Create a same-side source-consumption record/token.
- [ ] Prevent duplicate customer-side consumption.
- [ ] Do **not** mark owner-side evidence consumed.

## 17. Owner calculation engine

- [ ] Accept the same finalized physical source evidence independently.
- [ ] Resolve effective Owner/Lessor Agreement/version.
- [ ] Snapshot owner rate identity.
- [ ] Calculate only confirmed/configured components:
  - base owner rental payable;
  - owner excess-distance payable;
  - driver reimbursement;
  - OT/night-out reimbursement;
  - approved owner credits/expenses;
  - supported fuel/repair/damage deductions;
  - supported advance/debit adjustments;
  - withholding only through confirmed Tax/Finance policy.
- [ ] Create immutable Owner Calculation snapshot.
- [ ] Create an owner-side source-consumption record/token.
- [ ] Prevent duplicate owner-side consumption.
- [ ] Do **not** depend on Customer Invoice amount or status.

## 18. Calculation cancellation/reversal

- [ ] Allow governed cancellation before downstream posting where owner-module state permits it.
- [ ] Restore source eligibility only through explicit reversal/cancellation semantics.
- [ ] Never delete consumed history.
- [ ] Once downstream financial documents are posted, use Invoice/Payment/Finance reversal flows rather than Rental-local mutation.

---

# P0 — Financial-document integration

## 19. Customer Invoice handoff

- [ ] Convert finalized Customer Calculation snapshot into the canonical Invoice owner-module request/DTO.
- [ ] Use `Customer Invoice` terminology.
- [ ] Preserve source Calculation/Running Chart references.
- [ ] Pass semantic line/component identities; do not pass raw legacy GL codes from UI.
- [ ] Use Tax-owner snapshots/configuration.
- [ ] Use Finance semantic posting profile on posting.
- [ ] Preserve idempotency so repeated request cannot create duplicate Invoice.
- [ ] Expose downstream Invoice number/status/balance in Rental UI without duplicating Invoice state.

## 20. Owner Payable Voucher / Owner Settlement handoff

- [ ] Use **Owner Payable Voucher / Owner Settlement** terminology.
- [ ] Do not model the normal flow as a customer-style “Owner Invoice”.
- [ ] Create canonical payable/AP financial document from Owner Calculation snapshot.
- [ ] Preserve source Calculation/Running Chart references.
- [ ] Pass semantic components/deductions.
- [ ] Use Tax/Finance owner modules for tax/withholding/posting.
- [ ] Preserve idempotency.
- [ ] Expose payable number/status/balance in Rental UI.

## 21. Posted-document immutability

- [ ] No generic Edit/Delete for posted Customer Invoice or Owner Payable.
- [ ] Corrections use owner-module reversal, debit note, credit note, or replacement document.
- [ ] Rental UI explains correction path rather than bypassing financial controls.

---

# P0 — Customer receipts and owner payments

## 22. Customer Receipt

- [ ] Launch/create Customer Receipt through Payment owner module.
- [ ] Default customer/invoice context from Rental workflow.
- [ ] Support allocation through canonical Payment allocation service/API.
- [ ] Display receipt/allocation state from Payment source of truth.
- [ ] Preserve partial/unapplied balance behavior defined by Payment.
- [ ] Do not create a Rental-local cash/cheque ledger.

## 23. Owner/Supplier Payment

- [ ] Launch/create Owner Payment through Payment owner module.
- [ ] Default owner/payable context.
- [ ] Support allocation through canonical Payment service/API.
- [ ] Preserve cheque/instrument lifecycle through Payment/Finance.
- [ ] Display payment/allocation state from owner module.

## 24. Debit notes, credit notes, adjustments, deductions

- [ ] Enumerate only evidence-supported Rental adjustment purposes.
- [ ] Record reason/source reference.
- [ ] Prevent duplicate application of the same deduction/adjustment.
- [ ] Use financial owner-module documents for posted adjustments.
- [ ] Do not silently rewrite agreement rates or original settlement snapshots.

---

# P1 — Deposits and advances

## 25. Security deposit capability

Implement only once deposit policies needed by the chosen release slice are confirmed.

- [ ] Store explicit agreement deposit requirement/fact.
- [ ] Model append-only deposit movements rather than one mutable balance field as the sole history.
- [ ] Link receipts through Payment identities; support multiple partial receipts.
- [ ] Support application/refund/forfeiture only through confirmed policy/actions.
- [ ] Recalculate balance from authoritative movements.
- [ ] Guard each movement with tenant/party/agreement/payment identity validation.
- [ ] Expose received/applied/refunded/forfeited/remaining amounts clearly.
- [ ] Add reversal lineage rather than deleting movement history.

---

# P0 — Finance, Tax, cheque, and bank reconciliation

## 26. Finance semantic posting

- [ ] Define semantic Rental posting roles/profile requirements through Finance-owned configuration.
- [ ] Do not hardcode TACGL account `7048-000` or any legacy account number.
- [ ] Verify Customer Invoice posting creates the required receivable/revenue/tax journal atomically.
- [ ] Verify Owner Payable posting creates the required cost/payable/tax/withholding journal atomically.
- [ ] Verify Payment allocations/reclassifications follow existing Payment/Finance contracts.
- [ ] Respect accounting-period close controls.
- [ ] Ensure source -> financial document -> tax -> journal lineage is queryable.

## 27. Tax integration

- [ ] Keep tax percentages/rules in Tax configuration.
- [ ] Pass semantic component taxable context only when business policy proves it.
- [ ] Store tax snapshot/reference with posted calculation/document.
- [ ] Add tests proving Rental cannot bypass Tax owner service.

## 28. Cheque and bank reconciliation

- [ ] Reuse Payment instrument/cheque lifecycle.
- [ ] Reuse Finance bank/reconciliation lifecycle.
- [ ] Rental only exposes source context/status links.
- [ ] Do not implement a second reconciliation engine.

---

# P0 — Vehicle Service / availability integration

## 29. Shared availability contract

- [ ] Define/reuse one shared Vehicle availability policy/contract.
- [ ] Vehicle Rental publishes planned/active custody/use blockers.
- [ ] Vehicle Service publishes workshop/maintenance/breakdown/off-road blockers.
- [ ] Vehicle selection filters using the shared policy.
- [ ] Backend always revalidates availability in the transaction.
- [ ] Avoid direct table coupling between Rental and Vehicle Service.
- [ ] Preserve reason/source metadata so the UI can explain why a Vehicle is unavailable.
- [ ] Do not convert unavailability into automatic financial downtime deduction until VR-U05 is resolved.

---

# P0 — Permissions, audit, and security

## 30. Permissions

Define semantic permissions aligned to real tasks, for example:

- [ ] Vehicle Rental view.
- [ ] Customer Agreement view/manage.
- [ ] Owner Agreement view/manage.
- [ ] Vehicle assignment/custody view/manage.
- [ ] Running Chart view/manage/finalize/reverse.
- [ ] Customer calculation/billing create.
- [ ] Owner calculation/settlement create.
- [ ] Rental adjustments/deposit manage where enabled.
- [ ] Rental reports view/export.

Do not reproduce numeric legacy user levels.

## 31. Auditability

- [ ] Record creator/updater/action actor for mutable workflow records.
- [ ] Keep state-transition history for important aggregates.
- [ ] Store reversal/correction reason and lineage.
- [ ] Store agreement/rate/source snapshot references on calculations.
- [ ] Make source-to-Invoice/Payable/Payment/GL traceability available to authorized users.
- [ ] Never log secrets or sensitive document contents unnecessarily.

---

# P0 — APIs

## 32. API design

- [ ] REST resources/actions follow current AutoERP conventions.
- [ ] Human-readable validation errors.
- [ ] Explicit transition endpoints/actions for Activate/Close/Finalize/Reverse/Handover/Return/Replace where appropriate.
- [ ] Expected-version required on concurrency-sensitive updates.
- [ ] Tenant/organization scope enforced server-side.
- [ ] Reject client-supplied owner-module state that the server can resolve authoritatively.
- [ ] Idempotency protection on downstream financial creation.
- [ ] Pagination/filter/search for agreement, Running Chart, and history lists.
- [ ] Avoid exposing internal raw GL/account mapping as ordinary Rental API fields.

---

# P0 — Frontend / operator workflow

## 33. Navigation

Keep the module compact. Recommended top-level concepts:

```text
Vehicle Rental
  Overview
  Customer Agreements
  Owner Agreements
  Running Charts
  Customer Billing
  Owner Settlements
  Deposits/Adjustments — only if enabled/needed
  Reports
```

- [ ] Do not create one page for every backend table.
- [ ] Do not expose a generic technical “side” selector where agreement context already determines it.
- [ ] Hide actions the user lacks permission to execute.

## 34. Customer Agreement UI

- [ ] Simple sections: party/dates, basis, rental/KM, driver/OT/night-out, tax/deposit/notes.
- [ ] Searchable Customer selector.
- [ ] Clear Monthly/Daily and With Driver/self-drive choices.
- [ ] AC-rate fields only when relevant.
- [ ] No raw GL code inputs.
- [ ] Activate/close/version actions clearly separated from ordinary draft edits.
- [ ] `Select Vehicle` action from active agreement.

## 35. Owner Agreement UI

- [ ] Same simplicity principles.
- [ ] Searchable Lessor/Supplier and Vehicle selectors.
- [ ] Owner-side rate labels clearly distinguished from customer rates.
- [ ] No customer-billing amount copied into owner form.
- [ ] `Owner Payable Voucher` terminology downstream.

## 36. Running Chart UI

- [ ] Fast entry/edit while Draft.
- [ ] Clear derived KM values.
- [ ] Driver/AC/OT/night-out context only where relevant.
- [ ] Finalize action with concise confirmation.
- [ ] Reversal/correction action requires reason.
- [ ] Show downstream customer/owner consumption independently.

## 37. Customer Billing UI

```text
Select/derive Customer Agreement + Period
-> Load eligible finalized Running Charts
-> Review component calculation
-> Create Customer Invoice
```

- [ ] Display component quantities/rates/amounts.
- [ ] Mark unresolved-policy error precisely.
- [ ] Show created Invoice number/status/balance.
- [ ] Provide Customer Receipt handoff.

## 38. Owner Settlement UI

```text
Select/derive Owner Agreement + Period
-> Load eligible finalized Running Charts
-> Review owner calculation/deductions
-> Create Owner Payable Voucher
```

- [ ] Display owner-side components independently.
- [ ] Show payable number/status/balance.
- [ ] Provide Owner Payment handoff.

---

# P0 — Reports

## 39. Operational reports

- [ ] Vehicle utilization/usage by period.
- [ ] Customer Agreement/use history.
- [ ] Owner/source Vehicle history.
- [ ] Running Chart register/detail.
- [ ] Driver usage where applicable.
- [ ] Original/replacement Vehicle lineage.
- [ ] Vehicle availability/conflict history where useful.

## 40. Financial reports

- [ ] Customer Rental billing by customer/vehicle/agreement/period.
- [ ] Customer outstanding/receipt/allocation view through financial source of truth.
- [ ] Owner payable by owner/vehicle/agreement/period.
- [ ] Owner payment/allocation/deduction statement.
- [ ] Rental margin/profitability only from independently posted customer revenue and owner cost; never calculate owner cost from customer revenue.
- [ ] Source Running Chart -> Calculation -> Invoice/Payable -> Payment -> GL drill-down.
- [ ] Tax/Finance reconciliation views.
- [ ] Export authorization and audit where project standards require it.

---

# P0 — Testing and verification

## 41. Domain tests

- [ ] Customer and owner agreement independence.
- [ ] Effective agreement/rate version selection.
- [ ] Historical versions immutable after consumption.
- [ ] Company-owned Vehicle skips external owner payable path.
- [ ] External Vehicle requires valid owner/source coverage.
- [ ] Vehicle overlap rejection.
- [ ] Driver overlap rejection where applicable.
- [ ] Replacement lineage/integrity.
- [ ] Running Chart odometer/time integrity.
- [ ] Finalized Running Chart immutability/reversal.
- [ ] Customer same-side duplicate consumption rejected.
- [ ] Owner same-side duplicate consumption rejected.
- [ ] Customer consumption does not block owner consumption.
- [ ] Owner consumption does not block customer consumption.
- [ ] Unresolved policy paths fail closed.

## 42. Calculation tests after each policy is confirmed

- [ ] Monthly base rental.
- [ ] Daily/date-range rental.
- [ ] Included/excess KM.
- [ ] Non-AC / Front-AC / Dual-AC.
- [ ] With Driver / self-drive.
- [ ] Driver salary/recovery.
- [ ] Normal/double/triple OT.
- [ ] Night-out.
- [ ] Replacement charging.
- [ ] Partial month.
- [ ] Tax/withholding.
- [ ] Exact decimal/rounding behavior.

Each test must reference the authoritative policy/source it validates.

## 43. Owner-module integration tests

- [ ] Customer Calculation -> Invoice exactly once.
- [ ] Owner Calculation -> Payable exactly once.
- [ ] Customer Receipt allocation/reversal.
- [ ] Owner Payment allocation/reversal.
- [ ] Tax snapshot ownership.
- [ ] Finance journal ownership/period enforcement.
- [ ] Source-to-GL traceability.
- [ ] No generic edit/delete of posted documents.

## 44. Database-engine tests

- [ ] Default/SQLite suite where project uses it.
- [ ] MySQL/MariaDB strict profile.
- [ ] Composite tenant FK behavior.
- [ ] Unique/overlap/source-consumption constraints.
- [ ] Real transaction/locking/concurrency cases on MySQL.
- [ ] `migrate:fresh --seed` for clean install.
- [ ] Upgrade rehearsal from the supported previous production schema before release.

## 45. Frontend tests

- [ ] Agreement-first vehicle selection.
- [ ] Draft/edit/activate/close/version actions.
- [ ] Running Chart create/edit/finalize/reverse.
- [ ] Customer and owner calculation flows.
- [ ] Source eligibility and duplicate-consumption messaging.
- [ ] Permission visibility/read-only behavior.
- [ ] Stale row-version handling.
- [ ] Vehicle Service availability error handling.
- [ ] Customer Receipt / Owner Payment handoff.
- [ ] Accessible labels and keyboard flow.
- [ ] Windows-portable test paths.

## 46. Browser E2E / UAT

Run real user workflows for:

- [ ] owner-supplied Vehicle;
- [ ] company-owned Vehicle;
- [ ] self-drive;
- [ ] with-driver;
- [ ] Monthly basis;
- [ ] Daily basis after policy confirmation;
- [ ] excess-KM case;
- [ ] OT/night-out case after policy confirmation;
- [ ] replacement after charging-policy confirmation;
- [ ] customer billing first, then owner settlement;
- [ ] owner settlement first, then customer billing;
- [ ] Customer Receipt + allocation;
- [ ] Owner Payment + allocation;
- [ ] reversal/correction;
- [ ] workshop/off-road availability conflict;
- [ ] report and GL drill-down.

---

# P1 — Performance and operability

## 47. Performance

- [ ] Index agreement effective-period lookup.
- [ ] Index Vehicle/source/customer-use period queries.
- [ ] Index Running Chart eligibility/period queries.
- [ ] Index source-consumption lookup.
- [ ] Avoid N+1 relation loading in list/report APIs.
- [ ] Load large selectors through paginated/search APIs.
- [ ] Measure report queries on realistic Rental volumes before optimization.

## 48. Operational monitoring

- [ ] Explicit logging for failed financial handoffs without leaking secrets.
- [ ] Detect orphan source-consumption / downstream-document inconsistencies.
- [ ] Health checks cover required queues/schedulers only if Rental introduces asynchronous work.
- [ ] No hidden background repair job that changes financial history automatically.

---

# P0 — Documentation and traceability

## 49. Documentation

- [x] Maintain `docs/knowledgebase.md` as canonical domain knowledge.
- [x] Maintain this TODO as the implementation backlog.
- [ ] Add API/domain design documentation when code is introduced.
- [ ] Add source-to-rule traceability for confirmed formerly-unresolved policies.
- [ ] Add operator/UAT workflow documentation after UI stabilizes.
- [ ] Add Finance posting-profile/configuration documentation.
- [ ] Keep `docs/changes` append-only for each implementation batch.

---

# Release slicing — avoid a giant rewrite

The complete TODO is intentionally comprehensive, but implementation should proceed in small, verifiable owner-module batches.

## Release Slice A — source-backed operational foundation

Can proceed without inventing unresolved financial formulas:

- [ ] fresh module/provider/permissions/routes;
- [ ] Customer and Owner Agreement persistence/versioning;
- [ ] simple agreement-first Vehicle selection;
- [ ] effective source/use relationship and overlap integrity;
- [ ] handover/return/replacement lineage without commercial charging assumptions;
- [ ] Running Chart Draft/Finalized/Reversed physical evidence;
- [ ] shared Vehicle Service availability integration;
- [ ] UI for agreements/vehicle selection/Running Charts;
- [ ] architecture, tenant, concurrency, MySQL, frontend tests.

## Release Slice B — confirmed commercial calculation foundation

Proceed only for policies already explicitly confirmed/configured:

- [ ] Customer and Owner calculation snapshot framework;
- [ ] same-side source consumption;
- [ ] component engine using only confirmed rules;
- [ ] explicit missing-policy failures;
- [ ] review UI.

## Release Slice C — financial handoffs

- [ ] Customer Invoice;
- [ ] Owner Payable Voucher;
- [ ] Customer Receipt/Owner Payment handoffs;
- [ ] Tax/Finance semantic posting;
- [ ] allocation/reversal/source-to-GL tests.

## Release Slice D — deposits/adjustments and full reports

Proceed after the relevant open policies are confirmed.

## Release Slice E — production readiness

- [ ] all required policy gates closed;
- [ ] full SQLite/MySQL/frontend/build checks green;
- [ ] migration upgrade rehearsal;
- [ ] browser E2E/UAT;
- [ ] source-to-GL reconciliation;
- [ ] operational deployment checks.

---

# Definition of Done

Vehicle Rental is complete only when:

1. the practical TACGL/video workflow is available without unnecessary UI complexity;
2. Customer and Owner commercial calculations are independently agreement/rate driven;
3. Running Chart physical evidence is immutable and same-side duplicate consumption is impossible;
4. physical Vehicle identity/source/use/replacement history is correct;
5. posted financial history is immutable and corrections are governed;
6. Customer/Supplier/Vehicle/HR/Vehicle Service/Invoice/Payment/Tax/Finance/Reporting ownership boundaries remain clean;
7. no legacy Rental code is restored as a dependency;
8. no hardcoded legacy account/rate/tax magic values are introduced;
9. every financially material formula used in production is authoritative, configured, and tested;
10. unresolved rules remain explicit rather than hidden in defaults;
11. default and MySQL/MariaDB backend suites, frontend tests/typecheck/lint/build, migrations, and E2E/UAT are green for the final head;
12. `docs/knowledgebase.md`, this TODO, and append-only change records accurately describe the shipped behavior.

Until these conditions are met, do not label the Vehicle Rental module “fully production-ready”.
