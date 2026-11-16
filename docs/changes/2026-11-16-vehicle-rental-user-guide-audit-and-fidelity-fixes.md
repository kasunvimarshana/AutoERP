# Vehicle Rental User Guide: Audit, Terminology Parity, and Fidelity Upgrades

**Date:** 2026-11-16  
**Scope:** `VehicleRental`, `User Guides`, `Documentation`, `UI/UX Fidelity`  
**Focus:** Review, defect remediation, and comprehensive expansion of the Vehicle Rental End-User Guide (`docs/user-guides/vehicle_rental_user_guide.md`) to establish 100% fidelity to the actual React implementation, button labels, modal tabs, and error prevention guards.

---

## 1. Summary of Changes

A rigorous audit of the prior user guide draft against the React components in `resources/js/modules/vehicle-rental/` and `resources/js/modules/payment/` revealed multiple terminology deviations, missing operational actions, and inaccurate modal descriptions. The following updates were implemented:

1. **Agreements Workspace & Financial Review (`AgreementFinancialSummaryPanel.tsx`, `DepositPanel.tsx`):**
   - **Corrected Security Deposits:** Replaced incorrect claim that deposit collection opens a generic modal. Documented the actual **inline deposit receipt creation form** in `DepositPanel.tsx` (`Deposit receipt date`, `exchange_rate`, `reference_number`, `notes`, `PaymentLineTable`, and button `Create deposit receipt`).
   - **Documented Financial Position & Notes Tab:** Added comprehensive guidance for `AgreementFinancialSummaryPanel`, detailing how operators issue ad-hoc agreement adjustments (`Credit Note` / `Debit Note` targeting invoices), execute **Deposit Allocation to Invoice** without new cash receipts, record agreement payments directly via `AgreementPaymentModal`, and disburse cash/bank refunds against unapplied balances.

2. **Vehicle Dispatch & Custody Management (`VehicleUsePanel.tsx`, `VehicleUseEditor.tsx`, `ReassignDriverModal.tsx`):**
   - **Corrected Button Terminology:** 
     - Assignment submit button is `Save assignment` (not `Assign vehicle`). Checkbox is `Company-owned vehicle`. Added `Auto-select owner agreement` helper documentation.
     - Handover button on planned rows is `Hand over vehicle` (not `Handover vehicle`), featuring quick buttons `Use planned handover time` and `Use current time`, followed by `Confirm handover`.
     - Return button on active rows is `Record return` (not `Return vehicle`), featuring quick button `Use current time` and `Confirm return`.
   - **Corrected Driver Reassignments:** Corrected false claim of external vs employee driver radio buttons in `ReassignDriverModal`. Clarified that `ReassignDriverModal` strictly searches the staff directory by employee name/number and provides `Confirm Driver Reassignment`. Clarified that driver reassignments can be executed at the vehicle assignment level OR individually on a specific running chart.

3. **Daily Running Charts & Telematics Log (`RunningChartsPanel.tsx`, `RunningChartEditor.tsx`):**
   - **Button Parity:** Corrected `Finalize usage` (was `Finalize chart`) and `Reverse usage` (was `Reverse chart`). Documented the `Create correction` lineage button on reversed charts.
   - **Real-World Guard Messages:** Documented the exact validation error messages preventing early/late finalization:
     - *"Usage has not started yet. Running charts can only be finalized as physical evidence after the trip has concluded."*
     - *"Usage is currently ongoing. Running charts can only be finalized as physical evidence after the trip has concluded."*
     - *"Usage ends after vehicle was returned. Edit the draft chart to match custody dates."*
   - **Direct Sheet Printing:** Documented `Print Sheet` (lessee) and `Print Owner Sheet` (supplier) buttons located directly in the Running Charts panel.
   - **Draft Badges:** Documented real-time status badges (`Upcoming trip`, `Trip in progress`, `Ready to finalize`, `Exceeds return custody`).

4. **Customer Invoicing & Billing Cockpit (`RentalInvoicesAndBillsPage.tsx`, `RentalInvoiceCreateModal.tsx`, `BillingCycleWizardModal.tsx`):**
   - Documented both `+ Advance Base Rent` (direct standalone base rent creation) and `+ Billing Cycle Invoice` (full telematics cycle wizard).
   - Documented all 4 sub-tabs of the cycle wizard: `overview`, `charts`, `items` (`Additions & Deductions`), and `finalize`.
   - Documented direct `Record Payment` launch from within `RentalBillingDocumentDetailModal`.

5. **Payment Processing & Multi-Agreement Settlements (`PaymentEntryPage.tsx`):**
   - Documented all **4** financial KPI cards: `Total Tendered`, `Total Allocated`, `Unapplied Advance / Surplus`, and `Balance After Payment` (previously omitted).
   - Documented table controls: `Pay full balance on all`, `Clear allocations`, per-document `Pay full`, and `DecimalInput` manual allocations.

6. **Fleet Expenses, Recoveries & Incidents (`RentalExpensesPage.tsx`, `IncidentRegisterPage.tsx`):**
   - Documented the customer recovery billing form: checkbox `Issue as Debit Note adjustment (unchecked issues standard Sales Invoice)` and `Generate & Issue Invoice`.
   - Documented the `Unlink` button that restores unbilled status if a linked invoice is cancelled.
   - Documented incident file proof specifications (PDF, JPEG, PNG <= 10 MB, non-deletable once saved; reviewed incidents cannot accept new files).
   - Documented manager review actions: `Confirm evidence` / `Reject evidence` with required explanation.

7. **Expanded Operational Modules:**
   - **Vehicle Availability:** Documented direct `Reserve` and `Dispatch` action buttons on each vehicle row linking into reservations and agreements.
   - **Reservations:** Documented reservation lifecycle and the `Convert to Agreement` action.
   - **Vehicle Finance:** Documented hire purchase and loan schedule management.
   - **Operations Dashboard:** Documented real-time fleet KPI cards.
   - **Reports & Matrix Sheet:** Documented the 3 report categories and the 31-day running chart matrix sheet export.

---

## 2. Key Files Modified and Created

- [`docs/user-guides/vehicle_rental_user_guide.md`](file:///d:/project-new/AutoERP/docs/user-guides/vehicle_rental_user_guide.md) (Updated: Comprehensive handbook with 100% UI fidelity)
- [`docs/changes/2026-11-16-vehicle-rental-user-guide-audit-and-fidelity-fixes.md`](file:///d:/project-new/AutoERP/docs/changes/2026-11-16-vehicle-rental-user-guide-audit-and-fidelity-fixes.md) (Created)

---

## 3. Verification Record

- **TypeScript Compilation & Syntax Verification:**
  - `npm run typecheck`: **0 errors**.
- **Frontend Test Suite Execution:**
  - `npx vitest run resources/js/modules/vehicle-rental/`: **33 passed (33 test files), 154 passed (154 tests)**.
  - `npx vitest run resources/js/modules/payment/`: **5 passed (5 test files), 11 passed (11 tests)**.
- **Working Tree & Git Status:**
  - Clean working tree for application code. Only documentation and change records touched.
