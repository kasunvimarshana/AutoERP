# AutoERP Vehicle Rental — Complete AI Agent Domain Specification

> **Critical context for the receiving AI agent**
>
> You do **not** have access to the original recordings. The recordings were privately reviewed before this document was produced. Therefore, treat this file as the complete replacement for the videos and do not assume that another agent can inspect them.
>
> This is not merely a summary. It is a consolidated domain specification containing:
>
> - visible legacy screens, fields, menus, printed documents, and reports;
> - reconstructed end-to-end business workflows;
> - accounting meaning and financial effects;
> - data-integrity and state-transition rules;
> - recommended AutoERP module boundaries;
> - implementation and audit guidance;
> - explicit unknowns that must not be invented.
>
> Read the entire document before auditing or changing Vehicle Rental.

---

## Source Coverage

The knowledge in this document was derived from complete review of:

| Recording | Duration | Primary coverage |
|---|---:|---|
| `1.mp4` | 40:50 | Customer/lessee registry, customer agreement, daily running chart, customer invoice, owner payable, owner deductions, cheque/payment and printed outputs |
| `2.mp4` | 09:38 | General-ledger transaction groups, customer/owner reports, ageing, statements, unallocated balances, vehicle documents, lease installments and profitability |
| `Recording 2026-06-21 132314.mp4` | 41:58 | Agreement variations, custody handovers/returns, replacement running chart, receipt allocation, owner statements, taxes, WHT, reports and broader workflow confirmation |

Total reviewed duration: approximately **92 minutes**.

---

## Evidence Classification

Every requirement should be interpreted using these labels:

- **Observed** — directly visible in the recordings.
- **Derived** — necessary for the observed workflow to remain correct, consistent and auditable.
- **Recommended** — modern AutoERP design that preserves business behavior without copying legacy technical debt.
- **Open Question** — not sufficiently determined by the recordings; do not hardcode an answer.

---

## Non-Negotiable Domain Understanding

The Vehicle Rental domain contains three separate commercial relationships:

```text
Customer / Lessee
→ Receivable side
→ Rental charges, invoices, receipts and customer statements

Vehicle Owner / Lessor
→ Payable side
→ Usage-based owner costs, deductions, payments and owner statements

Leasing Company
→ Vehicle finance side
→ Installment schedules, due payments and lease reports
```

The same approved running chart can feed both customer billing and owner payable calculation, but the two financial sides must remain independent.

```text
One authoritative usage stream
├── Customer revenue stream
└── Vehicle-owner cost stream
```

Never assume:

```text
Customer charge = Owner payable
```

---

## Video-to-Domain Traceability

### `1.mp4`

Directly establishes:

- lessee/customer registration;
- credit limit and opening balance;
- VAT/SVAT-related customer data;
- customer agreement terms;
- monthly/daily rental basis;
- company/personal agreement;
- with-driver/self-drive behavior;
- maximum/included kilometres;
- excess-kilometre rates;
- driver salary and working-hour rules;
- normal/double/triple overtime;
- night-out;
- rental/recovery accounting mappings;
- daily running chart;
- customer and owner agreement references on usage;
- mileage/time carry-forward behavior;
- customer credit invoice generated from running-chart data;
- separate owner payable calculation;
- fuel/repair deductions;
- cheque/payment and printed documents.

### `2.mp4`

Directly establishes:

- broader GL transaction coverage;
- cash, petty-cash, cheque and bank transactions;
- bank deposits and reconciliation;
- customer and owner debit/credit adjustments;
- customer and owner ledger reports;
- vehicle-wise statements;
- outstanding and ageing;
- unallocated receipts/payments;
- driver overtime reporting;
- licence and insurance expiry;
- lease-installment reports;
- agreement status;
- vehicle profitability.

### `Recording 2026-06-21 132314.mp4`

Directly establishes or confirms:

- separate agreements with customers, vehicle owners and leasing companies;
- vehicle custody handover/return documents;
- self-drive handover and return;
- replacement running chart;
- detailed receipt allocation;
- owner/vehicle statement structure;
- WHT and tax-related settlement behavior;
- vehicle/rate categories;
- running-chart reports;
- agreement-wise and vehicle-wise reporting;
- legacy user/password-register behavior that must not be copied.

---

## Agent Operating Rule

When the current implementation conflicts with this file:

1. Verify whether current behavior is an intentional newer requirement.
2. Preserve valid newer behavior.
3. Fix clear integrity, accounting, authorization, or workflow errors.
4. Do not copy legacy UI or legacy database structure literally.
5. Do not invent answers for items marked as open questions.
6. Keep the implementation simple, readable and maintainable.
7. Reuse Invoice, Payment, Finance, Tax, Customer, Supplier, Vehicle and HR modules.
8. Fix backend problems in the backend.
9. Use transactions, locking, idempotency and database constraints where correctness requires them.
10. Add verification/tests for each corrected invariant.

---

# 1. How to Read This Document

Information is classified as follows:

- **Observed** — directly visible in the recordings.
- **Derived** — logically required for correctness based on the observed workflow.
- **Recommended** — preferred AutoERP implementation design.
- **Open Question** — requires domain-owner confirmation before production hardcoding.

When a rule is not confirmed, keep it configurable and auditable rather than inventing a fixed policy.

---

# 2. Executive Understanding

The demonstrated system is not a simple vehicle-rental CRUD module.

It is a complete rental operations and accounting subsystem containing:

- customer/lessee agreements;
- vehicle-owner/lessor agreements;
- leasing-company finance agreements;
- vehicle allocation and physical custody;
- daily running charts;
- customer revenue calculations;
- owner payable calculations;
- driver salary/overtime/night-out rules;
- fuel, repair, tax, and other adjustments;
- invoices, payables, receipts, payments, and allocations;
- statements, ageing, bank reconciliation, and profitability reports.

The central architecture is:

```text
Reservation
→ Customer Agreement
→ Vehicle Source Agreement
→ Vehicle Allocation
→ Vehicle Handover
→ Daily Running Chart
→ Replacement / Return when required

Approved Running Chart
├── Customer Revenue Calculation
│   → Customer Invoice
│   → Receipt Allocation
│
└── Vehicle Owner Cost Calculation
    → Owner Payable
    → Debit/Credit Adjustments
    → Payment Allocation

Approved Financial Documents
→ Finance Ledger
→ Statements / Ageing / Unallocated Balances / Profitability
```

## Canonical rule

> Keep one authoritative operational usage stream, but maintain separate customer-revenue and owner-cost streams with independent agreements, rates, calculations, taxes, documents, ledger postings, and settlements.

---

# 3. Core Parties and Roles

## 3.1 Customer / Lessee

The party receiving the vehicle.

Financial side:

- accounts receivable;
- customer agreement;
- customer invoice;
- debit/credit notes;
- receipts;
- customer outstanding, ageing, and statement.

## 3.2 Vehicle Owner / Lessor

A third party providing a vehicle to the rental company.

Financial side:

- accounts payable;
- owner agreement;
- usage-based owner payable;
- fuel/repair/service deductions;
- WHT and other adjustments;
- payments;
- owner and vehicle-wise statements.

## 3.3 Leasing Company

A finance provider connected to a vehicle lease or hire-purchase arrangement.

Financial side:

- finance agreement;
- installment schedule;
- due/overdue installments;
- lease payments;
- lease-expiry and installment-due reports.

This is not the same as a usage-based vehicle-owner payable.

## 3.4 Driver

A person assigned to an agreement/vehicle/usage period.

Driver-related values may include:

- base salary or reimbursement;
- weekday working hours;
- Saturday working hours;
- public-holiday working hours;
- normal overtime;
- double overtime;
- triple overtime;
- night-out.

## 3.5 Party-role principle

Party identity and party role must be separate.

One party may act as:

- customer;
- vehicle owner;
- supplier;
- leasing company;
- payee.

Do not hardcode one party to exactly one role.

---

# 4. Vehicle Source Types

At least three vehicle-source models are required.

## 4.1 Company-owned vehicle

- no third-party usage-based owner payable;
- internal vehicle expenses still affect profitability;
- may still have a leasing-company installment obligation.

## 4.2 Third-party owner vehicle

- requires owner agreement;
- generates usage-based owner payable;
- may have fuel/repair/service deductions;
- requires handover from and return to the owner.

## 4.3 Leased/financed vehicle

- operationally controlled by the company;
- requires finance agreement and installment schedule;
- installment payments are separate from owner rental payables.

Use explicit source and agreement relationships. Do not infer accounting behavior only from `vehicle.owner_id`.

---

# 5. Legacy Application Areas Observed

## 5.1 Registers

Observed registers include:

- company;
- cost centre;
- general ledger accounts;
- payees;
- customers/lessees;
- vehicle owners/lessors;
- leasing companies;
- vehicles;
- drivers;
- months;
- agreements with lessees;
- agreements with vehicle owners;
- agreements with leasing companies.

## 5.2 General-ledger transactions

Observed transactions include:

- cash payment vouchers;
- petty-cash payment vouchers;
- cheque payment vouchers;
- cash/cheque receipts;
- bank deposit slips;
- journal vouchers;
- vehicle-expense journals;
- bank debit/credit vouchers;
- bank reconciliation.

## 5.3 Rental transactions

Observed rental transaction groups include:

- customer/lessee transactions;
- vehicle-owner/lessor transactions;
- leasing-company transactions;
- daily running charts;
- replacement running charts;
- handover/return documents;
- invoices;
- owner payables;
- fuel/repair debit notes;
- receipts/payments and allocations.

---

# 6. Customer / Lessee Master

Observed or implied fields:

- customer code;
- name;
- address and contact details;
- credit limit;
- opening balance;
- VAT/SVAT information;
- active status;
- possible default driver/rental settings.

## Recommended ownership

Reusable identity, credit, and contacts belong to the Customer module.

Rental-specific defaults belong to:

- rental profile;
- rate plan;
- agreement template.

Do not store mutable agreement pricing permanently on the customer master.

---

# 7. Vehicle Owner / Lessor Master

Required fields/concepts:

- owner code;
- name and contacts;
- tax/WHT information;
- payment terms;
- bank/payment details;
- linked vehicles;
- active ownership periods;
- default owner-rental settings, if useful.

Ownership must be effective-date aware.

Where required, a vehicle may have only one current owner relationship, while historical ownership remains preserved.

---

# 8. Customer Rental Agreement

## 8.1 Identity and dates

Observed or required:

- agreement number;
- agreement date;
- execution date;
- start date;
- end date;
- customer;
- agreement status;
- vehicle or vehicle category;
- agreement-vehicle number/reference.

## 8.2 Commercial classification

Observed:

- company or personal;
- monthly or daily;
- with-driver or self-drive;
- vehicle/rate categories such as Non-AC, Front-AC, Dual-AC.

Recommended:

```text
Vehicle Category / Rental Class
→ Rate Plan
→ Agreement Rate Snapshot
```

Do not create permanent hardcoded columns for each vehicle feature/rate class.

## 8.3 Distance terms

Observed:

- included/maximum kilometres;
- maximum-kilometre rate;
- excess-kilometre rate;
- multiple excess-km calculation modes.

Modes observed:

```text
period / normal
per_hire
per_usage_log
```

Represent them as a controlled strategy/enum.

## 8.4 Driver terms

Observed:

- driver salary;
- weekday working hours;
- Saturday working hours;
- public-holiday working hours;
- normal OT rate;
- double OT rate;
- triple OT rate;
- night-out rate.

## 8.5 Tax and recovery terms

Observed:

- VAT/SVAT;
- SSCL;
- rental income;
- excess-km income;
- refundable driver salary;
- refundable driver OT;
- refundable driver night-out;
- parking and other recovery;
- security deposit;
- financial account mappings.

AutoERP should resolve accounting mappings through configuration, not expose raw GL codes in routine transaction forms.

## 8.6 Conditions

Support:

- structured terms;
- free-text notes;
- renewal/termination conditions.

Do not use fixed numbered condition columns.

---

# 9. Vehicle Owner Agreement

Must be independent from the customer agreement.

Required:

- owner;
- vehicle;
- agreement number;
- execution/start/end dates;
- monthly/daily/per-hire basis;
- included kilometres;
- excess-km payable rate;
- driver reimbursement rules;
- owner-side taxes;
- WHT;
- fuel/repair/service deduction rules;
- payment terms;
- status;
- renewal/termination.

## Critical rule

```text
Customer Invoice Rate ≠ Vehicle Owner Payable Rate
```

The same running chart can produce different:

- included-distance quantities;
- excess rates;
- driver rates;
- taxes;
- rounding;
- totals.

---

# 10. Leasing Company Agreement

Observed as a separate agreement type.

Recommended model:

```text
Vehicle Finance Agreement
├── financing party
├── vehicle
├── principal / financed amount
├── start and maturity dates
├── installment frequency
├── installment schedule
├── interest / fees
├── due / paid / overdue state
└── finance/ledger references
```

Do not model lease installments as owner rental charges.

---

# 11. Immutable Agreement Rate Snapshots

Historical transactions must not depend on mutable agreement fields.

Freeze separate snapshots for:

1. customer billing;
2. vehicle-owner payable;
3. driver reimbursement;
4. tax treatment;
5. currency/exchange-rate context;
6. excess-km strategy;
7. rounding/precision.

Each snapshot should retain:

- source agreement/version;
- effective period;
- billing basis;
- rental class;
- base rate;
- included distance;
- excess rate;
- driver/OT/night-out rates;
- taxes;
- currency;
- creation/finalization metadata.

Agreement edits should create a new version/effective snapshot rather than rewrite history.

---

# 12. Reservation and Agreement Conversion

Recommended lifecycle:

```text
Draft Reservation
→ Confirmed Reservation
→ Converted Agreement
→ Cancelled / Expired
```

Conversion must validate:

- customer;
- dates;
- vehicle/category;
- currency;
- organization unit;
- with-driver/self-drive;
- rate plan;
- deposit requirements.

Do not silently create an agreement that contradicts the reservation without an explicit override and audit note.

---

# 13. Agreement Vehicle Allocation

An agreement may use:

- one vehicle;
- multiple vehicles over time;
- replacement vehicles;
- vehicles from different owners.

Use an effective-dated allocation entity.

Required allocation fields:

- agreement;
- vehicle;
- customer;
- owner-cost context;
- start/end date-time;
- status;
- predecessor/successor replacement link;
- pickup/return references;
- rate snapshots;
- custody state.

## Invariants

- no overlapping active allocations for the same vehicle;
- allocation must be inside the valid agreement period unless explicitly extended;
- selected vehicle must be available and eligible;
- owner relationship must be valid for the allocation period;
- returned/replaced/closed allocations cannot receive normal new usage.

Concurrency-sensitive allocation must use transactions and locking.

---

# 14. Vehicle Custody

Canonical custody chain:

```text
Vehicle Owner
→ Rental Company
→ Customer
→ Rental Company
→ Vehicle Owner
```

## Required documents/events

- handover from owner to company;
- handover from company to customer;
- self-drive handover note;
- return from customer to company;
- self-drive return note;
- return from company to owner;
- pickup inspection;
- return inspection.

## Capture

- date/time;
- agreement/allocation;
- vehicle;
- odometer;
- fuel level;
- condition/damage;
- accessories;
- documents/keys;
- handed-over by;
- received by;
- driver, if relevant;
- notes;
- photos/signatures;
- previous/resulting custody state.

## Rules

- return cannot precede handover;
- usage cannot occur before customer handover;
- owner return cannot occur while customer custody is open;
- vehicle must not become available while an active allocation/custody state exists;
- finalized custody documents require reversal/amendment rather than hard deletion.

---

# 15. Vehicle Replacement

Replacement must be one atomic business transaction:

```text
Validate replacement vehicle
→ Record old vehicle return/inspection
→ Close old allocation
→ Create replacement link
→ Open new allocation
→ Record new vehicle handover/inspection
→ Continue agreement usage context
```

## Rules

- replacement vehicle must be available;
- old/new periods must be valid;
- agreement continuity must be preserved;
- customer billing uses the correct vehicle/rate period;
- owner payable uses the actual owner/vehicle period;
- odometer continuity is vehicle-specific;
- billing continuity is agreement-specific;
- any failure rolls back the entire replacement.

---

# 16. Daily Running Chart

The running chart is the authoritative operational source.

Observed fields/concepts:

- vehicle;
- customer agreement;
- owner agreement/cost context;
- driver;
- start/finish date;
- day of week;
- start/finish odometer;
- calculated kilometres;
- start/finish time;
- working hours;
- OT category;
- normal OT;
- double OT;
- triple OT;
- night-outs;
- garage/internal kilometres;
- other charges;
- hire particulars;
- status.

Observed convenience actions:

- continue previous finish mileage;
- clear/re-enter mileage;
- continue previous time;
- clear/re-enter time.

## Recommended states

```text
Draft
→ Submitted
→ Approved
→ Consumed
→ Corrected / Reversed
```

## Validations

- finish odometer must be >= start odometer;
- distance must be backend-derived;
- usage must fall inside an active allocation;
- party/vehicle/driver contexts must match;
- duplicate/overlapping usage windows must be prevented;
- OT/night-out values must be consistent;
- approved usage cannot be directly edited;
- consumed usage cannot be silently changed;
- carry-forward values must come from the correct vehicle’s latest valid event.

Store operational facts, not mutable financial totals.

---

# 17. Billing Period

A billing period groups approved usage for calculation.

Required:

- agreement;
- customer;
- start/end dates;
- included usage contexts;
- billing basis;
- status;
- customer rate snapshot;
- owner-cost context;
- calculation version;
- finalization metadata.

## Rules

- one usage context cannot be consumed twice on the same financial side;
- customer billing and owner costing may use different grouping periods;
- partial invoicing must use explicit allocations;
- finalized periods require adjustment/reversal, not destructive recalculation.

---

# 18. Customer Invoice Flow

Observed invoice fields/concepts:

- customer;
- invoice heading;
- agreement number;
- agreement vehicle number;
- start/end dates;
- invoice date/number;
- total kilometres;
- excess kilometres;
- normal/double/triple OT;
- night-outs;
- number of days/hires;
- agreement type/basis;
- VAT/SVAT;
- maximum kilometres;
- vehicle/rate category;
- driver salary and OT rates;
- invoice description;
- import running-chart data.

Observed components:

- rental income;
- excess-km income;
- refundable driver salary;
- refundable driver OT;
- refundable driver night-out;
- SSCL;
- VAT;
- total.

## Recommended flow

```text
Approved Running Charts
→ Select Billing Period
→ Calculate Customer Charges
→ Review
→ Approve
→ Create Invoice
→ Post to Finance
```

## Safeguards

- backend resolves authoritative usage and rates;
- never trust client-submitted totals;
- prevent duplicate source consumption;
- enforce currency consistency or explicit conversion;
- use actual tax date;
- finalized charges/invoices cannot be hard-edited;
- reversal must restore source allocations and reverse tax/ledger effects.

---

# 19. Customer Charge Components

Minimum components:

```text
base_rental
excess_km
driver_salary_recovery
normal_overtime_recovery
double_overtime_recovery
triple_overtime_recovery
night_out_recovery
parking_recovery
other_recovery
sscl
vat
svat
```

Use typed calculation lines with:

- component code;
- quantity;
- unit;
- rate;
- pre-tax amount;
- tax;
- final amount;
- source usage;
- snapshot;
- explanation.

Do not add one schema column per future component.

---

# 20. Excess-Kilometre Calculation

Observed methods:

1. period/normal;
2. per hire;
3. per usage log.

Customer and owner methods may differ.

## Period-based

```text
Excess KM = max(0, Period Chargeable KM - Period Included KM)
Amount = Excess KM × Rate
```

## Per-hire

```text
For each hire:
  Excess KM = max(0, Hire KM - Included KM)
Total = sum(Excess KM × Rate)
```

## Per-usage-log

```text
For each approved log:
  Excess KM = max(0, Log KM - Included KM)
Total = sum(Excess KM × Rate)
```

## Configuration must define

- included-distance scope;
- garage/internal mileage treatment;
- rollover behavior;
- replacement continuity;
- rounding;
- unit;
- rate;
- minimum/maximum charge.

The exact business meaning of “per hire” must be confirmed before production hardcoding.

---

# 21. Driver and Overtime

Observed:

- driver salary;
- weekday hours;
- Saturday hours;
- public-holiday hours;
- normal OT;
- double OT;
- triple OT;
- night-out.

## Rules

- driver assignment must be effective-date aware;
- actual start/finish time comes from running chart;
- OT category/hours must be backend-derived or validated;
- customer recovery is not automatically equal to payroll cost;
- owner reimbursement may differ from customer recovery.

## Driver report

Include:

- driver;
- date;
- vehicle;
- customer/agreement;
- start/finish time;
- normal/double/triple OT;
- night-outs;
- customer recovery;
- owner reimbursement;
- payroll/actual cost where integrated.

---

# 22. Vehicle Owner Payable

Observed owner payable fields/concepts:

- agreement vehicle number;
- owner agreement;
- owner;
- customer;
- rate category;
- start/end dates;
- payable date;
- sequence/reference;
- total/excess kilometres;
- normal/double/triple OT;
- night-outs;
- days/hires;
- agreement basis;
- VAT;
- driver salary;
- maximum kilometres;
- applicable rates.

Observed components:

- rental expense;
- excess-km expense;
- VAT;
- refundable driver salary;
- refundable driver OT;
- refundable driver night-out;
- total.

## Recommended mapping

```text
Approved Owner Cost Calculation
→ Supplier/Owner Payable
→ Finance Posting
→ Adjustment Allocation
→ Payment Allocation
```

Reuse central Payable/Payment/Finance capabilities.

---

# 23. Owner Deductions and Adjustments

Observed:

- lessor debit note for fuel and repair;
- owner control account;
- vehicle;
- customer;
- description;
- debit amount;
- fuel/repair classification;
- source reference;
- allocation.

Required adjustment types may include:

- fuel deduction;
- repair deduction;
- service/maintenance deduction;
- licence/insurance recovery;
- damage recovery;
- other debit adjustment;
- credit adjustment;
- WHT.

## Traceability

Every adjustment must reference:

- owner;
- vehicle;
- agreement;
- source expense/document;
- payable or statement period;
- allocated amount;
- remaining amount;
- tax treatment;
- reversal/cancellation.

---

# 24. Owner Net Payable Formula

```text
Base Rental Payable
+ Excess-KM Payable
+ Driver Reimbursement
+ Night-Out / Other Agreed Cost
+ Owner Credit Adjustment
- Fuel Deduction
- Repair / Service Deduction
- WHT
- Other Debit Adjustment
= Net Owner Payable
```

Customer revenue and owner cost must never be assumed equal.

---

# 25. Customer Receipt Allocation

Observed allocation behavior:

- original receipt amount;
- remaining receipt amount;
- selected invoice/reference;
- reference balance;
- amount to allocate;
- removal/unallocation.

Required:

- one receipt to multiple invoices;
- multiple receipts to one invoice;
- partial allocation;
- unallocated balance;
- customer consistency;
- currency consistency;
- concurrency-safe allocation;
- controlled reversal/unallocation;
- audit trail.

Do not hard-delete posted allocation rows.

---

# 26. Owner Payment Allocation

Required:

- one payment to multiple payables;
- multiple payments to one payable;
- partial settlement;
- unallocated balance;
- adjustment/WHT interaction;
- cash/cheque/bank methods;
- owner/vehicle traceability;
- concurrency-safe allocation;
- reversal instead of deletion.

---

# 27. Payment Methods, Cheques, and Bank Reconciliation

Observed:

- cash payment;
- petty cash;
- cheque payment;
- cash/cheque receipt;
- bank deposits;
- bank debit/credit vouchers;
- bank reconciliation;
- printed cheque/payment documents.

AutoERP should reuse Payment and Finance modules for:

- payment methods;
- cheque templates;
- cheque issue/status;
- deposits;
- bank matching;
- reconciliation;
- reversal.

Vehicle Rental should store source links and business context only.

---

# 28. Tax Treatment

Observed:

- VAT;
- SVAT;
- SSCL;
- WHT;
- tax invoices;
- SVAT supplementary reporting.

## Rules

- tax definitions belong to Tax;
- rates are effective-date aware;
- customer and owner tax treatment are independent;
- tax date must match the taxable event;
- tax lines remain visible/auditable;
- GL mapping is configuration-driven;
- reversals reverse source, tax, and finance effects together.

---

# 29. Finance Ledger as Source of Truth

Approved financial documents must post through Finance.

Examples:

- customer invoice;
- customer debit/credit note;
- owner payable;
- owner debit/credit note;
- receipt;
- payment;
- expense/recovery;
- tax;
- reversal.

Statements, balances, and ageing must reconcile to posted ledger entries and allocations.

---

# 30. Customer Statement

```text
Opening Balance
+ Customer Invoices
+ Customer Debit Notes
- Receipts
- Customer Credit Notes
± Reversals
= Closing Balance
```

Required filters:

- customer;
- vehicle;
- agreement;
- date range;
- organization unit;
- currency;
- open/all items;
- summary/detail.

Every row must drill down to the source document.

---

# 31. Owner / Vehicle Statement

```text
Opening Balance
+ Owner Payables
+ Credit Adjustments
- Payments
- Fuel / Repair Deductions
- WHT
± Reversals
= Closing or Overpaid Balance
```

Required filters:

- owner;
- vehicle;
- agreement;
- date range;
- organization unit;
- currency;
- open/all items;
- summary/detail.

Statements must come from posted finance entries and allocations.

---

# 32. Reports Observed in the Recordings

## Customer/lessee

- customer ledger;
- customer vehicle ledger;
- agreement listing by vehicle;
- agreement listing by period;
- invoice listing by vehicle;
- invoice listing by type;
- invoice listing by vehicle type;
- invoice listing by customer;
- outstanding ageing;
- customer balance;
- customer statement;
- vehicle-wise customer statement;
- credit-note listing;
- debit-note listing;
- unallocated receipt/credit-note listing;
- SVAT supplementary declaration.

## Vehicle owner/lessor

- owner ledger;
- vehicle ledger;
- agreement listing by vehicle;
- agreement listing by period;
- owner statement;
- vehicle-wise owner statement;
- owner balance;
- owner payable listing;
- owner credit-note listing;
- owner debit-note listing;
- unallocated payment/debit-note listing;
- fuel/repair deduction listing.

## Vehicle/operations

- daily/monthly running chart;
- agreement status;
- replacement history;
- handover/return history;
- driver OT;
- vehicle utilization;
- unbilled usage;
- revenue-licence expiry;
- insurance expiry;
- document expiry;
- lease installment due;
- lease installment due as at date;
- vehicle profitability.

---

# 33. Recommended AutoERP Report Catalog

## Operational

- Active Agreements
- Agreement Expiry/Renewal
- Vehicle Allocation Timeline
- Vehicle Availability
- Custody/Handover History
- Replacement History
- Daily Running Chart
- Monthly Running Chart
- Driver Overtime
- Unapproved Usage
- Unbilled Usage
- Unprocessed Owner Cost
- Vehicle Utilization
- Revenue Licence Expiry
- Insurance Expiry
- Other Vehicle Document Expiry

## Customer financial

- Customer Invoice Detail/Summary
- Customer Debit/Credit Notes
- Customer Outstanding
- Customer Ageing
- Customer Statement
- Customer Vehicle/Agreement Statement
- Unallocated Receipts/Credits

## Owner financial

- Owner Payable Detail/Summary
- Owner Debit/Credit Notes
- Owner Outstanding
- Owner Ageing
- Owner Statement
- Vehicle-wise Owner Statement
- Owner Fuel/Repair Deductions
- Unallocated Payments/Debits

## Management

- Vehicle Profitability
- Customer Revenue vs Owner Cost
- Recoverable vs Non-Recoverable Expense
- Driver Recovery vs Cost
- Lease Installment Due/Overdue
- Agreement Margin
- Vehicle Downtime

---

# 34. Vehicle Profitability

```text
Customer Rental Revenue
+ Excess-KM Revenue
+ Driver / OT / Night-Out Recoveries
+ Parking / Other Recoveries
+ Recoverable Expense Income
- Vehicle Owner Rental Payable
- Owner Excess-KM Payable
- Driver Actual Cost / Reimbursement
- Non-Recoverable Fuel and Repairs
- Maintenance / Service Cost
- Licence and Insurance Allocation
- Lease / Finance Cost Allocation
- Other Direct Rental Costs
= Vehicle Contribution / Profit
```

## Rules

- avoid double-counting recoverable expenses;
- distinguish approved/accrued from posted/paid;
- allow vehicle/owner/customer/agreement/date filters;
- provide component drill-down;
- reversals must update profitability consistently.

---

# 35. State Models

## Agreement

```text
Draft
→ Active
→ Suspended
→ Completed / Expired
→ Terminated
→ Cancelled
```

## Allocation

```text
Planned
→ Active
→ Replaced / Returned / Completed
→ Cancelled
```

## Custody event

```text
Draft
→ Confirmed
→ Reversed / Corrected
```

## Running chart

```text
Draft
→ Submitted
→ Approved
→ Consumed
→ Corrected / Reversed
```

## Charge/cost calculation

```text
Draft
→ Calculated
→ Approved
→ Invoiced / Payable Created
→ Reversed / Cancelled
```

Aggregate run status must derive from all children.

## Financial document

```text
Draft
→ Approved / Posted
→ Partially Settled
→ Settled
→ Reversed
```

Posted documents must not return to editable draft without explicit reversal.

---

# 36. Critical Data Integrity Rules

1. Tenant scope on every read/write.
2. Organization-unit scope validation.
3. Party role must match transaction context.
4. No overlapping vehicle allocation.
5. Agreement/allocation dates must be compatible.
6. Owner relationship valid for vehicle/period.
7. Usage requires active allocation.
8. Odometer/time chronology valid.
9. Approved usage not destructively editable.
10. Usage not invoiced twice.
11. Usage not owner-costed twice.
12. Customer/owner snapshots independent.
13. Currency match or explicit conversion.
14. Correct tax date/effective rate.
15. Suitable decimal precision.
16. Allocation concurrency safety.
17. Posted records use reversal.
18. Replacement atomic.
19. Availability derived from allocation/custody.
20. Statements reconcile to Finance.
21. Backend permissions mandatory.
22. Frontend hiding is not security.
23. Historical snapshots immutable.
24. Idempotency prevents duplicates.
25. Delete behavior protects audit/finance history.

---

# 37. Currency and Precision

Required:

- explicit agreement currency;
- customer invoice currency from customer snapshot;
- owner payable currency from owner snapshot;
- explicit conversion when currencies differ;
- exchange-rate source/date retained;
- consistent quantity/rate/tax/amount precision;
- deterministic rounding boundaries.

Never reuse raw totals across currencies.

---

# 38. Authorization

Permissions should cover:

- view/create/update/activate/terminate agreements;
- allocate/replace/return vehicles;
- confirm custody;
- create/submit/approve running charts;
- calculate/approve customer charges;
- generate/reverse invoices;
- calculate/approve owner costs;
- create/reverse owner payables/deductions;
- allocate/reverse receipts/payments;
- view/export reports;
- view profitability/finance data.

Backend enforcement is mandatory.

---

# 39. Module Boundaries

## Vehicle Rental owns

- reservations;
- rental agreements;
- agreement-vehicle allocations;
- rate snapshots;
- custody/handover/return;
- running charts;
- usage events/contexts;
- billing periods;
- customer charge calculations;
- owner cost calculations;
- rental source links;
- rental status history.

## Customer owns

- customer identity;
- contacts;
- credit profile;
- customer-vehicle relationships where relevant.

## Supplier / Party owns

- owner/supplier identity;
- supplier payment profile;
- owner-vehicle relationships.

## Vehicle owns

- vehicle master;
- base availability state;
- documents;
- licence/insurance;
- ownership history;
- finance-agreement references.

## HR owns

- driver/employee identity;
- employment/payroll;
- availability/assignment.

## Invoice owns

- customer invoices;
- invoice lines;
- debit/credit adjustments;
- source allocations.

## Payment owns

- receipts;
- payments;
- methods;
- cheques;
- allocations.

## Finance owns

- ledger postings;
- accounts;
- statements;
- deposits;
- reconciliation;
- reversals.

## Tax owns

- tax definitions;
- effective rates;
- applicability;
- tax calculation/posting rules.

## Reporting owns

- cross-module read models and exports.

Do not duplicate these capabilities in Vehicle Rental.

---

# 40. Suggested Entity Set

Potential entities:

- `rental_reservations`
- `rental_agreements`
- `rental_agreement_vehicles`
- `rental_agreement_vehicle_links`
- `rental_agreement_rate_snapshots`
- `rental_pickup_inspections`
- `rental_return_inspections`
- `rental_usage_logs`
- `rental_usage_events`
- `rental_usage_contexts`
- `rental_billing_periods`
- `rental_charge_runs`
- `rental_charge_calculations`
- `rental_charges`
- `rental_expenses`
- `rental_invoice_links`
- `rental_payment_links`
- `rental_status_histories`

Add new tables only when existing entities cannot cleanly represent:

- full custody-chain events;
- owner-payable links;
- deduction allocations;
- vehicle-finance agreements/installments.

Avoid one table per charge type and one column per future rate component.

---

# 41. Recommended Service Responsibilities

Possible focused services:

- AgreementLifecycleService
- VehicleAvailabilityService
- VehicleAllocationService
- CustodyService
- VehicleReplacementService
- RentalUsageService
- BillingPeriodService
- CustomerChargeCalculator
- OwnerCostCalculator
- RentalChargeApprovalService
- RentalInvoiceLinkService
- OwnerPayableLinkService
- RentalAdjustmentService
- RentalReportQueryService

Do not create unnecessary generic frameworks.

A facade may coordinate focused services, but business logic should not remain in thousand-line monoliths.

---

# 42. Core Use Cases

An implementation agent should expect:

- create/update customer agreement;
- activate/suspend/terminate agreement;
- create/update owner agreement;
- create/update finance agreement;
- reserve/allocate vehicle;
- confirm owner handover;
- confirm customer handover;
- record usage;
- submit/approve/reject usage;
- replace vehicle;
- confirm customer return;
- confirm owner return;
- open/finalize billing period;
- calculate customer charges;
- approve/reverse customer charge;
- create customer invoice;
- calculate owner payable;
- approve/reverse owner cost;
- create owner payable;
- record owner deduction;
- allocate receipt;
- allocate owner payment;
- reverse allocation;
- generate statements/reports.

Each use case must validate scope, state, authorization, concurrency, and idempotency.

---

# 43. API Contract Principles

- return readable related resources, not only IDs;
- requests submit facts/identifiers, not trusted totals;
- backend derives distance, hours, rates, tax, totals;
- mutation response returns status/totals/next actions;
- business-rule errors are explicit;
- lookup endpoints support search/pagination/filters;
- boolean query values are normalized;
- risky financial mutations support idempotency;
- concurrency-sensitive operations use locking/constraints.

---

# 44. Modern UI/UX Direction

Do not clone the legacy UI.

## Legacy weaknesses

- overly dense forms;
- editable/calculated values mixed;
- GL codes exposed;
- confusing lessee/lessor wording;
- duplicate manual entry;
- weak status/next-action visibility;
- unsafe edit/delete controls;
- poor source traceability;
- difficult report navigation.

## Recommended guided flow

```text
Agreement
→ Vehicle & Driver Allocation
→ Handover
→ Daily Usage
→ Usage Review
→ Customer Billing
→ Owner Payable
→ Settlement
```

## UI rules

- step-based or clearly sectioned forms;
- inherited/calculated values read-only;
- load source data automatically;
- separate customer and owner financial panels;
- show current state and allowed next actions;
- hide accounting configuration from routine users;
- provide source-document drill-down;
- use reversal/adjustment for posted records;
- consistent report filters/exports;
- backend remains the source of authorization.

---

# 45. Legacy Behavior Not to Copy

Do not copy:

1. raw GL selection in every transaction form;
2. fixed Non-AC/Front-AC/Dual-AC schema columns;
3. rental-specific password register;
4. hard editing/deleting posted records;
5. manual running-chart total transfer;
6. shared customer/owner rates;
7. free-text financial allocations;
8. application-only uniqueness;
9. driver-specific migration behavior that silently skips constraints;
10. reports from inconsistent raw tables;
11. giant all-purpose services/components;
12. frontend-only business validation.

---

# 46. Key Audit Checklist for Current AutoERP

Verify:

1. backend read/write authorization;
2. tenant and organization scoping;
3. party-role correctness;
4. allocation overlap prevention;
5. custody state enforcement;
6. atomic replacement;
7. immutable customer/owner snapshots;
8. triple OT;
9. excess-km strategies;
10. tax date/effective rates;
11. currency consistency;
12. duplicate invoice prevention;
13. duplicate owner-cost prevention;
14. charge-run aggregate status;
15. deduction/payable allocation;
16. receipt/payment locking;
17. reversal rules;
18. ledger-backed statements;
19. complete profitability;
20. lease installments;
21. driver OT report;
22. unallocated reports;
23. source drill-down;
24. maintainability hotspots;
25. clean migrations/constraints.

---

# 47. Acceptance Scenarios

## A. Company-owned vehicle with driver

- customer agreement;
- company vehicle/driver allocation;
- customer handover;
- approved usage;
- invoice with rental/excess/OT/night-out;
- receipt allocation;
- no third-party owner payable;
- internal costs affect profitability.

## B. Third-party owner vehicle

- owner and customer agreements;
- owner handover;
- customer handover;
- approved usage;
- customer invoice from customer snapshot;
- owner payable from owner snapshot;
- fuel/repair deduction;
- net owner payment;
- both statements reconcile.

## C. Self-drive

- no driver charges unless explicitly configured;
- handover captures odometer/fuel/condition/documents/signatures;
- return compares usage/condition;
- damage recovery is traceable.

## D. Replacement

- old vehicle return/inspection;
- available replacement allocated;
- replacement handover;
- agreement/billing continuity;
- owner costs split by actual vehicle/owner periods;
- no overlap;
- full rollback on failure.

## E. Partial billing

- selected eligible usage invoiced;
- remaining usage stays unbilled;
- same usage cannot be reused;
- partial receipt leaves correct outstanding.

## F. Owner partial settlement

- partial payment;
- debit note and WHT reduce balance;
- statement shows all movements;
- reversal restores balance.

## G. Tax-rate change

- correct rate by taxable event date;
- old finalized invoices remain unchanged.

## H. Lease installment

- finance agreement;
- due schedule;
- payment settlement;
- due/overdue/expiry reports;
- not mixed with owner payable.

---

# 48. Minimum Automated Test Coverage

## Backend

- tenant isolation;
- permission checks;
- allocation overlap concurrency;
- replacement rollback;
- odometer/time validation;
- duplicate usage consumption;
- customer charge calculations;
- owner cost calculations;
- excess-km methods;
- normal/double/triple OT;
- tax effective date;
- currency mismatch;
- receipt/payment allocation concurrency;
- reversal;
- statement reconciliation;
- profitability.

## Frontend

- permission-aware actions;
- agreement dependencies;
- running-chart derived fields;
- unsaved changes;
- source import;
- status-based actions;
- allocation/reversal;
- report filtering/drill-down;
- backend business-error rendering.

---

# 49. Open Questions Requiring Business Confirmation

Do not invent fixed answers for:

1. exact “per hire” excess-km formula;
2. unused included-km rollover;
3. whether customer and owner periods must match;
4. Saturday/public-holiday OT thresholds;
5. driver salary basis;
6. garage mileage treatment;
7. WHT base/timing;
8. SSCL applicability;
9. SVAT workflow;
10. security-deposit accounting/refund;
11. damage assessment/approval;
12. lease principal/interest posting;
13. owner payable approval levels;
14. agreement renewal/versioning;
15. backdated corrections after period close.

Until confirmed:

- keep rules configurable where reasonable;
- preserve audit history;
- avoid hardcoding assumptions;
- make calculations explainable.

---

# 50. Definition of Done

Vehicle Rental is complete when:

- customer, owner, and leasing-company relationships are represented correctly;
- one approved running chart generates correct customer revenue and owner cost independently;
- snapshots remain historically immutable;
- custody is fully traceable;
- replacement is atomic;
- triple OT works;
- excess-km strategies work;
- duplicate source consumption is prevented;
- partial/multiple allocations are safe;
- deductions/WHT reconcile;
- posted records use reversal;
- statements reconcile to Finance;
- profitability is complete without double-counting;
- lease installments are separate;
- APIs enforce permission and tenant scope;
- reports drill down to source;
- migrations, seeders, rollback, tests, and build pass.

---

# 51. Instructions for an AI Agent

When auditing or implementing:

1. inspect existing migrations, models, services, routes, DTOs, enums, tests, and frontend before editing;
2. reuse core modules instead of duplicating them;
3. fix backend business issues in backend;
4. preserve valid current behavior;
5. do not copy the legacy UI/schema literally;
6. implement the smallest maintainable change;
7. keep customer revenue and owner cost separate;
8. keep operational facts separate from financial calculations;
9. use transactions, locking, idempotency, and constraints where required;
10. prefer focused services over monoliths or excessive abstraction;
11. make every calculation traceable to usage and snapshots;
12. update tests for every invariant;
13. report assumptions and unresolved policies;
14. never silently skip a constraint based on DB driver;
15. use portable Laravel Schema Builder where possible.

---

# Final Canonical Understanding

The recordings describe a dual-sided Vehicle Rental ERP domain:

- the customer consumes the vehicle and owes rental revenue;
- the vehicle owner supplies the vehicle and is owed a separately calculated payable;
- the leasing company finances the vehicle through installments;
- the running chart is the shared operational source;
- allocation and custody determine valid usage;
- customer and owner rates are independent and immutable;
- invoice, payable, receipt, payment, tax, and ledger behavior belongs to central financial modules;
- statements and profitability must reconcile to approved/posted documents;
- replacement, reversal, allocation, and period-close operations must be transactional and auditable.

> **Canonical architecture rule:**  
> Use one authoritative rental-usage stream, then derive separate customer-revenue and owner-cost streams with independent agreements, rates, calculations, taxes, documents, postings, and settlements.

---

# Appendix A — Complete Legacy Screen and Document Map

The receiving agent cannot see the videos, so the following visible legacy artifacts are explicitly recorded.

## Registers

- Company Register
- Cost Centre Register
- Account Group Register
- Account Note Register
- General Ledger Account Register
- Payee Register
- Lessee Register
- Lessor Register — Leasing Companies
- Lessor Register — Vehicle Owners
- Vehicle Register
- Driver Register
- Month Register
- Agreement Register — With Leasing Companies
- Agreement Register — With Vehicle Owners
- Agreement Register — With Lessee

## Rental operational documents

- Daily Running Chart
- Replacement Running Chart
- Self-drive Vehicle Handover Note to Lessee
- Self-drive Vehicle Return Note from Lessee
- Vehicle Handover Note from Lessor
- Vehicle Return Note to Lessor
- Customer Credit Invoice
- Owner Payment Payable Voucher
- Lessor Debit Note — Fuel & Repair
- Receipt Allocation
- Cheque Payment
- Printed Invoice
- Printed Payable Voucher
- Printed Cheque
- Customer Statement
- Owner Statement
- Running Chart Report

## General ledger transaction groups

- Cash Payment Voucher
- Petty Cash Payment Voucher
- Cheque Payment Voucher
- Cash/Cheque Receipt
- Bank Deposit Slip
- Journal Voucher
- Journal Voucher — Own Vehicle Expenses
- Bank Debit Voucher
- Bank Credit Voucher
- Bank Reconciliation Editing

---

# Appendix B — Field-Level Knowledge

## Lessee/customer screen

Observed or strongly implied:

- customer/lessee code;
- customer name;
- address/contact data;
- credit limit;
- opening balance;
- VAT/SVAT data;
- driver salary defaults;
- working-hour defaults;
- normal/double/triple OT rates;
- night-out rate;
- active status.

## Customer agreement screen

Observed or strongly implied:

- agreement number;
- agreement date;
- execution date;
- start date;
- end date;
- customer;
- vehicle/vehicle type;
- company/personal;
- monthly/daily;
- with-driver/self-drive;
- maximum/included kilometres;
- excess-km rate;
- excess-km calculation method;
- driver salary;
- weekday/Saturday/holiday hours;
- normal/double/triple OT rates;
- night-out;
- VAT/SVAT;
- SSCL;
- security deposit;
- rate category;
- agreement conditions;
- rental and recovery account mappings.

## Daily running chart screen

Observed:

- customer agreement;
- owner agreement;
- vehicle;
- driver;
- start date/time;
- finish date/time;
- start mileage;
- finish mileage;
- calculated kilometres;
- day of week;
- working hours;
- OT type;
- normal/double/triple OT;
- night-outs;
- garage mileage;
- hire particulars;
- other charges;
- mileage/time carry-forward actions.

## Customer invoice screen

Observed:

- customer;
- agreement number;
- agreement vehicle number;
- billing period;
- invoice date/number;
- total kilometres;
- excess kilometres;
- days/hires;
- normal/double/triple OT;
- night-outs;
- agreement basis/type;
- vehicle/rate type;
- maximum kilometres;
- driver rates;
- VAT/SVAT;
- SSCL;
- running-chart import;
- rental income;
- excess-km income;
- driver salary recovery;
- overtime recovery;
- night-out recovery;
- total.

## Owner payable screen

Observed:

- owner;
- customer;
- owner agreement;
- agreement vehicle number;
- start/end dates;
- payable date/reference;
- total/excess kilometres;
- days/hires;
- normal/double/triple OT;
- night-outs;
- owner rate category;
- VAT;
- rental expense;
- excess-km expense;
- driver reimbursement;
- overtime reimbursement;
- night-out reimbursement;
- total.

## Owner debit-note screen

Observed:

- debit-note date/number;
- owner control account;
- vehicle;
- customer;
- description;
- fuel/repair classification;
- source reference;
- debit amount;
- credit account/amount;
- allocation detail.

## Receipt-allocation screen

Observed:

- receipt reference;
- original amount;
- remaining amount;
- selected invoice/reference;
- reference balance;
- allocation amount;
- remove/unallocate action.

---

# Appendix C — Canonical End-to-End Workflows

## Third-party owner vehicle

```text
Register Owner
→ Link Vehicle to Owner
→ Create Owner Agreement
→ Create Customer Agreement
→ Owner Hands Vehicle to Company
→ Allocate Vehicle
→ Company Hands Vehicle to Customer
→ Record Daily Running Charts
→ Approve Usage
├── Calculate Customer Revenue
│   → Create Invoice
│   → Allocate Receipt
└── Calculate Owner Cost
    → Create Owner Payable
    → Allocate Deductions/WHT
    → Pay Owner
→ Customer Returns Vehicle
→ Company Returns Vehicle to Owner
→ Close Agreements / Statements
```

## Company-owned vehicle

```text
Create Customer Agreement
→ Allocate Company Vehicle
→ Customer Handover
→ Record/Approve Usage
→ Customer Invoice
→ Receipt
→ Track Internal Vehicle/Driver Costs
→ Profitability
→ Customer Return
```

## Replacement

```text
Detect Replacement Need
→ Validate Replacement Vehicle
→ Return/Inspect Old Vehicle
→ Close Old Allocation
→ Open Linked Replacement Allocation
→ Handover Replacement Vehicle
→ Continue Usage
→ Split Revenue/Cost by Actual Vehicle Period
```

## Leasing-company finance

```text
Create Vehicle Finance Agreement
→ Generate Installment Schedule
→ Identify Due Installments
→ Make/Allocate Payment
→ Update Due/Paid/Overdue State
→ Report Lease Position
```

---

# Appendix D — Calculation Knowledge

## Customer invoice

```text
Base Rental
+ Excess-KM Charge
+ Driver Salary Recovery
+ Normal OT Recovery
+ Double OT Recovery
+ Triple OT Recovery
+ Night-Out Recovery
+ Parking / Other Recovery
+ SSCL where applicable
+ VAT or SVAT treatment
= Customer Invoice Total
```

## Owner payable

```text
Base Owner Rental
+ Owner Excess-KM Amount
+ Driver Reimbursement
+ Normal/Double/Triple OT Reimbursement
+ Night-Out Reimbursement
+ Other Agreed Owner Cost
- Fuel Deduction
- Repair/Service Deduction
- WHT
- Other Debit Adjustments
+ Credit Adjustments
= Net Owner Payable
```

## Profitability

```text
Customer Rental Revenue
+ Excess-KM Revenue
+ Driver/OT/Night-Out Recoveries
+ Parking/Other Recoveries
+ Recoverable Expense Income
- Owner Rental Payable
- Owner Excess-KM Payable
- Driver Actual Cost
- Non-Recoverable Fuel/Repair
- Maintenance/Service
- Licence/Insurance Allocation
- Lease/Finance Cost Allocation
- Other Direct Costs
= Vehicle Contribution / Profit
```

---

# Appendix E — Report Knowledge

## Customer/lessee reports

- Ledger Accounts
- Vehicle Ledger
- Agreement Listing — Vehicle Wise
- Agreement Listing — Period Wise
- Invoice Listing — Vehicle Wise
- Invoice Listing — Invoice Type Wise
- Invoice Listing — Vehicle Type Wise
- Invoice Listing — Customer Wise
- Outstanding Age Analysis
- Balance
- Statement of Accounts
- Vehicle-wise Statement
- Credit Note Listing
- Debit Note Listing
- Unallocated Receipt/Credit Note Listing
- SVAT Supplementary Declaration

## Owner/lessor reports

- Ledger Accounts
- Vehicle Ledger
- Agreement Listing — Vehicle Wise
- Agreement Listing — Period Wise
- Statement of Accounts
- Vehicle-wise Statement
- Balance
- Payment Payable Listing
- Credit Note Listing
- Debit Note Listing
- Unallocated Payment/Debit Note Listing
- Fuel & Repair Debit Note Listing

## Vehicle/operations reports

- Daily Running Chart
- Monthly Running Chart
- Agreement Status
- Replacement History
- Handover/Return History
- Driver OT
- Vehicle Utilization
- Unbilled Usage
- Revenue Licence Expiry
- Insurance Expiry
- Document Expiry
- Lease Installment Due
- Lease Installment Due as at Date
- Vehicle Profitability

---

# Appendix F — Unresolved Policies

These items were not sufficiently determined from the recordings and must remain configurable or be confirmed:

1. Exact `per_hire` excess-km formula.
2. Included-km rollover.
3. Customer vs owner billing-period alignment.
4. Saturday/public-holiday OT thresholds.
5. Driver salary basis.
6. Garage-mileage treatment.
7. Security-deposit posting/refund.
8. Damage assessment and approval.
9. WHT base and timing.
10. SSCL applicability.
11. Current SVAT treatment.
12. Lease principal/interest posting.
13. Owner-payable approval levels.
14. Agreement renewal/version policy.
15. Backdated corrections after period close.

---

# Appendix G — AI Agent Completion Checklist

Before declaring Vehicle Rental complete, verify:

- [ ] Customer, owner and leasing-company relationships are separate.
- [ ] Running chart is the authoritative operational source.
- [ ] Customer and owner calculations use independent snapshots.
- [ ] Vehicle allocation overlap is prevented.
- [ ] Custody handover/return is traceable.
- [ ] Replacement is atomic.
- [ ] Normal/double/triple OT is supported.
- [ ] Excess-km methods are supported.
- [ ] Duplicate invoice and owner-cost consumption is prevented.
- [ ] Owner deductions and WHT allocate correctly.
- [ ] Partial/multiple receipt and payment allocations work.
- [ ] Currency and tax-date integrity are enforced.
- [ ] Posted documents use reversal.
- [ ] Statements reconcile to Finance.
- [ ] Profitability avoids double counting.
- [ ] Lease installments are separate from owner payables.
- [ ] Backend permissions and tenant scope are enforced.
- [ ] Reports drill down to source documents.
- [ ] Migrations, seeders, rollback, tests and build pass.

