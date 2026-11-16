# Vehicle Rental Complete Technical & Operational Guide

**Date:** 2026-11-16  
**Scope:** `VehicleRental`, `Documentation`, `Architecture`  
**Focus:** Creation of the authoritative, beginner-friendly, and complete technical and operational guide for the Vehicle Rental module.

---

## 1. Summary of Work Done

1. **Created Comprehensive Technical Guide**:
   - Location: [`docs/modules/vehicle_rental_complete_guide.md`](file:///d:/project-new/AutoERP/docs/modules/vehicle_rental_complete_guide.md)
   - Scope: Documented all 28 core database tables, 38 enums, 38 domain services, 21 controllers, 33 frontend Vitest suites, and 41 backend PHPUnit test suites (306 tests, 2,477 assertions).
   - Content:
     - **Executive & Architectural Overview:** Dual-track operational decoupling (Customer AR vs Owner AP vs Physical Fleet Custody), "Do Not Invent Money" invariant, Decoupled Commercial Independence, Exact BCMath Decimal Precision, Civil Timezone Standard (`Asia/Colombo`), and Optimistic Concurrency Control (OCC).
     - **Database Architecture:** Master Mermaid ERD diagram, detailed definitions for all 28 core tables, immutable audit history tables, and the 12-key terms JSON schema.
     - **Subsystems & State Machines:** Detailed operational lifecycles for Reservations, Customer Agreements, Owner Agreements, Vehicle Custody (`VehicleUse`), Mid-Lease Atomic Swaps, Driver Reassignments, Daily Running Charts, Fleet Operating Expenses (4-Way Allocation Matrix), Damage Incidents with private proofs, Vehicle Finance Amortization, and Agreement Adjustments.
     - **The Two-Stage Invoicing Engine:** Detailed mathematical proration across vehicles by physical custody days, candidate running chart attachment, multi-tier overtime calculations (Normal 1.5x, Double 2.0x, Triple 3.0x), AC differentials, Stage 2 Supplementary Mode A with line discount deduction mapping (`applyDeductionsAsDiscounts`), negative delta Credit Notes with open balance offsetting, Mode B Consolidated Revisions with payment transfer, and bidirectional source restoration upon cancellation.
     - **Owner Settlement AP Billing:** Gross lease rent, usage reimbursements, statutory 10% Withholding Tax (:3305-000) deduction accounting, fleet maintenance chargebacks, deduction capping safeguards, and full restoration on cancellation.
     - **Multi-Agreement Financial Settlements:** Cross-agreement payment tenders, interactive Document Allocation Grid, party-wide unapplied advance pooling, and payment reversals.
     - **Financial Integrations & GL Ledger:** Double-entry journal entry table for customer invoices, supplementary invoices, owner settlement bills, payments, and statutory withholdings.
     - **Enterprise Reporting Suite:** 8 analytical reports (Fleet Utilization, Revenue Licenses, Rental Revenue, Lessee Outstanding, Owner Disbursements, Vehicle Profitability, Incidents Summary, Running Chart Sheets) plus dedicated Telematics Statements (HTML, Print, PDF).
     - **Frontend UI/UX Architecture:** Component hierarchy, route map, permissions, controlled selection standards, and human-readable formatting.
     - **REST API Specification:** Complete endpoint table with HTTP methods, paths, required permissions, and functional summaries.
     - **Developer Quickstart:** Testing verification checklists and guidelines for maintaining and extending the module.

2. **Linked Documentation**:
   - Updated [`docs/README.md`](file:///d:/project-new/AutoERP/docs/README.md) to feature the new guide under "Core Module Technical Guides".
   - Updated [`docs/VEHICLE_RENTAL_GUIDE.md`](file:///d:/project-new/AutoERP/docs/VEHICLE_RENTAL_GUIDE.md) with a prominent banner pointing to the canonical master guide.

---

## 2. Key Files Created and Modified

- [`docs/modules/vehicle_rental_complete_guide.md`](file:///d:/project-new/AutoERP/docs/modules/vehicle_rental_complete_guide.md) (Created)
- [`docs/README.md`](file:///d:/project-new/AutoERP/docs/README.md) (Updated)
- [`docs/VEHICLE_RENTAL_GUIDE.md`](file:///d:/project-new/AutoERP/docs/VEHICLE_RENTAL_GUIDE.md) (Updated)

---

## 3. Verification Record

- **Test Suite Execution:** Ran all 41 test files in `app/Modules/VehicleRental/Tests/`:
  - `php artisan test app/Modules/VehicleRental/Tests/`: **306 passed, 2,477 assertions** (Duration: 95.28s).
- **TypeScript Strict Check:** `npm run typecheck`: **0 errors**.
- **Documentation Verification:** Validated all file paths and verified code accuracy directly against the implementation.
