# Vehicle Rental clean rebuild — production TODO

**Status:** Authoritative implementation backlog for a fresh Vehicle Rental module  
**Business source of truth:** TACGL  
**Engineering source of truth:** latest `worktree-0.0.8` at rebuild start  
**Rebuild start commit:** `752a41d0c960b38a05adfb8781ed7d75c393a67a`  
**Old Rental implementation:** must not be restored, copied, revived, or used as an implementation dependency  
**Domain reference:** `docs/knowledgebase.md`

---

## 1. Goal and non-negotiable constraints

Build a new Vehicle Rental module from the current AutoERP architecture and the business meaning proven by TACGL. Preserve valid business behavior while rejecting TACGL's legacy structural defects.

Non-negotiable domain invariants:

- one physical vehicle has one stable `Vehicle` identity;
- registration formatting variants never create duplicate physical vehicles;
- Vehicle legal/party ownership history remains owned by the Vehicle module;
- Rental owns Rental-specific commercial supply/use relationships and does not overload `VehicleOwnership`;
- Lessee/Customer and Lessor/Supplier agreements are separate commercial contracts;
- one physical Running Chart is shared operational evidence;
- customer billing and owner settlement are independent calculations over the same eligible physical usage;
- customer rates/amounts never determine owner rates/amounts and vice versa;
- finalized physical facts and posted financial facts are immutable;
- correction uses explicit lineage/reversal rather than mutation or hard delete;
- same finalized source cannot be consumed twice on the same commercial side;
- processing one commercial side must not consume or block the other side;
- unresolved monetary/eligibility policy must fail closed rather than guess;
- ordinary users never type raw IDs or GL accounts;
- cross-module responsibilities remain with the owning module.

---

## 2. Evidence status and unresolved policy blockers

These are intentionally **not** implementation defaults until TACGL or approved business policy uniquely proves them.

### 2.1 Partial-period rules

- [ ] Confirm whether all future monthly agreements may use fixed-30-day proration. TACGL proves fixed-30-day examples but not a universal rule.
- [ ] Confirm first/last-day inclusion outside the observed fixed-30-day examples.
- [ ] Confirm minimum billable period.
- [ ] Confirm early return/extension commercial treatment beyond preserving exact effective periods.

Safe implementation until confirmed:

- full agreement-cycle monthly periods may calculate normally;
- `FIXED_30_DAY` may exist only as an explicit policy mode because TACGL directly evidences it;
- a partial period without an explicit supported policy must be rejected before money is calculated.

### 2.2 Included/excess kilometre rules

- [ ] Confirm unused-KM carry-forward.
- [ ] Confirm replacement-vehicle KM pooling.
- [ ] Confirm exact legacy `Normal / By Hire / By Log Transaction` algorithm semantics if those modes are required.
- [ ] Confirm KM rounding rule.
- [ ] Confirm commercial treatment of Garage Mileage. It must be stored separately and must not be silently subtracted.

### 2.3 Replacement/downtime

- [ ] Confirm customer charging during replacement.
- [ ] Confirm owner payable during replacement.
- [ ] Confirm customer downtime credit.
- [ ] Confirm owner downtime deduction.
- [ ] Confirm partial-day replacement treatment.

### 2.4 Driver/time rules

- [ ] Confirm universal working-hour window/classification rules.
- [ ] Confirm normal/double/triple OT qualification.
- [ ] Confirm weekend/holiday rules.
- [ ] Confirm OT rounding/minimum block.
- [ ] Confirm multi-driver split behavior.
- [ ] Confirm night-out qualification/counting rule.
- [ ] Confirm driver salary/recovery partial-period formula.

### 2.5 Fuel/repair/accident/insurance

- [ ] Confirm who is liable for fuel/repair under each agreement type.
- [ ] Confirm permitted markup/approval thresholds.
- [ ] Confirm accident/insurance-excess responsibility and recovery priority.

TACGL does prove the process shape for owner Fuel/Repair recovery: explicit debit note + evidence/reference + allocation. That process can be implemented without inventing liability rules.

### 2.6 Deposits/adjustments

- [ ] Confirm mandatory/optional deposit policy.
- [ ] Confirm due timing.
- [ ] Confirm application priority and partial application.
- [ ] Confirm refund timing/forfeiture.
- [ ] Confirm owner/customer advance handling.
- [ ] Confirm debit/credit-note approval thresholds.

### 2.7 Tax/accounting/governance

- [ ] Confirm exact VAT/SVAT/SSCL applicability by effective date/party/transaction.
- [ ] Confirm withholding applicability/order.
- [ ] Confirm tax/currency rounding and FX policy.
- [ ] Confirm maker-checker requirements.
- [ ] Confirm agreement activation/termination authority.
- [ ] Confirm Running Chart finalization authority.
- [ ] Confirm payment-preparation vs bank-reconciliation segregation requirements.

Tax/Finance modules remain authoritative owners for these policies.

---

## 3. P0 — module foundation and ownership boundaries

### 3.1 Module skeleton

- [ ] Create `app/Modules/VehicleRental` from scratch using current module conventions only.
- [ ] Add explicit module config.
- [ ] Add provider registration to `bootstrap/providers.php` only when the foundation is internally complete.
- [ ] Add route group under `/api/v1/vehicle-rental` with current tenant/org/auth middleware.
- [ ] Add `VehicleRentalPermission` definitions and permission registry registration.
- [ ] Add feature/entitlement wiring owned by Tenant/Core using the current feature system, not historical Rental feature code.
- [ ] Add frontend module namespace and route registration only after backend contracts are stable.

### 3.2 Explicit module ownership

Vehicle Rental owns:

- [ ] Rental agreement identity/versioning and Rental commercial terms;
- [ ] Rental vehicle supply coverage derived from Lessor commercial context;
- [ ] Lessee customer-use allocation/custody context;
- [ ] Daily/Replacement Running Chart physical Rental evidence;
- [ ] Rental calculation plans/results and same-side source-consumption identity;
- [ ] Rental-specific debit/credit adjustment source metadata;
- [ ] Rental operational statements/read models.

Vehicle Rental must **not** own:

- [ ] physical Vehicle master/registration/ownership history — Vehicle;
- [ ] customer master — Customer;
- [ ] lessor/supplier master — Supplier/party owner;
- [ ] employee/driver master — HR;
- [ ] invoice financial lifecycle — Invoice;
- [ ] receipts/payments/instruments/allocations — Payment;
- [ ] tax determination — Tax;
- [ ] GL/account posting — Finance;
- [ ] Workshop/service state — Vehicle Service;
- [ ] generic audit/event storage — Audit.

---

## 4. P1 — database schema and domain model

Use one table per migration, explicit portable Laravel migration APIs, tenant/org-safe foreign keys, indexes for every high-frequency eligibility/period lookup, row-versioning on mutable aggregates, and no speculative tables.

### 4.1 Agreements

- [ ] `vehicle_rental_lessee_agreements` — stable agreement header/identity, tenant/org, customer, contracted Vehicle, agreement number, lifecycle status, row version.
- [ ] `vehicle_rental_lessee_agreement_versions` — immutable effective commercial versions: execution/start/end, Monthly/Daily basis, with-driver, maximum/included KM, AC mode/rate matrix, driver/OT/night-out components, security-deposit commercial terms where evidenced, tax-context references/snapshots as owned integration data, proration policy when explicitly selected.
- [ ] `vehicle_rental_lessor_agreements` — stable agreement header/identity, tenant/org, supplier/Lessor, contracted Vehicle, agreement number, lifecycle status, row version.
- [ ] `vehicle_rental_lessor_agreement_versions` — immutable effective owner-side terms independently from Lessee terms.
- [ ] Enforce exactly one active/effective agreement version per side/agreement instant.
- [ ] Prevent in-place mutation of active historical terms.

### 4.2 Allocation/custody/supply

- [ ] `vehicle_rental_supply_coverages` — Rental-owned commercial vehicle source coverage tied to Lessor agreement/version and Vehicle.
- [ ] `vehicle_rental_allocations` — customer-use/custody period tied to Lessee agreement/version and Vehicle; optional source coverage reference for externally supplied vehicle.
- [ ] Model company-owned vehicles without fake external Lessor rows.
- [ ] Prevent overlapping blocking customer-use allocations atomically.
- [ ] Require external use to be fully covered by valid Lessor supply coverage.
- [ ] Preserve historical allocation periods; never overwrite prior custody.
- [ ] Add handover/return odometer fields only as structured custody facts; no free-text authority.

### 4.3 Running Chart

- [ ] `vehicle_rental_running_charts` — one physical usage record containing Vehicle, allocation, Lessee/Lessor context, actual start/end, mileage, times, driver, OT/night-out counts, garage mileage, remarks, lifecycle state, row version.
- [ ] `vehicle_rental_running_chart_corrections` or equivalent explicit supersession lineage without mutating finalized originals.
- [ ] Store calculated physical KM separately from commercial quantities.
- [ ] Keep Garage Mileage as a separate field.
- [ ] Persist continuity action/evidence when prior mileage/time is continued/reset.
- [ ] Enforce finalized immutability.

### 4.4 Replacement

- [ ] `vehicle_rental_replacements` — original allocation/Vehicle, replacement Vehicle, exact effective period, reason, status, row version.
- [ ] Running Chart records actual Vehicle used and links replacement context when applicable.
- [ ] Prevent contradictory overlapping original/replacement physical use.

### 4.5 Commercial calculations

- [ ] `vehicle_rental_calculations` — side (`customer`/`lessor`), agreement/version, period, state, fingerprint/idempotency key, totals, source snapshot metadata, posting reference, reversal state.
- [ ] `vehicle_rental_calculation_lines` — component type, quantity, unit, rate, amount, source rate identity, taxability metadata, explanatory text generated from structured facts.
- [ ] `vehicle_rental_calculation_sources` — Running Chart source identities and quantities consumed by one commercial side.
- [ ] Unique constraint preventing same finalized Running Chart/source from being consumed twice for the same side/effective calculation role.
- [ ] Customer and Lessor consumption must be independent dimensions.

### 4.6 Adjustment source metadata

- [ ] Add Rental-owned source entities for customer/lessor debit/credit adjustments only where Rental-specific evidence is required; financial document itself remains in owning finance/invoice/payment module.
- [ ] Model Fuel/Repair Lessor Debit Note evidence/reference without inventing liability calculation.

---

## 5. P2 — enums, value objects, state machines

- [ ] `RentalAgreementStatus`: Draft, Active, Closed/Terminated only as evidence-compatible minimum states.
- [ ] `RentalBillingBasis`: Monthly, Daily.
- [ ] `RentalCommercialSide`: Customer, Lessor.
- [ ] `RentalAcMode`: NonAC, FrontAC, DualAC where used by agreement/rates.
- [ ] `RentalRunningChartStatus`: Draft, Finalized, Corrected/Superseded semantics.
- [ ] `RentalCalculationStatus`: Draft/Calculated, Posted/Consumed, Reversed as required by owner integrations.
- [ ] `RentalProrationPolicy`: only evidence-supported modes; start with explicit `Fixed30Day` support and no silent default.
- [ ] `RentalChargeComponent`: base rental, excess KM, driver salary/recovery, normal/double/triple OT, night-out, parking/other, supported adjustment components.
- [ ] `RentalQuantityUnit`: day, hire, km, hour, night-out/count, amount where justified.
- [ ] Use named value objects for agreement effective period and calculation period where they reduce repeated validation.
- [ ] Do not encode business status with magic integers/strings.

---

## 6. P3 — agreement workflows

### 6.1 Lessee agreement

- [ ] Create draft.
- [ ] Update draft with optimistic version check.
- [ ] Validate customer/Vehicle scope.
- [ ] Validate agreement period.
- [ ] Validate Monthly/Daily basis fields.
- [ ] Validate max/included KM and rates as non-negative structured values.
- [ ] Activate only a complete version.
- [ ] Prevent overlapping contradictory active commercial coverage for the same agreement identity/Vehicle where invalid.
- [ ] Close/terminate without rewriting historical effective versions.
- [ ] Query/list/show human-readable customer + Vehicle + effective version.

### 6.2 Lessor agreement

- [ ] Same lifecycle, independently owned rates and validation.
- [ ] Validate supplier/Lessor identity rather than customer identity.
- [ ] Validate Vehicle relationship against current Vehicle ownership/source context without treating Vehicle ownership as the commercial agreement.
- [ ] Create/update source coverage when agreement is activated/changed through Rental-owned service.

### 6.3 Agreement concurrency

- [ ] Lock aggregate/version rows during transition.
- [ ] Reject stale `row_version` updates with explicit conflict response.
- [ ] Make activation and effective-version publication atomic.

---

## 7. P4 — allocation, custody, and shared availability

- [ ] API to find eligible Vehicles for requested period.
- [ ] Call `VehicleAvailabilityService` before creating/changing blocking Rental use.
- [ ] Register a Rental `VehicleAvailabilityBlockerInterface` implementation so active/planned Rental customer-use allocation blocks conflicting consumers.
- [ ] Rental blocker must ignore owner/supply coverage because supply entitlement is not physical use.
- [ ] Lock Vehicle + overlapping Rental allocation rows in deterministic order.
- [ ] Validate Lessee agreement effective coverage.
- [ ] Validate Lessor supply coverage for external Vehicle use.
- [ ] Support company-owned Vehicle without Lessor payable path.
- [ ] Record handover/return odometer when supplied.
- [ ] Complete/return allocation without destroying history.
- [ ] Expose clear blocker reasons to UI/API.

---

## 8. P5 — Daily Running Chart and replacement workflow

### 8.1 Daily Running Chart

- [ ] Create draft from active allocation.
- [ ] Auto-resolve current Lessee and applicable Lessor agreement versions for the physical use period.
- [ ] Capture start/end date/time, mileage, driver, OT/night-out, garage mileage and particulars.
- [ ] Validate end mileage >= start mileage.
- [ ] Validate date/time range and agreement/allocation coverage.
- [ ] Validate physical Vehicle/driver conflicts.
- [ ] Compare against adjacent usage for continuity.
- [ ] Support explicit continue/reset actions rather than silently overwriting discontinuity.
- [ ] Finalize atomically with row-version check.
- [ ] Block edits/deletes after finalization.
- [ ] Correct via explicit supersession/correction with reason/actor/time.

### 8.2 Replacement

- [ ] Create replacement with original/replacement Vehicle and exact period.
- [ ] Validate replacement Vehicle availability.
- [ ] Validate source coverage for replacement if externally supplied.
- [ ] Ensure exact non-conflicting physical timeline.
- [ ] Preserve original allocation/agreement history.
- [ ] Running Charts during replacement point to actual physical Vehicle.
- [ ] Commercial treatment requiring unproven replacement policy must fail closed.

---

## 9. P6 — commercial calculation engine

Build one calculation orchestration service with separate side-specific rate resolution. Shared physical fact extraction may be reused; commercial pricing must never be shared across sides.

### 9.1 Eligibility and source collection

- [ ] Select only finalized, effective, unconsumed Running Charts for requested side/period.
- [ ] Aggregate eligible physical facts within the applicable agreement cycle.
- [ ] Use agreement-cycle/anniversary monthly periods; never hardcode calendar-month boundaries.
- [ ] Snapshot exact agreement version/rates/sources used.
- [ ] Reject ambiguous Garage Mileage commercial treatment when material to charge calculation and no explicit approved policy exists.
- [ ] Reject unsupported replacement pooling/downtime formula where required.

### 9.2 Full-cycle monthly calculation

- [ ] Base rental uses effective side-specific agreement rate.
- [ ] Included/max KM evaluated within applicable agreement cycle.
- [ ] Excess quantity = policy-supported commercial KM beyond included entitlement.
- [ ] Excess line rate comes from the same side's agreement version.
- [ ] Driver/OT/night-out components require structured source quantities and evidenced agreement rates.
- [ ] Generate human-readable explanation from structured calculation facts.

### 9.3 Partial monthly calculation

- [ ] If exact full cycle: full monthly rate.
- [ ] If partial and policy = explicit `Fixed30Day`: calculate using evidence-supported fixed-30 method.
- [ ] If partial and no supported policy: fail closed with domain error.
- [ ] Persist policy identity in calculation snapshot.

### 9.4 Daily basis

- [ ] Calculate from structured days/hires only where Daily basis is selected/evidenced.
- [ ] Do not infer minimum-day/partial-day behavior without approved policy.

### 9.5 Exactly-once/idempotency

- [ ] Deterministic calculation fingerprint from side + agreement/version + period + source set.
- [ ] Database uniqueness for same-side source consumption.
- [ ] Retry returns/recognizes prior result rather than duplicating charges.
- [ ] Reversal restores source eligibility exactly once through Rental-owned restoration logic.

---

## 10. P7 — Invoice, Tax, Finance and owner-payable integration

### 10.1 Customer Invoice

- [ ] Add/restore active Rental invoice source support in the **Invoice module owner** only when Rental source implementation is ready.
- [ ] Do not add downstream exceptions while leaving `InvoiceType::Rental` marked retired.
- [ ] Rental creates customer calculation/source plan; Invoice owns invoice number, posting lifecycle, balance and reversal.
- [ ] Use `customer_rental_invoice` Finance posting profile and `rental_revenue` account role.
- [ ] Tax module resolves effective taxes; calculation stores immutable tax snapshot/reference.
- [ ] Posting must be atomic across source-consumption + invoice + required Finance consequences.
- [ ] Source restoration on invoice reversal must call Rental-owned restoration handler.

### 10.2 Owner/Lessor payable

- [ ] Determine current owning financial document abstraction for supplier payable; fix/add capability in its owner module if missing.
- [ ] Rental owns owner calculation/source only.
- [ ] Use `supplier_rental_invoice`/Rental expense posting vocabulary as appropriate to current Finance design.
- [ ] Payment uses open payable balance; Rental does not duplicate AP allocation logic.
- [ ] Reversal restores owner-side Running Chart eligibility exactly once.

### 10.3 No direct legacy expense shortcut

- [ ] Do not reproduce TACGL's free-text `RENTAL PAYMENT -> bank` path for ordinary settlement.
- [ ] Every ordinary owner payment must have structured Lessor/payable/source identity.

---

## 11. P8 — receipts, payments, deposits, debit/credit adjustments

### 11.1 Customer receipt

- [ ] Use Payment module to receive against customer Rental invoice.
- [ ] Support partial receipt and one receipt allocated across multiple invoices.
- [ ] Prevent over-allocation/concurrent duplicate allocation.
- [ ] Preserve unallocated customer balance/advance semantics in Payment owner.

### 11.2 Owner payment

- [ ] Use Payment module supplier payment path.
- [ ] Support partial/multi-document allocation where owner supports it.
- [ ] Preserve cheque/instrument lifecycle and bank reconciliation in Payment/Finance.

### 11.3 Security deposit

- [ ] Keep Rental deposit source semantics separate from invoice receipt/advance.
- [ ] Use Finance `rental_deposit` posting vocabulary where applicable.
- [ ] Until deposit application/refund/forfeiture policy is confirmed, allow only evidence-safe capture/status operations or fail closed for unsupported transitions.

### 11.4 Fuel/Repair Lessor Debit Note

- [ ] Rental source captures Lessor, Vehicle, Fuel/Repair reason, Fuel Chit/Invoice reference, evidence and amount.
- [ ] Financial adjustment document/allocation remains in owning financial module.
- [ ] Do not auto-decide liability/markup without approved policy.

---

## 12. P9 — API surface

All APIs tenant/org scoped, permission checked, human-readable related objects returned, no raw-ID-only UX contracts.

### Agreement APIs

- [ ] Lessee agreement index/lookup/show/create/update/activate/close.
- [ ] Lessor agreement index/lookup/show/create/update/activate/close.
- [ ] Effective-version/rate preview endpoint.

### Allocation APIs

- [ ] Eligible Vehicle lookup by requested period/agreement.
- [ ] allocation index/show/create/update/return/cancel where evidence-safe.
- [ ] availability/blocker explanation endpoint.

### Running Chart APIs

- [ ] index/show/create/update/finalize/correct.
- [ ] continuity preview/options endpoint.
- [ ] eligible-unbilled/unsettled source endpoints per side.

### Replacement APIs

- [ ] create/show/end/correct with explicit period.

### Calculation APIs

- [ ] customer preview/calculate/post.
- [ ] lessor preview/calculate/post.
- [ ] calculation source drill-down.
- [ ] domain errors for unresolved policy blockers.

### Adjustment/report APIs

- [ ] Fuel/Repair debit-note source CRUD/submit where supported.
- [ ] customer/lessor/vehicle/running-chart statement endpoints.
- [ ] source-to-document/payment/ledger traceability endpoint/read model.

---

## 13. P10 — UI/UX

UI must prioritize speed, clarity and guided relationships.

### Navigation

- [ ] Vehicle Rental top-level feature entry only when backend feature is enabled.
- [ ] Overview/dashboard queue rather than table-per-page navigation.

### Agreements

- [ ] Lessee Agreement list/detail/create/edit/activate/close.
- [ ] Lessor Agreement list/detail/create/edit/activate/close.
- [ ] Customer/Supplier/Vehicle selectors use search/autocomplete and meaningful labels.
- [ ] Show effective version/rates and agreement cycle clearly.
- [ ] Never expose raw GL IDs.

### Operations

- [ ] Allocation/custody workflow with period-aware eligible Vehicle selector.
- [ ] Daily Running Chart fast-entry screen mirroring proven operational facts without legacy clutter.
- [ ] Explicit mileage/time continuity choice when discontinuity exists.
- [ ] Replacement workflow contextual to current allocation.

### Commercial processing

- [ ] Customer Billing queue showing eligible finalized Running Charts and quantity × rate preview.
- [ ] Owner Settlement queue independently showing eligible sources and owner-side rates.
- [ ] Clear warning/blocker when a policy is unresolved rather than calculating a guess.
- [ ] Source drill-down from posted financial document back to Running Chart/agreement/rate snapshot.

### Financial follow-through

- [ ] Link to Invoice/Receipt/Payment records owned by their modules.
- [ ] Fuel/Repair Debit Note evidence entry and allocation status.
- [ ] Deposit status without inventing unsupported transitions.

### Accessibility/quality

- [ ] loading/empty/error/conflict states;
- [ ] keyboard-usable forms and selectors;
- [ ] responsive layout;
- [ ] user-visible validation mirrors backend rules but backend stays authoritative.

---

## 14. P11 — permissions and security

Suggested granular permissions, finalized using current permission naming conventions:

- [ ] agreements.view
- [ ] agreements.create
- [ ] agreements.update-draft
- [ ] agreements.activate
- [ ] agreements.close
- [ ] allocations.view
- [ ] allocations.manage
- [ ] running-charts.view
- [ ] running-charts.create
- [ ] running-charts.update-draft
- [ ] running-charts.finalize
- [ ] running-charts.correct
- [ ] replacements.manage
- [ ] customer-calculations.view/create/post
- [ ] lessor-calculations.view/create/post
- [ ] adjustments.view/manage
- [ ] reports.view

Security requirements:

- [ ] tenant/org scope every query/write;
- [ ] route-model binding cannot cross tenant/org;
- [ ] no mass-assignable guarded ownership/audit/system fields;
- [ ] permission check before business mutation;
- [ ] authorization enforced server-side;
- [ ] audit all business-significant transitions.

---

## 15. P12 — reporting and reconciliation

### Operational

- [ ] Lessee/Lessor Log Sheet.
- [ ] vehicle Running Chart statement.
- [ ] replacement by original Vehicle.
- [ ] replacement by replacement Vehicle.
- [ ] driver OT/night-out summary.
- [ ] mileage/garage-mileage continuity exceptions.

### Customer

- [ ] agreement listing/effective version report.
- [ ] invoice/source statement.
- [ ] customer/Vehicle Rental ledger views using authoritative finance sources.
- [ ] outstanding/aging links to Invoice/Finance owner.

### Lessor

- [ ] agreement listing.
- [ ] owner payable/payment statement.
- [ ] Vehicle-wise Lessor statement.
- [ ] Fuel/Repair deductions.
- [ ] unallocated payment/adjustment links.

### Integrity/reconciliation

- [ ] source-consumption reconciliation.
- [ ] calculation-to-financial-document reconciliation.
- [ ] financial-document-to-GL reconciliation delegated to Finance read model.
- [ ] report invalid states; do not use reports as normal repair mechanisms.

---

## 16. P13 — tests

### Unit/domain tests

- [ ] agreement period/rate validation.
- [ ] agreement-cycle calculation (`25 -> 24`, `18 -> 17`).
- [ ] explicit fixed-30 partial examples (`225,000 × 13/30 = 97,500`; `180,000 × 21/30 = 126,000`).
- [ ] structured excess examples including `1,172 × 90 = 105,480`.
- [ ] no customer-rate leakage into lessor calculation.
- [ ] no lessor-rate leakage into customer calculation.
- [ ] Garage Mileage remains separate and unsupported treatment fails closed.
- [ ] unsupported partial/replacement policy fails closed.

### Feature/API tests

- [ ] tenant/org isolation.
- [ ] permissions for every transition.
- [ ] stale row-version conflict.
- [ ] agreement activate/close immutability.
- [ ] allocation overlap rejection.
- [ ] Workshop blocker conflict.
- [ ] Running Chart draft/finalize/correct lifecycle.
- [ ] customer/lessor independent source eligibility.
- [ ] duplicate same-side consumption rejected.
- [ ] either side may process first.
- [ ] calculation posting/reversal restores source exactly once.
- [ ] receipt/payment allocation integration.
- [ ] Fuel/Repair evidence/adjustment flow.

### Concurrency tests against real MySQL

- [ ] two overlapping allocation requests;
- [ ] two Running Chart finalizers;
- [ ] duplicate customer calculation/post;
- [ ] duplicate owner calculation/post;
- [ ] posting vs reversal;
- [ ] allocation vs Workshop hold;
- [ ] rate-version change vs calculation;
- [ ] receipt/payment concurrent allocation where applicable.

### Frontend tests

- [ ] form validation and conflict handling;
- [ ] selectors show human-readable related data;
- [ ] Running Chart continuity UX;
- [ ] independent customer/owner queues;
- [ ] unresolved-policy blocker messaging;
- [ ] source drill-down.

### Regression

- [ ] existing Vehicle, Vehicle Service, Invoice, Payment, Finance, Tax and Tenant tests remain green.
- [ ] no existing feature gains a dependency on Vehicle Rental when feature disabled.

---

## 17. P14 — migration/import and legacy reconciliation

Fresh implementation must not depend on old Rental code/schema.

- [ ] Define an import/reconciliation mapping from TACGL business evidence to the new schema, separate from runtime domain code.
- [ ] Normalize registrations before Vehicle matching; never create duplicate Vehicle for punctuation differences.
- [ ] Map `OWN...` as Outside Work evidence only, never Lessor identity.
- [ ] Treat `LCH...` as broad service/labour source; identify Rental semantics from actual charge/business evidence.
- [ ] Never use `VEHTYP` alone as ownership/source truth.
- [ ] Parse narrative quantities only as migration evidence; require reconciliation because text may conflict with stored amount/date.
- [ ] Preserve original TACGL references for audit traceability.
- [ ] Reconcile imported invoice/receipt/GL lineage where needed; do not rewrite historical financial facts.
- [ ] Produce migration exception report for ambiguous records rather than guessing.

---

## 18. P15 — documentation and release readiness

- [ ] Keep `docs/knowledgebase.md` synchronized only when business understanding changes.
- [ ] Add module README describing ownership/contracts.
- [ ] Document APIs and permission matrix.
- [ ] Document state machines and reversal semantics.
- [ ] Document unresolved-policy configuration/blocking behavior.
- [ ] Add release checklist and rollback strategy.
- [ ] Add `/docs/changes` record for each meaningful implementation milestone.
- [ ] Verify migrations from clean database and upgrade path.
- [ ] Run PHP tests, frontend tests, lint/type checks, and MySQL concurrency suite without GitHub Actions.
- [ ] Verify no old Rental code/artifacts were copied or restored.
- [ ] Verify no TACGL legacy account code/rate/customer/payee value is hardcoded.

---

## 19. Definition of done

Vehicle Rental is production-ready only when all of the following are true:

- [ ] stable Vehicle identity and registration normalization are preserved;
- [ ] separate versioned Lessee/Lessor agreements exist;
- [ ] external supply coverage and customer-use custody are explicit and effective-dated;
- [ ] overlapping physical use is atomically prevented;
- [ ] Workshop/off-road availability blocks conflicting Rental use through Vehicle contract;
- [ ] one authoritative Running Chart can feed both commercial sides independently;
- [ ] finalized usage is immutable and correctable with lineage;
- [ ] customer and owner calculations use only their own effective rates;
- [ ] commercial quantities/rates/amounts are structured;
- [ ] agreement-cycle monthly billing works;
- [ ] partial-period logic never guesses an unapproved policy;
- [ ] Garage Mileage/replacement/downtime ambiguous policies do not silently alter money;
- [ ] same-side source consumption is exactly-once and retry-safe;
- [ ] Invoice/Payable/Payment/Tax/Finance ownership boundaries are respected;
- [ ] reversals restore source eligibility exactly once;
- [ ] receipts/payments cannot over-allocate;
- [ ] tenant/org isolation, permissions, auditing and concurrency controls are proven;
- [ ] user UI exposes human-readable business context, not database internals;
- [ ] source-to-document-to-payment-to-ledger traceability is readable;
- [ ] unresolved rules used by enabled workflows have explicit approved policy or fail closed;
- [ ] all relevant backend/frontend/regression/concurrency tests pass;
- [ ] `docs/knowledgebase.md`, module docs and `/docs/changes` reflect the final implementation.

---

## 20. Implementation order

Execute in this dependency order; do not skip forward by adding compatibility shims:

1. P0 module boundary + permissions/feature contract.
2. P1/P2 schema, models, enums, immutable/versioned foundations.
3. P3 agreements.
4. P4 allocation/custody + Vehicle availability integration.
5. P5 Running Chart + correction + replacement structure.
6. P6 calculation engine + exactly-once side consumption.
7. P7 Invoice/Payable/Tax/Finance integrations in owning modules.
8. P8 Payment/deposit/adjustment integrations.
9. P9 APIs completed alongside each backend slice.
10. P10 UI after backend contracts for each slice are stable.
11. P11 security throughout, not as a final patch.
12. P12 reporting/reconciliation.
13. P13 tests throughout, including MySQL race tests before release.
14. P14 controlled migration/reconciliation tooling if historical import is required.
15. P15 documentation/release verification.

No TODO may be marked complete merely because a screen or endpoint exists; its state, validation, permission, concurrency, reversal, audit and tests must also be complete.