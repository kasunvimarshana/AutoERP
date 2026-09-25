# AutoERP Vehicle Rental Business Knowledge Base

**Status:** Canonical Vehicle Rental business/domain and production-policy reference for AutoERP.

**Knowledge refresh date:** 2026-09-25

**Primary business source / conflict tie-breaker:** TACGL legacy application/data corpus

**Authoritative practical workflow evidence:** all four supplied Vehicle Rental videos

**Authoritative engineering source:** latest `worktree-0.0.8`

**Continuation review base:** `8261887e9266ea52ad5fc225c64e4e52dad7563f`

**Architecture policy:** root `RULES.md` / `AGENTS.md`

**Implementation acceptance ledger:** [`vehicle-rental/TODO.md`](vehicle-rental/TODO.md)

---

## 1. Purpose and operating rule

This document is the self-contained Vehicle Rental source of truth for AutoERP. It preserves the demonstrated TACGL/video business meaning while defining the clean implementation contract used by the fresh module. It must not be interpreted as permission to restore or copy the removed legacy Rental code.

The engineering rule is:

> **Understand first, verify second, change third.**

The product rule is:

> **Do not invent money.** A source-observed physical fact can be captured even when its financial consequence is unknown, but no charge, credit, tax, withholding, deduction or payable may be manufactured from an unproved formula.

The production design closes source ambiguity by choosing deterministic safe behavior, not by guessing rates or thresholds. When a contract-specific monetary effect is not represented by a named policy or explicit amount, Rental creates no automatic monetary effect. A separately authorized financial adjustment must use the owning Invoice/Payment/Tax/Finance capability with source evidence and audit lineage.

---

## 2. Source authority and evidence

### 2.1 TACGL corpus

Canonical upload:

- `TACGL.zip`
- SHA-256 `0e0733fff720072af4c3aaa787995ff128bfa79060a37739d6d2ebbe18a25313`
- 452 business files after directory entries are excluded.

The dated `TACGL(20260925-035203).zip` has different archive-wrapper bytes but the same 452 normalized inner paths and SHA-256 file contents. `TACGL.rar` was reconciled to the same business corpus. The dated archive therefore adds no conflicting Rental evidence.

Important structured sources include vehicle, customer/debtor, creditor/owner, charge vocabulary, transaction, invoice, allocation, GL, account and report-expression data. TACGL is evidence for business meaning and historical accounting lineage; it is not an implementation template.

### 2.2 Video corpus

| Video | Approx. duration | SHA-256 | Main Rental evidence |
|---|---:|---|---|
| `1.mp4` | 40:50 | `ac4ca8e632081c32cd2a1d2e6facb070acf4a1f5304a4dc7a468ca7073b953cf` | Agreements, Running Chart, customer billing, owner payable, deductions, cheque/payment, reconciliation |
| `Recording 2026-06-21 132314.mp4` | 41:58 | `11866d255dbb709055b43bb7428538a3e2f0858a8ee1d0144187bcdaf4616ffa` | Party/vehicle registers, agreements, Running Chart, invoice/receipt allocation, owner statement, reports |
| `2.mp4` | 21:14 | `cd2ba1399f149003f19080327458e4bbe4619b88eed9416053c7f8d21431c36f` | Transactions/reports, allocations, cheque/bank reconciliation and repair procedures |
| `ScreenVideo_03-04-2026_18-02-52.mp4` | 12:24 | `c9853b7923e7cb95f1014cf598416faa550bfbd56f19da56b613f160d0528ce9` | Vehicle Service / workshop availability boundary |

The workshop video is supporting evidence for shared vehicle availability, not a Rental pricing source.

### 2.3 Evidence classes

Rules and decisions use these classes:

- **Explicit-TACGL** — directly represented by TACGL records/reports/accounting lineage.
- **Explicit-Video** — directly visible in supplied workflow evidence.
- **Cross-source** — independently supported by TACGL and video evidence.
- **Integrity-derived** — the narrowest technical rule necessary to preserve proven meaning safely.
- **External-research** — an authoritative legal/accounting/technical source used only within its stated scope.
- **Observed precedent only** — a real example that is not a universal contract rule.
- **Legacy mechanism rejected** — a real legacy capability whose implementation pattern must not be copied.

### 2.4 External research boundary

[`vehicle-rental/commercial-research.md`](vehicle-rental/commercial-research.md) records the research basis used when TACGL cannot uniquely determine a production default. Key conclusions remain:

- vehicle-rental products use materially different proration, mileage, replacement and downtime policies;
- therefore one operator's public terms are not AutoERP defaults;
- statutory tax/withholding treatment is effective-dated and party/jurisdiction dependent;
- contract changes must preserve already-consumed historical economics instead of rewriting them;
- database locking must be transaction-scoped and backed by uniqueness/FK constraints where possible.

IFRS 15 contract-modification guidance is supporting accounting context for prospective contract changes, not a replacement for TACGL terms. Sri Lankan IRD circulars/gazettes are supporting statutory context owned by Tax/Invoice/Payment, not hardcoded Rental tariff rules. The 2026-09-25 recheck of official IFRS, IRD and MySQL material did not justify any new Rental tariff, tax percentage or ownership exception.

---

## 3. Canonical business model

Vehicle Rental is a dual-sided operational and financial domain:

```text
Owner / Lessor                         Customer / Lessee
      |                                      |
Owner Agreement                       Customer Agreement
      |                                      |
      +----------------+---------------------+
                       |
                 Vehicle use/custody
                       |
                 Finalized Running Chart
                    /             \
                   /               \
        Customer calculation     Owner calculation
        customer terms only      owner terms only
                 |                    |
        Customer Invoice         Owner Payable Voucher
                 |                    |
        Customer Receipt         Owner Payment
                  \                  /
                   Tax / Finance / Reconciliation / Reports
```

Non-negotiable invariants:

1. Customer and owner agreements are separate.
2. One physical Running Chart may support both commercial sides.
3. Customer billing never derives owner payable.
4. Owner settlement never derives customer billing.
5. Processing one side does not consume the other side.
6. The same source/component cannot be consumed twice on the same side without governed release/reissue semantics.
7. Finalized operational evidence and posted financial history are immutable.
8. Every financial calculation retains the applicable agreement/source revision.

---

## 4. Terminology

### Customer / Lessee

The party renting from the business. Customer-side economic direction is receivable/revenue and Customer Receipt allocation.

### Owner / Lessor / Supplier

The external party supplying a vehicle. Owner-side economic direction is payable/cost and Owner Payment allocation.

The normal owner document is described to the operator as **Owner Payable Voucher**, **Owner Settlement** or **Lessor Settlement**. The Invoice/AP owner module may internally use a purchase/inbound invoice aggregate; that implementation detail must not turn the operator workflow into a customer-style sales invoice.

### Company-owned vehicle

No artificial external Owner Agreement or owner payable is created merely to reuse the externally supplied path. Internal transfer cost is zero/not applicable unless an explicit Finance policy is configured later.

### Running Chart

The Running Chart is physical operational truth. It can hold vehicle, period, odometer, commercial/garage KM, driver identity/evidence, AC context, typed OT minutes, night-outs and remarks. It is not itself an invoice or owner payable.

---

## 5. Canonical operator workflow

```text
Vehicle / Customer / Owner setup
-> Owner Agreement only for externally supplied vehicle
-> Customer Agreement
-> Select / assign vehicle
-> Handover / custody
-> Daily or replacement Running Chart
-> Customer billing and Owner settlement independently
-> Customer Receipt / Owner Payment
-> Tax / Finance / bank reconciliation / reports
```

The UI should remain close to the video workflow. Separate integrity tables/services may exist behind the form, but raw IDs, side codes and database architecture must not be exposed to operators.

---

## 6. Agreements and effective revisions

### 6.1 Customer Agreement

Supported concepts include:

- monthly/daily basis;
- agreement/agreed/executing/start/end dates;
- base rental rate;
- included KM and excess-KM rate;
- Non-AC / Front-AC / Dual-AC rate context;
- self-drive / with-driver context;
- recorded driver amount;
- normal/double/triple OT rates;
- night-out rate;
- explicit security-deposit requirement;
- notes and explicit recoveries.

Visible legacy fields prove the concepts exist; they do not prove hidden fallback or qualification formulas.

### 6.2 Owner Agreement

Supported concepts include owner/supplier, supplied vehicle, independent base/mileage/driver/OT/night-out terms, deductions/adjustments and settlement/payment context.

### 6.3 Draft / Active / Closed

- Draft terms are editable with expected-version control.
- Activation freezes the effective revision.
- Active commercial terms are not edited in place.
- Closure preserves history.
- Manual closure is a lifecycle stop: new commercial coverage, including base rent and mileage allowance/assessment, cannot extend past the closure civil date. If the contract already has an earlier `ends_on`, that earlier date remains the stricter commercial boundary.
- The closure civil date is derived from Configuration-owned tenant/org `localization.timezone`; Rental does not use the process-global Laravel application timezone as a tenant commercial calendar.
- Historical calculations keep their original agreement revision and historical periods through the effective boundary remain billable.

### 6.4 Successor agreement

A future commercial change uses a successor agreement rather than mutating an Active agreement.

Production lifecycle:

```text
Active predecessor
-> Create successor Draft
-> Review/edit successor Draft
-> Activate successor
   -> revalidate cutover
   -> close predecessor at effective boundary
   -> activate successor in same transaction
```

Creating the Draft is **non-destructive**. The predecessor remains Active while the successor is reviewed.

At activation the backend rejects a cutover that would:

- split a non-cancelled vehicle use;
- cross a retained base-rent charge period;
- cross a retained usage/mileage commercial period;
- violate predecessor/successor date ordering.

The successor effective-day check uses the same configured tenant/org commercial timezone as closure coverage. Adjacent half-open operational periods are allowed: use ending exactly at successor start is not an overlap.

A successor keeps the same counterparty; an owner-agreement successor also keeps the same physical supplied vehicle. A different party/owner vehicle is a new agreement, not a rate revision.

The successor relationship is intentionally one-way (`successor -> predecessor`). No redundant stored inverse relationship is required. One predecessor can have at most one direct successor; the successor can later become the predecessor of another revision, producing a clean chain.

Security-deposit requirements are not copied automatically because Payment receipts are linked to the original agreement source. If the amended agreement genuinely creates a new requirement, the operator records it explicitly on the successor Draft.

---

## 7. Vehicle supply, use, custody and replacement

Vehicle owns canonical vehicle identity. Rental owns only Rental-specific use/custody/source lineage.

Backend integrity must prevent:

- cross-tenant relationships;
- wrong owner-source/vehicle association;
- customer use outside agreement/source coverage;
- physically overlapping use of the same vehicle;
- stale state changes;
- broken replacement lineage;
- use conflicting with the shared Vehicle/Vehicle-Service availability contract.

### Replacement production policy

Replacement is physical continuity, not a new unrelated customer rental.

A replacement **does not automatically create a second base-rent charge, surcharge, credit or owner deduction** merely because the vehicle changed. Customer base rent remains agreement-period driven. Owner settlement remains based on the actual owner/source and explicit commercial evidence. Any exceptional recovery/credit requires an explicit approved financial adjustment with source evidence.

This closes the historical ambiguity without inventing a tariff.

### Downtime production policy

Workshop/off-road state is operational availability evidence. It does **not** automatically create a Rental credit/deduction. A contractual downtime adjustment must be separately evidenced/authorized in the owning financial document workflow.

---

## 8. Running Chart and driver identity

### 8.1 Lifecycle

Core states are Draft -> Finalized -> Reversed/Corrected.

No extra Submit/Verify/Approve ceremony is imposed because the authoritative sources do not prove a universal additional stage.

Finalization freezes physical evidence. Corrections use reversal/correction lineage rather than in-place mutation.

### 8.2 Physical integrity

- usage must fit actual custody;
- vehicle periods cannot overlap;
- odometer readings are monotonic where known;
- unknown is distinct from known zero;
- garage KM and commercial KM stay distinct;
- correction lineage is explicit;
- stale expected versions fail.

### 8.3 Driver identity

Driver identity is optional source evidence and has two explicit modes:

- **Employee** — authoritative HR employee ID with server-captured employee number/name snapshots.
- **External** — operator records a name plus stable business reference; the stable reference is normalized and snapshotted.

`driver_observation` remains narrative evidence and is not an identity key.

HR owns employee identity/lifecycle. Rental does not duplicate employees or payroll rates. The operator lookup is a Rental-permission-scoped, least-privilege façade over the HR-owned employee query and exposes only selector identity rather than HR contact data. Running Chart owns the fact that a driver performed a specific Rental usage.

When authoritative driver identity is recorded, two Finalized Running Charts in the same tenant cannot overlap for that same driver. Adjacent periods are allowed. The constraint applies across different vehicles.

Historical charts retain driver snapshots even if the HR record is later renamed, deactivated or soft-deleted.

---

## 9. Commercial calculation policies

### 9.1 Base rent / proration

The shipped named policy is `actual_calendar_days_v1`.

- Daily basis uses inclusive civil days multiplied by the explicit daily base rate.
- Monthly basis uses the agreement's anniversary cycle and actual cycle-day denominator.
- Short-month anchors recover from the original anchor (for example, a day-31 contract does not permanently move the anchor after February).
- Adjacent partial segments use cumulative decimal allocation so their sum reconciles with the full cycle.
- Preview and billing require an explicit policy and agreement revision; no hidden `30-day` divisor exists.
- Manual lifecycle closure caps new base-rent coverage at the tenant-local closure civil date. It does not rewrite existing charges or the original contractual end date; an earlier explicit `ends_on` remains stricter.

This is an explicit AutoERP production policy, not a claim that every historical TACGL customer used it.

### 9.2 Mileage

The shipped mileage policy uses explicit commercial KM, explicit included KM and explicit excess-KM rate. Customer and owner pools are independent.

- blank is not zero;
- zero allowance is distinct from unknown allowance;
- mileage is assessed within a defined daily/monthly agreement cycle;
- manual lifecycle closure caps allowance accrual/assessment at the same tenant-local effective agreement boundary used by base rent;
- an existing mileage pool preserves its snapshotted timezone so later historical assessments do not silently move cycle boundaries after a configuration change;
- customer allowance can be shared across replacement charts according to the named policy;
- owner pools remain tied to their owner agreement;
- no cross-cycle carry-forward;
- zero-cost assessment can still consume allowance;
- later mileage assessment depends on earlier pool state and must be reversed in dependency order.

### 9.3 Garage mileage

Garage KM remains separate physical evidence. The excess-KM engine uses `commercial_km`; `garage_km` has **no automatic customer or owner financial effect**. A separately contracted recovery must be represented as an explicit adjustment, never inferred from the garage-KM field.

### 9.4 OT

Rental does not infer when time becomes Normal/Double/Triple OT. The finalized Running Chart records the already-classified integer minutes. Billing prices those typed minutes using the exact matching agreement rate.

Formula for a typed OT component is:

```text
recorded minutes * explicit hourly rate / 60
```

The minute-to-hour denominator is a unit conversion, not a business qualification threshold.

### 9.5 Night-out

Rental does not infer a night-out from clock times. The Finalized Running Chart records the approved count. Billing uses explicit count * explicit matching night-out rate.

### 9.6 AC mode

Non-AC, Front-AC and Dual-AC are separate recorded contexts/rates. **No implicit fallback is allowed.** A missing rate is not replaced by another AC mode's rate.

No automatic AC charge is created unless a named commercial component/policy explicitly consumes the recorded AC mode. Presence of the legacy field alone is not an entitlement.

### 9.7 Driver amount

The agreement can capture an explicit driver amount, but Rental does not invent a daily/monthly/hourly proration rule from that field. HR owns employee compensation. A customer recovery or owner reimbursement that is not covered by an explicit named Rental policy must be posted as a separately evidenced/authorized financial adjustment rather than an automatic driver charge.

### 9.8 Accident / insurance excess / fuel / repair / meal / highway / parking / miscellaneous

These are supported adjustment **purposes**, not automatic formulas.

Production rule:

- no amount is derived automatically from an incident or legacy field;
- the side that bears the amount, source evidence, approved amount, tax treatment and reason must be explicit;
- posted effects use the owning Invoice/AP adjustment flow;
- the original agreement/running-chart calculation is not rewritten.

### 9.9 Company-owned transfer cost

No artificial owner payable or internal transfer cost is created for a company-owned vehicle unless Finance explicitly configures such a policy in the future.

---

## 10. Independent customer and owner financial paths

### Customer side

Rental creates immutable source calculations/charges and hands them to Invoice as customer sales/outbound documents using customer terms only.

### Owner side

Rental creates independent immutable source calculations/charges and hands them to the Invoice/AP capability as supplier purchase/inbound documents using owner terms only. User-facing text is Owner Payable Voucher / Owner Settlement.

### Duplicate consumption

The fresh implementation combines Rental-side immutable charges with Invoice source allocation. A live source component cannot create duplicate same-side financial quantity. Cancellation/reversal releases the downstream document through owner-module semantics; Rental source history remains auditable and is voided/reissued explicitly rather than deleted.

### Historical revision

Chart-based charges resolve the exact customer/owner agreement history revision frozen on the Vehicle Use. The current agreement row is not substituted for that historical revision.

---

## 11. Tax, withholding and accounting

### 11.1 Ownership

Tax owns:

- rate/applicability configuration;
- effective dates;
- inclusive/exclusive treatment;
- statutory rounding;
- tax snapshots.

Finance owns:

- posting profiles;
- account roles;
- journals;
- periods;
- bank reconciliation.

Payment owns:

- Customer Receipts;
- Owner Payments;
- allocation;
- unapplied balance;
- refunds/reversals;
- payment instruments.

Rental supplies semantic source context and never hardcodes statutory percentages, thresholds or GL account numbers.

### 11.2 Withholding

Owner-payment withholding is determined by the Tax/Payment configuration for the actual party/payment/statutory period. Rental does not independently withhold at agreement or invoice stage and does not reset aggregate statutory thresholds per vehicle, branch or split payment.

### 11.3 Current Sri Lankan format/rule changes

Current IRD material demonstrates why tax behavior must remain effective-dated. Invoice format and withholding guidance changed in 2026. These are owner-module configuration/legal-compliance concerns, not Rental magic constants.

---

## 12. Deposits

Security deposit is customer-side security/advance context, not base rental revenue by default.

Confirmed implementation principles:

- requirement is explicit; no universal amount;
- null and zero differ;
- a requirement is not a receipt;
- Payment owns the real inbound receipt and disposition;
- applied/refunded/unapplied balances reconcile in Payment;
- no automatic forfeiture;
- no duplicate spend of the same available balance;
- reversal/refund lineage is preserved.

A successor agreement does not silently duplicate the predecessor's deposit requirement.

---

## 13. Adjustments and corrections

Legacy debit notes, credit notes, miscellaneous invoices, fuel/repair deductions and allocations prove adjustment capability but not entitlement.

Production design deliberately does **not** add a generic Rental `other_charge` balance engine.

For already-created financial documents, corrections belong to Invoice/AP through its adjustment/reversal mechanisms. Payment corrections belong to Payment. Tax recalculation belongs to Tax. Rental retains the source context and links rather than duplicating those ledgers.

This is a module-ownership decision, not a missing Rental feature.

---

## 14. Cheques and bank reconciliation

The business distinguishes payable creation, payment instrument, allocation, realization and bank reconciliation. AutoERP preserves that distinction using Payment/Finance.

Rental may initiate/navigation-link the owner/customer context, but it must not create a second cheque register or bank-reconciliation engine.

---

## 15. Reporting

Rental provides stable operational source records:

- agreements and revision history;
- vehicle-use/custody/replacement lineage;
- Running Chart register/detail;
- customer/owner immutable Rental charges and source references.

Cross-module financial reporting belongs to Reporting/Invoice/Payment/Finance. Customer/owner statements, outstanding balances, journal/GL lineage and bank reconciliation should read the authoritative owner-module data rather than maintain Rental copies.

Profit/margin is valid only from independently posted customer revenue and owner cost. Never infer owner cost as a percentage/difference of customer revenue.

---

## 16. Permissions and security

Rental permissions are semantic/action based, including separate customer agreement, owner agreement, vehicle-use, Running Chart, finalization/reversal and customer/owner billing capabilities.

Never reproduce legacy numeric user levels/password-register authorization.

All commands must enforce authenticated tenant and organization context server-side. Client-supplied tenant/organization IDs cannot override that context. Cross-tenant aggregate IDs are treated as inaccessible.

No secrets or protected backup contents are logged into runtime records.

---

## 17. Concurrency and integrity

Required controls include:

- explicit DB transactions for multi-row transitions;
- stable lock ordering around shared physical vehicle and commercial source state;
- optimistic expected-version checks;
- tenant-safe composite foreign keys;
- database uniqueness for successor lineage and source-consumption identities;
- immutable finalized/posted history;
- conflict instead of last-write-wins;
- retry only by re-running the complete command and revalidating current state.

Important competing operations:

| Competing operations | Invariant |
|---|---|
| Two uses/workshop admissions | one physical vehicle cannot occupy conflicting operational periods |
| Two billings same side/source | same eligible commercial source cannot be consumed twice |
| Customer vs owner billing | both may independently consume the same physical source |
| Finalize/reverse vs bill | committed source state and financial source state cannot disagree |
| Successor activation vs use/charge | commercial boundary cannot bisect retained operational/financial history |
| Manual closure vs commercial calculation | no new base-rent or mileage commercial coverage may extend after the tenant-local closure civil date |
| Two driver charts | same authoritative driver cannot have overlapping finalized Rental usage |
| Deposit allocate/refund | one available balance cannot be spent twice |

---

## 18. Module ownership

### Vehicle Rental owns

- Customer/Owner Rental agreements and successor lineage;
- Rental vehicle-use/custody/replacement lineage;
- Running Charts and Rental driver-use evidence;
- Rental component calculations and immutable source charges;
- same-side source eligibility/consumption orchestration;
- Rental-specific UI/orchestration.

### Vehicle owns

Canonical vehicle master/identity/ownership context and shared vehicle availability contract.

### HR owns

Employee identity, HR status, HR availability and payroll/compensation.

### Customer / Supplier own

Counterparty master identity/contact data.

### Invoice/AP owns

Financial document lifecycle, balances, adjustments, source allocation and posted-document correction path.

### Payment owns

Receipts/payments, instruments, allocation, advance/unapplied balance, refund/reversal.

### Tax owns

Tax/withholding applicability, rates, snapshots and statutory rounding.

### Finance owns

Accounts, posting profiles, journals, periods, bank reconciliation.

### Vehicle Service owns

Workshop/service/off-road evidence and its availability blocker.

### Reporting owns

Cross-module analytical presentation/export where appropriate.

### Configuration owns

Tenant/org configuration definitions, inheritance and validated workspace timezone values. Rental consumes the stable `localization.timezone` key through `ConfigurationResolverInterface`; it does not create a parallel timezone setting.

A missing owner-module behavior is fixed in that owner module, never compensated for by a Rental-local ledger or compatibility patch.

---

## 19. Relationship review and rationale

The clean schema intentionally avoids redundant bidirectional state:

- Customer Agreement and Owner Agreement remain separate because they represent different parties and obligations.
- Vehicle Use links the customer agreement, actual vehicle and optional external owner agreement because this is the point where physical supply and customer use meet.
- Replacement uses one predecessor link; no duplicate `next_replacement_id` is stored.
- Running Chart belongs to one physical Vehicle Use; financial sides derive their exact agreement revisions from that frozen use.
- Running Chart may reference one HR employee driver, but HR does not store a Rental back-reference.
- Successor Agreement stores one predecessor link; no stored inverse link is required.
- Financial document IDs/statuses are not duplicated as mutable Rental truth; source allocations in Invoice are authoritative.

These relationships are deliberately directional and high-cohesion. The commercial-calendar correction required no schema/relationship change and added no circular dependency.

---

## 20. UI/UX contract

- no raw IDs;
- searchable human-readable Customer/Supplier/Vehicle/Employee selectors;
- no generic side selector when context already defines customer vs owner;
- Draft edits separated from Activate/Close/Supersede actions;
- active commercial terms are read-only;
- successor creation is a compact future-revision action;
- Running Chart entry is fast and keeps uncommon observations under secondary detail;
- unknown values remain blank; zero is entered only when verified;
- billing panels show source component/rate/amount and created financial document links;
- permissions hide actions the operator cannot execute;
- history/audit remains available without crowding primary workflow.

---

## 21. Rejected legacy mechanisms

Never restore or recreate:

- raw GL/account code entry on ordinary Rental forms;
- mutable posted invoices/payments;
- customer amount as source for owner payable;
- repeated same-side consumption of one Running Chart component;
- numeric user-level/password-register authorization;
- repair reports as the primary integrity mechanism;
- duplicate lessor/leasing-company calculation engines without actual commercial difference;
- hardcoded legacy account numbers/rates/tax percentages;
- insurance/revenue-licence Rental blockers without explicit Rental policy;
- old removed Rental runtime through a compatibility layer.

---

## 22. Production decisions for formerly unresolved rules

The historical source uncertainty is preserved, but runtime behavior is now explicit.

| Former ID | Production decision |
|---|---|
| VR-U01 / U02 | Use only explicitly selected named proration policy. Current shipped policy is actual-calendar anniversary-cycle proration; no universal hidden divisor. |
| VR-U03 | Explicit cycle-based mileage allowance policy is implemented independently per commercial side. |
| VR-U04 | Replacement alone creates no automatic surcharge/double base rent/credit. |
| VR-U05 | Downtime/off-road evidence creates no automatic financial deduction. |
| VR-U06 | Garage KM is physical evidence only; automatic mileage pricing uses commercial KM. |
| VR-U07 | Accident/insurance-excess liability requires an explicit approved adjustment; no inferred responsibility. |
| VR-U08 / U09 | Deposit requirement/disposition are explicit and Payment-owned; no automatic priority/forfeiture. |
| VR-U10 / U11 | Tax applicability and statutory rounding are Tax-owned configuration. |
| VR-U12 | Withholding is Tax/Payment-owned and effective-dated; Rental does not hardcode it. |
| VR-U13 | No AC rate fallback. |
| VR-U14 | Rental does not derive OT categories; chart stores typed minutes and billing prices those typed minutes. |
| VR-U15 | Rental does not infer night-out from time; chart stores explicit count. |
| VR-U16 | No invented driver proration; HR owns compensation and explicit financial recovery uses a governed policy/adjustment. |
| VR-U17 | Core Running Chart lifecycle remains Draft/Finalized/Reversed-Corrected; no unsupported approval stages. |
| VR-U18 / U19 | Insurance/revenue-licence documents are not universal Rental assignment blockers. |
| VR-U20 | One Owner/Lessor engine; party subtype does not silently change calculation semantics. |
| VR-U21 | Company-owned vehicles create no artificial external owner payable/internal transfer cost. |
| VR-U22 | Miscellaneous recoveries are explicit authorized adjustments only; historical occurrence is not automatic entitlement. |

These decisions are fail-safe defaults. They eliminate undefined runtime behavior while preserving the distinction between "not automatically charged" and "business can never charge this".

---

## 23. Current fresh implementation

The fresh `app/Modules/VehicleRental` implementation includes:

- separate customer and owner agreements;
- tenant-safe identity snapshots and histories;
- expected-version lifecycle commands;
- effective successor Draft/review/activation flow using the tenant/org commercial calendar;
- closure-aware base-rent and mileage coverage so a Closed agreement cannot generate future commercial periods while historical covered periods remain available;
- bounded/open-ended vehicle planning;
- owner-source and company-owned paths;
- handover/return/cancel/replacement lineage;
- reciprocal Vehicle/Vehicle-Service availability integration;
- Running Chart Draft/Finalize/Reverse/Correction;
- odometer continuity;
- authoritative Employee/External driver identity snapshots and driver-overlap finalization guard;
- actual-calendar base-rent preview/billing;
- explicit cycle-based mileage allowance/assessment with timezone snapshot preservation;
- typed Normal/Double/Triple OT and Night-out pricing;
- independent customer/owner immutable Rental charges;
- Invoice/Tax/Finance source handoff;
- unchanged reissue and governed void after downstream release;
- customer security-deposit receipt/disposition through Payment;
- scoped registers/history and guided frontend workflow.

This module is fresh code. Removed legacy Rental runtime remains excluded.

---

## 24. Protected backup investigation

Nested protected archive:

`DATABACKUP/!   CTACGLDATABACKUP202503271759.rar`

Free local archive inspection lists 86 entries and reports every listed entry as password-protected. The RAR has no archive comment. Accessible TACGL text/configuration was searched for explicit backup-password/passcode/credential references and no explicit backup credential was found. The encrypted archive itself contains `password.DBF` / `password.CDX`, but those files are also protected and are not accessible password evidence.

No guessed variants, dictionary attack, brute force or arbitrary password mutation was used. Therefore the backup remains unavailable source evidence. This is a source-access limitation, not an undefined production rule: runtime behavior is governed by the explicit policies in this knowledge base.

---

## 25. Source limitations

- The supplied TACGL corpus is authoritative evidence but is not proven to contain the entire dedicated AT Tours Rental data/application shown in every video frame.
- The protected nested backup cannot currently be inspected without a valid passphrase.
- Prior video work includes interval/full-resolution anchor review; it is not evidence that every spoken-only sentence has been reliably transcribed.
- Executable-only hidden logic is not treated as a business rule without safe demonstration/corroboration.
- One historical example never becomes a universal monetary formula by itself.

These limitations do not authorize guessing. They are resolved in production by the explicit non-automatic/default policies above.

---

## 26. Verification contract

For any future Rental change, verify at minimum:

1. tenant/organization isolation;
2. expected-version/stale-write behavior;
3. DB FK/unique constraints;
4. customer/owner independence;
5. source revision snapshot correctness;
6. duplicate-consumption rejection;
7. reversal/reissue lineage;
8. migration fresh-install and upgrade behavior;
9. SQLite and MySQL/MariaDB transaction behavior where relevant;
10. frontend unit/integration tests, typecheck, lint and build;
11. authenticated browser/UAT for changed operator flows.

A change record must state which checks were actually executed. Never write "passed" for a check that was only statically reviewed.

---

## 27. AI-agent decision procedure

When deciding Vehicle Rental behavior:

1. Identify the physical event.
2. Identify the financial side, if any.
3. Resolve the exact agreement and frozen revision.
4. Resolve physical source evidence.
5. Use only an explicit named policy/rate or an explicit authorized amount.
6. Check same-side source consumption.
7. Check tenant/org/permission/version constraints.
8. Delegate financial-document, payment, tax and GL behavior to owner modules.
9. Preserve immutable snapshots and correction lineage.
10. If no automatic financial policy exists, create no automatic money; require an explicit governed adjustment rather than guessing.

---

## 28. Final authority statement

1. TACGL is the primary business source and conflict tie-breaker.
2. The four supplied videos are authoritative practical workflow evidence.
3. `worktree-0.0.8` is the authoritative implementation source.
4. `RULES.md` / `AGENTS.md` govern engineering quality and module ownership.
5. Never restore or reuse the removed Rental implementation.
6. Never hardcode a legacy rate, tax percentage, GL account or magic business code.
7. Preserve customer/owner independence and historical revisions.
8. Unknown historical formulas do not justify undefined runtime behavior: use named explicit policy where available, otherwise no automatic financial effect.
9. Keep the operator workflow simple while enforcing strong hidden backend integrity.
