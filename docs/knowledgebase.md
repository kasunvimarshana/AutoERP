# AutoERP Vehicle Rental Domain Knowledge Base

**Status:** Canonical Vehicle Rental business/domain knowledge for a future clean implementation  
**Knowledge refresh date:** 2026-08-27  
**Authoritative engineering branch at refresh:** `worktree-0.0.8`  
**Authoritative engineering commit reviewed before refresh:** `0b805561c622826d416fbdc4a7e39e83a291fe3f`  
**Business evidence:** all four supplied Vehicle Rental videos plus the supplied TACGL legacy application/data archive  
**Architecture policy:** `RULES.md` and `AGENTS.md`

---

## 1. Purpose

This document is the canonical Vehicle Rental knowledge base for AutoERP. It consolidates the business meaning demonstrated by the supplied Vehicle Rental videos and TACGL legacy data into one evidence-based reference for future design, implementation, QA, migration and reconciliation work.

It is deliberately **not** a screen-for-screen, table-for-table or transaction-code-for-transaction-code copy of either legacy system. The sources contain real business knowledge and also contain design weaknesses. AutoERP must preserve the valid business meaning while correcting the underlying design.

The project rules remain binding:

- understand first, verify second, change third;
- do not guess unconfirmed business policy;
- fix root causes in the owning module;
- maintain one source of truth;
- protect historical facts;
- make writes atomic, version checked and conflict aware;
- keep module ownership explicit;
- never expose raw IDs or database structures to ordinary users;
- prefer the simplest clean solution that preserves data integrity.

As of the engineering commit named above, Vehicle Rental is intentionally retired from the active AutoERP runtime. This knowledge base therefore describes the **business domain and clean target foundation for a future rebuild**. It does not claim that the described Rental workflows are currently active in AutoERP.

---

## 2. Source authority and evidence method

### 2.1 Authoritative Vehicle Rental videos

The following four videos are authoritative business evidence:

| Video | Duration | Strongest evidence |
|---|---:|---|
| `1.mp4` | 40:50 | Lessee and lessor agreements, customer invoice calculation, Daily Running Chart, owner payable processing, owner deductions, allocations, cheque payment and bank reconciliation |
| `Recording 2026-06-21 132314.mp4` | 41:58 | Vehicle and lessee registers, lessee agreement, customer invoice/PDF, Daily Running Chart, receipt allocation, owner agreement/statement, integrated rental ledger and security/register screens |
| `2.mp4` | 21:14 | Broad lessor/lessee transaction menus, registers, statements, allocation/error procedures, double-entry checks and reconciliation/report inventory |
| `ScreenVideo_03-04-2026_18-02-52.mp4` | 12:24 | Vehicle Service/workshop flow: vehicle/reminder context, job, material issue, Outside Work, labour and job invoice; supporting evidence for shared vehicle availability, not Rental formulas |

Total reviewed footage represented by the audit evidence is approximately **1 hour 56 minutes 26 seconds**.

The strongest video-level domain conclusion is:

> **One physical Daily Running Chart is shared operational evidence, while customer billing and owner settlement are independent commercial calculations using different agreements/rates.**

### 2.2 TACGL business source

The supplied TACGL archives are authoritative legacy business/accounting evidence. The latest supplied `TACGL(6).zip` was independently hashed and is byte-identical to the previously audited TACGL archive. SHA-256:

`0e0733fff720072af4c3aaa787995ff128bfa79060a37739d6d2ebbe18a25313`

Therefore `TACGL(3).zip`, the later re-supplied TACGL copies with this exact hash, and `TACGL(6).zip` represent the same evidence corpus rather than independent conflicting datasets.

TACGL is a Visual FoxPro-era operational/accounting system containing vehicle, workshop, customer, creditor, invoice, subledger and General Ledger data. It is highly valuable as evidence of actual business usage, but it is not a clean Vehicle Rental architecture.

### 2.3 Engineering source

The latest `worktree-0.0.8` branch is authoritative for current AutoERP engineering state, ownership boundaries and existing module capabilities. Business source evidence does not override current project architecture rules.

### 2.4 Evidence labels

This knowledge base uses these meanings:

- **Observed — Video:** directly visible in supplied video screens, reports, menus or printed outputs.
- **Observed — TACGL:** directly found in supplied TACGL tables, reports or exports.
- **Cross-source conclusion:** supported by both source sets or by multiple independent source facts.
- **Derived integrity requirement:** required to preserve observed business meaning safely; not a claim that legacy systems enforced it correctly.
- **Target design:** recommended AutoERP responsibility/architecture derived from business evidence plus project rules.
- **Observed precedent, policy unconfirmed:** real historical arithmetic or handling exists, but the sources do not prove it is the universal business rule.
- **Needs business confirmation:** the exact policy is not proven and must not be silently defaulted.

When the videos and TACGL demonstrate different legacy mechanisms, preserve the business meaning and record the difference. Do not invent a reconciliation rule.

---

## 3. Executive domain model

Vehicle Rental is a **dual-sided operational and financial domain**.

```text
Vehicle Owner / Lessor
    -> Lessor Agreement
    -> Vehicle supply / owner-side commercial terms
                              \
                               -> Vehicle allocation/custody
                              /              |
Customer / Lessee                            v
    -> Lessee Agreement              Daily Running Chart
    -> Customer-side terms            /             \
                                     /               \
                    Customer commercial             Owner commercial
                    calculation                      calculation
                    Lessee rates                     Lessor rates
                         |                                |
                         v                                v
                  Customer Invoice              Owner Payable Voucher
                         |                                |
                         v                                v
                  Receipt / Allocation             Payment / Allocation
                                      \            /
                                       Tax / Finance / GL
```

### Central invariant

> **The physical usage truth is common. The revenue and cost calculations are separate.**

The customer amount comes from the effective Lessee Agreement/rate context. The owner payable comes from the effective Lessor Agreement/rate context.

Customer revenue is never the formula source for owner payable. Owner cost is never the formula source for customer billing.

Processing one commercial side must not block the other. A finalized usage source must not be consumed twice on the same commercial side.

---

## 4. Canonical terminology

### 4.1 Lessee / Customer

The party receiving/using the rented vehicle. This is primarily the receivable/revenue side.

Business consequences include:

- Lessee Agreement;
- customer rental/usage charges;
- Customer Invoice;
- receipt and allocation;
- advance/deposit where applicable;
- debit/credit adjustments;
- customer ledger, statement, aging and outstanding balance.

### 4.2 Lessor / Vehicle Owner

The party supplying a vehicle to the rental operation. The legacy Rental UI separates vehicle owners and leasing companies into separate registers, but their shared business role is **Lessor**.

Business consequences include:

- Lessor Agreement;
- effective vehicle-supply coverage;
- owner rental payable;
- owner-side excess-KM/driver/OT/night-out components where applicable;
- supported deductions such as fuel/repair;
- payment and allocation;
- debit/credit adjustments;
- owner and vehicle-wise statements.

A future implementation should use one lessor/supplier role with classifications rather than duplicate settlement engines for individual owner versus company/leasing-company classifications.

### 4.3 Owner Payable Voucher / Self-Billed Owner Settlement

The videos use **Payment Payable Voucher** and **Payment Payable Processing**. Its business meaning is not a customer sales invoice. The clean domain term is **Owner Payable Voucher** or **Self-Billed Owner Settlement**, subject to final accounting/legal naming approval.

### 4.4 Daily Running Chart

The authoritative operational record of physical vehicle usage. It is not merely an invoice line and is not owned by Invoice/Finance. It supplies physical facts used independently by customer billing and owner settlement.

### 4.5 Allocation / Custody

An effective-dated relationship assigning a physical vehicle to a commercial agreement/use context. Vehicle selection may be simple in the UI, but the persistent model needs history and overlap protection.

### 4.6 Replacement

A controlled substitution of a replacement vehicle during an existing allocation/commercial period. Original/replacement lineage must remain explicit.

### 4.7 `OWN` in TACGL

**Critical semantic correction:** TACGL `jobtxn` references beginning `OWN...` with `TXNTYPE = 2` mean **Outside Work**, not Owner/Lessor. TACGL report artifacts explicitly use labels such as `Outside Work Order Note`, `Outside Work Order Note Analysis` and `Outside Work Order Invoice (IOW) Analysis`.

Never interpret `OWN` as owner settlement.

---

## 5. End-to-end Vehicle Rental lifecycle

A clean business lifecycle reconstructed from the source evidence is:

```text
Reference / company / finance / tax setup
    -> Customer / Lessee setup
    -> Lessor / vehicle-supplier setup
    -> Driver setup where relevant
    -> Stable physical Vehicle setup
    -> Lessor Agreement / supply coverage where externally supplied
    -> Lessee Agreement
    -> Effective vehicle allocation / custody
    -> Handover or self-drive movement where applicable
    -> Daily or Replacement Running Chart
    -> Finalized physical evidence
         |-- Customer calculation -> Customer Invoice -> Receipt / Allocation
         `-- Owner calculation    -> Owner Payable  -> Payment / Allocation
    -> Adjustments / deductions / deposit movements where applicable
    -> Cheque / bank clearing and reconciliation
    -> Operational, subledger, tax, GL and management reporting
    -> Governed reversal/correction when required
```

Customer billing and owner settlement are parallel consumers of shared usage evidence, not a mandatory serial chain.

---

## 6. Master and reference data

### 6.1 Vehicle

**Observed — Video** Vehicle Register concepts include:

- registration/vehicle number;
- registered owner;
- address/contact context;
- registration and transfer dates;
- licensing authority;
- fuel type;
- body type;
- vehicle/taxation class;
- manufacture year;
- chassis and engine numbers;
- colour;
- seating/cylinder capacity;
- make/model;
- weight/dimensional attributes;
- vehicle type;
- legacy GL asset code;
- legacy lessor code;
- revenue licence expiry;
- insurance expiry.

**Observed — TACGL:** `scfveh` contains vehicle registration, debtor/commercial context, make/name/address/contact and vehicle-type data.

The separate `vehtyp` vocabulary defines Own/Hired/Outside labels, but all **1,076 non-deleted `scfveh` rows** in the inspected archive carry `VEHTYP = 03` (`OUTSIDE VEHICAL`). Therefore that field is not trustworthy as authoritative ownership/supply truth.

### Stable vehicle identity requirement

TACGL contains **six normalized registration duplicate groups** (12 rows). Examples include:

- `CAD-1608` / `CAD 1608`;
- `CAF-6512` / `CAF 6512`;
- `CAQ-7638` / `CAQ 7638`;
- `CBD-3677` / `CBD 3677`;
- `CBJ-6594` / `CBJ 6594`;
- `KJ7558` / `KJ-7558`.

Several alternate-format pairs carry different debtor/commercial contexts, showing a legacy workaround where the same physical registration was duplicated for relationship/context purposes.

**Non-negotiable rule:**

> **One physical vehicle = one stable Vehicle record.**

Registration formatting differences must not create new physical vehicles. Ownership, supply and customer-use relationships change through effective-dated relationship records.

### 6.2 Customer / Lessee

Observed concepts include:

- customer code/name;
- address/contact;
- credit/balance context;
- VAT/SVAT-related attributes;
- driver salary defaults;
- working hours;
- normal/double/triple OT values;
- night-out values;
- customer ledger/statement reporting.

Master-level commercial values are templates/defaults only when business policy explicitly says so. Posted calculation authority is the effective agreement/rate snapshot.

### 6.3 Lessor

Video evidence shows separate registers for vehicle owners and leasing companies, both participating in payable/transaction/report flows.

**Target:** one shared Lessor role/classification with structured party identity and financial relationship.

### 6.4 Driver

Video evidence includes a Driver Register and driver-related agreement/Running Chart values.

Relevant concepts include:

- driver identity and active status;
- licence/qualification where required;
- assignment/availability history;
- working-hours context;
- salary/reimbursement basis;
- normal/double/triple OT;
- night-out treatment.

Employee driver identity belongs to HR; Vehicle Rental references approved driver identity/availability rather than duplicating employee master data.

---

## 7. Lessee / Customer Agreement

**Observed — Video** concepts include:

- agreement date and number;
- customer/lessee;
- execution/start/end dates;
- company/personal format;
- monthly or daily basis;
- selected vehicle in the legacy UI;
- included/maximum kilometres;
- base rental/rate for included entitlement;
- excess-KM rate;
- with-driver versus self-drive context;
- driver salary/recovery;
- working hours;
- normal/double/triple overtime;
- night-out;
- Non-AC / Front-AC / Dual-AC rate contexts;
- VAT/SVAT/SSCL-related configuration;
- security deposit;
- legacy GL/account mappings.

### Target agreement rules

- Lessee and Lessor agreements are separate aggregates.
- Terms are effective-dated/versioned.
- Activated/executed versions are immutable.
- Later rate changes create new versions rather than rewriting history.
- Historical billing snapshots the effective agreement/rates/tax/source facts used.
- Ordinary rental operators do not select raw GL accounts; Finance/Tax resolve accounting from owned configuration.
- Simple UI vehicle selection may create/update a dedicated allocation record, not mutate Vehicle identity/history.

---

## 8. Lessor / Vehicle Owner Agreement

**Observed — Video** concepts include:

- lessor/owner;
- agreement number/date/period;
- vehicle;
- monthly or daily basis;
- included/maximum KM;
- base rental payable;
- excess-KM payable rate;
- with-driver/self-drive context;
- driver reimbursement values;
- OT/night-out reimbursement context;
- legacy payable/account mappings;
- owner/vehicle statements and payable processing.

### Target rules

- The effective Lessor Agreement is authoritative for owner settlement.
- Customer price/invoice total cannot determine owner payable.
- Externally supplied vehicle coverage is effective-dated.
- Company-owned vehicles do not require an external owner-payable path unless an explicit internal-charge policy exists.
- Historical settlements retain exact lessor agreement/rate snapshots.

---

## 9. Vehicle supply, allocation and custody

The videos do not clearly prove separate standalone `Lessor Vehicle Allocation` and `Lessee Vehicle Allocation` screens. Vehicles are selected in agreements and reused in Running Charts.

Preserve that practical simplicity in the UI while maintaining a clean backend relationship model.

### Minimum effective allocation/custody record

- stable Vehicle identity;
- agreement identity and commercial side;
- effective start/end;
- owner/supply coverage where applicable;
- customer-use coverage;
- driver/self-drive context;
- handover odometer;
- return odometer;
- operational status;
- original/replacement lineage;
- evidence references where approved;
- row/version and audit metadata.

### Mandatory controls

- No overlapping blocking customer allocations for one physical vehicle.
- Allocation must fit agreement dates.
- Customer-use coverage must be supported by valid company-owned or lessor-supplied vehicle coverage.
- Ownership/source mismatch is rejected.
- Vehicle-owned workshop/off-road/breakdown holds block new conflicting Rental allocation/use.
- Historical allocations are preserved rather than overwritten.
- Concurrent allocation attempts are resolved deterministically with version/constraint protection.

---

## 10. Handover, return and replacement

**Observed — Video:** self-drive handover/return concepts, Daily Running Chart, Replacement Running Chart and original/replacement reporting exist.

### Target behavior

- Handover/return are contextual actions on allocation/custody.
- Replacement references original vehicle, replacement vehicle and exact effective period.
- Odometer/fuel/condition evidence belongs to custody/handover evidence when required, not invoice free text.
- Replacement cannot create overlapping usage or silently break owner-supply coverage.
- A replacement vehicle under a blocking availability hold is not eligible.

### Needs confirmation

The sources do not prove exact customer charging, owner payment, KM entitlement, downtime or driver treatment during replacement.

---

## 11. Daily Running Chart — central operational evidence

The Running Chart is the core physical evidence of Vehicle Rental operations.

Observed/strongly evidenced concepts include:

- vehicle;
- customer/lessee agreement;
- owner/lessor agreement/source context;
- operational date/date range;
- start/finish mileage;
- total kilometres;
- excess kilometres;
- garage/internal kilometres;
- start/finish times;
- working hours;
- normal/double/triple overtime;
- night-outs;
- number of days/hires;
- AC/rate basis where applicable;
- driver;
- original/replacement relationship;
- remarks/details;
- print/report output.

Customer invoice processing visibly supports Running Chart data import.

### Required integrity behavior

- End odometer cannot be below start odometer.
- Odometer continuity is validated against adjacent custody/usage facts.
- Usage must fit valid agreement/allocation periods.
- Vehicle/driver timeline conflicts are rejected.
- One physical usage event is not duplicated into customer and owner copies.
- Finalized physical facts are immutable.
- Corrections preserve original evidence and add correction/reversal lineage.
- Customer and owner commercial consumption are tracked independently.
- A finalized source cannot be consumed twice on the same side.
- Customer processing does not block owner processing and vice versa.

### Lifecycle boundary

The videos do not prove a mandatory maker-checker chain. Use the minimum approved lifecycle. `Draft -> Finalized` is enough if no approval policy exists. Add Submitted/Verified/Approved only when business governance explicitly requires them.

---

## 12. Customer billing

**Observed — Video:** customer Credit Invoice processing includes customer/agreement/period, total/excess KM, overtime, night-outs, days/hires, rental/excess rates, driver-related recovery, tax, total, Running Chart import and print/PDF output.

Representative component model:

```text
Base rental
+ Excess kilometre charge
+ Driver salary recovery
+ Normal overtime
+ Double overtime
+ Triple overtime
+ Night-out recovery
+ Approved parking / other recoveries
+ Approved miscellaneous charges
- Approved discount / credit adjustment
+ Effective tax
= Customer invoice total
```

This is a component vocabulary, not permission to invent unknown formulas.

### Customer billing rules

- Effective Lessee Agreement/rate version determines commercial rates.
- Only eligible finalized customer-side unconsumed usage is selectable.
- Operator sees a human-readable quantity × rate breakdown.
- Exact source identities, quantities, rates, period and tax snapshots persist.
- Same-side duplicate consumption is impossible.
- Vehicle Rental owns the source/calculation plan; Invoice owns financial document lifecycle/balance/reversal.
- Tax owns tax determination/snapshot.
- Finance owns account resolution/journal/ledger.
- Posted financial documents are immutable.

### Minimum calculation snapshot

Persist enough facts to reproduce the posted value:

- agreement/version;
- allocation/custody;
- usage sources;
- calculation source fingerprint/idempotency identity;
- rate components and units;
- included-KM entitlement used;
- actual/commercial/excess KM;
- driver/OT/night-out facts;
- original/replacement context;
- tax snapshot;
- rounding policy identity;
- invoice source allocations.

---

## 13. Owner / Lessor settlement

The owner side is an independent payable/cost calculation.

**Observed — Video:** owner payable processing, statements, cash/petty-cash/cheque payments, receipts, debit/credit notes, fuel/repair deductions, allocations, vehicle-wise statements and unallocated transaction reports.

Representative component vocabulary:

```text
Base owner rental payable
+ Excess kilometre payable
+ Driver reimbursement
+ Overtime reimbursement
+ Night-out reimbursement
+ Approved other reimbursements
- Supported fuel deductions
- Supported repair deductions
- Approved advances / debit adjustments
+ Approved credit adjustments
- Withholding where legally/applicably required
= Net owner payable
```

Exact tax/withholding order and rounding are policy decisions unless explicitly approved.

### Owner settlement rules

- Effective Lessor Agreement/rate version determines owner-side rates.
- Customer invoice value/rates never determine owner payable.
- Only eligible finalized owner-side unconsumed usage is selectable.
- Same-side duplicate settlement is blocked.
- Vehicle remains an analytical/source dimension.
- Fuel/repair deductions reference authoritative evidence to prevent duplicate recovery.
- Posted owner payable is immutable.
- Payment allocation cannot exceed open payable and is concurrency-safe.

---

## 14. TACGL archive structure — deep audit

The byte-identical TACGL archive contains **452 non-directory files**.

Main file inventory:

- 114 DBF tables/data files;
- 109 FRX report definitions;
- 109 FRT report memo/data companions;
- 46 IDX indexes;
- 32 CDX indexes;
- 16 PDFs;
- 5 XLS exports;
- Visual FoxPro DBC/DCT/DCX and runtime/application artifacts.

`gl.dbc` contains approximately:

- 39 active table objects;
- 910 field objects;
- 122 index objects;
- 5 database-level objects.

Relevant non-deleted business-data counts in the inspected archive include:

- `scfveh`: 1,076 rows;
- `scfjob`: 6,653 rows;
- `jobtxn`: 23,645 rows;
- `scfinv`: 6,630 rows;
- `scfglt`: 78,760 rows;
- `scfdeb`: 379 rows;
- `scftdb`: 13,775 rows;
- `scftcr`: 5,083 rows;
- `scftxn`: 10,377 rows;
- `scfsmf`: 483 rows;
- `scfacc`: 78 rows;
- `scfchr`: 58 rows.

These counts describe this evidence archive; they are not a target schema.

### Legacy PDF/XLS evidence

The archive has 16 PDFs totaling **63 pages**. The readable PDFs are predominantly `Debtor's Outstanding Age Analysis` outputs and confirm customer/reference/job/vehicle aging and receivable traceability. They do not establish a new Rental pricing formula.

The five XLS files are legacy exports/report artifacts; their existence supports export/report operations but is not a reason to reproduce legacy report storage patterns.

---

## 15. TACGL charge vocabulary and transaction semantics

### 15.1 Rental charge codes

`scfchr` includes:

| Code | Description | Master rate |
|---|---|---:|
| `HIRIN` | Hiring charges for with-driver monthly-basis car | 0 |
| `EXCES` | Excess charges for with-driver monthly-basis car | 0 |
| `RENT1` | Hiring charges for self-drive monthly-basis car | 0 |
| `HIRE1` | Hiring charges for with-driver van | 0 |
| `OT100` | Driver overtime | 0 |

Zero master rates strongly indicate these codes classify charge types while actual commercial values are transaction/agreement-specific.

### 15.2 Job transaction type semantics

Active `jobtxn` references map as:

- `TXNTYPE = 1` / `MIN...` -> Material Issue;
- `TXNTYPE = 2` / `OWN...` -> Outside Work;
- `TXNTYPE = 3` / `LCH...` -> Labour/service charge.

`OWN` must never be used as an owner/lessor semantic in AutoERP.

---

## 16. TACGL customer Rental billing — exact lineage

TACGL embeds many Rental charges inside workshop-style RMS jobs and `LCH` lines.

A focused search of explicit rental/hire/excess/driver-OT descriptions found **44 active Rental/hire job lines** associated with **42 invoice references**. Corresponding invoice GL credits were predominantly legacy workshop sales/Pending Jobs accounts rather than a clean Rental revenue account:

- `0001-005 SALES: - BREAKDOWN`: 26 matching credit postings;
- `0001-004 SALES: - TINKERING & PAINTING`: 14 matching credit postings;
- `6001-000 PENDING JOBS`: 2 matching credit postings.

This is strong evidence of real Rental billing and equally strong evidence that legacy account/module classification must **not** be copied.

### 16.1 Exact excess-KM trace

Example:

`LCH2005407`

- vehicle: `CBD 3677`;
- job: `RMS2005443`;
- description: `EXCESS CHARGES FOR WITH DRIVER MONTHLY BASIS CAR`;
- detail: `01/05/2025 TO 31/05/2025 1,172KM*90.00`;
- stored line value: `105,480`;
- invoice: `INV2005519`.

Arithmetic: `1,172 × 90 = 105,480`.

That line flowed to:

- `scfinv` invoice `INV2005519` for customer `ASMARA`, vehicle `CBD 3677`, job `RMS2005443`, value `105,480`;
- `scftdb` Debtor entry `105,480`;
- later receipt `REC2003089` allocating `105,480` against `INV2005519`;
- GL credit `0001-005 SALES: - BREAKDOWN` for `105,480`;
- GL debit `5000-000 TRADE DEBTORS` for `105,480`.

This proves the end-to-end legacy customer chain:

```text
Rental/Excess business charge
    -> LCH / RMS job line
    -> Customer Invoice
    -> Debtor subledger
    -> Receipt allocation
    -> General Ledger
```

**Target conclusion:** preserve source-to-invoice-to-receipt-to-GL traceability, but Rental must not be modeled as workshop labour/breakdown sales.

---

## 17. Structured quantity/rate requirement from TACGL data quality

Legacy Rental/excess lines often encode the actual formula in free text while structured numeric fields carry only the final amount.

For `LCH2005407`:

- `TXNQTY` is empty;
- `TXNRATE = 105,480`;
- `TXNVAL = 105,480`;
- the meaningful quantity/rate `1,172 KM × 90` exists in text.

Other correct arithmetic examples include:

- `1,165 KM × 65 = 75,725`;
- `1,082 KM × 90 = 97,380`;
- `1,962 KM × 75 = 147,150`;
- `635 KM × 65 = 41,275`.

At least one observed legacy line has **text/amount disagreement**: a detail describes `1,080KM *90.00` while the stored value is `81,000`, which does not match that textual arithmetic. This is direct evidence that free-text calculation narratives are not a safe source of truth.

### Target rule

Every calculated commercial component must store structured facts such as:

- component type;
- measured quantity;
- unit;
- effective rate;
- rate source/agreement version;
- calculation period;
- amount;
- taxability/tax snapshot;
- source Running Chart identities;
- calculation fingerprint;
- human-readable explanation derived from structured facts.

Do not make free text the authoritative quantity/rate source.

---

## 18. Monthly periods and partial-period proration evidence

TACGL proves that a legacy `monthly` rental period is not necessarily a calendar month.

Observed self-drive monthly periods include:

- `25/06/2025 -> 24/07/2025`;
- `25/07/2025 -> 24/08/2025`;
- `18/06/2025 -> 17/07/2025`;
- `18/07/2025 -> 17/08/2025`;
- `18/08/2025 -> 17/09/2025`.

Therefore the clean domain must support **agreement-cycle/anniversary billing periods**, not hardcode calendar month start/end.

### 18.1 Customer-side partial-period precedent

For vehicle `CBM-9887`, full monthly lines show `225,000` for 18th-to-17th periods. A later invoiced line shows:

- `18/09/2025 -> 30/09/2025`;
- amount `97,500`.

This exactly matches:

`225,000 × 13 / 30 = 97,500`.

### 18.2 Owner/payment-side partial-period precedent

A `PRB` rental-payment row shows:

- payee `B M K M BALASOORIYA...`;
- description `RENTAL PAYMENT 21DAYS`;
- amount `126,000`.

The same payee later has full-month Rental Payment entries of `180,000`. Arithmetic matches:

`180,000 × 21 / 30 = 126,000`.

### Policy boundary

These are **observed TACGL precedents**, not sufficient evidence that every monthly agreement uses fixed-30-day proration. The future AutoERP rule must be explicitly approved. Until then:

- do not hardcode `monthly / 30` globally;
- do not assume actual-calendar-day proration;
- do not assume calendar-month billing;
- store the agreement cycle and the selected proration policy explicitly/versioned when the business confirms it.

---

## 19. TACGL third-party hire / Outside Work flow

TACGL also demonstrates one-off/third-party hired-vehicle business through Outside Work.

Example `OWN2003536`, job `RMS2005503`, vehicle `CAW-6550`:

- `JEEP WITH DRIVER PER DAY 35000 X 3 DAY` = `105,000`;
- `DRIVER OVER TIME 24.30 HRS X500` = `12,250`;
- `DRIVER BATA` = `2,000`;
- an `EXCESS 544KM X 300.00` line existed but was deleted before final invoicing.

The active Outside Work amount `119,250` flowed to:

- `6001-000 PENDING JOBS` on the job cost side;
- `7000-000 TRADE CREDITORS`;
- creditor subledger reference `OWN2003536`.

The same job produced customer invoice `INV2005580` for `289,400`.

This demonstrates a valid business need for:

- sourcing a third-party vehicle/hire service;
- recording driver/OT/incidental cost components;
- maintaining creditor/payable consequences;
- recovering billable amounts from the customer;
- measuring margin/profitability between cost and revenue.

**Target conclusion:** support the valid third-party Rental sourcing/recovery scenario, but do not make Vehicle Rental a Vehicle Service `Outside Work` subtype. Rental owns rental sourcing/usage/commercial context; the owning financial modules handle payables/invoices/payments/GL.

---

## 20. TACGL regular Rental/owner payment evidence

The GL account master contains:

`7048-000 RENTAL PAYMENT`

The inspected active GL data has **25 positive debit rows across 21 `PRB...` bank-payment vouchers** to this account.

Descriptions include:

- monthly Rental Payment;
- multi-day Rental Payment;
- Jeep Hire Payment;
- Hiring Payment;
- Hiring Payment Van.

Some vouchers aggregate multiple vehicle detail lines. Example `PRB1000970` contains separate Rental Payment lines for `CBD-3677`, `CAD-1608` and `CBJ-6594` against one payment reference/payee context.

The counterpart is a bank account such as `4202-000 BANK BALANCE - 1`.

This proves an actual legacy practice:

```text
free-text payee/vehicle detail
    -> PRB bank voucher
    -> debit RENTAL PAYMENT expense
    -> credit bank
```

### Target conclusion

The economic event is valid; the architecture is not.

Future AutoERP should use:

```text
Structured Lessor / Supplier
    -> Lessor Agreement / approved one-off source snapshot
    -> Owner Payable / settlement source
    -> Payment
    -> Bank instrument / reconciliation
    -> Finance posting
```

Do not use free-text payee/vehicle descriptions as financial identity. Do not bypass payable/source traceability with direct expense vouchers for ordinary owner settlement.

---

## 21. TACGL deleted/replaced transaction evidence

The archive contains deleted flags/records and `del_*` mirror tables. Rental-related examples include deleted/replaced job lines.

Examples observed during the deep pass include:

- deleted `OWN2003502` monthly hire line for `CBD 3677`;
- deleted `LCH2005405` monthly line for `CAF 6512`, followed by another line for the same job/vehicle with the period populated;
- a deleted `OWN2003536` excess-KM line while other lines on the same source were invoiced.

The legacy system therefore demonstrates real correction/re-entry behavior but not a safe immutable financial-history model.

### Target rule

- Finalized operational facts are not hard-deleted.
- Posted financial documents are not edited/deleted.
- Correction creates explicit supersession/reversal/correction lineage.
- Original quantities, rates, amounts and source identities remain queryable.
- Reversal restores source eligibility only through controlled owner logic.

---

## 22. Deposits, advances, refunds and adjustments

**Observed — Video:** security-deposit fields exist; both lessor and lessee menus contain receipts, payments, debit notes and credit notes.

TACGL narratives additionally contain examples suggesting rental deductions/claims/excess/write-off handling. Those narratives prove adjustment capability is needed but do not prove the exact liability policy.

### Integrity requirements

- Distinguish ordinary receipt, customer advance and rental security deposit.
- Deposit/advance balances remain semantically separate where accounting treatment differs.
- Adjustment references party, business source, reason and evidence.
- Allocation cannot exceed open balance.
- Refund references original economic source.
- Reversal preserves the original and creates compensating history.
- A generic debit/credit note cannot bypass source-specific business rules.

### Needs confirmation

- deposit due timing;
- mandatory/optional deposit by agreement type;
- application priority;
- partial application;
- refund timing;
- forfeiture;
- early termination/damage/accident treatment;
- tax treatment of deposit/application/forfeiture.

---

## 23. Receipts, payments and allocations

**Observed — Video:** cash, petty cash, cheque, receipts, payments, debit/credit notes, allocations and unallocated-transaction reports exist on both commercial sides.

### Customer-side semantics

- invoice receipt;
- partial receipt;
- receipt allocated across invoices;
- unallocated advance;
- security-deposit receipt;
- refund where applicable;
- allocation reversal;
- receipt reversal.

### Owner-side semantics

- owner payable payment;
- partial payment;
- payment across owner payables;
- approved owner advance;
- owner receipt/refund where applicable;
- allocation reversal;
- payment reversal.

### Mandatory controls

- party/currency/document direction match;
- no over-allocation;
- no concurrent double consumption of open balance;
- no double posting when an already-posted payment is later allocated;
- coordinated reversal of allocations and balances;
- Payment owns receipt/payment/instrument/allocation lifecycle;
- Invoice/approved payable owner owns document balance lifecycle.

TACGL exact trace `INV2005519 -> REC2003089` confirms that allocation history is a real business requirement.

---

## 24. Cheque lifecycle and bank reconciliation

**Observed — Video:** payment date, voucher/reference, bank, cheque number/date, crossed/bearer/account-payee-style options, payee, amount, detail lines, print and realization/reconciliation workflows exist.

### Target rules

- cheque number uniqueness is correctly scoped to bank account/cheque book;
- payment economic facts become immutable after posting/issue;
- clearing/realization/reconciliation creates controlled status/reconciliation events;
- original date/payee/amount are not rewritten during reconciliation;
- bounced/stopped/cancelled/replaced instruments have explicit states/events and audit history.

Exact instrument lifecycle remains Payment/Finance policy.

---

## 25. Tax and General Ledger integration

The videos expose VAT, SVAT, SSCL and raw GL mappings inside operational/commercial forms. TACGL demonstrates direct GL account use for Rental revenue/cost/payment.

The valid requirement is strong accounting integration. The placement of accounting logic in legacy screens is not authoritative.

### Clean ownership

```text
Vehicle Rental
    agreements, rate versions, allocation/custody,
    replacement, Running Chart, commercial calculation source,
    customer/owner source-consumption identity

Vehicle
    physical identity, registration normalization,
    legal documents, odometer and shared availability

Customer / Supplier-or-approved-Party owner
    party/master identity and credit/supplier context

HR
    employee driver identity/availability

Invoice / approved payable-document owner
    financial-document lifecycle, balance, source allocations, reversal coordination

Payment
    receipts, payments, instruments, allocations and refunds

Tax
    tax determination and immutable tax snapshots

Finance
    account roles/profiles, periods, journals, ledger and GL reversal

Reporting
    cross-domain read models/statements

Audit
    immutable audit events
```

### Atomicity rule

A business source must not be marked financially posted if required Tax/Finance consequences fail. Where source/document/tax/journal are one business transition, commit them through the appropriate owner-controlled atomic boundary.

---

## 26. Reporting and reconciliation knowledge

### Operational/Running Chart reports observed

- Log Sheet — Lessee;
- Log Sheet — Lessor;
- replacement by original vehicle;
- replacement by replacement vehicle;
- vehicle log-entry checks;
- driver-wise overtime;
- self-drive movement;
- date/mileage/time/OT/night-out/garage-KM summaries.

### Customer reports observed

- customer/lessee ledger;
- vehicle ledger;
- agreement listing;
- invoice listing;
- aging/outstanding;
- statements;
- debit/credit notes;
- receipt/allocation/unallocated reports;
- tax/SVAT-related output.

### Owner reports observed

- owner/lessor ledger;
- vehicle-wise owner ledger/statement;
- agreement listing;
- payable listing;
- payment listing;
- owner balance/outstanding;
- unallocated transactions;
- fuel/repair deductions.

### Reconciliation/error procedures observed

Legacy menus/reports include:

- allocation errors;
- relationship/allocation inconsistencies;
- double-entry errors;
- source-transaction versus GL mismatches;
- bank reconciliation.

This proves reconciliation matters, but also shows a repair-oriented legacy design.

**Target:** prevent invalid states at authoritative write time. Reports verify integrity; they do not substitute for correct transactions.

---

## 27. Vehicle Service / workshop integration

The workshop video and TACGL establish this general workshop flow:

```text
Customer + Vehicle
    -> Job
    -> Material Issue
    -> Outside Work
    -> Labour
    -> Debtor Job Invoice
    -> Payment / close
```

### Ownership boundary

- Vehicle Service owns workshop jobs, inspections, labour, parts intent, outside-work intent, QA/service history.
- Vehicle owns physical identity, odometer and shared availability/holds.
- Vehicle Rental owns rental agreements, allocation/custody, Running Chart and rental commercial facts.

### Shared availability contract

A vehicle under a blocking workshop/off-road/breakdown condition is not eligible for conflicting Rental allocation/use. Vehicle Service publishes/owns its hold through the Vehicle availability boundary. Rental queries/enforces it without importing workshop business logic.

Rental must never clear a hold it does not own.

---

## 28. Legacy design mistakes that must not be copied

Evidence-backed legacy weaknesses include:

1. Duplicate physical Vehicle records using registration punctuation/spacing variants.
2. Commercial relationship context stored by duplicating/mutating vehicle master identity.
3. Rental billing embedded in workshop RMS/LCH/Outside Work structures.
4. Rental revenue posted into workshop sales categories such as Breakdown/Tinkering & Painting.
5. Regular rental/owner payments posted directly to free-text GL expense/bank vouchers.
6. Quantity/rate formulas stored in free-text descriptions while structured fields contain only totals.
7. At least one observed free-text excess-KM formula disagreeing with stored amount.
8. Raw GL/customer/lessor/vehicle codes exposed to operators.
9. Tax/account mapping mixed into operational forms.
10. Duplicated lessor workflows for owner versus leasing-company classifications.
11. Edit/Delete-style financial behavior and deleted-record mirrors.
12. After-the-fact allocation/double-entry/source-GL repair procedures.
13. Password register/numeric user-level security patterns.
14. Free-text payee and vehicle identity in financial vouchers.
15. Historical rates/amounts repeated without an obvious immutable effective-version source.
16. Mutable-looking bank-reconciliation handling rather than explicit bank-status events.
17. Vehicle `VEHTYP` field unsuitable as ownership truth in the inspected dataset.

Preserve the business capability behind these mechanisms; replace the mechanisms themselves.

---

## 29. Non-negotiable domain invariants

Unless later explicit business evidence supersedes them:

1. One physical vehicle has one stable Vehicle identity.
2. Registration formatting differences do not create a new vehicle.
3. Ownership/supply/customer-use relationships are effective-dated and historically preserved.
4. Lessee and Lessor Agreements are separate commercial contracts.
5. Customer-use coverage requires valid company-owned or lessor-supplied vehicle coverage.
6. One physical Running Chart/fact stream is shared operational evidence.
7. Customer billing and owner settlement are independent calculations.
8. Customer rates never determine owner payable rates.
9. Owner rates never determine customer billing rates.
10. Same finalized usage source cannot be consumed twice on the same commercial side.
11. Historical calculation freezes agreement/rate/usage/tax/source snapshots.
12. Finalized operational facts are immutable; correction preserves lineage.
13. Posted financial documents are immutable; correction uses governed reversal/adjustment.
14. Allocation, usage finalization, billing consumption, settlement consumption and financial allocation are concurrency-safe.
15. Every authoritative write is tenant/organization scoped and permission checked.
16. Vehicle-owned blocking availability prevents new conflicting Rental allocation/use.
17. Raw foreign keys/internal IDs are never typed by ordinary users.
18. Cross-module rules remain in the owning module and are consumed through explicit contracts.
19. Report balances derive from authoritative operational/financial sources rather than parallel mutable totals.
20. Business-significant transitions are auditable and historical values remain reconstructable.
21. Billing period boundaries come from the agreement cycle/policy, not an assumption that every monthly cycle is calendar month.
22. Commercial component quantity/rate/amount facts are structured; free text is explanatory only.
23. Third-party hire cost and customer recovery may be related for profitability but one side does not calculate the other.

---

## 30. Concurrency and data-integrity scenarios

High-risk races include:

- two users allocating the same vehicle for overlapping periods;
- owner-supply period changing during customer allocation;
- two users finalizing overlapping Running Charts;
- two billing officers consuming the same customer-side source;
- two settlement officers consuming the same owner-side source;
- replacement creation while original usage is finalized;
- workshop hold creation during Rental allocation;
- two cashiers allocating the same receipt/payment balance;
- posting and reversal running concurrently;
- rate/agreement version changing during commercial calculation.

Controls should include deterministic lock order, row versions, database constraints where appropriate, idempotent source identities and explicit conflict responses. Never hide a stale-write conflict with frontend workarounds.

---

## 31. UI/UX principles

The videos show direct back-office workflows. Preserve speed while moving integrity behind the UI.

Preferred operator flow:

```text
Lessor Agreement where required
    -> Lessee Agreement
    -> Select/Assign Vehicle
    -> Daily Running Chart
    -> Customer Billing / Owner Settlement queues
    -> Receipt / Payment
    -> Reports
```

Rules:

- search vehicles by registration/meaningful label;
- search customers/lessors by human-readable identity;
- no raw database IDs;
- no ordinary operator GL-code entry;
- show only relevant rate fields;
- progressive disclosure for optional/advanced data;
- show eligible source Running Charts before posting;
- show quantity × rate breakdown from structured facts;
- show agreement/rate effective dates;
- explain blockers such as overlap, stale version, workshop hold, closed period or already-consumed source;
- keep handover/return/replacement contextual;
- do not create a page for every backend table;
- do not add approval stages unless business governance requires them.

---

## 32. Business rules that must not be guessed

### Rental period / proration

- universal partial-month method;
- fixed-30-day versus actual-calendar-day policy;
- first/last-day inclusivity;
- early return/extension treatment;
- minimum billable period.

TACGL now provides fixed-30-day arithmetic **precedents**, but not enough evidence to promote them to a universal rule.

### Included/free kilometres and excess kilometres

- entitlement reset period;
- daily/monthly/agreement-cycle pooling;
- pooling across multiple Running Charts;
- pooling across replacement vehicles;
- exact Normal/By Hire/By Log Transaction modes;
- garage/internal mileage treatment;
- KM rounding.

### Replacement and downtime

- original versus replacement charging;
- owner payable during replacement;
- customer credit for downtime;
- owner deduction for downtime/off-road;
- partial-day replacement.

### Driver/time charges

- working-hour window;
- normal/double/triple OT qualification;
- weekend/holiday policy;
- OT rounding/minimum block;
- multi-driver split;
- night-out qualification/count;
- driver salary/recovery proration.

### Fuel, repair, accident and insurance

- fuel responsibility;
- garage fuel treatment;
- repair responsibility/markup;
- damage approval/evidence;
- accident/insurance excess responsibility;
- recovery priority among customer, owner, insurer and deposit.

### Deposit/adjustments

- deposit requirement/timing;
- application priority;
- refund/forfeiture;
- owner/customer advance treatment;
- debit/credit-note approval thresholds.

### Tax/accounting

- exact VAT/SVAT/SSCL applicability by date/party/transaction;
- withholding applicability;
- tax ordering;
- tax/currency rounding;
- FX policy;
- exact posting-profile catalogue for future Rental sources;
- source-specific reversal behavior not already centrally defined.

### Governance

- maker-checker requirements;
- agreement activation/termination permissions;
- Running Chart finalization authority;
- owner-deduction approval;
- posting/reversal authorities;
- payment preparation versus bank-reconciliation segregation.

### Operations not proven by source videos

- reservation lifecycle before agreement;
- photo/condition checklist requirements;
- fuel-level evidence;
- customer signature requirements;
- credit-limit hard-block rules;
- notification/reminder policy.

No implementation should select convenient defaults for these unresolved policies.

---

## 33. Video traceability summary

### `1.mp4`

Strong evidence around:

- Lessee Agreement;
- customer Credit Invoice;
- Daily/Replacement Running Chart concepts;
- Vehicle Owner Agreement;
- owner payable processing;
- owner statement/deductions;
- debit-note allocation;
- cheque/payment and reconciliation.

Representative timeline evidence includes approximately:

- 03-04 min — Lessee Agreement;
- 05-06 min — customer invoice/calculation;
- 11-15 min — owner agreement/Running Chart context;
- 24-27 min — owner agreement/payable;
- 30-36 min — owner statement/deductions/allocation;
- final minutes — cheque/GL/bank reconciliation.

### `Recording 2026-06-21 132314.mp4`

Strong evidence around:

- Vehicle Register;
- Lessee Register/Agreement;
- invoice/PDF;
- Daily Running Chart and reporting;
- excess-KM mode parameters;
- customer receipt/allocation;
- Vehicle Owner Agreement/statement;
- integrated Rental ledger;
- user/password register.

### `2.mp4`

Strong evidence for breadth of transaction/report inventory:

- lessor cash/petty-cash/cheque payments;
- lessor receipts/debit/credit notes;
- Payment Payable Processing;
- fuel/repair debit note;
- lessee payment/receipt/debit/credit/invoice/misc invoice;
- company/cost-centre/GL/payee/party/vehicle/driver/agreement registers;
- customer/owner/vehicle ledgers/statements;
- payable/payment/allocation/unallocated reports;
- Running Chart and replacement reports;
- allocation/double-entry/source-to-GL checks.

### `ScreenVideo_03-04-2026_18-02-52.mp4`

Authoritative supporting evidence for Vehicle Service/workshop integration only:

- vehicle/service reminders;
- workshop job;
- material issue;
- Outside Work;
- labour;
- debtor job invoice;
- item/stock context;
- shared vehicle availability implications.

It does not define Rental agreement/billing/settlement formulas.

---

## 34. TACGL traceability map

| Artifact | Evidence |
|---|---|
| `tacdata/gl.dbc` | Legacy database object inventory |
| `tacdata/scfveh.DBF` | Vehicle identities/context; six normalized duplicate-registration groups; all active rows `VEHTYP=03` |
| `tacdata/vehtyp.dbf` | Own/Hired/Outside labels; not reliable ownership truth for current `scfveh` data |
| `tacdata/scfchr.dbf` | Rental/hire/excess/driver-OT charge categories with zero master rates |
| `tacdata/jobtxn.DBF` | Rental/hire/excess/driver-OT lines, deleted/replaced lines and Outside Work transaction details |
| `REPORTS/prnstoown.FRT`, `prncrewon.FRT`, `prndebiow1.FRT` | Confirms `OWN` = Outside Work Order, including supplier/cost/customer/sales/margin reporting |
| `tacdata/scfjob.DBF` | RMS job context and linked invoices |
| `tacdata/scfinv.DBF` | Customer invoice relationship to job/vehicle/debtor |
| `tacdata/scftdb.DBF` | Debtor invoice/receipt-allocation history |
| `tacdata/scftcr.DBF` | Creditor/Outside Work payable history |
| `tacdata/scfacc.dbf` | Account vocabulary including Trade Debtors, Trade Creditors, Pending Jobs, workshop Sales and Rental Payment |
| `tacdata/scfglt.DBF` | Invoice GL lineage and direct `PRB` Rental Payment evidence |
| `tacdata/password.DBF` | Legacy security pattern that must not be copied |
| `del_*`, temp/backup artifacts | Mutable/repair-oriented legacy operational patterns, not target architecture |
| `PDFFILES/*.PDF` | Debtor Outstanding Age Analysis exports; 16 PDFs / 63 pages |
| `PDFFILES/*.XLS` | Legacy report/export artifacts |

---

## 35. Current AutoERP implementation status

At the authoritative engineering state reviewed before this refresh:

- active `app/Modules/VehicleRental` runtime is removed;
- active `resources/js/modules/vehicle-rental` frontend is removed;
- Vehicle Rental provider registration, tenant feature/catalogue, routes, navigation and entitlements are removed;
- Rental-specific Reporting runtime/tests are removed;
- new-tenant Rental-specific Finance seeds are removed;
- fresh-install Rental source migrations are removed;
- historical `InvoiceType::Rental` and necessary Finance/Payment vocabulary remain only for already-posted history;
- no destructive migration blindly drops already-deployed Rental historical tables/data;
- Vehicle and Vehicle Service remain active.

This documentation refresh does **not** restore the retired Rental runtime.

---

## 36. Minimum complete future Vehicle Rental release

A future first production-capable Rental release should be business-complete rather than table-complete.

Minimum scope:

1. Lessor Agreement for externally supplied vehicles.
2. Lessee Agreement.
3. Effective vehicle allocation/custody with overlap prevention.
4. Daily Running Chart with immutable finalized evidence.
5. Customer calculation from Lessee terms.
6. Customer Invoice through Invoice-owned lifecycle.
7. Owner calculation from Lessor terms.
8. Owner Payable Voucher/settlement through approved financial-document ownership.
9. Customer Receipt through Payment.
10. Owner Payment through Payment.
11. Customer/owner/vehicle/Running Chart statements and source drill-down.
12. Reversal/correction paths for every finalized/posted business document.
13. One-off/third-party vehicle sourcing capability when business requires it, without using Vehicle Service Outside Work as the Rental owner.
14. Agreement-cycle billing-period support rather than calendar-month-only assumptions.

### Release blockers

Do not call the implementation complete until it proves:

- one stable physical Vehicle identity;
- effective supply/use coverage;
- workshop/off-road availability blocking;
- agreement/rate version snapshots;
- Running Chart immutability/correction lineage;
- independent customer/owner calculations;
- structured quantity/rate/amount facts;
- same-side duplicate consumption prevention;
- atomic financial posting boundaries;
- receipt/payment allocation integrity;
- reversals restoring source eligibility correctly;
- tenant/organization isolation and granular permissions;
- real database concurrency safety for high-risk races;
- readable source -> document -> payment -> ledger traceability;
- explicit business decisions for every unresolved policy used by the release.

---

## 37. Knowledge maintenance rules

1. Do not silently convert assumptions into business rules.
2. Record whether new evidence is observed, derived, target design, precedent or approved policy.
3. Do not rewrite historical evidence to fit a new implementation.
4. Policy decisions should include owner, decision date, effective date, worked examples and acceptance tests.
5. Architecture changes must respect current `RULES.md` / `AGENTS.md` and module ownership.
6. This knowledge base is authoritative for captured business understanding; code is authoritative for what is actually implemented at a specific commit.
7. If implementation intentionally differs from legacy design, preserve business meaning and document why.
8. Historical TACGL rates/amounts are examples/precedents, never system defaults.
9. Historical names, account details and credentials are not requirements and must not be copied into tests/fixtures unnecessarily.
10. Keep `/docs/changes` append-only.
11. When a re-supplied TACGL archive has the same SHA-256, treat it as the same evidence corpus; do not pretend it is independent corroboration.
12. New deep-audit findings should strengthen or narrow evidence confidence without turning a single historical example into a universal rule.

---

## 38. Final domain conclusion

Vehicle Rental is not a Vehicle CRUD screen and not merely a customer Invoice feature. It coordinates:

- stable physical vehicle identity and availability;
- lessor/owner supply agreements;
- customer/lessee commercial agreements;
- effective allocation/custody;
- Daily/Replacement Running Chart evidence;
- independent revenue and owner-cost calculations;
- customer receivables and owner payables;
- receipts/payments/allocations;
- deposits/adjustments where applicable;
- Tax and Finance/GL;
- workshop/off-road availability;
- bank/cheque reconciliation;
- reporting and profitability;
- immutable historical traceability.

The most dangerous failure is the combination:

```text
wrong physical vehicle
+ wrong agreement/rate version
+ wrong billing cycle/effective period
+ conflicting allocation
+ bad/unstructured physical quantity
+ duplicated source consumption
= wrong customer billing and/or wrong owner settlement
```

The correct AutoERP foundation is:

> **one stable Vehicle identity + separate effective Lessee/Lessor agreements + effective supply/use allocation + one authoritative Running Chart + structured calculation facts + independent customer/owner commercial consumption + owning-module financial posting + immutable/reversible history.**

TACGL additionally proves that a future design must support non-calendar agreement cycles, third-party hire sourcing/recovery and detailed source-to-ledger traceability, while explicitly rejecting the legacy duplication, workshop embedding, free-text arithmetic and direct-expense-voucher weaknesses.
