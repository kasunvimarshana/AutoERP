# AutoERP Vehicle Rental Business Knowledge Base

**Status:** Canonical Vehicle Rental business/domain knowledge  
**Knowledge refresh date:** 2026-08-29  
**Primary business source of truth:** TACGL legacy application/data corpus  
**Authoritative workflow evidence:** all four supplied Vehicle Rental videos  
**Authoritative engineering source:** `worktree-0.0.8`  
**Engineering HEAD reviewed:** `f9e8cd33a9e296ab4b831003339759e0cba95df8`  
**Latest TACGL re-supply audited:** `TACGL(20260829-042529).zip`  
**TACGL SHA-256:** `0e0733fff720072af4c3aaa787995ff128bfa79060a37739d6d2ebbe18a25313`  
**Architecture policy:** `RULES.md` and `AGENTS.md`

---

## 1. Purpose

This document is the self-contained, authoritative Vehicle Rental business knowledge base for AutoERP. It exists so that an AI agent, developer, tester, analyst, or reviewer can understand the Vehicle Rental domain and make consistent decisions without relying on undocumented assumptions or legacy implementation quirks.

It captures the complete evidence-supported business model: parties and vehicles, agreements, vehicle supply/use, allocation/custody, Daily and Replacement Running Charts, customer billing, owner/lessor settlement, receipts/payments/allocations, deposits/adjustments, tax/Finance/GL, bank reconciliation, reporting, Workshop interaction, states, validations, concurrency, corrections, edge cases and explicit/implicit/ambiguous/unproven rules.

This is **not** a screen-for-screen, table-for-table, code-prefix-for-code-prefix or GL-account-for-GL-account copy of TACGL. TACGL is authoritative for the business meaning demonstrated by its data and behavior; its historical design defects are evidence to correct, not defects to preserve.

Project rules remain binding: understand first, verify second, change third; do not guess unconfirmed policy; fix root causes in the owning module; keep one source of truth; protect historical facts; make writes atomic/version checked/conflict aware; use enums/constants/configuration rather than hardcoded magic values; and prefer the simplest clean design that preserves data integrity.

---

## 2. Source authority and conflict-resolution hierarchy

### 2.1 TACGL — primary business source and tie-breaker

TACGL is the primary source of truth for actual Vehicle Rental business/accounting behavior. When a rule can be established from repeated TACGL transactions, source-to-ledger lineage, report artifacts, structured records or consistent historical arithmetic, that evidence has the highest business weight.

TACGL does **not** make every legacy implementation choice correct. Duplicate vehicle identities, Rental charges embedded in Workshop structures, free-text arithmetic and Rental revenue posted to Workshop sales accounts are legacy mechanisms to reject while preserving the underlying economic event.

### 2.2 Vehicle Rental videos — authoritative workflow evidence

The four videos are authoritative evidence for practical operator workflow, visible business fields, screen transitions, report inventory, Running Chart behavior, customer/owner parallel processing and Workshop interaction.

Where a video label is obviously reused or defective, TACGL transaction behavior and surrounding screen context determine the business meaning. Example: the Vehicle Owner Agreement screen contains reused `LESSEE` labels, but the form title, Lessor ID, owner-payable accounts and downstream payable flow establish that it is the Lessor side.

### 2.3 `worktree-0.0.8` — engineering source of truth

The latest `worktree-0.0.8` branch is authoritative for current AutoERP architecture, existing module ownership, integration contracts and what is actually implemented now. Business evidence does not justify moving responsibility into the wrong module.

### 2.4 Decision rule when evidence is incomplete

1. Prefer direct repeated TACGL business/accounting evidence.
2. Use video behavior to explain operator workflow and visible intent.
3. Use cross-source consistency to strengthen conclusions.
4. Derive only integrity controls necessary to preserve proven business meaning safely.
5. If multiple interpretations remain plausible, record the rule as **Unresolved** rather than choosing a convenient default.
6. Never promote one historical transaction into a universal rule without supporting recurrence or explicit policy evidence.

---

## 3. Evidence verification and audit scope

### 3.1 TACGL archive

Latest audited re-supply: `TACGL(20260829-042529).zip`.

SHA-256:

`0e0733fff720072af4c3aaa787995ff128bfa79060a37739d6d2ebbe18a25313`

Archive facts:

- compressed size **59,554,116 bytes**;
- **456** ZIP entries;
- **452** non-directory files and **4** directories;
- uncompressed size **420,055,750 bytes**;
- **114** DBF, **109** FRT, **109** FRX, **46** IDX, **32** CDX, **16** PDF and **5** XLS files;
- Visual FoxPro DBC/DCT/DCX, runtime/application and backup artifacts.

This hash is byte-identical to the previously audited canonical TACGL archive. Re-supplied files with the same hash are the **same corpus**, not independent corroborating datasets.

Key active-record counts re-audited:

| Artifact | Active rows | Business relevance |
|---|---:|---|
| `scfveh.dbf` | 1,076 | Vehicle master/context |
| `scfjob.dbf` | 6,653 | RMS/job source context |
| `jobtxn.dbf` | 23,645 | Material/Outside Work/Labour and Rental-like charge lines |
| `scfinv.dbf` | 6,630 | Customer invoices |
| `scfglt.dbf` | 78,760 | General Ledger lineage |
| `scftdb.dbf` | 13,775 | Debtor transactions/allocations |
| `scftcr.dbf` | 5,083 | Creditor transactions |
| `scftxn.dbf` | 10,377 | Financial transactions |
| `scfdeb.dbf` | 379 | Debtor master |
| `scfcre.dbf` | 29 | Creditor master |
| `scfacc.dbf` | 78 | Account vocabulary |
| `scfchr.dbf` | 58 | Charge vocabulary |

Deleted/repair artifacts including `del_jobtxn`, `del_scfglt`, `del_scftdb`, `del_scftcr` and `del_scftxn` demonstrate historical correction/re-entry behavior; they are not a target deletion strategy.

### 3.2 Video verification

The four source videos were re-verified by hash and end-to-end timeline visual review, with full-resolution inspection of business-significant screens:

| Video | Duration | SHA-256 | Primary evidence |
|---|---:|---|---|
| `1.mp4` | 40:50 | `ac4ca8e632081c32cd2a1d2e6facb070acf4a1f5304a4dc7a468ca7073b953cf` | Lessee/Lessor agreements, Running Chart, customer invoice, owner payable, deductions, cheque/payment, reconciliation |
| `Recording 2026-06-21 132314.mp4` | 41:58 | `11866d255dbb709055b43bb7428538a3e2f0858a8ee1d0144187bcdaf4616ffa` | Registers, agreements, invoice/PDF, Running Chart, receipt allocation, owner statement, Rental ledger/reports |
| `2.mp4` | 21:14 | `cd2ba1399f149003f19080327458e4bbe4619b88eed9416053c7f8d21431c36f` | Broad transaction/report inventory, allocations and reconciliation/error procedures |
| `ScreenVideo_03-04-2026_18-02-52.mp4` | 12:24 | `c9853b7923e7cb95f1014cf598416faa550bfbd56f19da56b613f160d0528ce9` | Vehicle Service/workshop flow and shared vehicle availability |

Total represented footage is approximately **1 hour 56 minutes 26 seconds**. The audit method is an end-to-end timeline deep visual audit with detailed key-screen review, not a claim that every spoken word was manually transcribed.

---

## 4. Evidence classification

- **Explicit — TACGL:** directly represented by repeated data, report artifacts or accounting lineage.
- **Explicit — Video:** directly visible in a form, menu, report or workflow.
- **Cross-source confirmed:** independently supported by TACGL and video evidence.
- **Evidence-derived decision:** narrowest safe interpretation needed to reconcile source evidence.
- **Legacy mechanism rejected:** real legacy behavior whose business capability is valid but implementation is structurally wrong.
- **Observed precedent only:** real historical example that does not establish a universal rule.
- **Unresolved:** evidence cannot uniquely determine the rule; implementation must not guess.

---

## 5. Executive domain model

Vehicle Rental is a dual-sided operational and financial domain:

```text
Vehicle Owner / Lessor
    -> Lessor Agreement / supply terms
                         \
                          -> Effective vehicle supply/use relationship
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
                       Receipt/Allocation      Payment/Allocation
                                      \         /
                                       Tax / Finance / GL
```

### Central invariant

> **One physical usage truth; two independent commercial calculations.**

The Daily Running Chart is the shared operational evidence. Customer billing uses the effective Lessee Agreement. Owner/lessor settlement uses the effective Lessor Agreement. Customer revenue is never the formula source for owner payable, and owner cost is never the formula source for customer billing.

TACGL reinforces this: the same physical vehicles have customer monthly amounts such as 307,500 / 250,000 / 185,000 / 225,000 while owner/source payments for those vehicles are different amounts such as 180,000 / 162,000 / 100,000 / 180,000 and vary by period.

---

## 6. Canonical terminology

- **Vehicle:** one physical registered vehicle. Registration formatting is not identity.
- **Lessee / Customer:** party receiving/using the rented vehicle; primarily receivable/revenue side.
- **Lessor / Vehicle Owner / Supplier:** party supplying a vehicle; primarily payable/cost side.
- **Lessee Agreement:** effective customer-side commercial contract.
- **Lessor Agreement:** effective owner/supplier-side commercial contract.
- **Allocation / Custody:** effective-dated relationship tying physical vehicle to supply/use context.
- **Daily Running Chart:** authoritative operational record of physical vehicle usage.
- **Replacement:** controlled substitution preserving original/replacement lineage and period.
- **Customer Invoice:** receivable document from customer-side calculation.
- **Owner Payable Voucher:** owner/supplier payable document; videos call it `Payment Payable Voucher` / `Payment Payable Processing`.
- **`OWN...`:** definitively Outside Work when `TXNTYPE = 2`, **not** Owner/Lessor.
- **`LCH...`:** broad Labour/service-charge family (`TXNTYPE = 3`), not Rental-specific.

---

## 7. End-to-end lifecycle

```text
Company / Finance / Tax / party setup
    -> Customer / Lessee setup
    -> Lessor / supplier setup where externally sourced
    -> Driver setup where applicable
    -> Stable physical Vehicle setup
    -> Lessor Agreement / vehicle supply coverage
    -> Lessee Agreement
    -> Vehicle allocation / custody
    -> Handover / self-drive movement where applicable
    -> Daily Running Chart or Replacement Running Chart
    -> Finalized physical usage evidence
         |-- customer calculation -> Customer Invoice -> Receipt -> Allocation
         `-- owner calculation    -> Owner Payable  -> Payment -> Allocation
    -> Debit/Credit adjustments and supported deductions
    -> Cheque/instrument realization and bank reconciliation
    -> Customer/Lessor/Vehicle/Running-Chart/Finance reporting
    -> Explicit correction/reversal when required
```

Customer billing and owner settlement are parallel consumers. Either may be processed first. Processing one side must not consume or block the other side's eligibility.

---

## 8. Parties and master data

### Customer / Lessee

Observed concepts include customer ID/name, address/contact, balance/statement context, tax attributes, agreements, invoices, receipts and allocations. Mutable customer defaults are not historical rate authority; posted billing uses effective agreement/rate snapshots.

### Lessor / Supplier

Observed concepts include Lessor identity, vehicle relationship, owner agreement, payable/statement history, fuel/repair debit notes, cash/petty-cash/cheque payments and allocations.

**Evidence-derived decision:** use one Lessor/Supplier role with classification/subtype for individual owner vs leasing company. Do not create two settlement engines for the same economic role.

### Driver

Videos show a Driver Register and Running Chart driver identity. Agreement/invoice/payable screens contain driver salary/recovery, working hours, normal/double/triple OT and night-out fields. Employee identity/qualification belongs to HR; Rental owns rental assignment and usage facts.

---

## 9. Physical Vehicle identity and relationship integrity

TACGL contains six normalized duplicate registration groups:

- `CAQ-7638` / `CAQ 7638`;
- `CAF-6512` / `CAF 6512`;
- `CAD-1608` / `CAD 1608`;
- `CBJ-6594` / `CBJ 6594`;
- `CBD-3677` / `CBD 3677`;
- `KJ7558` / `KJ-7558`.

Several punctuation variants carry different debtor/commercial contexts, proving a legacy workaround where physical identity was duplicated to encode relationships.

> **Authoritative rule: one physical vehicle = one stable Vehicle identity.**

Registration normalization must prevent punctuation/spacing variants from creating another vehicle. Customer use, owner supply and agreement relationships are effective-dated records, not alternate Vehicle rows.

`vehtyp.dbf` defines `01 OWN VEHICAL`, `02 HIRED VEHICAL`, `03 OUTSIDE VEHICAL`, yet all **1,076 active `scfveh` rows** have `VEHTYP = 03`. Therefore `VEHTYP` is not reliable ownership/supply truth. Ownership/source must come from explicit effective-dated relationships.

---

## 10. Lessee Agreement — customer commercial contract

The Lessee Agreement screen directly exposes:

- agreement date/type, active/close status;
- Lessee ID and agreement number;
- vehicle registration/type;
- executing/start/end dates;
- Company/Personal format;
- Monthly/Daily basis;
- maximum KM;
- Non-AC / Front-AC / Dual-AC rate contexts;
- rate for maximum KM and excess KM;
- default AC mode;
- With Driver;
- VAT calculation, VAT/SVAT invoice context, VAT % and SSCL %;
- legacy Rental Income, Excess KM Income and Parking/Other GL mappings;
- introducer, NIC/passport, driving licence and security deposit.

Rules:

- agreement dates define commercial validity;
- Monthly and Daily are distinct bases;
- With Driver and self-drive are commercially meaningful;
- rates may differ by AC mode;
- effective Lessee Agreement/version is customer-rate authority;
- executed historical terms must not be rewritten; later changes create new versions;
- raw GL mapping is not a normal Rental operator responsibility in AutoERP.

---

## 11. Lessor Agreement — owner/supplier contract

The Vehicle Owner Agreement screen directly exposes agreement type/date, Lessor ID, agreement number, vehicle, executing/start/end dates, Monthly/Daily basis, maximum KM, AC rate contexts, max-KM/excess rates, With Driver, VAT calculation, description/status, and legacy owner-side account mappings.

Some labels incorrectly say `LESSEE`; surrounding Lessor context proves this is a legacy label defect.

Rules:

- Lessor Agreement is separate from Lessee Agreement;
- it is owner-payable rate authority;
- customer invoice rates/amounts cannot determine owner payable;
- historical owner settlement freezes the effective Lessor Agreement version/rates;
- externally sourced vehicle use requires valid source coverage for the usage period.

---

## 12. Charge vocabulary and rate-source rule

`scfchr.dbf` includes:

| Code | Meaning | Master rate |
|---|---|---:|
| `HIRIN` | With-driver monthly car hiring | 0 |
| `EXCES` | With-driver monthly car excess charge | 0 |
| `RENT1` | Self-drive monthly car hiring | 0 |
| `HIRE1` | With-driver van hiring | 0 |
| `OT100` | Driver overtime | 0 |

The zero master rates strongly establish that these classify charge components while actual values are agreement/transaction specific. No universal rate may be inferred from these codes.

---

## 13. Vehicle supply, allocation and custody

The videos often select vehicles in agreements/Running Charts rather than exposing a dedicated allocation screen. UI simplicity does not remove the backend relationship requirement.

Minimum facts:

- stable Vehicle ID;
- Lessee Agreement/version where customer use exists;
- Lessor Agreement/version/source where external supply exists;
- effective start/end;
- driver/self-drive context;
- handover/return odometer where relevant;
- operational status;
- original/replacement lineage;
- audit/version metadata.

Mandatory rules:

- no conflicting blocking customer-use allocations for one physical vehicle/period;
- allocation must fit agreement dates;
- external customer use requires valid vehicle-supply coverage;
- source vehicle must match allocated vehicle;
- Workshop/off-road/breakdown holds block conflicting Rental use through shared Vehicle availability;
- historical relationships are not overwritten;
- concurrent allocation attempts are resolved atomically.

---

## 14. Daily Running Chart — authoritative physical usage truth

The `Daily Running Chart - Normal` screen directly contains **both commercial sides in one physical record**:

- Vehicle Registration Number;
- Lessee Agreement No/basis and Lessee identity;
- Lessor Agreement No/basis and Lessor identity;
- Driver identity;
- Start/Finish Date;
- OT Type/day of week;
- Start/Finish Mileage and KM Reading;
- Start/Finish Time;
- working/OT hours;
- Particulars of Hire;
- Night Outs;
- Other Charges;
- Garage Mileage.

Visible continuity controls include `Clear Both Mileage`, `Continue with Finish Mileage`, `Clear Both Time`, `Clear Start Time`, `Clear Finish Time`, and `Continue Both Time`.

This is direct proof that one physical usage record links customer and owner commercial contexts. Do **not** create separate owner/customer physical Running Charts for the same event.

Required validations:

- finish mileage >= start mileage;
- derived KM reconciles with structured odometer facts when both exist;
- usage period fits allocation/agreement coverage;
- vehicle/driver conflicts rejected;
- continuity checked against adjacent usage;
- continuation can be deliberate or reset can be deliberate; unexplained discontinuity is not silently overwritten;
- garage mileage remains a separate operational quantity;
- finalized usage is immutable;
- corrections preserve lineage;
- customer and owner commercial consumption are independent.

### Lifecycle

Minimum evidence-compatible lifecycle:

```text
Draft -> Finalized
Finalized -> Corrected/Superseded through explicit lineage
```

Customer-side and owner-side consumption are independent state dimensions. Extra maker-checker stages are added only with approved governance evidence.

---

## 15. Monthly agreement cycles

TACGL proves `Monthly` is not calendar-month-only. Observed cycles include:

- `25/06/2025 -> 24/07/2025`;
- `25/07/2025 -> 24/08/2025`;
- `18/06/2025 -> 17/07/2025`;
- `18/07/2025 -> 17/08/2025`;
- `18/08/2025 -> 17/09/2025`.

**Resolved rule:** monthly billing periods are agreement-cycle/anniversary periods. The billing anchor/cycle must be represented explicitly; monthly calculation must not hardcode calendar month boundaries.

---

## 16. Partial-period proration

Customer precedent: `CBM-9887` has full monthly customer lines of 225,000 for 18th-to-17th cycles and later an invoiced `18/09/2025 -> 30/09/2025` amount 97,500.

`225,000 x 13 / 30 = 97,500`.

An intended full `18/09 -> 17/10` 225,000 line also exists before the shortened invoiced period, demonstrating period truncation/correction.

Owner/payment precedent: `RENTAL PAYMENT 21DAYS` = 126,000 in a recurring context whose full-month amount is 180,000.

`180,000 x 21 / 30 = 126,000`.

### Evidence-derived decision

- `FIXED_30_DAY` is the only directly evidenced TACGL proration method for these observed partial monthly cases.
- It is appropriate for reconstructing/importing those historical source transactions.
- It is **not** proven as the universal default for every future agreement.
- New agreements must carry an explicit/versioned proration policy if partial periods can occur; do not hardcode `/30` globally.
- Actual-calendar-day proration is not evidenced as a universal rule.

---

## 17. Included KM, excess KM and garage mileage

Explicit evidence:

- Lessee/Lessor agreements contain maximum/included KM and excess rates;
- Running Chart records physical KM;
- invoice/payable screens contain total KM and total excess KM;
- TACGL contains repeated excess charges tied to billing periods.

Verified arithmetic includes:

- `1,172 x 90 = 105,480`;
- `1,165 x 65 = 75,725`;
- `1,082 x 90 = 97,380`;
- `1,962 x 75 = 147,150`;
- `635 x 65 = 41,275`;
- `1,845 x 90 = 166,050`;
- `483 x 75 = 36,225`;
- `2,135 x 65 = 138,775`;
- `1,352 x 90 = 121,680`;
- `3,356 x 75 = 251,700`.

**Evidence-derived interpretation:** excess evaluation is scoped to the applicable billing/agreement cycle; aggregate eligible Running Chart usage within that cycle before excess calculation.

Still unresolved: unused-KM carry-forward, pooling across replacement vehicles, exact legacy `Normal / By Hire / By Log Transaction` algorithms and KM rounding.

### Garage mileage

Garage Mileage is explicitly a separate Running Chart/report fact. Preserve it separately. The sources do **not** prove that it is always billable, always excluded or always subtracted. Never hardcode `commercial_km = total_km - garage_km`; commercial treatment must be policy/agreement driven.

---

## 18. Customer billing

The `Credit Invoice` screen supports Lessee/agreement/vehicle/period, invoice date/no/sequence, total and excess KM, normal/double/triple OT, night-outs, days/hires, driver salary/recovery, working hours, OT/night-out rates, maximum KM, rental/excess rates, tax and component totals. It explicitly includes `Import Running Chart Data`, Process, Create Invoice, Find and Print.

Evidence-supported component vocabulary:

```text
Base rental
+ excess-KM charge
+ driver salary recovery (when applicable)
+ normal/double/triple OT recovery
+ night-out recovery
+ approved parking/other recovery
+ approved miscellaneous adjustment
- approved discount/credit adjustment
+ effective tax
= customer invoice total
```

Rules:

- use effective Lessee Agreement/version;
- consume only finalized eligible customer-side Running Chart facts;
- persist structured quantity/unit/rate/amount/period/source for each component;
- freeze agreement/rate/tax snapshots;
- same source cannot be customer-billed twice;
- retries/double-clicks are idempotent;
- Rental owns source/calculation orchestration; Invoice owns financial-document lifecycle.

---

## 19. Exact customer invoice -> AR -> receipt -> GL lineage

`LCH2005407` proves an end-to-end chain:

- vehicle `CBD 3677`;
- job `RMS2005443`;
- detail `1,172KM*90.00`;
- amount `105,480`;
- invoice `INV2005519`.

`scfinv` carries the same invoice/customer/vehicle/job/amount; `scftdb` carries the debtor entry. Receipt `REC2003089` later allocates 105,480 to the invoice. The same receipt has **12 invoice allocations totaling 2,033,010**, proving one receipt can allocate across many invoices.

Legacy GL has Trade Debtors `5000-000` and, in this example, a credit to `0001-005 SALES: - BREAKDOWN`.

**Required business capability:** source -> invoice -> receivable -> receipt allocation -> GL traceability.

**Rejected legacy mechanism:** Rental revenue must not use Workshop Breakdown/Tinkering sales accounts merely because TACGL did.

---

## 20. Structured calculation authority vs free text

TACGL often stores meaningful formulas in narrative text while structured numeric fields hold only totals. Direct conflicts exist:

- text `1,080KM * 90.00` with stored value `81,000`;
- impossible text date `31/09/2025`;
- deleted `544KM x 300` Outside Work line stored 163,000, later corrected to 163,200.

**Authoritative rule:** free text is explanatory only. Financial calculation authority must be structured.

Minimum calculation snapshot:

- component type;
- quantity/unit;
- effective rate and rate/agreement version;
- source Running Chart IDs;
- calculation period;
- amount;
- tax snapshot;
- rounding-policy identity;
- source fingerprint/idempotency identity;
- generated human-readable explanation.

---

## 21. Owner / Lessor settlement

The `Payment Payable Voucher` screen uses the same physical metrics as customer billing but owner-side terms: agreement/vehicle/Lessor, period, total/excess KM, OT/night-outs, days/hires, basis, With Driver, driver salary, maximum KM, rate matrix and output components including Rental Expenses, Excess KM Expenses, VAT and refundable driver salary/OT/night-out. It explicitly supports `Import Running Chart Data`, Process and Create Payable Voucher.

Evidence-supported component vocabulary:

```text
Base owner rental payable
+ excess-KM payable
+ driver reimbursement
+ normal/double/triple OT reimbursement
+ night-out reimbursement
+ approved other reimbursements
- approved supported deductions
- approved advances/debit adjustments
+ approved credit adjustments
- applicable withholding
= net owner payable
```

Rules:

- use effective Lessor Agreement/version;
- use same finalized physical usage but separate owner-side consumption;
- customer invoice value/rates never determine owner payable;
- same owner-side source cannot be settled twice;
- payment allocation cannot exceed open payable;
- posted owner payable is immutable and corrected by reversal/adjustment.

---

## 22. Regular Rental Payment evidence

TACGL account `7048-000` is `RENTAL PAYMENT`. The active GL corpus has **25 positive debit rows across 21 PRB bank-payment vouchers totaling 3,396,309**.

Example `PRB1000970` contains vehicle detail amounts:

- `CBD-3677` 180,000;
- `CAD-1608` 112,500;
- `CBJ-6594` 162,000.

Descriptions include monthly Rental Payment, multi-day Rental Payment, Jeep Hire Payment, Hiring Payment and Hiring Payment Van.

The economic event is valid, but direct free-text expense -> bank is not a sufficient target settlement model. The stronger structured business flow evidenced by the videos is:

```text
Lessor/Supplier
 -> effective Lessor Agreement/source
 -> structured owner payable calculation
 -> payable document
 -> payment/allocation
 -> bank instrument/reconciliation
 -> Finance/GL
```

---

## 23. Fuel/repair and other owner adjustments

Video evidence explicitly shows `Lessor Debit Note - Fuel & Repair` with date, note number/sequence, Lessor Control, vehicle, Lessor, total debit, Fuel/Repair choice, Fuel Chit/Invoice No, credit GL/detail/amount and an allocation tab. A generic `Lessor Debit Note` also exists.

**Resolved process rule:** owner-side Fuel/Repair recovery is an explicit adjustment/debit note with reason, evidence and allocation, not silent mutation of the original payable.

**Unresolved liability rule:** source evidence does not establish universally when the owner is liable, allowed markup, approval threshold or exact calculation basis.

---

## 24. Receipts, payments and allocations

Customer side supports full/partial receipt, one receipt allocated across multiple invoices, unallocated balance/advance, allocation reversal, receipt reversal and deposit receipt where applicable. Video visibly shows `Lessee's Receipt` / `Lessee's Receipt Allocation`.

Owner side supports cash/petty-cash/cheque payment, allocation, debit/credit notes, owner receipt/refund cases where applicable and unallocated reporting.

Mandatory controls:

- party/direction/currency/source match;
- no over-allocation;
- concurrent allocation cannot consume same balance twice;
- allocation must not duplicate GL posting of already-posted receipt/payment;
- reversal restores balances/eligibility consistently;
- Payment owns receipt/payment/instrument/allocation lifecycle.

---

## 25. Cheque realization and bank reconciliation

`Lessor's Cheque Payments` includes date, payment voucher, bank, cheque number, Cross/Bearer, Account Payee Only, amount, details, realized date, transaction type, Lessor, cheque payee, vehicle and lessor amount. A separate `Edit Cheque Payment For Bank Reconciliation` form demonstrates realization/reconciliation handling.

Required behavior:

- payment economic facts immutable after posting/issue;
- realization/clearing is an event/status, not rewrite of original amount/payee/source;
- cheque uniqueness correctly scoped to bank account/cheque book;
- cancelled/stopped/bounced/replaced instruments use explicit states/events;
- reconciliation is auditable.

Exact instrument state names belong to Payment/Finance policy.

---

## 26. Deposits, advances and generic adjustments

Security Deposit appears on agreement screens. Lessee/Lessor transaction menus contain receipts, payments, debit and credit notes.

Do not collapse invoice receipt, customer advance, security deposit, owner advance, debit/credit adjustment, refund and forfeiture/application into one generic balance. Each requires party, source, reason, amount, evidence and audit history.

Unresolved: whether deposit is mandatory, due timing, application priority, partial application, refund timing, forfeiture, early-termination/damage treatment and tax treatment.

---

## 27. Driver, working time, OT and night-out

Cross-source evidence establishes:

- agreements hold working-hour/rate context;
- Running Chart stores actual time, OT type/hours and night-outs;
- customer invoice can recover driver salary/OT/night-out;
- owner payable can reimburse driver salary/OT/night-out.

**Resolved rule:** physical driver/time facts are shared operational evidence; customer recovery and owner reimbursement are independent calculations using their own agreement rates.

Unresolved: universal working-hour window, normal/double/triple OT qualification algorithm, weekend/holiday treatment, OT rounding/minimum block, multi-driver split, night-out qualification and universal driver-salary partial-period formula.

---

## 28. Replacement vehicle and downtime

Replacement Running Chart and reports by original/replacement vehicle are directly evidenced.

Resolved structural rules:

- explicit original vehicle, replacement vehicle and exact effective period;
- Running Chart identifies actual physical vehicle used;
- replacement cannot silently rewrite original allocation/history;
- replacement must pass availability/source checks;
- no contradictory overlapping physical usage;
- source-to-finance traceability preserves replacement lineage.

Unresolved commercial rules: customer charging during replacement, owner payable during replacement, customer downtime credit, owner downtime deduction, included-KM pooling across original/replacement and partial-day treatment.

---

## 29. Third-party / one-off hired vehicle flow

TACGL demonstrates one-off/third-party rental through legacy Outside Work.

Example `OWN2003536`, job `RMS2005503`, vehicle `CAW-6550`:

- Jeep with driver `35,000 x 3 days = 105,000`;
- Driver OT `24.30 hrs x 500 = 12,250`;
- Driver Bata `2,000`;
- deleted excess line had `544KM x 300` with wrong stored 163,000;
- corrected `OWN2003537` stores 163,200.

Cost flowed through Pending Jobs / Trade Creditors and creditor subledger; customer invoice `INV2005580` was 289,400. Additional active examples include car-rent charges and `SELF DRIVE DAILY BASIS CAR - 14 DAYS` with `14 x 8,000 = 112,000`.

**Business capability:** support third-party/one-off vehicle sourcing, supplier cost, customer recovery and profitability.

**Boundary rule:** do not make Vehicle Rental a Vehicle Service Outside Work subtype. Rental owns rental source/use/commercial context; financial owners handle payable/payment/GL.

---

## 30. Tax

Videos expose VAT, SVAT and SSCL fields. In the re-audited TACGL sample of active Rental-related invoices, invoice VAT/NBT amounts are zero.

**Correct interpretation:** Rental is not proven universally tax-free. Zero-tax historical samples cannot become a global default. Effective Tax policy determines applicability by date/party/transaction and posted calculations freeze a tax snapshot.

Vehicle Rental sources do not by themselves prove exact VAT/SVAT/SSCL effective rules, withholding, tax ordering, currency/tax rounding or FX policy. Those remain Tax/Finance responsibilities.

---

## 31. Finance / GL ownership

TACGL proves accounting integration and simultaneously shows legacy misclassification:

- Rental customer revenue posted to Workshop sales accounts such as Breakdown/Tinkering;
- regular owner/source payments debited directly to `7048-000 RENTAL PAYMENT` and credited to bank;
- third-party hire cost flowed through Pending Jobs / Trade Creditors.

Vehicle Rental owns agreements, supply/use relationships, Running Chart/replacement, commercial calculation source and source-consumption identity.

Finance owns account roles/posting profiles, periods, journals, ledger/GL and financial reversal.

Current AutoERP already has reusable Finance vocabulary including `customer_rental_invoice`, `supplier_rental_invoice`, `rental_deposit`, `rental_revenue` and `rental_expense`. These are integration vocabulary, not evidence that an active Rental source module currently exists.

A Rental source must not be marked financially posted if required Invoice/Tax/Finance consequences fail.

---

## 32. Vehicle Service / workshop interaction

Workshop evidence shows Customer + Vehicle -> Job -> Material Issue -> Outside Work -> Labour -> Debtor Job Invoice -> Payment/close.

Vehicle Service owns jobs/service/breakdown operational state. Vehicle Rental owns rental agreements/allocation/Running Chart/commercial facts. Vehicle owns physical identity and shared availability.

Current AutoERP exposes `VehicleAvailabilityBlockerInterface` with tag `vehicle.availability_blocker`.

Rules:

- Workshop/off-road/breakdown may block Rental use;
- Rental queries/enforces shared Vehicle availability;
- Rental never clears a hold owned by Vehicle Service;
- Vehicle Service does not calculate Rental charges;
- Rental does not duplicate Workshop state.

---

## 33. Business states and transitions

Use explicit enums/value types, not magic status integers/strings.

Agreement minimum model:

```text
Draft -> Active -> Closed/Terminated
```

Executed versions are immutable; later term/rate changes create effective versions.

Allocation/custody conceptually moves from planned/active to returned/completed or replacement lineage.

Running Chart:

```text
Draft -> Finalized
Finalized -> Corrected/Superseded through explicit correction lineage
```

Commercial consumption is independent per side:

```text
Unconsumed -> Calculated/Reserved -> Posted/Consumed
```

A failed/idempotent retry must not leave a source falsely consumed. Financial document states belong to their owning modules.

---

## 34. Core validation rules

### Identity/scope

- tenant/organization scope every authoritative write;
- stable Vehicle ID rather than free-text registration as relationship authority;
- party IDs valid for correct tenant/context;
- no raw internal IDs entered by ordinary users.

### Agreement

- start <= end;
- allocation/usage fits effective agreement version;
- side-specific party semantics match agreement side;
- rates match declared basis/component;
- executed versions not silently rewritten.

### Supply/use

- no overlapping blocking use of one physical vehicle;
- external customer use requires valid supply coverage;
- vehicle/source mismatch rejected;
- availability blockers enforced atomically.

### Running Chart

- finish mileage >= start mileage;
- KM reconciles with structured odometer facts where present;
- valid date/time range;
- fits allocation/agreement;
- continuity discrepancy explicit;
- duplicate physical usage rejected;
- finalized record immutable.

### Commercial calculation

- correct side/agreement version;
- finalized eligible sources only;
- exact quantity/rate/period snapshots;
- no duplicate same-side consumption;
- customer side never reads owner amount as rate source and vice versa;
- unresolved monetary policy must not silently fall back to a guessed formula.

### Money allocation

- no over-allocation;
- correct party/currency/direction;
- concurrency-safe open balance;
- reversal restores balance exactly once.

---

## 35. Concurrency and idempotency

High-risk races include same-vehicle overlapping allocation, supply change during customer allocation, conflicting Running Chart finalization, duplicate customer billing, duplicate owner settlement, replacement vs finalization, Workshop hold vs Rental allocation, duplicate receipt/payment allocation, posting vs reversal and rate-version change during calculation.

Required controls: database transactions, deterministic lock order, row/version checks, constraints where appropriate, idempotency/source fingerprints, explicit conflict responses, and no frontend workaround that hides stale writes.

---

## 36. Correction, reversal and historical truth

TACGL deleted records and `del_*` mirrors prove correction/re-entry is real but legacy deletion is not the target.

Rules:

- finalized physical usage is not hard-deleted;
- posted financial documents are not edited/deleted;
- correction creates new version/supersession with reason/actor/time;
- financial correction uses reversal/adjustment;
- original quantity/rate/amount/source remains queryable;
- reversal restores source eligibility only through the owner workflow;
- no double reversal;
- correction/reversal is atomic with affected balances/consumption.

---

## 37. Reporting and reconciliation

Operational evidence includes Lessee/Lessor Log Sheets, replacement reports by original/replacement vehicle, vehicle log checks, driver overtime, self-drive movement and date/mileage/time/OT/night-out/garage-KM summaries.

Customer evidence includes ledger, vehicle ledger, agreement/invoice lists, aging/outstanding, statements, debit/credit notes, receipt/allocation/unallocated and tax-related outputs.

Owner evidence includes ledger, vehicle-wise statement, agreement/payable/payment lists, outstanding, unallocated transactions and fuel/repair deductions.

Legacy menus contain allocation, relationship, double-entry, source-vs-GL and bank-reconciliation procedures/reports.

**Target interpretation:** prevent invalid states at write time; reconciliation verifies authoritative state and identifies external/legacy discrepancies rather than serving as routine repair for avoidable invalid writes.

---

## 38. Legacy design defects that must not be copied

1. Duplicate physical Vehicle rows for registration formatting/commercial context.
2. `VEHTYP` treated as ownership truth despite contradictory data.
3. Rental billing embedded in Workshop RMS/LCH structures.
4. `OWN` misread as owner settlement instead of Outside Work.
5. `LCH` misread as Rental-specific instead of broad Labour/service charge.
6. Rental revenue posted into Workshop Breakdown/Tinkering sales accounts.
7. Owner/source payments represented primarily by free-text expense-to-bank vouchers.
8. Quantity/rate/date authority left in free-text narratives.
9. Free-text narrative disagreeing with stored amount/date.
10. Raw GL codes exposed on operational agreement forms.
11. Duplicate settlement workflows for owner vs leasing-company classifications.
12. Deleted-record mirror tables as correction-history strategy.
13. After-the-fact repair procedures substituting for write-time integrity.
14. Password/numeric-user-level legacy security patterns.
15. Mutable-looking bank reconciliation instead of explicit instrument events.
16. Reused/misleading labels such as `LESSEE` fields on owner agreement screens.
17. Historical rates repeated without obvious immutable effective-version source.

Preserve business capability, not these mechanisms.

---

## 39. Ambiguity and business-rule decision register

| Rule/question | Evidence status | Authoritative interpretation |
|---|---|---|
| Monthly period = calendar month? | Resolved | **No.** Use agreement-cycle/anniversary periods. |
| Partial monthly proration | Partially resolved | `FIXED_30_DAY` is evidenced for observed legacy partials. Use it for reconstructing those source transactions; no universal future default without policy. |
| First/last-day inclusion | Inferred from examples | Historical examples behave as inclusive counts; do not generalize beyond selected proration policy. |
| Early close/shortened period | Strong precedent | Period may be truncated/recalculated; preserve superseded/original history. |
| Minimum billable period | Unresolved | No universal rule evidenced. |
| Included-KM scope | Evidence-derived | Evaluate within applicable billing/agreement cycle. |
| Unused-KM carry-forward | Unresolved | No universal carry-forward evidence. |
| Multiple Running Charts in same cycle | Evidence-derived | Aggregate eligible facts for same agreement cycle; consume each source once per side. |
| Pool KM across replacement vehicles | Unresolved | Explicit agreement/replacement policy required. |
| `Normal / By Hire / By Log Transaction` algorithm | Unresolved | Preserve named mode if needed; do not invent formula. |
| Garage mileage | Partially resolved | Preserve separately; commercial inclusion/exclusion is policy-specific. |
| KM rounding | Unresolved | No universal rule evidenced. |
| Replacement relationship | Resolved structurally | Explicit original/replacement + exact period + actual physical usage. |
| Replacement customer charge | Unresolved | Agreement/policy required. |
| Replacement owner payable | Unresolved | Effective source/agreement policy required. |
| Downtime customer credit | Unresolved | No universal rule evidenced. |
| Downtime owner deduction | Unresolved | No universal rule evidenced. |
| Driver working-hour context | Partially evidenced | Agreement has working-hour fields; exact classification algorithm not universal. |
| Normal/double/triple OT qualification | Unresolved | Do not derive solely from elapsed hours without policy. |
| Weekend/holiday OT | Unresolved | No universal policy evidenced. |
| OT rounding/minimum block | Unresolved | No universal policy evidenced. |
| Night-out qualification | Unresolved | Count/rate components exist; qualification rule not proven. |
| Driver salary partial-period formula | Unresolved | No universal formula proven. |
| Fuel/repair deduction process | Resolved | Explicit Lessor Debit Note + evidence + allocation. |
| Fuel/repair liability/markup | Unresolved | Agreement/policy required. |
| Accident/insurance excess responsibility | Unresolved | No universal responsibility rule evidenced. |
| Deposit exists | Resolved | Agreement and transaction concepts support it. |
| Deposit mandatory/timing/refund/forfeiture | Unresolved | No universal rule evidenced. |
| Rental tax universally zero | Resolved as false inference | Sample invoices are zero-tax but forms expose VAT/SVAT/SSCL; Tax policy controls. |
| Customer amount determines owner amount | Resolved | **Never.** Separate agreements/rates. |
| One Running Chart per commercial side | Resolved as false | One physical Running Chart links both sides; commercial consumption is separate. |
| Owner vs leasing company need separate settlement engine | Evidence-derived | No. Use one Lessor/Supplier role with classification. |
| `OWN...` means owner | Resolved as false | `OWN` = Outside Work. |
| `LCH...` means Rental | Resolved as false | Broad Labour/service-charge family. |
| `VEHTYP` determines ownership | Resolved as false | Data contradicts it; use effective source relationship. |
| Free-text formula/date is authoritative | Resolved as false | Structured facts are authoritative. |
| Maker-checker mandatory | Unresolved | Do not add approval stages without governance evidence. |
| Agreement activation/termination authority | Unresolved | Permission policy required. |
| Running Chart finalization authority | Unresolved | Permission policy required. |
| Payment preparation vs bank reconciliation segregation | Unresolved | Governance policy required. |
| Reservation before agreement | Unresolved/not evidenced | Do not invent reservation subsystem. |
| Condition photos/checklist | Unresolved/not evidenced | Add only with separate evidence. |
| Fuel-level evidence | Unresolved/not evidenced | Add only with separate evidence. |
| Customer signature requirement | Unresolved/not evidenced | Add only with separate evidence. |
| Credit-limit hard block | Unresolved/not evidenced | Customer/Finance policy required. |
| Notifications/reminders | Unresolved/not evidenced | Configuration/business policy required. |

### Rule for unresolved items

An unresolved item is not permission to choose the easiest formula. A future implementation must obtain approved policy, support an explicit versioned policy mode where the business supports alternatives, or fail closed when proceeding would fabricate a monetary/eligibility result.

---

## 40. Non-negotiable domain invariants

1. One physical vehicle has one stable Vehicle identity.
2. Registration formatting differences do not create a new vehicle.
3. Ownership/supply/customer-use relationships are effective-dated and historically preserved.
4. Lessee and Lessor Agreements are separate contracts.
5. External customer use requires valid vehicle supply coverage.
6. One physical Running Chart/fact stream is shared operational evidence.
7. Running Chart can link both Lessee and Lessor agreements for same physical use.
8. Customer billing and owner settlement are independent calculations.
9. Customer rates/amounts never determine owner rates/amounts.
10. Owner rates/amounts never determine customer rates/amounts.
11. Same finalized usage cannot be consumed twice on the same commercial side.
12. Processing one side does not consume/block the other side.
13. Historical calculations freeze agreement/rate/usage/tax/source snapshots.
14. Structured quantity/rate/date facts are authoritative; free text is explanatory.
15. Monthly period boundaries follow agreement cycle/policy, not calendar-month assumption.
16. Finalized operational facts are immutable and corrected with lineage.
17. Posted financial documents are immutable and corrected through reversal/adjustment.
18. Allocation, usage finalization, source consumption and money allocation are concurrency-safe.
19. Every authoritative write is tenant/organization scoped and permission checked.
20. Vehicle-owned blocking availability prevents conflicting Rental use.
21. Cross-module rules remain with their owning module and are consumed via explicit contracts.
22. Reports derive from authoritative sources rather than parallel mutable totals.
23. Business-significant transitions are auditable.
24. Third-party hire cost and customer recovery may be related for profitability but one does not calculate the other.
25. Predefined options use enums/value types; shared immutable values use constants; changeable/environment values use configuration/policy.

---

## 41. Current AutoERP implementation reconciliation

At `worktree-0.0.8` HEAD `f9e8cd33a9e296ab4b831003339759e0cba95df8`:

- active `app/Modules/VehicleRental` runtime is absent/retired;
- active Vehicle Rental frontend is absent;
- historical `InvoiceType::Rental` remains as a retired-source invoice type for already-posted/history compatibility;
- active shared modules include Customer, Finance, HR, Invoice, Payment, Vehicle, Vehicle Service, Audit and Idempotency;
- Vehicle exposes `VehicleAvailabilityBlockerInterface` as shared availability boundary;
- Finance vocabulary already distinguishes customer/supplier Rental posting profiles and Rental revenue/expense/deposit roles.

A future Rental rebuild should own only Rental agreements/versioning, Rental supply/use/custody context, Running Chart/replacement, Rental commercial calculation orchestration, source-consumption identity and Rental-specific operational reports/read models.

It should reuse/coordinate with:

- **Vehicle:** physical identity, registration, shared availability/odometer context;
- **Customer:** customer identity;
- **Supplier/party owner:** Lessor/Supplier identity;
- **HR:** driver identity/qualification;
- **Invoice:** customer financial-document lifecycle;
- **Payment:** receipts, payments, instruments and allocations;
- **Tax:** tax determination/snapshots;
- **Finance:** posting profiles, journals, ledger/GL and reversal;
- **Audit:** immutable event history;
- **Idempotency:** retry-safe mutations where appropriate;
- **Vehicle Service:** Workshop/service/breakdown state through shared Vehicle contract.

Do not resurrect a historical VehicleRental branch wholesale. Rebuild from proven business meaning against current module contracts.

---

## 42. AI-agent decision protocol

When an AI agent reasons about or changes Vehicle Rental behavior:

1. Identify the physical Vehicle and exact effective period.
2. Identify whether action is shared operational, customer-side or owner-side.
3. Resolve effective Lessee/Lessor Agreement versions separately.
4. Verify supply/use coverage and availability.
5. Use finalized structured Running Chart facts, not narrative text.
6. Apply only evidenced/configured commercial policies.
7. Keep customer and owner rates/calculations independent.
8. Freeze source/rate/tax snapshots for any financial result.
9. Enforce same-side exactly-once consumption and idempotency.
10. Delegate financial document/payment/tax/GL responsibility to owning modules.
11. Preserve immutable history and correct with explicit lineage/reversal.
12. If unresolved policy changes money or eligibility, require explicit policy or fail closed.
13. Never infer Rental semantics from legacy prefixes alone (`OWN`, `LCH`, `VEHTYP`).
14. Never copy a legacy GL/account placement merely because TACGL historically posted there.
15. Never add/remove cross-domain relationships without justifying ownership, direction and integrity effect.

---

## 43. Minimum production-capable Vehicle Rental scope

A business-complete first release requires at least:

1. Lessor/Supplier role and external vehicle supply coverage.
2. Versioned Lessor Agreement.
3. Versioned Lessee Agreement.
4. Stable Vehicle identity/registration normalization.
5. Effective allocation/custody with overlap protection.
6. Shared Vehicle/Workshop availability integration.
7. Daily Running Chart with controlled mileage/time continuity.
8. Replacement lineage.
9. Immutable Running Chart finalization/correction.
10. Customer calculation from Lessee terms and structured usage.
11. Customer Invoice through Invoice-owned lifecycle.
12. Owner calculation from Lessor terms and same physical usage.
13. Owner payable through approved financial-document owner.
14. Customer Receipt/Allocation through Payment.
15. Owner Payment/Allocation through Payment.
16. Explicit debit/credit adjustments and supported Fuel/Repair deduction.
17. Tax/Finance integration through configured posting profiles.
18. Customer/owner/vehicle/Running-Chart source drill-down/statements.
19. Correction/reversal for finalized/posted documents.
20. Third-party/one-off sourcing without abusing Workshop Outside Work ownership.
21. Agreement-cycle billing support.
22. Explicit policy for every unresolved rule actually used by the release.

---

## 44. Release verification scenarios

Verify at minimum:

- same physical vehicle cannot be double-allocated for conflicting periods;
- registration punctuation variants cannot create duplicate physical vehicles;
- external customer use cannot exceed source coverage;
- Workshop hold blocks Rental conflict;
- one Running Chart can feed customer and owner sides independently;
- either commercial side can be processed first;
- retry/double-click cannot double bill/settle;
- customer rate never leaks into owner calculation and vice versa;
- non-calendar cycles `25 -> 24` and `18 -> 17` work;
- fixed-30 historical partial examples reproduce when that policy is selected;
- no universal `/30` assumption exists for every agreement;
- garage mileage remains separate and is not silently subtracted;
- structured `1,172 x 90 = 105,480` regression works;
- malformed/free-text date/amount cannot override structured facts;
- one receipt can allocate across multiple invoices without over-allocation;
- owner payments allocate without duplicate posting;
- Fuel/Repair debit note references evidence/allocation;
- posted correction uses reversal/lineage, not mutation;
- tax is policy-driven, not hardcoded zero;
- Rental postings use Rental-specific Finance profiles, not Workshop sales accounts;
- tenant/organization isolation holds under direct API access;
- concurrency races produce explicit conflict, not corrupted state.

---

## 45. TACGL traceability map

| Artifact | Business evidence |
|---|---|
| `tacdata/scfveh.DBF` | Vehicle identity/context; normalized duplicate-registration problem; unreliable `VEHTYP` usage |
| `tacdata/vehtyp.dbf` | Own/Hired/Outside vocabulary contradicted as ownership truth by actual master data |
| `tacdata/scfchr.dbf` | Rental/hire/excess/driver-OT component vocabulary with zero master rates |
| `tacdata/jobtxn.DBF` | Rental-like LCH lines, Outside Work, deleted/replaced transactions, free-text calculation evidence |
| `REPORTS/*.FRT/FRX` | Outside Work semantics and report inventory |
| `tacdata/scfjob.DBF` | RMS/job context connecting transaction lines to invoices |
| `tacdata/scfinv.DBF` | Customer invoice amount/customer/vehicle/job lineage |
| `tacdata/scftdb.DBF` | Debtor invoice and receipt allocation history |
| `tacdata/scftcr.DBF` | Creditor/Outside Work payable history |
| `tacdata/scfacc.dbf` | Trade Debtors/Creditors, Pending Jobs, Workshop Sales and Rental Payment vocabulary |
| `tacdata/scfglt.DBF` | Invoice GL postings and PRB Rental Payment evidence |
| `del_*` tables | Legacy correction/deletion behavior requiring immutable correction lineage |
| `PDFFILES/*.PDF` | Debtor outstanding/aging report evidence; no universal pricing rule |
| `PDFFILES/*.XLS` | Legacy reporting/export capability |

---

## 46. Video traceability map

### `1.mp4`

Direct evidence for Lessee Agreement fields/rates, customer Credit Invoice, shared Daily Running Chart linking Lessee + Lessor agreements, mileage/time continuation choices, Vehicle Owner Agreement, Payment Payable Voucher, owner Fuel/Repair Debit Note, generic owner Debit Note, Lessor cheque payment/allocation and cheque realization/bank reconciliation.

### `Recording 2026-06-21 132314.mp4`

Direct evidence for Vehicle Register, Lessee Register/Agreement, customer invoice/PDF, Daily Running Chart/reporting, receipt/allocation, Vehicle Owner Agreement/statements, Rental ledger/report workflow and legacy user/security screens.

### `2.mp4`

Evidence for lessor cash/petty-cash/cheque payments, lessor receipts/debit/credit notes, Payment Payable Processing, fuel/repair debit note, lessee payment/receipt/debit/credit/invoice/misc invoice, registers, ledgers/statements, payable/payment/allocation/unallocated reports, Running Chart/replacement reports, and allocation/double-entry/source-to-GL checks.

### `ScreenVideo_03-04-2026_18-02-52.mp4`

Supporting authority for Workshop job flow, Material Issue, Outside Work, Labour, job invoice and shared physical vehicle availability. It does not define Rental pricing formulas.

---

## 47. Knowledge maintenance rules

1. TACGL is primary business evidence; identical archive hashes are one corpus, not repeated independent proof.
2. Videos are authoritative workflow evidence for operator intent and shared physical processes.
3. Do not silently convert an observed precedent into a universal policy.
4. When a source label conflicts with transaction behavior, document the conflict and choose the stronger evidence-supported interpretation.
5. Preserve business meaning while rejecting legacy architectural defects.
6. New approved policies record owner, decision date, effective date, examples and acceptance tests.
7. Historical rates/amounts are regression evidence, never system defaults.
8. No legacy account code, customer/payee name or credential becomes hardcoded behavior.
9. Keep `/docs/changes` append-only.
10. Code is authoritative for what is implemented at a commit; this document is authoritative for captured Vehicle Rental business understanding.
11. If implementation differs from a legacy mechanism, preserve the economic/operational event and document why.
12. Use enums/value objects for option sets, constants for shared immutable values, configuration/policy for changeable values.
13. Do not add/remove cross-domain relationships blindly; justify ownership/direction/integrity impact.
14. When evidence is insufficient to calculate money, fail closed rather than fabricate a result.

---

## 48. Final domain conclusion

Vehicle Rental is not Vehicle CRUD and not merely an Invoice subtype. It coordinates:

- one stable physical Vehicle identity;
- Lessor/Supplier vehicle source and commercial terms;
- Customer/Lessee commercial terms;
- effective supply/use allocation and custody;
- driver/self-drive context;
- Daily/Replacement Running Chart physical evidence;
- independently calculated customer revenue and owner cost;
- customer receivables and owner payables;
- receipts/payments/allocations;
- deposits/adjustments and Fuel/Repair deductions where supported;
- Tax and Finance/GL;
- Workshop/off-road availability;
- cheque/bank realization and reconciliation;
- operational/financial reporting;
- immutable historical traceability.

The most dangerous failure pattern is:

```text
wrong physical vehicle
+ wrong supply/use relationship
+ wrong agreement/version
+ wrong billing cycle
+ bad/unstructured usage quantity
+ guessed commercial policy
+ duplicate source consumption
= wrong customer invoice and/or wrong owner payable
```

The correct AutoERP foundation is:

> **one stable Vehicle + separate versioned Lessee/Lessor agreements + effective supply/use relationship + one authoritative physical Running Chart + structured calculation facts + independent customer/owner consumption + owning-module financial posting + immutable/reversible history.**

TACGL and the videos establish this business shape strongly. They also establish where the legacy implementation cannot be trusted: duplicate Vehicle identities, misleading prefixes/labels, Workshop-embedded Rental billing, free-text arithmetic, direct free-text owner expense vouchers, mutable/deleted history and repair-oriented integrity controls.

Where the corpus truly cannot determine a universal policy, this document intentionally says **Unresolved**. That is a correctness guarantee, not missing implementation detail.
