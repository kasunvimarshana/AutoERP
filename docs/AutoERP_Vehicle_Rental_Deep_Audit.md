# AutoERP Vehicle Rental — End-to-End Deep Audit

## 1. Audit scope and evidence

This audit focuses only on the **Vehicle Rental / Integrated Rent-a-Car** domain shown across the four uploaded videos.

| Source video | Duration | Vehicle-rental evidence |
|---|---:|---|
| `1.mp4` | 40:50 | Lessee master, customer agreement, customer invoice, vehicle-owner agreement, running chart, owner payable, debit-note allocation, cheque payment and bank reconciliation |
| `Recording 2026-06-21 132314.mp4` | 41:58 | Vehicle master, lessee master, agreement, invoice/PDF, daily running chart, reports, receipt allocation, owner statement, ledger and user register |
| `ScreenVideo_03-04-2026_18-02-52.mp4` | 12:24 | Primarily workshop operations. It contributes only shared vehicle/customer/accounting concepts; it does not demonstrate a direct rental transaction lifecycle |
| `2.mp4` | 21:14 | Rental transaction menus, reconciliation listing, rental reports, running-chart report menu and integrated rental ledger; most of the later screen is visually idle |

**Total footage reviewed:** approximately **1 hour 56 minutes 26 seconds**.

### Evidence classification used in this report

- **Observed:** directly visible in a form, report, menu or transaction.
- **Derived:** a workflow connection strongly supported by multiple observed screens.
- **Recommended:** target design guidance for AutoERP; not a claim about the legacy implementation.
- **Unconfirmed:** the recordings do not provide enough evidence to define the exact rule safely.

The visual application workflow was audited frame-by-frame around every meaningful screen transition. Spoken meeting content was not used as authoritative evidence where it could not be independently verified from the visible system behavior.

---

## 2. Executive system understanding

The legacy Vehicle Rental system is an integrated operational and accounting application. It manages both sides of a rental transaction:

1. **Customer/lessee side** — the company rents a vehicle to a customer and creates invoices, receipts, debit notes and customer statements.
2. **Vehicle-owner/lessor side** — the company obtains a vehicle from an owner or leasing source and creates owner payables, deductions, payments and owner statements.
3. **Operational side** — daily running charts capture mileage, time, overtime, night-out and other usage data.
4. **Accounting side** — invoices, owner payables, receipts, payments and adjustments are linked to GL accounts and reports.

The main business chain reconstructed from all recordings is:

> **Party and fleet setup → customer agreement + owner agreement → daily running chart → customer billing → customer receipt allocation → owner payable calculation → owner deduction/adjustment → owner payment → cheque realization/bank reconciliation → operational and financial reports**

The two commercial contracts are independent but connected through the same vehicle and operating period:

- The **lessee agreement** defines what the customer is charged.
- The **vehicle-owner agreement** defines what the vehicle owner is paid.
- The difference between the two sides, after operating costs and taxes, forms the rental margin.

---

## 3. Full end-to-end Vehicle Rental lifecycle

## Phase A — Foundation and master data

### A1. Vehicle registration

**Observed fields**

- Vehicle number / registration number
- Registered owner and address
- Date of registration
- Date of first registration/tax
- Licensing authority
- Date of transfer
- Class of vehicle
- Taxation class
- Fuel type
- Type of body
- Year of manufacture
- Chassis number
- Engine number
- Colour
- Seating capacity
- Cylinder capacity
- Make and model
- Weight unladen and gross weight
- Front/rear tyre sizes
- Wheelbase, overhang, length, width and height
- Internal height
- Vehicle type
- GL asset account
- Lessor/financing code
- Revenue licence expiry
- Insurance expiry

**Important observed business meaning**

- Vehicle records are not only operational assets; they also carry accounting and ownership references.
- The visible `Vehicle Type` value includes **OWN VEHICLE**, implying the fleet can contain multiple ownership categories.
- A `Lessor's Code` field and separate vehicle-owner/leasing-company transaction menus indicate externally owned or financed vehicles are also supported.

**Recommended ownership model**

Do not store ownership as an unstructured code. Model it explicitly:

- `Owned`
- `ThirdPartyOwner`
- `LeasedOrFinanced`

Ownership must be effective-dated. A vehicle may change owner or financing arrangement over time, but historical agreements, invoices and payables must continue to reference the original ownership snapshot.

### A2. Lessee/customer registration

**Observed fields**

- Lessee code
- Name
- Address
- Contact numbers
- Contact person
- Credit limit
- Year-beginning balance
- VAT registration flag and number
- SVAT registration flag and number
- Driver salary
- Working hours for weekdays
- Working hours for Saturday
- Normal OT rate
- Double OT rate
- Triple OT rate
- Night-out rate

**Audit observation**

Commercial driver-related defaults are stored at the customer level. These may act as defaults for agreements, but the invoice screens also show agreement-level values.

**Recommended rule**

Customer-level rates should be templates only. Once an agreement is activated, the contract must own an immutable version of its commercial rates. Updating the customer master must not silently change an active or historical agreement.

### A3. Vehicle owner / lessor / leasing source

**Observed evidence**

- Separate `Agreement Register - With Vehicle Owners`.
- Separate transaction and report menus for:
  - Leasing companies
  - Vehicle owners
- Owner statement reports are vehicle-wise.
- Owner payable vouchers are calculated from the vehicle agreement and running data.

**Recommended party design**

Use one Party foundation with role assignments rather than duplicate unrelated customer/owner/supplier tables:

- Party
- Customer role
- Vehicle-owner role
- Supplier role
- Leasing-company role

The UI must still show separate focused screens, but identity, addresses and contacts should remain a single source of truth.

### A4. Financial and operational reference data

**Observed**

- GL account codes are selected throughout agreement, invoice, payable, receipt and payment forms.
- Bank codes and cash/cheque-in-hand accounts are selected during payments and receipts.
- Tax percentages and tax account codes are visible in agreements and invoices.
- Driver identity is selected in running charts.

**Required reference masters**

- Chart of accounts
- Tax definitions and effective periods
- Banks and bank accounts
- Payment methods
- Drivers
- Vehicle classes/types
- Rental charge types
- Agreement types
- Number sequences

---

## Phase B — Commercial agreements

## B1. Lessee/customer rental agreement

The customer agreement is the primary source for customer billing.

### Observed agreement fields

**Identity and period**

- Agreement date
- Agreement type
- Agreement active flag
- Agreement close flag
- Lessee/customer
- Agreement number
- Vehicle registration number
- Vehicle type
- Agreement execution date
- Agreement starting date
- Agreement ending date

**Commercial basis**

- Agreement format: Company or Personal
- Agreement based on: Monthly or Daily
- Maximum number of kilometres
- Rate for maximum number of kilometres
- Rate for excess kilometres
- With-driver option
- Non-AC, Front-AC and Dual-AC rate variants on relevant screens

**Driver terms**

- Driver salary
- Working hours for weekdays
- Working hours for Saturday
- Normal OT rate per hour
- Double OT rate per hour
- Triple OT rate per hour
- Night-out rate

**Tax and account mapping**

- VAT calculation flag
- Invoice type: VAT invoice or SVAT invoice
- VAT percentage
- SSCL percentage
- NBT GL account code
- VAT/SVAT GL account code
- Rental income account
- Excess-kilometre income account
- Parking and other-charge recovery account

**Additional contractual information**

- Introduced by
- Lessee NIC/passport number
- Lessee driving-licence number
- Security deposit
- Agreement description/condition lines are visible in later agreement screens

### Deep audit findings

1. **Agreement terms are the core billing source.** The invoice screen repeats agreement dates, basis, vehicle, tax, maximum kilometres and driver rates. This indicates the invoice consumes agreement configuration.
2. **The system supports multiple pricing dimensions.** Monthly/daily basis, air-conditioning mode, kilometre allowance and driver terms can all affect the invoice.
3. **The legacy UI allows account selection inside the agreement.** This is operationally flexible but exposes raw accounting internals to rental users.
4. **Active and closed are separate flags.** This can create invalid combinations unless controlled by a real state machine.
5. **Agreement Type is numeric in the UI.** The business meaning is not human-readable and should not be exposed as a raw code.

### Recommended state model

`Draft → PendingApproval → Active → Suspended → Expired → Closed`

Rules:

- Only an `Active` agreement may accept new running-chart entries.
- `Expired` is system-derived from the end date but may require a controlled extension.
- `Closed` means no new operations; historical billing and settlement remain accessible.
- An agreement cannot be activated until mandatory commercial, tax and accounting configuration is valid.
- Same-vehicle overlapping active agreements must be blocked or explicitly authorized according to a documented replacement/shared-use policy.

## B2. Vehicle-owner agreement

The owner agreement defines the payable side of the same vehicle operation.

### Observed fields

- Agreement type
- Agreement date
- Active/closed indicators
- Vehicle owner/lessor
- Agreement number
- Vehicle registration number
- Vehicle type
- Execution/start/end dates
- Monthly/daily basis
- Maximum kilometres
- Rate for maximum kilometres
- Excess-kilometre rate
- With-driver option
- Driver salary
- Agreement description and multiple condition lines
- Account mappings for:
  - Rental amount/payable
  - Excess-kilometre amount
  - Refundable driver salary
  - Refundable driver OT
  - Refundable driver night-out
  - Parking and other charges payable/recoverable

### Derived relationship

The owner agreement is not merely a vehicle master attribute. It is a commercial contract that may have a different period, rate and reimbursement structure from the customer agreement.

### Recommended rule

Never calculate owner payables from the customer agreement. Both agreements should independently consume the same finalized operational usage records and calculate their own financial outcomes.

---

## Phase C — Daily vehicle operation

## C1. Daily Running Chart — Normal

The running chart is the central operational source for mileage- and time-based billing.

### Observed fields

**Relationship context**

- Vehicle registration number
- Lessee agreement number and basis
- Vehicle-owner agreement number and basis
- Lessee/customer code and name
- Owner/lessor code and name

**Daily operation**

- Rate to apply for daily basis:
  - Non-AC
  - Front-AC
  - Dual-AC
- Driver ID and name
- Start date
- Finish date
- Day of week
- OT type
- Start mileage
- Finish mileage
- Kilometre reading
- Start time
- Finish time
- Number of working hours
- Particulars of hire
- Number of night-outs
- Other charges
- Garage mileage

**Continuation controls**

- Clear both mileage
- Continue with finish mileage
- Clear both time
- Clear start time
- Clear finish time
- Continue both time

These continuation options indicate the system supports consecutive daily records where a prior finishing value may become the next starting value.

### Observed reports/calculation modes

Running-chart report parameters show:

- Monthly basis
- Daily basis
- Excess-kilometre calculation:
  - Normal
  - By Hire
  - By Log Transaction
  - Normal (Value)
  - By Hire (Value)
  - By Log Transaction (Value)

### Deep audit findings

1. **The running chart links both contracts.** This is the shared operational source for customer invoice and owner payable.
2. **Mileage continuity is a business-control issue.** The legacy UI provides convenience controls but does not visibly show anomaly warnings.
3. **A single record may span start and finish dates.** Overnight hires and night-outs are supported.
4. **The exact meaning of each excess-kilometre calculation mode is not fully proven by the recordings.** These modes must be clarified before implementation.
5. **Replacement-vehicle handling exists in reports.** Menus show original/replacement vehicle log sheets, although the actual replacement transaction form was not demonstrated.

### Required validations

- Vehicle and agreements must be active for the operation date.
- Finish odometer cannot be below start odometer unless a documented meter replacement/reset event exists.
- Odometer continuity must be checked against the prior finalized entry.
- Finish time/date must not precede start time/date.
- Duplicate or overlapping running entries for the same vehicle must be conflict-checked.
- Driver overlap across vehicles must be detected where applicable.
- Night-out, overtime and other-charge values must be non-negative and supported by appropriate permission/evidence.
- Finalized or billed entries must not be edited.

### Recommended status model

`Draft → Submitted → Approved → Finalized → Billed`

A correction after finalization must be a separate adjustment or reversal, not an overwrite of the original operational record.

---

## Phase D — Customer billing

## D1. Credit Invoice

### Observed invoice inputs and totals

**Identity**

- Lessee/customer
- Invoice heading
- Agreement number
- Agreement vehicle
- Start date
- Finish date
- Invoice date
- Invoice number
- Invoice sequence number

**Usage totals**

- Number of total kilometres
- Number of total excess kilometres
- Number of normal OT hours
- Number of double OT hours
- Number of triple OT hours
- Number of night-outs
- Number of days/hires

**Calculated financial components**

- Rental income
- Excess-kilometre income
- Refundable driver salary
- Refundable driver OT
- Refundable driver night-outs
- SSCL
- VAT
- Total value

**Agreement snapshot displayed inside the invoice**

- Active status and agreement type
- Agreement/execution/start/end dates
- Monthly/daily basis
- Company/personal format
- VAT/SVAT invoice type
- With-driver flag
- Maximum kilometres
- Vehicle type
- Base rate and excess-kilometre rate
- Driver salary, work hours, OT rates and night-out rate

**Actions**

- Import Running Chart Data
- Process
- Create Invoice
- Delete Invoice
- Find
- Print
- Optional PDF generation confirmation

**Calculation mode options**

- Excess KM Calculation Normal
- Excess KM Calculation By Hire
- Excess KM Calculation By Log Transaction

### Derived billing pipeline

1. Select customer agreement and billing period.
2. Import eligible running-chart records.
3. Aggregate mileage, overtime, night-outs, days/hires and other charges.
4. Apply agreement rates.
5. Calculate taxes.
6. Preview/process totals.
7. Create/post invoice.
8. Print or generate PDF.

### Calculation requirements

The exact legacy formula cannot be safely reconstructed for every mode, but the target calculation engine must provide an explicit breakdown.

**Monthly basis, conceptual formula**

`BaseRental + ExcessKM + DriverRecoveries + OtherRecoveries + Taxes`

**Daily basis, conceptual formula**

`Sum(ApplicableDailyRate × BillableDaysOrHires) + ExcessKM + DriverRecoveries + OtherRecoveries + Taxes`

Where every component must show:

- Source running-chart records
- Quantity
- Rate
- Tax basis
- Rounding
- Final amount

### Critical billing controls

- A running-chart entry may be billed only once per charge context.
- The invoice must retain a snapshot of the agreement and tax configuration used.
- Reprocessing must be idempotent.
- Posted invoices cannot be deleted or edited.
- Cancellation must use a reversal/credit-note process.
- Invoice number allocation must be concurrency-safe.
- Invoice date must be inside an open accounting period.
- Credit-limit warnings or blocks must be explicit and configurable.

## D2. Printed invoice

The recording shows a generated tax invoice containing:

- Company details
- Invoice date, invoice number and agreement/reference number
- Customer name/address
- Vehicle number
- Rental description
- Billing period
- Rate and amount
- Excess mileage line where applicable

**Recommended output rule**

The PDF must be generated from the posted invoice snapshot, not from live agreement/customer data. Historical PDFs must remain reproducible even after master-data changes.

---

## Phase E — Customer collection and allocation

## E1. Lessee Receipt

### Observed fields

- Date
- Receipt number
- Sequence number
- Cash-receipt indicator
- Cash/cheque-in-hand account code
- Cheque number
- Receipt amount
- Description and detail lines
- Lessee control account
- Lessee/customer
- Vehicle registration number
- Lessee amount

### Receipt allocation screen

- Lessee/customer
- Reference number
- Sequence
- Original receipt amount
- Receipt amount
- Allocated reference
- Reference to settle
- Amount to be allocated
- Balance on the reference
- Allocate/remove-allocation actions

### Required controls

- A receipt may be unallocated, partially allocated or fully allocated.
- One receipt may settle multiple invoices.
- One invoice may receive multiple receipts.
- Allocation cannot exceed either receipt unallocated balance or invoice outstanding balance.
- Allocation and accounting entries must be atomic.
- Two users must not allocate the same remaining balance simultaneously.
- Removing an allocation from a posted/reconciled receipt must require a controlled reversal, not silent deletion.
- Cheque receipts require status handling such as received, deposited, realized and dishonoured.

### Recommended receipt states

`Draft → Posted → PartiallyAllocated → FullyAllocated → Reconciled`

Possible controlled outcomes:

- `Dishonoured`
- `Reversed`

---

## Phase F — Vehicle-owner settlement

## F1. Payment Payable Voucher

This screen calculates the amount owed to the vehicle owner.

### Observed fields and components

- Agreement vehicle
- Agreement number
- Vehicle owner/lessor
- Vehicle
- Start and finish dates
- Payment payable date/number
- Invoice sequence number
- Agreement type/basis/dates
- Maximum kilometres
- Base rate and excess-kilometre rate
- Driver salary
- Total kilometres
- Excess kilometres
- Normal/double/triple OT
- Night-outs
- Days/hires
- Expense components:
  - Rental expenses
  - Excess-kilometre expenses
  - Refundable driver salary
  - Refundable driver OT
  - Refundable driver night-outs
  - Total value
- Payable description

**Actions**

- Import Running Chart Data
- Process
- Create Payable Voucher
- Delete Payable Voucher
- Find
- Print

### Derived payable pipeline

1. Select owner agreement and settlement period.
2. Import finalized running-chart data.
3. Calculate contractual base rental and usage amounts.
4. Add refundable driver-related values.
5. Apply deductions/adjustments.
6. Post payable voucher.
7. Include the voucher in owner statement and payment processing.

### Critical control

The same running-chart data may feed both customer invoice and owner payable, but the two calculations must remain independent. The system must prevent duplicate owner settlement for the same usage period and charge component.

## F2. Vehicle-owner debit note — Fuel & Repair

### Observed fields

- Date
- Debit-note number/reference
- Sequence number
- Owner/lessor control account
- Vehicle number
- Owner/lessor
- Description and detail
- Total debit amount
- Fuel or Repair classification
- Fuel-chit/invoice number
- Credit GL account
- Debit GL account
- Credit GL amount

### Allocation

A separate allocation screen links the debit note to an existing owner reference/payment and shows:

- Owner/lessor code
- Reference number
- Sequence
- Original payment amount
- Payment amount
- Allocated reference
- Bill/reference to settle
- Amount to allocate

### Business meaning

Fuel, repair or similar expenses can be recovered/deducted from amounts otherwise payable to the vehicle owner.

### Required controls

- Debit note must reference the vehicle and supporting source document.
- Deduction cannot be applied twice.
- Allocation cannot exceed the open owner payable/reference.
- A posted owner debit note is immutable.
- Correction must use a credit note or reversal.
- Attachments should be supported for invoices, repair documents or fuel chits.

## F3. Vehicle-owner statement

Observed owner statements are vehicle-wise and include:

- Opening balance
- Rental and other payable amounts
- Settlement/payment entries
- Repair deductions
- Balance over the period

This confirms that the owner subledger is maintained by both owner and vehicle context.

**Recommended reporting rule**

The accounting control account remains party-based, while vehicle is a mandatory analytical dimension for vehicle-specific owner agreements and settlements.

---

## Phase G — Payments, banking and reconciliation

## G1. Cheque payment

### Observed fields

- Date
- Payment voucher number
- Sequence number
- Bank code
- Cheque number
- Cross/bearer option
- Account-payee-only option
- Name of payee
- Payment amount
- Description and detail lines
- Debit GL account
- Realized date

Separate screens are visible for lessee-related cheque payments and general-ledger cheque payments.

### Recommended payment lifecycle

`Draft → Approved → Issued → Presented → Realized → Reconciled`

Controlled alternative outcomes:

- `Cancelled`
- `Stopped`
- `Returned`
- `Reversed`

### Required controls

- Cheque number must be unique per bank account/cheque book.
- Bank and currency must be explicit.
- Cross/bearer/account-payee states must be validated rather than represented by conflicting flags.
- Realization and reconciliation dates must not modify the original payment date.
- A reconciled payment cannot be edited.
- Bank reconciliation must be a separate event with an audit trail.

## G2. Bank reconciliation

The video shows an `Edit Cheque Payment For Bank Reconciliation` screen with realized-date handling.

**Audit concern**

The word `Edit` suggests the same payment record may be modified during reconciliation. The target design should instead create a reconciliation event or update a restricted reconciliation state without allowing financial fields to change.

---

## Phase H — Rental reporting

## H1. Running-chart reports

Observed report menu items:

- Log Sheet — Lessee
- Log Sheet — Lessor
- Log Sheet — Replaced Vehicles (giving original vehicle)
- Log Sheet — Replaced Vehicles (giving replaced vehicle)
- Vehicles with log entries check
- Driver-wise driver's OT calculation
- Self-drive vehicle movement

Observed running-chart output includes:

- Date
- Start/finish mileage
- Mileage/computed mileage
- Start/finish times
- Overtime hours/type
- Night-out
- Remarks
- Totals for kilometres, excess kilometres, overtime, night-outs and garage kilometres

## H2. Financial and operational reports

Observed report groups:

- Register listing
- General-ledger reports
- Lessor reports — leasing companies
- Lessor reports — vehicle owners
- Lessee reports
- Vehicle report
- Running-chart report
- Vehicle statement of account for a date range
- Integrated Rent-a-Car ledger account report
- Invoice listing by type/date/vehicle
- Processed and reconciled settlement listing

### Recommended reporting architecture

Operational reports and financial reports should share the same posted source records, but they should be separate read models:

- Operational usage dashboard
- Customer billing report
- Owner payable report
- Vehicle profitability report
- Customer aging
- Owner payable aging
- Receipt allocation report
- Cheque status and bank reconciliation report
- Agreement expiry report
- Licence/insurance expiry report
- Odometer anomaly report
- Replacement-vehicle report

---

## 4. Consolidated domain model

## Parties and identity

- `Party`
- `PartyContact`
- `CustomerProfile`
- `VehicleOwnerProfile`
- `LeasingCompanyProfile`
- `DriverProfile`

## Fleet

- `Vehicle`
- `VehicleOwnershipPeriod`
- `VehicleLegalDocument`
- `VehicleOdometerEvent`
- `VehicleReplacementAssignment`

## Agreements

- `CustomerRentalAgreement`
- `CustomerRentalAgreementVersion`
- `OwnerRentalAgreement`
- `OwnerRentalAgreementVersion`
- `AgreementChargeRule`
- `AgreementDriverRule`
- `AgreementTaxSnapshot`
- `AgreementAccountMapping`

## Operations

- `RunningChart`
- `RunningChartCharge`
- `RunningChartApproval`
- `DriverAssignment`

## Billing and receivables

- `RentalInvoice`
- `RentalInvoiceLine`
- `RentalInvoiceSourceLink`
- `CustomerReceipt`
- `CustomerReceiptAllocation`
- `CustomerDebitNote`
- `CustomerCreditNote`

## Owner payables

- `OwnerPayableVoucher`
- `OwnerPayableLine`
- `OwnerPayableSourceLink`
- `OwnerDebitNote`
- `OwnerCreditNote`
- `OwnerAllocation`

## Banking and accounting

- `Payment`
- `Cheque`
- `BankReconciliationEvent`
- `JournalEntry`
- `JournalLine`
- `AccountingPeriod`
- `DocumentNumberSequence`

## Audit

- `AuditEvent`
- `DocumentStatusHistory`
- `ApprovalDecision`
- `Attachment`

### Relationship rules

- A vehicle can have many ownership periods, but only one effective ownership state at a given instant unless a documented co-ownership model exists.
- A vehicle can have many agreements over time.
- Every running chart must reference the effective customer agreement and owner agreement where applicable.
- Running-chart source links must prevent duplicate invoice/payable consumption.
- Every posted financial document must reference a balanced journal entry.
- Every allocation must reference both source and target documents.
- Every reversal must reference the original transaction.

---

## 5. Accounting behavior — observed and inferred

The screens prove that GL accounts are mapped and ledger reports are produced. They do not reveal every debit/credit line. The following is the expected conceptual posting and must be verified with accounting stakeholders before implementation.

### Customer invoice — inferred

- Debit: Customer/Lessee control
- Credit: Rental income
- Credit: Excess-kilometre income
- Credit: Driver/other recoveries where applicable
- Credit: Tax payable accounts

### Customer receipt — inferred

- Debit: Cash, bank or cheques-in-hand
- Credit: Customer/Lessee control

### Owner payable voucher — inferred

- Debit: Rental expense
- Debit: Excess-kilometre expense
- Debit: Refundable driver-related expenses
- Credit: Vehicle-owner/Lessor payable control

### Owner debit note — inferred

The exact posting depends on whether the note recovers an expense or reduces a payable. It must be configured by debit-note type and must not be hardcoded in the UI.

### Owner payment — inferred

- Debit: Vehicle-owner/Lessor payable control
- Credit: Bank or cash

### Mandatory accounting controls

- Every journal batch balances.
- All postings are atomic with the source document.
- Closed periods reject posting.
- Source document and journal are immutable after posting.
- Reversal creates equal and opposite entries.
- Vehicle, agreement, party and cost centre are retained as reporting dimensions.

---

## 6. Cross-cutting audit findings

## Critical

### C1. Posted documents appear to expose Edit/Delete actions

Invoice, voucher, receipt, debit-note and payment forms use a generic Add/Edit/Delete navigation bar. The recordings do not prove that the backend blocks unsafe changes.

**Target rule:** only drafts may be edited/deleted. Posted documents require reversal or an adjustment document.

### C2. Authentication design is unsafe by modern standards

A `Password Register` shows login name, password/re-enter-password fields and numeric user level.

**Target rule:** secure password hashing, MFA readiness, named roles, granular permissions, session controls and append-only security audit logs.

### C3. Billing, payable and GL operations must be atomic

A failure between source calculation and GL posting would corrupt financial integrity.

**Target rule:** one database transaction per posting operation, idempotency keys for retries and balanced journal validation before commit.

### C4. Allocation concurrency is not visibly protected

Two users could potentially allocate the same invoice/payable balance.

**Target rule:** lock or version-check allocation balances inside the database transaction.

## High

### H1. Raw codes dominate primary workflows

Customer codes, vehicle codes, agreement numbers and GL codes are frequently the primary selectors.

**Target:** human-readable searchable selectors; codes remain secondary metadata.

### H2. Commercial terms are repeated across masters, agreements and invoices

Driver rates and tax/account information appear in several places.

**Target:** versioned agreements are the source of truth; invoices retain immutable snapshots.

### H3. Tax setup is embedded directly in transaction forms

VAT, SVAT, SSCL and older NBT account fields are visible.

**Target:** effective-dated tax rules with jurisdiction, tax type, percentage, account mapping and historical snapshots.

### H4. Date presentation is ambiguous

Dates such as `03/04/2026` are ambiguous.

**Target:** store ISO dates and display unambiguous values such as `03 Apr 2026`.

### H5. Calculation traceability is weak

Totals are visible, but detailed quantity × rate × source explanations are limited.

**Target:** calculation preview and stored breakdown for each invoice/payable line.

### H6. Agreement and record-state logic is represented by flags

Active and closed flags can produce inconsistent combinations.

**Target:** explicit state machines and controlled transitions.

### H7. Replacement vehicle functionality is under-specified

Reports exist, but the transaction flow was not demonstrated.

**Target:** define original/replacement vehicle period, customer pricing, owner payable ownership, odometer continuity and report attribution before implementation.

## Medium

- Dense forms show many irrelevant zero-value fields.
- Mandatory fields and dependencies are not clearly communicated.
- Record-by-record Next/Previous navigation is slow.
- Search/filtering is secondary.
- Approval and posting status are not visually prominent.
- Generic button bars hide the risk level of actions.
- Sensitive business data is visible in meeting recordings.

---

## 7. Required permissions and separation of duties

Recommended named permissions:

- View rental masters
- Manage customer master
- Manage vehicle master
- Manage vehicle-owner master
- Draft customer agreement
- Approve/activate customer agreement
- Draft owner agreement
- Approve/activate owner agreement
- Enter running chart
- Approve/finalize running chart
- Generate invoice preview
- Post invoice
- Reverse invoice
- Record receipt
- Allocate receipt
- Post owner payable
- Post owner adjustment
- Approve payment
- Issue cheque
- Reconcile bank transaction
- View operational reports
- View financial reports
- Manage tax/account mapping
- Manage users and roles

Critical separation:

- The person who drafts an agreement should not automatically approve it where segregation is required.
- The person who prepares a payment should not be the only approver.
- Bank reconciliation should be independent from payment preparation.
- Security administration must not be controlled through a generic numeric user level.

---

## 8. Target validation matrix

### Vehicle

- Unique active registration number.
- Valid ownership period.
- Insurance and revenue-licence expiry warnings.
- Vehicle cannot be scheduled when inactive, disposed or unavailable.

### Customer agreement

- Valid customer and vehicle.
- Start date ≤ end date.
- No unauthorized overlapping active agreement.
- Monthly/daily pricing fields must match the selected basis.
- Driver rates required only when with-driver is enabled.
- Tax and GL mapping must be complete before activation.
- Deposit cannot be negative.

### Owner agreement

- Valid owner and vehicle ownership period.
- Agreement period must fit the owner/vehicle relationship.
- Payable rate and expense mappings required before activation.
- Duplicate settlement terms must be conflict-checked.

### Running chart

- Date inside valid agreement period.
- Odometer and time order valid.
- No overlapping vehicle operation.
- No duplicate finalized log.
- Prior odometer continuity checked.
- Finalized logs immutable.

### Invoice

- Only eligible, finalized, unbilled sources included.
- Calculation breakdown stored.
- Unique invoice number.
- Open accounting period.
- Balanced journal.
- Posted invoice immutable.

### Receipt/allocation

- Receipt amount > 0.
- Valid payment method/account.
- Allocation does not exceed balances.
- Concurrency-safe allocation.
- Reconciled receipt immutable.

### Owner payable

- Only eligible, finalized, unsettled sources included.
- No duplicate payable source consumption.
- Deductions supported by source documents.
- Balanced journal and open period.

### Cheque/reconciliation

- Unique cheque number per bank account.
- Valid state transition.
- Realized/reconciled dates logical.
- Financial amount/account cannot change after issue/posting.

---

## 9. Recommended AutoERP screen flow

### Fast primary workflow

1. Search/select vehicle.
2. Show current customer agreement, owner agreement and availability.
3. Create daily running entry with prior odometer/time prefilled.
4. Submit and approve running entry.
5. Generate customer invoice preview with a transparent calculation breakdown.
6. Post invoice.
7. Generate owner payable preview from the same finalized usage source.
8. Post owner payable.
9. Record and allocate customer receipt.
10. Prepare, approve and reconcile owner payment.

### Screen design rules

- Never ask users to type raw foreign-key IDs.
- Show vehicle number, customer/owner name and agreement label.
- Show codes only as secondary information.
- Hide irrelevant driver/tax/rate fields.
- Put accounting mappings in protected configuration or an advanced section.
- Show source usage records and calculation lines before posting.
- Display status and permitted next action clearly.
- Require explicit confirmation for posting, reversal, allocation removal and reconciliation.

---

## 10. Video traceability map

## `1.mp4`

- ~02:30 — Lessee register
- ~03:30 — Customer/lessee agreement
- ~05:00–06:30 — Credit invoice and calculation fields
- ~09:30 — Agreement rate/account variants
- ~11:00 — Vehicle-owner agreement
- ~13:30 — Daily running chart
- ~16:30 — Owner payment payable voucher
- ~18:30 — Running chart report
- ~20:30 — Invoice-listing filter
- ~24:00 — Vehicle-owner agreement details
- ~25:30 — Vehicle-wise owner statement
- ~26:30 — Owner payable voucher
- ~27:30–32:30 — Fuel/repair debit note and GL selection
- ~33:30 — Debit-note allocation
- ~34:30 — Lessee cheque payment
- ~37:00 — General-ledger cheque payment
- ~39:30 — Bank-reconciliation edit/realization

## `Recording 2026-06-21 132314.mp4`

- ~01:00 — Vehicle register
- ~02:00 — Lessee register
- ~03:30 — Customer agreement
- ~06:00 — Credit invoice
- ~08:00 — Printed invoice
- ~11:00 — Daily running chart
- ~14:00 — Invoice with driver terms
- ~15:30 — Password/user register
- ~18:30 — Running-chart report parameters
- ~19:00 and ~23:30 — Running-chart report output
- ~22:30 — Calculation-mode report parameters
- ~26:30 — Lessee receipt
- ~27:30 — Receipt allocation
- ~28:30 — Existing receipt record
- ~30:30 — Vehicle-owner agreement
- ~32:00 — Agreement condition fields
- ~33:30 — Vehicle-owner statement
- ~34:00 — Vehicle statement report parameters
- ~37:30 — Integrated rental ledger report
- ~40:30 — Invoice creation/PDF confirmation

## `ScreenVideo_03-04-2026_18-02-52.mp4`

- No direct rental agreement, running-chart, rental invoice, owner payable or receipt-allocation flow is demonstrated.
- The video supports only shared vehicle/customer/accounting architecture and should not be used as authority for rental-specific rules.

## `2.mp4`

- ~00:00 — Rental transaction menus, including vehicle-owner and lessee transactions
- ~02:30 — Processed/reconciled settlement listing
- ~04:30 — Rental report groups and running-chart report submenu
- ~05:00 — Integrated rental ledger report
- After ~05:20 — no additional meaningful visible rental forms; the screen remains mostly static

---

## 11. Requirements that remain unconfirmed

These points must be clarified before implementation; guessing would risk incorrect billing or accounting.

1. Exact formulas for each excess-kilometre mode: Normal, By Hire and By Log Transaction.
2. Meaning of the corresponding `(Value)` report modes.
3. Monthly-rental proration rules for partial months.
4. Whether maximum kilometres are monthly, agreement-period, per-hire or configurable.
5. Exact treatment of garage mileage.
6. Exact use of normal/double/triple OT type and day/holiday rules.
7. Whether driver rates come from customer, agreement, driver master or a precedence chain.
8. Replacement-vehicle transaction workflow and who is billed/paid during replacement.
9. Security-deposit receipt, adjustment and refund lifecycle.
10. Credit-limit enforcement behavior.
11. Exact GL postings for owner fuel/repair debit notes.
12. Tax-calculation order, rounding and historical tax handling.
13. Agreement overlap exceptions and vehicle-sharing rules.
14. Approval requirements before invoice/payable posting.
15. Whether `Delete` is allowed only before posting in the legacy system.
16. Cheque dishonour and cancellation workflows.
17. Multi-currency support, if any.
18. Exact role meanings behind the numeric `User Level`.

---

## 12. Implementation priority

### Phase 1 — Correct foundation

- Parties and roles
- Vehicle registry and effective-dated ownership
- GL/tax/bank reference data
- Customer and owner agreement versioning
- Secure roles/permissions
- Audit trail and document-number infrastructure

### Phase 2 — Rental operations

- Running chart
- Odometer continuity
- Driver assignment
- Approval/finalization
- Replacement-vehicle model after clarification

### Phase 3 — Billing and payables

- Transparent calculation engine
- Customer invoice
- Owner payable
- Tax snapshots
- Atomic GL posting

### Phase 4 — Cash, allocations and banking

- Receipts
- Allocation engine
- Owner adjustments
- Payments and cheque lifecycle
- Bank reconciliation

### Phase 5 — Reports and controls

- Running-chart reports
- Customer/owner statements
- Vehicle profitability
- Aging and reconciliation
- Agreement/document expiry alerts
- Audit and exception reports

---

## 13. Final audit conclusion

The videos provide a strong functional blueprint for a two-sided rental business: customer billing and vehicle-owner settlement are driven by a common daily usage record but governed by separate agreements. The most important target architecture decision is to make agreements versioned, running charts controlled and finalized, calculations transparent, and every financial posting immutable and atomic.

The new AutoERP implementation should preserve the demonstrated business capabilities but must not clone the legacy desktop structure, raw-code-driven UX, numeric security levels or potentially mutable accounting behavior. The correct foundation is a modular rental domain with explicit states, human-readable relationships, concurrency-safe allocations, balanced journal posting and append-only audit history.
