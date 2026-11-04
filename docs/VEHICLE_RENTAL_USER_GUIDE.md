# AutoERP Vehicle Rental: End-User Operations Manual & Guide

Welcome to the **AutoERP Vehicle Rental Operations Manual**. This guide is designed for rental managers, fleet dispatchers, front-desk agents, depot clerks, and accountants. It explains how to use the Vehicle Rental system to manage bookings, contracts, vehicle handovers, daily trip logs, customer billing, owner disbursements, and fleet reports.

---

## Table of Contents

1. [Introduction to Vehicle Rental](#1-introduction-to-vehicle-rental)
2. [Navigating the Module & Quick Links](#2-navigating-the-module--quick-links)
3. [Managing Reservations & Customer Inquiries](#3-managing-reservations--customer-inquiries)
4. [Creating & Managing Customer Agreements](#4-creating--managing-customer-agreements)
5. [Managing Third-Party Vehicle Owner Agreements](#5-managing-third-party-vehicle-owner-agreements)
6. [Checking Vehicle Availability](#6-checking-vehicle-availability)
7. [Assigning Vehicles & Physical Dispatch (Handover & Return)](#7-assigning-vehicles--physical-dispatch-handover--return)
8. [Handling Vehicle Breakdowns & Swaps (Atomic Replacement)](#8-handling-vehicle-breakdowns--swaps-atomic-replacement)
9. [Recording Daily Running Charts (Trip Sheets)](#9-recording-daily-running-charts-trip-sheets)
10. [Customer Billing: Base Rent & Usage Invoicing](#10-customer-billing-base-rent--usage-invoicing)
11. [Collecting & Refunding Security Deposits](#11-collecting--refunding-security-deposits)
12. [Recording Fleet Operating Expenses (Fuel, Tolls, Repairs)](#12-recording-fleet-operating-expenses-fuel-tolls-repairs)
13. [Logging Incidents, Accidents & Traffic Fines](#13-logging-incidents-accidents--traffic-fines)
14. [Managing Vehicle Bank Leases & Financing](#14-managing-vehicle-bank-leases--financing)
15. [Official Reports & Exporting A4 Trip Sheets](#15-official-reports--exporting-a4-trip-sheets)
16. [Frequently Asked Questions & Troubleshooting](#16-frequently-asked-questions--troubleshooting)

---

## 1. Introduction to Vehicle Rental

### How AutoERP Manages Vehicle Rentals

In commercial transport and fleet operations, vehicle rental works on a **dual-sided business model**:
- **Customer Side (Revenue):** Clients rent vehicles from your company. You invoice them for daily or monthly base rent, excess kilometers driven, driver overtime, and overnight outlays.
- **Owner Side (Cost):** When your company leases vehicles from external owners/suppliers, you pay those owners agreed monthly lease rates, reimburse them for driver overtime, or deduct operational expenses (like fuel or repairs paid on their behalf).
- **Company Fleet:** When vehicles are owned directly by your company, they are flagged as company-owned; zero external owner payables are created.

```text
+---------------------------------------------------------------------------------+
|                                 DAILY RUNNING CHART                             |
|                   (Physical Log: Distance, Overtime, Tolls, Night-Outs)         |
+---------------------------------------------------------------------------------+
                                       |
                   +-------------------+-------------------+
                   |                                       |
                   v                                       v
        [ CUSTOMER BILLING ]                      [ OWNER DISBURSEMENT ]
      Customer Base Rent Invoice                 Owner Base Lease Settlement
     + Excess Mileage Invoices                  + Excess KM Payout
     + Overtime & Night-Out Fees                + Driver OT Reimbursements
     = Total Customer Receivable                - Operating Expense Deductions
                                                = Net Owner Payable
```

### Key Rules to Remember

1. **No Raw Database Codes:** The system displays human-readable names, vehicle registration numbers (e.g., `WP-PX-2020`), and party names instead of raw database IDs.
2. **Never Edit an Active Contract Directly:** Once an agreement is marked **Active**, its rates are locked to protect past invoices. If rates change, use the **Create Successor** button to establish a revision without disrupting current billing.
3. **Physical Truth is Permanent:** Finalized trip sheets (Running Charts) cannot be altered directly. If a mistake was made, use the **Correct** button to issue an auditable correction.

---

## 2. Navigating the Module & Quick Links

When you log into AutoERP, select the **Vehicle Rental** workspace from the main navigation sidebar. The key screens available to you are:

| Menu Item / Feature | URL Path | What You Can Do Here |
|---|---|---|
| **Rental Dashboard** | `/vehicle-rental/dashboard` | View live metrics: active agreements, vehicles in custody, pending bookings, and today's trips. |
| **Reservations** | `/vehicle-rental/reservations` | Log phone/email inquiries, check dates, and convert confirmed leads into agreements. |
| **Customer Agreements** | `/vehicle-rental/customer/agreements` | Create client contracts, set daily/monthly rates, bill rent, and track deposits. |
| **Owner Agreements** | `/vehicle-rental/owner/agreements` | Manage contracts with external vehicle owners and lessors. |
| **Vehicle Dispatch Register** | `/vehicle-rental/uses` | View all active and planned vehicle assignments across your fleet. |
| **Fleet Availability** | `/vehicle-rental/vehicles/available` | Check real-time vehicle availability for future dates without booking collisions. |
| **Running Charts Register** | `/vehicle-rental/running-charts` | Log daily trip sheets, start/end odometers, commercial KM, overtime, and AC modes. |
| **Fleet Expenses** | `/vehicle-rental/expenses` | Record petrol, tolls, and maintenance bills; allocate them to company, customer, or owner. |
| **Incident Register** | `/vehicle-rental/incidents` | Record accidents, damage, police fines, or traffic violations and attach file proofs. |
| **Vehicle Finance** | `/vehicle-rental/finance-agreements` | Track bank leasing contracts, installment schedules, and maturing payable invoices. |
| **Reports Hub** | `/vehicle-rental/reports` | Generate 8 official reports, including the 31-day Running Chart matrix sheet. |

---

## 3. Managing Reservations & Customer Inquiries

When a prospective client contacts your company to rent a vehicle, use the **Reservations** screen to capture their inquiry before creating a binding contract.

```text
[ Step 1: Lead Capture ]       [ Step 2: Verification ]       [ Step 3: Commitment ]
  Customer Inquires     -->     Verify Availability     -->    Convert to Agreement
  (Draft Reservation)           (Confirm Booking)             (Execute Contract)
```

### How to Create a New Reservation

1. Navigate to **Vehicle Rental > Reservations** (`/vehicle-rental/reservations`).
2. Click the **New Reservation** button in the top right.
3. Complete the form:
   - **Customer:** Type the customer's name to select them from the dropdown list.
   - **Requested Vehicle (Optional):** If the customer requested a specific vehicle, select it. Otherwise, leave it blank.
   - **Rental Mode:** Choose **Self Drive** (client drives) or **With Driver** (company provides chauffeur).
   - **Billing Cycle:** Choose **Daily Rental** or **Monthly Rental**.
   - **Start & End Date/Time:** Select the requested departure and return date/time.
   - **Currency:** Select the agreed currency (e.g., `LKR`, `USD`).
   - **Estimated Rent Amount:** Enter the quoted estimate (e.g., `75,000.00`).
   - **Estimated Deposit Amount:** Enter the required refundable deposit (e.g., `50,000.00`).
   - **Inquiry Source:** Select how the lead came in (**Phone Inquiry**, **Walk-in**, **Email**, or **Website Booking**).
   - **Remarks & Notes:** Add any special customer requests (e.g., "Airport pickup required", "Child safety seat requested").
4. Click **Create Reservation**.

### Managing Reservation Statuses

From the reservations table, click on any booking to update its status:
- **Pending:** Mark as pending while waiting for the client to confirm their schedule.
- **Confirmed:** Mark as confirmed once the client agrees and a vehicle is secured.
- **Convert to Agreement:** When the client arrives to sign the contract, click **Convert to Agreement**. The system will automatically create an active `CustomerAgreement` copying over the customer, currency, dates, and driver mode.

---

## 4. Creating & Managing Customer Agreements

A **Customer Agreement** is the official commercial contract defining what you charge the customer.

### How to Create a Customer Agreement

1. Navigate to **Vehicle Rental > Customer Agreements** (`/vehicle-rental/customer/agreements`).
2. Click **New agreement**.
3. Complete the agreement details:
   - **Agreement reference:** Enter your contract reference code (e.g., `AGR-2026-0045`).
   - **Customer:** Search and select the client.
   - **Currency:** Select contract currency (e.g., `LKR`).
   - **Agreement date:** Defaults to today's date.
   - **Start date:** The date rental coverage begins.
   - **End date (Optional):** The agreed end date. Leave blank if open-ended / long-term.
   - **Rental basis:** Select **Daily** or **Monthly**.
   - **Driver arrangement:** Select **Self-drive** or **With driver**.
4. Expand **Agreed rates and allowances** to specify pricing terms:
   - *Base rental rate:* The fixed charge per day or per month.
   - *Included distance (km):* Free kilometers allowed per cycle (e.g., `100 km/day` or `3,000 km/month`).
   - *Excess distance rate per km:* Amount charged for every kilometer driven over the free allowance.
   - *Agreed driver amount:* Fixed chauffeur allowance per cycle.
   - *Normal / Double / Triple overtime hourly rates:* Rates for extra hours.
   - *Night-out rate:* Daily charge when the vehicle/driver stays overnight away from the home depot.
   - *Agreed security deposit:* Required refundable deposit amount.
5. Click **Save draft**.

### Activating the Agreement
While in `Draft` status, you can freely edit contract terms. Once verified against the physical paper contract:
1. Click **Review [Reference]**.
2. Click **Activate**.
3. Confirm activation. The agreement is now **Active** and ready for vehicle dispatch and billing.

### Mid-Contract Rate Changes (Creating a Successor)
> [!IMPORTANT]
> Never attempt to change terms on an active agreement retroactively. This would corrupt past billing records.

If a client negotiates a new monthly rate after 6 months:
1. Open the active agreement review screen.
2. Click **Create successor**.
3. Enter the new reference (e.g., `AGR-2026-0045-REV1`), effective start date, and revision reason.
4. Update the newly agreed rates in the draft.
5. On the effective date, click **Activate**. The system will atomically close the predecessor agreement and activate the successor without interrupting vehicle custody.

---

## 5. Managing Third-Party Vehicle Owner Agreements

When your company leases vehicles from private owners or fleet investors to rent out to your clients, use **Owner Agreements** (`/vehicle-rental/owner/agreements`).

### Step-by-Step: Setting Up an Owner Agreement

1. Navigate to **Vehicle Rental > Owner Agreements**.
2. Click **New agreement**.
3. Fill in the lessor details:
   - **Agreement reference:** Contract reference with the owner (e.g., `OWN-2026-012`).
   - **Owner / supplier:** Search and select the vehicle owner from your supplier directory.
   - **Vehicle:** Select the specific vehicle supplied by this owner.
   - **Start date & End date:** The period the owner has placed the vehicle in your fleet.
4. In **Agreed rates and allowances**, enter what you owe the owner:
   - *Owner base rental payable:* Guaranteed monthly or daily payout to the owner.
   - *Owner included distance (km):* Free distance covered under the base payout.
   - *Owner excess distance payable per km:* What you pay the owner if your client exceeds mileage.
   - *Driver / Overtime reimbursements:* Reimbursements owed if the owner provided their own driver.
5. Save as draft and click **Activate**.

---

## 6. Checking Vehicle Availability

Before promising a car to a customer, verify that it is physically and commercially available.

1. Navigate to **Vehicle Rental > Fleet Availability** (`/vehicle-rental/vehicles/available`).
2. Enter your desired **Start Date & Time** and **End Date & Time**.
3. Optionally filter by **Vehicle Category** (e.g., Sedan, SUV, Van).
4. Click **Check Availability**.
5. The system lists all fleet vehicles with real-time status badges:
   - <span style="color:green;font-weight:bold;">Available:</span> Free for dispatch.
   - <span style="color:blue;font-weight:bold;">In Custody:</span> Currently out with another customer.
   - <span style="color:orange;font-weight:bold;">Workshop / Maintenance:</span> Booked for mechanical service in the workshop.
   - <span style="color:purple;font-weight:bold;">Reserved:</span> Reserved under an active booking.

---

## 7. Assigning Vehicles & Physical Dispatch (Handover & Return)

Once a Customer Agreement is **Active**, you can assign a vehicle and record physical dispatch.

### Step 1: Assigning a Vehicle to the Contract
1. Go to **Customer Agreements**, find the active agreement, and click **Review**.
2. Switch to the **Assigned Vehicles & Handover** tab.
3. Click **Assign vehicle**.
4. In the form:
   - **Vehicle:** Select the vehicle from the dropdown.
   - **Planned handover:** Enter intended departure date and time.
   - **Planned return:** Enter intended return date and time (leave blank if open-ended).
   - **Supply Source:**
     - If company-owned: Check **Company-owned vehicle**.
     - If leased from an owner: Leave unchecked and select the active **Owner agreement**.
   - **Notes:** Add any dispatch notes.
5. Click **Save assignment**.

### Step 2: Handover (Vehicle Leaves Depot)
When the client arrives to take the vehicle:
1. In the **Assigned Vehicles** list, find the planned vehicle and click **Handover**.
2. Enter:
   - **Handover Date & Time:** Exact moment the keys are handed over.
   - **Starting Odometer:** Current speedometer reading (e.g., `45,210 km`).
   - *(Note: The system will reject any odometer reading lower than the vehicle's last recorded mileage).*
3. Click **Confirm Handover**. The vehicle status changes to **In Custody**.

```text
[ Depot Dispatch Desk ]
1. Inspect vehicle exterior with client.
2. Read speedometer: 45,210 km.
3. Click Handover -> Enter 45,210 km -> Confirm.
Vehicle is now officially dispatched!
```

### Step 3: Return (Vehicle Comes Back to Depot)
When the rental concludes and the client returns the vehicle:
1. Open the agreement, go to **Assigned Vehicles & Handover**, and click **Return vehicle**.
2. Enter:
   - **Return Date & Time:** Exact moment the car returned.
   - **Return Odometer:** Speedometer reading upon return (e.g., `46,850 km`).
   - *(Note: Must be greater than or equal to the starting odometer).*
3. Click **Confirm Return**.
4. The vehicle status updates to **Returned** and the asset becomes instantly available for future dispatches.

---

## 8. Handling Vehicle Breakdowns & Swaps (Atomic Replacement)

If a car breaks down, experiences a flat tire, or requires urgent depot maintenance during an active rental, use the **Replace vehicle** function.

> [!TIP]
> **Why use Replace Vehicle instead of Return + New Assignment?**
> `Replace vehicle` handles the return of the disabled car and the dispatch of the fresh car in a **single step**. Crucially, the replacement car shares the customer's existing monthly free mileage allowance—preventing accidental double base rent charges!

### How to Perform a Vehicle Replacement

1. Open the Customer Agreement and navigate to the **Assigned Vehicles & Handover** tab.
2. Locate the active vehicle and click **Replace vehicle**.
3. In the replacement form:
   - **Old vehicle return odometer:** Enter the final mileage on the vehicle being brought back.
   - **Replacement vehicle:** Select the replacement car from the available fleet.
   - **Replacement handover odometer:** Enter the starting mileage of the new vehicle.
   - **Replacement time:** Exact date and time of the swap.
   - **Supply source:** Select company-owned or choose the owner agreement covering the new car.
   - **Replacement reason:** Enter a mandatory explanation (e.g., "Air conditioner failure on vehicle WP-PX-2020; swapped for WP-CA-1122").
4. Click **Confirm replacement**.
5. Both vehicles' records update atomically in the database without disrupting the customer's billing cycle.

---

## 9. Recording Daily Running Charts (Trip Sheets)

The **Running Chart** is the official daily log of where the vehicle traveled, what distances were driven, which driver operated the vehicle, and any extra overtime or toll charges incurred.

```text
+-----------------------------------------------------------------------------------------+
|                                DAILY RUNNING CHART FORM                                 |
+-----------------------------------------------------------------------------------------+
| Chart Ref: RC-2026-11-04-01       Date: 2026-11-04 08:00 to 2026-11-04 19:30            |
| Start Odo: 45,210 km              End Odo: 45,460 km              Total: 250 km         |
| Commercial KM: 230 km             Garage KM: 20 km                AC Mode: Dual AC      |
| Driver: D-104 (Sunil Perera)      Normal OT: 90 mins              Night Outs: 0         |
+-----------------------------------------------------------------------------------------+
```

### How to Record a Daily Running Chart

1. Open **Vehicle Rental > Running Charts** (`/vehicle-rental/running-charts`) or access it directly from the vehicle assignment panel.
2. Click **Record Running Chart**.
3. Enter primary trip facts:
   - **Chart reference:** Enter driver trip sheet voucher number (e.g., `RC-2026-11-04-01`).
   - **Usage start & Usage end:** Date and time the journey started and ended.
   - **Start odometer & End odometer:** Speedometer readings at start and finish.
4. Expand **Additional usage observations**:
   - **Commercial distance (km):** Distance driven for the customer.
   - **Garage distance (km):** Deadhead travel between depot and pickup site.
   - **Air conditioning mode:** Select **Non-AC**, **Front AC**, or **Dual AC**.
   - **Driver arrangement:**
     - Select **Employee driver** and choose your company driver from the dropdown.
     - OR select **External driver** and type the outsourced chauffeur's name and ID.
   - **Overtime minutes:** Enter any billable overtime (Normal, Double, or Triple OT in minutes).
   - **Night outs:** Enter count of overnight stays (e.g., `1` if the driver stayed out overnight).
   - **Notes:** Add details such as expressway toll pass usage, fuel stops, or client route remarks.
5. Click **Save Running Chart**.

### Finalizing Trip Sheets
When all daily logs are verified against the driver's signed log sheet:
1. Open the running chart.
2. Click **Finalize**.
3. The operational facts are permanently locked and immediately ready for automated billing.

---

## 10. Customer Billing: Base Rent & Usage Invoicing

AutoERP's billing engine automatically turns finalized contracts and running charts into commercial invoices in the [`Invoice`](file:///d:/project-new/AutoERP/app/Modules/Invoice) module.

### Part 1: Invoicing Base Rent (Monthly or Daily)
1. Open the Customer Agreement and go to the **Billing & Rate Estimates** tab.
2. Review the **Base Rent Billing** panel:
   - Click **Preview base rent** to see an exact preview of the calculation.
   - The system shows the covered cycle (e.g., `2026-11-01` to `2026-11-30`) and exact proration if partial.
3. To generate the invoice:
   - **Invoice Date:** Defaults to today.
   - **Due Date (Optional):** Enter client payment deadline.
   - **Exchange Rate:** Defaults to `1.00` for base currency.
   - Check the confirmation box: *"Apply this calendar-cycle policy..."*
   - Click **Create base rent invoice**.
4. The system posts an official customer invoice. You can click the invoice reference link (e.g., `INV-2026-0089`) to view, print, or email it to the client.

### Part 2: Assessing Commercial Mileage & Free Allowances
At the end of a trip or monthly cycle, assess whether the client exceeded their free kilometer allowance:
1. In the agreement's Running Charts panel, click **Mileage assessment** on a finalized chart.
2. The system displays a live mileage breakdown:
   - *Cycle allowance:* E.g., `3,000 km`.
   - *Commercial distance:* E.g., `3,450 km`.
   - *Included applied:* `3,000 km` (consumed free allowance).
   - *Excess distance:* `450 km`.
   - *Rate per KM:* `LKR 120.00`.
   - *Amount before tax:* `LKR 54,000.00`.
3. Check the confirmation box and click **Record mileage assessment**.
4. If excess kilometers exist, the system generates an official customer invoice for the extra distance. If within the free limit, it logs zero cost and consumes the allowance.

### Part 3: Invoicing Overtime, Night-Outs & Auxiliary Usage
1. On any finalized running chart, navigate to the **Usage Charges** panel.
2. Select the component you wish to bill: **Overtime (Normal/Double/Triple)** or **Night-Out**.
3. The system calculates the charge based on the exact minutes/counts logged on the chart multiplied by agreed agreement rates.
4. Click **Create usage charge invoice** to post the bill.

---

## 11. Collecting & Refunding Security Deposits

To protect against vehicle damage or unpaid invoices, security deposits are recorded and tracked through the [`Payment`](file:///d:/project-new/AutoERP/app/Modules/Payment) module.

### How to Collect a Customer Deposit

1. Open the Customer Agreement and switch to the **Security Deposit** tab.
2. The summary panel displays:
   - *Agreed requirement:* E.g., `LKR 50,000.00`.
   - *Net receipts:* Currently collected cash.
   - *Remaining collection capacity:* Remaining amount to collect.
3. In the deposit receipt form:
   - **Date:** Defaults to today.
   - **Exchange rate:** Defaults to `1.00`.
   - **Payment Lines:** Select payment method (**Cash**, **Bank Transfer**, **Cheque**, or **Credit Card**), enter the amount, and add transaction/cheque references.
4. Click **Create deposit receipt**.
5. The receipt is recorded. You can click the payment number to view the receipt voucher.

### Applying or Refunding Deposits
- **Apply to Final Invoice:** When the rental concludes, open the final invoice and click **Apply Payment / Deposit**. The unapplied deposit balance will settle the invoice.
- **Refund to Customer:** If the car is returned with no damages and all bills are settled, open the original deposit receipt in the Payment module and click **Refund Deposit**.

---

## 12. Recording Fleet Operating Expenses (Fuel, Tolls, Repairs)

When drivers or depot staff purchase petrol, pay highway tolls, or incur emergency puncture repairs, record them in **Fleet Expenses** (`/vehicle-rental/expenses`).

### How to Record an Expense
1. Go to **Vehicle Rental > Fleet Expenses**.
2. Click **Record Rental Fleet Expense**.
3. Fill in the details:
   - **Vehicle:** Select the vehicle that incurred the cost.
   - **Expense Type:** Select **Fuel**, **Highway Toll**, **Maintenance & Repairs**, **Traffic Fine**, or **Other Operational Cost**.
   - **Expense Date:** Date on the receipt.
   - **Net Amount & Tax Amount:** Enter the monetary values.
   - **Supplier / Station (Optional):** Select petrol station or garage from your supplier list.
   - **Bill / Receipt Reference:** Enter receipt number (e.g., `PET-88992`).
   - **Description:** Enter notes (e.g., "Full tank diesel refill at Southern Expressway exit").
4. Click **Save Expense Record**.

### Approving & Allocating Who Pays for the Expense
Once recorded, the rental manager approves the bill and decides who absorbs the cost:
1. Open the expense and click **Approve & Allocate**.
2. Choose one of four allocation destinations:
   - **Company Cost:** Your company absorbs the cost as operating overhead.
   - **Customer Recovery:** The cost is billed to the customer on their next invoice.
   - **Owner Deduction:** The cost was paid on behalf of a leased vehicle; it will be deducted from the vehicle owner's monthly lease disbursement.
   - **Employee Reimbursement:** The driver paid out of pocket; it will be reimbursed to the employee via HR/Payroll.
3. Click **Confirm Allocation**.

---

## 13. Logging Incidents, Accidents & Traffic Fines

When a vehicle suffers body damage, is involved in an accident, or incurs a police traffic fine, record it in the **Incident Register** (`/vehicle-rental/incidents`).

```text
[ Step 1: Record Facts ]       [ Step 2: Attach Proofs ]      [ Step 3: Factual Review ]
  Select Vehicle Use      -->    Upload Photos / Police   -->   Manager Reviews Evidence
  Describe Incident              Reports (PDF/JPG/PNG)          (Confirm or Reject)
```

### How to Record an Incident
1. Go to **Vehicle Rental > Incident Evidence** (`/vehicle-rental/incidents`).
2. In the **Record new incident** box:
   - **Find a vehicle use:** Type the vehicle registration or customer name and click **Find assignments**.
   - **Vehicle assignment:** Select the specific trip assignment from the dropdown list.
   - **Running Chart (Optional):** Link the specific daily trip sheet if known.
   - **Incident category:** Select **Accident / Damage**, **Traffic Fine / Penalty**, **Highway Toll Violation**, **Repair**, etc.
   - **Business date of incident:** Date the event occurred.
   - **Evidence / document reference:** Police report number, insurance claim number, or traffic citation number.
   - **Description:** Provide a factual narrative of what occurred.
3. Click **Record evidence**.

### Attaching Proof Files (Photos & Police Reports)
1. In the incident list, locate the recorded incident and click to expand **File proofs**.
2. Click **Choose File** and select your photo or PDF document (up to 10 MB).
3. Click **Attach file**.
4. The file is uploaded to secure private storage with a tamper-evident SHA-256 digital fingerprint.

### Factual Review
A rental manager reviews the incident and clicks **Confirm Evidence** (verifies that the event took place as described) or **Reject Evidence** (invalid report).
*(Note: Reviewing an incident records verified physical evidence; any financial deduction or damage recovery requires an explicit, separate billing action).*

---

## 14. Managing Vehicle Bank Leases & Financing

If your company finances fleet vehicles through commercial banks or leasing companies (e.g., Commercial Bank, HNB, LOLC), manage their amortization schedules in **Vehicle Finance** (`/vehicle-rental/finance-agreements`).

### Setting Up a Bank Lease
1. Navigate to **Vehicle Rental > Vehicle Finance**.
2. Click **New Vehicle Finance Agreement**.
3. Fill in the financing terms:
   - **Financing Company / Supplier:** Select the bank from your supplier directory.
   - **Financed Vehicle:** Select the vehicle being financed.
   - **Principal Financed Amount:** Total loan or lease value (e.g., `LKR 5,000,000.00`).
   - **Initial Deposit Paid:** Down payment paid at signing.
   - **Residual / Balloon Value:** Final lump sum payment due at lease end (if any).
   - **Annual Interest Rate (%):** E.g., `14.5%`.
   - **Interest Method:** Select **Flat Interest Rate** or **Reducing Balance**.
   - **Installment Frequency & Count:** E.g., **Monthly** with `36` installments.
4. Click **Create Agreement**, then review and click **Activate**.

### Managing Monthly Bank Installments
- The system automatically generates a complete 36-month amortization schedule.
- Click **Refresh Due Statuses** anytime to automatically identify installments maturing this month.
- When an installment becomes due, click **Create Payable Invoice**. AutoERP posts an inbound supplier invoice in the [`Invoice`](file:///d:/project-new/AutoERP/app/Modules/Invoice) module so your accounts team can schedule bank payment.

---

## 15. Official Reports & Exporting A4 Trip Sheets

Vehicle Rental includes a complete reporting suite accessible from **Vehicle Rental > Reports** (`/vehicle-rental/reports`).

### How to Print the Official 31-Day Running Chart Matrix Sheet
The 31-day Running Chart matrix sheet is the standard legal trip sheet format used across Sri Lankan fleet operations (replicating historical `pmdeblogsheet2.frx` and `pmcre1logsheet2.frx` layouts).

```text
+-----------------------------------------------------------------------------------------------+
|                      RUNNING CHART 31-DAY MATRIX SHEET (LANDSCAPE A4)                         |
| Client: John Keells Logistics               Vehicle: WP-PX-2020 (Toyota KDH)                 |
| Month: November 2026                        Mode: Customer / Lessee View                      |
+-----+------------+-----------+---------+------------+----------+-----------+------------------+
| Day | Start Time | End Time  | Start KM| End KM     | Total KM | Normal OT | Night Out | Toll |
+-----+------------+-----------+---------+------------+----------+-----------+------------------+
|  1  | 08:00      | 18:30     | 45,210  | 45,350     | 140 km   | 90 mins   |     -     |  Yes |
|  2  | 08:30      | 17:00     | 45,350  | 45,430     |  80 km   |    -      |     -     |   -  |
| ... | ...        | ...       | ...     | ...        | ...      | ...       |    ...    |  ... |
+-----+------------+-----------+---------+------------+----------+-----------+------------------+
| Signatures: Driver: Sunil Perera __________     Customer Signature: _________________________ |
+-----------------------------------------------------------------------------------------------+
```

1. Navigate to **Vehicle Rental > Reports > Running Chart 31-Day Matrix Sheet** (or click **Print Sheet** on any running chart).
2. Select:
   - **Vehicle Assignment:** Choose the customer trip assignment.
   - **Month & Year:** E.g., `November 2026`.
   - **Mode:** Select **Lessee Mode** (shows client commercial mileage) or **Owner Mode** (shows lessor settlement mileage).
3. Click **Generate Report**.
4. The complete 31-day calendar matrix displays on your screen.
5. In the top action bar:
   - Click **Print Preview** for instant browser printing.
   - Click **Download PDF** to generate an official downloadable Landscape A4 PDF with company header and driver signature blocks.

### Summary of Other Available Reports

- **Fleet Utilization Report:** Check which vehicles are active, idle, or in the workshop, and see overall fleet utilization percentages.
- **Revenue Licence Expiry Report:** Displays a countdown of vehicle revenue licence expiry dates with color-coded warnings (Red = Expired, Yellow = Expiring in 30 days).
- **Rental Revenue Summary:** Total income earned per customer contract broken down by base rent, excess KM, and overtime.
- **Lessee Outstanding Aging:** Accounts receivable aging report showing client debt across 30, 60, 90, and 120+ day buckets.
- **Owner Disbursement Ledger:** Full payout breakdown for third-party vehicle owners, including base lease entitlements, expense deductions, and Withholding Tax (WHT) statements.
- **Fleet Vehicle Profitability:** Vehicle-level profit & loss report showing total income generated minus operating costs (fuel, repairs, owner payouts) to reveal the true net profit per vehicle.

---

## 16. Frequently Asked Questions & Troubleshooting

### Q1: Why does the system say "Conflict: The record has been modified by another user"?
**Answer:** AutoERP uses optimistic concurrency protection. If another staff member updated the agreement, vehicle use, or running chart while you had it open on your screen, the system stops you from overwriting their changes. Simply refresh your browser tab to see the latest version, then reapply your edit.

### Q2: Why is a vehicle not appearing in the vehicle assignment dropdown?
**Answer:** The vehicle may already be booked or unavailable. Check the following:
1. Is the vehicle currently dispatched to another customer? (Check **Fleet Availability**).
2. Is the vehicle currently admitted to the workshop in **Vehicle Service**?
3. If it is an owner-leased vehicle, does the owner agreement cover the dates you selected?

### Q3: Can I delete an invoice or running chart if I made a mistake?
**Answer:** No. In commercial accounting and fleet management, deleting records creates financial audit discrepancies. Instead:
- To correct a trip sheet: Open the running chart and click **Correct**. The system reverses the old chart and creates an editable copy.
- To cancel an unposted billing item: Click **Void**.

### Q4: What happens if a client returns a car with less mileage than when it left?
**Answer:** The system strictly validates odometer continuity. If you try to enter a return odometer lower than the handover odometer, the form displays a validation error: *"Return odometer cannot be less than handover odometer."* Re-check the vehicle's speedometer reading.

### Q5: Can I give a customer a discount on their monthly base rent?
**Answer:** Yes. When setting up or reviewing a `Draft` customer agreement, you can enter any agreed monthly or daily rate. If the agreement is already active, click **Create successor** to negotiate and activate the discounted rate starting from the next billing cycle.

---

## Summary & Best Practices Checklist

- [x] **Verify Before Dispath:** Always check fleet availability before promising cars to clients.
- [x] **Record Immediate Odometer Readings:** Inspect the speedometer at handover and return; record readings immediately.
- [x] **Finalize Daily Charts Promptly:** Ensure drivers submit signed trip sheets daily so billing clerks can invoice without delays.
- [x] **Use Replace Vehicle for Breakdowns:** Never manually return and re-assign a breakdown car; use atomic replacement so free mileage allowances remain shared.
- [x] **Review Before Activation:** Confirm customer rates carefully before clicking Activate.
- [x] **Attach Clear Incident Proofs:** When accidents occur, upload clear photos and police vouchers immediately to protect company liability.
