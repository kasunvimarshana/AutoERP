# AutoERP Vehicle Rental Business Knowledge Base

**Status:** Canonical Vehicle Rental business/domain and production-policy reference for AutoERP.

**Knowledge refresh date:** 2026-09-29

**Primary business source / conflict tie-breaker:** TACGL legacy application/data corpus

**Authoritative practical workflow evidence:** all four supplied Vehicle Rental videos

**Authoritative engineering source:** latest `worktree-0.0.8`

**Authoritative branch head reviewed:** `8b74ff48a1dbf2a07220f5edcb36dabc3086f980`

**Validated runtime baseline:** `2c80584446536fa8b1ebfec4c05adbd129706f45`

**Pre-release completion-audit head:** `890d14bbacaa39b0e271faa3fa83b08ee596b2e6`

**Architecture policy:** root `RULES.md` / `AGENTS.md`

**Implementation acceptance ledger:** [`vehicle-rental/TODO.md`](vehicle-rental/TODO.md)

---

## 1. Purpose and authority

This document is the self-contained Vehicle Rental source of truth for AutoERP. It records the business meaning proven by TACGL and the supplied videos and reconciles that evidence with the fresh Vehicle Rental implementation on `worktree-0.0.8`.

It is not a screen-by-screen legacy specification and is not permission to restore, copy, cherry-pick or revive removed Rental code.

Three rules govern every Vehicle Rental decision:

> **Understand first, verify second, change third.**

> **Do not invent money.** Physical facts may be recorded when the financial consequence is unknown, but no charge, credit, deduction, tax, withholding or payable may be manufactured from an unproved rule.

> **Keep the operator workflow simple; enforce integrity behind the workflow.**

When sources conflict, use this precedence:

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

- `TACGL(20260929-164333).zip`
- SHA-256: `79c240494943437978754169c3360bb7c6e35d911ef8263c4b2d6b6246384d77`
- 452 non-directory business files.

The two ZIP files have different outer packaging but contain the same business payload. After normalizing the canonical archive's outer `TACGL/` wrapper, all 452 relative paths and every per-file SHA-256 are identical. The dated archive therefore adds no conflicting business evidence.

Corroborating package:

- `TACGL.rar`
- SHA-256: `0001a91ab4dfdf31d60f669b30f1ccc2da475d7841b2ceea505ef97eff5bedbf`

The protected nested backup remains unavailable without a valid password. It must not be brute-forced and inaccessible content must not be used as justification to invent hidden rules.

TACGL provides evidence for:

- vehicle, debtor/customer and creditor/owner/supplier masters;
- transaction, invoice, receipt, payment and allocation data;
- cheque, bank and GL structures;
- vehicle/job/service structures;
- charge/type vocabulary;
- report layouts and calculations;
- historical allocation/error/mismatch procedures.

TACGL is a business-evidence source, not an architecture template.

### 2.2 Video corpus

| Video | Duration | SHA-256 | Main evidence |
|---|---:|---|---|
| `1.mp4` | 40:50 | `ac4ca8e632081c32cd2a1d2e6facb070acf4a1f5304a4dc7a468ca7073b953cf` | Customer/owner agreements, Running Chart, customer billing, owner payable, deductions, cheque/payment/reconciliation |
| `Recording 2026-06-21 132314.mp4` | 41:58 | `11866d255dbb709055b43bb7428538a3e2f0858a8ee1d0144187bcdaf4616ffa` | Vehicle/customer registers, agreements, Running Chart, invoice/receipt allocation, owner statements/reports |
| `2.mp4` | 21:14 | `cd2ba1399f149003f19080327458e4bbe4619b88eed9416053c7f8d21431c36f` | Rental transactions/reports, allocation/reconciliation and legacy repair procedures |
| `ScreenVideo_03-04-2026_18-02-52.mp4` | 12:24 | `c9853b7923e7cb95f1014cf598416faa550bfbd56f19da56b613f160d0528ce9` | Workshop / Vehicle Service availability boundary, not Rental pricing |

The four videos total approximately 1 hour 56 minutes 26 seconds. The workshop-focused video supports shared vehicle availability, maintenance and off-road boundaries only; it is not a source for Rental tariffs.

### 2.3 Evidence classes

Every rule belongs to one of these classes:

- **Explicit-TACGL** — directly represented by TACGL data/report/accounting evidence.
- **Explicit-Video** — directly demonstrated in supplied workflow evidence.
- **Cross-source** — supported independently by TACGL and video evidence.
- **Integrity-derived** — the narrowest technical rule required to preserve proven business meaning safely.
- **Observed precedent only** — an actual historical example that is not proof of a universal rule.
- **Production policy** — an explicit safe AutoERP decision adopted because runtime behavior must be deterministic.
- **Legacy mechanism rejected** — historical design behavior that must not be copied.

When evidence is incomplete:

1. preserve the physical/business fact;
2. keep unknown distinct from zero;
3. do not convert one example into a universal formula;
4. do not add hidden fallback rates, thresholds or account codes;
5. use only a named production policy where documented;
6. otherwise create no automatic financial effect and use an explicit governed adjustment if the business later authorizes one.

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

The practical workflow should stay close to the videos:

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

Customer Billing and Owner Settlement are parallel outcomes of the same operational evidence. The diagram is not a strict serial dependency between those two branches.

A company-owned vehicle does not require an artificial external Owner Agreement merely to reuse the external-owner path. No fictitious Owner payable is created unless an explicit Finance policy requires an internal transfer model.

---

## 5. Core parties and terminology

### Customer / Lessee

The party renting the vehicle from the business. Customer-side economic direction is receivable/revenue.

### Owner / Lessor / Supplier

The external party supplying a vehicle to the business. Owner-side economic direction is payable/cost.

The normal operator-facing Owner-side document is **Owner Payable Voucher**, **Owner Settlement** or **Lessor Settlement**. The business meaning demonstrated by the legacy Payment Payable processing must not be presented as a customer-style sales invoice.

### Customer Receipt

Money received from the customer. Do not label this as Customer Payment in the Rental UI.

### Owner Payment

Money paid by the business to the external owner/supplier.

### Running Chart

The Running Chart is physical operational truth, not a financial document. It records the facts from which Customer and Owner calculations may independently derive financial components.

### Vehicle Use / Assignment

The hidden integrity aggregate that records effective vehicle use, custody, source coverage and replacement lineage. The operator does not need a technical allocation wizard for normal agreement-first vehicle selection.

---

## 6. Master-data ownership

Vehicle Rental reuses canonical masters; it must not clone them.

- Vehicle identity/registration/ownership: **Vehicle** module.
- Customer identity/contact: **Customer** module.
- Owner/supplier identity/contact: **Supplier/party** owner module.
- Employee driver identity/status: **HR** module.
- Currency/tax configuration: appropriate **Configuration/Tax** owners.
- Accounts/posting profiles/journals: **Finance** module.
- Invoice/AP documents: **Invoice/AP** owner.
- Receipts/payments/allocations: **Payment** owner.
- Workshop/off-road state: **Vehicle Service** owner.
- Cross-module presentation: **Reporting** where appropriate.

A missing owner-module capability must be fixed in the owner module, not worked around inside Rental.

---

## 7. Agreements

Two separate agreement types exist:

### Customer Agreement

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

### Owner Agreement

Defines what the business may owe an external owner/supplier. Owner rates and reimbursements are independent from Customer rates. Proven concepts include:

- base rental payable;
- included/excess kilometre terms where applicable;
- driver-related reimbursement where applicable;
- overtime/night-out reimbursement where applicable;
- approved fuel/repair/deduction concepts;
- currency/tax/withholding context where explicitly configured;
- effective period.

### Agreement lifecycle

Canonical lifecycle:

```text
Draft → Active → Closed
```

Rules:

- Draft commercial terms may be edited with optimistic concurrency.
- Active commercial terms are immutable.
- Future commercial changes use successor/revision lineage rather than mutating history.
- Activation validates party, currency, dates, required commercial context and overlap/cutover rules.
- Closure prevents new coverage after the effective closure boundary without rewriting historical periods.
- Every financial calculation stores/follows the exact effective agreement revision.

---

## 8. Vehicle source, selection, custody and replacement

Normal UI flow:

```text
Open active agreement → Select vehicle → enter period/driver details → Save
```

Backend integrity may maintain effective-dated source/use records, but users should not be forced through a separate technical allocation workflow unless the business explicitly requires it.

### External owner vehicle

Customer use must resolve valid Owner-source coverage for the vehicle/period where external supply is required.

Planning coverage uses civil-date eligibility where defined by the current production policy; actual handover/replacement enforces operational timestamp integrity and active source coverage.

### Company-owned vehicle

No artificial Owner Agreement or Owner payable is manufactured.

### Required controls

- reject overlapping physical use of the same vehicle;
- reject cross-tenant/cross-org relationships;
- enforce agreement-period coverage;
- enforce external owner/source coverage when required;
- reuse shared Vehicle/Vehicle-Service availability contracts;
- lock concurrent use/replacement changes deterministically;
- preserve replacement predecessor/successor lineage;
- replacement alone does not create an automatic second base rent, surcharge or credit;
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

A replacement may preserve the same driver. The source/use checks must distinguish the linked source from unrelated overlapping assignments so valid source linkage does not create a false driver conflict.

---

## 10. Running Chart

### Purpose

The Running Chart is the central operational evidence record.

### Core evidence

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

### Lifecycle

```text
Draft → Finalized → Reversed / Corrected
```

Do not introduce extra Submit/Verify/Approve stages unless explicit business evidence requires them.

### Finalization rules

- end time must not precede start time;
- odometer values must be physically valid and continuity-safe;
- required vehicle/use context must be valid;
- authoritative driver overlap must be prevented;
- finalization freezes the operational evidence;
- corrections create traceable lineage rather than mutating finalized facts.

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

This is a component model, not permission to apply every component automatically. A component has financial effect only when the agreement/policy proves its entitlement and formula.

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

## 13. Mileage policy

Keep physical and commercial mileage distinct.

- Odometer delta is physical evidence.
- Garage mileage is physical evidence unless an explicit commercial policy says otherwise.
- Automatic pricing uses the configured commercial mileage policy.
- Included/free kilometres are evaluated independently on Customer and Owner sides.
- A Customer mileage allowance must never be reused as the Owner allowance.
- Cycle-based pooling/reset must be explicit and reproducible.
- No hidden maximum-kilometre or fallback rule may be inferred from one historical example.

---

## 14. Driver, overtime and night-out

The videos prove that driver salary, Normal/Double/Triple OT and night-out are meaningful commercial concepts. They do not prove that Rental should derive statutory qualification rules from clock time.

Production boundary:

- Running Chart stores typed OT minutes and explicit night-out count;
- Rental prices those facts only if the relevant agreement/rate permits it;
- HR owns employee compensation rules;
- Rental must not invent payroll salary/proration rules;
- external-driver identity uses stable business reference plus immutable display snapshot where needed.

---

## 15. Deposits

Deposits are financial liabilities/advances, not Rental-local cash balances.

- Rental may define a deposit requirement and link the business context.
- Payment owns receipt identity, application, refund, forfeiture/disposition and reversal.
- No automatic forfeiture is inferred.
- Deposit application priority is explicit Payment policy; do not guess it.
- Received, applied, refunded, forfeited/disposed and remaining amounts must reconcile.
- Wrong party, direction or payment classification must not be reused as a Rental deposit receipt.

---

## 16. Debit notes, credit notes, fuel, repair and damage

Legacy evidence shows these concepts exist, but historical occurrence is not proof of automatic entitlement.

Production rule:

- preserve the underlying fact/evidence;
- require explicit approved financial adjustment when liability/entitlement is not proven automatically;
- create the financial document in its owning module;
- retain source link and audit lineage;
- never hardcode a universal fuel/repair/damage deduction formula without evidence.

---

## 17. Invoice, receipt, payable and payment ownership

### Customer side

```text
Rental calculation → Invoice-owned Customer Invoice → Payment-owned Customer Receipt / allocation
```

### Owner side

```text
Rental calculation → AP/Invoice-owned Owner Payable Voucher → Payment-owned Owner Payment / allocation
```

Rental owns the business source and calculated component snapshots. It does not duplicate mutable Invoice or Payment balances/statuses.

Posted financial documents must not have generic Edit/Delete behavior. Corrections use reversal, credit/debit adjustment or governed reissue according to the financial owner module.

---

## 18. Tax, withholding and Finance

- Tax percentages, withholding thresholds and statutory rounding are not hardcoded in Rental.
- Tax/withholding resolution is effective-dated and delegated to the Tax/Payment owners.
- Finance owns account mappings, posting profiles, journals, accounting periods and bank reconciliation.
- Rental never exposes raw GL IDs in normal operator forms.
- Source-to-journal relationships remain traceable.
- Legacy GL mismatch/error procedures are evidence that accounting integrity mattered; AutoERP should prevent invalid states rather than rely on post-error repair scripts.

---

## 19. Cheques and bank reconciliation

TACGL/videos demonstrate cheque/payment and bank-reconciliation workflows as part of the wider Rental financial lifecycle.

Rental must not implement a parallel cheque engine. It hands financial documents/payments to the canonical Payment/Finance capabilities and reporting surfaces the resulting status.

---

## 20. Reporting requirements

Useful reporting views include:

- Customer agreement/current terms;
- Owner agreement/current terms;
- vehicle use/custody/replacement history;
- Running Chart history;
- Customer invoice/receipt/outstanding balance;
- Owner payable/payment/outstanding balance;
- vehicle-wise revenue/cost/margin analysis without deriving one side from the other;
- Customer statements;
- Owner statements;
- mileage/usage summaries;
- deposit reconciliation;
- cheque/bank-reconciliation status;
- audit/correction lineage.

Historical reports may expose legacy fields, but reports must not become a mechanism for silently repairing invalid production state.

---

## 21. Validation and integrity rules

### Tenant and organization

- every Rental aggregate is tenant scoped;
- organization-unit scope is explicit where required;
- cross-tenant/cross-org foreign relationships are rejected;
- API authorization and database constraints both protect ownership boundaries.

### Concurrency

- writes are atomic;
- mutable aggregates use expected-version / row-version checks;
- deterministic lock ordering is used where multiple shared records are locked;
- stale writes fail explicitly;
- retries must not create duplicate financial documents or duplicate source consumption.

### History

- original transaction values are preserved;
- finalized/posted facts are not overwritten;
- corrections use explicit lineage;
- agreement rate/source revisions remain reconstructable.

### Relationships

- avoid unnecessary bidirectional links;
- use explicit composite tenant/org-safe foreign keys where applicable;
- expose human-readable related objects to the UI rather than raw IDs;
- keep ownership direction clear.

---

## 22. UI/UX contract

User speed and clarity take priority over exposing technical detail.

Rules:

- no raw database IDs;
- searchable human-readable Customer/Owner/Vehicle/Driver selectors;
- no generic Customer-vs-Owner side selector when page context already determines the side;
- agreement-first vehicle selection;
- Draft edit separated from Activate/Close/Supersede lifecycle actions;
- Active commercial terms read-only;
- Running Chart entry fast and table/form oriented;
- uncommon evidence moved to secondary/advanced sections;
- unknown fields may stay blank;
- zero is entered only when known;
- Customer Billing and Owner Settlement shown as independent branches;
- financial document links open owner-module records;
- permissions hide actions the user cannot execute;
- audit/history is available without crowding primary workflows;
- do not create a page for every backend table.

Recommended main navigation:

```text
Vehicle Rental
├── Overview
├── Agreements
│   ├── Customer Agreements
│   └── Owner Agreements
├── Running Charts
├── Customer Billing
├── Owner Settlements
├── Deposits
└── Reports
```

A standalone Assignments/Vehicle Use screen may remain for timeline review, handover, return, replacement and cancellation, but normal assignment starts from the relevant agreement.

---

## 23. Permissions and audit

Permissions should be capability-based, not numeric legacy user levels.

Separate as appropriate:

- view agreements;
- manage Draft agreements;
- activate/close/supersede agreements;
- manage vehicle use/custody;
- create/finalize/correct Running Charts;
- create Customer calculations;
- create Owner calculations;
- view deposits;
- perform Payment/Finance actions only through those modules' permissions.

Every sensitive transition retains actor/time/reason/context as appropriate. Password-register or numeric-level legacy authorization must never be recreated.

---

## 24. Business states and valid transitions

### Agreement

```text
Draft → Active → Closed
```

Successor creation preserves predecessor history and future-effective lineage.

### Vehicle Use / Assignment

Conceptually:

```text
Planned → In Custody / Active → Returned
   └────→ Cancelled
```

Replacement creates linked predecessor/successor use; it is not destructive mutation of history.

### Running Chart

```text
Draft → Finalized → Reversed / Corrected
```

### Calculation

A calculation is an immutable source snapshot once committed to a financial handoff. Cancellation/reissue must release/re-establish consumption explicitly and idempotently.

### Financial documents

Lifecycle belongs to Invoice/AP/Payment/Finance. Rental must not duplicate their state machines.

---

## 25. Explicitly rejected legacy mechanisms

Do not restore or recreate:

- removed old Rental runtime or compatibility layer;
- direct mutable agreement-to-vehicle hard binding without historical use lineage;
- Customer revenue as source of Owner payable;
- same Running Chart/component billed twice on the same side;
- posted Invoice/Payment destructive edit/delete;
- duplicate Owner and leasing-company calculation engines without real commercial difference;
- raw Customer/Vehicle/GL codes in normal UI;
- numeric user levels or Password Register authorization;
- hardcoded legacy rates, taxes, thresholds or GL account numbers;
- post-error allocation/GL repair procedures as the primary integrity model;
- insurance/revenue-licence Rental blockers without explicit Rental policy;
- a user-facing technical workflow merely because a backend integrity table exists.

---

## 26. Historically ambiguous rules and current production decisions

TACGL and the videos do not prove every universal formula. Production behavior must nevertheless be deterministic, explicit and safe.

| Area | Production decision |
|---|---|
| Partial-month proration | Use only the named shipped policy: actual-calendar anniversary-cycle proration; no hidden fixed divisor. |
| Included-KM pooling | Explicit cycle-based Customer and Owner policies, independently evaluated. |
| Replacement charging | Replacement itself creates no automatic surcharge, second base rent or credit. |
| Downtime | Workshop/off-road evidence creates no automatic financial deduction. |
| Garage KM | Physical evidence only by default; automatic mileage pricing uses commercial KM. |
| Accident/insurance excess | Requires explicit approved adjustment; liability is not inferred. |
| Deposit priority/forfeiture | Payment-owned explicit disposition; no automatic forfeiture. |
| Tax | Tax-owned effective-dated configuration; no Rental hardcode. |
| Withholding | Tax/Payment-owned effective-dated policy; no Rental hardcode. |
| AC rates | No implicit AC-mode fallback. |
| OT qualification | Rental stores typed OT minutes; it does not infer category from clock time. |
| Night-out qualification | Explicit count/evidence; no automatic clock-time inference. |
| Driver salary/proration | No invented payroll formula; HR owns compensation. |
| Running Chart approval | Draft/Finalized/Reversed-Corrected only unless new authoritative business evidence requires more. |
| Insurance/revenue licence | Vehicle-owned documents, not universal Rental assignment blockers. |
| Owner vs leasing company | One Owner/Lessor engine unless proven commercial behavior differs. |
| Company-owned vehicle | No artificial Owner Agreement/payable. |
| Miscellaneous recovery | Explicit governed adjustment only; historical occurrence is not automatic entitlement. |

The distinction is important:

> **No automatic effect** does not mean **the business can never apply an explicit governed effect**.

---

## 27. Current AutoERP implementation reconciliation

The fresh `app/Modules/VehicleRental` implementation is present on `worktree-0.0.8`. It does not depend on the removed legacy Rental runtime.

The module contains dedicated responsibilities for:

- agreement lifecycle and validation;
- base-rent billing/preview;
- mileage allowance;
- vehicle use/source/custody/replacement;
- operational time and odometer continuity;
- Running Chart register/lifecycle/validation;
- usage-charge billing;
- Rental authorization;
- Rental-specific deposit linkage/receipt orchestration;
- shared vehicle-use availability integration.

Cross-cutting financial responsibilities remain in their owner modules.

The closed implementation acceptance ledger records completion of:

- separate Customer and Owner agreements;
- tenant/org-safe snapshots and histories;
- optimistic concurrency lifecycle commands;
- successor agreement lineage;
- company-owned and external Owner-source paths;
- agreement-first vehicle selection;
- handover/return/cancel/replacement lineage;
- Vehicle/Vehicle-Service availability integration;
- Running Chart Draft/Finalize/Reverse/Correction;
- odometer and driver integrity;
- named base-rent proration;
- mileage pools/assessments;
- typed OT/night-out pricing;
- independent Customer and Owner calculations;
- Invoice/AP handoff;
- Payment-owned deposit/receipt/payment behavior;
- Tax/Finance ownership;
- guided frontend workflows;
- relationship and concurrency controls.

The release record states that the validated runtime baseline passed the recorded executable gates and later release-candidate commits were documentation-only. Therefore this source reconciliation does not justify inventing new runtime code merely to create a delta.

---

## 28. Verification baseline and future gate

Recorded release evidence for the validated runtime baseline includes:

- Laravel / SQLite: 820 tests / 8,913 assertions;
- frontend Vitest: 88 files / 327 tests;
- TypeScript typecheck;
- ESLint;
- production Vite build;
- fresh SQLite migration/seed;
- rollback/reapply of the September 25 Rental upgrade migrations with foreign-key integrity clean;
- MySQL grammar compilation of changed constraint migrations.

For every future Vehicle Rental change, verify as applicable:

1. tenant/organization isolation;
2. permission boundaries;
3. expected-version/stale-write behavior;
4. foreign-key/uniqueness constraints;
5. migration ownership/order and upgrade path;
6. Customer/Owner independence;
7. historical agreement/source revision correctness;
8. duplicate same-side consumption rejection;
9. reversal/reissue/correction lineage;
10. commercial-calendar consistency;
11. physical custody vs commercial entitlement;
12. SQLite behavior where supported;
13. MySQL/MariaDB behavior where relevant;
14. frontend tests/typecheck/lint/build;
15. authenticated browser/UAT for changed operator flows.

A change record must state exactly which checks were executed. Never claim a test passed when it was only statically reviewed.

---

## 29. AI-agent decision procedure

When deciding Vehicle Rental behavior:

1. Identify the physical event.
2. Identify the financial side, if any: Customer or Owner.
3. Resolve the exact agreement and frozen/effective revision.
4. Resolve the physical source evidence.
5. Resolve the tenant/org commercial calendar where civil dates matter.
6. Use only an explicit named policy/rate or explicit authorized amount.
7. Check same-side source consumption.
8. Check physical vehicle/driver conflicts.
9. Check tenant/org/permission/version constraints.
10. Keep automatic financial coverage inside the applicable agreement boundary.
11. Delegate Invoice/Payment/Tax/Finance behavior to those owner modules.
12. Preserve immutable snapshots and correction lineage.
13. If no automatic financial policy exists, create no automatic money and require an explicit governed adjustment instead of guessing.

---

## 30. Remaining work classification

The implementation TODO is a closed acceptance ledger, not a speculative rebuild list.

### Confirmed business/runtime work already implemented

- agreements and revision lineage;
- vehicle source/use/custody/replacement;
- Running Chart evidence and correction;
- Customer/Owner independent calculations;
- financial handoff and source traceability;
- deposits/payment ownership;
- availability, concurrency, permissions and audit foundations;
- production UI workflows.

### Decision-required only when new evidence/business direction appears

Do not reopen these by assumption:

- a different universal proration convention;
- automatic replacement surcharge/credit;
- automatic downtime deduction;
- automatic garage-KM billing;
- universal accident/insurance liability;
- automatic deposit-forfeiture priority;
- new tax/withholding formulas;
- extra Running Chart approval stages;
- new Owner-vs-leasing commercial engine split.

### Safe next coding rule

Do not change runtime code unless a reproducible defect, authoritative new business rule or owner-module capability gap is identified. Fix that root cause in the owning module with the smallest verified change.

---

## 31. Source-reconciliation result — 2026-09-29

This audit revalidated the exact source set supplied in the current conversation and reconciled it with the latest authoritative implementation branch.

Confirmed:

- `TACGL.zip` SHA-256 and file count;
- `TACGL(20260929-164333).zip` SHA-256 and file count;
- all 452 normalized business files are byte-identical between the two ZIPs;
- `TACGL.rar` SHA-256;
- all four video SHA-256 values and durations;
- the current `worktree-0.0.8` head at audit start: `8b74ff48a1dbf2a07220f5edcb36dabc3086f980`;
- the current branch contains the fresh Vehicle Rental module and the closed acceptance ledger;
- the latest branch release record is documentation-only after the validated runtime baseline;
- no source conflict or evidence-backed unfinished runtime feature was discovered in this revalidation.

This refresh corrects the dated TACGL archive filename in the canonical knowledge base from the earlier packaging label to the exact current upload `TACGL(20260929-164333).zip` while preserving the verified hash/content-equivalence result.

No runtime code, schema, API, permission or frontend behavior is changed by this source-reconciliation update.

---

## 32. Final authority statement

1. TACGL is the primary Vehicle Rental business source and conflict tie-breaker.
2. The four supplied videos are authoritative practical workflow evidence.
3. `worktree-0.0.8` is the authoritative implementation source.
4. `RULES.md` / `AGENTS.md` govern engineering quality, module ownership and maintainability.
5. Never restore or reuse the removed legacy Rental implementation.
6. Never hardcode legacy rates, taxes, GL accounts or unexplained magic business values.
7. Preserve Customer/Owner independence.
8. Preserve physical truth separately from financial entitlement.
9. Preserve historical commercial revisions and financial lineage.
10. Keep the operator workflow simple while enforcing strong hidden backend integrity.
11. When evidence is insufficient, document uncertainty and fail safely instead of inventing a rule.


## 33. Integrated release verification — 2026-09-30

The Selling/worktree integration preserves the fresh Rental runtime and the business policies above. Customer and owner financial workflows continue to use Invoice, Payment, Tax and Finance ownership. Supplier balance presentation now consumes a Supplier-owned contract implemented by Invoice, avoiding a circular module dependency without duplicating balance logic. No removed Rental code or new inferred tariff is introduced.

The merged runtime passed 881 backend tests on both SQLite and MariaDB/InnoDB, and 374 frontend tests. Fresh/upgrade and rollback/reapply schemas match across 238 tables on both engines. See [the integrated release record](changes/2026-09-30-integrated-release-verification.md) for exact scope, known lint warnings, the GitHub billing-related CI startup failure, and the distinction between repository publication and live deployment. These checks do not claim multi-process contention, production-data rehearsal or human UAT.
