# 2026-11-05 Vehicle Rental End-User Operations Guide Documentation

## Context
Following the comprehensive technical and domain documentation in `docs/knowledgebase.md` and `docs/VEHICLE_RENTAL_GUIDE.md`, an intuitive, step-by-step, beginner-friendly operational guide was requested for non-technical users, operational dispatchers, rental managers, billing clerks, and depot officers.

## Changes Made
1. **Created `docs/VEHICLE_RENTAL_USER_GUIDE.md`**:
   - Structured into 16 user-focused chapters covering every operational workflow in the Vehicle Rental module.
   - **Introduction & System Philosophy**: Explains core business concepts (e.g. why physical operations like trips and handovers are separated from financial documents like invoices).
   - **Navigation & Getting Started**: Guidance on locating Vehicle Rental in the main sidebar, navigating sub-tabs, and using human-readable select lookups without entering raw IDs.
   - **Step-by-Step Flow 1: Inquiries & Reservations**: How to record a booking inquiry, reserve vehicles, calculate quote estimations, and convert inquiries into formal commercial agreements.
   - **Step-by-Step Flow 2: Customer Rental Agreements**: Creating, editing, and activating rental contracts (Self-Drive vs. With-Driver, Daily vs. Monthly rental basis, free distance allowances, and billing terms).
   - **Step-by-Step Flow 3: Vehicle Dispatch, Handover & Return**: Walkthrough of vehicle dispatch, recording outbound odometer and fuel levels, logging returns, and swapping vehicles mid-rental via "Replace Vehicle" while preserving mileage allowances.
   - **Step-by-Step Flow 4: Daily Running Charts (Trips)**: Entering daily running sheets, tracking client passenger movements, recording driver tips/allowances, logging en-route fuel purchases, and auto-calculating net kilometers.
   - **Step-by-Step Flow 5: Billing & Invoicing**: Walkthrough of Base Rental billing, Usage Charges (excess kilometers, excess hours, driver overnight allowances), and Mileage Assessments.
   - **Step-by-Step Flow 6: Security Deposits & Payments**: Recording customer security deposits, allocating them against open rental invoices, and processing deposit refunds upon agreement closure.
   - **Step-by-Step Flow 7: Vehicle Sourcing & Owner Disbursements**: Registering outsourced/investor fleet custody, calculating owner earnings, entering owner deductions, and issuing settlement payment vouchers.
   - **Step-by-Step Flow 8: Rental Fleet Expenses**: Logging fuel, repairs, insurance renewals, and maintenance costs with optional customer recharge checkboxes.
   - **Step-by-Step Flow 9: Incidents, Accidents & File Proofs**: Logging incidents and traffic violations, uploading photo/PDF evidence proofs with tamper-evident digital fingerprints, and performing manager factual reviews.
   - **Step-by-Step Flow 10: Vehicle Finance & Bank Leases**: Setting up lease contracts, managing monthly EMI amortization schedules, logging interest/capital splits, and settlement tracking.
   - **Step-by-Step Flow 11: Agreement Successors & Extensions**: Extending contracts and transitioning customers to successor agreements without duplicate onboarding.
   - **Step-by-Step Flow 12: Business Intelligence & Reports**: Detailed usage guide for all 8 business reports with explanation of filter parameters and analytical metrics.
   - **Troubleshooting & FAQs**: Answers to common operator questions (e.g., date formats, concurrency conflict warnings, zero vehicle dropdown results).

2. **Cross-Referenced Documentation**:
   - Updated `docs/knowledgebase.md` to reference `VEHICLE_RENTAL_USER_GUIDE.md` as the authoritative End-User Operations Guide.
   - Updated `docs/VEHICLE_RENTAL_GUIDE.md` with an informational note directing operational staff to `VEHICLE_RENTAL_USER_GUIDE.md`.

## Verification
- Verified with `git diff --check` to ensure clean formatting with zero trailing whitespace or CRLF warnings.
- Verified all cross-file markdown links and terminology match the actual codebase implementation (`app/Modules/VehicleRental` and `resources/js/modules/vehicle-rental`).
