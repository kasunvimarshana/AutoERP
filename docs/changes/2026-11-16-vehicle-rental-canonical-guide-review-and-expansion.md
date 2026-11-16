# Vehicle Rental Canonical Master Guide Review & Comprehensive Expansion

**Date:** 2026-11-16  
**Scope:** `VehicleRental`, `Documentation`, `Architecture`  
**Focus:** Review, audit, and comprehensive expansion of the canonical technical and operational manual for the Vehicle Rental module.

---

## 1. Summary of Changes

1. **Comprehensive Review & Identification of Gaps in Prior Attempt:**
   - The prior attempt established a high-level guide structure but omitted crucial practical elements:
     - **Missing Table Schemas:** The 28 core database tables were only summarized in 1-sentence blurbs without column data types, nullabilities, and foreign keys.
     - **Incomplete REST API Table:** The API endpoint table omitted over 20 active routes from `Routes/api.php` (such as driver/vehicle reassignment routes, agreement adjustments, deposit allocations, and direct charge actions).
     - **Missing Concrete CRUD Request & Response Payloads:** Developers and beginners had zero concrete JSON payloads for testing or integrating endpoints with cURL or Postman.
     - **Missing End-to-End Numeric Invoicing & Owner Settlement Walkthroughs:** Lacked concrete numeric calculations walking through day-by-day scenarios with real monetary and operational figures.
     - **Missing Domain Enums Catalog:** The 38 strict PHP 8.2 backed enums were not cataloged with their backed values and business roles.
     - **Undocumented Frontend Component Hierarchy & User Journeys:** The frontend architecture lacked detailed component hierarchy diagrams, state flow, and role-based user journeys.

2. **Exhaustive Guide Expansion (`docs/modules/vehicle_rental_complete_guide.md`):**
   - **Database Architecture:** Added explicit column-by-column schema tables for all primary entities (`vehicle_rental_customer_agreements`, `vehicle_rental_owner_agreements`, `vehicle_rental_uses`, `vehicle_rental_running_charts`, `vehicle_rental_billing_cycles`, `vehicle_rental_reassignments`, `vehicle_rental_expenses`, `vehicle_rental_incidents`, etc.).
   - **Complete 38 Enums Catalog:** Detailed table listing every enum in `app/Modules/VehicleRental/Enums/`, backed values, and domain responsibilities.
   - **Step-by-Step CRUD Payloads:** Included realistic JSON request payloads and responses for Reservations, Agreements, Successors, Custody Handover/Return, Mid-Lease Swaps, Daily Running Charts, Fleet Expenses, and Damage Incidents.
   - **End-to-End Numeric Case Studies:**
     - *Customer Two-Stage Invoicing:* Step-by-step 31-day corporate rental walkthrough with LKR 150,000 base rent, Day 18 mid-lease vehicle swap proration, 420 excess KM ($45/km), Multi-tier overtime (14 hrs 1.5x, 5 hrs 2.0x, 1 hr 3.0x), night-outs, expressway tolls, downtime rebate line-discount mapping (`applyDeductionsAsDiscounts`), and resulting Stage 2 invoice.
     - *Owner Settlement AP Billing:* Step-by-step calculation of gross lease rent, overtime reimbursement, 10% statutory Withholding Tax (:3305-000), fleet maintenance chargebacks, and deduction capping safeguard formula (`maxDeductionsCapacity`).
     - *Multi-Agreement Settlement:* Consolidated payment tender across multiple customer agreements, allocation grid, and unapplied advance pooling.
   - **Complete REST API Specification:** Documented all 65+ endpoints from `app/Modules/VehicleRental/Routes/api.php` with HTTP methods, URI patterns, required permissions, and functional summaries.
   - **Frontend Architecture & User Journeys:** Documented full React 19 component tree, routing, modal wizards, and 3 step-by-step role-based journeys (Dispatcher, Billing Specialist, Accountant).
   - **General Ledger Double-Entry Posting Matrix:** Documented DR/CR account codes, names, and posting profiles for all 8 commercial events.

---

## 2. Key Files Modified and Created

- [`docs/modules/vehicle_rental_complete_guide.md`](file:///d:/project-new/AutoERP/docs/modules/vehicle_rental_complete_guide.md) (Expanded to 1,370 lines / 98 KB)
- [`docs/changes/2026-11-16-vehicle-rental-canonical-guide-review-and-expansion.md`](file:///d:/project-new/AutoERP/docs/changes/2026-11-16-vehicle-rental-canonical-guide-review-and-expansion.md) (Created)

---

## 3. Verification Record

- **Backend PHPUnit Test Suite:**
  - `php artisan test app/Modules/VehicleRental/Tests/`: **306 passed (2,477 assertions)**.
- **Frontend Vitest Test Suite:**
  - `npx vitest run resources/js/modules/vehicle-rental/`: **33 test files passed (154 tests passed)**.
- **TypeScript Strict Compilation:**
  - `npm run typecheck`: **0 errors**.
- **Markdown & Links Verification:**
  - Verified that all file references follow the clickable markdown format `[name](file:///d:/project-new/AutoERP/...)`.
  - Verified Mermaid diagram syntax.
