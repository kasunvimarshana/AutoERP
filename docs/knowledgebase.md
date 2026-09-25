# AutoERP Vehicle Rental Business Knowledge Base

**Status:** Canonical Vehicle Rental business/domain reference for AutoERP. Confirmed rules are authoritative; unresolved rules are intentionally fail-closed and must not be guessed.

**Knowledge refresh date:** 2026-09-25

**Primary business source and conflict tie-breaker:** TACGL legacy application/data corpus

**Authoritative workflow evidence:** all four supplied Vehicle Rental videos

**Authoritative engineering source:** latest `worktree-0.0.8`

**Implementation baseline reconciled:** `e8576e3f7ea0923852cc5ff0487994a9d992e45e`

**Architecture policy:** root/docs `RULES.md` and `AGENTS.md`

**Implementation backlog:** [`vehicle-rental/TODO.md`](vehicle-rental/TODO.md)

---

## 1. Purpose

This document is the self-contained business knowledge base for AutoERP Vehicle Rental. It exists so an AI agent, developer, tester, analyst, reviewer, or future maintainer can understand and reason about the domain without depending on undocumented chat history, legacy implementation quirks, or guessed business rules.

It defines:

- domain concepts and terminology;
- entities and relationships;
- operator workflows;
- states and transitions;
- validations and invariants;
- customer billing and owner settlement boundaries;
- Running Chart meaning;
- financial document handoffs;
- deposits, payments, adjustments and reconciliation;
- module ownership boundaries;
- permissions, concurrency and audit requirements;
- UI/UX principles;
- known edge cases;
- explicit, implicit, conflicting, incomplete and unresolved rules;
- what is currently implemented and what remains blocked by missing business evidence.

The goal is not to copy TACGL screen-for-screen or table-for-table. TACGL is authoritative for demonstrated business meaning. Legacy defects, duplicate workflows, raw codes, mutable posted records, repair-after-error procedures and other historical architecture problems are evidence to correct, not behavior to preserve.

The engineering rule is:

> **Understand first, verify second, change third.**

When evidence is insufficient, preserve uncertainty. Do not invent a default merely to complete implementation.

---

## 2. Source authority

### 2.1 TACGL — primary business source

TACGL is the primary source for demonstrated Vehicle Rental economic and accounting behavior. Repeated structured transactions, source-to-ledger lineage, reports, charge vocabularies and consistent historical arithmetic have the highest business weight.

Canonical uploaded archive:

- `TACGL.zip`
- SHA-256: `0e0733fff720072af4c3aaa787995ff128bfa79060a37739d6d2ebbe18a25313`
- 456 ZIP entries
- 452 files plus 4 directory entries

The dated upload `TACGL(20260925-035203).zip` has a different archive SHA because its wrapper/compression metadata differs, but after removing the optional `TACGL/` wrapper all **452 business files are path-for-path and SHA-256 content-identical** to the canonical archive. It therefore introduces no conflicting or additional business evidence.

`TACGL.rar` was also previously compared against the same 452-file corpus and is equivalent at inner-file content level.

Important structured sources include:

- `scfveh.dbf` — vehicle master/context;
- `scfdeb.dbf` — customer/debtor master;
- `scfcre.dbf` — creditor/owner/supplier context;
- `scfchr.dbf` — charge vocabulary;
- `jobtxn.dbf` — line-level commercial/workshop/rental-like transaction evidence;
- `scfinv.dbf` — customer invoices;
- `scftdb.dbf` — debtor transactions/allocations;
- `scftcr.dbf` — creditor transactions;
- `scftxn.dbf` — financial transactions;
- `scfglt.dbf` — General Ledger lineage;
- `scfacc.dbf` — account vocabulary;
- report FRX/FRT files — reporting and calculation-expression evidence;
- deleted/error/history structures — evidence of legacy repair-oriented operation, not a target design.

### 2.2 Video evidence — authoritative practical workflow source

| Video | Duration | SHA-256 | Main evidence |
|---|---:|---|---|
| `1.mp4` | ~40:50 | `ac4ca8e632081c32cd2a1d2e6facb070acf4a1f5304a4dc7a468ca7073b953cf` | Agreements, Running Chart, customer invoice, owner payable, deductions, cheque/payment, reconciliation |
| `Recording 2026-06-21 132314.mp4` | ~41:58 | `11866d255dbb709055b43bb7428538a3e2f0858a8ee1d0144187bcdaf4616ffa` | Vehicle/party registers, agreements, Running Chart, invoice/PDF, receipt allocation, owner statement, reports, users |
| `2.mp4` | ~21:14 | `cd2ba1399f149003f19080327458e4bbe4619b88eed9416053c7f8d21431c36f` | Transaction/report inventory, allocations, cheque/bank reconciliation, ledger/error procedures |
| `ScreenVideo_03-04-2026_18-02-52.mp4` | ~12:24 | `c9853b7923e7cb95f1014cf598416faa550bfbd56f19da56b613f160d0528ce9` | Workshop/Vehicle Service; supporting evidence for vehicle availability only |

Total represented footage is approximately **1 hour 56 minutes 26 seconds**.

The workshop-focused video is not a source for Rental pricing formulas. It only supports the operational boundary that maintenance, breakdown, workshop custody or off-road conditions can affect vehicle availability.

### 2.3 Engineering authority

The latest `worktree-0.0.8` branch is authoritative for:

- current AutoERP architecture;
- module ownership;
- integration contracts;
- tenant isolation;
- concurrency and versioning conventions;
- historical-data compatibility boundaries;
- what is actually implemented now.

Business evidence does not justify placing logic in the wrong module. Rental may orchestrate Rental workflows, but it must not duplicate Customer, Supplier, Vehicle, HR, Invoice, Payment, Tax, Finance, Reporting or Vehicle Service responsibilities.

### 2.4 Evidence classes

Every rule should be classified as one of:

- **Explicit — TACGL**: directly represented by TACGL data, reports or accounting lineage.
- **Explicit — Video**: directly visible in supplied video behavior.
- **Cross-source confirmed**: supported independently by TACGL and video evidence.
- **Integrity-derived**: the narrowest technical control required to preserve a proven rule safely.
- **Observed precedent only**: a historical example that does not establish a universal policy.
- **Legacy mechanism rejected**: real legacy behavior whose business capability is valid but whose implementation must not be copied.
- **Unresolved**: available evidence does not uniquely determine the rule.

### 2.5 Conflict rule

When sources differ or are incomplete:

1. Confirm they refer to the same party, agreement, period and business event.
2. Prefer repeated/direct TACGL economic or accounting evidence for business meaning.
3. Use videos for practical workflow and visible operator intent.
4. Use cross-source consistency to strengthen conclusions.
5. Derive only integrity controls necessary to preserve proven meaning.
6. If financially meaningful alternatives remain plausible, mark the rule **Unresolved**.
7. Never promote one historical amount, narration, customer contract or UI default into a universal policy without corroboration.

---

## 3. Executive domain model

Vehicle Rental is a **dual-sided operational and financial domain**.

```text
Vehicle Owner / Lessor
    -> Owner Agreement
    -> Owner-supply vehicle availability

Customer / Lessee
    -> Customer Agreement
    -> Customer-use vehicle assignment

Both sides meet at physical vehicle usage
    -> Daily / Replacement Running Chart

One finalized physical Running Chart
    |-- Customer commercial calculation
    |      -> Customer Invoice
    |      -> Customer Receipt / allocation
    |
    `-- Owner commercial calculation
           -> Owner Payable Voucher / Owner Settlement
           -> Owner Payment / allocation
```

The strongest invariant is:

> **One physical Running Chart can feed two independent commercial calculations.**

Customer billing is governed by Customer Agreement terms. Owner settlement is governed by Owner Agreement terms. Neither amount is derived from the other.

Processing one side must not block the other side. The same source evidence must not be consumed twice on the same financial side.

---

## 4. Core terminology

### 4.1 Lessee / Customer

The party renting a vehicle from the business.

Customer-side accounting meaning:

- receivable;
- rental revenue;
- customer invoice;
- receipt and allocation;
- debit/credit adjustments;
- customer statement.

### 4.2 Lessor / Vehicle Owner / Supplier

The external party providing a vehicle to the business. It may be an individual, company or leasing-company context.

Owner-side accounting meaning:

- payable/cost;
- owner rental settlement;
- owner payable voucher;
- payment and allocation;
- deductions/adjustments;
- owner/vehicle statement.

Do not model the normal owner-side settlement as a customer sales invoice. The demonstrated business meaning is a **Payment Payable Voucher / Owner Payable Voucher / Self-billed Owner Settlement**.

### 4.3 Company-owned vehicle

A vehicle owned by the operating company does not need an artificial Owner Agreement solely to imitate the external-owner path unless an explicit internal transfer-cost policy is later proven.

### 4.4 Physical vehicle

The Vehicle module owns the canonical vehicle identity and legal/operational vehicle master.

Rental owns only Rental-specific usage relationships and commercial/operational state.

### 4.5 Running Chart

The Running Chart is the central physical usage evidence record. It can contain or support:

- service date/period;
- vehicle;
- customer/lessee context;
- owner/lessor context;
- driver;
- start/finish mileage;
- start/finish time;
- commercial kilometres;
- garage mileage;
- overtime evidence;
- night-out evidence;
- AC mode;
- other operational remarks/evidence.

It is not an invoice line and not an owner payable line. It is the operational source from which commercial calculations are derived.

---

## 5. Canonical user workflow

The target user-facing flow must stay simple and close to the demonstrated workflow:

```text
Vehicle / Customer / Owner setup
    -> Owner Agreement (only when externally supplied)
    -> Customer Agreement
    -> Open Agreement -> Select Vehicle -> Save
    -> Handover / driver where applicable
    -> Daily Running Chart
    -> Customer Invoice
    -> Owner Payable Voucher
    -> Customer Receipt / Owner Payment
    -> Reports
```

Technical integrity records may exist behind this flow, but users should not be forced through separate technical wizards merely because the database has separate tables.

---

## 6. Agreements

### 6.1 Separate agreements are mandatory

Customer/lessee and owner/lessor agreements are separate commercial contracts.

They may share:

- a physical vehicle;
- overlapping service periods;
- the same Running Chart evidence;

but they do not share rates or financial calculation ownership.

### 6.2 Customer Agreement terms demonstrated by the sources

Supported business concepts include:

- monthly or daily basis;
- agreement start/end period;
- base rental rate;
- included kilometres;
- excess kilometre rate;
- Non-AC / Front-AC / Dual-AC pricing context;
- driver inclusion/context;
- driver salary/recovery value;
- normal/double/triple OT values;
- night-out value;
- deposit/security context;
- tax context;
- notes/other recoveries where explicitly configured.

A visible field proves the concept exists. It does not prove requiredness, qualification thresholds, fallback rules or universal defaults unless corroborated.

### 6.3 Owner Agreement terms demonstrated by the sources

Supported concepts include:

- owner/lessor party;
- vehicle supply relationship;
- monthly/daily payable rate;
- included/excess KM terms;
- driver reimbursements;
- OT/night-out reimbursements;
- fuel/repair or other deductions;
- settlement/payment context;
- vehicle-wise owner statement reporting.

Misleading legacy labels such as owner-side `Rental Income` do not change the economic meaning. Determine semantics from party direction and downstream financial document.

### 6.4 Agreement history

Commercial history must be immutable after it becomes effective.

Required design:

- draft editing is version-checked;
- activation freezes the effective revision;
- material commercial changes create successor/effective revisions rather than rewriting history;
- calculations reference the exact effective agreement revision used;
- financial history can be reproduced later.

---

## 7. Vehicle assignment and custody

### 7.1 User-facing assignment

The user-facing interaction should remain:

```text
Open Agreement -> Select Vehicle -> enter period/driver context -> Save
```

Do not expose raw foreign keys or technical side codes.

### 7.2 Hidden integrity model

Backend records may separately represent:

- owner-supply availability;
- customer-use allocation;
- actual custody/handover;
- return;
- replacement lineage;
- predecessor/successor relationship;
- effective period.

This is an integrity mechanism, not a reason to burden the operator with duplicated workflows.

### 7.3 Proven integrity requirements

The system must prevent:

- impossible overlapping vehicle use;
- wrong agreement/vehicle association;
- customer use outside valid source coverage;
- cross-tenant relationship leakage;
- stale version writes;
- broken replacement lineage;
- vehicle use while a confirmed shared availability blocker applies.

Planning coverage may be date-based where implemented policy explicitly says so; physical handover/custody and overlap controls use actual time evidence where relevant.

### 7.4 Replacement vehicle

Replacement is a physical continuity event, not a new unrelated rental.

Preserve:

- predecessor assignment;
- replacement assignment;
- actual replacement time;
- Running Chart lineage;
- customer/owner source continuity;
- independent billing history.

**Unresolved:** replacement-day/period charging is not proven well enough to invent a surcharge or double-charge rule.

---

## 8. Running Chart lifecycle

### 8.1 Meaning

The Running Chart is physical operational truth.

A finalized chart should be immutable except through governed correction/reversal lineage.

### 8.2 Evidence fields

Confirmed or directly supported concepts include:

- start/finish mileage;
- start/finish time;
- total/commercial kilometres;
- garage mileage;
- driver;
- OT type/minutes/hours as represented;
- night-out count/evidence;
- AC selection context;
- customer and owner agreement context;
- trip/remarks context.

### 8.3 Required integrity behavior

The system must distinguish:

- unknown value;
- known zero;
- missing/not applicable.

Do not silently turn null into zero when the distinction is financially meaningful.

Required controls include:

- odometer monotonicity/continuity where physically applicable;
- no impossible vehicle time overlap;
- no impossible driver time overlap when driver identity is authoritative;
- tenant/organization isolation;
- version checks;
- immutable finalized source history;
- correction/reversal lineage instead of destructive editing.

### 8.4 Approval stages

The videos establish operational entry and downstream processing but do not prove a universal multi-stage `Submit -> Verify -> Approve -> Finalize` workflow.

Therefore:

- Draft and Finalized are valid core states;
- additional approval layers require explicit business evidence/configuration;
- do not add ceremony merely because it seems enterprise-like.

---

## 9. Commercial calculation model

### 9.1 Independence

```text
Finalized Running Chart
    |-- Customer calculation -> Customer Agreement revision
    `-- Owner calculation    -> Owner Agreement revision
```

The two calculations may use the same physical facts but different:

- rates;
- inclusions;
- deductions;
- tax applicability;
- commercial policy;
- settlement timing.

### 9.2 Customer-side components supported by evidence

Potential customer invoice components include:

- base rental;
- excess kilometres;
- AC-mode difference where configured;
- driver salary/recovery;
- normal/double/triple overtime;
- night-out;
- approved miscellaneous recoveries;
- debit/credit adjustments;
- tax.

A component must only be charged when its agreement/policy is explicit and the source evidence required by that policy exists.

### 9.3 Owner-side components supported by evidence

Potential owner settlement components include:

- base rental payable;
- excess kilometre payable;
- driver reimbursements;
- overtime reimbursement;
- night-out reimbursement;
- fuel/repair/damage or other supported deductions;
- debit/credit adjustments;
- applicable tax/withholding when governed by the Tax/Finance configuration.

### 9.4 Base rent

The fresh implementation has an explicit actual-calendar base-rent policy and immutable source calculation/handoff. That implementation is authoritative only for the selected policy. Historical TACGL evidence does not justify silently applying the same proration convention to every future agreement.

### 9.5 Mileage

The current implementation supports an explicit allowance/reset policy with separate customer and owner commercial pools.

Core invariants:

- physical distance is source evidence;
- customer allowance and owner allowance are independent;
- customer usage may pool across replacement vehicles only when the selected policy explicitly says so;
- owner-side pools remain tied to the relevant owner commercial context;
- no cross-cycle carry-forward unless explicitly configured;
- a zero-cost consumption can still consume allowance;
- same source distance cannot be billed twice on the same side.

### 9.6 OT and night-out

The implementation can price recorded OT/night-out evidence when explicit agreement rates/policy are available.

**Still unresolved unless explicitly configured:**

- automatic qualification thresholds;
- what makes OT normal/double/triple;
- what qualifies a night-out;
- statutory/contract interactions not represented in the agreement.

Do not infer these from generic industry practice.

---

## 10. Financial documents

### 10.1 Customer Invoice

The customer calculation hands off to the Invoice module.

Rental owns:

- Rental source identity;
- source calculation snapshot;
- duplicate-consumption prevention;
- Rental business context.

Invoice owns:

- invoice document lifecycle;
- lines/totals;
- source allocations;
- tax preparation contract;
- posting/reversal behavior delegated to owning modules.

### 10.2 Owner Payable Voucher / Owner Settlement

The normal owner-side financial document is a payable/settlement document, not a customer sales invoice.

Use human-facing terminology such as:

- `Owner Payable Voucher`;
- `Owner Settlement`;
- `Lessor Settlement`.

If an external supplier formally submits a supplier tax invoice, that exceptional document belongs to the existing supplier/AP/Invoice owner module; do not collapse it into the normal Rental self-billed settlement workflow.

### 10.3 Customer Receipt

Money received from a customer is a **Customer Receipt**, not a customer payment from the company's perspective.

Payment module owns:

- payment/receipt identity;
- allocation;
- unapplied amount;
- reversal/refund mechanics;
- accounting handoff.

Rental only supplies Rental source context where needed.

### 10.4 Owner Payment

Company payment to an owner/supplier belongs to Payment/AP ownership, with Rental source context and owner settlement allocation.

### 10.5 Financial immutability

Posted/realized financial history must not be edit/delete mutable.

Corrections use:

- reversal;
- replacement/reissue;
- debit/credit adjustment;
- append-only audit trail.

---

## 11. Deposits

Security deposit is a customer-side liability/advance concept, not rental revenue by default.

Confirmed design principles:

- agreement may explicitly require a deposit;
- no universal deposit amount may be invented;
- receipt must be a real inbound customer advance/deposit transaction;
- received, applied, refunded and remaining balances must reconcile;
- forfeiture must not happen automatically without an explicit supported business decision;
- corrections/reversals must preserve audit history.

Deposit application/refund belongs to Payment/Finance ownership; Rental owns the requirement/context.

---

## 12. Adjustments, deductions and exceptions

The legacy domain demonstrates:

- debit notes;
- credit notes;
- miscellaneous invoices/charges;
- fuel deductions;
- repair deductions;
- owner/customer allocations.

These prove adjustment capability, not automatic entitlement.

Before posting a deduction/recovery, the implementation must know:

- which side bears it;
- source evidence;
- amount or calculation policy;
- tax treatment;
- whether approval/authorization is required;
- financial document type.

Do not make a generic `other_charge` field automatically billable merely because legacy screens contained one.

---

## 13. Cheques and bank reconciliation

Legacy workflow demonstrates a separation between:

- payable creation;
- cheque/payment instrument creation;
- allocation;
- realization;
- bank reconciliation.

Modern implementation should preserve the business distinction while reusing Payment/Finance ownership.

Do not put cheque/bank-ledger logic inside Rental merely because Rental users initiate the payment.

---

## 14. Tax and withholding

Tax is owned by the Tax module.

Rental must provide:

- source document/context;
- component classification;
- taxable amount/line context;
- party and jurisdiction context where required.

Rental must not invent:

- tax percentages;
- inclusive/exclusive policy;
- rounding;
- withholding applicability;
- withholding account mapping.

These remain configuration/statutory decisions owned by Tax/Finance.

---

## 15. Accounting model

Vehicle Rental participates in accounting but does not own the General Ledger engine.

Typical economic directions:

```text
Customer invoice
    -> Rental revenue / recoveries
    -> Accounts receivable

Customer receipt
    -> Cash / bank
    -> Accounts receivable or customer advance

Owner settlement
    -> Rental/vehicle cost or configured expense
    -> Accounts payable

Owner payment
    -> Accounts payable
    -> Cash / bank
```

Exact accounts, tax lines, withholding and organizational dimensions must be resolved through Finance posting profiles, not hardcoded in Rental.

---

## 16. Reports

Evidence supports the need for:

- customer/lessee agreement reporting;
- owner/lessor agreement reporting;
- Running Chart reports;
- customer invoice/rental sales reports;
- owner payable/settlement reports;
- customer ledger/statement;
- owner ledger/statement;
- vehicle-wise activity/financial reports;
- outstanding balances;
- cheque/payment/reconciliation reports;
- General Ledger lineage/reconciliation reports.

Reporting belongs to Reporting/Finance where appropriate. Rental should provide stable source views/contracts instead of duplicating ledger logic.

---

## 17. Permissions

Permissions should be action-based and human-meaningful, not legacy numeric user levels.

Examples of distinct capability boundaries:

- view agreements;
- create/update draft agreements;
- activate/close agreements;
- assign vehicles;
- record handover/return/replacement;
- view/edit draft Running Charts;
- finalize/reverse Running Charts;
- create commercial calculations;
- issue customer billing;
- create owner settlement;
- view reports.

Financial posting, tax configuration, payment authorization and accounting-period control remain in their owner modules.

---

## 18. Concurrency and data integrity

Assume multiple actors can update the same business objects concurrently.

Required engineering controls:

- database transactions around multi-row state changes;
- deterministic lock order for multi-resource operations;
- optimistic `row_version`/expected-version checks for operator updates;
- tenant-safe composite relationships where applicable;
- atomic replacement/handover/return transitions;
- exactly-once source consumption for commercial calculations and financial handoffs;
- idempotent retry behavior;
- no silent last-write-wins overwrite of business history.

---

## 19. Module ownership boundaries

### Vehicle Rental owns

- Rental agreements and effective revisions;
- Rental-side vehicle-use relationships;
- Rental custody/replacement lineage;
- Running Chart business records;
- Rental commercial source calculations;
- Rental source-consumption identity;
- Rental-specific orchestration and UI.

### Vehicle owns

- canonical vehicle identity;
- vehicle master attributes;
- ownership/legal document master where applicable;
- shared operational status contract.

### Customer owns

- customer master identity/contact/commercial party data.

### Supplier/Party owner owns

- owner/supplier master identity and payment party data.

### HR owns

- internal employee/driver identity and HR lifecycle.

### Invoice owns

- invoice document lifecycle and source allocation.

### Payment owns

- receipts/payments, allocations, advances, refunds and reversals.

### Tax owns

- tax rules, snapshots, rounding and statutory treatment.

### Finance owns

- chart of accounts;
- posting profiles;
- journals;
- periods;
- bank reconciliation.

### Vehicle Service owns

- workshop/service jobs;
- maintenance/off-road states;
- availability blockers originating from service operations.

### Reporting owns

- cross-module analytical/reporting presentation where appropriate.

Do not fix a missing owner-module behavior by embedding a workaround in Rental.

---

## 20. UI/UX rules

The practical legacy workflow is simple even though the domain is complex. Preserve that simplicity.

Required UI principles:

- no raw database IDs;
- relationship fields use searchable human-readable selectors;
- show vehicle number/model, customer name, owner name and agreement reference;
- hide technical side/type identifiers when context already determines them;
- avoid duplicated navigation and giant forms;
- show only fields relevant to the current action;
- move history, audit, advanced details and calculations into review/secondary views;
- provide clear financial previews before irreversible actions;
- display the impact of finalize, issue, cancel, reverse, replace or settle actions;
- never require operators to understand database architecture.

Canonical assignment UX:

```text
Open active agreement -> Select vehicle -> enter period/driver details -> Save
```

Canonical Running Chart UX should remain fast, table/form based and operationally focused.

---

## 21. Legacy mechanisms that must not be copied

Reject these implementation patterns even when found in TACGL/videos:

- agreement hard-binding with no effective history;
- raw customer/vehicle/GL code entry;
- duplicate lessor/leasing-company engines without proven commercial difference;
- mutable posted invoices/payments;
- password-register/numeric-level authorization;
- same Running Chart consumed repeatedly on the same financial side;
- customer amount used as source for owner payable;
- error reports/procedures as the primary way to restore integrity after invalid records are already posted;
- compatibility layers that revive the removed legacy Rental implementation;
- hardcoded magic codes, rates or accounts.

---

## 22. Key edge cases

The implementation must reason correctly about at least:

- company-owned vs externally owned vehicle;
- open-ended agreements/assignments;
- replacement mid-period;
- same-day planning with different handover times;
- backfilled Running Charts;
- odometer correction;
- unknown vs zero OT/night-out;
- customer-side calculation completed while owner-side remains pending;
- owner-side calculation completed while customer-side remains pending;
- zero-cost but allowance-consuming usage;
- stale quote after agreement revision changes;
- failed financial handoff and safe retry;
- invoice/payment reversal after source consumption;
- deposit application/refund reversal;
- vehicle entering workshop/off-road state during a planned rental;
- cross-tenant IDs submitted maliciously;
- duplicate requests/concurrent operators;
- closed accounting period;
- partial/over allocations and unapplied receipts/payments.

---

## 23. Business invariants

The following invariants are safe to treat as non-negotiable:

1. Customer and owner agreements are separate.
2. One physical Running Chart may support both financial sides.
3. Customer billing uses customer-side terms only.
4. Owner settlement uses owner-side terms only.
5. Customer and owner financial processing are independent.
6. A source record cannot be consumed twice on the same side without governed reversal/reissue.
7. Finalized/posted history is immutable.
8. Vehicle identity is canonical in Vehicle, not duplicated in Rental.
9. Financial documents are owned by their financial modules.
10. Cross-tenant relationships are invalid even if IDs exist.
11. Concurrency must not silently overwrite business history.
12. Unproven financial policy must fail closed rather than guess.
13. User-facing workflow must not be made unnecessarily more complex than the demonstrated business process.

---

## 24. Explicit unresolved / unproven rules

These rules remain materially unresolved unless an explicit agreement/configuration or new authoritative evidence settles them.

### VR-U01 — Partial-month monthly-rental proration

TACGL/video evidence does not prove one universal formula for every contract.

Do not silently choose `days/30`, actual-calendar, anniversary-cycle, or any other method for all agreements.

### VR-U02 — Monthly day-count convention

If proration is enabled, the source does not prove one universal denominator/calendar convention.

### VR-U04 — Replacement charging

The sources prove replacement capability/need, not whether a replacement day can produce double base rent, a surcharge, a free continuation or another treatment.

### VR-U05 — Downtime/off-road deduction

Maintenance/off-road affects operational availability. The financial deduction rule is not universally proven.

### VR-U06 — Garage mileage treatment

Garage mileage exists as a business concept. The source does not prove a universal customer/owner charging rule.

### VR-U07 — Accident / insurance excess responsibility

Responsibility and recoverability must be explicit; do not infer from ordinary repair deductions.

### VR-U10 / VR-U11 — Tax applicability and rounding

Owned by Tax configuration/statutory logic; Rental must not invent it.

### VR-U12 — Withholding on owner settlement

Requires explicit statutory/configuration evidence.

### VR-U13 — AC fallback behavior

The sources show Non-AC, Front-AC and Dual-AC concepts but do not prove that missing rates fall back to another mode.

### VR-U14 — OT qualification thresholds

Normal/double/triple OT values exist, but universal threshold logic is not proven.

### VR-U15 — Night-out qualification

Night-out exists, but automatic qualification conditions are not universally proven.

### VR-U16 — Driver salary proration

Driver salary/recovery exists; universal proration is not proven.

### VR-U17 — Additional Running Chart approval stages

Extra approval ceremony is not universally demonstrated.

### VR-U18 / VR-U19 — Insurance / Revenue Licence as Rental blockers

The documents may exist in Vehicle, but Rental assignment blocking is not proven as a universal business rule and must not be added without evidence.

### VR-U20 — Individual owner vs leasing-company commercial difference

Do not create two settlement engines unless a real commercial rule difference is proven.

### VR-U21 — Company-owned internal transfer cost

Default is no artificial external-owner payable unless an internal accounting policy is explicitly configured.

### VR-U22 — Miscellaneous recoveries

Meal, highway, parking, fuel, damage and other recoveries must be individually policy-backed; historical occurrence does not make them automatic.

---

## 25. Current implementation reconciliation — `worktree-0.0.8`

The current branch contains a fresh Vehicle Rental module and must not be replaced with the removed old implementation.

Confirmed implemented foundation includes:

- separate customer and owner agreement services;
- agreement validation/versioning;
- fresh tenant-safe schema;
- vehicle-use/custody/replacement operations;
- Running Chart draft/finalize/reverse/correction lineage;
- odometer continuity;
- operational time handling;
- owner-source coverage logic;
- base-rent preview and billing;
- recorded OT/night-out pricing workflows;
- explicit-policy mileage allowance/assessment;
- Rental charge documents and Invoice handoff;
- customer security-deposit receipt/disposition support;
- Rental authorization/permissions;
- shared vehicle availability integration;
- frontend Rental workspace and guided lifecycle UI;
- tests and architecture contracts for delivered slices.

The current branch head differs from the previously documented `250888bf...` baseline by one later Invoice-owner correction commit. That commit updates Invoice adjustment allocation residual/release behavior plus the corresponding Rental documentation; it does not justify changing Vehicle Rental business rules.

### 25.1 What is safe to implement now

Only changes backed by:

- confirmed TACGL/video evidence;
- an explicit agreement/configuration policy;
- or a necessary integrity invariant.

### 25.2 What must remain blocked

Any calculation depending on an unresolved VR-U rule above must either:

- require an explicit policy selection/configuration;
- or fail closed with a clear user-facing explanation.

Do not add speculative defaults merely to declare the module complete.

---

## 26. Production-ready TODO

The authoritative execution backlog remains [`vehicle-rental/TODO.md`](vehicle-rental/TODO.md). At a high level:

### P0 — Source and policy authority

- keep hashes/evidence registry current;
- retain timestamp/record evidence for financially material rules;
- complete uninspected source work where realistically possible;
- resolve or explicitly configure financially material VR-U policies.

### P1 — Domain completion

- finish effective agreement successor behavior where still incomplete;
- complete authoritative driver identity/external-driver rules;
- complete remaining commercial components only when their policies are proven;
- complete owner settlement semantics through the owning financial modules;
- complete supported debit/credit adjustment scenarios;
- complete required reports and source lineage.

### P2 — Release verification

- clean install and upgrade rehearsal;
- real MySQL/MariaDB transaction/concurrency verification;
- authenticated browser/UAT acceptance;
- role/permission smoke testing;
- Finance/Tax/Payment end-to-end posting/reversal verification;
- backup/restore and operational readiness checks.

---

## 27. Source limitations

The following limitations remain important:

- The supplied TACGL archive contains real Rental-related economic/accounting evidence but is not proven to be the complete dedicated AT Tours Rental application/data store shown in the videos.
- The nested password-protected backup has not been extracted; its directory can be listed, but content is unavailable without a valid password.
- Interval-frame/video evidence and selected anchors do not prove every spoken-only rule.
- Hidden executable decision logic is not business evidence unless safely demonstrated or independently corroborated.
- One historical transaction proves that a case occurred, not that its formula applies universally.

Unknown source areas are **unknown**, not proof that a feature or rule does not exist.

---

## 28. AI-agent decision procedure

When an AI agent must decide how Vehicle Rental should behave:

1. Identify the physical business event.
2. Identify which commercial side is being processed: customer or owner.
3. Locate the exact agreement/effective revision.
4. Locate the physical source evidence, usually Running Chart/assignment/custody.
5. Check whether the requested commercial component is explicitly configured and evidence-backed.
6. Check whether the source was already consumed on that side.
7. Resolve tenant, organization, permission and version constraints.
8. Delegate Invoice/Payment/Tax/Finance responsibilities to their owner modules.
9. Preserve immutable snapshots and audit lineage.
10. If a financially meaningful rule is unresolved, stop and surface the missing policy instead of guessing.

---

## 29. Canonical mental model

Use this mental model for all future design, implementation and QA work:

```text
PARTIES
Customer / Lessee                     Owner / Lessor
       |                                    |
       v                                    v
Customer Agreement                    Owner Agreement
       |                                    |
       +---------------+--------------------+
                       |
                       v
             Physical vehicle usage
                       |
                       v
              Finalized Running Chart
                 /                 \
                /                   \
               v                     v
Customer Calculation             Owner Calculation
Customer Agreement terms         Owner Agreement terms
               |                     |
               v                     v
Customer Invoice                Owner Payable Voucher
               |                     |
               v                     v
Customer Receipt                Owner Payment
               \                     /
                \                   /
                 v                 v
          Tax / Finance / Reconciliation / Reports
```

The architecture may be sophisticated internally, but the operator experience should remain simple:

> **Simple video-style UI + strong hidden backend integrity.**

---

## 30. Final authority statement

For Vehicle Rental business decisions:

1. **TACGL is the primary business source and conflict tie-breaker.**
2. **The supplied videos are authoritative practical workflow evidence.**
3. **`worktree-0.0.8` is the authoritative current implementation source.**
4. **`RULES.md` / `AGENTS.md` govern engineering quality and module ownership.**
5. **Never restore the old Rental implementation or preserve its design mistakes through compatibility patches.**
6. **Never invent financially material business rules without evidence.**
7. **When evidence is insufficient, document the uncertainty and fail closed.**
8. **Preserve the demonstrated business meaning while keeping the user workflow fast, clear and simple.**
