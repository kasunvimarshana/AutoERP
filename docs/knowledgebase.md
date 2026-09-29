# AutoERP Vehicle Rental Business Knowledge Base

**Status:** Canonical Vehicle Rental business/domain and production-policy reference for AutoERP.

**Knowledge refresh date:** 2026-09-29

**Primary business source / conflict tie-breaker:** TACGL legacy application/data corpus

**Authoritative practical workflow evidence:** all four supplied Vehicle Rental videos

**Authoritative engineering source:** latest `worktree-0.0.8`

**Runtime implementation baseline reviewed:** `2c80584446536fa8b1ebfec4c05adbd129706f45`

**Architecture policy:** root `RULES.md` / `AGENTS.md`

**Implementation acceptance ledger:** [`vehicle-rental/TODO.md`](vehicle-rental/TODO.md)

**Latest source-reconciliation record:** [`changes/2026-09-29-vehicle-rental-knowledgebase-authority-refresh.md`](changes/2026-09-29-vehicle-rental-knowledgebase-authority-refresh.md)

---

## 1. Purpose

This document is the self-contained Vehicle Rental source of truth for AutoERP. It captures the business meaning demonstrated by TACGL and the supplied videos, then states the clean production policies used by the fresh AutoERP Vehicle Rental module.

It is intentionally not a screen-by-screen copy of the legacy application and is not permission to restore removed legacy Rental code.

The engineering rule is:

> **Understand first, verify second, change third.**

The financial rule is:

> **Do not invent money.** A physical fact can be captured even when its financial consequence is unknown, but no charge, credit, tax, withholding, deduction or payable may be manufactured from an unproved formula.

The UI rule is:

> **Keep the operator workflow simple; enforce complexity behind the workflow.**

---

## 2. Source authority and evidence

### 2.1 TACGL corpus

Canonical upload:

- `TACGL.zip`
- SHA-256: `0e0733fff720072af4c3aaa787995ff128bfa79060a37739d6d2ebbe18a25313`
- 452 non-directory business files.

Dated upload supplied on 2026-09-29:

- `TACGL(20260929-141809).zip`
- SHA-256: `79c240494943437978754169c3360bb7c6e35d911ef8263c4b2d6b6246384d77`
- 452 non-directory business files.

The two ZIP files have different outer packaging, but after removing the canonical archive's outer `TACGL/` wrapper, all 452 normalized relative paths and every per-file SHA-256 are identical. Therefore the dated archive adds no conflicting business evidence.

`TACGL.rar` is corroborating packaging of the same source family. The protected nested backup remains unavailable without a valid password and is not treated as permission to invent hidden behavior.

Important TACGL evidence includes:

- vehicle records and vehicle classifications;
- debtor/customer records;
- creditor/owner/supplier records;
- transaction and invoice data;
- receipt/payment and allocation data;
- bank and cheque data;
- GL/accounting structures;
- vehicle/job/service data;
- charge/type vocabulary;
- report layouts and report expressions;
- legacy integrity/error reports.

TACGL is a business-evidence source, not an implementation template.

### 2.2 Video corpus

| Video | Approx. duration | SHA-256 | Main Vehicle Rental evidence |
|---|---:|---|---|
| `1.mp4` | 40:50 | `ac4ca8e632081c32cd2a1d2e6facb070acf4a1f5304a4dc7a468ca7073b953cf` | Customer/owner agreements, Running Chart, customer billing, owner payable, deductions, cheque/payment and reconciliation |
| `Recording 2026-06-21 132314.mp4` | 41:58 | `11866d255dbb709055b43bb7428538a3e2f0858a8ee1d0144187bcdaf4616ffa` | Vehicle/customer registers, agreements, Running Chart, invoice/receipt allocation, owner statements and reports |
| `2.mp4` | 21:14 | `cd2ba1399f149003f19080327458e4bbe4619b88eed9416053c7f8d21431c36f` | Rental transactions/reports, allocation/reconciliation and legacy repair procedures |
| `ScreenVideo_03-04-2026_18-02-52.mp4` | 12:24 | `c9853b7923e7cb95f1014cf598416faa550bfbd56f19da56b613f160d0528ce9` | Workshop / Vehicle Service availability boundary, not Rental pricing |

The workshop-focused video is supporting evidence for shared vehicle availability, maintenance and off-road state only. It is not a source for Rental tariffs or pricing formulas.

### 2.3 Evidence classes

Every rule should be understood as one of these classes:

- **Explicit-TACGL** — directly represented by TACGL data/report/accounting evidence.
- **Explicit-Video** — directly visible in supplied workflow evidence.
- **Cross-source** — supported independently by TACGL and video evidence.
- **Integrity-derived** — the narrowest technical rule necessary to preserve proven business meaning safely.
- **Observed precedent only** — a real example but not proof of a universal rule.
- **External-research** — legal/accounting/technical support used only inside its scope.
- **Legacy mechanism rejected** — a historical behavior/mechanism that must not be copied into the fresh implementation.

### 2.4 What to do when evidence is incomplete

1. Preserve the observed physical/business fact.
2. Do not convert one historical example into a universal rule.
3. Do not create hidden fallback rates or thresholds.
4. Use a named production policy only when it is explicitly documented.
5. If no automatic financial policy exists, create no automatic financial effect.
6. Route explicit approved corrections/recoveries through the module that owns the financial document.

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
                  Finalized Running Chart
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

1. Customer and Owner agreements are separate.
2. One physical Running Chart may support both commercial sides.
3. Customer billing never derives Owner payable.
4. Owner settlement never derives Customer billing.
5. Processing one financial side does not consume the other side.
6. The same source/component cannot be consumed twice on the same side without governed release/reissue semantics.
7. Finalized physical evidence is immutable except through reversal/correction lineage.
8. Posted financial history is owned by the financial module and is corrected through governed reversal/adjustment mechanisms, not destructive edit/delete.
9. Financial calculations retain the exact applicable agreement/source revision.
10. Unknown values and known zero values are different states.

---

## 4. Core terminology

### Customer / Lessee

The party renting from the business. Customer-side economic direction is receivable/revenue.

### Owner / Lessor / Supplier

The external party supplying a vehicle. Owner-side economic direction is payable/cost.

The normal operator-facing Owner-side document is:

- **Owner Payable Voucher**;
- **Owner Settlement**; or
- **Lessor Settlement**.

Internally, the Invoice/AP owner module may use a purchase/inbound financial aggregate. That internal representation must not turn the operator workflow into a customer-style sales-invoice flow.

### Company-owned vehicle

A company-owned vehicle does not require an artificial external Owner Agreement merely to reuse the external-owner path. No Owner payable/internal transfer cost is manufactured unless an explicit Finance policy is configured.

### Running Chart

The Running Chart is physical operational truth. It is not itself a financial document.

It may contain:

- vehicle/use identity;
- start/end period;
- odometer evidence;
- commercial KM;
- garage KM;
- driver identity/evidence;
- Non-AC / Front-AC / Dual-AC context;
- Normal / Double / Triple OT minutes;
- night-out count;
- remarks and correction lineage.

---

## 5. Canonical operator workflow

The user-facing flow must remain close to the demonstrated videos.

```text
Vehicle / Customer / Owner setup
-> Owner Agreement only for externally supplied vehicle
-> Customer Agreement
-> Select / assign vehicle
-> Handover / custody / optional driver
-> Daily or replacement Running Chart
-> Customer billing and Owner settlement independently
-> Customer Receipt / Owner Payment
-> Tax / Finance / bank reconciliation / reports
```

Preferred contextual UX:

```text
Open Agreement -> Select Vehicle -> Enter period / driver -> Save
```

Backend allocation/history records may exist for integrity, but operators should not be forced through technical allocation wizards, raw side codes, database IDs or duplicated setup pages.

---

## 6. Parties and master-data ownership

### Customer

Customer master identity/contact ownership remains in the Customer module. Rental stores only the Rental relationship and historical snapshots required for audit.

### Owner / Supplier

Supplier/owner master identity belongs to the Supplier/party owner module. Rental stores Owner Agreement and Rental-specific supply context.

### Vehicle

Vehicle owns canonical vehicle identity, registration, ownership context and shared availability capability. Rental must not duplicate the vehicle master.

### Driver

Two evidence modes are supported:

- **Employee driver** — HR employee identity with immutable name/employee-number snapshot.
- **External driver** — stable external reference plus name snapshot.

HR owns payroll, employment lifecycle and compensation. Rental records only who operated the vehicle for the Rental usage.

---

## 7. Customer Agreement

Supported concepts proven by legacy evidence include:

- monthly/daily basis;
- agreed/executing/start/end dates;
- base rental rate;
- included KM;
- excess-KM rate;
- Non-AC / Front-AC / Dual-AC rate context;
- self-drive / with-driver context;
- explicit driver amount;
- Normal / Double / Triple OT rates;
- night-out rate;
- explicit security-deposit requirement;
- notes / explicitly approved recoveries.

The presence of a legacy field proves the concept existed. It does not prove a hidden qualification formula or fallback rule.

---

## 8. Owner Agreement

The Owner Agreement is independent from the Customer Agreement.

Supported concepts include:

- owner/supplier;
- supplied vehicle;
- agreement effective dates;
- independent base rental payable;
- included/excess-KM Owner terms;
- driver reimbursement context;
- OT/night-out terms;
- explicit deductions/adjustments;
- settlement/payment context.

Customer revenue must never be used as the source of the Owner payable amount.

---

## 9. Agreement lifecycle and revisions

### 9.1 States

Core commercial states:

```text
Draft -> Active -> Closed
```

Rules:

- Draft terms are editable with optimistic expected-version checks.
- Activation freezes the effective commercial revision.
- Active terms are not edited in place.
- Closure preserves historical terms and consumed economics.
- New future commercial changes use successor/revision lineage instead of rewriting history.

### 9.2 Commercial end boundaries

Three concepts are intentionally distinct:

- `ends_on` — contractual/effective term end.
- `closed_at` — audit timestamp of lifecycle closure.
- `closed_on` — immutable tenant/org civil closure date captured at closure.

New automatic commercial coverage cannot extend after the stricter of contractual `ends_on` and lifecycle `closed_on`.

The tenant/org civil calendar comes from Configuration-owned `localization.timezone`. Rental must not reinterpret historical civil boundaries later if the workspace timezone changes.

### 9.3 Successor agreement

Future contract changes use a successor Draft:

```text
Active predecessor
-> Create successor Draft
-> Review successor
-> Activate successor
   -> revalidate boundary
   -> close predecessor
   -> activate successor atomically
```

Creating the successor Draft is non-destructive; the predecessor remains Active while the successor is reviewed.

### 9.4 Successor cutover integrity

A successor cannot cut through retained physical/financial history.

At activation:

- Planned vehicle use is evaluated against its planned end.
- Returned vehicle use is evaluated against the actual return.
- In-Custody/open use blocks cutover until actual return exists.
- An elapsed planned return does not prove custody ended.
- An early actual return may free a later planned period.
- A late actual return still blocks an earlier cutover.
- Adjacent half-open use ending exactly at the successor boundary is allowed.

A successor keeps the same counterparty; an Owner successor also keeps the same physical supplied vehicle. A different party/vehicle is a new agreement, not a commercial revision.

The relationship is one-way:

```text
successor -> predecessor
```

No redundant inverse pointer is stored.

---

## 10. Vehicle supply, assignment and custody

Rental owns Rental-specific use/custody/source lineage, not the vehicle master.

Backend integrity must prevent:

- cross-tenant/cross-org relationships;
- wrong Owner source for a vehicle;
- Customer use outside Customer agreement coverage;
- Owner-supplied use outside Owner agreement/source coverage;
- overlapping physical use of one vehicle;
- stale state transitions;
- broken replacement lineage;
- conflicts with shared Vehicle/Vehicle-Service availability.

### 10.1 Planning coverage

Planning coverage is evaluated with tenant/org commercial-calendar semantics.

The system can distinguish planning coverage from actual operational timestamps. Calendar-date eligibility must not be silently replaced by client-offset clock comparisons.

### 10.2 Actual custody

Handover and return are physical evidence and use exact timestamps.

Actual return may occur after a commercial boundary. Recording physical truth does not extend the financial entitlement of an expired agreement.

### 10.3 Company-owned vs external-source vehicle

- External source: valid Owner Agreement/source context is required.
- Company-owned source: no artificial Owner Agreement/payable is created.

---

## 11. Replacement vehicle policy

Replacement is continuation of physical service, not automatically a second unrelated rental.

A replacement alone creates **no automatic**:

- second base-rent charge;
- replacement surcharge;
- Customer credit;
- Owner deduction;
- downtime credit.

Customer base rent remains agreement-period driven.

Owner settlement follows the actual Owner/source and explicit evidence.

Any exceptional replacement recovery/credit requires an explicit approved financial adjustment with source evidence.

This policy closes source ambiguity without inventing a tariff.

---

## 12. Workshop / downtime policy

Workshop, breakdown or off-road state is availability evidence.

It does not by itself authorize an automatic Rental financial deduction or credit.

Vehicle Service owns workshop/service state and exposes availability through its contract. Rental consumes that capability; it must not read/write Vehicle Service internals as a workaround.

---

## 13. Running Chart lifecycle

Core lifecycle:

```text
Draft -> Finalized -> Reversed / Corrected
```

The authoritative sources do not prove a universal `Submit -> Verify -> Approve -> Finalize` ceremony. Therefore the fresh system must not add that workflow unless the business explicitly requires it.

Finalization freezes physical evidence.

Corrections use reversal/correction lineage instead of in-place mutation.

---

## 14. Running Chart integrity

Required controls:

- usage must fit actual physical custody;
- same vehicle usage cannot physically overlap;
- odometer values are monotonic where known;
- blank/unknown is not converted to zero;
- garage KM and commercial KM are different evidence;
- authoritative driver overlap is prevented across vehicles;
- stale expected-version writes fail;
- correction lineage is explicit.

Physical custody can overrun a planned end. The Running Chart must preserve what actually happened.

That physical overrun does **not** silently authorize money outside the effective agreement boundary.

---

## 15. Base rent and proration policy

Historical TACGL/video evidence proves monthly and daily rental concepts but does not uniquely prove one universal partial-month formula.

The named AutoERP production policy is:

`actual_calendar_days_v1`

### Daily basis

```text
inclusive civil days * explicit daily base rate
```

### Monthly basis

- uses the agreement anniversary cycle;
- uses the actual number of civil days in the relevant cycle;
- preserves the original cycle anchor after short months;
- uses cumulative decimal allocation so adjacent partial segments reconcile to a full cycle;
- does not use a hidden fixed 30-day divisor.

This is an explicit AutoERP production policy. It is not a claim that every historical TACGL contract used the same convention.

---

## 16. Mileage policy

Customer and Owner mileage pools are independent.

Inputs:

- explicit commercial KM;
- explicit included-KM allowance;
- explicit excess-KM rate;
- explicit daily/monthly cycle context.

Rules:

- blank is not zero;
- zero allowance is different from unknown allowance;
- `commercial_km` is the automatic financial distance source;
- `garage_km` is physical evidence and has no automatic Rental price;
- no cross-cycle carry-forward unless a future explicit policy says so;
- replacement Running Charts may share the same Customer cycle pool under the named policy;
- Owner pooling stays tied to the Owner Agreement;
- same-side mileage source consumption is protected against duplication;
- zero-cost assessment may still consume allowance;
- dependency-aware reversal is required when later assessments depend on earlier pool state.

---

## 17. Garage mileage

Garage KM is recorded separately because it is operationally meaningful.

It is not automatically included in Customer excess-KM billing or Owner payable mileage.

If a business agreement explicitly makes garage mileage recoverable, the effect must be represented by an explicit named policy or governed financial adjustment. It must not be silently inferred from the physical field.

---

## 18. Overtime

The Running Chart stores already-classified typed minutes:

- Normal OT;
- Double OT;
- Triple OT.

Rental does not infer the category from clock time.

For a typed component:

```text
recorded minutes * explicit matching hourly rate / 60
```

`60` is a unit conversion, not a business qualification threshold.

---

## 19. Night-out

Rental does not infer a night-out from start/end times.

The Running Chart records the explicit night-out count.

Automatic billing/payable uses only:

```text
explicit count * explicit matching rate
```

---

## 20. AC mode

Recorded contexts:

- Non-AC;
- Front-AC;
- Dual-AC.

No implicit rate fallback is allowed.

If a specific AC-mode rate is missing, the system must not silently substitute another AC-mode rate.

The mere existence of an AC field does not prove a universal separate automatic charge; a named pricing component must explicitly consume the evidence.

---

## 21. Driver amount

Agreements can contain explicit driver amounts, but the sources do not prove one universal proration formula.

Therefore:

- HR owns employee compensation/payroll;
- Rental may record driver identity and Rental evidence;
- no hidden daily/monthly/hourly driver formula is invented;
- an explicit Customer recovery or Owner reimbursement not covered by a named Rental policy uses a governed financial adjustment.

---

## 22. Fuel, repair, accident and miscellaneous items

The sources prove that adjustments/deductions can exist for purposes such as:

- fuel;
- repair;
- damage;
- accident;
- insurance excess;
- meal;
- highway;
- parking;
- miscellaneous approved recovery.

They do not prove universal automatic entitlement or formula.

Production rule:

- side must be explicit;
- approved amount must be explicit;
- reason/evidence must be explicit;
- tax treatment must be delegated to Tax/Invoice/AP;
- the original agreement/Running Chart history is not rewritten to simulate the adjustment.

Do not create a generic automatic `other_charge` engine from historical occurrence alone.

---

## 23. Independent Customer and Owner calculations

The strongest business invariant is:

```text
One finalized physical Running Chart
├── Customer calculation — Customer Agreement terms
└── Owner calculation    — Owner Agreement terms
```

### Customer side

Produces Customer receivable/revenue source calculations and hands them to Invoice as Sales/Outbound financial documents.

### Owner side

Produces independent Owner cost/payable source calculations and hands them to Invoice/AP as Purchase/Inbound financial documents.

### Independence

- Customer Invoice does not block Owner Settlement.
- Owner Settlement does not block Customer Invoice.
- The same physical source may support both sides.
- The same source/component cannot be consumed twice on the same side.

---

## 24. Historical agreement revision

Financial calculations use the agreement/source revision applicable to the physical usage.

The current live agreement row must not replace the historical revision used by an already-finalized Vehicle Use / Running Chart.

Historical pricing evidence remains stable even after later commercial revisions.

---

## 25. Commercial coverage guard

Physical truth and commercial entitlement are separate.

A Running Chart may honestly record a real overrun after a planned or commercial boundary.

Before automatic Customer Invoice / Owner Payable creation, the Rental handoff must confirm that the immutable source period remains inside the applicable agreement's effective commercial coverage.

If not:

- retain the physical Running Chart;
- reject automatic Rental money;
- require a valid commercial revision or explicit governed adjustment.

Never reuse an expired rate merely because the vehicle physically remained in custody.

---

## 26. Customer Invoice

Customer Invoice is owned by Invoice.

Rental owns the immutable Rental source calculation and source lineage.

Invoice owns:

- document lifecycle;
- tax snapshot integration;
- balance;
- posting status;
- adjustment/reversal/cancellation semantics;
- source allocation.

Posted/live financial state must not be duplicated into mutable Rental truth.

---

## 27. Owner Payable Voucher / Owner Settlement

The operator-facing Owner document is Owner Payable Voucher / Owner Settlement.

It is not the same as a Customer sales invoice.

Rental owns the immutable Owner-side source calculation.

Invoice/AP owns the actual payable financial document, tax integration, balance and governed correction lifecycle.

---

## 28. Customer Receipt and Owner Payment

Payment owns real money movement.

### Customer

Customer pays the business -> **Customer Receipt**.

### Owner

Business pays the Owner/Supplier -> **Owner Payment**.

Payment owns:

- instrument;
- receipt/payment lifecycle;
- allocation;
- unapplied balance;
- refund/reversal;
- cheque/payment status.

Rental must not build a second cash/payment ledger.

---

## 29. Security deposits

A security-deposit requirement is explicit. There is no universal amount.

Rules:

- null and zero differ;
- a requirement is not a receipt;
- Payment owns the actual inbound Customer deposit receipt;
- Payment owns applied/refunded/unapplied disposition;
- no automatic forfeiture;
- no duplicate spending of one available balance;
- reversal/refund lineage is preserved;
- successor agreements do not silently duplicate the predecessor deposit requirement.

---

## 30. Debit notes, credit notes and corrections

Legacy debit-note/credit-note/miscellaneous flows prove adjustment capability, not automatic entitlement.

Financial correction ownership:

- Invoice/AP -> document adjustment/reversal/credit/debit behavior;
- Payment -> payment/refund/reversal behavior;
- Tax -> tax recalculation/snapshot behavior;
- Finance -> journal/reversal behavior.

Rental retains source lineage and does not duplicate these ledgers.

---

## 31. Tax and withholding

Rental must not hardcode:

- tax percentages;
- statutory thresholds;
- withholding percentages;
- statutory rounding rules.

Tax owns:

- applicability;
- effective dates;
- inclusive/exclusive treatment;
- rounding;
- tax snapshots.

Owner withholding belongs to Tax/Payment for the actual party/payment/statutory period.

Rental supplies semantic source context only.

---

## 32. Finance and GL

Finance owns:

- chart of accounts;
- posting profiles;
- account roles;
- journals;
- accounting periods;
- reversal;
- bank reconciliation.

Legacy TACGL account numbers are evidence of historical accounting lineage, not constants to hardcode into the new module.

Customer revenue and Owner cost must post independently through semantic Finance profiles.

---

## 33. Cheque lifecycle and bank reconciliation

The business distinguishes:

1. payable/receivable creation;
2. payment instrument;
3. allocation;
4. cheque realization/clearance;
5. bank reconciliation.

AutoERP preserves those responsibilities through Payment and Finance. Rental may provide context/navigation but must not create a separate cheque register or bank-reconciliation engine.

---

## 34. Reporting

Rental owns stable operational source records and Rental-specific registers/history.

Core traceability includes:

- Customer Agreement register/history;
- Owner Agreement register/history;
- Vehicle Use / custody / replacement history;
- Running Chart register/detail/correction history;
- immutable Customer/Owner Rental source calculations;
- source references to created financial documents.

Cross-module reporting should read authoritative Invoice/Payment/Tax/Finance data rather than maintain duplicate Rental balances.

Valid profitability/margin analysis must compare independently posted Customer revenue and Owner cost. Never infer Owner cost as a percentage or difference of Customer revenue.

---

## 35. Permissions and security

Permissions are semantic/action-based, not legacy numeric user levels.

Examples:

- Customer Agreement view/manage;
- Owner Agreement view/manage;
- Vehicle Use/custody view/manage;
- Running Chart view/manage/finalize/reverse;
- Customer billing authorization;
- Owner billing/settlement authorization.

Tenant and organization context are trusted server-side execution context. Client-supplied tenant/org IDs cannot override authenticated scope.

Cross-tenant aggregate IDs are treated as inaccessible.

---

## 36. Concurrency and data integrity

Required controls:

- transactions for multi-row state transitions;
- stable lock ordering around shared vehicle/source state;
- optimistic expected-version checks;
- tenant/org-safe foreign keys;
- database uniqueness for successor/source-consumption identities;
- immutable finalized/posted history;
- conflict instead of last-write-wins;
- retry only by re-running the entire command against current state.

Important competing operations:

| Competing operations | Required invariant |
|---|---|
| Two vehicle uses / workshop events | one physical vehicle cannot occupy conflicting periods |
| Two billings on same side/source | one eligible commercial source cannot be consumed twice on the same side |
| Customer vs Owner billing | both may independently consume the same physical source |
| Finalize/reverse vs bill | committed physical source state and financial source state cannot disagree |
| Successor activation vs vehicle use | commercial revision cannot bisect retained use/custody history |
| Two successor creations | one predecessor has at most one direct successor |
| Physical custody overrun vs billing | preserve physical truth; do not automatically bill outside agreement coverage |
| Two driver charts | same authoritative driver cannot overlap across finalized Rental usage |
| Deposit allocate/refund | one available balance cannot be spent twice |

---

## 37. Relationship design

The clean schema intentionally avoids redundant bidirectional state.

### Required relationships

- Customer Agreement -> Customer.
- Owner Agreement -> Owner/Supplier.
- Owner Agreement -> supplied Vehicle where applicable.
- Vehicle Use -> Customer Agreement.
- Vehicle Use -> actual Vehicle.
- Vehicle Use -> optional Owner Agreement/source for externally supplied vehicle.
- Replacement Vehicle Use -> predecessor Vehicle Use.
- Running Chart -> Vehicle Use.
- Running Chart -> optional HR employee driver.
- Successor Agreement -> predecessor Agreement.
- Rental source calculation -> immutable physical/commercial source lineage.
- Invoice source allocation -> Rental source identity.

### Relationships intentionally not duplicated

- no stored predecessor `next_successor_id` inverse pointer;
- no HR -> Rental back-reference;
- no duplicated mutable Invoice/Payment status inside Rental;
- no duplicated GL balances inside Rental;
- no duplicated vehicle master inside Rental.

This preserves high cohesion and avoids circular ownership.

---

## 38. Module ownership

### Vehicle Rental owns

- Customer and Owner Rental agreements;
- successor lineage;
- Rental vehicle-use/custody/replacement lineage;
- Running Charts;
- Rental driver-use evidence;
- Rental component calculations;
- same-side source-consumption orchestration;
- Rental-specific operator UI/orchestration.

### Vehicle owns

Canonical vehicle identity, registration, ownership context and shared availability contract.

### HR owns

Employee identity/status/compensation and HR-specific availability.

### Customer / Supplier modules own

Counterparty master identity/contact data.

### Invoice/AP owns

Financial document lifecycle, balances, adjustment/reversal and source allocation.

### Payment owns

Receipts/payments, instruments, allocation, advances/unapplied balance and refund/reversal.

### Tax owns

Tax/withholding rules, effective dates, snapshots and statutory rounding.

### Finance owns

Accounts, posting profiles, journals, accounting periods and bank reconciliation.

### Vehicle Service owns

Workshop/service/off-road evidence and availability blocker.

### Configuration owns

Tenant/org settings and validated workspace timezone.

### Reporting owns

Cross-module analytical presentation/export where appropriate.

A missing owner-module capability must be fixed in the owner module. Do not compensate with a Rental-local workaround.

---

## 39. UI/UX contract

The interface must optimize speed, clarity and valid task completion.

Rules:

- no raw IDs;
- human-readable searchable Customer/Supplier/Vehicle/Employee selectors;
- no generic side selector when context already determines Customer vs Owner;
- agreement-context vehicle selection;
- Draft edit separated from Activate/Close/Supersede lifecycle actions;
- Active commercial terms read-only;
- Running Chart entry fast and table/form oriented;
- uncommon evidence grouped as secondary detail;
- unknown fields may remain blank;
- zero is entered only when known;
- Customer and Owner billing panels remain independent;
- created financial document links point to owner-module records;
- permissions hide actions the operator cannot perform;
- audit/history is accessible without crowding the primary workflow.

Do not create a separate user-facing page for every backend table.

---

## 40. Rejected legacy mechanisms

Never restore/recreate these patterns:

- raw GL/account code entry on ordinary Rental forms;
- mutable posted invoices/payments;
- Customer revenue as source of Owner payable;
- duplicate same-side Running Chart/component consumption;
- numeric user-level / Password Register authorization;
- post-error repair reports as the primary integrity mechanism;
- duplicate Owner vs leasing-company engines without real commercial difference;
- hardcoded legacy GL account numbers;
- hardcoded legacy rates/tax percentages;
- automatic insurance/revenue-licence Rental assignment blockers without explicit Rental policy;
- removed old Rental runtime via compatibility layer.

The legacy business meaning is preserved. Legacy design defects are not.

---

## 41. Formerly ambiguous rules and production decisions

The historical uncertainty remains documented, but runtime behavior must be deterministic and safe.

| Area | Production decision |
|---|---|
| Partial-month proration | Use only an explicit named policy. Current shipped policy is actual-calendar anniversary-cycle proration; no hidden fixed divisor. |
| Included-KM pooling | Use explicit cycle-based independent Customer/Owner mileage policies. |
| Replacement vehicle charging | Replacement alone creates no automatic surcharge, second base rent or credit. |
| Downtime | Workshop/off-road evidence creates no automatic financial deduction. |
| Garage KM | Physical evidence only; automatic mileage pricing uses commercial KM. |
| Accident / insurance excess | Requires explicit approved adjustment; liability is not inferred. |
| Deposit priority / forfeiture | Payment-owned explicit disposition; no automatic forfeiture. |
| Tax | Tax-owned effective-dated configuration; no Rental hardcode. |
| Withholding | Tax/Payment-owned effective-dated policy; no Rental hardcode. |
| AC rates | No implicit AC-mode fallback. |
| OT qualification | Rental does not derive OT category; chart stores typed minutes. |
| Night-out qualification | Rental does not infer from clock time; explicit count required. |
| Driver proration | No invented universal formula; HR owns compensation. |
| Running Chart approvals | Draft/Finalized/Reversed-Corrected only unless explicit business evidence requires more. |
| Insurance/revenue licence | Not universal Rental assignment blockers. |
| Owner vs leasing company | One Owner/Lessor engine unless real commercial behavior differs. |
| Company-owned vehicle | No artificial Owner Agreement/payable. |
| Miscellaneous recovery | Explicit governed adjustment only; historical occurrence is not automatic entitlement. |

The key distinction is:

> **No automatic effect** does not mean **the business can never apply an explicit governed effect**.

---

## 42. Fresh implementation status

The fresh `app/Modules/VehicleRental` implementation is present on `worktree-0.0.8` and remains independent from the removed legacy Rental runtime.

The closed implementation acceptance ledger records completion of:

- separate Customer and Owner agreements;
- tenant/org-safe snapshots and histories;
- expected-version lifecycle commands;
- persisted successor lineage;
- Customer/Owner commercial revisions;
- agreement lifecycle closure boundaries;
- company-owned and external Owner-source vehicle paths;
- agreement-first vehicle selection;
- handover/return/cancel/replacement lineage;
- Vehicle/Vehicle-Service availability integration;
- Running Chart Draft/Finalize/Reverse/Correction;
- odometer and driver integrity;
- named base-rent proration;
- mileage pools/assessments;
- typed OT/night-out pricing;
- independent Customer and Owner source calculations;
- Invoice/AP financial handoff;
- Payment-owned deposit/receipt/payment behavior;
- Tax/Finance semantic ownership;
- guided frontend workflows;
- relationship and concurrency controls.

### Latest reviewed implementation correction

Runtime baseline reviewed: `2c80584446536fa8b1ebfec4c05adbd129706f45`.

That correction preserves the documented model and tightens implementation details:

- published migration identities remain stable;
- foreign-key drop operations include column context needed by SQLite while retaining named MySQL constraints;
- successor-lineage schema hardening remains Rental-owned;
- successor cutover uses actual custody evidence correctly:
  - Planned -> planned end;
  - Returned -> actual return;
  - In Custody/open -> blocks cutover until actual return.

This is an integrity correction, not a new business tariff or compatibility patch.

---

## 43. Protected backup investigation

Nested protected archive:

`DATABACKUP/!   CTACGLDATABACKUP202503271759.rar`

The archive is password-protected. No explicit valid password was established from accessible source evidence.

Rules:

- do not brute-force;
- do not invent password variants;
- do not infer hidden business rules from inaccessible data;
- do not block implementation solely because protected historical data is unavailable.

The unavailable backup is a source limitation, not authority to guess production behavior.

---

## 44. Source limitations

- The supplied TACGL corpus is authoritative evidence, but it is not proven to contain every executable rule behind every legacy screen.
- The protected nested backup remains inaccessible without a valid credential.
- Visual video evidence is authoritative for demonstrated workflow; spoken-only statements that are not reliably captured/corroborated are not promoted into financial formulas.
- Executable-only hidden logic is not treated as a universal business rule without safe evidence.
- One historical transaction/example does not define a universal monetary policy.

These limitations are handled by explicit named policies and safe non-automatic defaults, not guessing.

---

## 45. Verification contract for future changes

For every future Vehicle Rental change, verify as applicable:

1. tenant/organization isolation;
2. permission boundaries;
3. expected-version / stale-write behavior;
4. foreign-key and uniqueness constraints;
5. migration ownership/order and fresh/upgrade path;
6. Customer/Owner independence;
7. historical agreement/source revision correctness;
8. same-side duplicate-consumption rejection;
9. reversal/reissue/correction lineage;
10. commercial-calendar consistency;
11. physical custody vs commercial entitlement boundary;
12. SQLite behavior where supported;
13. MySQL/MariaDB behavior where relevant;
14. frontend tests/typecheck/lint/build;
15. authenticated browser/UAT for changed operator flows.

A change record must state exactly which checks were executed. Never write “passed” for a check that was only statically reviewed.

---

## 46. AI-agent decision procedure

When deciding Vehicle Rental behavior:

1. Identify the physical event.
2. Identify the financial side, if any: Customer or Owner.
3. Resolve the exact agreement and effective/frozen revision.
4. Resolve the physical source evidence.
5. Resolve the applicable tenant/org commercial calendar where civil dates matter.
6. Use only an explicit named policy/rate or explicit authorized amount.
7. Check same-side source consumption.
8. Check physical vehicle/driver conflicts.
9. Check tenant/org/permission/version constraints.
10. Confirm automatic financial coverage remains inside the applicable agreement boundary.
11. Delegate Invoice/Payment/Tax/Finance behavior to those owner modules.
12. Preserve immutable snapshots and correction lineage.
13. If no automatic financial policy exists, create no automatic money and require explicit governed adjustment rather than guessing.

---

## 47. Final authority statement

1. TACGL is the primary Vehicle Rental business source and conflict tie-breaker.
2. The four supplied videos are authoritative practical workflow evidence.
3. `worktree-0.0.8` is the authoritative implementation source.
4. `RULES.md` / `AGENTS.md` govern engineering quality, module ownership and maintainability.
5. Never restore or reuse the removed legacy Rental implementation.
6. Never hardcode legacy rates, tax percentages, GL accounts or unexplained magic business values.
7. Preserve Customer/Owner independence.
8. Preserve physical truth separately from financial entitlement.
9. Preserve historical commercial revisions and financial lineage.
10. Keep the operator workflow simple while enforcing strong hidden backend integrity.
11. When evidence is insufficient, document the uncertainty and fail safely instead of inventing a rule.

---

## 48. 2026-09-29 source-reconciliation result

This refresh revalidated the uploaded source set and the current authoritative branch.

Confirmed in this session:

- canonical `TACGL.zip` hash;
- dated `TACGL(20260929-141809).zip` hash;
- 452 non-directory files in each ZIP;
- content equivalence of all 452 files after normalizing the outer folder wrapper;
- all four video hashes;
- all four video durations;
- current `worktree-0.0.8` implementation baseline reviewed at `2c80584446536fa8b1ebfec4c05adbd129706f45` before documentation commits;
- existing Vehicle Rental acceptance ledger is closed, not an open speculative TODO list;
- latest implementation correction aligns with this knowledge base and does not introduce a conflicting business rule.

No runtime code, schema, API, permissions or frontend behavior is changed by this knowledge-base refresh.
