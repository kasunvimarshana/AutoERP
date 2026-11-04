# AutoERP Vehicle Rental Business Knowledge Base & Domain Specification

**Status:** Canonical Vehicle Rental Business Domain, Architectural Policy, and Operational Reference for AutoERP.
**Owning Module:** `app/Modules/VehicleRental` & `resources/js/modules/vehicle-rental`
**Architecture Policy:** Root `AGENTS.md` and `RULES.md`
**Technical & Architecture Guide:** [`VEHICLE_RENTAL_GUIDE.md`](VEHICLE_RENTAL_GUIDE.md)
**End-User Operations Guide:** [`VEHICLE_RENTAL_USER_GUIDE.md`](VEHICLE_RENTAL_USER_GUIDE.md)
**Shared Memory & Change History:** `docs/changes/`

---

## 1. Purpose, Authority & Guiding Principles

This document is the authoritative, self-contained domain reference for the Vehicle Rental module in AutoERP. It synthesizes proven business rules from historical commercial operations (the TACGL corpus and authoritative video workflows) with the clean architecture, strict data integrity, optimistic concurrency, and multi-tenant security demanded by AutoERP.

It is **not** a retrospective audit diary or a collection of dated commit notes. It is the living canonical specification of how Vehicle Rental operates, how data integrity is enforced, how financial boundaries are preserved, and how operators interact with the system.

### Core Guiding Principles

1. **Understand first, verify second, change third.**
   Never guess, assume, or apply changes blindly. Fully understand business context, module ownership, and data consequences before modifying the codebase.
2. **Do not invent money.**
   Physical events (trips, fuel purchases, vehicle handovers, incidents) may be recorded freely as operational evidence. However, **no financial transaction**—charge, invoice, payable voucher, credit, debit note, tax, withholding, or deduction—may ever be created without an explicit, approved business rule and verified source entitlement.
3. **Keep operator workflows fast and simple; enforce strict integrity behind the UI.**
   Users want speed and task completion efficiency without cognitive overload. Do not burden operators with manual ID entry, repetitive dates, or internal database concepts. Present human-readable labels, controlled lookups, and sensible defaults while backend validation strictly enforces concurrency, tenant isolation, and audit immutability.
4. **Fix issues at their root source.**
   Never compensate for a bug or limitation in one module by adding workarounds in another. Rental consumes master and financial modules through clean contracts; it does not clone them or patch around them.
5. **KISS + Explicit over DRY.**
   Clarity, predictability, and safety always outweigh clever or dynamic abstractions. Database schemas, migrations, calculation snapshots, and business rules must be brutally explicit.

### Source Precedence Hierarchy

When evaluating domain behavior or resolving conflicting requirements, prioritize in this strict order:
1. **Proven business evidence** from the TACGL corpus and authoritative operational workflow videos.
2. **Root architectural rules** (`AGENTS.md` / `RULES.md`): single source of truth, atomic concurrency, clean module boundaries, immutable history.
3. **Narrowest integrity rule** necessary to preserve physical and commercial truth safely.
4. **Explicit AutoERP production policies** formally documented in this specification.
5. **Absence of automatic financial effect:** If an edge case has no confirmed business policy, physical evidence is preserved but no automatic financial document is generated until explicitly authorized.

---

## 2. Authoritative Source Evidence Register

### 2.1 TACGL Legacy Corpus

The foundational business knowledge derives from the historical TACGL Visual FoxPro ERP database (`TACGL.zip`, 452 normalized business data files and report definitions). TACGL provides definitive evidence of real-world Sri Lankan commercial fleet rental operations, including:
- **Vehicle master and operational profiles:** `scfveh.DBF` (registration, vehicle numbers, make/model, engine/chassis, service mileage).
- **Commercial parties:** `scfdeb.DBF` (debtors / rental customers / credit limits) and `scfcre.DBF` (creditors / vehicle owners / suppliers).
- **Billing and transaction logs:** `scfinv.DBF` (invoice numbers, vehicle references, customer/owner references, base rates, distance charges, VAT/NBT taxes) and `scftxn.DBF` (transaction-level items and rates).
- **Accounting subledgers:** `scftdb.DBF` (debtor receivables subledger) and `scftcr.DBF` (creditor payables subledger).
- **General ledger and banking:** `scfglt.DBF` (GL transaction entries, cheque numbers, vouchers, financial posting).
- **Operational report layouts:** FoxPro report forms (`.FRX`), including the official 31-day running chart matrix sheets (`pmdeblogsheet2.frx` for customer/lessee and `pmcre1logsheet2.frx` for vehicle owners), revenue licence tracking, and outstanding receivables age analyses.

*TACGL serves as authoritative proof of business concepts, commercial terms, and operational reporting requirements; it is never an architectural or database schema template for AutoERP.*

### 2.2 Authoritative Video Corpus

Four primary operational screen recordings demonstrate actual day-to-day fleet management workflows:
- **Video 1 (`1.mp4`, ~40m 50s):** End-to-end commercial operations: Customer and Owner Agreements, daily Running Chart logging, customer invoice generation, owner payable voucher settlement, expense deductions, and cheque payments.
- **Video 2 (`Recording 2026-06-21 132314.mp4`, ~41m 58s):** Master registers: Vehicle onboarding, customer setup, dual agreement flows, trip log entry, receipt-to-invoice allocations, and monthly owner settlement statements.
- **Video 3 (`2.mp4`, ~21m 14s):** Transaction reporting, financial allocations, reconciliation routines, and historical error investigations.
- **Video 4 (`ScreenVideo_03-04-2026_18-02-52.mp4`, ~12m 24s):** Workshop operations: Maintenance admissions, vehicle service jobs, off-road statuses, and vehicle availability boundaries (confirming that vehicle service manages mechanical custody while rental manages commercial custody).

### 2.3 Evidence Classification Taxonomy

Every operational rule and calculation in Vehicle Rental belongs to one of seven classifications:
- **`Explicit-TACGL`**: Directly proven by database schemas, report layouts, or transaction logs in TACGL.
- **`Explicit-Video`**: Directly demonstrated in operator workflow videos.
- **`Cross-Source`**: Corroborated independently by both TACGL database records and video workflows.
- **`Integrity-Derived`**: Strict technical rules required to enforce atomicity, multi-tenancy, concurrency, and audit immutability.
- **`Observed-Precedent-Only`**: A historical transaction pattern that demonstrates past practice but is not a universal rule.
- **`Production-Policy`**: An explicit AutoERP design decision establishing deterministic behavior where historical precedent was inconsistent or ambiguous.
- **`Legacy-Mechanism-Rejected`**: Antiquated or flawed historical practices (e.g. destructive edits, raw database codes, arbitrary password registers) explicitly forbidden in AutoERP.

---

## 3. Canonical Domain Model & Architecture

Vehicle Rental is an integrated operational and financial domain structured around a **dual-sided commercial model**:

```text
       Owner / Supplier / Lessor                          Customer / Lessee
                  │                                               │
           Owner Agreement                                Customer Agreement
                  │                                               │
                  └───────────────┬───────────────────────────────┘
                                  │
                         Vehicle Use / Custody
                                  │
                         Daily Running Chart
                             /         \
                            /           \
                 Customer Side         Owner Side
               (Receivable/Rev)       (Payable/Cost)
                        │                   │
                 Customer Invoice      Owner Payable Voucher
                        │                   │
                 Customer Receipt       Owner Payment
                         \                 /
             Finance / Tax / Banking / Reconciliation / BI Reports
```

### Non-Negotiable Invariants

1. **Independent Commercial Aggregates:** Customer Agreements and Owner Agreements are completely independent commercial contracts. Customer rates never derive Owner payables; Owner costs never dictate Customer billing.
2. **Unified Physical Evidence:** A single physical vehicle assignment and its daily Running Charts serve as the single source of truth for both commercial sides.
3. **No Double Consumption:** An operational component (such as a calendar day of base rent or a kilometer of commercial distance) can never be billed twice on the same side without formal reversal/reissue lineage.
4. **Asynchronous Parallel Processing:** Invoicing the customer does not block settling with the owner, and owner disbursement does not wait for customer payment.
5. **No Fictitious Owner Costs:** When a vehicle is company-owned, no artificial Owner Agreement or external payable voucher is manufactured.
6. **Immutable Operational Truth:** Finalized Running Charts, custody transitions, and recorded incidents are permanent historical records. Adjustments or corrections create auditable reversal lineage rather than mutating historical facts.
7. **Strict Financial Separation:** Vehicle Rental delegates accounting, invoicing, tax, and cash movements to their respective owning modules (`Invoice`, `Payment`, `Finance`, `Tax`). Rental never maintains a duplicate GL or shadow cash ledger.
8. **Tenant & Organization Isolation:** Every entity, lookup query, and write operation is strictly scoped to the active tenant and organization unit.

### Module Ownership & Clear Boundaries

Vehicle Rental adheres to clean architecture principles and respects module boundaries:
- **Vehicle Master & Specifications:** Owned by `Vehicle`. Rental references vehicles by ID and reads active fleet lookups.
- **Customer Profiles & Debtor Accounting:** Owned by `Customer`.
- **Owner / Supplier Profiles & Creditor Accounting:** Owned by `Supplier` / `Party`.
- **Driver Profiles & Employment Records:** Owned by `HR`. Rental references active driver employees and tracks external driver metadata.
- **Receivable & Payable Invoices:** Owned by `Invoice`. Rental invokes invoice contracts for customer billing and owner payables.
- **Payments, Deposits & Receipts:** Owned by `Payment`. Rental triggers deposit receipts and views payment allocations; Payment owns payment methods, allocations, and refunds.
- **Taxes & Statutory Rates:** Owned by `Tax`. Rental provides semantic items and quantities; Tax computes exact levies.
- **General Ledger & Journal Entries:** Owned by `Finance`. Posting profiles govern debits and credits; Rental never writes direct GL journals.
- **Workshop Maintenance & Off-Road Availability:** Owned by `VehicleService`. Workshop admission blocks rental availability; rental custody blocks workshop dispatch.

---

## 4. End-to-End Operator Workflows

The system guides users through a clean, progressive workflow from booking inquiry to final reconciliation:

```text
 1. Booking Inquiry & Reservation
    └── Record customer, requested vehicle/category, rental mode, dates, estimated deposit.
    └── Confirm reservation when logistics and fleet availability are verified.
    └── Convert directly into a Customer Agreement upon customer commitment.

 2. Agreement Setup
    └── Establish Customer Agreement (commercial rates, allowances, overtime, driver mode).
    └── For third-party fleet supply, establish matching Owner Agreement with vehicle lessor.
    └── Review terms, verify dates, and activate the agreement.

 3. Vehicle Assignment & Custody
    └── Open active Customer Agreement and select available vehicle.
    └── For owner-supplied vehicles, link to an active Owner Agreement covering the period.
    └── For company-owned vehicles, flag as company supply (zero external payable).
    └── Plan handover and return timestamps.

 4. Physical Dispatch & Custody Tracking
    └── Handover: Record actual departure timestamp and verified starting odometer.
    └── In-Service: Vehicle is active; availability blocker prevents duplicate dispatch.
    └── Replacement (if required): Record old return odometer and new vehicle handover atomically.
    └── Return: Record actual return timestamp and verified return odometer.

 5. Daily Running Chart Logging
    └── Record daily trip sheets: start/end times, start/end odometers, commercial vs. garage KM.
    └── Log operational context: AC mode, driver identity (employee or external), overtime, night outs.
    └── Finalize chart to freeze physical operational truth.

 6. Commercial Billing & Settlement (Parallel Tracks)
    ├── Customer Billing Track:
    │   ├── Preview and generate Base Rent Invoices (daily or monthly anniversary cycles).
    │   ├── Assess commercial mileage against agreed cycle allowances.
    │   ├── Incur usage charges (excess mileage, overtime hours, night-out allowances).
    │   └── Receive security deposit receipts through Payment module.
    │
    └── Owner Settlement Track:
        ├── Generate monthly owner base lease entitlements.
        ├── Calculate owner excess distance and driver/OT reimbursements.
        ├── Deduct approved operating expenses (fuel, tolls, maintenance).
        ├── Apply withholding tax (WHT) per statutory rules.
        └── Generate Owner Payable Voucher for disbursement.

 7. Reporting & Reconciliation
    └── Generate official 31-day Running Chart matrix sheets (Landscape A4).
    └── Monitor fleet utilization, revenue licence expirations, lessee aging, and vehicle P&L.
```

---

## 5. Agreements & Successor Architecture

### 5.1 Customer Agreement

The Customer Agreement governs receivables from the client. It specifies:
- **Rental Basis:** `daily` or `monthly`.
- **Driver Arrangement:** `self_drive` or `with_driver`.
- **Agreed Commercial Rates & Allowances:**
  - Base rental rate per cycle (daily or monthly).
  - Included free distance (kilometers per day or monthly cycle).
  - Excess distance rate per kilometer.
  - Driver salary reimbursement (where applicable).
  - Normal, Double, and Triple overtime hourly rates.
  - Night-out daily rate.
  - Air conditioning rates (Non-AC, Front AC, Dual AC).
  - Agreed security deposit requirement (customer-only term).
- **Temporal Boundaries:** Agreement date, optional executing date, start date, and optional end date (or open-ended).

### 5.2 Owner Agreement

The Owner Agreement governs liabilities owed to an external vehicle lessor. It is completely independent of customer pricing and specifies:
- Owner base lease payable per cycle.
- Owner included distance and excess distance payable per kilometer.
- Driver wage and overtime reimbursement payable to the owner.
- Night-out reimbursement payable to the owner.
- Air conditioning rate entitlements.
- Assigned vehicle identity (mandatory for owner agreements).

### 5.3 Agreement Lifecycle

```text
Draft ──(Activate)──> Active ──(Close)──> Closed
  │                     │
(Edit)             (Successor)
                        │
                  Draft Successor
```

- **Draft:** Mutable. Terms, rates, and dates can be edited freely under optimistic concurrency (`expected_version`).
- **Active:** Immutable. Commercial terms are locked. Any future change in rates or terms requires creating a successor agreement.
- **Closed:** Terminal. Coverage ceases at the recorded closure boundary. Historical calculations remain permanently intact.

### 5.4 Successor Agreement Architecture

When commercial rates or contract terms change mid-lease, AutoERP prohibits mutating the active agreement. Instead, it uses **successor lineage**:
1. **Non-Disruptive Drafting:** While the predecessor agreement remains `Active`, operators create a `Draft` successor specifying the new reference, effective start date, revision reason, and updated terms.
2. **Deposit Non-Duplication:** Existing terms are copied into the draft for review, **except the security deposit requirement**. Security deposits belong to their original payment source and are never duplicated automatically; a new deposit is entered only if an amended agreement requires additional funds.
3. **Atomic Boundary Cutover:** The successor is activated on or after its effective start date. In a single atomic database transaction, the system:
   - Validates that active vehicle uses and billing periods do not conflict with the boundary cutover.
   - Closes the predecessor agreement exactly at the cutover boundary.
   - Activates the successor agreement.
   - Preserves lineage (`supersedes_agreement_id` / `superseded_by_agreement_id`).

---

## 6. Rental Reservations & Inquiries

Rental Reservations manage customer booking inquiries before a formal agreement is executed:

### 6.1 Reservation Data Model

- **Customer:** Linked via controlled lookup to customer master.
- **Requested Vehicle / Category:** Optional specific vehicle or category preference.
- **Rental Mode & Cycle:** `self_drive` vs. `with_driver`; `daily` vs. `monthly`.
- **Requested Period:** Requested start and end timestamps (business calendar).
- **Estimated Financials:** Estimated rent amount, estimated security deposit, currency.
- **Logistics & Origin:** Inquiry source (`phone`, `walk_in`, `email`, `website`), operational remarks.
- **Status Lifecycle:** `draft` -> `pending` -> `confirmed` -> `converted` (or `cancelled`, `expired`).

### 6.2 Conversion to Customer Agreement

When a reservation is confirmed and the customer is ready to sign, the operator converts the reservation:
- Optimistic version checking ensures the reservation was not concurrently modified.
- An active `CustomerAgreement` is created atomically, copying the customer, currency, basis, driver mode, and planned start date.
- The reservation status transitions to `converted`, immutably recording the resulting agreement ID and transition history.

---

## 7. Vehicle Source, Custody, Handover, Return & Replacement

### 7.1 Supply Sources: Company-Owned vs. Owner-Leased

Every vehicle dispatched to a customer agreement must have proven physical and commercial coverage:
- **Company-Owned Supply:** The vehicle belongs to the business fleet. The operator flags "Company-owned vehicle". The system verifies that no conflicting customer custody exists and creates **zero owner payables**.
- **Owner-Leased Supply:** The vehicle is leased from an external owner. The operator links an active `OwnerAgreement` covering the vehicle for the planned period. The system validates that the owner agreement period covers the planned dispatch.

### 7.2 Vehicle Availability & Conflict Prevention

The `VehicleUseAvailabilityBlocker` service prevents overlapping bookings:
- Implements `VehicleAvailabilityBlockerInterface` tagged in the service container.
- Checks planned dispatch windows, active in-custody periods, and workshop admissions (`VehicleService`).
- Half-open interval matching (`starts_at <= existing_end AND ends_at >= existing_start`) guarantees that exact-boundary turnovers (e.g. return at 10:00, handover at 10:00) are permitted without false collision.

### 7.3 Handover, Return & Atomic Replacement

Operational custody tracks physical asset possession:
- **Handover:** Transitions state to `active_custody`. Records exact handover timestamp and starting odometer. Odometer cannot be lower than the vehicle's last recorded odometer reading.
- **Return:** Transitions state to `returned`. Records exact return timestamp and return odometer. Odometer must be greater than or equal to the handover odometer.
- **Atomic Vehicle Replacement:** If a vehicle breaks down or requires swap:
  - The operator provides return details for the current vehicle (return odometer, return timestamp, reason).
  - The operator selects the replacement vehicle, handover odometer, and supply source.
  - In a single database transaction, the existing use is closed, the replacement use is activated, and predecessor/successor lineage is linked.
  - **Shared Allowance:** Replacement vehicles under the same customer agreement share the agreement's monthly mileage allowance. No duplicate base rent or bonus free KM is granted.

---

## 8. Daily Running Chart Physical Evidence Register

### 8.1 Purpose & Role

The Running Chart is the **single physical operational log** of vehicle usage. It is not an invoice or payable voucher; it is the factual foundation from which billing and settlements are calculated.

### 8.2 Operational Evidence Facts

Each Running Chart records:
- **Trip Intervals:** Usage start and usage end timestamps (business timezone).
- **Odometer Observations:** Starting odometer, ending odometer, total distance.
- **Distance Segmentation:**
  - `commercial_km`: Customer billable / operational travel distance.
  - `garage_km`: Deadhead distance between company depot and customer location.
- **Air Conditioning Modes:** `non_ac`, `front_ac`, or `dual_ac`.
- **Driver Identity Tracking:**
  - **Employee Driver:** Controlled lookup to `HR` employee master; preserves employee number and name snapshot.
  - **External / Subcontracted Driver:** Name and identification reference captured as operational snapshot.
- **Operational Allowances & Time:**
  - `normal_ot_minutes`: Standard overtime hours/minutes.
  - `double_ot_minutes`: Double overtime hours/minutes.
  - `triple_ot_minutes`: Triple overtime hours/minutes.
  - `night_outs`: Count of overnight stays away from base depot.
- **Narrative Notes & Observations:** Driver log remarks, expressway pass notes, fuel observations.

### 8.3 Lifecycle & Immutability

```text
Draft ──(Finalize)──> Finalized ──(Reverse/Correct)──> Reversed
```
- **Draft:** Operational facts can be revised or corrected by fleet clerks.
- **Finalized:** Facts are frozen. Finalized charts are immediately available for commercial billing and owner settlement.
- **Reversed / Corrected:** If errors are discovered after finalization, the chart is reversed with an auditable reason. A corrected chart references the reversed predecessor.

---

## 9. Commercial Calculations & Billing Engine

### 9.1 Base Rent Billing (`actual_calendar_days_v1`)

Base rental is billed on either a Daily or Monthly cycle using deterministic calendar rules:
- **Daily Basis:** Multiplies the agreed daily rate by civil calendar days covered.
- **Monthly Basis:** Uses the agreement's start-date anniversary cycle (e.g. 15th of the month to 14th of the next month).
- **Proration Policy:** Partial monthly cycles use actual calendar days (`actual_days / days_in_month * monthly_rate`). Anchor days are preserved across 28/29/30/31-day months.
- **Idempotent Invoicing:** Once a billing period is invoiced, that date range is locked. Overlapping base charges for the same agreement are rejected.

### 9.2 Commercial Mileage Assessment & Allowance Pooling

Mileage calculation separates physical odometer readings from commercial allowances:
- **Customer Allowance Pooling:** Commercial distance from all running charts within an anniversary cycle is pooled against the agreed cycle allowance.
- **Prorated Allowance:** For partial final months, the free kilometer allowance is prorated based on actual days active.
- **Excess Mileage Billing:** Total commercial distance minus pooled allowance yields excess kilometers, billed at the agreement's `excess_km_rate`.
- **Zero-Cost Assessments:** If usage falls within the free allowance, a zero assessment is recorded to consume the allowance without creating an invoice.
- **Independent Owner Mileage:** Owner excess distance is evaluated separately using the Owner Agreement's allowance terms.

### 9.3 Overtime, Night-Out & Auxiliary Charges

- **Overtime Calculations:** Minutes logged on finalized charts are converted to hours (`minutes / 60`) and multiplied by the agreed hourly rate for each tier (Normal, Double, Triple).
- **Night-Out Charges:** Count of night-outs multiplied by the agreed daily night-out rate.
- **Reissue & Voiding:** Usage charges freeze the source chart revision and business calendar timezone. Reissuing or voiding unwinds invoice linkages safely through the `Invoice` module.

---

## 10. Vehicle Finance & Bank Leasing

Vehicle Finance manages commercial bank leases and financial hire-purchase agreements:

### 10.1 Agreement & Amortization Model

- **Financing Supplier:** Commercial bank or leasing institution (linked to `Supplier`).
- **Asset Identity:** Specific financed vehicle (linked to `Vehicle`).
- **Financial Terms:**
  - Principal financed amount.
  - Initial deposit / down payment.
  - Residual / balloon value.
  - Annual interest rate (%).
  - Interest calculation method: `flat` or `reducing_balance`.
  - Installment frequency: `monthly`, `quarterly`, `annual`.
  - Installment count & payment term days.

### 10.2 Installment Lifecycle & Payable Automation

- **Schedule Generation:** Creating an agreement generates an automated installment amortization schedule.
- **Status Progression:** Each installment progresses through `scheduled` -> `due` -> `overdue` -> `partially_paid` -> `paid` (or `waived`, `reversed`).
- **Due Status Synchronization:** An automated refresh routine scans installment due dates against current business dates to mark maturing installments as `due` or `overdue`.
- **AP Invoice Integration:** Maturing installments can be converted into inbound supplier invoices (`Invoice` module), establishing accounts payable without manual re-entry.

---

## 11. Rental Fleet Expenses & Direct Cost Allocations

Rental Expenses tracks fleet operating expenditures and governs their commercial recovery:

### 11.1 Expense Recording

Fleet expenses (fuel receipts, highway toll cards, emergency repairs, traffic fines, cleaning) are recorded with:
- Vehicle reference (controlled lookup).
- Typed category: `fuel`, `toll`, `maintenance`, `fine`, `other` (using `RentalExpenseType`).
- Financial amounts: net amount, tax amount, gross amount, currency.
- Optional supplier / petrol station reference.
- Receipt / invoice proof reference.

### 11.2 Multi-Sided Allocation Matrix

Expenses are reviewed (`draft` -> `submitted` -> `approved`) and then allocated across four commercial destinations:
1. **`Company Cost`:** Operational fleet expense absorbed directly by the company.
2. **`Customer Recovery`:** Billable to the customer. Linked to the active `CustomerAgreement` to be invoiced on the next billing run.
3. **`Owner Deduction`:** Deductible from the vehicle lessor's monthly disbursement. Linked to the `OwnerAgreement`.
4. **`Employee Reimbursement`:** Reimbursable to the driver employee through HR/Payroll.

All allocations are governed by the typed enum `RentalExpenseAllocationStatus` (`Draft`, `Confirmed`, `Invoiced`, `Reversed`).

---

## 12. Incident Evidence, Damage Risk & Private File Proofs

### 12.1 Purpose & Semantic Boundary

Rental incidents capture damage, accidents, police fines, toll violations, and vehicle abuse.
**Critical Semantic Boundary:** Recording or reviewing an incident is a **factual verification of evidence**, not a financial liability adjudication. An incident never automatically creates an invoice, debit note, or owner deduction without a separate authorized commercial decision.

### 12.2 Evidence Register & Private File Proofs

- **Incident Categorization:** Typed classifications via `IncidentType`: `fuel`, `toll`, `parking`, `repair`, `maintenance`, `accident_damage`, `penalty`, `cleaning`, `other`.
- **Linkage:** Tied to exact `VehicleUse` and its immutable history version; optionally tied to a specific `RunningChart`.
- **Review Lifecycle:** `recorded` -> `reviewed` / `confirmed` vs. `rejected`, with mandatory reason.
- **Private Tamper-Evident Proofs:**
  - Operators can attach PDF, JPEG, or PNG proof documents up to 10 MB.
  - Stored in private tenant disk storage with SHA-256 integrity checksums.
  - Delivered via streaming downloads with `no-store`, `no-sniff`, and private cache headers.
  - Files cannot be modified or replaced; reviewing the incident permanently freezes attachments.

---

## 13. Security Deposits, Receipts, Payments & Financial Ownership

### 13.1 Payment Module Authority

AutoERP enforces strict module ownership: **Payment owns all money movement**.
- Vehicle Rental records deposit requirements in the Customer Agreement.
- Deposit receipts are processed by calling the `Payment` module API.
- Customer Receipts, Owner Disbursements, allocations, and bank reconciliation live strictly in `Payment` and `Finance`.
- Rental displays remaining collection capacity (`agreed_deposit - net_receipts`) but never implements a shadow cash book.

### 13.2 Deposit Disposition & Refunds

- **Invoice Application:** Unapplied deposit balances are allocated against final rental invoices via Payment allocation workflows.
- **Deposit Refunds:** Refunds require the dedicated Payment refund workflow, linking to the original posted receipt.
- **No Automatic Forfeiture:** Deposits are never forfeited automatically without verified legal liability and explicit authorized journal entries.

---

## 14. Comprehensive Reporting & Business Intelligence

Vehicle Rental provides eight official, production-ready operational and financial reports:

| Report Name | Output & Layout | Primary Purpose & Key Metrics |
|---|---|---|
| **Running Chart 31-Day Matrix Sheet** | Landscape A4 (PDF / Print / HTML) | Official Sri Lankan monthly trip sheet (`pmdeblogsheet2.frx` / `pmcre1logsheet2.frx`). Dual-mode (Customer vs. Owner). Daily breakdown of odometers, commercial/garage KM, overtime tiers, night outs, expressway toll passes, and driver/owner signature blocks. |
| **Fleet Utilization & Status Report** | Landscape A4 | Physical fleet asset availability, custody status (In Custody, Available/Idle, Planned), ownership type (Company vs. Leased), period distance driven, and utilization rate %. |
| **Vehicle Revenue Licence Expire Report** | Portrait A4 | Regulatory compliance tracking for fleet revenue licences. Expiry dates, countdown days, owner references, and renewal alerts (Expired, Expiring in 30 Days, Valid). |
| **Rental Revenue & Commercial Billing Summary** | Landscape A4 | Customer commercial billing totals: base rent, excess distance revenue, overtime charges, night-out allowances, and fuel/toll reimbursements. |
| **Lessee's Outstanding Age Analysis** | Portrait A4 | Accounts receivable aging analysis grouped by customer, rental agreement reference, and vehicle. Breakdown across Current, 30, 60, 90, and 120+ day buckets. |
| **Vehicle Owner Disbursement & Payables Ledger** | Landscape A4 | Lessor settlements: monthly base lease entitlements, usage revenue sharing, fuel/toll deductions, and Withholding Tax (WHT) ledger statements. |
| **Fleet Vehicle Profitability & Unit Economics** | Landscape A4 | Vehicle-level profit and loss: total revenue minus direct operating costs (lessor payout, fuel, repairs, maintenance, tolls), gross margins, and per-kilometer unit economics. |
| **Rental Incident & Damage Risk Report** | Landscape A4 | Fleet risk register: incident event classifications, vehicle history, review status, and photographic proof verification audits. |

*All reports support white-label tenant branding, export to PDF (via Spatie Laravel PDF), Print Preview, and interactive HTML data grids.*

---

## 15. Concurrency, Multi-Tenancy & Audit Integrity

### 15.1 Optimistic Concurrency Control

In a multi-user enterprise environment, overlapping modifications are assumed. Every write operation in Vehicle Rental enforces optimistic concurrency:
- Mutable models carry an integer `row_version`.
- API write requests (`PUT`, `PATCH`, transition endpoints) require `expected_version`.
- Updates execute atomic version-checked queries: `WHERE id = ? AND row_version = ?`.
- Stale updates fail immediately with `409 Conflict`, leaving existing state intact.

### 15.2 Multi-Tenant Isolation

- Every database table includes `tenant_id` and `organization_unit_id`.
- Routes enforce `current.tenant`, `current.organization-unit:required`, and `tenant.feature:vehicle_rental`.
- Controller queries utilize scoped scopes (`forContext($tenantId, $orgUnitId)`); foreign keys from other tenants or organizations are rejected.

### 15.3 Append-Only Immutable History

Every major operational entity has a dedicated, append-only history audit table:
- `CustomerAgreementHistory` & `OwnerAgreementHistory`
- `VehicleUseHistory`
- `RunningChartHistory`
- `RentalReservationHistory`
- `RentalIncidentEvent`
- `VehicleFinanceStatusHistory`

Histories record the exact action, timestamp, actor ID, old/new revision snapshots, and human-readable narrative reasons. History records are permanent and cannot be updated or deleted.

### 15.4 User Experience (UX) & Ergonomic Standards

- **Zero Raw IDs:** Database IDs are strictly for internal relationships. The UI presents human-readable labels, registration numbers, customer names, and formatted references.
- **Controlled Lookups:** All relational inputs use controlled components (`LookupSelect`, `Select`) with search and pagination.
- **Tenant Business Calendar:** Dates and times are evaluated against the tenant's operational business timezone (`Asia/Colombo`). Forms default to `businessDateInputValue()`; dates format via `formatBusinessDate()`.
- **Sensible Defaults:** Forms default standard values (e.g. today's date, base exchange rate `'1.00'`) to eliminate repetitive operator keystrokes without compromising customizability.

---

## 16. Legacy Anti-Patterns & Prohibited Traps

To maintain long-term architectural integrity, the following legacy mechanisms are strictly prohibited:

1. **No Direct Vehicle-to-Agreement Foreign Keys:** Vehicles must not be directly hardcoded to customer agreements. All vehicle assignments must pass through the `VehicleUse` custody aggregate to preserve replacement history and prevent silent double-booking.
2. **No Destructive Financial Edits:** Posted invoices, payments, vouchers, or running charts must never be edited in-place or deleted. Corrections must follow governed reversal/credit/reissue workflows.
3. **No Inter-Module Workarounds:** Rental must never compensate for missing features in Invoice, Payment, or Finance by building shadow tables. Fix issues at their root source.
4. **No Artificial Owner Payables:** Never create fake lessor agreements or payable vouchers for company-owned vehicles.
5. **No Hardcoded Magic Account Numbers:** General Ledger account numbers must never be hardcoded from legacy charts. Use semantic Finance posting profiles.
6. **No Compatibility Workarounds:** Do not compromise clean architecture to preserve historical design flaws. Build the correct foundation now.

---

## 17. Definition of Done & AI-Agent Decision Rules

### AI-Agent Decision Sequence

Any developer or autonomous agent modifying Vehicle Rental must adhere to this sequence:
1. **Identify Module Responsibility:** Determine whether the change belongs in Rental or an owning module (`Invoice`, `Payment`, `Vehicle`, `Tax`, `Finance`).
2. **Check Single Source of Truth:** Verify that the rule or calculation is not duplicated elsewhere.
3. **Verify Evidence & Policies:** Consult this knowledge base. If no commercial policy exists, physical facts may be recorded, but **no monetary transaction may be invented**.
4. **Enforce Optimistic Concurrency:** Ensure all mutations pass `expected_version` and update `row_version`.
5. **Preserve Immutable History:** Record full transition snapshots in the respective append-only history table.
6. **Maintain UI Ergonomics:** Use `LookupSelect`, human-readable labels, and tenant business date utilities. Never expose raw database IDs.
7. **Append to Change Memory:** Document the completed work in `docs/changes/YYYY-MM-DD-*.md`. Never edit previous change records.

### Definition of Done Checklist

A Vehicle Rental feature or refactoring task is complete only when:
- [x] Concurrency is atomic, version-checked, and conflict-aware (`expected_version`).
- [x] Tenant and organization unit boundaries are strictly enforced.
- [x] Relational selections use controlled lookup components; zero raw IDs are exposed in the UI.
- [x] All dates and times use tenant operational business calendar utilities.
- [x] Backend tests (`php artisan test --filter=VehicleRental`) pass completely.
- [x] Frontend tests (`npx vitest run resources/js/modules/vehicle-rental`) pass completely.
- [x] TypeScript compile checks (`npm run typecheck`) report zero errors.
- [x] ESLint (`npm run lint`) reports zero errors and zero warnings.
- [x] Production asset compilation (`npm run build`) succeeds cleanly.
- [x] A new change record is appended to `docs/changes/`.
