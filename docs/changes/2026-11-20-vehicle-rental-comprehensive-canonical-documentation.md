# Vehicle Rental Canonical Technical Specification & Architecture Manual Update

**Date:** 2026-11-20  
**Scope:** `docs/modules/vehicle_rental_complete_guide.md`, `docs/VEHICLE_RENTAL_GUIDE.md`  
**Topic:** Comprehensive documentation synchronization for all recent Vehicle Rental capabilities, workflows, APIs, and verification baselines.

---

## 1. Summary of Work

1. **Comprehensive Documentation Audit & Synthesis**:
   - Inspected the current implementation of the `VehicleRental` module across both backend (`app/Modules/VehicleRental/`) and frontend (`resources/js/modules/vehicle-rental/`).
   - Updated the canonical master guide [`docs/modules/vehicle_rental_complete_guide.md`](file:///d:/project-new/AutoERP/docs/modules/vehicle_rental_complete_guide.md) to serve as a complete, beginner-friendly, and authoritative technical reference.

2. **Key Capabilities & Architecture Documented**:
   - **Internal Company Fleet Deployments (`planInternal`)**:
     - Documented internal operations workflow deploying company-owned fleet vehicles (via `CompanyVehicleCoverageService`) and third-party owner-leased vehicles without requiring customer agreements.
     - Documented `POST /api/v1/vehicle-rental/vehicle-uses/internal`, schema nullability (`customer_agreement_id` is nullable in `vehicle_rental_uses`), and scope filters (`kind=customer`, `kind=owner`, `kind=internal`) across running charts and custody registers.
   - **Baseline Working Hours & Automatic Overtime Engine**:
     - Documented `working_hours` (agreed daily working baseline hours) in customer and owner agreement terms JSON.
     - Documented [`OvertimeCalculator`](file:///d:/project-new/AutoERP/app/Modules/VehicleRental/Services/OvertimeCalculator.php) mathematical logic, duration comparison, and HH:MM formatting.
     - Documented live Overtime Baseline Card in [`RunningChartEditor.tsx`](file:///d:/project-new/AutoERP/resources/js/modules/vehicle-rental/RunningChartEditor.tsx) with quick-apply and routing actions.
   - **Billing Cycle Lifecycle Hardening & Cancellation**:
     - Documented `POST /api/v1/vehicle-rental/billing-cycles/{id}/cancel` for cycle cancellation, release of date locks, and unlocking attached running charts.
     - Documented double-billing race condition prevention across concurrent cycles.
   - **Agreement Adjustments Lifecycle & Voiding**:
     - Documented `POST /api/v1/vehicle-rental/adjustments/{id}/void` along with `Draft` -> `Authorized` -> `Applied` -> `Cancelled`/`Voided` lifecycle and authorization enforcement.
   - **Company Fleet Regulatory Running Sheet**:
     - Documented fleet report generation for internal company vehicles without agreement constraints (`RunningChartReportService`).
   - **Pro-Forma Quotation and Bill Previews**:
     - Documented endpoints `POST /billing/quotation-preview` and `POST /billing/owner-bill-preview`.
   - **Complete REST API Specification**:
     - Cataloged all 70+ active endpoints in `Routes/api.php` with HTTP methods, route paths, required permissions, and functional summaries.
   - **Synchronized Test Metrics**:
     - Updated verification baseline to 344 backend PHPUnit tests (2,948 assertions) and 38 frontend Vitest suites (181 tests), all passing with 0 errors.

---

## 2. Modified & Created Files

- [`docs/modules/vehicle_rental_complete_guide.md`](file:///d:/project-new/AutoERP/docs/modules/vehicle_rental_complete_guide.md) (Updated canonical guide to 1,200+ comprehensive lines)
- [`docs/VEHICLE_RENTAL_GUIDE.md`](file:///d:/project-new/AutoERP/docs/VEHICLE_RENTAL_GUIDE.md) (Updated test baseline metrics)
- [`docs/changes/2026-11-20-vehicle-rental-comprehensive-canonical-documentation.md`](file:///d:/project-new/AutoERP/docs/changes/2026-11-20-vehicle-rental-comprehensive-canonical-documentation.md) (Created)

---

## 3. Verification Record

- **Backend Integration Tests:**
  - `php artisan test app/Modules/VehicleRental/Tests/`: **344 passed (2,948 assertions)**.
- **Frontend Vitest Suites:**
  - `npx vitest run resources/js/modules/vehicle-rental/`: **38 passed (181 tests passed)**.
- **TypeScript Static Verification:**
  - `npm run typecheck`: **Passed with 0 errors**.
