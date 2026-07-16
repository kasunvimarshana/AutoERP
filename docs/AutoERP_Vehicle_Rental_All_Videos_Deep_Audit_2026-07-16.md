# AutoERP Vehicle Rental — All Uploaded Videos End-to-End Deep Audit

**Audit date:** 2026-07-16  
**Source material:** `1.mp4`, `2.mp4`, `Recording 2026-06-21 132314.mp4`, `ScreenVideo_03-04-2026_18-02-52.mp4`, `AGENTS.md`, and `RULES.md`  
**Total video duration:** approximately 1 hour 56 minutes 26 seconds  
**Primary scope:** Vehicle Rental business, operational flow, accounting integration, control weaknesses, target architecture, and remediation priorities

---

## 1. Executive verdict

The legacy Vehicle Rental system contains substantial and valuable business knowledge. It supports two independent commercial sides:

1. **Lessee/customer side** — revenue, receivables, invoices, receipts, and customer allocations.
2. **Lessor/vehicle-owner side** — rental cost, payables, owner settlements, payments, deductions, and owner allocations.

Both sides use the same operational evidence, especially the Daily Running Chart, but they apply separate agreements, rates, taxes, recoveries, and settlement rules.

The strongest business principle visible across the videos is:

```text
One operational usage record
    ├── Customer billing calculation
    └── Vehicle-owner settlement calculation
```

The most serious architectural weakness is that the legacy design is **agreement-centered and accounting-repair-oriented**, rather than **allocation-first and prevention-oriented**. Vehicle identity is selected directly inside agreements and transactions, while a complete effective-dated allocation lifecycle is not clearly demonstrated. The system also contains procedures and reports for finding allocation errors, double-entry errors, and source-to-ledger mismatches after they occur. A new AutoERP implementation should prevent those states during the authoritative write, not detect and repair them later.

The correct target is not a screen-by-screen clone. It is a clean rebuild that preserves the valid business rules while replacing the weak foundations with:

- effective-dated vehicle allocations;
- versioned agreements and frozen rate snapshots;
- one authoritative Running Chart stream;
- independent customer and owner consumption controls;
- immutable posted financial documents;
- atomic Tax and Finance posting;
- governed reversals;
- deterministic locking and optimistic concurrency;
- human-readable relationship selection;
- append-only audit history;
- clear module ownership.

---

## 2. Audit methodology and confidence

### 2.1 Directly observed

The following were directly visible in forms, menus, reports, and print previews:

- Vehicle Register.
- Lessee Register.
- Driver Register.
- Lessor registers for vehicle owners and leasing companies.
- Agreement Register — With Lessee.
- Agreement Register — With Vehicle Owners.
- Agreement Register — With Leasing Companies.
- Monthly and daily agreement basis.
- Included/maximum kilometres.
- Excess kilometre rates.
- Non-AC, Front-AC, and Dual-AC rates.
- Driver salary.
- Working hours.
- Normal, double, and triple overtime.
- Night-out rates.
- Security deposit fields.
- VAT/SVAT/SSCL-related fields and fixed GL account mappings.
- Daily Running Chart.
- Replacement Running Chart.
- Self-drive handover/return notes.
- Customer credit invoices and miscellaneous invoices.
- Customer cash/cheque receipts.
- Customer debit/credit notes.
- Lessor cash, petty-cash, and cheque payments.
- Lessor receipts.
- Lessor debit/credit notes.
- Payment Payable Processing.
- Fuel and repair deductions.
- Receipt/payment allocations.
- Cheque and bank reconciliation.
- Lessee, lessor, vehicle, agreement, invoice, allocation, balance, and ledger reports.
- Reports or procedures for allocation errors and ledger mismatches.
- Password Register and numeric-style user-level/security patterns.
- Workshop vehicle, job, labour, item, and maintenance-related screens.

### 2.2 Derived requirements

The following are not necessarily explicit legacy features, but are required to make the observed business flow correct and maintainable:

- Separate lessor and lessee vehicle allocations.
- Non-overlapping vehicle timelines.
- Agreement versioning and approval.
- Running Chart approval/finalization state.
- Separate customer and owner consumption records.
- Atomic source, Tax, and Finance posting.
- Reversal-only correction after posting.
- Optimistic concurrency and deterministic lock ordering.
- Odometer continuity.
- Maintenance/off-road availability blocking.
- Segregation of duties.
- Effective-dated Tax and GL mappings.
- Immutable transaction snapshots.
- Source-to-ledger reconciliation invariants.

### 2.3 Not safely inferable

The videos do not prove the exact business rules for:

- partial-month proration;
- fixed 30-day versus calendar-day versus working-day divisors;
- replacement-vehicle charging;
- downtime and off-road deductions;
- free-kilometre pooling;
- garage-mileage charging;
- fuel responsibility;
- accident and insurance-excess responsibility;
- early termination penalties;
- deposit utilization order;
- multiple-driver splits;
- holiday/OT treatment;
- foreign-currency treatment;
- tax calculation order in every scenario;
- one Running Chart split across multiple agreements.

These must remain explicit business-decision items. They should not be guessed or hidden behind speculative defaults.

---

## 3. Evidence map by video

## 3.1 `1.mp4`

This is the strongest rental reference for the relationship between master data, agreements, invoices, owner settlements, adjustments, and reporting.

Representative evidence:

- **~02:00 — Vehicle Register**
  - Vehicle number, registered owner, registration/tax dates, fuel type, body type, engine/chassis, seating, vehicle type, lessor code, insurance expiry, revenue licence expiry.
- **~04:00 — Agreement Register — With Lessee**
  - Lessee, agreement number, vehicle, agreement dates, company/personal format, monthly/daily basis, maximum kilometres, excess kilometre rate, driver option, VAT/SVAT, Tax/GL mappings, security deposit.
- **~06:00 — Credit Invoice**
  - Agreement import, date range, total/excess kilometres, OT and night-outs, rental income, excess-KM income, refundable driver components, taxes, and total.
- **~08:00 — Running Chart Transaction menu**
  - Daily Running Chart, Replacement Running Chart, self-drive handover notes, and self-drive return notes.
- **~12:00 and ~24:00 — Agreement Register — With Vehicle Owners**
  - Owner agreement, vehicle, dates, monthly/daily basis, maximum kilometres, rental rate, excess-KM rate, driver option, payable mappings.
- **~16:00 — Daily customer invoice example**
  - Five hires/days, 661 total kilometres, 161 excess kilometres, Non-AC rate, per-excess-KM rate, VAT and total.
- **~20:00 — Lessee reports**
  - Customer ledger, agreement listings, invoice listings, outstanding age analysis, statements, debit/credit-note reports, and allocation reports.
- **~28:00 — Vehicle-owner reports**
  - Owner ledger, vehicle ledger, agreement reports, statements, payable listings, payment listings, unallocated transactions, and fuel/repair deductions.
- **~32:00 — Lessor Debit Note Allocation**
  - Vehicle-specific owner adjustment with lessor control account and credit GL detail.
- **~36:00 — Vehicle-wise owner Statement of Accounts**
  - Rental payable, settlement, repair deductions, and final balance.
- **~40:00 — payment/cheque print output**
  - Cheque/payment and reconciliation-related output.

## 3.2 `Recording 2026-06-21 132314.mp4`

This is the strongest reference for rental registers, master-data fields, multiple agreement variations, Running Chart/report output, customer receipts, owner processing, and security/accounting menus.

Representative evidence:

- **~02:00 — Lessee Register**
  - Address/contact, credit limit, opening balance, VAT/SVAT status, driver salary, working hours, normal/double/triple OT, and night-out rates.
- **~04:00 — Lessee Agreement**
  - Company/personal, monthly/daily, VAT/SVAT, SSCL, GL codes, and security deposit.
- **~06:00 onward — invoice and agreement processing**
  - Different agreement/rate structures and vehicle/customer combinations.
- **~12:00 to ~18:00 — Running Chart and operational forms**
  - Usage and agreement-driven calculations.
- **~20:00 to ~28:00 — report and invoice previews**
  - Date, mileage, ON/OFF, overtime, totals, and operational summaries.
- **~30:00 onward — receipt/payment and owner processing**
  - Customer receipts, allocation-style forms, owner payment/payable processing, and statements.
- **Final section — Tax invoice and report output**
  - Financial document generation and print workflows.

## 3.3 `2.mp4`

This video is strongest for menu inventory and the breadth of transactions/reports.

Representative evidence:

- **Opening — Lessor transactions**
  - Cash payment, petty-cash payment, cheque payment, cash/cheque receipts, debit note, credit note, Payment Payable Processing, and fuel/repair debit note.
- **~01:00 — master/register menu**
  - Password Register, Company Register, Cost Centre, account groups/notes, GL accounts, Payee, Lessee, Lessor for leasing companies, Lessor for vehicle owners, Vehicle, Driver, Month, and all agreement registers.
- **~02:00 — Lessee transactions**
  - Cash payment, petty-cash payment, cheque payment, cash/cheque receipt, debit note, credit note, invoice, and miscellaneous invoice.
- **~03:00 — report menu**
  - Lessor and lessee ledgers, agreement listings, statements, payable/invoice listings, unallocated transactions, and repair/fuel deductions.
- **~05:00 — Lessee ledger report**
  - Agreement/vehicle references, invoices, receipts, debit/credit movement, and running balance.

The later part is mostly static or low-information. Its business value is concentrated in the first several minutes and the menu/report inventory.

## 3.4 `ScreenVideo_03-04-2026_18-02-52.mp4`

This is primarily a workshop/auto-care video, not a direct rental workflow. It is relevant to Vehicle Rental only where the same vehicle and accounting architecture must integrate with maintenance availability.

Directly visible supporting concepts include:

- Vehicle Register and service-reminder fields.
- Job invoice.
- Labour charges.
- Debtor/customer job invoice.
- Payee and mechanic registers.
- Workshop operational records.

The valid rental conclusion is limited but important:

> A vehicle under active workshop maintenance, breakdown, inspection failure, or off-road status must not remain freely allocatable for rental. Vehicle Service and Vehicle Rental require a shared availability contract without moving one module’s business logic into the other.

---

## 4. Reconstructed end-to-end Vehicle Rental lifecycle

```text
Company / Organization / Finance / Tax Setup
                    ↓
       Party and Vehicle Master Setup
                    ↓
      ┌─────────────┴─────────────┐
      │                           │
Lessor / Owner                Lessee / Customer
      │                           │
Lessor Agreement             Lessee Agreement
      │                           │
Lessor Vehicle Allocation    Lessee Vehicle Allocation
      └─────────────┬─────────────┘
                    ↓
 Reservation / Handover / Custody / Driver Assignment
                    ↓
       Daily or Replacement Running Chart
                    ↓
        Submit → Verify → Approve → Finalize
                    ↓
      ┌─────────────┴────────────────┐
      │                              │
Customer Revenue Context       Owner Cost Context
      │                              │
Lessee Calculation Run         Lessor Calculation Run
      │                              │
Customer Invoice               Owner Payable Settlement
      │                              │
Receipt / Allocation           Payment / Allocation
      └─────────────┬────────────────┘
                    ↓
 Debit/Credit Adjustments, Deposit, Refund, Cheque Lifecycle
                    ↓
        Tax Snapshot + Balanced Finance Journal
                    ↓
     Bank Reconciliation, Reports, Audit and Reversal
```

---

## 5. Domain audit

## 5.1 Party model

### Observed

The system treats these as separate register families:

- Lessee.
- Lessor — vehicle owner.
- Lessor — leasing company.
- Payee.
- Driver.
- General-ledger party/account records.

### Problem

Vehicle-owner and leasing-company rental flows are duplicated even though their core behavior is the same: they provide a vehicle or financing interest and participate in a payable/settlement subledger.

### Correct foundation

Use a shared party identity and role/policy model:

```text
Party
 ├── Customer / Lessee role
 ├── Lessor role
 │    ├── Individual owner
 │    ├── Company owner
 │    └── Leasing company
 ├── Supplier / Payee role
 └── Driver / Employee relation where applicable
```

Do not merge distinct business rules blindly. Share identity, addresses, contacts, Tax identity, bank details, and audit history while keeping role-specific services and policies in their owning modules.

## 5.2 Vehicle master and ownership

### Observed

Vehicle Register contains:

- registration number;
- registered owner;
- registration/tax dates;
- transfer date;
- fuel and body type;
- engine/chassis;
- dimensions and seating;
- vehicle type;
- lessor code;
- revenue-licence expiry;
- insurance expiry.

### Gaps

- Ownership appears directly stored/selected without a clearly demonstrated effective-dated ownership history.
- Vehicle availability is not visibly derived from rental, workshop, accident, document-expiry, and off-road timelines.
- Same registration may be exposed to invalid overlapping agreements/allocations.
- Insurance/revenue-licence expiration appears informational; enforcement is not proven.
- Vehicle status history, custody history, and replacement lineage are not clearly authoritative.

### Required design

- `vehicle_ownership_periods`
- `vehicle_availability_periods`
- `vehicle_document_validities`
- `rental_vehicle_allocations`
- `rental_custody_events`
- `rental_vehicle_replacements`
- `vehicle_status_histories`

Every timeline write must validate overlap, scope, and effective date.

## 5.3 Lessee agreement

### Observed terms

- Agreement number and date.
- Executing/start/end dates.
- Agreement active/close flags.
- Customer and vehicle.
- Company/personal format.
- Monthly or daily basis.
- Maximum/included kilometres.
- Rate for maximum kilometres.
- Rate for excess kilometres.
- With-driver option.
- Driver salary and OT/night-out components.
- Non-AC, Front-AC, Dual-AC variants.
- Invoice type.
- VAT/SVAT/SSCL.
- Rental and excess-KM income accounts.
- Parking/other recovery account.
- Security deposit.
- Identity/licence fields for personal agreements.

### Gaps

- Vehicle is hard-bound directly to the agreement.
- Agreement approval/execution evidence is weak.
- Rate/version history is not visible.
- Direct editing can change historical commercial meaning unless backend controls exist.
- Agreement status is checkbox-style rather than a governed state machine.
- Overlap, extension, suspension, termination, and renewal behavior is unclear.
- No clear evidence of an immutable executed document snapshot.
- GL and Tax mappings are embedded in the agreement form, creating duplication and configuration drift.

### Recommended lifecycle

```text
Draft
 → Pending Approval
 → Approved
 → Executed
 → Active
 → Suspended
 → Expired
 → Terminated
 → Closed
```

Activation must require an executed date, validated legal context, approved rate version, and valid agreement period. Printable clauses may be optional according to business policy, but the legal snapshot must be immutable after activation.

## 5.4 Lessor agreement

### Observed terms

- Lessor/owner.
- Vehicle.
- Agreement number and dates.
- Monthly/daily basis.
- Maximum kilometres.
- Base rental payable.
- Excess-KM payable.
- With-driver option.
- Reimbursement/payable accounts.
- Tax applicability.
- Parking and other payable mappings.
- Security and identity-style fields in some forms.

### Important business rule

The owner rate and customer rate are independent. The customer may be billed one rate while the owner is paid another rate.

### Gaps

- Direct vehicle hard-binding.
- Owner agreement and vehicle ownership are not clearly separated.
- No effective-dated version/freeze of owner rates.
- No formal suspension/termination/renewal state.
- Fuel/repair responsibility is not modeled explicitly as an agreement policy.
- No clear distinction between self-billed settlement and supplier-issued invoice.
- Leasing-company and vehicle-owner agreement flows are duplicated.

### Correct meaning

The legacy “lessor invoice” is better modeled as:

> **Owner Payable Voucher / Lessor Settlement / Self-Billed Settlement**

It should not be treated as a customer sales invoice.

## 5.5 Vehicle allocation

### Strongest confirmed gap

A separate, controlled effective-dated lessor or lessee allocation workflow is not clearly demonstrated. The vehicle is selected inside agreements and Running Charts.

This is insufficient for:

- vehicle replacement;
- overlapping customer allocations;
- ownership changes;
- temporary off-road periods;
- workshop downtime;
- early vehicle return;
- vehicle swap;
- proof of which vehicle served which customer for which period;
- handover/return condition;
- custody and responsibility;
- agreement without a permanently fixed vehicle.

### Required allocation records

#### Lessor allocation

- lessor agreement;
- vehicle;
- effective start/end;
- ownership relationship;
- delivery and return mileage;
- document-validity checks;
- condition and custody;
- original/replacement linkage;
- status;
- row version.

#### Lessee allocation

- lessee agreement;
- vehicle;
- reservation;
- effective start/end;
- handover and return time;
- handover and return mileage;
- fuel level;
- customer/driver/custodian;
- branch/location;
- replacement reason;
- inspection/checklists;
- security deposit reference;
- status;
- row version.

### Allocation lifecycle

```text
Reserved
 → Confirmed
 → Handed Over
 → Active
 → Extended / Replaced
 → Returned
 → Closed
 → Cancelled
```

### Mandatory controls

- Same vehicle cannot have overlapping active customer allocations.
- Same vehicle cannot be allocated outside the lessor availability period.
- Allocation cannot exceed either agreement period.
- Off-road/workshop/accident/document-expired vehicles cannot be handed over.
- Replacement closes or suspends the original timeline and starts a traceable replacement timeline.
- Every write is version-checked and transactionally validated.

## 5.6 Handover, return, custody, and replacement

### Observed

The Running Chart menu includes:

- self-drive vehicle handover to lessee;
- self-drive vehicle return from lessee;
- self-drive vehicle handover from lessor;
- self-drive vehicle return to lessor;
- replacement Running Chart.

### Gap

The menu proves the business need, but not a clean custody state machine or common custody record. Handover/return may exist as isolated printable documents instead of authoritative timeline events.

### Correct design

Use append-only custody events:

```text
Prepared
 → Handed over by lessor
 → Received into fleet
 → Handed over to lessee
 → Returned by lessee
 → Inspected
 → Returned to lessor
```

Each event stores:

- actor;
- date/time;
- location;
- odometer;
- fuel;
- condition checklist;
- images/documents;
- signatures;
- exceptions;
- previous event;
- row version or event sequence.

Replacement must link:

- original allocation;
- replacement allocation;
- original vehicle;
- replacement vehicle;
- effective date/time;
- reason;
- charge-policy decision;
- Running Chart split.

## 5.7 Running Chart

### Observed data

The Daily Running Chart and invoice screens imply:

- operational date;
- vehicle;
- customer;
- owner;
- agreement references;
- start and finish dates/times;
- start and finish odometer;
- total kilometres;
- excess kilometres;
- normal/double/triple overtime;
- night-outs;
- days/hires;
- driver;
- AC mode;
- monthly/daily basis;
- replacement vehicle;
- garage/other charges;
- remarks.

### Correct business meaning

The Running Chart is the authoritative operational evidence. It is not merely an invoice line.

### Required lifecycle

```text
Draft
 → Submitted
 → Verified
 → Approved
 → Finalized
 → Reversed
```

### Required controls

- End odometer cannot be below start odometer.
- Odometer continuity must be checked against previous/next usage and custody events.
- Usage period must fit valid allocations and agreements.
- Vehicle and driver overlap must be prevented.
- Finalized physical facts cannot be edited.
- A reversal requires a reason and creates compensating history.
- Customer and owner facts are derived separately.
- Each finalized usage source is consumed at most once per side.
- Customer processing must not block owner processing.
- Owner processing must not block customer processing.
- Duplicate same-side consumption must be impossible.

### Critical risk

The worst failure is not just a wrong arithmetic formula. It is:

```text
wrong vehicle
+ wrong agreement version
+ wrong period
+ duplicated usage consumption
= wrong customer invoice and wrong owner payment
```

## 5.8 Customer billing

### Observed calculation components

```text
Base rental
+ Excess KM
+ Driver salary recovery
+ Normal OT
+ Double OT
+ Triple OT
+ Night-out
+ Parking / other recoveries
+ Miscellaneous charges
- Discount / credit
+ VAT / SSCL / other effective Tax
= Customer invoice total
```

The videos also show:

- monthly and daily invoices;
- Non-AC, Front-AC, Dual-AC rates;
- normal, by-hire, and by-log-transaction excess-KM options;
- Running Chart import;
- invoice processing;
- invoice print;
- miscellaneous invoice;
- vehicle/customer/agreement reports.

### Gaps

- Calculation policy is spread across agreement and invoice screens.
- Rate source and effective version are not obvious.
- Tax and GL mappings are duplicated in commercial forms.
- Posted invoices appear to retain edit/delete-style controls.
- No clear posted-invoice reversal coordinator is shown.
- No visible idempotency/source-consumption key.
- Customer credit-limit and overdue-blocking enforcement is not proven.
- Calculation preview, explanation, and source traceability are weak.
- Invoice lines may not preserve a complete agreement/rate/usage snapshot.

### Required invoice snapshot

- agreement ID and version;
- allocation ID;
- usage/fact IDs;
- calculation-run ID;
- rate source;
- included kilometres;
- actual kilometres;
- excess kilometres;
- AC mode;
- driver/OT/night-out facts;
- Tax version;
- rounding policy;
- source fingerprint;
- posting profile;
- source allocations.

## 5.9 Owner settlement

### Observed calculation behavior

The owner statement visibly combines:

```text
Rental and other payable
- Settlement/payment
- Repair deductions
= Final owner balance
```

Other screens and menus show:

- owner payable processing;
- cash/petty-cash/cheque payment;
- owner receipts;
- debit/credit notes;
- fuel/repair deductions;
- owner allocation;
- vehicle-wise statements;
- unallocated transaction reports.

### Recommended calculation

```text
Base rental payable
+ Excess-KM payable
+ Driver reimbursement
+ OT reimbursement
+ Night-out reimbursement
+ Approved parking/other reimbursements
- Fuel deductions
- Repair deductions
- Advances
- Other debit notes
± Credit/debit adjustments
= Net owner payable
```

### Gaps

- Settlement may be called an invoice even though it is self-billed payable.
- Fuel/repair responsibility and approval are unclear.
- Deductions may be entered as generic debit notes without source evidence.
- No immutable calculation snapshot is proven.
- Duplicate Running Chart cost consumption is not visibly prevented.
- Reversal and re-settlement coordination are unclear.
- Owner payment allocation concurrency is not proven.
- Leasing-company and owner settlement implementations may duplicate logic.

## 5.10 Deposits, advances, refunds, debit notes, and credit notes

### Observed

Security deposit fields and bidirectional receipts/payments/adjustments are present.

### Gaps

- Deposit status lifecycle is not demonstrated.
- Deposit receipt identity is not clearly separated from customer receipt.
- Application, refund, forfeiture, reversal, and remaining balance are unclear.
- Customer refund and owner receipt scenarios are not documented.
- Debit and credit notes may be generic free-form accounting adjustments.
- Source document, reason, evidence, and approval are not mandatory in the visible UI.
- A debit/credit note may bypass the original commercial context.

### Required deposit lifecycle

```text
Required
 → Partially Received
 → Received
 → Partially Applied
 → Applied
 → Partially Refunded
 → Refunded
 → Forfeited
 → Reversed
```

Every movement must preserve original amount, movement amount, balance, source, reason, actor, and journal identity.

## 5.11 Receipts, payments, and allocations

### Observed

Both customer and owner sides support multiple directions:

- cash;
- petty cash;
- cheque;
- receipt;
- payment;
- debit note;
- credit note;
- allocation;
- unallocated reports.

### Gap

Direction alone is not enough to determine accounting meaning. Customer receipt, customer advance, security deposit, customer refund, supplier/owner advance, owner payment, and refund all require different semantic posting.

### Required controls

- Allocation cannot exceed open balance.
- Payment cannot be allocated to wrong party, currency, or reversed document.
- Same open balance cannot be consumed by concurrent users.
- Initial payment journal and later allocation reclassification must not double-post.
- Unallocated receipts remain explicit advances/liabilities.
- Allocation reversal coordinates invoice balance, payment balance, Tax/Finance effects, and audit.
- Payment reversal coordinates all active allocations.
- Refund must reference the original economic transaction.
- Refund-of-refund should be rejected.
- Cash/cheque lifecycle must be explicit.

## 5.12 Cheque and bank reconciliation

### Observed

- bank account;
- cheque number/date;
- payee;
- cash/cheque document;
- cheque payment print;
- edit cheque payment for reconciliation;
- realization/reconciliation reports.

### Risks

- “Edit cheque payment” after posting may mutate historical payment meaning.
- Cheque status is not visibly governed.
- Bounced/stopped/cancelled behavior is unclear.
- Reconciliation may modify a payment instead of creating a bank-status event.
- Bank fees, dishonour charges, and replacement cheques are not demonstrated.

### Recommended cheque lifecycle

```text
Prepared
 → Issued / Deposited
 → Presented
 → Cleared
 → Bounced / Stopped / Cancelled
 → Replaced or Reversed
```

Bank reconciliation should record reconciliation events, not rewrite original commercial transactions.

## 5.13 Tax and GL integration

### Observed

Commercial screens contain VAT, SVAT, SSCL, Tax account codes, rental income accounts, excess-KM accounts, payable accounts, and parking/other mappings.

### Problems

- GL configuration is mixed into agreements and invoice forms.
- Raw account codes are exposed to business users.
- Mapping duplication can cause drift.
- The presence of double-entry and source-mismatch repair procedures indicates that source and ledger can diverge.
- Tax snapshots and posting coordination are not visible.
- Reversal coordination is unclear.
- Closed accounting periods are not evidenced.

### Correct ownership

```text
Vehicle Rental
  owns operational and commercial facts

Invoice / Payment
  owns financial-document lifecycle, balances, and allocations

Tax
  owns Tax rules, snapshots, transactions, and Tax reversal facts

Finance
  owns posting profiles, accounts, periods, journals, ledger, and journal reversal
```

### Atomic posting invariant

For every posted rental invoice or owner settlement:

```text
Source finalized
+ Financial document posted
+ Tax posted
+ Balanced Finance journal posted
= one atomic database transaction
```

If any step fails, all steps roll back.

## 5.14 Reports and procedures

### Observed reports

- customer and owner ledgers;
- vehicle ledgers;
- agreement listings;
- invoice/payable listings;
- vehicle-wise statements;
- outstanding balances;
- ageing;
- debit/credit notes;
- unallocated transactions;
- fuel/repair deductions;
- Running Chart/log-sheet reports;
- General Ledger;
- bank reconciliation;
- Tax reports.

### Critical interpretation

The breadth of “error check” and mismatch procedures suggests the legacy architecture tolerates inconsistent states and later finds or repairs them.

A maintainable system should use reports to explain valid data, not to compensate for preventable write defects.

### Required reconciliation invariants

- Every posted source has exactly one expected active journal.
- Journal totals equal source totals.
- Tax snapshot totals equal posted Tax totals.
- Invoice balance equals total minus active allocations/credits.
- Owner payable balance equals payable minus active payments/credits plus valid adjustments.
- Running Chart consumption totals equal calculation-source allocations.
- No active source references a reversed document.
- No tenant or organization scope mismatch exists.
- Every balance report can drill down to immutable source events.

## 5.15 Security and permissions

### Observed concerns

- Password Register.
- Numeric or opaque user-level approach.
- Add/Edit/Delete buttons across financial screens.
- No clear role/action separation.
- No clear approval evidence.
- Raw accounting structures are exposed to general transaction users.

### Required permission model

Named permissions, not numeric levels:

- rental reservations view/manage;
- agreements view/create/approve/execute/terminate;
- allocations view/manage/handover/return/replace;
- Running Charts create/submit/verify/approve/reverse;
- customer calculations run/review/finalize;
- owner calculations run/review/finalize;
- invoices create/approve/post/reverse;
- settlements create/approve/post/reverse;
- receipts/payments create/approve/post/reverse;
- allocation manage/reverse;
- deposit receive/apply/refund/forfeit/reverse;
- Finance configure/post/reverse;
- reports view/export;
- audit view.

Segregation of duties should prevent one actor from creating, approving, posting, paying, and reconciling the same high-risk transaction without policy approval.

## 5.16 UI and UX

### Legacy weaknesses

- Raw IDs and GL codes.
- Dense forms.
- Multiple responsibilities on one screen.
- Checkbox-style lifecycle.
- Add/Edit/Delete controls without clear state-based governance.
- Little explanation of calculation impact.
- No clear source preview before approval/posting.
- Unclear disabled-button reasons.
- Search and selection appear code-driven.

### Required UI design

- Search by customer name, owner name, vehicle registration, agreement number, and human-readable account name.
- Never ask users to type foreign keys.
- Guided step-based flows:
  - agreement;
  - allocation;
  - handover;
  - Running Chart;
  - calculation review;
  - invoice/settlement;
  - receipt/payment.
- Show only essential fields in the primary task.
- Place history, audit, and advanced accounting mappings in separate tabs/sections.
- Display calculation source and formula.
- Show version/conflict messages.
- Preview irreversible posting/reversal impact.
- Explain why a vehicle is unavailable.
- Show agreement, allocation, Running Chart, invoice, settlement, and payment as one navigable audit timeline.

## 5.17 Workshop and maintenance integration

The workshop video proves a separate Vehicle Service domain with job, labour, parts, and service-reminder data.

The correct boundary is:

- Vehicle Service owns workshop jobs, inspections, parts, labour, maintenance status, and off-road facts.
- Vehicle Rental owns reservations, allocations, custody, usage, and rental commercial calculations.
- Vehicle module or a shared availability contract owns the resolved availability timeline.

Do not place workshop rules inside Vehicle Rental or rental rules inside Vehicle Service.

A vehicle must be unavailable for handover when it has an overlapping status such as:

- active workshop job that blocks use;
- breakdown;
- accident;
- inspection failure;
- off-road status;
- expired required documents;
- active rental allocation;
- pending return inspection.

---

## 6. Comprehensive issue summary

### P0 — Critical

1. No clearly demonstrated authoritative effective-dated vehicle allocation lifecycle.
2. Agreement directly hard-binds vehicle and mixes commercial terms with operational assignment.
3. Same-vehicle overlap prevention is not proven.
4. Agreement and rate versioning/frozen snapshots are not proven.
5. Running Chart finalization and same-side duplicate-consumption protection are not proven.
6. Posted invoices, payments, vouchers, and adjustments expose edit/delete-style actions.
7. Source, Tax, and General Ledger can apparently diverge, requiring mismatch procedures.
8. Governed reversal across source, invoice/payment, Tax, Finance, and consumption is unclear.
9. Atomicity and concurrency protection for allocations, invoice creation, and payment allocation are not proven.
10. Numeric/legacy security model and Password Register are unsuitable for a modern ERP.

### P1 — High

11. Vehicle-owner and leasing-company domains are duplicated.
12. Ownership history is not clearly effective-dated.
13. Vehicle availability does not visibly integrate all blocking statuses.
14. Insurance/revenue-licence dates may not be enforced.
15. Handover/return appears document-oriented rather than event-authoritative.
16. Replacement vehicle charging and Running Chart splitting are unclear.
17. Odometer continuity is not proven.
18. Driver overlap and multiple-driver rules are unclear.
19. Agreement approval/execution evidence is weak.
20. Agreement termination, renewal, suspension, and extension are unclear.
21. GL and Tax mapping is duplicated in commercial forms.
22. Raw IDs/account codes are primary UI inputs.
23. Customer credit-limit and overdue policy enforcement is not proven.
24. Owner fuel/repair deduction approval and evidence are unclear.
25. Security-deposit lifecycle is incomplete/unclear.
26. Debit/credit notes may bypass original commercial context.
27. Refund identity and refund-of-refund rules are unclear.
28. Cheque bounce/stop/cancel/replacement lifecycle is unclear.
29. Accounting-period close and backdated-posting control are not evidenced.
30. Reports may not be authoritative if source-to-GL completeness is not enforced.

### P2 — Medium / maintainability

31. Dense screens mix business, Tax, and accounting concerns.
32. Calculation logic appears duplicated across agreements, invoices, and payables.
33. Hardcoded or manually selected GL values create configuration drift.
34. Lifecycle is represented by checkboxes/buttons rather than explicit state machines.
35. Audit history and actor/reason evidence are not prominent.
36. Attachments, signatures, and handover-condition evidence are incomplete.
37. Search/filter UX is code-heavy.
38. Report drill-down to source facts is unclear.
39. Idempotency for repeated create/post commands is not proven.
40. Tenant/organization isolation is not visible in the legacy UI.
41. Data-retention and reversal explanation are unclear.
42. User guidance for irreversible actions is weak.
43. Static menu duplication increases maintenance risk.
44. Workshop/rental availability integration is not demonstrated.
45. Business decisions that are not proven may be hidden in operator knowledge rather than explicit configuration.

---

## 7. Recommended target architecture

```text
Vehicle
 ├── Registry and document validity
 ├── Ownership periods
 └── Availability timeline

Party
 ├── Lessee role
 ├── Lessor role
 ├── Driver relation
 └── Contacts / addresses / Tax identity

Vehicle Rental
 ├── Reservations
 ├── Lessee agreements and rate versions
 ├── Lessor agreements and rate versions
 ├── Lessor vehicle allocations
 ├── Lessee vehicle allocations
 ├── Custody events
 ├── Driver assignments
 ├── Vehicle replacements
 ├── Running Charts
 ├── Customer facts
 ├── Owner facts
 ├── Customer calculation runs
 ├── Owner calculation runs
 ├── Deposits
 ├── Rental expenses / recoveries
 └── Source-consumption records

Invoice
 ├── Customer invoice
 ├── Owner payable/self-billed settlement
 ├── Lifecycle
 ├── Balances
 ├── Source allocations
 └── Reversal coordination

Payment
 ├── Receipts
 ├── Payments
 ├── Advances
 ├── Deposits
 ├── Refunds
 ├── Allocations
 └── Reversals

Tax
 ├── Effective-dated rules
 ├── Snapshots
 ├── Tax transactions
 └── Tax reversal

Finance
 ├── Accounts and account roles
 ├── Effective-dated posting profiles
 ├── Accounting periods
 ├── Journals
 ├── Ledger
 ├── Bank reconciliation
 └── Journal reversal

Vehicle Service
 ├── Jobs
 ├── Inspections
 ├── Parts / labour
 └── Availability-blocking events
```

---

## 8. Concurrency and transaction design

Assume multiple users or integrations can modify the same data at overlapping times.

### Required order for Running Chart creation/finalization

```text
Lock vehicle timeline
→ Lock relevant allocations in deterministic ID order
→ Lock driver timeline
→ Lock agreement/rate versions
→ Validate usage and odometer continuity
→ Create/finalize physical usage
→ Create customer and owner fact snapshots
→ Commit
```

### Required order for customer invoice generation

```text
Lock finalized customer facts
→ Lock source-consumption rows
→ Reject already-consumed sources
→ Create calculation snapshot
→ Create invoice and source allocations
→ Create Tax snapshot/transactions
→ Create and post balanced Finance journal
→ Mark customer facts consumed
→ Commit
```

### Required order for owner settlement

```text
Lock finalized owner facts
→ Lock approved expenses/deductions
→ Reject already-consumed sources
→ Create owner calculation snapshot
→ Create payable/settlement and source allocations
→ Create Tax snapshot where applicable
→ Create and post balanced Finance journal
→ Mark owner facts consumed
→ Commit
```

### Required order for receipt/payment allocation

```text
Lock payment
→ Lock open documents in deterministic order
→ Validate party/currency/scope/status
→ Validate remaining balances
→ Create allocation
→ Create reclassification journal where required
→ Update balances
→ Commit
```

Every mutable aggregate should use `row_version` and require `expected_version`.

---

## 9. Example accounting entries

### Customer invoice

```text
Dr Accounts Receivable — Lessee
    Cr Rental Income
    Cr Excess-KM Income
    Cr Driver/OT/Night-out Recovery Income
    Cr Other Recovery Income
    Cr Output Tax
```

### Owner settlement

```text
Dr Vehicle Rental Expense
Dr Driver/OT/Night-out Reimbursement Expense
Dr Approved Other Rental Expense
    Cr Lessor / Owner Payable
```

### Owner deduction

```text
Dr Lessor / Owner Payable
    Cr Fuel / Repair Recovery or appropriate expense-recovery account
```

### Customer receipt allocated to invoice

```text
Dr Cash / Bank
    Cr Accounts Receivable
```

### Unapplied customer advance

```text
Dr Cash / Bank
    Cr Customer Advances
```

### Rental security deposit

```text
Dr Cash / Bank
    Cr Rental Security Deposits
```

### Owner payment

```text
Dr Lessor / Owner Payable
    Cr Cash / Bank
```

The exact account catalogue and Tax treatment must be configured by semantic posting profiles, not hardcoded in Vehicle Rental forms.

---

## 10. Minimum acceptance criteria

### Agreements

- Cannot activate outside valid dates.
- Cannot activate without execution/legal evidence required by policy.
- Rate versions are immutable after effective use.
- Historical calculations remain unchanged after later agreement changes.
- Same party/period conflicts are detected according to approved policy.

### Allocations

- Same vehicle cannot overlap active allocations.
- Allocation must fit agreement and ownership periods.
- Document-expired/off-road/workshop-blocked vehicle cannot be handed over.
- Replacement creates a linked timeline.
- Every update is version-checked.

### Running Charts

- Must reference valid allocations.
- Must preserve odometer continuity.
- Must prevent vehicle and driver overlap.
- Must require review before approval.
- Finalized facts are immutable.
- Customer and owner consumption are independent.
- Same-side duplicate consumption is rejected.

### Customer billing

- Calculation is reproducible from frozen facts.
- Every line shows source and rate.
- Posted invoice cannot be edited or deleted.
- Reversal restores source availability through governed compensating records.
- Tax and Finance posting are atomic.
- Credit/limit policy is enforced in backend.

### Owner settlement

- Settlement uses owner agreement, not customer rate.
- Fuel/repair deductions require approved evidence.
- Duplicate source consumption is prevented.
- Posted settlement is immutable.
- Payment allocation is concurrency-safe.
- Reversal is coordinated.

### Deposits and payments

- Deposit, advance, receipt, payment, and refund have distinct semantic identities.
- Allocation cannot over-consume balances.
- Refund references original transaction.
- Reversal coordinates allocation and Finance.
- Cheque lifecycle is explicit.

### Finance and reporting

- Every posted source has the expected active balanced journal.
- Closed periods block posting and reversal unless governed reopening exists.
- Reports reconcile exactly to immutable source data.
- Users can drill from report to source and journal.
- No source-to-GL mismatch repair should be required for normal operation.

---

## 11. Prioritized remediation plan

### Phase 0 — Confirm business decisions

Confirm only the rules that cannot be safely inferred:

- proration;
- replacement charging;
- downtime;
- kilometre pooling;
- garage mileage;
- fuel/repair responsibility;
- accident/insurance excess;
- termination penalties;
- deposit utilization;
- Tax order;
- multiple drivers.

### Phase 1 — Allocation and agreement integrity

- Separate agreement from allocation.
- Add effective-dated lessor/lessee allocations.
- Add ownership and availability timelines.
- Add agreement/rate versions and approval.
- Add row versions and deterministic locks.

### Phase 2 — Running Chart integrity

- Add explicit lifecycle.
- Add review-before-approval.
- Add odometer/overlap validation.
- Add customer and owner fact snapshots.
- Add separate consumption protection.

### Phase 3 — Customer and owner calculations

- Centralize calculation policies.
- Freeze calculation snapshots.
- Add explainable calculation preview.
- Add owner expenses/deductions with evidence and approval.

### Phase 4 — Financial lifecycle

- Atomic Invoice + Tax + Finance posting.
- Owner settlement posting.
- Semantic receipt/payment/deposit/refund policy.
- Allocation/reclassification journals.
- Governed reversals.
- Accounting periods.
- Cheque lifecycle and bank reconciliation.

### Phase 5 — UI and permissions

- Human-readable selectors.
- Focused workflows.
- Clear state and action guidance.
- Permission/action matrix.
- Audit timeline.
- Reversal and posting previews.

### Phase 6 — Reports and verification

- Source-to-ledger invariant tests.
- Vehicle/customer/owner profitability.
- Running Chart and allocation reconciliation.
- SQLite and MySQL suites.
- Real concurrent MySQL tests.
- Cross-module API workflows.
- Browser E2E.
- Business UAT.

---

## 12. Final conclusion

The legacy system should be treated as a **business-rule reference**, not an architecture template.

Preserve:

- separate lessor and lessee agreements;
- detailed rates;
- Running Chart evidence;
- independent customer and owner calculations;
- receipts, payments, adjustments, allocations, Tax, General Ledger, and reports.

Replace:

- direct vehicle binding;
- repair-oriented mismatch procedures;
- mutable posted documents;
- duplicated lessor modules;
- raw ID/account-code UI;
- checkbox lifecycle;
- numeric user levels;
- duplicated calculation and GL logic.

The correct AutoERP foundation is:

> **allocation-first, agreement-versioned, Running-Chart-driven, source-consumption-safe, audit-immutable, concurrency-aware, and atomically accounting-integrated.**

This design resolves the root cause instead of preserving legacy mistakes through compatibility workarounds.
