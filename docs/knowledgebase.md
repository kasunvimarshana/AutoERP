# AutoERP Vehicle Rental Domain Knowledge Base

**Status:** Canonical business/domain knowledge for a future clean Vehicle Rental implementation  
**Knowledge capture date:** 2026-08-27  
**Authoritative engineering branch at capture:** `worktree-0.0.8`  
**Authoritative engineering commit at capture:** `3d690433253176375721af5706b232bdb5ff9564`  
**Business evidence:** all four supplied Vehicle Rental videos and the supplied TACGL legacy application/data archive  
**Architecture policy:** `RULES.md` and `AGENTS.md`

---

## 1. Purpose

This document consolidates the Vehicle Rental business knowledge demonstrated by the supplied videos and the TACGL legacy system into one maintainable domain reference for AutoERP.

It is deliberately **not** a specification to copy the legacy applications screen-for-screen, table-for-table, or transaction-code-for-transaction-code. The source systems contain valid business knowledge and also contain legacy design weaknesses. AutoERP must preserve the valid business meaning while following the project rules: fix root causes, keep clear module ownership, protect history, prevent invalid states at write time, avoid raw identifiers in the UI, and do not guess unconfirmed business policy.

As of the engineering commit named above, Vehicle Rental has been intentionally removed from the active AutoERP runtime. This knowledge base therefore describes the **business domain and the clean target boundaries for a future rebuild**, not a claim that these screens or workflows are currently active in AutoERP.

---

## 2. Source authority and evidence method

### 2.1 Business source set

The following four videos are authoritative business evidence:

| Video | Duration | Strongest Vehicle Rental evidence |
|---|---:|---|
| `1.mp4` | 40:50 | Lessee and lessor agreements, customer invoice calculation, Daily Running Chart, owner payable processing, owner deductions, allocations, cheque payment and bank reconciliation |
| `Recording 2026-06-21 132314.mp4` | 41:58 | Vehicle and lessee masters, lessee agreement, customer invoice/PDF, Daily Running Chart, customer receipt allocation, owner agreement/statement, integrated rental ledger, security/register screens |
| `2.mp4` | 21:14 | Broad lessor/lessee transaction menu, register inventory, reports, allocation-error procedures, GL double-entry checks and reconciliation procedures |
| `ScreenVideo_03-04-2026_18-02-52.mp4` | 12:24 | Workshop/Auto-care flow: vehicle/service reminders, job, material issue, outside work, labour, job invoice and item/stock concepts; relevant to Rental through shared vehicle availability only |

Total reviewed footage represented by the audit evidence is approximately **1 hour 56 minutes 26 seconds**.

The supplied `TACGL(3).zip` archive is also authoritative business evidence. It is a Visual FoxPro-era operational/accounting system with live-looking vehicle, workshop, billing and GL data. For reproducibility, the archive inspected for this knowledge capture had SHA-256:

`0e0733fff720072af4c3aaa787995ff128bfa79060a37739d6d2ebbe18a25313`

### 2.2 Engineering source set

The latest `worktree-0.0.8` branch is authoritative for AutoERP engineering state and module boundaries. `RULES.md` and `AGENTS.md` govern how legacy business meaning may be translated into maintainable AutoERP design.

### 2.3 Evidence labels used in this document

- **Observed — Video:** directly visible in a supplied video form, report, menu or printed output.
- **Observed — TACGL:** directly found in the supplied TACGL data/schema/report artifacts.
- **Cross-source conclusion:** supported by both evidence sets or strongly supported by multiple source facts.
- **Derived integrity requirement:** required to preserve the observed business safely; not a claim that the legacy system implemented it correctly.
- **Target design:** recommended AutoERP ownership or design based on business evidence plus project architecture rules.
- **Needs business confirmation:** the sources do not prove the exact policy. It must not be silently defaulted.

When video and TACGL evidence reflect different legacy workflows, this document records the difference rather than inventing a reconciliation rule.

---

## 3. Executive domain model

Vehicle Rental is a **dual-sided operational and financial domain**.

```text
Vehicle Owner / Lessor
    -> Lessor Agreement
    -> Vehicle supply / owner-side commercial terms
                              \
                               -> Daily Running Chart / usage evidence
                              /
Customer / Lessee
    -> Lessee Agreement
    -> Vehicle use / customer-side commercial terms

Daily Running Chart
    |-- Customer calculation using Lessee Agreement terms
    |      -> Customer Invoice
    |      -> Customer Receipt / Allocation
    |
    `-- Owner calculation using Lessor Agreement terms
           -> Owner Payable Voucher / Self-Billed Owner Settlement
           -> Owner Payment / Allocation
```

### Central invariant

> **One physical operational usage record is common evidence, while customer billing and owner settlement are independent commercial calculations.**

The customer amount must come from the Lessee Agreement/rate context. The owner payable must come from the Lessor Agreement/rate context. Customer revenue is not the formula source for the owner payable, and owner cost is not the formula source for the customer invoice.

Processing one commercial side must not block the other side. The same finalized usage source must not be consumed twice on the same commercial side.

This is the strongest Vehicle Rental principle established across the source material.

---

## 4. Canonical terminology

### 4.1 Lessee / Customer

The party receiving and using the rented vehicle. This is primarily the receivable/revenue side.

Typical business consequences:

- customer rental agreement;
- customer rental and usage charges;
- customer invoice;
- receipt and receipt allocation;
- debit/credit adjustments;
- deposit/advance where applicable;
- customer ledger, statement and outstanding balance.

### 4.2 Lessor / Vehicle Owner

The party providing a vehicle to the rental operation. The video system separates vehicle owners and leasing companies into different registers, but the valid business concept is one **Lessor** role with classifications where needed.

Typical business consequences:

- lessor agreement;
- vehicle-supply period;
- owner rental payable;
- reimbursable owner-side usage components;
- deductions such as supported fuel/repair deductions;
- payment and payment allocation;
- debit/credit adjustments;
- owner and vehicle-wise statements.

A future implementation must not duplicate the full settlement workflow merely because a lessor is an individual owner versus a company. Classification-specific policy should sit on a shared lessor concept.

### 4.3 Owner Payable Voucher / Self-Billed Owner Settlement

The legacy rental videos use wording such as **Payment Payable Voucher** and **Payment Payable Processing**. Its business meaning is not a customer-style sales invoice. The clean AutoERP term should be **Owner Payable Voucher** or **Self-Billed Owner Settlement**, subject to final accounting/legal naming approval.

### 4.4 Daily Running Chart

The central operational evidence for vehicle usage. It is not merely an invoice line and it is not owned by Invoice or Finance. It provides the physical facts from which each commercial side independently calculates amounts.

### 4.5 Allocation

A time-bounded relationship assigning a physical vehicle to a supply/use agreement and operational context. Legacy screens often select a vehicle directly in an agreement; that is acceptable as a simple UI action but insufficient as the persistent data model.

### 4.6 Replacement

A controlled substitution of one vehicle for another during an existing commercial period. The videos contain replacement Running Chart/report concepts, proving that original/replacement traceability matters even though the exact charging policy is not fully demonstrated.

### 4.7 `OWN` in TACGL job transactions

**Important semantic correction:** TACGL `jobtxn` transaction references beginning with `OWN` and `TXNTYPE = 2` mean **Outside Work**, not vehicle owner/lessor. TACGL report artifacts explicitly label these records as **Outside Work Order Note** / **Outside Work Order Invoice**. Never interpret the `OWN` prefix as owner settlement.

---

## 5. End-to-end business lifecycle

A complete Vehicle Rental lifecycle, reconstructed from the evidence, is:

```text
Reference / company / finance / tax setup
    -> Customer / Lessee setup
    -> Lessor / Vehicle Owner setup
    -> Driver setup where relevant
    -> Physical Vehicle setup
    -> Lessor Agreement / source coverage where vehicle is externally supplied
    -> Lessee Agreement
    -> Effective vehicle allocation / custody
    -> Handover or self-drive movement where applicable
    -> Daily or replacement Running Chart
    -> Finalized operational evidence
         |-- Customer calculation -> Customer Invoice -> Receipt / Allocation
         `-- Owner calculation    -> Owner Payable  -> Payment / Allocation
    -> Adjustments / deductions / deposit movements where applicable
    -> Cheque/bank lifecycle and reconciliation
    -> Operational, subledger, tax, GL and management reporting
    -> Governed reversal/correction when required
```

The customer and owner financial branches are **parallel consumers of shared usage evidence**, not a strict serial chain.

---

## 6. Master and reference data

### 6.1 Vehicle

**Observed — Video** Vehicle Register concepts include:

- registration / vehicle number;
- registered owner;
- address/contact context;
- registration date and first-registration/tax-related dates;
- licensing authority and transfer date;
- fuel type;
- body type;
- vehicle class / taxation class;
- year of manufacture;
- chassis number;
- engine number;
- colour;
- seating capacity;
- cylinder capacity;
- make and model;
- weight/dimensional attributes;
- vehicle type;
- GL asset code in the legacy UI;
- lessor code in the legacy UI;
- revenue licence expiry;
- insurance expiry.

**Observed — TACGL:** the vehicle table contains registration, debtor/context, make/name/address/contact and vehicle-type fields. A separate `vehtyp` table defines `OWN VEHICAL`, `HIRED VEHICAL`, and `OUTSIDE VEHICAL`, but every currently non-deleted vehicle row inspected carries type `03` (`OUTSIDE VEHICAL`). Therefore TACGL vehicle type is not trustworthy as authoritative ownership evidence.

**Target design:** one physical vehicle must have one stable identity. Ownership/supply/customer relationships belong in effective-dated relationship records, not duplicated vehicle masters.

### 6.2 Customer / Lessee

**Observed — Video** concepts include:

- customer/lessee code and name;
- address/contact;
- credit limit and opening/balance context;
- VAT/SVAT-related attributes;
- driver salary defaults;
- working hours;
- normal/double/triple overtime rates;
- night-out rates;
- customer ledger and statement reporting.

Master-level commercial amounts should be treated as templates/defaults only when the business confirms that behavior. The authoritative billed commercial terms must be the effective agreement/rate snapshot.

### 6.3 Lessor

**Observed — Video:** separate registers exist for vehicle owners and leasing companies, both participating in payable/transaction/report flows.

**Target design:** use one lessor/vehicle-supplier business role with classifications such as individual owner, company owner, or leasing company. Do not duplicate settlement engines by classification.

### 6.4 Driver

The videos show a Driver Register and driver-related agreement/running-chart values.

Relevant concepts include:

- driver identity;
- active status;
- licence/qualification data where required;
- assignment/availability history;
- working-hour context;
- salary/reimbursement basis where applicable;
- normal, double and triple overtime;
- night-out treatment.

The owning AutoERP module for employee drivers should remain HR where the driver is an employee; Vehicle Rental should reference approved driver identity/availability rather than duplicate employee master data.

---

## 7. Lessee / Customer Agreement

**Observed — Video** commercial concepts include:

- agreement date;
- agreement number;
- lessee/customer;
- agreement/execution/start/end dates;
- company or personal format;
- monthly or daily basis;
- selected vehicle in the legacy UI;
- maximum/included kilometres;
- base rate / rate for the included kilometre entitlement;
- excess kilometre rate;
- with-driver versus self-drive context;
- driver salary/recovery;
- working hours;
- normal/double/triple overtime;
- night-out;
- Non-AC / Front-AC / Dual-AC rate contexts in billing evidence;
- VAT/SVAT/SSCL-related configuration;
- security deposit;
- GL/account mappings in the legacy UI.

### Target agreement rules

- Customer and owner agreements remain separate aggregates.
- Commercial terms are effective-dated/versioned.
- An activated/executed version is immutable.
- A future rate change creates a new version; it does not rewrite history.
- Historical calculations snapshot the exact effective agreement/rate/tax context used.
- Raw GL accounts should not be exposed to ordinary rental operators; Finance/Tax configuration should resolve them through owned configuration.
- Vehicle selection may remain simple in the UI, but persistence must create an effective allocation rather than mutate the vehicle master or make the agreement-to-vehicle field the only source of truth.

---

## 8. Lessor / Vehicle Owner Agreement

**Observed — Video** lessor-side concepts include:

- lessor/owner;
- agreement number and dates;
- vehicle;
- monthly or daily basis;
- included/maximum kilometres;
- base rental payable;
- excess kilometre payable rate;
- with-driver/self-drive context;
- driver-related reimbursement values;
- overtime and night-out reimbursement context;
- payable/account mappings in the legacy UI;
- owner/vehicle statements and payable processing.

### Target agreement rules

- The Lessor Agreement is the authoritative commercial source for owner settlement.
- Customer price or customer invoice total must never be reused as owner payable logic.
- Owner-supply coverage must be effective-dated.
- For company-owned vehicles, an external lessor agreement/payable path is not required unless the business explicitly defines an internal charge model.
- Historical settlement calculations must retain the effective lessor agreement/rate snapshot.

---

## 9. Vehicle ownership, supply, allocation and custody

The legacy videos do not clearly prove a dedicated standalone lessor-allocation and lessee-allocation workflow. Vehicles are visibly selected directly in agreements and later reused in Running Charts.

That legacy UI simplicity should be preserved while correcting the data foundation.

### Target effective allocation record

At minimum, an allocation/custody record needs:

- stable vehicle identity;
- agreement identity and side;
- effective start and end;
- owner/supply relationship where applicable;
- customer-use relationship where applicable;
- driver or self-drive context;
- handover odometer;
- return odometer;
- operational status;
- original/replacement lineage;
- handover/return evidence references where the business requires them;
- row/version and audit metadata.

### Mandatory integrity controls

- Prevent overlapping blocking allocations for the same vehicle.
- Prevent allocation outside the agreement effective period.
- Prevent customer-use coverage that is not supported by a valid company-owned or lessor-supplied vehicle period.
- Prevent ownership/source mismatch.
- Prevent rental allocation while the Vehicle-owned availability timeline contains a blocking workshop/off-road/breakdown state.
- Preserve old allocation history; never overwrite a previous customer/owner relationship to represent a new period.
- Apply deterministic locking/version checks so two users cannot concurrently allocate the same vehicle into conflicting periods.

### Evidence from TACGL: why one stable vehicle identity matters

The TACGL vehicle table contains six normalized registration-number duplicate groups. Multiple examples use alternate registration punctuation/spacing to store what appears to be the same physical registration in different debtor/commercial contexts. This is valuable evidence of a legacy workaround and must **not** become AutoERP design.

Correct rule:

> **One physical vehicle = one stable Vehicle record. Commercial relationships change through effective-dated ownership/supply/use records, not through duplicate vehicle identities.**

---

## 10. Handover, return and replacement

**Observed — Video:** the rental transaction menus include self-drive handover/return notes, Daily Running Chart and Replacement Running Chart concepts. Reports distinguish original and replacement vehicles.

The evidence establishes the need for traceability but does not prove all charge rules.

### Target operational behavior

- Handover/return should be contextual actions on an allocation/custody period rather than unrelated global masters.
- Replacement must reference both the original and replacement vehicle and the effective replacement period.
- Odometer/fuel/condition evidence, where required by business policy, belongs to custody/handover evidence rather than invoice free text.
- Replacement must not silently break owner-supply coverage or create overlapping vehicle usage.
- A replacement vehicle under workshop/off-road restriction must not be selectable.

### Needs business confirmation

Exact customer charging, owner payment, included-KM allocation, downtime treatment and driver treatment during replacement are unresolved and must be explicitly approved before implementation.

---

## 11. Daily Running Chart — central operational evidence

The Running Chart is the heart of Vehicle Rental operations.

**Observed / strongly evidenced fields and concepts:**

- vehicle and agreement context;
- lessee/customer agreement;
- lessor/source agreement context;
- operational date or date range;
- start mileage;
- finish mileage;
- total kilometres;
- excess kilometres;
- garage/internal kilometres;
- start time;
- finish time;
- working hours;
- normal overtime;
- double overtime;
- triple overtime;
- night-out count;
- number of days/hires;
- AC/rate basis where applicable;
- driver context;
- original/replacement vehicle relationship;
- remarks/details;
- print/report output.

The customer invoice workflow visibly supports importing Running Chart data.

### Required integrity behavior

- End odometer cannot be below start odometer.
- Odometer continuity must be checked against adjacent custody/usage facts.
- Usage must fit valid agreement and allocation periods.
- Vehicle and driver timeline conflicts must be rejected.
- One physical usage event must not exist as multiple editable copies for customer and owner purposes.
- Finalized physical facts are immutable.
- Corrections preserve original facts and create correction/reversal lineage rather than silently editing history.
- Customer and owner commercial consumption are tracked separately.
- Each finalized source can be consumed at most once per commercial side unless the business explicitly supports controlled partial consumption, in which case source quantities must be allocated exactly and idempotently.
- Customer processing does not block owner processing and vice versa.

### Lifecycle boundary

The recordings do not prove a mandatory maker-checker state machine. A future implementation should use the minimum business-approved lifecycle. `Draft -> Finalized` is sufficient if no approval requirement exists. If maker-checker is required, explicit submitted/approved states may be added. Do not invent approval stages merely because they are technically possible.

---

## 12. Customer billing

**Observed — Video:** the Credit Invoice flow includes customer/agreement/date range, total and excess kilometres, overtime, night-outs, days/hires, rental rate, excess-KM rate, driver-related recovery, tax, total, Running Chart import, processing and print/PDF output.

A representative observed billing composition is:

```text
Base rental
+ Excess kilometre charge
+ Driver salary recovery
+ Normal overtime
+ Double overtime
+ Triple overtime
+ Night-out recovery
+ Approved parking / other recoveries
+ Other approved miscellaneous charge
- Approved discount / credit adjustment
+ Effective tax
= Customer invoice total
```

This is a **component model**, not permission to invent default formulas for components whose exact policy is unconfirmed.

### Customer billing rules

- Use the effective Lessee Agreement/rate version for the usage period.
- Load only eligible finalized customer-side unconsumed usage facts.
- Show a human-readable quantity x rate breakdown before posting.
- Persist exact calculation source identities and rate/tax snapshots.
- Prevent duplicate customer-side consumption.
- The Rental domain should prepare the commercial calculation/source plan; the Invoice module owns the financial document lifecycle, balance, posting state and reversal.
- Tax owns authoritative tax determination/snapshot.
- Finance owns account resolution, journal and ledger posting.
- Posted financial documents are immutable; correction uses governed reversal/debit/credit mechanisms.

### Minimum historical calculation snapshot

A posted customer calculation should be reproducible from stored facts including:

- agreement and version;
- allocation/custody identity;
- usage source identities;
- calculation run/source fingerprint;
- rate components and units;
- included-kilometre entitlement used;
- actual/commercial/excess kilometres used;
- driver/OT/night-out facts used;
- original/replacement context where relevant;
- tax snapshot identity;
- rounding policy identity;
- invoice source allocations.

---

## 13. Owner / Lessor settlement

The owner side is an independent cost/payable calculation.

**Observed — Video:** owner payable processing, owner statements, cash/petty-cash/cheque payments, receipts, debit/credit notes, fuel/repair deductions, allocations, vehicle-wise statements and unallocated transaction reports.

A source-supported component model is:

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

Exact tax/withholding order and rounding remain policy decisions unless separately approved.

### Owner settlement rules

- Use the effective Lessor Agreement/rate version.
- Do not derive owner payable from customer invoice total or customer rates.
- Use eligible finalized owner-side unconsumed usage facts.
- Prevent same-side duplicate settlement.
- Preserve vehicle as an analytical dimension where settlement is vehicle-specific.
- Fuel/repair deductions must reference one authoritative evidence/source path to avoid duplicate recovery.
- Posted owner payable is immutable.
- Owner payable, Tax and Finance posting must be coordinated through the owning modules and committed atomically.
- Payment allocation must be concurrency-safe and cannot over-allocate an open payable.

---

## 14. TACGL rental billing evidence

TACGL is not organized as a clean standalone Vehicle Rental module, but it contains strong evidence of real hire/rental charging behavior.

### 14.1 TACGL structure inspected

The supplied archive contains **452 files** excluding directories. Major artifact groups include DBF data tables, FRX/FRT reports, CDX/IDX indexes, PDFs/XLS exports and Visual FoxPro runtime/application binaries.

The `gl.dbc` database container contains the following non-deleted object counts:

- 39 tables;
- 910 fields;
- 122 indexes;
- 5 database objects.

Relevant non-deleted data volumes inspected include approximately:

- `scfveh`: 1,076 vehicle rows;
- `scfjob`: 6,653 job rows;
- `jobtxn`: 23,645 job transaction rows;
- `scfinv`: 6,630 invoice rows/records;
- `scfglt`: 78,760 GL rows;
- `scfdeb`: 379 debtor/customer rows;
- `scfcre`: 29 creditor rows;
- `scftdb`: 13,775 debtor-subledger rows;
- `scftcr`: 5,083 creditor-subledger rows;
- `scftxn`: 10,377 transaction rows;
- `scfsmf`: 483 item/service master rows;
- `scfacc`: 78 GL account rows;
- `scfchr`: 58 service/charge codes.

Counts are evidence about the inspected archive, not an AutoERP schema recommendation.

### 14.2 Rental charge vocabulary

`scfchr` contains explicit charge codes with zero master rates:

| Code | Description | Master rate in inspected data |
|---|---|---:|
| `HIRIN` | Hiring charges for with-driver monthly-basis car | 0 |
| `EXCES` | Excess charges for with-driver monthly-basis car | 0 |
| `RENT1` | Hiring charges for self-drive monthly-basis car | 0 |
| `HIRE1` | Hiring charges for with-driver van | 0 |
| `OT100` | Driver overtime | 0 |

This is strong evidence that the code identifies a charge category while the actual commercial amount/rate comes from transaction/contract context rather than a universal charge-master rate.

### 14.3 Rental/hire lines inside workshop job transactions

TACGL `jobtxn` contains rental/hire descriptions such as:

- monthly with-driver car hiring;
- excess kilometre charges;
- monthly self-drive car hiring;
- with-driver van hiring;
- driver overtime;
- daily self-drive hiring;
- explicit excess-kilometre quantity x rate descriptions.

Some third-party vehicle hire/customer-billing examples are carried as `TXNTYPE = 2` / `OWN...` references. The report artifacts prove this is **Outside Work Order**, demonstrating that the legacy business sometimes routed hired-vehicle cost/billing through workshop outside-work constructs.

**Target conclusion:** preserve the valid capability to recover third-party vehicle hire and related costs, but do not model Vehicle Rental as an Outside Work subtype or a Vehicle Service workaround.

### 14.4 TACGL job transaction type semantics

The inspected active job transactions map cleanly by reference prefix:

- `TXNTYPE = 1` -> `MIN...` -> Material Issue;
- `TXNTYPE = 2` -> `OWN...` -> Outside Work;
- `TXNTYPE = 3` -> `LCH...` -> Labour / service charge.

Report artifacts explicitly include `Material Issue Note Analysis`, `Outside Work Order Note`, `Outside Work Order Invoice`, `Current Mileage`, and `Labour Charge` wording.

This prevents a dangerous inference: **`OWN` is not evidence of a vehicle-owner transaction.**

### 14.5 TACGL rental payment evidence

The GL account master contains:

`7048-000 RENTAL PAYMENT`

The inspected GL data contains 25 debit-side rows to this account, all using `PRB...` bank-payment references. Descriptions include monthly rental payments, multi-day rental payments, jeep-hire payments and hiring payments. Some voucher references aggregate multiple vehicle/payee detail lines against one bank credit.

This demonstrates an actual legacy practice where regular vehicle hire/owner payments could be recorded as direct bank-voucher rental expense using free-text payee/vehicle descriptions rather than a structured owner-agreement -> payable -> settlement chain.

**Target conclusion:** the underlying payable/payment business event is valid; the free-text direct-expense architecture is not. Future AutoERP should use structured lessor, agreement, vehicle, settlement source and Payment/Finance ownership.

### 14.6 TACGL invoice/job integration

TACGL shows rental/hire charges attached to jobs and later invoice records linked to job/vehicle/debtor context. Examples include daily with-driver hire, driver overtime/bata-like charges, excess kilometres, meals and parking/pass-like charges aggregated for customer billing.

These examples prove that rental profitability can span:

- vehicle hire cost;
- driver cost/reimbursement;
- excess mileage;
- incidental recoveries;
- customer invoice revenue.

They do **not** prove that Workshop Job is the correct owner of those facts in AutoERP.

### 14.7 TACGL security and history smells

The inspected archive contains:

- a `password` table with password and user-level fields;
- numeric/string-style user levels;
- deleted-record mirror tables such as `del_*`;
- many temporary DBFs;
- free-text financial references and payees;
- backup/archive artifacts inside the application folder.

These are historical evidence, not design requirements. Never copy plaintext-like password storage, numeric security levels, mutable/deleted mirrors, or free-text financial identity into AutoERP.

---

## 15. Rates and commercial examples

The legacy sources show real examples of monthly, daily, self-drive, with-driver, excess-kilometre and driver-overtime charging. Any numeric values seen in historical transactions are **examples only** and must never be converted into system defaults.

Rate authority must be:

```text
Effective Agreement Version
    -> effective charge/rate component
    -> usage quantity from finalized operational evidence
    -> deterministic calculation snapshot
```

A charge master may classify a charge, but it must not silently override the effective agreement.

---

## 16. Deposits, advances, refunds and adjustments

**Observed — Video:** security-deposit fields are present and both lessor and lessee transaction menus contain receipts, payments, debit notes and credit notes.

The existence of those transaction types proves bidirectional financial adjustments are required, but the recordings do not prove a full deposit state machine.

### Target integrity requirements

- Distinguish customer receipt, customer advance and rental security deposit semantically.
- Preserve deposit/advance balance independently from ordinary invoice allocation when accounting treatment differs.
- An adjustment must reference party, source context, reason and supporting evidence where applicable.
- Allocation cannot exceed open balance.
- Refund must reference the original economic source.
- Reversal must preserve the original transaction and create compensating history.
- Do not use a generic free-text debit/credit note to bypass the original commercial context.

### Needs business confirmation

- deposit due timing;
- whether deposit is mandatory by agreement type;
- deposit application priority;
- partial application rules;
- refund timing;
- forfeiture conditions;
- treatment on early termination, damage or accident;
- tax treatment of deposit/application/forfeiture.

---

## 17. Receipts, payments and allocations

**Observed — Video:** the source system supports cash, petty-cash, cheque, receipts, payments, debit/credit notes, allocations and unallocated transaction reports on both commercial sides.

### Customer side

Typical valid semantics include:

- invoice receipt;
- partial receipt;
- receipt allocated across multiple invoices;
- unallocated customer advance;
- security-deposit receipt;
- customer refund where applicable;
- allocation reversal;
- receipt reversal.

### Owner side

Typical valid semantics include:

- owner payable payment;
- partial payment;
- payment allocated across multiple owner payables;
- owner advance where approved;
- owner receipt/refund where applicable;
- allocation reversal;
- payment reversal.

### Mandatory controls

- Party, currency and document direction must match.
- Allocation cannot exceed the document open balance or available payment amount.
- Concurrent actors cannot consume the same open balance twice.
- Initial payment posting and later allocation reclassification must not double-post.
- Reversal coordinates active allocations and downstream balances.
- Payment and Invoice modules own the financial lifecycle; Rental supplies business source semantics.

---

## 18. Cheque lifecycle and bank reconciliation

**Observed — Video** cheque/payment fields and actions include:

- payment date;
- voucher/reference number;
- bank code/account;
- cheque number and date;
- crossed/bearer/account-payee-style options;
- payee;
- amount;
- description/detail lines;
- GL detail in the legacy UI;
- realized date;
- payment/cheque print;
- bank reconciliation edit/realization workflow.

### Target rules

- Cheque number uniqueness should be scoped correctly to bank account/cheque book.
- Payment economic facts become immutable after posting/issue.
- Realization/clearing/reconciliation must not rewrite original payment date, payee or amount.
- Bank reconciliation should create or update a controlled bank-status/reconciliation event, not turn a posted payment into a freely editable record.
- Bounced/stopped/cancelled/replaced behavior must use explicit states/events and audit history.

Exact cheque lifecycle policy remains subject to Payment/Finance requirements.

---

## 19. Tax and General Ledger integration

The video system visibly exposes VAT, SVAT, SSCL, rental-income accounts, excess-KM accounts, payable accounts and other GL mappings inside commercial screens. TACGL also demonstrates direct GL account usage for rental payments.

The business requirement is strong accounting integration; the legacy placement of accounting logic is not authoritative architecture.

### Clean ownership

```text
Vehicle Rental
    owns agreements, allocations, custody, operational facts,
    commercial calculation inputs and source-consumption identity

Invoice
    owns customer/owner financial-document lifecycle where that document type belongs,
    balances, posting state, financial reversal coordination

Payment
    owns receipts, payments, instruments, allocations, refunds and payment reversal

Tax
    owns tax rules, effective determination, snapshots and tax reversal facts

Finance
    owns chart of accounts, posting profiles, periods, journals, ledgers and GL reversal
```

### Integrity requirement

A source event must not be marked financially posted while its required Tax/Finance consequences fail. Where a workflow requires all of them, the source/document/tax/journal transition must be atomic or coordinated through a reliable owner-controlled transaction boundary.

Reconciliation reports should verify integrity, not compensate for weak transaction design.

---

## 20. Reporting and reconciliation knowledge

### 20.1 Running Chart / operational reports

Observed report concepts include:

- Log Sheet — Lessee;
- Log Sheet — Lessor;
- replaced vehicles by original vehicle;
- replaced vehicles by replacement vehicle;
- vehicle log-entry checks;
- driver-wise overtime calculation;
- self-drive vehicle movement;
- date/mileage/time/OT/night-out/garage-kilometre summaries.

### 20.2 Customer reports

Observed concepts include:

- customer/lessee ledger;
- vehicle ledger;
- agreement listings;
- invoice listings;
- aging/outstanding;
- statements;
- debit/credit-note reports;
- receipt and allocation/unallocated reports;
- tax/SVAT-related output.

### 20.3 Owner reports

Observed concepts include:

- owner/lessor ledger;
- vehicle-wise owner ledger/statement;
- agreement listing;
- owner payable listing;
- payment listing;
- owner outstanding/balance;
- unallocated transactions;
- fuel/repair deductions.

### 20.4 Accounting/reconciliation procedures

The legacy menus include procedures/reports for:

- allocation errors;
- relationship/allocation inconsistencies;
- double-entry errors;
- source-transaction versus GL mismatches;
- cheque/bank reconciliation.

This is important architectural evidence: the legacy applications are partly **repair-oriented**, detecting invalid states after the fact. AutoERP must be **prevention-oriented** where possible: validate relationships, source consumption and balanced posting before commit; keep reconciliation as verification rather than a repair substitute.

### Reporting rule

Reports should be read models derived from authoritative operational and posted financial records. Do not create independent report-owned balances that can drift from the source ledgers.

---

## 21. Vehicle Service / workshop integration

`ScreenVideo_03-04-2026_18-02-52.mp4` and TACGL together establish a strong workshop boundary.

**Observed workshop concepts:**

```text
Customer + Vehicle
    -> Job
    -> Material Issue
    -> Outside Work
    -> Labour Charge
    -> Debtor Job Invoice
    -> Payment / close
```

Additional evidence includes service reminders, mileage, item/stock control, creditor selection, outside work and job costing.

### Module boundary

- Vehicle Service owns workshop jobs, inspections, labour, parts intent, outside-work intent, QA/service history.
- Vehicle owns shared physical identity, odometer history and availability timeline/holds.
- Vehicle Rental owns rental allocation/custody/usage/commercial facts.

### Shared availability contract

A vehicle that is under a blocking workshop/off-road/breakdown condition must not remain freely rentable. Vehicle Service should publish/own its hold through the Vehicle-owned availability contract; Vehicle Rental should query/enforce that contract without importing workshop business logic.

Likewise, Rental must not clear or mutate a Vehicle Service hold it does not own.

---

## 22. Legacy design mistakes that must not be copied

The following are evidence-backed legacy weaknesses, not compatibility requirements:

1. **Duplicate physical vehicles using alternate registration formatting** to represent different commercial contexts.
2. **Vehicle selected directly in agreements as the only relationship model** with insufficient effective allocation history.
3. **Rental/hire billing embedded in workshop job transaction types** instead of a clear Rental-owned operational source.
4. **Regular rental/owner payments recorded as direct free-text GL bank expenses** instead of a structured lessor settlement chain.
5. **Raw GL, customer, lessor, vehicle and transaction codes exposed to users.**
6. **Tax/GL mapping mixed into operational/commercial forms.**
7. **Separate duplicated lessor workflows for owner versus leasing-company classifications.**
8. **Edit/Delete-style legacy financial screens** that do not prove immutable posted history.
9. **After-the-fact allocation/double-entry/source-GL repair procedures** rather than prevention at authoritative write time.
10. **Password register / numeric user-level security patterns.**
11. **Free-text payee and transaction identity** where structured party/source links are required.
12. **Deleted-record mirrors and temporary-table patterns** as business history mechanisms.
13. **Historical rates repeated across forms without an obvious immutable effective-version source.**
14. **Mutable-looking bank reconciliation** instead of bank-status/reconciliation events.

AutoERP must preserve the legitimate business capability behind these mechanisms while replacing the mechanism itself with a clean foundation.

---

## 23. Clean AutoERP module ownership for a future rebuild

The future Rental implementation should remain small in responsibility and integrate with existing owner modules.

| Capability | Owning module / boundary |
|---|---|
| Physical vehicle identity, normalized registration, legal documents, odometer, shared availability | **Vehicle** |
| Customer identity and credit profile | **Customer** |
| Vehicle owner/lessor identity and supplier-like financial identity where applicable | **Supplier / Party owner boundary approved by project architecture** |
| Employee driver identity/availability | **HR** |
| Rental agreements, rate versions, rental allocation/custody, replacement, Running Chart, rental-specific commercial calculation source and source-consumption identity | **Vehicle Rental** |
| Workshop/service availability holds and workshop work | **Vehicle Service** through Vehicle availability boundary |
| Customer/owner financial document lifecycle and balances | **Invoice or the approved financial-document owner** |
| Receipts, payments, cheques, allocations, refunds | **Payment** |
| Tax determination and immutable tax snapshot | **Tax** |
| Posting profiles, journals, ledgers, accounting periods and reversal | **Finance** |
| Cross-domain statements/dashboards/read projections | **Reporting** |
| Audit events | **Audit** |

Do not rebuild customer Invoice, Payment, Tax or GL engines inside Vehicle Rental.

---

## 24. Non-negotiable domain invariants

A future implementation must preserve all of the following unless explicit business evidence supersedes them:

1. **One physical vehicle has one stable Vehicle identity.**
2. Registration-number formatting differences do not create new physical vehicles.
3. Ownership/supply/use relationships are effective-dated and historically preserved.
4. Lessor and Lessee agreements are separate commercial contracts.
5. Customer-use coverage must be supported by a valid company-owned or owner-supplied vehicle period.
6. One physical Running Chart/usage fact stream is the shared operational evidence.
7. Customer billing and owner settlement are independent calculations.
8. Customer rates never determine owner payable rates.
9. Owner payable values never determine customer billing rates.
10. Same finalized usage source cannot be consumed twice on the same commercial side.
11. Historical calculation freezes agreement/rate/usage/tax/source snapshots.
12. Finalized operational facts are immutable; correction preserves lineage.
13. Posted financial documents are immutable; correction uses governed reversal/adjustment.
14. Allocation, usage finalization, billing consumption, settlement consumption and financial allocation are concurrency-safe.
15. Every authoritative write is tenant/organization scoped and permission checked.
16. A Vehicle-owned blocking availability state prevents new conflicting rental allocation/use.
17. Raw foreign keys and internal codes are never typed by ordinary users.
18. Cross-module business logic stays in the owning module and is consumed through explicit contracts.
19. Reported balances derive from authoritative source records, not parallel mutable report totals.
20. Audit/history is append-only for business-significant transitions.

---

## 25. Concurrency and data-integrity scenarios

Project rules require assuming multiple actors can act simultaneously. High-risk Rental races include:

- two users allocating the same vehicle for overlapping periods;
- owner-supply period changed while customer allocation is being created;
- two users creating/finalizing overlapping Running Charts;
- two billing officers consuming the same Running Chart customer-side;
- two settlement officers consuming the same Running Chart owner-side;
- replacement created while the original allocation/usage is being finalized;
- workshop hold created while a rental allocation is being made;
- two cashiers allocating the same receipt/payment balance;
- posting and reversal initiated concurrently;
- rate/agreement version changed while a calculation is being approved.

Correct controls include deterministic lock order, row/version checks, database constraints where appropriate, idempotent source identities and explicit conflict responses. Do not hide stale-write failures in the frontend.

---

## 26. UI/UX principles

The videos demonstrate a back-office workflow that is relatively direct. The new UI should preserve that speed while moving integrity controls behind the scenes.

### Preferred operator flow

```text
Owner / Lessor Agreement where required
    -> Customer / Lessee Agreement
    -> Select Vehicle
    -> Daily Running Chart
    -> Customer Billing
    -> Owner Settlement
    -> Receipt / Payment
    -> Reports
```

Customer billing and owner settlement remain parallel queues even if the navigation presents them sequentially.

### UI rules

- Search vehicles by registration/meaningful label, never raw database ID.
- Search customers/lessors by human-readable identity.
- Do not ask operators to type GL account IDs/codes for normal Rental work.
- Show only rate fields relevant to the selected agreement scenario.
- Use progressive disclosure for optional charges/advanced accounting context.
- Show source Running Charts and quantity x rate calculation before financial posting.
- Show effective agreement/rate dates clearly.
- Explain why an action is blocked: overlap, stale version, workshop hold, closed period, already consumed source, etc.
- Keep handover/return/replacement contextual to a vehicle allocation rather than creating unnecessary global pages.
- Do not create a UI page for every backend table.
- Do not add approval steps unless the business explicitly requires maker-checker.

---

## 27. Business rules that must not be guessed

The available sources do **not** safely prove the exact policy for the following. Each item requires an explicit decision owner, effective date, worked example and acceptance tests before becoming production behavior.

### Rental period / proration

- partial-month proration method;
- first/last day inclusivity;
- fixed-30-day versus actual-calendar-day behavior;
- early return and extension handling;
- minimum billable day/period.

### Included/free kilometres and excess kilometres

- included-KM reset period;
- daily versus monthly/period pooling;
- pooling across multiple Running Charts;
- pooling across replacement vehicles;
- exact semantics of by-log/by-hire/period excess calculation modes;
- treatment of garage/internal mileage;
- rounding of kilometre quantities.

### Replacement and downtime

- whether replacement day is billed on original or replacement terms;
- owner payable during replacement;
- customer credit/deduction for downtime;
- owner deduction for downtime/off-road periods;
- how partial-day replacement is treated.

### Driver and time charges

- exact working-hour window;
- normal/double/triple OT qualification;
- weekend/holiday rule;
- OT rounding/minimum block;
- multi-driver split;
- night-out qualification and count;
- driver salary/recovery proration.

### Fuel, repair, accident and insurance

- fuel responsibility and return-level policy;
- garage/internal fuel treatment;
- repair responsibility and markup/recovery;
- damage approval/evidence requirements;
- accident/insurance excess responsibility;
- recovery priority between customer, owner, insurer and deposit.

### Deposit and adjustments

- deposit requirement and due timing;
- deposit application priority;
- refund and forfeiture policy;
- treatment of advances;
- owner advance handling;
- debit/credit-note approval threshold.

### Tax and accounting

- exact VAT/SVAT/SSCL applicability by transaction/date/party;
- withholding applicability;
- tax calculation order;
- tax and currency rounding;
- foreign-currency policy;
- exact GL posting catalogue for each Rental source;
- accounting-period and reversal policy where not already centrally defined.

### Workflow governance

- exact maker-checker requirements;
- who may activate/terminate agreements;
- who may finalize Running Charts;
- who may approve owner deductions;
- who may post/reverse invoices/payables/payments;
- segregation of payment preparation and bank reconciliation.

### Customer operations not proven by recordings

- reservation/booking lifecycle before agreement;
- handover/return photo requirements;
- condition/damage checklist;
- fuel-level evidence;
- customer signature requirements;
- credit-limit/overdue hard-block policy;
- notification/reminder policy.

No implementation should choose convenient defaults for these policies merely because an old screen, old code branch, or historical transaction suggests one example.

---

## 28. Video traceability map

### 28.1 `1.mp4`

Representative business evidence:

- approximately 04:00 — Lessee Agreement, including customer, agreement, vehicle, period, monthly/daily, maximum KM, excess KM, driver, tax/account and deposit context;
- approximately 06:00 — customer Credit Invoice with Running Chart/agreement-driven quantities and financial components;
- approximately 08:00 — Daily Running Chart, Replacement Running Chart, self-drive handover and return menu concepts;
- approximately 12:00 and 24:00 — Vehicle Owner Agreement and condition/rate context;
- approximately 16:00 — daily customer invoice example using hires/days, total and excess KM, rate and VAT;
- approximately 20:00 — Lessee reports;
- approximately 28:00 — Vehicle Owner reports;
- approximately 32:00 — Lessor Debit Note Allocation;
- approximately 36:00 — vehicle-wise owner statement containing rental payable, settlement and deductions;
- approximately 40:00 — cheque/payment and reconciliation output.

### 28.2 `Recording 2026-06-21 132314.mp4`

Representative business evidence:

- approximately 01:00 — Vehicle Register;
- approximately 02:00 — Lessee Register with contact/credit/tax/driver/OT/night-out context;
- approximately 03:30-04:00 — Lessee Agreement;
- approximately 06:00 — customer invoice;
- approximately 08:00 — invoice/PDF;
- approximately 11:00-18:00 — Daily Running Chart and operational/rate examples;
- approximately 15:30 — user/password register;
- approximately 18:30-24:00 — Running Chart report parameters/output and excess-KM mode context;
- approximately 26:30 — customer receipt;
- approximately 27:30 — receipt allocation;
- approximately 30:30 — Vehicle Owner Agreement;
- approximately 33:30 — owner statement;
- approximately 37:30 — integrated rental ledger;
- final section — invoice/tax/report output.

### 28.3 `2.mp4`

Strongest evidence is the menu/report inventory:

- lessor cash/petty-cash/cheque payments;
- lessor receipts;
- lessor debit/credit notes;
- Payment Payable Processing;
- fuel/repair debit note;
- lessee payment/receipt/debit/credit/invoice/miscellaneous invoice transactions;
- company/cost-centre/GL/payee/lessee/lessor/vehicle/driver/month/agreement registers;
- lessee, lessor and vehicle ledgers/statements;
- invoice/payable/payment/allocation/unallocated reports;
- Running Chart original/replacement/log/driver-OT/self-drive reports;
- allocation error, double-entry and source-to-GL reconciliation procedures.

### 28.4 `ScreenVideo_03-04-2026_18-02-52.mp4`

This video is not authoritative for Rental-specific formulas. It is authoritative supporting evidence for:

- vehicle/service-reminder context;
- workshop job lifecycle;
- material issue;
- outside work;
- labour;
- debtor job invoice;
- stock/item context;
- the need for a shared Vehicle availability boundary between Rental and Vehicle Service.

---

## 29. TACGL traceability map

Key inspected artifacts and their business meaning:

| TACGL artifact | Evidence |
|---|---|
| `tacdata/gl.dbc` | Legacy database object inventory; 39 active tables, 910 fields, 122 indexes |
| `tacdata/scfveh.DBF` | Vehicle identities and debtor/context; proves duplicate-registration workaround and unreliable current `VEHTYP` ownership meaning |
| `tacdata/vehtyp.dbf` | Defines Own/Hired/Outside vehicle labels, but current `scfveh` rows are all Outside; not ownership truth |
| `tacdata/scfchr.dbf` | Rental charge-category codes for with-driver monthly, excess KM, self-drive monthly, van hire and driver OT; zero master rates |
| `tacdata/jobtxn.DBF` | Job transaction charge lines including rental/hire/excess/driver-OT examples |
| `REPORTS/prncrewon.FRT` and related reports | Confirms `OWN` transaction reference means Outside Work Order, not Owner |
| `tacdata/scfinv.DBF` | Job/vehicle/debtor invoice integration |
| `tacdata/scfacc.dbf` | Contains `7048-000 RENTAL PAYMENT` account |
| `tacdata/scfglt.DBF` | Direct bank-voucher rental/hire payment evidence and free-text payee/vehicle detail |
| `tacdata/scfdeb.DBF` / `scfcre.dbf` | Debtor/creditor masters |
| `tacdata/scftdb.DBF` / `scftcr.DBF` | Debtor/creditor subledger evidence |
| `tacdata/password.DBF` | Legacy password/user-level security pattern that must not be copied |
| `del_*`, `temp*`, backup artifacts | Legacy mutation/repair/operational patterns; not target architecture |

---

## 30. Current AutoERP implementation status

As of authoritative commit `3d690433253176375721af5706b232bdb5ff9564`:

- the active `app/Modules/VehicleRental` backend implementation has been removed;
- the active `resources/js/modules/vehicle-rental` frontend implementation has been removed;
- Vehicle Rental provider registration, tenant feature/catalogue entries, routes, navigation and entitlements have been removed;
- Rental-specific Reporting runtime and tests have been removed;
- new-tenant Rental-specific Finance seeds have been removed;
- fresh-install Rental source migrations have been removed;
- historical `InvoiceType::Rental` and necessary Finance/Payment vocabulary remain only to interpret already-posted historical records safely;
- no destructive migration blindly drops already-deployed Rental tables;
- Vehicle and Vehicle Service remain active and must not be confused with the retired Rental runtime.

This knowledge base intentionally does **not** restore any of the removed runtime code. It is the business/domain source for a clean future design.

---

## 31. Minimum complete future Vehicle Rental release

A first production-capable release should be business-complete rather than table-complete.

### Core operational/commercial scope

1. Lessor/Vehicle Owner Agreement for externally supplied vehicles.
2. Lessee/Customer Agreement.
3. Effective vehicle allocation/custody with overlap prevention.
4. Daily Running Chart with immutable finalized evidence.
5. Customer calculation from Lessee Agreement terms.
6. Customer Invoice through Invoice-owned lifecycle.
7. Owner calculation from Lessor Agreement terms.
8. Owner Payable Voucher / settlement through the approved financial-document owner.
9. Customer Receipt through Payment.
10. Owner Payment through Payment.
11. Basic customer/owner/vehicle/Running Chart statements and source drill-down.
12. Reversal/correction paths for every posted or finalized business document.

### Release blockers

Do not call a future Rental implementation complete until it proves:

- one stable physical Vehicle identity;
- effective owner/customer allocation coverage;
- workshop/off-road availability blocking;
- agreement/rate version snapshots;
- Running Chart immutability and correction lineage;
- independent customer/owner calculation;
- same-side duplicate consumption prevention;
- atomic financial posting boundaries;
- receipt/payment allocation integrity;
- reversal restoring source availability correctly;
- tenant/organization isolation and granular permissions;
- real database concurrency behavior for high-risk races;
- readable source-to-document-to-ledger traceability;
- explicit decisions for every commercially material unresolved policy used in the release.

---

## 32. Knowledge maintenance rules

1. Do not silently convert assumptions into business rules.
2. When new source evidence appears, record whether it is observed, derived, target design or policy decision.
3. Do not rewrite historical evidence to match a new implementation.
4. Business policy decisions should include owner, decision date, effective date, worked examples and acceptance tests.
5. Architecture changes must respect module ownership and current `RULES.md` / `AGENTS.md`.
6. A future Vehicle Rental implementation should reference this knowledge base, but code remains authoritative for what is actually implemented at a given commit.
7. When implementation diverges from legacy screens to correct a design flaw, preserve the business meaning and document the reason.
8. Historical TACGL numeric rates/amounts are examples, never defaults.
9. Historical user/customer/payee names and credentials are not requirements and must not be copied into tests, fixtures or documentation.
10. Keep `docs/changes` append-only for actual repository changes.

---

## 33. Final domain conclusion

The Vehicle Rental business is not simply a vehicle CRUD feature and not simply a customer invoice feature. It is a coordinated domain connecting:

- physical vehicle identity and availability;
- owner/lessor supply agreements;
- customer/lessee commercial agreements;
- operational custody and Running Chart evidence;
- independent revenue and owner-cost calculations;
- customer receivables and owner payables;
- receipts, payments and allocations;
- tax and General Ledger;
- workshop/off-road availability;
- bank/cheque reconciliation;
- operational and financial reporting;
- immutable historical traceability.

The most important failure to prevent is not merely arithmetic error. It is the combination of:

```text
wrong physical vehicle
+ wrong agreement/rate version
+ wrong effective period
+ conflicting allocation
+ duplicated usage consumption
= wrong customer billing and/or wrong owner settlement
```

The correct AutoERP foundation is therefore:

> **one stable Vehicle identity + separate effective Lessee/Lessor agreements + effective allocation/custody + one authoritative Running Chart + independent customer/owner commercial consumption + owner-module financial posting + immutable/reversible history.**

That foundation preserves the strongest business knowledge from both the videos and TACGL while intentionally rejecting the legacy design mistakes demonstrated by those systems.
