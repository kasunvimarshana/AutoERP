# AutoERP Legacy Video Audit — End-to-End Study

**Audit scope:** 4 uploaded MP4 recordings, reviewed as one connected legacy ERP walkthrough.

| Video | Duration | Resolution | Primary content |
|---|---:|---:|---|
| `1.mp4` | 40:50 | 1600×900 | Rent-a-car agreements, billing, payables, debit notes, cheque payments, reconciliation |
| `Recording 2026-06-21 132314.mp4` | 41:58 | 1370×774 | Vehicle/lessee masters, agreements, running charts, invoices, receipts, statements and security register |
| `ScreenVideo_03-04-2026_18-02-52.mp4` | 12:24 | 1280×720 | Workshop/garage workflow: vehicle → job → parts/outside work/labour → invoice |
| `2.mp4` | 21:14 | 1600×900 | Rental/ledger/report menus and reports; later portion is visually idle while meeting audio continues |

**Total footage:** approximately **1 hour 56 minutes 26 seconds**.

---

## 1. Executive understanding

The recordings show two closely connected legacy systems:

1. **Integrated Rent-A-Car + General Ledger system**
   - Handles lessees/customers, vehicle owners, vehicles, rental agreements, daily running charts, rental invoices, customer receipts, owner payables, cheque payments, bank reconciliation and financial reports.

2. **Auto-care/workshop + General Ledger system**
   - Handles customer vehicles, workshop jobs, material/parts issues, outsourced work, labour charges, job invoicing, debtor/customer records, item masters, mechanics and accounting codes.

The correct target for AutoERP is not merely a visual clone. It should preserve the observed business rules while replacing the legacy code-heavy, mutable, desktop-style interaction model with a controlled modular workflow and an auditable accounting core.

---

## 2. Video-by-video timeline map

### Video 1 — `1.mp4`

**00:00–02:00 — Navigation and meeting setup**
- Legacy desktop ERP menu navigation.
- Google Meet screen sharing.

**02:00–03:20 — Lessee master**
- `Lessee's Register`.
- Fields observed: lessee code, name, address, contacts, contact person, credit limit, opening balance, VAT/SVAT registration, driver salary and overtime-related rates.

**03:20–07:20 — Lessee rental agreement and invoice configuration**
- `Agreement Register - With Lessee`.
- Agreement dates, agreement type, lessee, vehicle, monthly/daily basis, invoice type, taxes, accounting codes, maximum kilometres, excess-kilometre rates, driver settings, parking/other recoveries and security deposit.
- `Credit Invoice` screen calculates rental and usage-related charges.

**08:00–12:30 — Agreement variants**
- Switching between lessee agreements and vehicle-owner agreements.
- Different income/payable account mappings and owner-side reimbursement fields.

**13:00–18:30 — Running chart and owner payable flow**
- `Daily Running Chart - Normal`.
- Start/finish dates, meter readings, start/finish times, driver, overtime, night-out, garage mileage and other charges.
- `Payment Payable Voucher` generated for the vehicle owner.
- Running-chart report preview.

**19:00–21:30 — Invoice listing/report filters**
- Invoice listing by vehicle/date/invoice type.
- Report parameter dialogs.

**23:30–26:30 — Vehicle-owner agreement and statement**
- `Agreement Register - With Vehicle Owners`.
- Statement of account vehicle-wise.
- Owner payable voucher fields and account allocations.

**27:00–36:30 — Debit notes, allocations and customer payments**
- `Lessee Debit Note - Fuel & Repair`.
- Debit-note allocation to a previous reference/payment.
- `Lessee's Cheque Payments` and allocation screens.

**37:00–40:50 — General ledger cheque and bank reconciliation**
- `General Ledger Cheque Payments`.
- Edit cheque for bank reconciliation.
- Bank account, cheque number, cross/bearer state, payee, debit GL code and reconciliation date.

### Video 2 — `Recording 2026-06-21 132314.mp4`

**00:00–02:30 — Core masters**
- `Vehicle Register`.
- `Lessee's Register`.
- Vehicle licensing, ownership, dimensions, class, engine/chassis, insurance, revenue licence and accounting links.

**02:30–08:30 — Agreement and invoice setup**
- `Agreement Register - With Lessee`.
- `Credit Invoice`.
- Printed tax invoice preview.

**09:00–14:30 — Daily running and billing**
- Daily running chart entry.
- Monthly/daily calculations.
- Rental income, excess kilometres, driver salary/overtime/night-out, tax and total invoice values.

**15:00–16:00 — User/password register**
- `Password Register` with login name, password confirmation, user level, create date, last login and access status.
- This is a major security redesign area in the target system.

**16:00–24:30 — Running-chart reporting**
- `Lessee's Agreement wise Running Charts` parameter screen.
- Calculation options include normal, by hire, by log transaction and value-based modes.
- Detailed running-chart report output with meter, mileage, time, overtime and charges.

**25:30–29:30 — Receipt and allocation**
- `Lessee's Receipt`.
- Cash/cheque handling, customer control account, vehicle reference and receipt amount.
- Receipt allocation against an original invoice/reference.

**30:00–33:30 — Vehicle-owner agreement and statement**
- Owner-side agreement and rate/account setup.
- Vehicle-wise statement including rental payable, settlements and repair deductions.

**34:00–39:30 — Accounting reports**
- Vehicle statement parameter screen.
- Integrated rent-a-car ledger account report.
- Debit, credit and running balance report.

**40:00–41:58 — Invoice finalization and menus**
- Invoice creation with optional PDF generation.
- Additional report menu exploration.

### Video 3 — `ScreenVideo_03-04-2026_18-02-52.mp4`

**00:00–00:25 — Workshop vehicle register**
- Vehicle, chassis, type, owner/debtor, contact, make, engine, fuel, mileage and service reminder fields.
- Next service by months/kilometres, timing-belt reminder and ATF reminder.

**00:25–01:15 — Job register**
- Job date/number, vehicle, customer/debtor and mileage.
- Service categories: mechanical, alignment, services, tinkering and painting, breakdown, electrical, auto A/C and motorcycle.
- Next ATF and belt-change kilometre targets.

**01:15–02:00 — Material issue to job**
- `Material Issue Note - Jobs`.
- Vehicle/job/customer, stock-control account, pending-job control, item group/code, quantity, cost, selling price and invoice description.

**02:00–02:50 — Outsourced work**
- `Outside Work Order Note`.
- Creditor/supplier, job, VAT/debit/credit accounts, cost, profit amount/margin and selling value for tax/non-tax invoices.

**03:00–03:35 — Labour charges**
- Mechanic, job, service description, cost-of-sales account, sales account, discount account and selling value.

**03:45–04:35 — Job invoice**
- `Debtor Job Invoice` aggregates material, outsourced work and labour.
- Labour is grouped by mechanical, alignment, tinkering/painting, breakdown, electrical, auto A/C and motorcycle.
- Invoice creation and job-closing actions are visible.

**05:00–08:20 — Supporting masters**
- Accounts note register.
- Accounts code register and cost centre.
- Debtor/customer register.
- Item group register.
- Item register: part number/model, brand, category, reorder values, stock balance and selling price.
- Mechanic register and attached cost centre.

**08:20–12:24 — No additional visual workflow**
- Shared application remains visually idle.
- The late audio is effectively silent, so no additional business flow was captured.

### Video 4 — `2.mp4`

**00:00–05:20 — Reporting and ledger navigation**
- Lessee transaction/report menus.
- Transaction listing and reconciliation-style reports.
- Ledger/log-sheet report menu.
- Monthly rental log sheet for vehicle owners.
- Integrated rent-a-car ledger report with debit, credit and balance.

**05:20–21:14 — Visual idle period with active discussion audio**
- The application remains mostly blank/static.
- Audio remains active during much of this period, indicating meeting discussion or clarification, but no additional on-screen forms or transactions are demonstrated.

---

## 3. End-to-end rental workflow reconstructed

### 3.1 Master setup

1. Create or maintain the lessee/customer.
2. Create or maintain the vehicle owner.
3. Register the vehicle and its legal/technical details.
4. Configure drivers, users, bank accounts and general-ledger accounts.
5. Configure tax and invoice behavior.

### 3.2 Vehicle-owner agreement

1. Select vehicle owner and vehicle.
2. Define agreement period and monthly/daily basis.
3. Define payable rental rate and kilometre conditions.
4. Define refundable driver salary, overtime and night-out costs.
5. Define deductions/recoveries such as fuel, repair, parking or other charges.
6. Map payable and expense GL accounts.

### 3.3 Lessee/customer agreement

1. Select lessee and vehicle.
2. Define start/end/execution dates.
3. Select monthly or daily agreement basis.
4. Configure Non-AC, Front-AC and Dual-AC rates where relevant.
5. Configure included kilometres and excess-kilometre rate.
6. Configure driver work hours, salary, overtime and night-out rates.
7. Configure invoice type and tax treatment.
8. Configure rental income, excess-kilometre income and tax GL accounts.
9. Capture security deposit and supporting identity/licence data.

### 3.4 Daily operation / running chart

1. Select vehicle and the linked lessee agreement.
2. Select driver.
3. Capture date, start/finish odometer and start/finish time.
4. Calculate mileage and overtime.
5. Capture night-out, garage mileage and other charges.
6. Apply a calculation mode where required.
7. Lock/finalize the running entry for billing.

### 3.5 Customer invoicing

1. Import eligible running-chart data.
2. Calculate base rental.
3. Calculate excess kilometres.
4. Calculate driver salary/overtime/night-out recoveries.
5. Add other recoverable charges.
6. Apply effective taxes.
7. Generate invoice number and accounting posting.
8. Generate/print PDF invoice.

### 3.6 Vehicle-owner payable

1. Select owner agreement and billing period.
2. Calculate owner rental payable.
3. Calculate refundable driver/overtime/night-out expenses.
4. Apply repair/fuel/other deductions.
5. Generate payable voucher.
6. Post to owner payable and expense accounts.

### 3.7 Receipt and allocation

1. Record cash or cheque receipt.
2. Select lessee/customer and optional vehicle.
3. Allocate receipt to an open invoice/reference.
4. Prevent over-allocation.
5. Post customer control and cash/bank entries atomically.

### 3.8 Debit note and allocation

1. Record fuel/repair/other debit note.
2. Link customer and vehicle.
3. Choose the correct GL account.
4. Allocate the debit note to the intended invoice/reference.
5. Preserve the original transaction and create a separate adjustment record.

### 3.9 Owner/GL payment and reconciliation

1. Create cheque/payment voucher.
2. Select bank, payee and debit GL account.
3. Record cheque number and crossing/bearer state.
4. Post the payment.
5. Mark realization/reconciliation separately.
6. Produce bank and ledger reports.

---

## 4. End-to-end workshop workflow reconstructed

1. **Customer/debtor master**
2. **Vehicle registration** with mileage and service reminders
3. **Job creation** with required service categories
4. **Parts/material issue** from stock to the job
5. **Outside work order** for subcontracted services
6. **Labour entry** by mechanic/service category
7. **Job invoice calculation**
   - Material
   - Outsourced work
   - Labour by category
   - Discount
   - Tax
   - Job total
8. **Create invoice**
9. **Close job** only when required entries are finalized
10. **Receive and allocate payment**
11. **Update vehicle history and next-service reminders**
12. **Post all financial effects to the general ledger**

---

## 5. Core entities and relationships observed

### Parties
- Lessee / rental customer
- Vehicle owner
- Debtor / workshop customer
- Creditor / outside-work supplier
- Driver
- Mechanic
- User

### Assets
- Vehicle
- Vehicle registration/licence/insurance details
- Vehicle ownership
- Service reminders and mileage history

### Rental
- Lessee agreement
- Vehicle-owner agreement
- Running chart / log sheet
- Rental invoice
- Owner payable voucher
- Debit note
- Receipt and receipt allocation
- Cheque payment

### Workshop
- Job
- Job service category
- Material issue
- Outside work order
- Labour charge
- Job invoice
- Item / item group / brand

### Finance
- GL account
- Cost centre
- Tax code/rate
- Journal/posting
- Customer control account
- Supplier/owner control account
- Cash/bank account
- Reconciliation record

### Key relationship rules
- A vehicle can have multiple agreements over time, but agreement periods for the same operational use must be conflict-checked.
- A running chart belongs to a vehicle and a valid agreement period.
- A rental invoice derives from one or more finalized running-chart records.
- A receipt can allocate to multiple invoices, and an invoice can receive multiple allocations.
- A job belongs to one vehicle and customer at the time the job is opened.
- A job aggregates many material issues, outside-work entries and labour entries.
- Every financial posting must reference its source document.

---

## 6. Important calculation rules captured

### Rental billing
- Monthly versus daily agreement basis.
- Non-AC, Front-AC and Dual-AC pricing variants.
- Included/maximum kilometres.
- Excess-kilometre rate.
- Start/finish odometer and mileage calculation.
- Driver standard hours.
- Normal, double and triple overtime.
- Night-out charges.
- Garage mileage and other charges.
- Customer recoveries versus vehicle-owner reimbursements.
- VAT/SVAT/SSCL and older tax fields visible in the legacy UI.

### Workshop billing
- Material cost and selling amount.
- Outsourced-work cost, profit amount/margin and selling amount.
- Labour charge by mechanic/service category.
- Tax and non-tax selling values.
- Job total aggregation.
- Stock quantity reduction from material issue.
- Vehicle service reminders based on date and mileage.

---

## 7. Audit findings and risks

### Critical

#### C1. Posted financial records appear editable/deletable from standard screens
The UI exposes Edit/Delete actions across invoices, receipts, vouchers and accounting transactions. The recording does not prove whether the backend blocks changes after posting.

**Target rule:**
- Draft records may be edited or deleted.
- Posted records are immutable.
- Corrections use reversal, credit/debit adjustment or replacement documents.
- All actions create append-only audit events.

#### C2. Legacy authentication/user management needs replacement
A direct `Password Register` with numeric user level is shown.

**Target rule:**
- Passwords must be securely hashed and never recoverable.
- Use named roles and permissions, not numeric user-level logic.
- Separate authentication from employee/customer records.
- Log sign-in and permission changes.

#### C3. Financial posting must be atomic
Invoice, stock issue, receipt allocation and GL posting are tightly connected.

**Target rule:**
- Each business operation runs in one database transaction.
- If inventory, source document or ledger posting fails, the complete operation rolls back.
- Retried requests must be idempotent.

### High

#### H1. Raw codes dominate the UI
GL codes, control codes, vehicle codes and customer codes are visible throughout the primary workflow.

**Target rule:** show readable labels and searchable selectors; keep IDs/codes internal or secondary.

#### H2. Tax behavior appears embedded in forms
Tax percentages and tax account codes are repeated across agreement and invoice screens.

**Target rule:** effective-dated tax configuration is the single source of truth. Historical invoices retain a tax snapshot.

#### H3. Business rules are duplicated across screens
Driver rates, excess-kilometre rules and account mappings appear in agreements, invoices and payable screens.

**Target rule:** agreements own commercial terms; invoices/payables consume immutable agreement snapshots or versioned terms.

#### H4. Date format is ambiguous
Dates such as `03/04/2026` can be interpreted in two ways.

**Target rule:** store ISO dates; display an explicit localized format such as `03 Apr 2026`.

#### H5. Concurrency behavior is unclear
The status bar shows record locked/unlocked states, but no visible conflict resolution is demonstrated.

**Target rule:** optimistic version checks for normal editing; database locks for allocations, number sequences, stock and posting.

#### H6. Calculation traceability is weak
The UI displays totals but not a transparent line-by-line explanation of how every value was derived.

**Target rule:** store calculation breakdowns and display a preview before posting.

#### H7. Sensitive information is exposed in recordings
Customer/company identities, account references, remote-session identifiers and business reports are visible.

**Target rule:** redact training recordings, mask sensitive values and use non-production demo data.

### Medium

- Dense forms create high cognitive load.
- Many zero-value fields are shown even when irrelevant.
- Mandatory fields are not clearly marked.
- Sequential `Next/Prev/Bottom` record navigation is inefficient.
- Search and filtering are secondary rather than primary.
- PDF/report generation appears coupled to the desktop process.
- Status transitions and approval states are not prominent.
- The same generic button bar is used for materially different actions.

---

## 8. Recommended AutoERP module ownership

1. **Identity & Access** — users, roles, permissions and authentication
2. **People/Parties** — customers, vehicle owners, suppliers and contacts
3. **Vehicle Registry** — vehicles, ownership, legal details, mileage and reminders
4. **Rental Agreements** — customer and owner commercial agreements
5. **Rental Operations** — running charts, drivers, mileage/time and operational charges
6. **Workshop Jobs** — job lifecycle and service categories
7. **Inventory** — items, stock, material issues and valuation
8. **Procurement/Outside Work** — subcontractors and outside-work orders
9. **Billing / Accounts Receivable** — rental invoices, job invoices, debit/credit notes
10. **Payables** — owner payables and supplier liabilities
11. **Payments & Banking** — receipts, allocations, cheque payments and reconciliation
12. **General Ledger** — chart of accounts, journals, periods and financial posting
13. **Reporting** — statements, ledgers, running charts, operational and financial reports
14. **Audit** — append-only history for every material action

Business rules must remain in the owning backend module. The frontend should only guide the user and provide immediate validation feedback.

---

## 9. Recommended target document states

### Rental agreement
`Draft → Active → Suspended/Expired → Closed`

### Running chart
`Draft → Submitted → Finalized → Billed`

### Invoice
`Draft → Posted → Partially Paid → Paid` with `Reversed` as a separate controlled outcome

### Receipt/payment
`Draft → Posted → Allocated/Partially Allocated → Reconciled`

### Workshop job
`Draft → Open → In Progress → Ready for Invoice → Invoiced → Closed`

### Material/outside-work/labour entry
`Draft → Confirmed → Invoiced`

Posted or finalized records must not be physically deleted.

---

## 10. UI/UX direction for the new system

- Use search/autocomplete for customer, owner, supplier, vehicle, agreement, item, mechanic and GL account.
- Show name/vehicle number first; show internal codes as secondary metadata only.
- Split complex forms into focused steps.
- Hide irrelevant fields based on agreement type, tax type and driver configuration.
- Provide a calculation preview with a clear breakdown.
- Display warnings before posting, allocating, reversing or closing.
- Put history, audit, ledger entries and reports in separate tabs/drawers.
- Use explicit status badges and dates.
- Prevent duplicate active agreements and invalid period overlaps.
- Provide fast search by vehicle number, customer, job, agreement and invoice.

---

## 11. Acceptance criteria derived from the recordings

### Rental
- A vehicle cannot be billed without a valid active agreement for the billing date.
- Running-chart odometer finish must not be less than start.
- Running-chart dates must be inside the agreement period unless an authorized override is recorded.
- The system must calculate included and excess kilometres consistently.
- Customer invoice and owner payable must use the same finalized operational records without double counting.
- Receipt allocation cannot exceed either unallocated receipt value or invoice outstanding value.
- Posted documents cannot be edited or deleted.
- Reconciliation must not alter the original payment amount or posting date.

### Workshop
- A job requires a customer and vehicle.
- Material issue cannot exceed available stock unless an authorized negative-stock policy explicitly allows it.
- Outside work requires a supplier/creditor.
- Labour requires a service category and responsible mechanic/employee where applicable.
- Job invoice total must equal the stored line breakdown.
- A job cannot close while uninvoiced confirmed costs remain, unless an authorized exception is recorded.
- Vehicle history and service reminder values update only after the relevant job event is finalized.

### Finance
- Debits and credits must balance for every posting batch.
- Posting periods must be open.
- Every journal line must reference its source document and module.
- Reversal entries must reference the original posting.
- Number sequences must be unique under concurrency.

---

## 12. Final conclusion

The four videos collectively provide a strong functional blueprint for both the **rental** and **workshop** areas of AutoERP. The most important implementation principle is to preserve the real workflow and calculation behavior while redesigning security, data integrity, auditability, usability and module ownership. The target system should not copy the legacy screen structure or mutable accounting behavior.
