# AutoERP Vehicle Rental Business Knowledge Base

**Status:** Canonical Vehicle Rental business/domain and production-policy reference for AutoERP.

**Knowledge refresh date:** 2026-10-05

**Primary business source / conflict tie-breaker:** TACGL legacy application/data corpus

**Authoritative practical workflow evidence:** all four supplied Vehicle Rental videos

**Authoritative engineering source:** latest `worktree-0.0.8`

**Executed Vehicle Rental runtime baseline:** `9f492b5094039522c53b4e7509be296217b2d5b9`

**Live authoritative branch head:** resolve from Git/release records; it is intentionally not embedded here because documentation-only release commits would otherwise make this document self-stale.

**Latest current-head re-verification:** [`changes/2026-10-05-vehicle-rental-release-verification-fixes.md`](changes/2026-10-05-vehicle-rental-release-verification-fixes.md)

**Architecture policy:** root `RULES.md` / `AGENTS.md`

**Implementation acceptance ledger:** [`vehicle-rental/TODO.md`](vehicle-rental/TODO.md)

---

## 1. Purpose and authority

This document is the self-contained Vehicle Rental source of truth for AutoERP. It captures the Vehicle Rental business meaning proven by TACGL and the supplied videos, separates proven business behavior from safe AutoERP production policy, and reconciles both with the fresh Vehicle Rental implementation on the authoritative `worktree-0.0.8` branch.

It is **not** a screen-by-screen legacy specification and it is **not** permission to restore, copy, cherry-pick or revive removed Rental code.

Three rules govern every Vehicle Rental decision:

> **Understand first, verify second, change third.**

> **Do not invent money.** Physical facts may be recorded when the financial consequence is unknown, but no charge, credit, deduction, tax, withholding or payable may be manufactured from an unproved rule.

> **Keep the operator workflow simple; enforce integrity behind the workflow.**

Source precedence:

1. TACGL business evidence;
2. directly demonstrated video workflow evidence;
3. the narrowest integrity rule needed to preserve proven meaning safely;
4. explicit AutoERP production policy documented here;
5. otherwise no automatic financial effect until the business rule is confirmed.

---

## 2. Source evidence register

### 2.1 TACGL corpus

Canonical upload:

- `TACGL.zip`
- SHA-256: `0e0733fff720072af4c3aaa787995ff128bfa79060a37739d6d2ebbe18a25313`
- 452 non-directory business files.

Dated upload supplied in this audit:

- `TACGL(20261005-182335).zip`
- SHA-256: `79c240494943437978754169c3360bb7c6e35d911ef8263c4b2d6b6246384d77`
- 452 non-directory business files.

The canonical and dated ZIPs contain the same normalized business payload. After removing the canonical archive's outer `TACGL/` wrapper, every relative path and every per-file SHA-256 matches. The 2026-10-05 dated package therefore adds no conflicting business evidence and does not change any Vehicle Rental business rule.

Corroborating package:

- `TACGL.rar`
- SHA-256: `0001a91ab4dfdf31d60f669b30f1ccc2da475d7841b2ceea505ef97eff5bedbf`

The protected nested backup remains unavailable without a valid password. Inaccessible content is not evidence and must not be used to invent hidden rules.

TACGL is a Visual FoxPro-era ERP corpus containing, among other artifacts:

- vehicle master data;
- debtor/customer and creditor/owner/supplier masters;
- invoice, transaction, receipt, payment and allocation data;
- cheque, bank and general-ledger structures;
- vehicle/job/service data;
- report layouts and financial statements;
- historical repair/error-detection procedures.

Concrete schema evidence includes:

- `scfveh.DBF` with vehicle code, customer/owner reference, make/model, engine/chassis, service-mileage and audit fields;
- `scfdeb.DBF` for debtor/customer master and balances/credit controls;
- `scfcre.DBF` for creditor/supplier master;
- `scfinv.DBF` for invoice references, vehicle, job, debtor, quantity/rate/value, VAT/NBT and audit fields;
- `scftdb.DBF` and `scftcr.DBF` for debtor/creditor subledger transactions and balances;
- `scfglt.DBF` for GL transaction date/reference/account/amount/cheque/job and posting metadata;
- `scftxn.DBF` for transaction-level item/vehicle/customer/supplier/value/tax fields.

These structures corroborate a dual-sided receivable/payable business with accounting integration. They are business evidence, not a schema template for AutoERP.

### 2.2 Video corpus

| Video | Duration | SHA-256 | Main evidence |
|---|---:|---|---|
| `1.mp4` | 40:50 | `ac4ca8e632081c32cd2a1d2e6facb070acf4a1f5304a4dc7a468ca7073b953cf` | Customer/owner agreements, Running Chart, customer billing, owner payable, deductions, cheque/payment/reconciliation |
| `Recording 2026-06-21 132314.mp4` | 41:58 | `11866d255dbb709055b43bb7428538a3e2f0858a8ee1d0144187bcdaf4616ffa` | Vehicle/customer registers, agreements, Running Chart, invoice/receipt allocation, owner statements/reports |
| `2.mp4` | 21:14 | `cd2ba1399f149003f19080327458e4bbe4619b88eed9416053c7f8d21431c36f` | Rental transactions/reports, allocation/reconciliation and legacy repair procedures |
| `ScreenVideo_03-04-2026_18-02-52.mp4` | 12:24 | `c9853b7923e7cb95f1014cf598416faa550bfbd56f19da56b613f160d0528ce9` | Workshop / Vehicle Service availability boundary, not Rental pricing |

The four videos total approximately 1 hour 56 minutes 26 seconds. The workshop-focused video is supporting evidence for vehicle availability, maintenance and off-road boundaries only; it is not a source for Rental tariffs.

### 2.3 Evidence classes

Every rule in this document belongs to one of these classes:

- **Explicit-TACGL** — directly represented by TACGL data/report/accounting evidence.
- **Explicit-Video** — directly demonstrated in the supplied workflow evidence.
- **Cross-source** — supported independently by TACGL and the videos.
- **Integrity-derived** — the narrowest technical rule required to preserve proven business meaning safely.
- **Observed precedent only** — an actual historical example that is not proof of a universal rule.
- **Production policy** — an explicit AutoERP decision required to make runtime behavior deterministic and safe.
- **Legacy mechanism rejected** — historical design behavior that must not be copied.

When evidence is incomplete:

1. preserve the physical/business fact;
2. keep unknown distinct from zero;
3. do not convert one example into a universal formula;
4. do not add hidden fallback rates, thresholds or account codes;
5. use a named production policy only where it is explicitly documented;
6. otherwise create no automatic financial effect.

---

## 3. Canonical business model

Vehicle Rental is a dual-sided operational and financial domain.

```text
Owner / Lessor / Supplier                   Customer / Lessee
          |                                        |
    Owner Agreement                         Customer Agreement
          |                                        |
          +----------------+-----------------------+
                           |
                    Vehicle use / custody
                           |
                   Daily Running Chart
                       /             \
                      /               \
          Customer calculation     Owner calculation
          customer terms only      owner terms only
                    |                    |
          Customer Invoice        Owner Payable Voucher
                    |                    |
          Customer Receipt           Owner Payment
                     \                  /
              Tax / Finance / Reconciliation / Reports
```

### Non-negotiable invariants

1. Customer and Owner agreements are separate commercial aggregates.
2. One physical Running Chart may support both financial sides.
3. Customer billing never derives Owner payable.
4. Owner settlement never derives Customer billing.
5. Processing one side never consumes or blocks the other side.
6. The same source/component cannot be consumed twice on the same side without governed cancellation/release/reissue semantics.
7. Finalized physical evidence is immutable except through reversal/correction lineage.
8. Posted financial records are corrected through owner-module reversal/adjustment, never destructive edit/delete.
9. Financial calculations retain the exact agreement/source revision used.
10. Unknown and known-zero values are separate states.
11. Tenant and organization-unit boundaries apply to every owned record and relationship.
12. User-facing simplicity must not weaken backend integrity.

---

## 4. Canonical operator workflow

The normal user-facing workflow should remain close to the videos:

```text
Vehicle / Customer / Owner Setup
→ Owner Agreement, when externally supplied
→ Customer Agreement
→ Open Agreement → Select Vehicle → Save
→ Handover / Driver Assignment as needed
→ Daily Running Chart
→ Customer Billing ───────→ Customer Invoice ───────→ Customer Receipt
→ Owner Settlement ───────→ Owner Payable Voucher ──→ Owner Payment
→ Cheques / Bank Reconciliation
→ Reports / Audit
```

Customer Billing and Owner Settlement are parallel outcomes of the same operational evidence. They are not a strict serial chain.

A company-owned vehicle does not require an artificial external Owner Agreement merely to reuse the external-owner path. No fictitious Owner payable is created unless a separate explicit Finance policy requires internal transfer accounting.

---

## 5. Core parties and terminology

### Customer / Lessee

The party renting the vehicle from the business. Customer-side economic direction is receivable/revenue.

### Owner / Lessor / Supplier

The external party supplying a vehicle to the business. Owner-side economic direction is payable/cost.

The normal operator-facing owner-side document is **Owner Payable Voucher**, **Owner Settlement** or **Lessor Settlement**. The legacy Payment Payable processing must not be presented as a customer-style sales invoice.

### Customer Receipt

Money received from the customer. Do not label this as Customer Payment in the Rental UI.

### Owner Payment

Money paid by the business to the external owner/supplier.

### Running Chart

The Running Chart is physical operational truth, not a financial document. It records the facts from which Customer and Owner calculations may independently derive financial components.

### Vehicle Use / Assignment

The integrity aggregate that records effective vehicle use, custody, source coverage and replacement lineage. Normal users should not be forced through a technical allocation wizard when agreement-context vehicle selection is sufficient.

---

## 6. Module ownership

Vehicle Rental reuses canonical masters and financial modules. It must not clone them.

- Vehicle identity, registration and ownership: **Vehicle**.
- Customer identity/contact: **Customer**.
- Owner/supplier identity/contact: **Supplier / party owner**.
- Employee driver identity/status: **HR**.
- Currency/tax configuration: **Configuration / Tax**.
- Accounts, posting profiles and journals: **Finance**.
- Customer and AP documents: **Invoice/AP**.
- Receipts, payments and allocations: **Payment**.
- Workshop/off-road state: **Vehicle Service**.
- Cross-module reporting: **Reporting** where appropriate.

A missing capability must be fixed in the module that owns it; Rental must not compensate with a duplicate ledger, duplicate master or cross-module workaround.

---

## 7. Agreements

Two separate agreement types exist.

### 7.1 Customer Agreement

Defines what the business may charge the customer. Proven commercial vocabulary includes:

- Daily or Monthly rental basis;
- Non-AC / Front-AC / Dual-AC context/rates where applicable;
- included/free kilometres;
- excess-kilometre rate/policy;
- driver salary recovery;
- Normal / Double / Triple overtime rates;
- night-out charge;
- deposit requirement where configured;
- currency/tax context;
- effective period.

### 7.2 Owner Agreement

Defines what the business may owe an external owner/supplier. Owner rates and reimbursements are independent from Customer rates. Proven concepts include:

- base rental payable;
- included/excess kilometre terms where applicable;
- driver-related reimbursement where applicable;
- overtime/night-out reimbursement where applicable;
- approved fuel/repair/deduction concepts;
- currency/tax/withholding context where explicitly configured;
- effective period.

### 7.3 Agreement lifecycle

Canonical production lifecycle:

```text
Draft → Active → Closed
```

Production rules:

- Draft commercial terms may be edited with optimistic concurrency.
- Active commercial terms are immutable.
- Future commercial changes use successor/revision lineage rather than mutating history.
- Activation validates party, currency, dates, commercial context and overlap/cutover rules.
- Closure stops new coverage after the effective closure boundary without rewriting historical periods.
- Every financial calculation retains the exact effective agreement revision.
- Successor lineage is one-way and scope-safe; redundant inverse state is not required.
- A successor Draft does not silently terminate an active predecessor.

---

## 8. Vehicle source, selection, custody and replacement

Normal UI:

```text
Open active agreement → Select vehicle → enter period/driver details → Save
```

Backend integrity may maintain effective-dated source/use records, but that complexity should stay behind the operator workflow.

### 8.1 External owner vehicle

Customer use must resolve valid owner-supply coverage where external supply is required.

Planning eligibility and actual operational eligibility are distinct concerns. AutoERP currently uses civil-date planning rules and exact operational timestamp rules where those policies are explicitly defined.

### 8.2 Company-owned vehicle

No artificial Owner Agreement or Owner payable is manufactured.

### 8.3 Required controls

- reject overlapping physical use of the same vehicle;
- reject cross-tenant/cross-org relationships;
- enforce agreement-period coverage;
- enforce external owner/source coverage when required;
- reuse shared Vehicle / Vehicle-Service availability contracts;
- lock concurrent use/replacement changes deterministically;
- preserve replacement predecessor/successor lineage;
- replacement alone does not create a second base rent, surcharge or credit;
- workshop/off-road status alone does not create an automatic downtime deduction.

Insurance and revenue-licence records remain Vehicle-owned documents and are not universal Rental assignment blockers without an explicit Rental policy.

---

## 9. Handover, return and replacement

Operational events are distinct from planning:

- planned start/end describe intended coverage;
- handover records actual custody start;
- return records actual custody end;
- replacement closes or supersedes physical vehicle use and opens traceable replacement lineage;
- odometer and timestamp evidence must remain consistent;
- genuine concurrent updates are rejected rather than silently overwritten.

A replacement may preserve the same driver. Linked source/use records must be distinguished from unrelated overlapping assignments so valid source linkage does not create false overlap rejection.

---

## 10. Running Chart

### 10.1 Purpose

The Running Chart is the central operational evidence record shared by the customer and owner commercial sides.

### 10.2 Core evidence

Where applicable it records:

- actual start/end timestamps;
- start/end odometer;
- total/commercial/garage distance as separate facts;
- vehicle identity and frozen use/source context;
- AC mode: Non-AC / Front-AC / Dual-AC;
- driver identity/evidence;
- Normal / Double / Triple overtime minutes;
- night-out count;
- notes and correction lineage.

### 10.3 Lifecycle

```text
Draft → Finalized → Reversed / Corrected
```

Do not introduce extra Submit/Verify/Approve stages unless explicit business evidence requires them.

### 10.4 Finalization rules

- end time must not precede start time;
- odometer values must be physically valid and continuity-safe;
- required vehicle/use context must be valid;
- authoritative driver overlap must be prevented;
- finalization freezes operational evidence;
- corrections create traceable lineage rather than mutating finalized facts.

Physical custody overrun may remain auditable evidence even when commercial entitlement does not extend beyond agreement coverage. Physical truth must not be rewritten to make billing succeed.

---

## 11. Customer calculation

Customer calculation consumes finalized physical evidence under the Customer Agreement revision only.

Conceptual component model:

```text
Base rental
+ excess-kilometre charge
+ driver salary recovery
+ Normal OT
+ Double OT
+ Triple OT
+ night-out
+ explicit approved customer recoveries/adjustments
- explicit approved credits/discounts
+ Tax-owned tax result
= Customer Invoice amount
```

This is a component model, not permission to apply every component automatically.

Rules:

- no Owner rate may feed Customer calculation;
- no implicit AC-mode fallback;
- no duplicate same-side consumption;
- calculation records the exact source chart/use/agreement revision;
- cancelled/reversed calculation releases its governed source consumption only through explicit lineage;
- Invoice creation is delegated to the Invoice owner module.

---

## 12. Owner calculation and settlement

Owner calculation consumes the same physical evidence independently under the Owner Agreement revision.

Conceptual model:

```text
Owner base rental payable
+ proven excess-kilometre payable
+ proven driver/OT/night-out reimbursement
+ explicit approved reimbursements
- explicit approved fuel/repair/damage/other deductions
± governed adjustments
+ Tax/withholding result owned by Tax/Payment policy
= Owner Payable Voucher / Settlement
```

Rules:

- Customer revenue is never the source of Owner payable;
- Customer invoice status must not block Owner settlement;
- one side's source consumption does not consume the other side;
- Owner settlement is handed off to the AP/Invoice owner module;
- payment/allocation is owned by Payment;
- posted financial history is immutable.

---

## 13. Base rent and proration

TACGL/video evidence proves Daily and Monthly business concepts, but does not prove every edge-case proration convention.

AutoERP production policy currently uses named policy `actual_calendar_days_v1` for deterministic base-rent treatment.

Policy requirements:

- explicit base rate required; blank is not zero;
- Daily uses civil-day calculation;
- Monthly uses anniversary-cycle actual-day proration;
- original anchor is preserved across short months;
- cumulative allocation must allow adjacent partial segments to reconcile to the full cycle;
- overlapping retained base-charge periods are rejected;
- Customer and Owner base charges are independent;
- no hidden fixed-30 divisor is inferred from legacy precedent.

This is **Production policy**, not a claim that TACGL proves a universal calendar formula.

---

## 14. Mileage policy

Keep physical and commercial mileage distinct.

- Odometer delta is physical evidence.
- Garage mileage is physical evidence unless an explicit commercial policy says otherwise.
- Automatic pricing uses the configured commercial mileage policy.
- Included/free kilometres are evaluated independently on Customer and Owner sides.
- Customer allowance must never be reused as Owner allowance.
- Cycle-based pooling/reset must be explicit and reproducible.
- Customer replacement charts may share the configured customer allowance cycle where the named policy defines that behavior.
- Owner pool remains independent and tied to Owner Agreement.
- Same-side mileage duplicate consumption is rejected.
- Zero-cost assessment may still consume allowance where the policy explicitly defines consumption.
- No hidden maximum-kilometre, carry-forward or fallback rule may be inferred from one historical example.

---

## 15. OT, night-out, AC and driver monetary treatment

### Overtime

- Normal/Double/Triple categories are separate facts.
- Runtime does not infer which OT category applies from clock time unless an explicit policy is configured.
- Monetary calculation requires an explicit matching rate.
- Minute/hour conversion uses a named constant, not a hidden threshold.

### Night-out

- Night-out is an explicit count/evidence concept.
- Runtime does not infer night-out automatically from timestamps.
- Monetary effect requires an explicit rate/policy.

### AC mode

- Non-AC / Front-AC / Dual-AC are proven business concepts.
- There is no implicit mode-to-rate fallback.
- Recording AC evidence does not itself authorize an automatic charge without an explicit component policy.

### Driver salary / compensation

- Agreement driver amounts are commercial terms.
- Employee payroll/compensation remains HR-owned.
- Rental must not invent an employee-pay formula from customer recoveries or owner reimbursements.

---

## 16. Deposits, receipts, owner payments and financial ownership

- Security deposit requirement must be explicit; there is no universal deposit amount.
- Customer deposit receipt uses Payment ownership.
- Applied/refunded/unapplied balance comes from Payment source of truth.
- Duplicate balance spend is prevented through Payment controls.
- No automatic deposit forfeiture is inferred from missing return/damage evidence.
- Customer Receipt remains Payment-owned.
- Owner Payment remains Payment/AP-owned.
- Cheque/payment instrument lifecycle remains Payment-owned.
- Bank reconciliation remains Finance-owned.
- Rental does not implement a second cash, cheque, payable or bank ledger.

---

## 17. Adjustments, deductions and exception policy

TACGL and the videos show concepts such as fuel, repair and other owner-side deductions/recoveries. They do **not** prove that every such event is automatically chargeable or deductible.

Therefore:

- fuel/repair/damage/accident/insurance-excess/meal/highway/parking/miscellaneous concepts are possible adjustment purposes, not automatic entitlements;
- no generic auto-billable `other_charge` bucket should manufacture money;
- an exceptional financial adjustment requires explicit source evidence, side, approved amount, reason and tax treatment;
- posted adjustments use the appropriate financial owner module;
- original Rental agreement/rate/source snapshots are never rewritten to simulate a deduction or credit;
- replacement and downtime must not double-apply the same incident automatically.

---

## 18. Tax, withholding and Finance

- Tax percentages are not hardcoded in Rental.
- Tax applicability, effective dates and rounding remain Tax-owned.
- Owner withholding remains Tax/Payment-owned and effective-dated.
- Rental does not evaluate statutory aggregate thresholds independently per vehicle/branch/invoice.
- Finance semantic posting profiles are used; TACGL GL account numbers are not copied as magic values.
- Customer revenue and owner cost use independent semantic posting directions.
- Accounting-period, journal and reversal controls remain Finance-owned.

TACGL accounting structures prove the existence of accounting integration, not a requirement to reproduce legacy account codes or repair procedures.

---

## 19. States and valid transitions

### Agreement

```text
Draft → Active → Closed
```

Invalid examples:

- silently mutating Active commercial terms;
- reopening history by changing predecessor rates in place;
- using an expired/closed agreement for new commercial coverage.

### Vehicle Use

Conceptual operational progression:

```text
Planned → Active/Handover → Returned
                   ↘ Replacement lineage
```

The exact storage model may differ, but physical overlap, source coverage and lineage invariants must hold.

### Running Chart

```text
Draft → Finalized → Reversed/Corrected
```

### Financial documents

Financial lifecycle is owned by Invoice/Payment/Finance. Rental must not duplicate mutable financial status columns as a second source of truth.

---

## 20. Validation and integrity rules

### Scope

- current tenant is authoritative;
- organization scope is authoritative where applicable;
- client input cannot override trusted tenant/org/user context;
- cross-tenant/cross-org references are rejected.

### Relationships

- users select human-readable Customer/Supplier/Vehicle/Driver values;
- raw foreign keys are never manually typed in normal UI;
- APIs return meaningful related summaries rather than isolated IDs where practical.

### Time and concurrency

- concurrency-sensitive transitions require expected row/version semantics;
- deterministic lock order is used for multi-row physical operations;
- tenant/org commercial-calendar policy is used for civil-date decisions;
- exact operational timestamps remain exact for custody/overlap/Running Chart evidence;
- a later configuration change must not reinterpret already recorded immutable commercial boundaries.

### Financial boundaries

- no automatic Rental financial handoff may create customer or owner money outside effective agreement coverage;
- chart-based OT/night-out charges freeze the configured tenant/org business timezone and inclusive source supply dates at first creation; a Running Chart end instant is exclusive, and later timezone changes cannot reinterpret reissues;
- physical evidence outside commercial coverage may remain auditable without becoming billable/payable;
- same-side source consumption is idempotent and duplicate-safe.

---

## 21. Legacy mechanisms that must not be copied

The legacy application is rich in business knowledge but weak as an architecture template.

Reject these mechanisms:

- direct agreement-to-vehicle binding without durable use/source history;
- same vehicle being logically available to overlapping customers;
- posted invoice/payment destructive edit/delete;
- customer billing derived from owner economics or vice versa;
- duplicate owner/leasing-company workflows for the same business meaning;
- raw customer/vehicle/GL codes exposed as operator workflow;
- numeric user levels or password-register design;
- invalid states created first and repaired later by mismatch/allocation procedures;
- copied TACGL account numbers as AutoERP magic values;
- compatibility patches that revive removed Rental code.

Preserve the **business meaning**, not the legacy implementation defects.

---

## 22. Reporting and traceability

Required business traceability includes:

- vehicle-use register/detail/history;
- Running Chart register/detail/history;
- Customer and Owner Agreement history;
- immutable Rental charge/settlement calculation breakdown and source references;
- Invoice/AP source allocation back to Rental source;
- Customer/Owner/Vehicle views where appropriate;
- customer/owner balances, receipts/payments, tax, journals and bank reconciliation through their owner modules;
- profitability/margin from independent customer revenue and owner cost, never from guessed owner cost.

Legacy report names and layouts are supporting evidence for information needs, not a requirement to copy old screens or report engine structure.

---

## 23. Permissions and API behavior

Permissions are separated by responsibility and commercial side. At minimum the implementation must distinguish:

- Customer Agreement view/manage;
- Owner Agreement view/manage;
- Vehicle Use/custody view/manage;
- Running Chart view/manage/finalize/reverse;
- Customer billing authorization;
- Owner settlement authorization.

Deposit, receipt, payment, tax and finance authorization remain delegated to the modules that own those financial responsibilities.

API rules:

- use trusted tenant/org context;
- use controlled lookups for related data;
- return human-readable related summaries;
- reject stale expected versions;
- return clear validation/conflict messages;
- never accept client-declared posted financial truth that can be resolved server-side.

---

## 24. User-experience contract

The business videos demonstrate a practical, task-oriented system. AutoERP should preserve that simplicity while hiding stronger integrity controls behind it.

Principles:

- speed and clarity over information overload;
- agreement-first workflow and navigation: Owner Agreements where external supply is needed, Customer Agreements, Vehicle Use, then Running Charts;
- compact forms, not giant technical wizards;
- Running Chart as a fast operational entry/review surface;
- customer and owner financial panels clearly separated, with Customer Invoice terminology on the customer side and Owner Payable Voucher / Owner Settlement terminology on the owner side;
- frontend direct routes enforce the same view permissions that govern Rental navigation visibility;
- destructive or corrective lifecycle actions use explicit action wording and a danger treatment distinct from ordinary workflow actions;
- human-readable labels instead of raw IDs/codes;
- shared status, loading, empty-state and disclosure patterns so operators can distinguish data state from financial state;
- operator-facing money and quantity values use shared presentation formatters; storage/calculation precision must not leak into the UI while exact backend values remain unchanged;
- required operational evidence such as handover time or replacement reason disables the transition action until present instead of relying only on a server rejection;
- register filters are explicitly resettable without mutating business data;
- immutable Agreement, Vehicle Use and Running Chart history must present the business evidence already supplied by the backend using the same labels/formatters as current-state screens;
- failed reloads must not leave stale rows visible as though they are current;
- configured business-local wall times must resolve to exactly one instant; nonexistent or ambiguous DST wall times are rejected rather than guessed;
- server validation remains authoritative, but actionable field errors are shown beside the exact operator input that must be corrected;
- narrative business evidence such as notes, revisions, reversals, replacements and void reasons uses a multiline control rather than a narrow code/value input;
- operational timestamp entry, edit-value conversion and display use the Configuration-owned organization/tenant business timezone; Rental must not maintain a browser-timezone copy of that setting;
- read-only operational/audit timestamps use the shared business-time formatter rather than exposing raw API ISO values;
- no unnecessary approval ceremony;
- technical allocation/history data visible only where it helps the user understand or resolve a real business issue;
- destructive-looking actions must reflect true domain lifecycle, not generic CRUD.

The 2026-10-02 UI/UX finalization, its second end-to-end completion audit, the 2026-10-04 business-timezone finalization, and the 2026-10-05 production UI/UX finalization apply this contract without changing any commercial rule, calculation, relationship or module ownership. See [Vehicle Rental UI/UX finalization](changes/2026-10-02-vehicle-rental-ui-ux-finalization.md), [Vehicle Rental end-to-end UI/UX audit completion](changes/2026-10-02-vehicle-rental-ui-ux-end-to-end-audit-completion.md), [Vehicle Rental business-timezone UI finalization](changes/2026-10-04-vehicle-rental-business-timezone-ui-finalization.md), and [Vehicle Rental UI/UX production finalization](changes/2026-10-05-vehicle-rental-ui-ux-production-finalization.md).

---

## 25. Ambiguous, incomplete or unproven rules

The following are **not** safely derivable as universal TACGL rules and must not be guessed:

1. universal partial-month proration formula;
2. a fixed 30-day monthly divisor;
3. free-KM pooling across every replacement or arbitrary period;
4. replacement-day double charging;
5. universal downtime credit/deduction formula;
6. universal fuel/repair/damage deduction priority;
7. universal security-deposit application priority;
8. universal automatic deposit forfeiture rule;
9. universal tax/withholding percentage or threshold;
10. automatic AC surcharge from AC evidence alone;
11. automatic OT category inference from clock time;
12. automatic night-out inference from timestamps;
13. automatic financial effect of every miscellaneous legacy charge type.

Where AutoERP needs deterministic behavior, it must use a named production policy documented explicitly. Otherwise the monetary effect is absent until a governed business rule is provided.

---

## 26. Current AutoERP production-policy decisions

These are implementation policies, not claims that TACGL universally proves them:

- agreement lifecycle: Draft → Active → Closed;
- commercial revisions use successor lineage rather than in-place mutation;
- `actual_calendar_days_v1` is the named base-rent proration policy;
- tenant/org localization timezone is the commercial civil-day source of truth;
- Vehicle Use planning may use explicit civil-date eligibility while actual handover/replacement uses exact timestamps;
- Running Chart lifecycle is Draft → Finalized → Reversed/Corrected without extra unproven approval stages;
- finalized chart-based financial source periods are resolved in tenant/org business time, store an inclusive last covered civil date from the exclusive chart end, and are frozen in the immutable charge calculation;
- same-side source consumption is duplicate-safe;
- customer and owner calculations remain independent;
- posted financial records remain owned and immutable under Invoice/Payment/Finance rules;
- Invoice, Payment and Finance freeze an explicit accounting exchange rate for foreign-currency transaction evidence; no market rate is guessed;
- an explicit tenant-base-currency document/journal, or a financial record with no explicit currency, uses exchange rate `1.000000`;
- Finance preserves transaction-currency debit/credit separately from frozen-rate base-currency debit/credit; consolidated accounting projections use the base amounts;
- a Payment created to settle an Invoice requires the Payment-date accounting exchange rate to be entered/verified independently; the Invoice's earlier exchange rate is not silently reused;
- Payment owns unapplied-balance allocation and instrument settlement; instrument transitions remain server-authoritative and use the real business event date;
- consolidated management reporting uses source-owned frozen exchange rates before aggregation, while transaction registers retain their own currency/rate and do not publish invalid mixed-currency totals;
- undefined historical tariffs create no automatic financial effect.

---

## 27. Current implementation reconciliation — 2026-10-02

Executed Vehicle Rental runtime baseline: `9f492b5094039522c53b4e7509be296217b2d5b9`.

Current authoritative branch at this re-verification start: `worktree-0.0.8` at `7601749d35696556b3585a9737cd6bd4ec299c30`.

Comparison result from the executed runtime baseline to that current head:

- current branch is 33 commits ahead of the executed runtime baseline;
- no file under `app/Modules/VehicleRental` changed;
- no file under `resources/js/modules/vehicle-rental` changed;
- no migration file changed after the executed Rental baseline;
- later changes are confined to other owning modules, shared UI/reporting, tests and documentation;
- therefore this audit found no evidence-backed Vehicle Rental runtime, schema, relationship or frontend defect that justifies a production-code change.

The current Vehicle Rental implementation remains the clean fresh module with dedicated constants/data/database/enums/http/models/providers/routes/services/tests structure and continues to rely on owner modules for master and financial responsibilities.

The implementation acceptance ledger at `docs/vehicle-rental/TODO.md` remains closed. It records completed coverage for agreements, successor revisions, vehicle source/use, Running Charts, base rent, mileage, OT/night-out, billing/settlement, deposits, financial handoff, permissions, relationships, UI and verification. It contains no remaining open product-policy TODO item.

Fresh source reconciliation on 2026-10-02 again confirmed that both TACGL ZIPs contain the same 452 normalized business files with identical per-file hashes, and all four video hashes/durations match the registered audit corpus. The protected nested backup still contains 86 encrypted entries and no explicit recoverable credential was found through non-destructive source inspection; no brute-force or credential-reuse guessing is permitted.

### Relationship re-verification

The retained relationships remain directional and responsibility-owned:

- successor Agreement → predecessor Agreement;
- Vehicle Use → Customer Agreement;
- Vehicle Use → optional Owner Agreement/source;
- Vehicle Use → physical Vehicle;
- replacement Vehicle Use → predecessor Vehicle Use;
- Running Chart → Vehicle Use;
- corrected Running Chart → reversed predecessor chart;
- Rental source charge → owner-module financial document through source allocation.

The database enforces unique one-way replacement/correction/successor links, tenant/org-scoped identity where required, frozen agreement/use revision references and restrictive history-preserving deletion behavior. No redundant inverse pointer, circular Rental dependency, duplicate mutable Invoice/Payment status or second Rental financial ledger was found.

### Audit conclusion

This pass is a **current-head production re-verification**, not a speculative code-change batch.

No legacy Rental code was restored or reused. No compatibility patch was introduced. No unproved business rule was converted into money. No runtime/schema relationship change is justified by the available evidence.

Current authoritative external guidance was also rechecked only for ownership/integrity boundaries: IFRS 16 remains accounting guidance rather than an operational tariff source; Sri Lanka IRD VAT/WHT rules remain effective-dated Tax/Invoice/Payment concerns; and MySQL/InnoDB locking guidance remains consistent with the module's transactional lock discipline.

### 2026-10-05 final source-period correction

A later end-to-end review found one narrow runtime defect after the 2026-10-02 re-verification: OT/night-out usage charges stored the Running Chart's exclusive end date directly as an inclusive charge `period_until`. Exact-midnight chart boundaries could therefore extend the Invoice supply period by one civil day, and reissue did not have a frozen business-time supply period in the immutable calculation snapshot.

The correction keeps responsibility inside Vehicle Rental:

- `RentalCalendar` is the single conversion boundary for exact chart interval → configured business-calendar inclusive supply dates;
- first charge creation freezes `timezone`, `supply_from` and `supply_until` in the immutable calculation;
- charge `period_from` / `period_until` use the same resolved dates;
- `RentalChargeDocuments` reuses the same calendar rule for chart-period fallback;
- reissue uses the frozen source period, so later workspace-timezone changes cannot reinterpret it;
- mileage is unchanged because its named calendar-cycle policy already freezes its own timezone and supply period.

No schema, relationship, API shape, tax/withholding rule, account mapping or commercial formula changed. See [the append-only correction record](changes/2026-10-05-vehicle-rental-usage-supply-period-correction.md).

### 2026-10-05 financial-owner production completion

A continuation end-to-end review found that the fresh Rental module's financial handoff exposed owner-module gaps in Finance, Payment and Reporting rather than a missing Rental relationship or duplicated Rental ledger.

The production completion release therefore fixes the responsibilities at their sources:

- Finance keeps immutable transaction-currency journal/ledger evidence and explicit frozen-rate base-currency ledger amounts; account balances, trial balance, statements, Finance cash flow, budget actuals and bank reconciliation consume base-currency facts.
- Invoice, Payment and Finance reject invalid tenant-base-currency exchange rates instead of allowing a bad document to survive until a later posting step.
- Payment exposes a least-privilege usable-method lookup, explicit Payment-date FX entry for Invoice settlement, governed allocation of posted unapplied balances, and server-authoritative instrument settlement with an explicit business event date.
- Rental deposit entry remains a thin Payment consumer; Rental does not gain a payment-method master, allocation table or instrument state machine.
- Reporting converts source-owned Invoice/Payment amounts with exact decimal arithmetic before consolidated aggregation, keeps transaction registers currency-aware, uses Configuration-owned business time for dashboard/generic aging, and sends supplier-payables drilldown to the canonical inbound Invoice population.
- No Rental schema or relationship change was justified after reviewing ownership, dependencies, history, and data-integrity impact.

This is a clean owner-module foundation change, not a compatibility patch. No old Rental runtime was restored and no unsupported tax, tariff, FX market rate, GL account or protected-backup rule was invented. See [the append-only financial foundation release record](changes/2026-10-05-vehicle-rental-financial-foundation-production-release.md).

---

## 28. Testing and verification policy

Only commands actually executed may be described as passed.

The latest dependency-backed local verification supplied on 2026-10-02, immediately before the final Vehicle Rental UI/UX delta, recorded:

- Laravel backend: **881 tests / 9,697 assertions passed**;
- TypeScript typecheck passed;
- ESLint passed with no errors or warnings;
- Vite production build passed with 693 transformed modules;
- frontend Vitest: **101 / 101 test files passed**, **374 / 374 tests passed**.

That backend run includes the Vehicle Rental agreement, successor/cutover, authenticated journey, base-rent, mileage, OT/night-out, deposit, Running Chart, driver, odometer, commercial-coverage, Vehicle Use and replacement test families.

The 2026-10-02 UI/UX deltas and the 2026-10-04 business-timezone finalization are frontend/shared-presentation/test/documentation deltas on top of that executed baseline. Focused regression tests cover interaction, immutable-history, stale-data, validation-feedback, configured business-time conversion/display and period-entry guards. The 2026-10-04 shared business-time algorithm also passed isolated runtime verification for Asia/Colombo, New York standard/DST offsets, seconds-preserving round-trip, rejection of a nonexistent DST wall time, and rejection of an ambiguous DST fall-back wall time. The follow-up verification correction also removed a duplicated focused-test import that would otherwise trigger TypeScript `TS2300 Duplicate identifier`. A complete post-change dependency-backed application rerun still cannot be claimed because the audit environment has no full repository dependency tree and hosted GitHub Actions remain excluded by the free-tools-only instruction.

The earlier integrated cross-engine verification additionally recorded:

- SQLite backend verification passed;
- MariaDB 10.11.7 / InnoDB backend verification passed;
- SQLite and MariaDB clean install, baseline upgrade, rollback/reapply and fresh seeding passed;
- 238-table fresh/upgrade/rollback schema metadata parity passed on both engines;
- foreign-key/integrity and final source/conflict review passed.

The 2026-10-05 usage-supply-period correction is a narrow Vehicle Rental runtime/test delta: RentalCalendar owns exclusive-end to inclusive-civil-period conversion, OT/night-out charges freeze that resolved timezone/period, and the Invoice handoff reuses the same policy. It changes no migration/schema, relationship, API shape, tax/withholding rule, account mapping or commercial formula. Focused regression source coverage was added for exact-midnight supply end and timezone-stable reissue. A fresh dependency-backed run of that new test is not claimed because the current container cannot obtain the repository dependency tree. The repository's automatic CI attempts on this head and earlier known-green heads terminate before any job step executes, so they do not provide application-test results.

The 2026-10-05 financial-foundation release adds focused regression source coverage for frozen-rate base-currency ledger conversion, base-currency exchange-rate guards, foreign-currency bank reconciliation, dated Payment instrument settlement, distinct Invoice-versus-Payment FX ownership in settlement reporting, explicit settlement FX entry, and least-privilege Rental deposit payment-method lookup. Both new Finance upgrade migration files passed local PHP syntax lint, and the new Payment allocation/settlement TSX components passed focused TypeScript transpile syntax checks. A fresh full dependency-backed Laravel/Vitest/typecheck/ESLint/Vite/migration run is not claimed because normal repository checkout remains blocked by environment DNS and no complete dependency tree is locally available. No GitHub Actions or paid verification service was used.

A later dependency-backed local verification of release head `ed0c85da38ea83bf64b654b75ae3579ce6ebe5e1` supplied on 2026-10-05 established concrete release regressions: Laravel **884 passed / 4 failed / 9,723 assertions**, TypeScript typecheck failed with **9** Vehicle Rental type errors, ESLint reported **1 error and 1 warning**, the Vite production build **passed with 697 transformed modules**, and Vitest recorded **405 passed / 8 failed** across **102 passing / 7 failing test files**. The failures were traced to Finance upgrade-migration tenant-boundary declarations, misplaced database-backed Invoice currency validation, one stale report-orientation assertion, a weaker-than-API Agreement currency type, one mileage 422 form-retention defect, lint drift, and stale frontend test assertions/mocks. The correction is recorded in `changes/2026-10-05-vehicle-rental-release-verification-fixes.md`. A fresh post-correction dependency-backed full run is still required before this exact correction head may be described as fully green for deployment; no unexecuted pass is inferred from the source fixes.

Future runtime changes must verify, as applicable:

- focused Vehicle Rental tests;
- full Laravel suite;
- MySQL/InnoDB suite when database behavior changes;
- migration upgrade/rollback behavior when schema changes;
- TypeScript typecheck;
- ESLint;
- frontend tests;
- production build;
- database FK/index integrity;
- tenant/org isolation;
- critical customer/owner financial handoff;
- human browser smoke/UAT for agreement → vehicle → Running Chart → billing/settlement.

Real production readiness additionally requires operational evidence such as deployment rehearsal, backup/restore, queues/scheduler, mail/storage/cache connectivity, TLS/secrets and user acceptance when those environment responsibilities are in scope.

---

## 29. AI-agent decision rules

Any AI Agent modifying Vehicle Rental must follow this decision sequence:

1. Identify the business question and owner module.
2. Search this knowledge base and the TACGL/video evidence class for the rule.
3. If explicit evidence exists, preserve the business meaning.
4. If only a legacy mechanism exists, do not copy it automatically.
5. If a runtime integrity rule is required, choose the narrowest rule that preserves the proven meaning.
6. If a named AutoERP production policy already exists, use the single source of truth for that policy.
7. If evidence is insufficient and no production policy exists, do not invent a monetary effect.
8. Keep financial ownership in Invoice/Payment/Tax/Finance.
9. Keep master ownership in Vehicle/Customer/Supplier/HR.
10. Keep the user-facing workflow simple and human-readable.
11. Preserve immutable historical snapshots and traceable correction lineage.
12. Add tests for every new invariant and every resolved ambiguity.
13. Record the evidence class and verification in documentation/change records.

---

## 30. Definition of Done

Vehicle Rental remains functionally complete only while all of the following stay true:

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
11. migrations and relationships remain tenant/org-safe, directional and single-owner for each schema fact;
12. successor/revision lineage remains physically persisted and immutable;
13. Vehicle Use eligibility and Owner-source lookup use the same documented commercial-calendar policy;
14. no automatic Rental financial handoff can create customer or owner money outside effective agreement coverage;
15. future-effective agreement activation uses the configured tenant/org commercial calendar;
16. recorded historical commercial boundaries are not reinterpreted after timezone configuration changes;
17. user-facing workflow remains simple enough to reflect the videos' practical operation;
18. future changes preserve this knowledge base and record actual verification evidence rather than assumptions;
19. transaction-currency evidence and base-currency accounting amounts remain explicit separate facts, with the frozen exchange rate applied exactly once;
20. Invoice settlement never guesses or silently reuses an earlier Invoice exchange rate as the Payment-date rate;
21. Payment remains the single owner of receipt/payment allocation and instrument settlement, including optimistic version checks and explicit business event dates;
22. consolidated financial/reporting totals never add unlike transaction currencies without converting through the source-owned frozen rate.

---

## 31. Final source-of-truth statement

TACGL and the supplied videos define the Vehicle Rental **business meaning**.

`worktree-0.0.8` defines the current AutoERP **implementation**.

`RULES.md` / `AGENTS.md` define the **engineering constraints**.

This document reconciles those authorities without copying legacy design defects, without inventing unsupported business rules, and without moving responsibilities into modules that do not own them.

---
## 32. Final independent source, standards and release audit — 2026-10-06

This pass re-audited the latest authoritative `worktree-0.0.8` source after the October 5 release-verification correction and the latest TACGL source-identity reconciliation.

### Source and implementation result

- The latest supplied TACGL dated ZIP is byte-for-byte equivalent at normalized business-file level to the canonical 452-file TACGL business corpus already audited.
- The four supplied video hashes remain unchanged from the registered evidence set.
- The fresh Vehicle Rental runtime remains the implementation under `app/Modules/VehicleRental`; no removed/legacy Rental runtime was restored, copied or used as a compatibility source.
- The closed acceptance ledger contains no open checklist item.
- Core Rental services/models reviewed in this pass contain no unresolved TODO/FIXME/HACK marker.
- Relationship review found no new circular aggregate or redundant bidirectional business relationship. Successor/correction/replacement lineage remains one-way, while history remains append-only. Cross-module references remain references to canonical master/financial owners rather than duplicated Rental-owned masters or ledgers.
- No evidence-backed runtime, schema, API, relationship, permission, calculation or UI defect was found that justifies creating a production-code delta merely for activity.

### External authoritative corroboration

External research is corroborating context only; it does not override TACGL/video business evidence.

- Current Sri Lanka Inland Revenue material continues to publish effective-dated VAT/WHT/AIT rules and thresholds. Therefore Rental must not hardcode a universal tax or withholding percentage; applicability, effective dates, thresholds and rates remain Tax-owned configuration/policy.
- IFRS 15 requires consideration expected to be refunded, or consideration for which the entity is not yet entitled, to remain a liability rather than revenue. That supports the existing AutoERP policy that Rental security-deposit cash and unapplied customer money remain Payment/Finance-owned liabilities until governed application/refund/forfeiture treatment exists.
- Neither source proves a TACGL-specific replacement-day charge, downtime credit, damage priority, deposit priority, mileage-pool exception or other historical tariff. Those remain governed by the named AutoERP policies already documented here; otherwise no automatic monetary effect is created.

### Protected backup recovery result

The accessible TACGL corpus was re-inspected with free local tooling.

- `tacdata/password.DBF` is readable and contains historical application user/password fields.
- The protected nested backup `DATABACKUP/!   CTACGLDATABACKUP202503271759.rar` contains 86 encrypted entries and no archive comment.
- Only source-derived credential evidence was inspected. No brute force, dictionary attack, arbitrary candidate mutation or unsupported password guessing was performed.
- The available archive backend reports that RAR encryption support is unavailable, so the source-derived historical application password fields cannot be safely validated against the nested archive in this execution environment.
- Backup access is not required by the completed runtime and remains non-evidence for any business rule.

### Verification boundary

The exact application-code head before the documentation-only source reconciliation is `07735be7f253cbcf55de0598cc19f477d41628c8`. Its October 5 correction repaired the concrete failures found by the previous dependency-backed local run at their owning boundaries.

This execution environment still cannot resolve `github.com` from its shell and has no complete Composer/npm dependency tree for AutoERP. GitHub Actions and paid runners are excluded by project instruction. Therefore a fresh full dependency-backed post-correction Laravel/MySQL/Vitest/typecheck/ESLint/build/migration run is **not** falsely claimed here.

This is an execution-evidence limitation, not an identified unfinished Vehicle Rental product requirement. Production promotion must continue to require actual executed release-gate evidence for the exact code head rather than converting static source review into a fabricated green test result.

### Final decision

The Vehicle Rental implementation remains functionally complete under the Definition of Done above. No additional business feature, schema relationship or financial rule is introduced by this final audit. Future changes must continue to be evidence-backed, owner-module scoped and accompanied by real executable verification.
