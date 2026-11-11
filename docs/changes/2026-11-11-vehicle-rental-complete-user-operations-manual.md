# Vehicle Rental Complete End-User Operations Manual Upgrade

## Context & Motivation
The user requested a comprehensive, well-structured document explaining everything in simple, clear, and easy-to-understand language. It needed to cover all features, functionalities, workflows, and usage instructions so that anyone—regardless of technical background—can easily understand how the entire system works and how to use it effectively.

[`docs/VEHICLE_RENTAL_USER_GUIDE.md`](file:///d:/project-new/AutoERP/docs/VEHICLE_RENTAL_USER_GUIDE.md) was upgraded and rewritten to serve as the definitive, beginner-friendly operational manual for day-to-day depot staff, front-desk agents, rental managers, billing clerks, and accountants.

---

## Changes Implemented

### 1. 19-Section Comprehensive Operational Manual
Upgraded [`docs/VEHICLE_RENTAL_USER_GUIDE.md`](file:///d:/project-new/AutoERP/docs/VEHICLE_RENTAL_USER_GUIDE.md) with 19 beginner-friendly, structured sections:
1. **Understanding the System: The Dual-Sided Business Model:** Visual breakdown of customer revenue vs. physical fleet logistics vs. vehicle owner payout costs, emphasizing the core principles (we never invent money, customer rates never derive owner costs, company vehicles have zero fictitious costs).
2. **Navigating the Workspace & User Interface Standards:** Full table of menu items and screens, explanation of zero raw database IDs, controlled search dropdowns (`LookupSelect`), progressive disclosure, and civil business calendar formatting (`Asia/Colombo`).
3. **The Complete Rental Journey at a Glance:** 8-step chronological journey from phone inquiry to contract activation, deposit receipt, curb dispatch, daily trip logging, check-in return, billing, and owner settlement.
4. **Managing Reservations & Customer Inquiries:** Lead capture form, booking statuses, and one-click conversion into active customer agreements.
5. **Customer Agreements (Client Contracts & Pricing Terms):** Daily vs. monthly basis, with driver vs. self-drive, rate breakdowns (base rent, free KM allowance, excess KM rate, overtime tiers, night-outs, security deposit), reviewing/activating, and mid-contract renegotiations using successor lineage without disrupting custody.
6. **Vehicle Owner Agreements (Leased Fleet Management):** Setting up lessor terms, binding physical vehicles, and independent payout rates.
7. **Fleet Availability & Checking Vehicle Status:** Color-coded availability badges (Available, In Custody, Workshop Service, Reserved) and explaining how workshop jobs in `VehicleService` automatically protect fleet cars.
8. **Vehicle Dispatches: Handover & Return Procedures:** Step-by-step handover and return instructions, inspecting speedometers at the curb, and odometer continuity rules.
9. **Vehicle Breakdowns & Emergency Swaps (Atomic Replacement):** Why atomic replacement is used instead of manual check-in, shared monthly mileage pooling across replaced vehicles, and visual amber badges (`For Repl. <old_car>`).
10. **Daily Running Charts (Official Trip Sheets):** Logging daily duty ON/OFF times, comparative/cumulative KM, overtime tier splits (Normal, Double/Sunday, Triple/Holiday), night outs, expressway toll passes (`Pass`), and prompt finalization.
11. **Customer Billing: Base Rent, Mileage & Usage Invoicing:** Base rent proration formula (`actual_calendar_days_v1`), commercial excess mileage pooling, and overtime/toll invoicing.
12. **Vehicle Owner Settlements & Withholding Tax (WHT :3305-000):** Vertical Statement Detail I (A4 Portrait), Horizontal Matrix Detail II (18-column A4 Landscape), statutory 10% Withholding Tax under Account `:3305-000 WHT`, repair chargeback debit notes (`CDN`), and settlement cheques (`PRB`/`PVV`).
13. **Customer Security Deposits & Payment Management:** Collecting deposits via `Payment`, capacity tracking, applying balances to final invoices, and safe refund workflows.
14. **Fleet Operating Expenses & Cost Allocation Matrix:** Logging receipts and 4-way allocation (Company Overhead, Customer Recovery, Owner Deduction, Driver Reimbursement).
15. **Incident Evidence Register & Private Proof Uploads:** Factual evidence vs. financial charges, uploading up to 10 MB PDF/images with SHA-256 digital security fingerprints, and manager review locks.
16. **Vehicle Finance & Commercial Bank Leasing:** Amortization schedules, due status automated refresh, and one-click payable invoice creation for maturing installments.
17. **Official Reports Hub & Exporting A4 Printouts:** How to generate and download all 8 official reports, including the 31-day running chart matrix sheet (`pmdeblogsheet2.frx` / `pmcre1logsheet2.frx`).
18. **Troubleshooting & Frequently Asked Questions (FAQ):** 5 real-world scenarios and error resolutions (concurrency conflicts, missing vehicles, non-deletable records, rejected return odometers, rate discounts).
19. **Daily Operational Best Practices Checklist:** Quick operational checklist for day-to-day staff.

---

## Verification & Quality Assurance
- Validated markdown formatting, Mermaid diagrams, and plain-text UI mockups for visual clarity.
- Ensured consistent terminology matching the actual codebase and user interface.
- Preserved strict adherence to repository rules in `AGENTS.md` and `RULES.md`.
