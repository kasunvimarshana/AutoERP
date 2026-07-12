# AutoERP Vehicle Rental — Complete End-to-End Deep Audit (Lessor + Lessee)

## 1. Audit scope

This report re-audits all four uploaded videos with a strict focus on the complete Vehicle Rental lifecycle.

| Video | Duration | Rental relevance |
|---|---:|---|
| `1.mp4` | 40:50 | Strong evidence for lessee master/agreement/invoice, vehicle-owner agreement, running chart, owner payable, owner adjustments, allocations, cheque payments, and bank reconciliation |
| `Recording 2026-06-21 132314.mp4` | 41:58 | Strong evidence for vehicle/lessee masters, lessee agreement, invoice/PDF, running chart, receipt allocation, vehicle-owner agreement/statement, ledger, and user security register |
| `2.mp4` | 21:14 | Strong menu/report evidence for lessor and lessee transactions, payments, receipts, debit/credit notes, payable processing, allocation-error checks, GL reconciliation checks, and report coverage |
| `ScreenVideo_03-04-2026_18-02-52.mp4` | 12:24 | Workshop-focused. It confirms shared vehicle/customer/accounting patterns but does not demonstrate a direct rental lifecycle |

**Total reviewed footage:** approximately 1 hour 56 minutes 26 seconds.

### Evidence labels

- **Observed:** directly visible in a screen, menu, form, or report.
- **Derived:** strongly supported by multiple observed screens.
- **Recommended:** clean target behavior for AutoERP, not a claim about the legacy implementation.
- **Unconfirmed:** cannot be safely concluded from the videos.

The visible application flow was reviewed across the full timeline. Rules not demonstrated by the videos are marked as unconfirmed rather than guessed.

---

## 2. Correct domain terminology

### Lessor

The party that provides the vehicle to the rental company. The legacy system separates:

- Lessor — Vehicle Owner
- Lessor — Leasing Company

This side is primarily a **payable/subledger** side, although the menu also contains lessor receipts, debit notes, and credit notes.

### Lessee

The customer/hirer receiving the vehicle. This side is primarily a **receivable/subledger** side, although the menu also contains lessee cash/petty-cash/cheque payment vouchers, which appear to support refunds or other amounts payable back to the lessee.

### Important semantic correction

The user-requested “lessor invoice” is not presented as a normal supplier invoice in the visible workflow. The principal lessor billing document is called:

> **Payment Payable Voucher** / **Payment Payable Processing**

For a maintainable design, this should be modeled as an **Owner Payable Voucher** or **Self-Billed Owner Settlement**, not mislabeled as a customer-style invoice.

---

## 3. Complete end-to-end lifecycle

```text
Reference setup
  ↓
Party setup: lessor + lessee + driver
  ↓
Vehicle registration and ownership period
  ↓
Lessor agreement
  ↓
Lessor vehicle assignment/allocation
  ↓
Lessee agreement
  ↓
Lessee vehicle assignment/allocation
  ↓
Daily running chart / operational usage
  ↓
┌───────────────────────────────┬────────────────────────────────┐
│ Lessee receivable side        │ Lessor payable side            │
│ Invoice calculation           │ Payable calculation            │
│ Invoice posting               │ Payable voucher posting        │
│ Receipt / credit allocation   │ Debit/credit-note allocation   │
│ Customer balance              │ Owner balance                  │
└───────────────────────────────┴────────────────────────────────┘
  ↓
Cash / cheque / bank processing
  ↓
Bank realization and reconciliation
  ↓
Operational, subledger, GL, tax, and profitability reports
```

### Central architectural fact

The two sides must use the same finalized operational usage but different contracts:

- **Lessee agreement:** determines what the customer is charged.
- **Lessor agreement:** determines what the owner/leasing company is paid.
- **Running chart:** proves the actual vehicle usage.

The lessor and lessee calculations must remain independent. Customer revenue must never be used as the formula source for owner payable.

---

## 4. Master and reference setup

### 4.1 Observed register menu

The legacy register menu includes:

- Password Register
- Company Register
- Cost Center Register
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

### 4.2 Vehicle register — observed data

- Vehicle registration number
- Registered owner and address
- Registration and transfer dates
- Vehicle class/type/body type
- Fuel type
- Manufacture year
- Chassis and engine numbers
- Colour
- Seating and cylinder capacity
- Make/model
- Vehicle dimensions and weights
- Tyre sizes
- Asset/GL reference
- Lessor code
- Revenue licence expiry
- Insurance expiry

### Audit gaps

1. Ownership is mixed directly into the vehicle master instead of being effective-dated.
2. The same vehicle may change owner or financing source, but the screen does not show historical ownership periods.
3. Asset accounting, legal registration, owner relationship, availability, and rental configuration are mixed into one dense record.
4. A single `Lessor Code` cannot safely represent ownership history or co-ownership.
5. Expiry fields are visible but no demonstrated blocking/warning workflow exists.

### Correct foundation

Use:

- `Vehicle`
- `VehicleOwnershipPeriod`
- `VehicleLegalDocument`
- `VehicleAvailabilityEvent`
- `VehicleOdometerEvent`

A vehicle must have one effective owner/financing relationship for a given date unless an explicitly defined co-ownership model exists.

---

# PART A — LESSOR END-TO-END AUDIT

## 5. Lessor agreement

### 5.1 Observed agreement types

- Agreement with Vehicle Owner
- Agreement with Leasing Company

The screens are similar but are maintained as separate legacy registers and transaction/report branches.

### 5.2 Observed fields

- Agreement type
- Agreement date
- Agreement active flag
- Agreement close flag
- Lessor/owner/leasing-company code
- Agreement number
- Vehicle registration number
- Vehicle type
- Agreement execution date
- Start date
- End date
- Agreement basis: Monthly or Daily
- Maximum kilometres
- Rate for maximum kilometres
- Excess-kilometre rate
- With-driver option
- Driver salary
- Agreement description
- Multiple condition lines
- VAT calculation flag
- Account mappings for:
  - Rental payable/expense
  - Excess-kilometre payable/expense
  - Refundable driver salary
  - Refundable driver OT
  - Refundable driver night-out
  - Parking and other charges

### 5.3 Expected lessor agreement workflow

1. Select an active lessor party role.
2. Confirm that the lessor legally owns or supplies the vehicle for the effective period.
3. Select the vehicle.
4. Define the agreement effective dates.
5. Define monthly/daily basis.
6. Define included mileage and excess mileage rates.
7. Define driver-related reimbursements.
8. Define taxes and accounting mappings through protected configuration.
9. Submit for approval.
10. Activate the agreement.
11. Preserve every activated version permanently.

### 5.4 Defects and gaps

- `Active` and `Closed` are independent checkboxes, allowing invalid combinations.
- No visible draft/approval/activation history.
- No visible versioning of commercial terms.
- No visible overlap validation for the same vehicle.
- Raw GL codes are exposed in the primary agreement screen.
- Agreement type is represented as an unexplained numeric value.
- Owner and leasing-company agreements are duplicated as parallel modules, creating inconsistent-rule risk.
- Historical payable calculations could change if the master agreement is edited.
- No attachment workflow for signed contract, ownership documents, insurance, or rate schedules.
- No visible currency, rounding, or tax-effective-date control.

### Required state model

`Draft → PendingApproval → Active → Suspended → Expired → Closed`

Only `Active` agreements may receive new operational allocations. Activated versions must be immutable.

---

## 6. Lessor vehicle allocation

### 6.1 What is actually observed

A separate “Lessor Vehicle Allocation” transaction screen is **not demonstrated** in the videos.

Instead, vehicle allocation appears to occur implicitly through:

1. Selecting the vehicle in the lessor agreement.
2. Selecting the lessor agreement/vehicle in the running chart.
3. Producing vehicle-wise owner statements and payable vouchers.

### 6.2 Why this is a major design weakness

Agreement and allocation are different responsibilities:

- Agreement answers: **What commercial terms apply?**
- Allocation answers: **Which vehicle is supplied, during what operational period, in what status, and for what lessee assignment?**

Directly selecting a vehicle on the agreement is insufficient for:

- Replacement vehicles
- Temporary substitutions
- Vehicle downtime
- Owner changes
- Agreement extensions
- One agreement covering multiple vehicles
- One vehicle moving between lessees
- Collision/repair periods
- Duplicate/overlapping use prevention
- Accurate historical profitability

### 6.3 Correct lessor allocation workflow

1. Choose active lessor agreement.
2. Select an eligible vehicle from the lessor’s effective ownership/supply pool.
3. Set allocation start and planned end.
4. Capture opening odometer and vehicle condition.
5. Confirm insurance/licence validity.
6. Set operational availability status.
7. Approve allocation.
8. Prevent overlapping active allocations.
9. Close allocation with return odometer and condition.
10. Create replacement allocation as a separate linked event when required.

### Recommended statuses

`Planned → Active → TemporarilyUnavailable → Replaced → Completed → Cancelled`

### Mandatory controls

- Allocation period must be inside both ownership period and lessor agreement period.
- Vehicle cannot have two conflicting active allocations.
- Replacement must link original and replacement allocations.
- Allocation history must never be overwritten.
- Closing odometer cannot be below opening odometer unless a documented odometer-reset event exists.

---

## 7. Lessor running chart

### 7.1 Observed behavior

The `Daily Running Chart - Normal` screen contains both sides of the rental relationship:

- Vehicle registration number
- Lessee agreement number and basis
- Lessor agreement number and basis
- Lessee code/name
- Lessor/owner code/name
- Driver
- Start and finish dates
- Start and finish mileage
- Computed kilometres
- Start and finish times
- Working hours
- OT type
- Number of night-outs
- Other charges
- Garage mileage
- Particulars of hire
- Daily-rate mode: Non-AC, Front-AC, Dual-AC

Continuation controls include:

- Clear both mileage
- Continue with finish mileage
- Clear both time
- Clear start time
- Clear finish time
- Continue both time

### 7.2 Lessor-side purpose

The finalized running chart is the usage source for:

- Base rental payable
- Excess-kilometre payable
- Driver salary reimbursement
- OT reimbursement
- Night-out reimbursement
- Other agreed owner charges

### 7.3 Critical gaps

- No demonstrated approval/finalization status.
- Generic Edit/Delete navigation remains visible.
- No visible duplicate/overlap warning.
- No visible odometer anomaly detection.
- No visible source locking after lessor payable generation.
- No explicit line-level linkage between running chart and payable voucher.
- The same operational record could potentially be settled twice.
- Garage mileage treatment is not explained.
- Normal, double, and triple OT derivation is not explained.
- Replacement-vehicle logic exists in reports but not in a demonstrated transaction flow.

### Required state model

`Draft → Submitted → Approved → Finalized → PartiallySettled → FullySettled`

Finalized records are immutable. Corrections must be separate adjustment records.

---

## 8. Lessor invoice / payable voucher

### 8.1 Observed document

The lessor settlement document is shown as **Payment Payable Voucher** and the transaction menu includes **Payment Payable Processing**.

### 8.2 Observed fields

- Agreement vehicle
- Agreement number
- Lessor/owner
- Start and finish dates
- Payable date and number
- Invoice sequence number
- Agreement type, dates, and basis
- Maximum kilometres
- Base and excess-kilometre rates
- Driver salary
- Total kilometres
- Excess kilometres
- Normal/double/triple OT
- Night-outs
- Days/hires
- Rental expense
- Excess-kilometre expense
- Refundable driver salary
- Refundable driver OT
- Refundable driver night-out
- Total value
- Payable description

Actions:

- Import Running Chart Data
- Process
- Create Payable Voucher
- Delete Payable Voucher
- Find
- Print

### 8.3 Correct calculation pipeline

1. Select active lessor agreement and settlement period.
2. Load finalized, unsettled running-chart records.
3. Calculate base payable using the lessor agreement version.
4. Calculate excess mileage.
5. Calculate reimbursable driver costs.
6. Add other agreed amounts.
7. Apply lessor debit/credit adjustments.
8. Preview a transparent calculation breakdown.
9. Post an immutable owner payable voucher.
10. Create a balanced journal entry in the same database transaction.
11. Mark source running-chart charge components as consumed.

### 8.4 Critical defects and gaps

- `Delete Payable Voucher` is visible; backend protection is not proven.
- The field `Invoice Sequence No` inside a payable voucher is semantically confusing.
- No visible approval or maker-checker process.
- No visible period-close validation.
- No visible idempotency protection against repeated processing.
- No visible tax snapshot.
- No visible source record list or quantity × rate explanation.
- No visible distinction between draft calculation and posted liability.
- No visible concurrency protection.

### Correct document states

`Draft → Calculated → PendingApproval → Posted → PartiallyPaid → Paid`

Controlled alternatives:

- `Reversed`
- `CancelledBeforePosting`

A posted voucher must never be deleted.

---

## 9. Lessor payments, receipts, debit notes, credit notes, and allocations

### 9.1 Observed lessor transaction menu

For vehicle owners, the menu shows:

- Lessor’s Cash Payment Vouchers
- Lessor’s Petty Cash Payment Vouchers
- Lessor’s Cheque Payment Vouchers
- Lessor’s Cash / Cheque Receipts
- Lessor’s Debit Note
- Lessor’s Credit Note
- Lessor’s Payment Payable Processing
- Lessor’s Debit Note — Fuel & Repair

This confirms that the lessor subledger is bidirectional, not a simple payment-only workflow.

### 9.2 Observed cheque payment fields

- Date
- Payment voucher number
- Sequence number
- Bank code
- Cheque number
- Cross/bearer
- Account-payee-only
- Payee name
- Payment amount
- Description/details
- Lessor control account
- Lessor
- Vehicle registration number
- Lessor amount
- Realized date
- Allocation tab

### 9.3 Observed lessor fuel/repair debit note

- Date
- Debit-note number
- Sequence number
- Lessor control account
- Vehicle
- Lessor
- Description
- Total debit amount
- Fuel/repair classification
- Fuel chit/invoice number
- Credit GL code
- Debit GL account and amount
- Allocation tab

### 9.4 Observed allocation behavior

The lessor allocation screen links an amount to an open reference and displays:

- Lessor code
- Reference number
- Sequence
- Original payment amount
- Current payment amount
- Allocated reference
- Bill/reference to settle
- Amount to allocate

### 9.5 Critical audit finding: error correction is procedural, not preventive

The Procedures menu contains:

- Check Lessor’s (Vehicle Owners) Allocation Errors
- Check Lessor’s (Leasing Companies) Allocation Errors
- Check Lessor transactions against General Ledger transactions
- Check GL Double Entry Errors

This is strong evidence that the legacy architecture expects users to detect or repair inconsistencies after they occur. The target system must prevent these inconsistencies transactionally.

### 9.6 Correct lessor payment workflow

1. Select posted owner payable/adjustment references.
2. Calculate open payable balance.
3. Prepare payment proposal.
4. Apply debit notes/credit notes transparently.
5. Obtain approval.
6. Issue cash/cheque/bank payment.
7. Allocate the payment atomically.
8. Update owner subledger and GL in the same transaction.
9. Track cheque presentation/realization.
10. Reconcile bank as a separate event.

### Required controls

- Allocation cannot exceed document open balance.
- Two users cannot allocate the same balance simultaneously.
- Payment and allocation must commit atomically.
- Cheque number must be unique per bank account/cheque book.
- Reconciled payments cannot be edited.
- Fuel/repair deduction requires evidence/attachment.
- Each deduction may be allocated only once.
- A posted debit/credit note is immutable.
- Correction requires reversal or opposite document.

---

# PART B — LESSEE END-TO-END AUDIT

## 10. Lessee agreement

### 10.1 Observed fields

- Agreement date
- Agreement type
- Agreement active flag
- Agreement close flag
- Lessee/customer
- Agreement number
- Vehicle registration number
- Vehicle type
- Execution/start/end dates
- Company or Personal format
- Monthly or Daily basis
- Maximum kilometres
- Rate for maximum kilometres
- Excess-kilometre rate
- With-driver option
- Non-AC, Front-AC, Dual-AC rate variants
- Driver salary
- Weekday/Saturday working hours
- Normal/double/triple OT rates
- Night-out rate
- VAT calculation
- VAT/SVAT invoice type
- VAT percentage
- SSCL percentage
- Rental income account
- Excess-kilometre income account
- Driver recovery accounts
- Parking/other-charge recovery account
- Introduced-by information
- NIC/passport number
- Driving licence number
- Security deposit
- Description/condition lines

### 10.2 Correct lessee agreement workflow

1. Select customer profile and validate credit/tax details.
2. Select available vehicle allocation.
3. Define effective dates and agreement basis.
4. Define mileage allowance and excess rate.
5. Define AC rate variant rules.
6. Define with-driver and OT terms.
7. Define security deposit.
8. Resolve taxes through effective-dated tax configuration.
9. Submit for approval.
10. Activate an immutable agreement version.

### 10.3 Defects and gaps

- Same flag/state problems as lessor agreement.
- No visible approval or versioning.
- Vehicle availability/overlap validation is not shown.
- Customer master and agreement both contain driver-rate defaults, creating precedence ambiguity.
- Tax and GL fields are duplicated inside the contract screen.
- Security deposit exists but receipt/refund/forfeiture lifecycle is not demonstrated.
- Credit limit exists in the customer master but enforcement behavior is not demonstrated.
- Exact monthly proration is unconfirmed.
- Exact excess-kilometre calculation modes are unconfirmed.
- No attachment/signature lifecycle is visible.

---

## 11. Lessee vehicle allocation

### 11.1 What is observed

A separate “Lessee Vehicle Allocation” transaction is not demonstrated. Vehicle assignment is embedded in:

- Lessee agreement vehicle selection
- Running-chart agreement/vehicle selection
- Vehicle-wise agreement and invoice reports

### 11.2 Required clean design

Lessee allocation should be a separate operational record because a contract may:

- Change vehicle temporarily
- Use a replacement vehicle
- Be suspended during repair
- Move between original and replacement vehicles
- Be extended without rewriting history
- Have a vehicle delivered/returned at specific odometer readings

### Correct lessee allocation workflow

1. Select active lessee agreement.
2. Show only eligible and available vehicles.
3. Create allocation with delivery date/time and odometer.
4. Record fuel/condition/checklist and documents.
5. Confirm driver assignment or self-drive mode.
6. Activate allocation.
7. Record temporary replacement separately.
8. Close on return with odometer, fuel, condition, and damage notes.
9. Send finalized allocation period to running-chart/billing eligibility.

### Critical controls

- No overlapping customer allocations for the same vehicle.
- Allocation must be inside agreement dates.
- Vehicle legal documents must be valid.
- Replacement period must preserve both original and substitute histories.
- Return condition and odometer are immutable after approval.

---

## 12. Lessee running chart

The same daily running chart drives customer billing.

### 12.1 Lessee-side charge sources

- Monthly/daily base rental
- Non-AC / Front-AC / Dual-AC daily rate
- Excess mileage
- Driver salary recovery
- Normal/double/triple OT recovery
- Night-out recovery
- Other charges
- Garage mileage, depending on configured rule
- Number of days/hires

### 12.2 Observed report modes

- Monthly basis
- Daily basis
- Excess KM Calculation — Normal
- Excess KM Calculation — By Hire
- Excess KM Calculation — By Log Transaction
- Corresponding `(Value)` report modes

### 12.3 Unconfirmed business rules

The videos do not prove:

- Exact formula for each excess-KM mode
- Whether maximum kilometres apply per month, agreement, hire, or log
- Whether garage mileage is billable
- Partial-month proration rule
- Holiday/weekend OT derivation
- Rounding order

These must be confirmed before implementation.

### Required billing-source controls

- Only approved/finalized running charts are billable.
- A charge component may be consumed once for a given billing side.
- Rebilling requires an explicit adjustment link.
- Source links must show which invoice line consumed which running-chart data.

---

## 13. Lessee invoices

### 13.1 Observed lessee transaction menu

- Lessee’s Cash Payment Vouchers
- Lessee’s Petty Cash Payment Vouchers
- Lessee’s Cheque Payment Vouchers
- Lessee’s Cash / Cheque Receipts
- Lessee’s Debit Note
- Lessee’s Credit Note
- Lessee’s Invoice
- Lessee’s Miscellaneous Invoice

### 13.2 Observed credit invoice fields

- Lessee/customer
- Invoice heading
- Agreement number and vehicle
- Billing start and finish dates
- Invoice date and number
- Sequence number
- Total kilometres
- Excess kilometres
- Normal/double/triple OT hours
- Night-outs
- Days/hires
- Rental income
- Excess-kilometre income
- Driver salary recovery
- Driver OT recovery
- Driver night-out recovery
- SSCL
- VAT
- Total value
- Agreement snapshot values
- Invoice description

Actions:

- Import Running Chart Data
- Process
- Create Invoice
- Delete Invoice
- Find
- Print
- Create PDF confirmation

### 13.3 Correct invoice workflow

1. Select active agreement and billing period.
2. Load finalized, unbilled running-chart components.
3. Calculate every line as quantity × rate.
4. Apply effective agreement version and tax snapshot.
5. Show source records and formula breakdown.
6. Validate credit limit and accounting period.
7. Approve and post invoice.
8. Post balanced GL entry atomically.
9. Lock source components from duplicate billing.
10. Generate PDF from the posted snapshot.

### 13.4 Critical defects and gaps

- `Delete Invoice` remains visible.
- No visible posting status distinction.
- No line-level source traceability.
- Reprocessing/idempotency not demonstrated.
- Tax percentage/account fields are live on the transaction.
- No visible credit-limit decision log.
- No approval or accounting-period control.
- No visible reversal/credit-note link.
- PDF reproducibility could fail if generated from current master data.

### Required invoice states

`Draft → Calculated → PendingApproval → Posted → PartiallyPaid → Paid`

Controlled alternatives:

- `Reversed`
- `WrittenOff`
- `CancelledBeforePosting`

---

## 14. Lessee payments, receipts, and allocations

### 14.1 Two different money directions exist

The legacy menu includes both:

- **Cash/Cheque Receipts** — money received from the lessee.
- **Cash/Petty Cash/Cheque Payment Vouchers** — money paid to the lessee, likely refunds, deposit returns, or other payable items.

These must not be collapsed into one ambiguous “payment” function.

### 14.2 Observed lessee receipt fields

- Date
- Receipt number
- Sequence
- Cash-receipt indicator
- Cash/cheque-in-hand account
- Cheque number
- Receipt amount
- Description/details
- Lessee control account
- Lessee/customer
- Vehicle registration number
- Lessee amount
- Allocation tab

### 14.3 Observed receipt allocation fields

- Lessee/customer
- Reference number
- Sequence
- Original receipt amount
- Current receipt amount
- Allocated reference
- Reference to settle
- Amount to allocate
- Balance on reference
- Allocate/remove-allocation actions

### 14.4 Observed lessee cash payment fields

- Date
- Payment voucher number
- Sequence
- Cash GL account
- Payment amount
- Lessee control account
- Lessee code
- Vehicle registration number
- Description/details

### 14.5 Correct lessee collection workflow

1. Record receipt with payment method and bank/cash account.
2. Post receipt atomically to lessee subledger and GL.
3. Allocate against one or more invoices/debit notes.
4. Preserve unallocated balance where necessary.
5. Track cheque status: received, deposited, realized, dishonoured.
6. Handle refund through a separate approved lessee payment voucher.
7. Reconcile bank separately.

### 14.6 Critical controls

- Allocation cannot exceed receipt unallocated amount.
- Allocation cannot exceed invoice outstanding amount.
- One invoice may have many receipts; one receipt may settle many invoices.
- Allocation removal after posting requires reversal, not silent delete.
- Concurrency lock/version check is mandatory.
- Dishonoured cheque must reopen balances through a controlled reversal.
- Refund cannot exceed validated credit/unallocated deposit.
- Vehicle number is an analytical dimension, not the accounting identity.

### 14.7 Legacy integrity warning

The Procedures menu includes `Check Lessee’s Allocation Errors` and `Check Lessee transactions with General Ledger transactions`.

The target system must prevent these inconsistencies at write time instead of depending on repair procedures.

---

# PART C — CROSS-FLOW DEFECT AND GAP REGISTER

## 15. Critical findings

| ID | Finding | Impact | Required correction |
|---|---|---|---|
| C-01 | No standalone vehicle-allocation lifecycle is demonstrated | Overlapping use, weak replacement history, unclear ownership/lessee period | Introduce effective-dated lessor and lessee allocation records |
| C-02 | Posted financial screens visibly expose Edit/Delete-style controls | Financial history may be altered | Only drafts editable; posted records corrected by reversal/credit/debit adjustment |
| C-03 | Allocation-error and GL-error checking exists as a repair procedure | Data integrity is not guaranteed during posting | Make source posting, allocation, subledger, and GL atomic |
| C-04 | Same running chart feeds two financial sides without visible source-consumption locks | Duplicate customer billing or owner settlement | Use immutable source links and unique charge-consumption constraints |
| C-05 | Agreements have independent Active/Closed flags | Invalid states and ambiguous eligibility | Replace flags with explicit state machine |
| C-06 | Agreement versioning is not visible | Historical invoices/payables can change after rate edits | Immutable agreement versions and transaction snapshots |
| C-07 | Numeric password/user-level register | Security and authorization weakness | Hashed passwords, named roles, granular permissions, security audit |
| C-08 | Payment/receipt allocations appear user-driven without visible concurrency protection | Double allocation and wrong balances | Database transaction + row/version lock + conflict response |
| C-09 | GL consistency is checked after the fact | Source/subledger/GL divergence | Post balanced journal atomically with source document |
| C-10 | Bank reconciliation uses an “Edit Cheque Payment” screen | Reconciliation may mutate original payment | Separate append-only reconciliation event |

## 16. High-priority findings

| ID | Finding | Required correction |
|---|---|---|
| H-01 | Vehicle-owner and leasing-company flows are duplicated | Shared lessor domain with role/type-specific policy, not duplicated business logic |
| H-02 | Raw GL and party codes dominate UI | Searchable human-readable selectors; codes secondary |
| H-03 | Tax fields are embedded in agreements/invoices | Effective-dated tax configuration and immutable snapshots |
| H-04 | Rate defaults appear in customer master and agreement | Agreement version is authoritative; master is template only |
| H-05 | Exact calculation modes are opaque | Document formulas, store line breakdown, add tests for every mode |
| H-06 | Replacement-vehicle reports exist without demonstrated transaction model | Add linked original/replacement allocations and period rules |
| H-07 | Security-deposit field exists without lifecycle | Deposit receipt, hold, adjustment, refund, forfeiture, and ledger tracking |
| H-08 | Lessee and lessor subledgers are bidirectional but menu names are ambiguous | Explicit `Receipt`, `Payment`, `Refund`, `Debit Note`, `Credit Note` semantics |
| H-09 | Agreement accounting mappings are exposed to operational users | Protected configuration with role restrictions |
| H-10 | Date formats are ambiguous | ISO storage; unambiguous display such as `03 Apr 2026` |
| H-11 | No visible maker-checker controls | Approval policies for agreement, invoice, payable, and payment |
| H-12 | No visible accounting-period lock | Open-period validation before posting |

## 17. Medium-priority findings

- Dense forms display many irrelevant zero-value fields.
- Numeric agreement types and transaction types are not understandable.
- Generic Add/Edit/Delete/Next/Previous navigation is slow and risky.
- Search and filtering are secondary to record-by-record navigation.
- Required fields and dependencies are not clearly indicated.
- Calculation preview does not adequately explain totals.
- Attachments and evidence are not demonstrated.
- Audit history and status history are not visible.
- Error handling, duplicate-number handling, and retry behavior are not demonstrated.
- Sensitive customer/account data appears in meeting recordings.

---

## 18. Required source-of-truth model

### Parties

- `Party`
- `PartyContact`
- `LesseeProfile`
- `LessorProfile`
- `LeasingCompanyProfile`
- `DriverProfile`

### Fleet

- `Vehicle`
- `VehicleOwnershipPeriod`
- `VehicleLegalDocument`
- `VehicleAvailabilityEvent`
- `VehicleOdometerEvent`

### Agreements and allocations

- `LessorAgreement`
- `LessorAgreementVersion`
- `LessorVehicleAllocation`
- `LesseeAgreement`
- `LesseeAgreementVersion`
- `LesseeVehicleAllocation`
- `VehicleReplacementAssignment`

### Operations

- `RunningChart`
- `RunningChartChargeComponent`
- `RunningChartApproval`
- `DriverAssignment`

### Lessee financials

- `LesseeInvoice`
- `LesseeInvoiceLine`
- `LesseeInvoiceSourceLink`
- `LesseeReceipt`
- `LesseeReceiptAllocation`
- `LesseePayment`
- `LesseeDebitNote`
- `LesseeCreditNote`

### Lessor financials

- `LessorPayableVoucher`
- `LessorPayableLine`
- `LessorPayableSourceLink`
- `LessorPayment`
- `LessorReceipt`
- `LessorDebitNote`
- `LessorCreditNote`
- `LessorAllocation`

### Accounting and controls

- `JournalEntry`
- `JournalLine`
- `AccountingPeriod`
- `PaymentInstrument`
- `ChequeLifecycleEvent`
- `BankReconciliationEvent`
- `DocumentNumberSequence`
- `AuditEvent`
- `DocumentStatusHistory`
- `ApprovalDecision`
- `Attachment`

---

## 19. Required accounting behavior

The videos prove that GL accounts and integrated ledger reports exist, but not every debit/credit line. The following conceptual entries require accountant confirmation.

### Lessee invoice

- Debit: Lessee control
- Credit: Rental income
- Credit: Excess-kilometre income
- Credit: Driver/other recoveries
- Credit: Tax payable

### Lessee receipt

- Debit: Cash/bank/cheques in hand
- Credit: Lessee control

### Lessee refund/payment

- Debit: Lessee control or validated refund liability
- Credit: Cash/bank

### Lessor payable voucher

- Debit: Rental expense
- Debit: Excess-kilometre expense
- Debit: Reimbursable driver/other expense
- Credit: Lessor payable control

### Lessor payment

- Debit: Lessor payable control
- Credit: Cash/bank

### Fuel/repair deduction

The exact posting depends on whether the company recovers a previously recorded expense, reduces a payable, or recognizes another receivable. This must be configured by adjustment type and confirmed by accounting stakeholders.

### Non-negotiable controls

- Every journal balances before commit.
- Source document and journal post in one atomic transaction.
- Posted records are immutable.
- Reversal posts equal and opposite entries.
- Accounting period must be open.
- Party, vehicle, agreement, and cost center remain reporting dimensions.

---

## 20. Calculation requirements

Every invoice and payable line must persist:

- Source running-chart record
- Charge type
- Quantity
- Unit
- Rate
- Agreement version
- Tax basis
- Tax rate
- Rounding rule
- Gross/net amount
- Adjustment links

### Conceptual lessee formula

```text
Base rental
+ Excess mileage recovery
+ Driver salary recovery
+ OT recovery
+ Night-out recovery
+ Other recoveries
+ Applicable taxes
= Lessee invoice total
```

### Conceptual lessor formula

```text
Base rental payable
+ Excess mileage payable
+ Driver salary reimbursement
+ OT reimbursement
+ Night-out reimbursement
+ Other agreed owner amounts
- Owner debit-note deductions
+ Owner credit-note adjustments
= Net amount payable to lessor
```

### Unconfirmed formulas requiring stakeholder decisions

1. Normal vs By Hire vs By Log Transaction excess-KM calculations.
2. `(Value)` variants in running-chart reports.
3. Monthly proration.
4. Included-kilometre reset period.
5. Garage-mileage treatment.
6. AC rate selection rules.
7. Holiday/weekend OT calculation.
8. Driver-rate precedence.
9. Replacement-vehicle charge and payable ownership.
10. Tax calculation and rounding order.

---

## 21. Permissions and segregation of duties

Recommended named permissions:

- Manage lessee master
- Manage lessor master
- Manage vehicle master
- Draft/approve lessor agreement
- Draft/approve lessee agreement
- Allocate vehicle to lessor agreement
- Allocate vehicle to lessee agreement
- Enter/approve/finalize running chart
- Calculate/post/reverse lessee invoice
- Record/allocate/reverse lessee receipt
- Calculate/post/reverse lessor payable
- Record/approve/reverse lessor payment
- Create/approve debit and credit notes
- Issue cheque
- Reconcile bank
- Manage tax and GL mappings
- View operational reports
- View financial reports
- Manage roles and users

### Required separation

- Agreement drafter should not automatically approve.
- Running-chart entry and approval should be separable.
- Payment preparer should not be sole approver.
- Bank reconciler should be independent from payment preparation.
- Reversal permission must be restricted and audited.

---

## 22. Recommended fast UI workflow

### Daily operator flow

1. Search vehicle by registration number.
2. Show active lessor agreement, lessee agreement, and allocation periods.
3. Prefill prior approved odometer/time.
4. Enter daily usage and exceptions only.
5. Submit for approval.

### Billing officer flow

1. Select agreement and billing period.
2. Show eligible finalized usage records.
3. Display quantity × rate breakdown.
4. Resolve exceptions.
5. Submit/post invoice or payable.

### Cashier flow

1. Search party by name/reference.
2. Record receipt/payment.
3. Show open references.
4. Allocate with real-time balance validation.
5. Post atomically.

### UI rules

- Never ask users to type raw IDs or foreign keys.
- Use searchable vehicle, party, agreement, and account labels.
- Hide irrelevant fields based on agreement/rate type.
- Keep accounting configuration out of normal rental operations.
- Display current status and permitted next action.
- Show source records before any financial posting.

---

## 23. Video traceability

### `1.mp4`

- ~02:30 — Lessee register
- ~03:30 — Lessee agreement
- ~05:00–06:30 — Lessee credit invoice and calculations
- ~09:30 — Agreement rate/account variants
- ~11:00 — Agreement with vehicle owner
- ~13:30 — Daily running chart linking both agreements
- ~16:30 — Payment payable voucher
- ~18:30 — Running-chart output
- ~20:30 — Lessee invoice listing filters
- ~24:00 — Vehicle-owner agreement details
- ~25:30 — Vehicle-wise owner statement
- ~26:30 — Owner payable voucher
- ~27:30–32:30 — Lessor fuel/repair debit note and GL selection
- ~33:30 — Lessor debit-note allocation
- ~34:30 — Lessor cheque payment/allocation
- ~37:00 — General-ledger cheque payment
- ~39:30 — Bank-reconciliation edit/realization

### `Recording 2026-06-21 132314.mp4`

- ~01:00 — Vehicle register
- ~02:00 — Lessee register
- ~03:30 — Lessee agreement
- ~06:00 — Lessee credit invoice
- ~08:00 — Printed invoice
- ~11:00 — Daily running chart
- ~14:00 — Invoice with driver terms
- ~15:30 — Password/user register
- ~18:30 — Running-chart report parameters
- ~19:00 and ~23:30 — Running-chart report output
- ~22:30 — Excess-KM calculation mode parameters
- ~26:30 — Lessee receipt
- ~27:30 — Lessee receipt allocation
- ~28:30 — Existing receipt record
- ~30:30 — Agreement with vehicle owner
- ~32:00 — Agreement condition fields
- ~33:30 — Vehicle-owner statement
- ~34:00 — Vehicle statement parameters
- ~37:30 — Integrated rental ledger
- ~40:30 — Invoice/PDF confirmation

### `2.mp4`

- Opening minutes — full transaction menu branches for GL, lessor leasing companies, lessor vehicle owners, lessee, and running chart
- Visible lessor transaction menu — cash/petty-cash/cheque payments, receipts, debit/credit notes, payable processing, fuel/repair debit note
- Visible lessee transaction menu — cash/petty-cash/cheque payments, receipts, debit/credit notes, invoice, miscellaneous invoice
- Register menu — separate lessor/lessee/vehicle/driver/agreement registers
- Procedure menu — allocation-error checks, GL double-entry checks, and source-to-GL reconciliation checks
- Lessee reports menu — ledger, vehicle ledger, agreement listings, invoice listings, aging, balances, statements, debit/credit-note listings, unallocated receipt/credit-note listing, SVAT declaration
- Running-chart reports — lessee, lessor, original/replacement vehicles, vehicle log checks, driver OT, self-drive movement

### `ScreenVideo_03-04-2026_18-02-52.mp4`

- No direct lessor/lessee agreement, allocation, running-chart, rental invoice, or rental payment lifecycle is demonstrated.
- It supports shared observations about vehicle/customer/accounting architecture only.

---

## 24. Final conclusion

The legacy system contains broad Vehicle Rental functionality, but its core design combines contracts, vehicle assignment, operations, allocations, and accounting through loosely connected forms and repair-style validation procedures.

The most serious missing foundation is a real **vehicle-allocation lifecycle**. Vehicles are selected directly on agreements and then reused in the running chart, which is insufficient for replacements, overlaps, owner changes, downtime, and historical traceability.

The correct AutoERP foundation is:

1. Effective-dated vehicle ownership.
2. Versioned lessor and lessee agreements.
3. Separate lessor and lessee vehicle allocations.
4. One approved running-chart source shared by both sides.
5. Independent lessee invoice and lessor payable calculations.
6. Immutable source links preventing duplicate billing/settlement.
7. Atomic subledger and GL posting.
8. Concurrency-safe allocations.
9. Reversal-based corrections.
10. Append-only audit and explicit document states.

The legacy business capabilities should be preserved, but the legacy design mistakes—raw-code UI, duplicate lessor modules, mutable-looking financial forms, after-the-fact error checks, numeric security levels, and implicit vehicle allocation—must not be copied into the new AutoERP architecture.
