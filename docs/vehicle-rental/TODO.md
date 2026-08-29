# Vehicle Rental clean rebuild — TACGL-grounded production TODO

**Status:** Authoritative implementation backlog for a fresh Vehicle Rental module  
**Business source of truth for this rebuild:** TACGL only  
**Engineering source of truth:** latest `worktree-0.0.8` at rebuild start  
**Rebuild start commit:** `752a41d0c960b38a05adfb8781ed7d75c393a67a`  
**Old Rental implementation:** must not be restored, copied, revived, or used as an implementation dependency  
**Existing `docs/knowledgebase.md`:** useful project context, but any rule used by this rebuild must be independently supported by TACGL because the current instruction makes TACGL the single business source of truth.

---

## 1. Audit conclusion that constrains the implementation

TACGL directly proves a real Vehicle Rental economic domain, but it does **not** expose a clean standalone Rental schema. Rental business is embedded in workshop/job/financial structures and free-text transaction details.

TACGL directly proves:

- recurring vehicle hiring/rental charges;
- monthly customer rental charges;
- self-drive monthly rental;
- with-driver monthly rental;
- with-driver van hire;
- explicit excess-kilometre charge quantities/rates/amounts;
- explicit driver overtime quantities/rates/amounts;
- driver BATA/allowance as a charge/cost concept;
- one-off/third-party car/Jeep hire costs;
- stable physical Vehicle identity being corrupted by registration-format duplicates;
- customer invoice -> debtor/subledger -> receipt allocation -> GL traceability;
- owner/source Rental Payment -> bank/GL activity;
- one payment voucher containing multiple vehicle-specific rental-payment lines;
- non-calendar recurring monthly cycles such as `25 -> 24` and `18 -> 17`;
- fixed-30-day partial-month arithmetic precedents;
- deleted/re-entered/corrected transactions;
- zero master rates for Rental charge categories, proving actual rates are transaction/contract-context specific rather than global charge-master defaults;
- data-quality failures where free-text formula/date can disagree with stored amount or be impossible.

TACGL does **not** directly prove, in the supplied corpus:

- a formal `Running Chart` entity/table;
- formal Lessee/Lessor Agreement tables or their exact fields/states;
- AC-mode pricing matrices;
- Garage Mileage rules;
- replacement-vehicle workflow/rules;
- security-deposit workflow;
- maker-checker approval requirements;
- reservation workflow;
- exact included/free-KM entitlement rules;
- automatic excess-KM derivation algorithm;
- exact driver working-hours / normal-double-triple OT classification;
- exact tax policy beyond the fact that legacy Rental-related invoices can contain tax fields and inspected examples are zero-tax;
- owner fuel/repair liability formula;
- universal future proration policy.

Therefore the clean module must model **only TACGL-proven business facts plus integrity structures strictly necessary to represent them safely**. Anything else remains an explicit policy/feature blocker.

---

## 2. Non-negotiable target invariants derived from TACGL

- [ ] One physical vehicle has one stable `Vehicle` identity.
- [ ] Registration punctuation/spacing differences do not create a second Vehicle.
- [ ] Vehicle ownership/legal party history remains owned by the Vehicle module.
- [ ] Rental does not use `VEHTYP` as ownership/source truth; all inspected active `scfveh` rows use `VEHTYP=03`, so the field is not reliable.
- [ ] Customer Rental revenue and owner/source Rental cost are separate commercial facts.
- [ ] Customer amount/rate never calculates owner/source amount/rate and vice versa.
- [ ] Actual Rental rates are effective contract/transaction context, not `scfchr` master rate; Rental charge master examples all carry zero master rate.
- [ ] Every billable/payable quantity, rate, period and amount is structured; narrative text is explanatory only.
- [ ] Posted financial history is immutable; correction is reversal/adjustment, not delete/edit.
- [ ] Same source fact cannot be financially consumed twice in the same commercial direction.
- [ ] All writes are tenant/org scoped, version checked, atomic and conflict-aware.
- [ ] Rental uses Invoice/Payment/Tax/Finance through their owning contracts rather than duplicating AR/AP/payment/GL logic.
- [ ] Ordinary users never type raw foreign keys or GL account IDs.
- [ ] Ambiguous monetary rules fail closed rather than guess.

---

## 3. TACGL evidence ledger to preserve as regression tests

### 3.1 Customer recurring rental examples

- [ ] With-driver monthly `CBD 3677`: 307,500 for `01/06/2025 -> 30/06/2025` and recurring later months.
- [ ] With-driver monthly `CBJ 6594`: 250,000.
- [ ] With-driver monthly `CAF 6512`: 185,000.
- [ ] Self-drive monthly `CAD 1608`: 80,000 for `25/06/2025 -> 24/07/2025` and `25/07/2025 -> 24/08/2025`.
- [ ] Self-drive monthly `CBM-9887`: 225,000 for `18/06/2025 -> 17/07/2025`, `18/07/2025 -> 17/08/2025`, and `18/08/2025 -> 17/09/2025`.
- [ ] Self-drive monthly `CAQ 7638`: 222,222.22 examples.

### 3.2 Excess-KM examples

- [ ] `1,172 × 90 = 105,480`.
- [ ] `1,165 × 65 = 75,725`.
- [ ] `1,082 × 90 = 97,380`.
- [ ] `1,962 × 75 = 147,150`.
- [ ] `635 × 65 = 41,275`.
- [ ] `1,845 × 90 = 166,050`.
- [ ] `483 × 75 = 36,225`.
- [ ] `2,135 × 65 = 138,775`.
- [ ] `1,352 × 90 = 121,680`.
- [ ] `3,356 × 75 = 251,700`.

Data-quality regression:

- [ ] Legacy text `1,080KM × 90` with stored amount 81,000 must never be trusted as authoritative arithmetic.

### 3.3 Partial-period precedent

- [ ] Full recurring `CBM-9887` monthly amount 225,000.
- [ ] Partial `18/09/2025 -> 30/09/2025` = 97,500.
- [ ] Reproduce `225,000 × 13 / 30 = 97,500` when explicit policy is `FIXED_30_DAY`.
- [ ] Owner/source precedent: recurring 180,000 context with `RENTAL PAYMENT 21DAYS` = 126,000 (`180,000 × 21 / 30`).
- [ ] Do **not** make `/30` a silent universal default.

### 3.4 One-off/third-party hire examples

- [ ] Jeep with driver `35,000 × 3 days = 105,000`.
- [ ] Driver OT `24.30 hrs × 500 = 12,250`.
- [ ] Driver BATA 2,000.
- [ ] Corrected excess `544KM × 300 = 163,200`; deleted legacy 163,000 must remain evidence of correction history.
- [ ] Self-drive daily `14 × 8,000 = 112,000` where present in TACGL narrative/transaction evidence.

### 3.5 Financial traceability examples

- [ ] `LCH2005407 -> RMS2005443 -> INV2005519 -> debtor -> REC2003089 allocation -> GL` remains reproducible as migration/reconciliation evidence.
- [ ] `REC2003089` demonstrates one receipt allocated across multiple invoices.
- [ ] `7048-000 RENTAL PAYMENT` contains 25 positive debit rows across 21 PRB vouchers totaling 3,396,309 in this TACGL corpus.
- [ ] `PRB1000970` demonstrates one payment voucher with multiple vehicle lines.

---

## 4. Unproven/ambiguous rules — explicit blockers

No implementation may turn these into hidden defaults.

### 4.1 Contract/agreement semantics

TACGL recurring transactions strongly imply stable commercial terms, but the supplied corpus does not expose formal Rental Agreement tables.

- [ ] Confirm formal contract terminology and required legal fields.
- [ ] Confirm whether one contract is always tied to one physical Vehicle.
- [ ] Confirm activation/termination workflow and authority.
- [ ] Confirm whether contract versions can overlap or future-date.

Target design may use versioned `CustomerRentalContract` and `SupplierRentalContract` as the clean source of recurring terms because TACGL proves repeated effective rates/periods but lacks a safe structured rate authority. This is a **derived integrity structure**, not a claim that TACGL had those tables.

### 4.2 Included/free kilometres

TACGL proves explicit excess-KM quantities and rates but does not expose the entitlement calculation that produced them.

- [ ] Confirm included-KM entitlement values and reset period.
- [ ] Confirm whether excess is derived from total KM, manually measured, or imported from another operational source.
- [ ] Confirm unused entitlement carry-forward.
- [ ] Confirm KM rounding.

Safe implementation until confirmed:

- store `excess_km` as a structured source quantity when explicitly supplied/evidenced;
- multiply only by the contract's explicit excess-KM rate;
- do not auto-derive excess from total KM without an approved entitlement policy.

### 4.3 Operational usage source

TACGL financial/job data proves periods, KM quantities, days/hires and OT quantities but does not expose a formal Running Chart table.

- [ ] Confirm whether business requires a Daily Running Chart, trip log, odometer log, or another operational source system.
- [ ] Confirm odometer continuity rules.
- [ ] Confirm driver assignment lifecycle.
- [ ] Confirm Garage Mileage semantics.
- [ ] Confirm replacement/downtime workflow.

Target implementation must use a neutral `RentalUsageRecord` source abstraction for structured period/KM/day/OT facts. Do not call it a legacy Running Chart unless separate TACGL evidence proves that terminology.

### 4.4 Partial-period policy

- [ ] Confirm universal future proration policy.
- [ ] Confirm first/last-day inclusion outside observed examples.
- [ ] Confirm minimum billable period.
- [ ] Confirm early-return/extension policy.

Safe implementation:

- `FIXED_30_DAY` is supported only as an explicitly selected policy because TACGL directly evidences it;
- unsupported partial-period calculation fails closed.

### 4.5 Driver/time

- [ ] Confirm driver salary model.
- [ ] Confirm normal/double/triple OT classification.
- [ ] Confirm weekend/holiday treatment.
- [ ] Confirm OT rounding/minimum blocks.
- [ ] Confirm night-out rules.

Safe implementation:

- TACGL-proven explicit OT quantity × explicit rate may be calculated;
- `DRIVER BATA` may be recorded as an explicit amount component;
- do not automatically classify elapsed time into OT categories.

### 4.6 Owner/source settlement

TACGL proves payee/vehicle-specific Rental Payment and one-off hire cost, but regular Rental Payment is often direct GL/bank with free-text payee/vehicle.

- [ ] Confirm formal owner/supplier contract fields.
- [ ] Confirm whether owner payable is self-billed or supplier-invoiced.
- [ ] Confirm withholding/tax treatment.
- [ ] Confirm deduction rules.

Safe target:

- require a structured Supplier/Lessor identity;
- create a structured Rental payable source before Payment;
- never reproduce free-text direct-expense payment as ordinary target flow.

### 4.7 Deposit, fuel/repair, replacement, AC modes, reservation

The supplied TACGL corpus is insufficient to establish these as required core Rental behavior.

- [ ] Security deposit workflow — unproven.
- [ ] Owner fuel/repair deduction workflow/formula — unproven in TACGL-only source.
- [ ] Replacement Vehicle charging/downtime — unproven.
- [ ] AC-mode rate matrix — unproven.
- [ ] Reservation/pre-booking — unproven.
- [ ] Condition photos/fuel-level/customer signature — unproven.

Do not implement speculative workflows merely because they may exist in another system or prior project artifact.

---

## 5. P0 — fresh module boundary and feature activation

- [ ] Create `app/Modules/VehicleRental` from scratch using only current module conventions.
- [ ] No code copied from any old/retired Rental branch.
- [ ] Add module config with only evidenced/configurable values.
- [ ] Add `VehicleRentalPermission` constants/descriptions.
- [ ] Add provider and `/api/v1/vehicle-rental` routes only after internal backend foundation is coherent.
- [ ] Add `TenantFeature::VEHICLE_RENTAL` in Core.
- [ ] Introduce Tenant plan schema **v4** that explicitly supports the rebuilt module while continuing to retire legacy persisted Rental entries from older schemas.
- [ ] Do not mutate historical schema-v1/v2 retirement semantics.
- [ ] Add feature dependency validation if Vehicle Rental requires Vehicle, Customer, Supplier, Invoice, Payment, Finance, Tax and Reporting to be enabled.
- [ ] Add frontend navigation only when backend feature is enabled.

---

## 6. P1 — clean domain schema

Use one table per migration. Every mutable aggregate gets `row_version`. Every relationship is tenant-safe. Every high-risk period lookup is indexed. No polymorphic relationship where explicit FKs are clearer.

### 6.1 Customer Rental contract

Derived structure required to safely represent recurring TACGL customer terms.

- [ ] `vehicle_rental_customer_contracts`
  - tenant/org;
  - contract number/reference generated by AutoERP;
  - customer;
  - contracted Vehicle;
  - status;
  - row version;
  - created/activated/closed audit fields.
- [ ] `vehicle_rental_customer_contract_versions`
  - immutable effective start/end;
  - billing basis: monthly/daily/hire/km/explicit amount only where TACGL evidence supports the basis;
  - base rental rate;
  - explicit excess-KM rate;
  - explicit OT rate where used;
  - driver BATA/default explicit allowance only if business confirms contract-level default;
  - proration policy nullable/explicit;
  - notes/source-reference metadata;
  - no AC/security-deposit/garage/replacement fields without new TACGL evidence.

### 6.2 Supplier/Lessor Rental contract

Derived structure required to replace free-text owner/source Rental Payment with structured cost authority.

- [ ] `vehicle_rental_supplier_contracts`
  - tenant/org;
  - Supplier/Lessor;
  - contracted Vehicle;
  - status/row version.
- [ ] `vehicle_rental_supplier_contract_versions`
  - effective period;
  - independent rates from customer contract;
  - supported basis/components;
  - explicit proration policy where needed.
- [ ] Never derive supplier amount from customer price or margin percentage.

### 6.3 Rental usage/source facts

- [ ] `vehicle_rental_usage_records`
  - tenant/org;
  - Vehicle;
  - customer contract/version;
  - supplier contract/version nullable for company-owned/internal-source cases;
  - exact service/rental period;
  - explicit days/hires quantity where known;
  - explicit total KM when known;
  - explicit excess KM when known;
  - explicit driver OT hours when known;
  - explicit driver BATA/other source amount when known;
  - source reference/evidence note;
  - status `draft/finalized/superseded` as integrity states, not claimed TACGL labels;
  - row version;
  - supersession lineage.
- [ ] Finalized usage/source facts immutable.
- [ ] Narrative description generated from structured facts, never the authority.

### 6.4 Customer calculation

- [ ] `vehicle_rental_customer_calculations` or a side-safe unified calculation table with explicit customer/supplier side and enforceable FKs.
- [ ] Persist exact contract/version, period, component quantities/rates/amounts, policy identity and source fingerprint.
- [ ] `vehicle_rental_customer_calculation_lines` structured by component.
- [ ] `vehicle_rental_customer_calculation_sources` with exactly-once active consumption guard.

### 6.5 Supplier calculation/payable source

- [ ] Separate supplier-side calculation aggregate or side-safe shared aggregate with independent contract FK.
- [ ] Supplier source consumption independent from customer source consumption.
- [ ] Payment/Finance posting link stored as external-owner reference, not duplicated financial state.

### 6.6 One-off/third-party hire

- [ ] Model one-off external hire as Rental-owned supplier source with Vehicle/period/component facts.
- [ ] Support explicit per-day, per-KM, OT and allowance components demonstrated by TACGL.
- [ ] Do not implement it as Vehicle Service `Outside Work` even though TACGL used `OWN...` workshop structures.

### 6.7 Corrections/reversals

- [ ] No hard delete for finalized source/calculation.
- [ ] Supersession/reversal references original record.
- [ ] Original TACGL/import source reference remains queryable.
- [ ] Reversal restores source eligibility exactly once.

---

## 7. P2 — enums/value objects

Only values supported by TACGL or required as integrity states.

- [ ] `RentalContractStatus`: Draft, Active, Closed/Terminated as minimal target lifecycle; document that this is a target integrity state, not a TACGL code mapping.
- [ ] `RentalBillingBasis`: Monthly, Daily, PerHire, PerKilometre, ExplicitAmount only where used by source evidence.
- [ ] `RentalUsageStatus`: Draft, Finalized, Superseded.
- [ ] `RentalCalculationStatus`: Calculated, Posted, Reversed.
- [ ] `RentalProrationPolicy`: `Fixed30Day` only initially; nullable means partial-period calculation unsupported.
- [ ] `RentalChargeComponent`: BaseRental, ExcessKilometre, DriverOvertime, DriverBata, OtherExplicitHireCharge.
- [ ] `RentalQuantityUnit`: Month, Day, Hire, Kilometre, Hour, Count, Amount.
- [ ] `RentalCommercialSide`: Customer, Supplier if a shared calculation/source-consumption abstraction is used.
- [ ] No AC/garage/replacement/deposit enums without TACGL evidence.

---

## 8. P3 — contract workflows

### Customer contract

- [ ] create draft;
- [ ] update draft with expected row version;
- [ ] validate tenant/org/customer/Vehicle;
- [ ] add immutable effective version;
- [ ] validate supported billing basis and non-negative rates;
- [ ] activate complete version atomically;
- [ ] close/terminate without rewriting historical versions;
- [ ] human-readable lookup/list/show.

### Supplier contract

- [ ] same lifecycle with Supplier/Lessor identity;
- [ ] validate Vehicle relationship without overloading `VehicleOwnership` as the contract;
- [ ] rates remain independent from customer contract;
- [ ] no free-text payee as authoritative identity.

### Contract concurrency

- [ ] deterministic lock order;
- [ ] expected-version conflict response;
- [ ] effective-version overlap validation;
- [ ] activation/version publish atomic.

---

## 9. P4 — usage/source workflow

- [ ] create usage record from selected active customer contract/version;
- [ ] optionally link effective supplier contract/version;
- [ ] capture exact period and only explicit physical/commercial source quantities actually known;
- [ ] validate period inside effective contract version(s);
- [ ] validate Vehicle matches selected contracts;
- [ ] finalize with row-version lock;
- [ ] block edit/delete after finalization;
- [ ] correct via superseding source with reason/actor/time;
- [ ] preserve all prior structured values;
- [ ] if business wants automatic odometer/Running-Chart logic later, add it only after new TACGL evidence/policy confirmation.

---

## 10. P5 — calculation engine

### 10.1 General

- [ ] Customer and Supplier pricing are separate services/rate resolvers.
- [ ] Calculation reads only finalized eligible usage facts.
- [ ] Contract/version/rates/period/source fingerprint frozen in result.
- [ ] Same finalized usage source cannot be customer-consumed twice.
- [ ] Same finalized usage source cannot be supplier-consumed twice.
- [ ] Customer consumption does not consume supplier eligibility.
- [ ] Retry/double-click idempotent.

### 10.2 Base monthly rental

- [ ] Full explicit contract cycle uses full side-specific monthly rate.
- [ ] Contract period boundaries may be non-calendar (`25->24`, `18->17`).
- [ ] Do not assume calendar-month billing.

### 10.3 Partial monthly rental

- [ ] If explicit policy = `Fixed30Day`, use inclusive billable-day count matching TACGL precedents and `/30`.
- [ ] Persist policy identity.
- [ ] If partial and no supported explicit policy, reject calculation.

### 10.4 Daily/per-hire/per-KM

- [ ] Use explicit structured quantity × explicit contract rate only.
- [ ] Do not infer minimum-day, partial-day or rounding rules.

### 10.5 Excess KM

- [ ] Use explicit structured `excess_km × excess_rate`.
- [ ] Do not auto-derive excess from total KM until included-KM entitlement policy is confirmed.
- [ ] Validate non-negative quantity/rate.
- [ ] Generate narrative from structured values.

### 10.6 Driver OT/BATA

- [ ] OT = explicit hours × explicit rate.
- [ ] BATA = explicit amount or explicit count × explicit rate only if contract/policy proves the rate unit.
- [ ] No automatic OT classification from elapsed time.

### 10.7 Tax

- [ ] Rental engine produces pre-tax structured commercial amount.
- [ ] Tax module determines applicable tax using effective tax policy.
- [ ] Zero-tax historical examples do not create a global zero-tax default.

---

## 11. P6 — current AutoERP integrations

### Vehicle

- [ ] Reuse stable Vehicle identity and registration normalization.
- [ ] Reuse `VehicleOwnership` only as legal/party ownership history where relevant.
- [ ] Do not put Rental contract/rate logic in Vehicle.
- [ ] Do not register a Rental availability blocker until TACGL proves a blocking operational allocation/reservation concept.

### Customer / Supplier

- [ ] Customer contracts reference Customer.
- [ ] Supplier contracts/reference payables use Supplier/Lessor identity.
- [ ] No duplicate Rental party master.

### Invoice

- [ ] Reactivate `InvoiceType::Rental` only when customer Rental source posting is actually ready.
- [ ] Fix retired-source semantics inside Invoice owner rather than adding downstream Rental exceptions.
- [ ] Rental source owns calculation; Invoice owns financial document lifecycle/balance/reversal.
- [ ] Add Rental source restoration handler for invoice reversal.

### Finance

- [ ] Use current `customer_rental_invoice`, `supplier_rental_invoice`, `rental_revenue`, `rental_expense`, and related configured posting vocabulary.
- [ ] Never copy TACGL Workshop sales account placement.
- [ ] Never hardcode `7048-000` as target account.

### Payment

- [ ] Customer receipt allocation uses Payment owner.
- [ ] Supplier Rental payment uses Payment owner.
- [ ] Support partial/multi-document allocation only through existing Payment semantics.
- [ ] No over-allocation/concurrent double consumption.

### Tax

- [ ] Effective determination/snapshot owned by Tax.

### Reporting/Audit/Idempotency

- [ ] Rental publishes/queryable source links; cross-domain reports derive from authoritative owners.
- [ ] Audit meaningful transitions.
- [ ] Use current idempotency facility for mutation endpoints where appropriate.

---

## 12. P7 — API surface

All endpoints under `/api/v1/vehicle-rental`, tenant/org scoped and permission checked.

### Contracts

- [ ] customer contracts: index/lookup/show/create/update/activate/close;
- [ ] supplier contracts: index/lookup/show/create/update/activate/close;
- [ ] effective-version/rate preview.

### Usage/source

- [ ] index/show/create/update-draft/finalize/correct;
- [ ] eligible unbilled customer sources;
- [ ] eligible unsettled supplier sources.

### Calculations

- [ ] customer preview/calculate/post-to-Invoice;
- [ ] supplier preview/calculate/create-payable-source;
- [ ] calculation/source drill-down;
- [ ] reversal/source-restoration endpoint only through owning document reversal flow.

### One-off hire

- [ ] create supplier one-off hire source with explicit Vehicle/period/component quantities;
- [ ] link customer recovery calculation separately when applicable.

### Reports/read models

- [ ] customer/Vehicle rental charge statement;
- [ ] supplier/Vehicle rental payment statement;
- [ ] source -> financial document -> receipt/payment -> GL traceability.

---

## 13. P8 — UI/UX

Do not reproduce TACGL screens/table structure. UI is task-oriented and human-readable.

### Navigation

- [ ] Vehicle Rental overview/queues only when tenant feature enabled.

### Customer contracts

- [ ] list/detail/create/edit/activate/close;
- [ ] customer/Vehicle autocomplete selectors;
- [ ] visible effective period, basis, base rate, excess rate, OT rate;
- [ ] proration policy shown only when relevant;
- [ ] no raw IDs/GL codes.

### Supplier contracts

- [ ] equivalent Supplier/Vehicle workflow with independent rates.

### Usage/source entry

- [ ] fast source-entry form: period, Vehicle, explicit days/hires/KM/excess-KM/OT/BATA/other amount as applicable;
- [ ] dynamic fields by billing/component basis;
- [ ] show source evidence/reference;
- [ ] finalize/correct with explicit impact warning.

### Customer billing queue

- [ ] eligible finalized customer sources;
- [ ] quantity × rate preview;
- [ ] clear partial-period-policy blocker;
- [ ] post/link to Invoice.

### Supplier settlement queue

- [ ] independent supplier-side eligible sources/rates;
- [ ] link to payable/payment owner.

### Traceability

- [ ] drill from Rental source to customer invoice/receipt/GL;
- [ ] drill from supplier Rental source to payable/payment/GL.

### UX quality

- [ ] loading/empty/error/conflict states;
- [ ] responsive/keyboard usable;
- [ ] backend authoritative validation mirrored only for immediate feedback.

---

## 14. P9 — permissions/security

Initial permission set using current naming conventions:

- [ ] `vehicle_rental.customer_contracts.view`
- [ ] `vehicle_rental.customer_contracts.manage`
- [ ] `vehicle_rental.customer_contracts.activate`
- [ ] `vehicle_rental.supplier_contracts.view`
- [ ] `vehicle_rental.supplier_contracts.manage`
- [ ] `vehicle_rental.supplier_contracts.activate`
- [ ] `vehicle_rental.usage.view`
- [ ] `vehicle_rental.usage.manage`
- [ ] `vehicle_rental.usage.finalize`
- [ ] `vehicle_rental.usage.correct`
- [ ] `vehicle_rental.customer_calculations.view`
- [ ] `vehicle_rental.customer_calculations.create`
- [ ] `vehicle_rental.customer_calculations.post`
- [ ] `vehicle_rental.supplier_calculations.view`
- [ ] `vehicle_rental.supplier_calculations.create`
- [ ] `vehicle_rental.supplier_calculations.post`
- [ ] `vehicle_rental.reports.view`

Security gates:

- [ ] tenant/org context on every read/write;
- [ ] route-model binding scoped to active tenant/org;
- [ ] guarded system/audit fields;
- [ ] server-side permission before mutation;
- [ ] no cross-tenant relation injection;
- [ ] audit all activation/finalization/post/reversal/correction transitions.

---

## 15. P10 — concurrency/idempotency

- [ ] concurrent contract version activation;
- [ ] concurrent source finalization/correction;
- [ ] two customer billing requests for same source;
- [ ] two supplier settlement requests for same source;
- [ ] customer and supplier calculations may proceed independently;
- [ ] post vs reversal race;
- [ ] payment allocation races delegated/tested with Payment owner;
- [ ] deterministic lock order;
- [ ] DB uniqueness/active-guard constraints where needed;
- [ ] explicit 409-style stale/conflict responses.

---

## 16. P11 — reporting/reconciliation

TACGL proves reporting/accounting traceability more strongly than operational trip workflow.

- [ ] customer Rental charges by customer/Vehicle/period/component;
- [ ] excess-KM charges by Vehicle/period/rate;
- [ ] supplier Rental payments by Supplier/Vehicle/period;
- [ ] one-off hire cost/recovery profitability;
- [ ] customer invoice/receipt outstanding links;
- [ ] supplier payable/payment outstanding links;
- [ ] calculation-to-Invoice/Payable reconciliation;
- [ ] financial document-to-GL reconciliation through Finance;
- [ ] migration exceptions for malformed narrative/date/formula data;
- [ ] source-consumption duplicate/integrity report.

Reports verify authoritative state; they do not become repair tools for avoidable invalid writes.

---

## 17. P12 — automated tests

### Domain/unit

- [ ] recurring monthly period calculation for calendar and non-calendar cycles;
- [ ] fixed-30 explicit policy examples;
- [ ] daily/per-hire/per-KM explicit quantity × rate;
- [ ] excess-KM regression examples;
- [ ] customer/supplier rate isolation;
- [ ] partial period without policy fails;
- [ ] automatic excess derivation without entitlement policy fails;
- [ ] free-text narrative cannot override structured amount/quantity/date.

### API/feature

- [ ] tenant/org isolation;
- [ ] permissions;
- [ ] row-version conflicts;
- [ ] immutable active versions;
- [ ] finalized source immutability/correction lineage;
- [ ] customer exactly-once consumption;
- [ ] supplier exactly-once consumption;
- [ ] side independence;
- [ ] Invoice reversal restores customer source exactly once;
- [ ] supplier payable reversal restores supplier source exactly once;
- [ ] Payment allocation integration.

### Real MySQL concurrency

- [ ] duplicate contract activation;
- [ ] duplicate customer calculation/post;
- [ ] duplicate supplier calculation/post;
- [ ] finalization vs correction;
- [ ] post vs reversal.

### Frontend

- [ ] controlled selectors;
- [ ] dynamic source-entry fields;
- [ ] policy-blocker UX;
- [ ] independent customer/supplier queues;
- [ ] source traceability.

### Regression

- [ ] Vehicle/Customer/Supplier/Invoice/Payment/Finance/Tax/Reporting existing tests remain green;
- [ ] Vehicle Rental disabled tenant sees no routes/navigation capability;
- [ ] no old Rental implementation becomes a dependency.

---

## 18. P13 — TACGL migration/reconciliation tooling

Runtime module must not depend on TACGL schemas or prefixes.

- [ ] Build separate import/reconciliation command/tooling.
- [ ] Normalize Vehicle registrations before matching.
- [ ] Never use `VEHTYP` alone as ownership/source classification.
- [ ] `OWN...` means Outside Work transaction family in the inspected TACGL corpus, not owner identity.
- [ ] `LCH...` is broad labour/service charge family, not a Rental-specific identifier.
- [ ] Identify Rental rows from charge/business descriptions + context, not prefix alone.
- [ ] Import structured quantity/rate only when confidently extractable/reconciled.
- [ ] Flag `1,080 × 90` vs 81,000-type mismatches for manual resolution.
- [ ] Reject/improve impossible dates such as `31/09/2025` through exception reporting; do not silently normalize.
- [ ] Preserve original TACGL reference/job/invoice/voucher IDs as source references.
- [ ] Preserve posted historical financial truth; import/reconcile rather than rewrite.

---

## 19. P14 — documentation/release readiness

- [ ] Add `app/Modules/VehicleRental/README.md` with explicit ownership boundaries and TACGL evidence scope.
- [ ] Document APIs/permissions/state transitions.
- [ ] Document every unsupported/unproven policy and fail-closed error.
- [ ] Update `docs/knowledgebase.md` only where the TACGL-only rebuild changes canonical understanding.
- [ ] Add append-only `/docs/changes` record for each meaningful milestone.
- [ ] Verify fresh migrations and upgrade migrations.
- [ ] Run backend tests, MySQL concurrency suite, frontend tests, lint/type/build locally without GitHub Actions.
- [ ] Verify no old Rental file/code was restored or copied.
- [ ] Verify no TACGL rate/account/payee/customer value is hardcoded as a target default.

---

## 20. Production definition of done

The rebuilt module is complete only when:

- [ ] every enabled workflow is traceable to TACGL evidence or an explicit approved policy;
- [ ] stable Vehicle identity is preserved;
- [ ] customer and supplier recurring terms are structured/versioned independently;
- [ ] structured usage/source facts replace free-text arithmetic as authority;
- [ ] customer and supplier calculations are independent and exactly-once per side;
- [ ] non-calendar monthly cycles work;
- [ ] fixed-30 proration is explicit, never hidden default;
- [ ] unsupported included-KM/OT/replacement/deposit/etc. rules fail closed;
- [ ] Invoice/Payment/Tax/Finance own their financial responsibilities;
- [ ] posted/finalized history is immutable and reversible with lineage;
- [ ] tenant/org/permission/concurrency/idempotency controls are proven;
- [ ] UI uses human-readable controlled relationships;
- [ ] TACGL regression examples pass;
- [ ] cross-module regression tests pass;
- [ ] no old Rental implementation dependency exists;
- [ ] docs and append-only change history match the final code.

---

## 21. Implementation order

1. **Evidence gate:** keep this TACGL-only rule register authoritative while coding.
2. **P0:** clean module + feature/permission contract.
3. **P1/P2:** contract/version, usage/source, calculation schema + enums.
4. **P3:** Customer/Supplier contract workflows.
5. **P4:** structured usage/source workflow.
6. **P5:** calculation engine with TACGL regression examples and fail-closed unresolved rules.
7. **P6:** Invoice/Supplier-payable/Payment/Tax/Finance integrations in owning modules.
8. **P7:** complete API surface alongside each backend slice.
9. **P8:** UI after corresponding backend contracts are stable.
10. **P9/P10:** permissions/security/concurrency throughout, never as patches.
11. **P11:** reporting/reconciliation.
12. **P12:** tests continuously; MySQL races before release.
13. **P13:** optional TACGL migration/reconciliation tooling if historical import is required.
14. **P14:** docs/release verification.

Do not mark a task complete merely because a table, screen or endpoint exists. Completion includes validation, permissions, concurrency, immutable history/reversal, tests and traceability.