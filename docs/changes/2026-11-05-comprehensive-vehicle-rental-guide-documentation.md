# Comprehensive Vehicle Rental Technical and Operational Guide

## Context
While `docs/knowledgebase.md` acts as the canonical business domain and policy specification, developers, beginners, and operators needed an end-to-end, beginner-friendly yet technically thorough Markdown guide detailing every workflow, component, CRUD operation, calculation, API route, database table, and user interaction.

## Key Changes
- **Authored Comprehensive Guide (`docs/VEHICLE_RENTAL_GUIDE.md`)**:
  - **Module Overview & Architecture Philosophy (§1-2)**: Detailed the dual-sided commercial model (Customer revenue vs. Owner costs), unified physical evidence, and clear module boundaries (`Vehicle`, `Customer`, `Supplier`, `HR`, `Invoice`, `Payment`, `Tax`, `Finance`, `VehicleService`).
  - **The End-to-End Operational Journey (§3)**: Comprehensive sequence walkthrough from customer booking inquiry through vehicle handover, daily running chart, return, dual-track billing, and financial settlement.
  - **Rental Reservations & Booking Inquiries (§4)**: Lead capture, status lifecycle (`draft` -> `pending` -> `confirmed` -> `converted`), and atomic conversion to Customer Agreements.
  - **Commercial Agreements & Successor Contracts (§5)**: Customer and Owner agreement terms, immutability of active agreements, successor drafting, deposit non-duplication, and atomic boundary cutover.
  - **Vehicle Sourcing, Custody & Fleet Availability (§6)**: Company fleet vs. owner-leased supply, half-open interval availability blocking (`VehicleUseAvailabilityBlocker`), handover/return odometers, and atomic vehicle replacements.
  - **Daily Running Charts (§7)**: Operational trip logging, commercial vs. garage KM, AC modes, driver identity tracking (employee vs. external), overtime tiers, and frozen lifecycle immutability.
  - **Commercial Billing Engine & Calculations (§8)**: Base rent proration via `actual_calendar_days_v1`, commercial mileage assessment against cycle allowances, usage charge billing, and invoice synchronization.
  - **Vehicle Finance & Bank Leasing (§9)**: Bank leasing agreements, amortization schedules, automated due status sync, and AP payable invoice creation.
  - **Rental Fleet Expenses & Direct Cost Recovery (§10)**: Expense recording and the 4-destination allocation matrix (`Company Cost`, `Customer Recovery`, `Owner Deduction`, `Employee Reimbursement`).
  - **Incident Evidence & Private Proofs (§11)**: Factual non-monetary register, review lifecycle, and private tenant file storage with SHA-256 deduplication and streaming delivery.
  - **Security Deposits & Cash Flow Operations (§12)**: Payment module ownership, collection capacity tracking, invoice application, and refund workflows.
  - **Comprehensive 8-Report Business Intelligence Suite (§13)**: Operational matrix for all 8 official reports, including the 31-day Running Chart matrix sheet (`pmdeblogsheet2.frx` / `pmcre1logsheet2.frx`).
  - **Concurrency, Multi-Tenancy & UX Contract (§14-15)**: Optimistic locking (`expected_version` / `row_version`), tenant scoping, append-only history tables, controlled lookups (`LookupSelect`), zero raw IDs, and tenant business calendar defaults.
  - **Database & API References (§16-17)**: Complete 22-table schema reference and REST API catalog.
  - **Developer Quickstart & Verification (§18)**: Step-by-step commands for testing, linting, typechecking, and building.
- Linked the technical guide from [`docs/knowledgebase.md`](file:///d:/project-new/AutoERP/docs/knowledgebase.md).

## Verification
- Verified against all source code in `app/Modules/VehicleRental/` and `resources/js/modules/vehicle-rental/`.
- Verified `git diff --check` passes with zero whitespace or line ending issues.
