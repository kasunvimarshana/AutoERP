# Vehicle Rental Module: Comprehensive End-User Guide & Operator Handbook

**Date:** 2026-11-16  
**Scope:** `VehicleRental`, `Documentation`, `User Guides`, `UI/UX`  
**Focus:** Creation of an end-user guide and operator handbook for daily business users (Dispatchers, Fleet Officers, Billing Clerks, Cashiers, and Managers), complementing the canonical technical specification.

---

## 1. Summary of Changes

1. **Created Authoritative End-User Guide (`docs/user-guides/vehicle_rental_user_guide.md`):**
   - Authored an exhaustive, beginner-friendly operator handbook spanning all 11 required operational areas:
     - **Welcome & Navigation Overview:** Role personas (Dispatcher, Field Clerk, Billing Clerk, AP Officer, Cashier, Fleet Manager), full sidebar navigation hierarchy, and AutoERP core operating principles (*Do Not Invent Money*, human-readable autocomplete inputs, audit trail preservation, civil business calendar timezone).
     - **Customer Rental Agreements:** Step-by-step wizard walkthrough, contract fields, rate setup grid (Base hire, included km, excess km rate, multi-tier overtime rates 1.5x/2.0x/3.0x, night-outs, deposit requirement), review panel tabs, activation/freezing, security deposit management, successor agreement lineage, and contract termination.
     - **Vehicle Owner Lease Agreements:** Supplier vehicle onboarding, decoupling customer vs owner commercial terms, company fleet handling, lessor payable rates, and contract lifecycle.
     - **Vehicle Dispatch & Custody Management:** Vehicle availability checks, dispatch planning, physical handover with start mileage and checklist, mid-lease atomic vehicle swaps/replacements (linking primary and replacement vehicles under shared contract allowances), return inspections, and driver assignments/reassignments.
     - **Daily Running Charts & Telematics Log:** Creating daily trip sheets, commercial vs private (garage) distance accounting, multi-tier driver overtime tracking (normal, double, triple), night-out allowances, expressway toll passes, supervisor approval/freezing, and reversal audit trails.
     - **Customer Invoicing & Telematics Statements:** The Two-Stage Invoicing Engine (Stage 1 advance provisional base rent, running chart pooling, addition/deduction adjustments, Stage 2 supplementary invoices via Mode A vs consolidated revisions via Mode B, credit notes for negative deltas, and printing official Telematics Statements).
     - **Dedicated Invoices & Owner Bills Register:** The dual-tab AR/AP workspace (`/vehicle-rental/billing`), reading real-time financial KPI cards (Total Invoiced, Total Collected, Outstanding, Overdue), filtering by status and date, inspecting itemized charges and telematics summaries, editing draft terms, and cancelling unposted records with mandatory audit reasons.
     - **Vehicle Owner Settlements & AP Bills:** Converting lessor earnings into formal supplier purchase invoices, statutory 10% Withholding Tax (WHT `:3305-000`) computation, fleet maintenance chargeback deductions, and settlement cancellation with automatic chargeback restoration.
     - **Payment Processing & Multi-Agreement Settlements:** Tendering single payments across multiple agreements, using the interactive allocation grid, one-click auto "Pay Full", tracking surplus advance/unapplied balances, printing branded receipts and canvas cheques, reversals, and refunds.
     - **Fleet Operating Expenses & Damage Incidents:** Recording fleet costs, the 4-way allocation matrix (Company Cost, Customer Recovery, Owner Deduction, Split), billing recoveries via Debit Notes or Invoices, and logging incident evidence (photos, police reports) with review verification.
     - **End-User Troubleshooting & FAQs:** Comprehensive guidance addressing 8 practical real-world questions (future timestamp validation, return odometer checks, invoice cancellation restrictions, replacement km pooling, 10% WHT explanation, unapplied advances, running chart reversals, and Mode A vs Mode B selection).

2. **Cross-Linked Documentation:**
   - Updated [`docs/README.md`](file:///d:/project-new/AutoERP/docs/README.md) to index the new User Guide under core module technical and user guides.
   - Updated [`docs/VEHICLE_RENTAL_GUIDE.md`](file:///d:/project-new/AutoERP/docs/VEHICLE_RENTAL_GUIDE.md) to cross-link the End-User Guide in the authoritative header callout.
   - Updated [`docs/modules/vehicle_rental_complete_guide.md`](file:///d:/project-new/AutoERP/docs/modules/vehicle_rental_complete_guide.md) to cross-link the Operator Handbook for daily business users.

---

## 2. Key Files Modified and Created

- [`docs/user-guides/vehicle_rental_user_guide.md`](file:///d:/project-new/AutoERP/docs/user-guides/vehicle_rental_user_guide.md) (Created: 650+ lines, comprehensive handbook)
- [`docs/README.md`](file:///d:/project-new/AutoERP/docs/README.md) (Modified: Cross-linked User Guide)
- [`docs/VEHICLE_RENTAL_GUIDE.md`](file:///d:/project-new/AutoERP/docs/VEHICLE_RENTAL_GUIDE.md) (Modified: Cross-linked User Guide)
- [`docs/modules/vehicle_rental_complete_guide.md`](file:///d:/project-new/AutoERP/docs/modules/vehicle_rental_complete_guide.md) (Modified: Cross-linked User Guide)
- [`docs/changes/2026-11-16-vehicle-rental-comprehensive-end-user-guide.md`](file:///d:/project-new/AutoERP/docs/changes/2026-11-16-vehicle-rental-comprehensive-end-user-guide.md) (Created)

---

## 3. Verification Record

- **TypeScript Compilation & Syntax Verification:**
  - `npm run typecheck`: **0 errors**.
- **Working Tree & Git Status:**
  - Verified git status clean for existing source code; only documentation and change record added/updated.
- **Markdown & Link Integrity:**
  - Verified file links formatted with `file:///` scheme.
  - Verified Mermaid and ASCII diagrams render correctly.
