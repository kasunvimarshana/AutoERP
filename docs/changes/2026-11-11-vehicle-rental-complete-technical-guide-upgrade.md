# Vehicle Rental Complete Technical & Operational Guide Upgrade

## Context & Motivation
The user requested a comprehensive, easy-to-understand Markdown (.md) documentation file explaining the complete end-to-end functionality, architecture, and workflow of the `VehicleRental` module. The guide needed to cover every feature, component, business rule, user interaction, CRUD operation, frontend/backend integration, database table, API endpoint, and validation rule, using simple, clear language with practical examples, file references, and workflow diagrams.

[`docs/VEHICLE_RENTAL_GUIDE.md`](file:///d:/project-new/AutoERP/docs/VEHICLE_RENTAL_GUIDE.md) was upgraded and rewritten to serve as the definitive, beginner-friendly technical guide reflecting the production implementation following our recent deep audit.

---

## Changes Implemented

### 1. Complete Architecture & Operational Journey Coverage
Updated [`docs/VEHICLE_RENTAL_GUIDE.md`](file:///d:/project-new/AutoERP/docs/VEHICLE_RENTAL_GUIDE.md) with 12 structured sections:
1. **Executive Overview & Business Purpose:** Fleet logistics vs. commercial accounting, core principles (do not invent money, dual-track independence, no fictitious owner costs, optimistic concurrency, exact decimal precision, civil timezone standard).
2. **Dual-Sided Commercial Architecture:** Decoupled customer revenue track and lessor payables track with Mermaid architecture diagram.
3. **End-to-End Operational Journey (Life of a Rental):** Step-by-step sequence diagram from initial customer inquiry to base rent invoicing, excess mileage assessment, usage charges, and lessor disbursement settlement.
4. **Component & Subsystem Architecture:** 10 decoupled subsystems mapped to core models, services, and roles.
5. **Step-by-Step Workflows & State Machines:** Detailed operational explanations for:
   - Reservations & Booking Inquiries (`RentalReservationStatus`).
   - Commercial Agreements & Successor Contracts (`supersedes_agreement_id` cutover boundary preserving security deposit origins).
   - Vehicle Custody, Handover, Return & Atomic Replacement (`VehicleUseService::replace()`) with shared monthly allowance and visual amber badges (`For Repl. <reg_no>`).
   - Daily Running Chart 31-day operational log (`pmdeblogsheet2.frx` / `pmcre1logsheet2.frx`) with civil business timezone timestamps, duty ON/OFF, comparative/cumulative KM, overtime tiers, and toll passes.
   - Debtor Revenue Billing (base rent proration via `actual_calendar_days_v1`, commercial mileage assessment, overtime hours billing).
   - Creditor Owner Settlements & WHT (Statement of Accounts Detail I & II, statutory 10% Withholding Tax under Account `:3305-000 WHT`, repair deduction debit notes `CDN`, and settlement cheques `PRB`/`PVV`).
   - Fleet Operating Expenses & 4-Way Allocation Matrix (`Company`, `Customer`, `Owner`, `HR`).
   - Vehicle Finance & Bank Leasing Contracts (amortization schedules, due status automated synchronization, AP invoice creation).
   - Incident Evidence Register & Private Proofs (non-monetary evidence, SHA-256 tamper-evident attachments, private streaming downloads).
   - Security Deposits & Payment Allocations (capacity tracking, payment receipts, unapplied balance allocations).
6. **Comprehensive Enterprise Reporting Suite:** Detailed specification of all 8 Vehicle Rental reports with layouts (A4 Landscape vs. Portrait) and legacy FoxPro FRX mappings.
7. **Data Flow & Cross-Module Integrations:** Flowchart showing interactions with `Vehicle`, `Customer`, `Supplier`, `Hr`, `Invoice`, `Payment`, `Tax`, `Finance`, and `VehicleService`.
8. **Database Schema Reference:** Complete reference of all 22 database tables and migrations.
9. **Complete REST API Reference:** Full list of `/api/v1/vehicle-rental` endpoints grouped by subsystem.
10. **Frontend UI/UX Architecture & Components:** Design standards (zero raw IDs, controlled lookups, progressive disclosure) and key component references.
11. **Data Integrity, Concurrency & Validation Invariants:** Summary of optimistic locking, proration math, and custody boundary rules.
12. **Developer Quickstart & Verification Checklist:** Updated terminal commands reflecting 234 PHPUnit tests and 138 Vitest tests.

---

## Verification & Quality Assurance
- Formatted in standard GitHub Flavored Markdown with Mermaid flowcharts and sequence diagrams.
- Validated all code file links and references against the actual codebase.
- Complies strictly with repository rules in `AGENTS.md` and `RULES.md`.
