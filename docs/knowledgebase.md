# AutoERP Vehicle Rental Business Knowledge Base

**Status:** Canonical Vehicle Rental business/domain knowledge  
**Knowledge refresh date:** 2026-08-29  
**Primary business source of truth and conflict tie-breaker:** TACGL legacy application/data corpus  
**Authoritative workflow evidence:** all four supplied Vehicle Rental videos  
**Authoritative engineering source:** `worktree-0.0.8`  
**Engineering HEAD audited before this documentation update:** `e8edc66fb7a82bf97176cfa2303c7add1c683952`  
**TACGL source file:** `TACGL.zip`  
**TACGL SHA-256:** `0e0733fff720072af4c3aaa787995ff128bfa79060a37739d6d2ebbe18a25313`  
**Architecture policy:** `RULES.md` and `AGENTS.md`

---

## 1. Purpose

This document is the self-contained, authoritative Vehicle Rental business knowledge base for AutoERP. It is written so an AI agent, developer, tester, analyst, reviewer, or future maintainer can reason about the Vehicle Rental domain consistently without depending on undocumented chat history, legacy implementation quirks, or guessed business rules.

It captures the complete evidence-supported model of:

- parties and physical vehicles;
- owner/lessor and customer/lessee agreements;
- vehicle supply, customer use, custody, handover, return, and replacement;
- Daily/Replacement Running Chart evidence;
- customer billing and owner/lessor settlement;
- receipts, payments, allocations, adjustments, deposits, cheques, and reconciliation;
- tax, Finance/GL integration, reports, corrections, and auditability;
- states, validations, concurrency/integrity requirements, edge cases, and unresolved policy decisions;
- boundaries with Customer, Supplier/Lessor, Vehicle, HR, Invoice, Payment, Tax, Finance, Reporting, and Vehicle Service.

This document is **not** a screen-for-screen, table-for-table, code-prefix-for-code-prefix, or GL-account-for-GL-account copy of TACGL. TACGL is authoritative for the business meaning demonstrated by its data and behavior. Historical design defects are evidence to correct, not defects to preserve.

The core engineering rule is: **understand first, verify second, change third**. Where TACGL and the videos do not uniquely establish a financially material rule, the rule remains unresolved and implementation must fail closed or wait for business confirmation rather than inventing a default.

---

## 2. Source authority and conflict-resolution hierarchy

### 2.1 TACGL — primary business source and tie-breaker

TACGL is the primary source of truth for actual Vehicle Rental economic and accounting behavior. Repeated transactions, source-to-ledger lineage, report artifacts, structured records, and consistent historical arithmetic carry the highest business weight.

TACGL does **not** make every legacy implementation mechanism correct. Examples of mechanisms that must not be copied blindly include duplicate vehicle identities, raw accounting codes as operator inputs, Rental charges embedded in unrelated legacy structures, free-text arithmetic, direct edit/delete of posted financial records, numeric user levels/password registers, and repair-after-error procedures.

### 2.2 Supplied videos — authoritative workflow evidence

The following four videos are authoritative evidence for practical operator workflow, visible Rental fields, form transitions, report inventory, Daily Running Chart behavior, separate customer/owner commercial paths, and the shared Vehicle Service boundary:

| Video | Duration | SHA-256 | Primary evidence |
|---|---:|---|---|
| `1.mp4` | ~40:50 | `ac4ca8e632081c32cd2a1d2e6facb070acf4a1f5304a4dc7a468ca7073b953cf` | Customer/owner agreements, Running Chart, invoice, owner payable, deductions, cheque/payment, reconciliation |
| `Recording 2026-06-21 132314.mp4` | ~41:58 | `11866d255dbb709055b43bb7428538a3e2f0858a8ee1d0144187bcdaf4616ffa` | Vehicle/party registers, agreements, Running Chart, invoice/PDF, receipt allocation, owner statement, Rental reports, user register |
| `2.mp4` | ~21:14 | `cd2ba1399f149003f19080327458e4bbe4619b88eed9416053c7f8d21431c36f` | Transaction/report inventory, allocations, cheque/bank reconciliation, integrated ledger/error procedures |
| `ScreenVideo_03-04-2026_18-02-52.mp4` | ~12:24 | `c9853b7923e7cb95f1014cf598416faa550bfbd56f19da56b613f160d0528ce9` | Vehicle Service/workshop flow; supporting evidence for shared vehicle availability only |

Total represented footage is approximately **1 hour 56 minutes 26 seconds**.

The Workshop-focused video is **not** a source for Rental pricing formulas. It is supporting evidence for the operational fact that maintenance, breakdown, workshop custody, and off-road conditions can affect whether a vehicle is available for Rental use.

### 2.3 `worktree-0.0.8` — engineering source of truth

The latest `worktree-0.0.8` branch is authoritative for current AutoERP architecture, module ownership, integration contracts, historical-data compatibility boundaries, and what is actually implemented now.

Business evidence does not justify putting responsibility in the wrong module. Rental may orchestrate Rental workflows, but it must not duplicate Customer, Supplier, Vehicle, HR, Invoice, Payment, Tax, Finance, Reporting, or Vehicle Service ownership.

### 2.4 Evidence classes used throughout this document

- **Explicit — TACGL:** directly represented by structured TACGL data, report artifacts, or accounting lineage.
- **Explicit — Video:** directly visible in a supplied video screen, field, report, or workflow.
- **Cross-source confirmed:** independently supported by both TACGL and video evidence.
- **Evidence-derived integrity decision:** the narrowest control necessary to preserve proven business meaning safely.
- **Legacy mechanism rejected:** real legacy behavior whose capability is valid but whose implementation must not be reproduced.
- **Observed precedent only:** a real historical example that does not establish a universal policy.
- **Unresolved:** available evidence cannot uniquely determine the rule.

### 2.5 Decision rule when evidence is incomplete

1. Prefer repeated/direct TACGL economic or accounting evidence.
2. Use video behavior to establish operator workflow and visible intent.
3. Use cross-source consistency to strengthen conclusions.
4. Derive only controls required to preserve proven business meaning and data integrity.
5. If multiple financially meaningful interpretations remain plausible, mark the rule **Unresolved**.
6. Never convert one historical amount, one narration, one customer contract, or one screen default into a universal rule without corroboration.

---

## 3. TACGL corpus verification

The supplied `TACGL.zip` is a Visual FoxPro-era operational/accounting corpus.

Archive facts:

- compressed size: **59,554,116 bytes**;
- **456** ZIP entries;
- **452** files and **4** directories;
- uncompressed size: **420,055,750 bytes**;
- **114 DBF**, **109 FRT**, **109 FRX**, **46 IDX**, **32 CDX**, **16 PDF**, and **5 XLS** files, plus application/runtime/database-container artifacts.

Important structured sources include:

- `scfveh.dbf` — vehicle master/context;
- `scfdeb.dbf` — debtor/customer master;
- `scfcre.dbf` — creditor/owner/supplier master;
- `scfchr.dbf` — charge vocabulary;
- `jobtxn.dbf` — line-level commercial/workshop/Rental-like transaction evidence;
- `scfinv.dbf` — customer invoices;
- `scftdb.dbf` — debtor transactions/allocations;
- `scftcr.dbf` — creditor transactions;
- `scftxn.dbf` — financial transactions;
- `scfglt.dbf` — General Ledger lineage;
- `scfacc.dbf` — account vocabulary;
- deleted/error/history structures — evidence of historical repair/re-entry behavior, not a target correction strategy.

The corpus proves that Rental was economically real even though the legacy implementation was not a clean modern bounded module.

---

## 4. Executive domain model

Vehicle Rental is a **dual-sided operational and financial domain**.

```text
Vehicle Owner / Lessor
    -> Lessor Agreement / supply terms
                         \
                          -> Vehicle supply/use relationship
                         /                    |
Customer / Lessee                            v
    -> Lessee Agreement              Daily Running Chart
    -> customer terms               physical usage truth
                                      /             \
                                     /               \
                         Customer calculation    Owner calculation
                         Lessee rates             Lessor rates
                              |                       |
                              v                       v
                       Customer Invoice        Owner Payable Voucher
                              |                       |
                              v                       v
                       Customer Receipt        Owner Payment
                       + allocation            + allocation
                                      \         /
                                       Tax / Finance / GL
```

### Central invariant

> **One physical usage truth; two independent commercial calculations.**

The Daily Running Chart is shared physical/operational evidence. Customer billing uses the effective Customer/Lessee Agreement. Owner settlement uses the effective Owner/Lessor Agreement.

Therefore:

- customer revenue must never be the formula source for owner payable;
- owner cost must never be the formula source for customer billing;
- customer billing may be completed before or after owner settlement;
- owner settlement may be completed before or after customer billing;
- the same source usage must not be financially consumed twice on the **same** side;
- processing one side must not consume or block the other side's eligibility.

TACGL transaction evidence reinforces the economic independence: customer rental amounts for physical vehicles differ materially from owner/source Rental Payment amounts for those vehicles and periods.

---

## 5. Canonical terminology

Use these terms consistently in code, APIs, UI, tests, reports, and future documentation.

- **Vehicle:** one physical registered vehicle. Formatting variations of registration are not separate identities.
- **Customer / Lessee:** party receiving or using a rented vehicle; primarily receivable/revenue side.
- **Lessor / Vehicle Owner / Supplier:** party supplying a vehicle to the Rental business; primarily payable/cost side.
- **Customer/Lessee Agreement:** effective customer-side commercial contract.
- **Owner/Lessor Agreement:** effective supplier/owner-side commercial contract.
- **Vehicle Allocation / Assignment:** effective-dated relationship connecting a physical vehicle to supply/use context. Backend relationship data may exist without exposing a technical allocation wizard to the operator.
- **Handover:** operational transfer into active customer use/custody.
- **Return:** end of customer use/custody.
- **Replacement:** controlled substitution of one vehicle for another while preserving lineage and period facts.
- **Daily Running Chart:** authoritative operational record of physical usage for a period/day.
- **Replacement Running Chart:** Running Chart evidence associated with replacement use where demonstrated/required.
- **Customer Calculation:** immutable calculation result from Running Chart evidence and effective customer agreement terms.
- **Owner Calculation:** immutable calculation result from Running Chart evidence and effective owner agreement terms.
- **Customer Invoice:** receivable document generated from customer-side calculation.
- **Owner Payable Voucher / Owner Settlement:** payable document for the lessor/supplier side. The video workflow uses `Payment Payable Voucher` / `Payment Payable Processing`; this is **not** a normal customer-style sales invoice.
- **Customer Receipt:** money received from the customer.
- **Owner/Supplier Payment:** money paid by the company to the owner/supplier.
- **Debit/Credit Note:** governed adjustment document; interpretation depends on whose subledger is affected.
- **Rental Payment:** TACGL accounting vocabulary for owner/source-side Rental expenditure/payment activity.

Legacy codes and account numbers are implementation-instance data unless explicitly identified as semantic business concepts.

---

## 6. End-to-end business lifecycle

The evidence-supported practical lifecycle is:

```text
Company / Finance / Tax setup
    -> Customer / Lessee setup
    -> Owner / Lessor / Supplier setup when externally supplied
    -> Driver setup where applicable
    -> Stable physical Vehicle setup
    -> Owner/Lessor Agreement where externally supplied
    -> Customer/Lessee Agreement
    -> Select/assign Vehicle
    -> Handover / driver context where applicable
    -> Daily or Replacement Running Chart
    -> Finalized physical usage evidence
         |-- Customer calculation -> Customer Invoice -> Customer Receipt -> Allocation
         `-- Owner calculation    -> Owner Payable  -> Owner Payment    -> Allocation
    -> supported debit/credit adjustments, deductions, deposits/refunds
    -> cheque/instrument realization and bank reconciliation
    -> Customer/Owner/Vehicle/Running-Chart/Finance reports
    -> explicit correction/reversal when required
```

### Company-owned vehicle exception

Where the company itself owns the vehicle and there is no external lessor payable, the owner/supplier agreement and owner-payable path are not required merely to satisfy a symmetric software model.

The practical flow becomes:

```text
Customer Agreement -> Select company vehicle -> Running Chart
-> Customer Calculation -> Customer Invoice -> Customer Receipt
```

Do not manufacture an owner payable for a company-owned asset unless the business explicitly has an internal transfer-cost policy.

---

## 7. Parties and master data

### 7.1 Customer / Lessee

Observed concepts include:

- customer code/identity;
- name, address, contact information;
- account/debtor context;
- opening/current balance and statement context;
- tax attributes;
- agreements;
- invoices;
- receipts and allocations.

Rules:

- Customer master owns reusable identity/contact/default data.
- Historical Rental pricing must come from the effective agreement/rate snapshot, not mutable customer defaults.
- Rental must reference the Customer owner module rather than duplicate customer identity tables.

### 7.2 Lessor / Vehicle Owner / Supplier

Observed concepts include:

- owner/supplier identity and contact details;
- creditor/payable context;
- vehicle relationship;
- owner agreement;
- payable/statement history;
- deductions/adjustments;
- cash/cheque/payment records and allocations.

Evidence-derived architecture decision:

- model the economic party once through the appropriate Supplier/Lessor identity and classification;
- do not build duplicated settlement engines for “vehicle owner” and “leasing company” where their economic role is the same;
- use subtype/classification only where business behavior actually differs.

### 7.3 Driver

Videos show Driver Register/driver identity and Running Chart driver context. Agreement/invoice/payable screens expose driver-related commercial fields.

Rental may own:

- Rental driver assignment/reference;
- Running Chart driver usage facts;
- Rental-specific driver commercial facts.

HR owns:

- employee identity;
- employment lifecycle;
- qualifications/licences/HR metadata.

Rental must not create a second employee master.

---

## 8. Physical Vehicle identity and relationship integrity

TACGL contains normalized registration duplicates such as punctuation/spacing variants. Some variants carry different commercial/customer contexts, showing that the old application sometimes used duplicate vehicle rows to encode relationships.

### Authoritative identity rule

> **One physical vehicle = one stable Vehicle identity.**

Registration normalization must prevent formatting-only variants from creating another physical vehicle.

Customer use, owner supply, replacement, and agreement relationships belong in effective-dated relationship/history records, not duplicate Vehicle master rows.

TACGL's legacy vehicle-type values are not sufficient ownership truth by themselves; source/ownership must come from explicit current Vehicle ownership/source relationships.

Vehicle master ownership remains in the Vehicle module. Rental stores only Rental-specific relationship/custody/use facts.

---

## 9. Customer / Lessee Agreement

### 9.1 Explicit video evidence

The Customer/Lessee Agreement workflow exposes concepts including:

- agreement date/type/status;
- customer/Lessee identity and agreement number;
- vehicle/vehicle context;
- executing/start/end dates;
- company/personal format/context;
- Monthly/Daily basis;
- maximum/included kilometre concepts;
- Non-AC / Front-AC / Dual-AC rate contexts;
- rate for included/max KM context and excess KM;
- default AC mode/context;
- With Driver / self-drive context;
- VAT calculation/tax context;
- VAT/SVAT/SSCL fields visible in the legacy application;
- legacy Rental Income, Excess KM Income, Parking/Other account mappings;
- introducer/identity/licence/security-deposit-related fields visible in agreement context.

### 9.2 Rules

- Customer Agreement is customer-side commercial authority.
- Agreement dates define the period in which its terms can apply.
- Monthly and Daily are distinct commercial bases.
- With Driver and self-drive are meaningful commercial distinctions.
- Rates may differ by AC mode/context.
- Included/max KM and excess KM are separate concepts.
- Historical billing must freeze the exact effective agreement/rate facts used.
- Later commercial changes must create a successor/effective version rather than silently rewriting already-consumed history.
- Raw GL account selection is not a normal Rental operator responsibility in modern AutoERP; semantic posting profiles belong to Finance.

### 9.3 What is not proven

The sources do **not** establish one universal:

- partial-month proration formula;
- calendar-day vs fixed-day monthly divisor;
- free-KM reset/pooling policy across periods;
- rounding convention for every component;
- tax treatment for every rate line.

These remain implementation gates.

---

## 10. Owner / Lessor Agreement

### 10.1 Explicit video evidence

The Vehicle Owner/Lessor Agreement workflow exposes concepts parallel to, but economically independent from, the customer agreement:

- agreement type/date/status;
- Lessor identity and agreement number;
- vehicle;
- executing/start/end dates;
- Monthly/Daily basis;
- maximum/included KM context;
- AC rate contexts;
- max-KM/excess rates;
- With Driver context;
- VAT/tax context;
- description/status;
- legacy owner-side account mappings.

Some legacy labels are reused incorrectly. Surrounding form title, Lessor identity, and downstream payable behavior determine meaning when a label conflicts.

### 10.2 Rules

- Lessor Agreement is separate from Customer Agreement.
- It is owner-payable commercial authority.
- Customer invoice rates/amounts must never determine owner payable rates/amounts.
- Historical owner settlement must freeze the effective owner agreement/rate facts used.
- Externally supplied vehicle use requires source/owner coverage for the relevant period.
- A company-owned vehicle does not require a fake lessor agreement merely for software symmetry.

---

## 11. TACGL charge vocabulary and what it proves

`scfchr.dbf` contains Rental-related charge classifications:

| Code | Description | Master rate in TACGL | Interpretation |
|---|---|---:|---|
| `HIRIN` | `HIRING CHARGES FOR WITH DRIVER MONTHLY BASIS CAR` | `0.0` | With-driver monthly car hiring category |
| `EXCES` | `EXCESS CHARGES FOR WITH DRIVER MONTHLY BASIS CAR` | `0.0` | Excess charge category |
| `RENT1` | `HIRING CHARGES FOR SELF DRIVE MONTHLY BASIS CAR` | `0.0` | Self-drive monthly car hiring category |
| `HIRE1` | `HIRING CHARGES FOR WITH DRIVER VAN` | `0.0` | With-driver van hiring category |
| `OT100` | `DRIVER OVER TIME` | `0.0` | Driver overtime category |

The zero master rates are important: these rows classify charge concepts, while actual amounts are contract/transaction specific.

### Rule

> Never hardcode TACGL example amounts or charge codes as universal modern pricing rules.

Codes may be useful historical evidence but modern AutoERP should model semantic rate components through named enums/configuration and agreement snapshots.

---

## 12. Transaction arithmetic evidence

`jobtxn` contains real historical Rental/hire arithmetic, including recurring monthly hiring lines and one-off/date-range/excess-distance examples.

Examples observed in the corpus include:

- recurring `HIRING CHARGES - MAR 2020` for specific vehicles with different amounts;
- `JEEP WITH DRIVER ... (14 X 8,000)` style period arithmetic;
- `A/C CAR HIRE CHARGES ... EXCESS 544KM X 50` style excess-distance arithmetic.

These prove:

- date/period-based hiring exists;
- daily-like quantity × rate arithmetic exists in at least some transactions;
- excess-distance quantity × rate arithmetic exists;
- different vehicles/contracts have different commercial values.

They do **not** prove:

- one universal daily rate;
- one universal excess-KM rate;
- one universal monthly divisor;
- one universal rounding policy.

Historical examples are precedents, not defaults.

---

## 13. Vehicle supply, allocation, custody, handover, return, and replacement

The videos often let an operator select the vehicle in agreement/Running Chart context rather than forcing a technical allocation workflow.

### 13.1 User-facing simplicity rule

Normal operator UX should remain close to:

```text
Open Agreement -> Select Vehicle -> Save
```

Do not expose every backend relationship as a separate wizard/page unless business evidence or operational necessity requires it.

### 13.2 Backend integrity requirement

A clean implementation still needs an effective-dated source/use relationship so the system can answer:

- which physical vehicle was supplied by which owner/source for this period?;
- which customer agreement used that vehicle for this period?;
- did periods overlap illegally?;
- was a replacement used?;
- what was the historical source/customer relationship at billing/settlement time?;
- was the vehicle unavailable because of another Rental, workshop custody, breakdown, or off-road state?

### 13.3 Handover and return

Exact handover/return timestamps are operational evidence, not pricing rules unless the applicable agreement explicitly uses them in a proven formula.

### 13.4 Replacement

Replacement is visible/meaningful in the Rental evidence and must preserve lineage:

- original vehicle;
- replacement vehicle;
- replacement effective time/period;
- associated customer/source context;
- Running Chart evidence for each physical vehicle where applicable.

What is **not** proven is how the replacement day/period is charged commercially. The system must not invent double-charge, no-charge, split-charge, or owner-deduction rules.

---

## 14. Daily Running Chart — central operational evidence

### 14.1 Business role

The Daily Running Chart is the strongest central workflow concept in the videos.

It records physical usage facts that are then used by:

- customer-side calculation/billing;
- owner-side calculation/settlement;
- operational reporting;
- vehicle/driver history.

### 14.2 Observed/related fields

Video evidence shows or supports concepts including:

- date/operational period;
- vehicle;
- customer/agreement context;
- owner/source context where applicable;
- driver;
- start/finish mileage/odometer;
- start/finish time;
- total/commercial/garage-related kilometre facts;
- normal/double/triple overtime;
- night-out;
- AC mode/context;
- other charges/remarks;
- original/replacement vehicle context.

Do not treat every visually present field as universally mandatory. Requiredness must follow the relevant business mode.

### 14.3 Minimal state model

The videos prove an operational record that becomes trusted for downstream processing, but they do not prove a mandatory five-step approval chain.

The simplest safe modern lifecycle is:

```text
Draft -> Finalized -> Reversed/Corrected
```

Additional Submit/Verify/Approve stages may be introduced only if the business explicitly requires separation of duties.

### 14.4 Finalization integrity

A finalized Running Chart must be immutable as historical evidence. Corrections must preserve lineage through reversal/replacement/correction records rather than editing the original physical truth in place.

### 14.5 Same-side duplicate consumption

A finalized Running Chart may feed both commercial sides independently, but it must not be consumed twice on the same side for the same commercial scope.

Correct conceptual model:

```text
Running Chart R1
  customer side -> consumed once for Customer Calculation C1
  owner side    -> consumed once for Owner Calculation O1
```

Customer consumption must not block owner consumption and vice versa.

---

## 15. Customer billing calculation

### 15.1 Inputs

A customer calculation may use only facts that are valid/effective for the billed usage period, including:

- finalized Running Chart physical facts;
- effective Customer/Lessee Agreement and frozen rate version;
- rental basis (Monthly/Daily as applicable);
- included/max KM context;
- excess KM quantity/rate where applicable;
- AC mode/rate context where applicable;
- driver salary/recovery where applicable;
- normal/double/triple OT where applicable;
- night-out where applicable;
- supported other recoveries/adjustments;
- tax facts as defined by the Tax owner module and proven policy.

### 15.2 Conceptual decomposition

Evidence supports a conceptual calculation of the form:

```text
Base rental
+ applicable excess-distance charge
+ applicable driver recovery
+ applicable overtime
+ applicable night-out
+ supported other recoveries/charges
- supported discounts/credits
+ applicable tax
= Customer amount
```

This is a component model, **not** permission to invent any unresolved component formula.

### 15.3 Output

The calculation result must be an immutable snapshot containing:

- source Running Chart(s);
- effective agreement/version identity;
- every component quantity/rate/amount;
- tax inputs/result where applicable;
- calculation timestamp/version;
- source-consumption identity.

The snapshot is then handed to the Invoice owner module. Rental must not implement a second general invoice engine.

---

## 16. Owner / Lessor settlement calculation

### 16.1 Inputs

Owner settlement uses:

- the same relevant finalized physical Running Chart evidence;
- effective Owner/Lessor Agreement and frozen rate version;
- owner-side base rental terms;
- owner-side excess-distance terms;
- supported driver salary/OT/night-out reimbursements;
- supported owner expenses/credits;
- supported fuel/repair/damage deductions;
- withholding/tax only where proven/configured through owning modules.

### 16.2 Conceptual decomposition

```text
Base owner rental payable
+ applicable excess-distance payable
+ applicable driver reimbursement
+ applicable overtime/night-out reimbursement
+ supported owner credits/expenses
- supported fuel/repair/damage deductions
- supported advances/debit adjustments
- applicable withholding
= Net owner payable
```

Again, this is an evidence-supported component structure, not a universal formula for unresolved policies.

### 16.3 Output terminology

The normal Rental owner-side document is:

> **Owner Payable Voucher / Owner Settlement**

Do not label the normal workflow “Owner Invoice” unless there is a separate, explicitly supported supplier-tax-invoice process.

The payable handoff must use the existing Invoice/AP/Finance ownership contracts rather than duplicating financial-document infrastructure inside Rental.

---

## 17. Customer receipts, owner payments, and allocations

### 17.1 Customer side

TACGL/video evidence supports:

```text
Customer Invoice -> Customer Receipt -> Receipt Allocation -> Customer balance/statement
```

Receipts may require instrument/cash/bank facts according to the Payment owner module.

### 17.2 Owner side

Evidence supports:

```text
Owner Payable Voucher -> Owner Payment -> Payment Allocation -> Owner balance/statement
```

### 17.3 Bidirectional adjustment capability

Legacy evidence also contains debit/credit note and allocation concepts on both customer/owner financial sides. A modern implementation must preserve economic meaning but use governed adjustment/reversal documents rather than arbitrary mutation of posted history.

### 17.4 Allocation integrity

Allocations must be:

- tenant/organization scoped;
- within source/document balances;
- concurrency safe;
- reversible through explicit governed operations;
- historically auditable.

Rental should invoke owner-module Payment/Invoice allocation APIs/services rather than maintain a parallel generic allocation ledger.

---

## 18. Deposits and advances

Security-deposit concepts are visible in Rental agreement/workflow evidence.

Proven capability:

- a Rental agreement may carry a deposit/security concept;
- receipts/advances/refunds/adjustments exist in the financial ecosystem.

Unresolved policy:

- exact deposit requirement formula;
- whether deposit is always required;
- automatic application priority against invoices/damage/other charges;
- refund timing/approval;
- forfeiture conditions;
- partial application ordering.

Therefore a modern deposit ledger may be implemented only around explicit, append-only movements and confirmed policies. Do not infer automatic priority from accounting convenience.

---

## 19. Fuel, repair, damage, and other deductions

Videos show owner-side fuel/repair deduction behavior and debit-note/adjustment concepts.

Rules:

- deductions must identify their business source/reason;
- deductions must not silently rewrite owner agreement rates;
- posted settlement history remains immutable;
- if a deduction originates in another owner module (for example a repair expense), Rental stores/reference the approved business fact rather than duplicating that module's ledger;
- the same deduction cannot be applied twice.

Unresolved:

- accident responsibility matrix;
- insurance-excess allocation;
- downtime deduction formula;
- automatic repair-cost responsibility;
- exact garage-mileage treatment.

---

## 20. Tax, Finance, General Ledger, cheques, and reconciliation

### 20.1 TACGL evidence

TACGL contains customer/debtor transactions, creditor transactions, financial transactions, and GL lineage connected to vehicles/Rental activity.

`scfacc.dbf` contains account:

- `7048-000` — `RENTAL PAYMENT`

`scfglt` contains multiple `R E N T A L   P A Y M E N T` postings with different amounts.

This proves a Rental-payment accounting concept. It does **not** make `7048-000` a universal AutoERP account number.

### 20.2 Modern rule

Finance owns semantic posting profiles and account mapping. Rental supplies semantic source facts such as:

- customer rental revenue component;
- owner rental payable/cost component;
- excess-distance component;
- approved deduction/adjustment source;
- source document identity and dimensions.

Operators should not type raw GL codes as part of normal Rental workflow.

### 20.3 Tax

Videos expose VAT/SVAT/SSCL-related Rental fields/context, but exact applicability by component and exact rounding policy are not sufficiently established as universal rules.

Tax owner module must remain source of truth. Rental must not hardcode tax percentages or account IDs.

### 20.4 Cheques and bank reconciliation

Videos demonstrate cheque/payment and bank-reconciliation workflows. Rental financial documents must hand off to the existing Payment/Finance instrument lifecycle; Rental must not build a second cheque or bank-reconciliation engine.

---

## 21. Reporting requirements

Evidence supports reports/statements across operational and financial views.

Minimum required reporting dimensions include:

### Operational

- vehicle;
- customer;
- owner/lessor/source;
- agreement;
- date/period;
- driver;
- Running Chart;
- original/replacement vehicle;
- usage/KM/time facts.

### Customer financial

- Customer Invoice;
- invoice balance/outstanding;
- Customer Receipt;
- receipt allocation;
- debit/credit adjustments;
- customer statement/ledger;
- vehicle/customer period analysis.

### Owner financial

- Owner Payable Voucher;
- payable balance;
- Owner Payment;
- payment allocation;
- deductions/adjustments;
- owner/vehicle statement.

### Finance/reconciliation

- Rental source-to-Invoice/Payable lineage;
- source-to-GL lineage;
- tax lineage;
- cheque/instrument status;
- bank reconciliation status;
- unmatched/inconsistent source diagnostics.

Reports must be generated from authoritative domain/financial records, not from duplicated reporting-only business logic.

---

## 22. State and correction model

The legacy application exposes edit/delete-style controls and repair procedures. That is not evidence that posted business history should be mutable.

### 22.1 Agreements

Recommended minimal lifecycle, subject to existing AutoERP conventions:

```text
Draft -> Active/Executed -> Closed
```

Historical consumed versions remain immutable.

### 22.2 Vehicle assignments/custody

Recommended integrity lifecycle:

```text
Planned -> Active/HandedOver -> Ended/Returned
       \-> Cancelled
```

Replacement preserves lineage instead of rewriting the old assignment.

These statuses are integrity-oriented implementation decisions, not claims that TACGL used the exact same enum names.

### 22.3 Running Charts

Simplest evidence-safe lifecycle:

```text
Draft -> Finalized -> Reversed/Corrected
```

Do not add Submit/Verify/Approve stages unless explicitly required by business governance.

### 22.4 Calculations

A finalized calculation is an immutable snapshot. Cancellation/reversal releases or reverses its source-consumption relationship according to governed rules; it does not mutate the old amount silently.

### 22.5 Financial documents

Posted Invoice, Payable, Receipt, Payment, tax, and Finance journal history must be immutable. Corrections happen through owner-module reversal, debit/credit note, or replacement-document flows.

---

## 23. Validation and integrity rules

The following are required to preserve proven business meaning safely.

### 23.1 Tenant and organization isolation

Every Rental aggregate/reference must respect AutoERP tenant/organization boundaries. Cross-tenant or cross-organization references are invalid unless the owning module explicitly supports such a relationship.

### 23.2 Physical vehicle identity

- one physical vehicle identity;
- normalized registration uniqueness according to Vehicle policy;
- no relationship encoded by duplicating the vehicle.

### 23.3 Agreement validity

A commercial calculation may use only an agreement/version effective for the relevant usage period and correct commercial side.

### 23.4 Vehicle source/use coverage

Externally supplied customer use must be supported by valid owner/source coverage for the relevant period.

### 23.5 Overlap prevention

The system must prevent physically impossible conflicting active use/custody of the same vehicle. Vehicle Service/off-road/workshop availability must be respected through the Vehicle/shared availability contract rather than duplicated Rental logic.

### 23.6 Driver conflict

Where a driver is a constrained physical resource, overlapping assignments/use must be prevented according to the actual operational timestamps.

### 23.7 Running Chart continuity

- end odometer must not be lower than start odometer without an explicit correction model;
- overlapping physical usage for the same vehicle is invalid;
- finalized evidence is immutable;
- replacement/original lineage must be preserved;
- exact timestamps belong to operational evidence even where planning coverage is date-based.

### 23.8 Duplicate consumption

A source Running Chart/usage scope cannot be consumed twice by the same customer calculation side or same owner calculation side.

### 23.9 Calculation snapshot integrity

Posted/generated financial documents must be traceable back to:

- source usage;
- effective agreement/version;
- component quantities/rates/amounts;
- tax snapshot/reference;
- calculation identity/version.

### 23.10 Concurrency

Financially meaningful writes must use existing AutoERP transaction, row-version/idempotency, and deterministic locking patterns where applicable. Stale user input must fail explicitly rather than overwrite concurrent changes.

---

## 24. User-facing UI/UX rules

The videos demonstrate a practical operator-oriented application. The new module must preserve that simplicity while moving integrity controls behind the interface.

### 24.1 Canonical simple flow

```text
Owner Agreement (only when externally supplied)
-> Customer Agreement
-> Select Vehicle
-> Daily Running Chart
-> Customer Invoice / Owner Payable
-> Customer Receipt / Owner Payment
-> Reports
```

Customer and owner financial branches remain independent even if the navigation presents them in one workflow.

### 24.2 Navigation principle

Prefer a small set of operator concepts, for example:

```text
Vehicle Rental
  - Overview
  - Customer Agreements
  - Owner Agreements
  - Running Charts
  - Customer Billing
  - Owner Settlements
  - Deposits / Adjustments where required
  - Reports
```

Do not expose every backend table as a page.

### 24.3 Vehicle selection

Normal UX should support:

```text
Open Agreement -> Select Vehicle -> Save
```

A backend effective-dated assignment/history record can be created without forcing the operator through a technical allocation wizard.

### 24.4 Running Chart entry

Prefer fast table/form entry centered on operational facts. Do not add an approval maze without business evidence.

### 24.5 Human-readable selectors

Use searchable customer/owner/vehicle/driver labels and business identifiers. Raw database IDs and raw GL codes are not normal primary input.

### 24.6 Financial terminology

Use:

- **Customer Invoice**;
- **Customer Receipt**;
- **Owner Payable Voucher / Owner Settlement**;
- **Owner/Supplier Payment**.

Avoid ambiguous “Owner Invoice” and “Customer Payment” labels in the normal flow.

---

## 25. Legacy mechanisms to reject explicitly

The following must not be carried forward simply for compatibility:

1. **Duplicate physical Vehicle rows** to represent different customer/owner contexts.
2. **Raw code-heavy UI** as the primary interaction model.
3. **Numeric user levels / Password Register** as authorization design.
4. **Posted transaction edit/delete** as a correction method.
5. **Customer amount copied into owner payable** or vice versa.
6. **Same usage billed/settled repeatedly** because source consumption is not tracked.
7. **Duplicated Vehicle Owner vs Leasing Company engines** where economic behavior is the same.
8. **Repair-after-error procedures** as the normal integrity strategy.
9. **Hardcoded legacy GL account numbers, tax rates, or example Rental prices**.
10. **Business relationships encoded in display-format variants** such as duplicate registration strings.
11. **Rental-specific duplicate Invoice/Payment/Tax/Finance ledgers** instead of owner-module integrations.
12. **Unnecessary workflow stages** that make the user flow more complex than the evidence requires.

---

## 26. Ambiguous, incomplete, or unproven rule register

These items are intentionally **not solved by guessing**.

| ID | Rule/question | Evidence status | Required implementation behavior now |
|---|---|---|---|
| VR-U01 | Partial-month monthly-rental proration formula | Unresolved | Do not invent divisor/calendar rule; require explicit policy/configuration before production calculation |
| VR-U02 | Exact Monthly basis day-count convention | Unresolved | No hardcoded 30/31/actual-days assumption |
| VR-U03 | Included/free-KM pooling/reset across days/months/replacements | Unresolved | Do not pool/reset automatically without policy |
| VR-U04 | Replacement-day charging and split between original/replacement vehicle | Unresolved | Preserve lineage; block automatic commercial treatment until configured |
| VR-U05 | Downtime/off-road deduction formula | Unresolved | Availability may block use; financial deduction needs explicit policy |
| VR-U06 | Garage mileage billability/payability | Unresolved | Record evidence separately; do not assume customer/owner treatment |
| VR-U07 | Accident/insurance-excess responsibility | Unresolved | Do not auto-charge customer/owner |
| VR-U08 | Security-deposit requirement formula | Unresolved | Support explicit amount/policy only |
| VR-U09 | Deposit application/forfeiture/refund priority | Unresolved | Append-only movements; no automatic priority without confirmed rule |
| VR-U10 | Tax applicability by each Rental component | Partially observed | Tax owner module + explicit configuration required |
| VR-U11 | Tax rounding convention | Unresolved | Use Tax owner module policy; Rental must not define its own |
| VR-U12 | Withholding applicability on owner settlement | Unresolved | Configure through Tax/Finance only when business confirms |
| VR-U13 | Exact AC-rate selection hierarchy/default behavior | Partially observed | Record explicit selected/contextual AC mode; do not infer hidden fallback |
| VR-U14 | Exact normal/double/triple OT thresholds | Partially observed | Rates/components visible; threshold policy requires confirmation unless explicitly stored in agreement |
| VR-U15 | Night-out qualification rule | Partially observed | Do not infer time threshold; use explicit evidence/policy |
| VR-U16 | Driver salary/recovery proration | Unresolved | Agreement-specific/configured only |
| VR-U17 | Mandatory multi-stage Running Chart approval | Not proven | Keep simplest safe Draft->Finalized unless business confirms segregation |
| VR-U18 | Insurance document mandatory as Rental assignment blocker | Not proven | Vehicle may track document; Rental must not block solely on assumed policy |
| VR-U19 | Revenue-licence document mandatory as Rental assignment blocker | Not proven | Same as above |
| VR-U20 | Owner vs leasing-company commercial-rule differences | Not sufficiently proven | Shared Lessor/Supplier engine with subtype; branch only on confirmed difference |
| VR-U21 | Internal transfer cost for company-owned vehicles | Unresolved | No artificial owner payable by default |
| VR-U22 | Miscellaneous/parking/other charge universal behavior | Partially observed | Model explicit approved components; no inferred default |

An AI agent must treat this table as a **hard boundary**. It may propose questions/configuration, but it must not silently choose a business value.

---

## 27. Edge-case decision matrix

### Same vehicle requested by two customers for overlapping physical use

**Decision:** invalid unless the evidence represents a controlled replacement/transition with non-overlapping actual custody. Prevent conflicting active use.

### Customer Agreement exists but no valid externally supplied owner/source coverage

**Decision:** customer use cannot proceed for that external vehicle. Company-owned vehicle path is different and does not require external source coverage.

### Customer invoice is created before owner settlement

**Decision:** valid. Owner side remains independently eligible.

### Owner settlement is created before customer invoice

**Decision:** valid. Customer side remains independently eligible.

### Same Running Chart is selected again for customer billing

**Decision:** block same-side duplicate consumption unless the previous calculation was explicitly cancelled/reversed and its source eligibility was correctly restored.

### Same Running Chart is selected for owner calculation after customer billing

**Decision:** valid; opposite-side consumption is independent.

### Rate changes after usage occurred

**Decision:** historical calculation uses the rate/agreement version effective for the usage, not the current mutable agreement value.

### Vehicle registration formatting changes

**Decision:** do not create another physical Vehicle. Preserve identity and audit the registration change through Vehicle ownership.

### Replacement vehicle appears mid-period

**Decision:** preserve original/replacement lineage and separate physical evidence. Commercial charging remains policy-gated where source evidence is insufficient.

### Workshop marks vehicle unavailable/off-road

**Decision:** shared Vehicle availability should prevent new conflicting Rental physical use. Rental must not duplicate Workshop status logic.

### Posted invoice/payment contains an error

**Decision:** do not edit/delete the posted record. Use the owning module's reversal/credit/debit/replacement workflow.

### TACGL contains a one-off rate

**Decision:** treat it as historical precedent only. Do not make it a global default.

---

## 28. Module ownership map

### Vehicle Rental owns

- Rental agreement domain and effective Rental commercial snapshots;
- Rental-specific vehicle source/use relationship/custody orchestration;
- Rental Running Chart physical usage evidence;
- customer and owner Rental calculation snapshots;
- same-side source-consumption protection;
- Rental-specific adjustment/deposit intent/facts where not owned elsewhere;
- Rental workflow/report definitions that aggregate owner-module facts.

### Customer owns

- customer identity/contact/master lifecycle.

### Supplier/Lessor owner module owns

- reusable supplier/lessor identity/contact/master lifecycle.

### Vehicle owns

- physical Vehicle identity;
- registration and ownership history;
- general vehicle documents/status;
- shared availability surface where appropriate.

### HR owns

- employee/driver master and HR lifecycle.

### Vehicle Service owns

- workshop job/service custody, maintenance/breakdown facts, and its own operational status.

### Invoice/AP owns

- governed customer/supplier financial documents and settlement balances according to existing AutoERP architecture.

### Payment owns

- receipts/payments, instruments, allocations, refunds/reversals according to existing contracts.

### Tax owns

- tax calculation/snapshots/configuration.

### Finance owns

- posting profiles, journals, account mapping, periods, ledger integrity, bank/reconciliation semantics.

### Reporting owns

- cross-module reporting infrastructure; Rental owns Rental report semantics/query definitions, not a duplicate reporting engine.

---

## 29. Current AutoERP implementation reconciliation

### 29.1 Audited engineering baseline

The authoritative implementation branch audited for this refresh is:

```text
worktree-0.0.8
HEAD before documentation change: e8edc66fb7a82bf97176cfa2303c7add1c683952
```

### 29.2 Active Runtime status

At that baseline there is **no active `app/Modules/VehicleRental` runtime module** in the authoritative branch. The previous Rental runtime had been intentionally removed in earlier work, while historical financial vocabulary/data compatibility remains owned by the relevant financial modules where required.

That absence is significant: a fresh rebuild must not restore, cherry-pick, revive, or depend on the removed implementation.

### 29.3 Why full runtime code is not generated from this document alone

The combined TACGL + video audit now proves much more workflow/domain structure than a TACGL-only audit:

- separate Customer and Owner/Lessor agreements;
- simple agreement-context vehicle selection;
- Running Chart as shared operational evidence;
- Monthly/Daily basis;
- AC contexts;
- with-driver/self-drive distinctions;
- excess distance;
- driver/OT/night-out components;
- separate customer invoice and owner payable paths;
- receipts/payments/allocations;
- replacement concept;
- cheque/bank reconciliation/reporting;
- Vehicle Service availability boundary.

However, several financially material formulas remain unresolved (Section 26). Implementing those formulas now would violate the explicit no-guessing rule.

Therefore the correct engineering posture is:

1. build only source-backed foundations and workflows;
2. keep unresolved formulas/configurations behind explicit gates;
3. do not claim production completeness until the required policies are confirmed and tested;
4. never resurrect the removed module simply because it previously contained code.

---

## 30. Fresh-module architecture requirements

When implementation proceeds, use a clean module boundary and existing owner-module contracts.

### 30.1 Aggregate candidates

A maintainable design will likely need concepts equivalent to:

- RentalAgreement + effective versions/rate components;
- RentalVehicleAssignment/Allocation + replacement lineage;
- RentalRunningChart;
- RentalCalculationSnapshot;
- RentalSourceConsumption;
- RentalDeposit/Adjustment movement facts where confirmed.

These names are architecture candidates, not mandates to create a UI page per table.

### 30.2 Historical snapshots

Every financial output must retain enough immutable source data to reproduce/explain:

- which physical usage was used;
- which agreement/rate version was used;
- which quantities/rates/amounts were applied;
- which tax snapshot/policy was applied;
- which source was consumed;
- which downstream Invoice/Payable/Payment/GL document resulted.

### 30.3 APIs

APIs must:

- use business-readable resources;
- validate tenant/organization ownership;
- enforce expected-version/concurrency rules on mutable aggregates;
- make state transitions explicit;
- never accept raw financial account selection where semantic owner-module APIs exist;
- expose immutable history/reversal lineage.

### 30.4 Permissions

Permissions should be semantic and task-oriented, for example separate view/manage rights for agreements, Running Charts, calculations/settlements, and reports. Do not reproduce numeric legacy user levels.

---

## 31. Testing requirements derived from the business model

A production-ready fresh implementation requires tests at multiple layers.

### 31.1 Domain/unit

Cover:

- effective agreement/version selection;
- Customer vs Owner rate independence;
- Monthly/Daily component selection once rules are confirmed;
- included/excess KM arithmetic once confirmed;
- AC mode selection once confirmed;
- OT/night-out rules once confirmed;
- immutable snapshots;
- same-side duplicate consumption rejection;
- opposite-side independent consumption;
- replacement lineage;
- correction/reversal state behavior.

### 31.2 Database/integration

Cover:

- tenant/organization FK integrity;
- normalized Vehicle identity references;
- overlapping physical-use prevention;
- deterministic concurrency/locking behavior on MySQL/MariaDB;
- owner-source coverage;
- Invoice/Payable handoff;
- Payment allocation/reversal handoff;
- Tax/Finance posting integration;
- source-to-GL traceability.

### 31.3 API

Cover positive/negative validation, permissions, stale expected versions, invalid cross-tenant references, invalid states, duplicate requests/idempotency, and human-readable errors.

### 31.4 Frontend

Cover the simple operator workflow:

```text
Agreement -> Select Vehicle -> Running Chart -> Calculation -> Financial handoff
```

Also cover permission visibility, stale-data conflicts, invalid vehicle availability, replacement, and readable financial breakdowns.

### 31.5 End-to-end/UAT

At minimum:

1. owner-supplied vehicle full flow;
2. company-owned vehicle full customer flow;
3. with-driver flow;
4. self-drive flow;
5. Monthly basis;
6. Daily basis once formula confirmed;
7. excess-KM case;
8. OT/night-out case once policy confirmed;
9. replacement case once charging policy confirmed;
10. customer billing before owner settlement;
11. owner settlement before customer billing;
12. Customer Receipt and allocation;
13. Owner Payment and allocation;
14. reversal/correction;
15. vehicle/workshop availability conflict;
16. reporting/source-to-GL reconciliation.

---

## 32. AI-agent decision protocol

Before changing Vehicle Rental code or data, an AI agent must follow this sequence.

### Step 1 — Identify the business side

Is the request about:

- physical vehicle usage;
- Customer/Lessee commercial terms;
- Owner/Lessor commercial terms;
- customer receivable;
- owner payable;
- receipt/payment/allocation;
- tax/GL/reporting;
- shared vehicle availability?

Do not mix customer and owner commercial sides.

### Step 2 — Find evidence class

For every material rule, classify it as:

- Explicit — TACGL;
- Explicit — Video;
- Cross-source confirmed;
- Integrity-derived;
- Unresolved.

### Step 3 — Check the unresolved register

If the requested behavior touches Section 26 and no new authoritative evidence has been supplied, **do not invent the rule**.

### Step 4 — Identify the owning module

Do not solve an Invoice, Payment, Tax, Finance, Customer, Vehicle, HR, or Vehicle Service defect by putting workaround logic in Rental.

### Step 5 — Preserve historical truth

Never rewrite a posted/finalized historical fact to make a correction appear clean. Use versioning/reversal/replacement lineage.

### Step 6 — Prefer simple UX

Do not expose technical backend complexity unless the user/business actually needs it. Preserve the video-style practical flow.

### Step 7 — Verify before completion

Run targeted and full regression gates appropriate to the changed owner modules and both default/MySQL database profiles where the project supports them. Do not claim green checks that were not run.

---

## 33. Implementation readiness gates

The fresh Vehicle Rental module may be considered production-ready only when all of the following are true:

- [ ] all P0 source-backed domain workflows are implemented;
- [ ] all financially material unresolved rules used by production are explicitly confirmed/configured;
- [ ] no removed legacy Rental runtime is restored as a dependency;
- [ ] Customer and Owner calculations are independently source/rate driven;
- [ ] same-side Running Chart/source duplicate consumption is impossible;
- [ ] Vehicle identity/overlap/source coverage is integrity protected;
- [ ] finalized physical and financial history is immutable with governed corrections;
- [ ] Invoice/AP, Payment, Tax, Finance, Reporting, Vehicle, HR, Customer/Supplier, and Vehicle Service ownership boundaries are respected;
- [ ] semantic posting profiles replace raw legacy GL-code dependence;
- [ ] SQLite/default and MySQL/MariaDB suites pass where applicable;
- [ ] browser E2E/UAT demonstrates the practical source-backed workflow;
- [ ] source-to-financial-document-to-GL reconciliation is traceable;
- [ ] unresolved business-policy items are not silently implemented as guesses.

---

## 34. Canonical summary for future agents

If only one section is retained in working memory, retain this:

1. **TACGL is the primary business/accounting truth and conflict tie-breaker; the four supplied videos are authoritative workflow evidence.**
2. **Vehicle Rental has two independent commercial sides: Customer/Lessee revenue and Owner/Lessor cost/payable.**
3. **The Daily Running Chart is shared physical usage evidence.**
4. **Customer billing uses Customer Agreement terms; Owner settlement uses Owner Agreement terms. Never derive one from the other.**
5. **The same usage may feed both sides, but cannot be consumed twice on the same side.**
6. **Use one stable physical Vehicle identity; relationships are effective-dated, not encoded through duplicate vehicles.**
7. **Normal owner-side document is Owner Payable Voucher / Owner Settlement; customer money is a Customer Receipt.**
8. **Keep the UI simple: Agreement -> Select Vehicle -> Running Chart -> financial outputs. Put integrity controls behind the workflow.**
9. **Do not copy legacy security, mutation, raw-code, duplicate-workflow, or repair-after-error mechanisms.**
10. **Do not invent partial-month, free-KM pooling, replacement charging, downtime, garage-mileage, deposit-priority, tax, withholding, or other unresolved policies.**
11. **The authoritative branch currently has no active Rental runtime; rebuild fresh and integrate through existing owner modules.**
12. **Correctness and auditability outrank compatibility with removed legacy code.**

This knowledge base is the business/domain authority for future Vehicle Rental work until new authoritative TACGL/video/business evidence explicitly supersedes a rule recorded here.
