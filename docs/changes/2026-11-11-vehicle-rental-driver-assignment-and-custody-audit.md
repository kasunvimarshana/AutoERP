# Vehicle Rental: Driver Assignment Lineage, Operational Custody, and Vehicle Replacement Audit

**Date:** 2026-11-11  
**Module:** `VehicleRental` (`app/Modules/VehicleRental`, `resources/js/modules/vehicle-rental`)  
**Scope:** Verification and enhancement of vehicle replacement workflows, driver assignment/reassignment across running chart physical logs, contract driver arrangement visibility, and operational lifecycle integrity.

---

## 1. Audit Findings & Functional Verification

### A. Vehicle Replacement & Swapping Workflows
- **Planned Vehicles:**
  - Before physical handover (`VehicleUseStatus::Planned`), users can cancel the planned vehicle reservation using `Cancel plan` with an audited reason. Cancelling releases the physical vehicle hold immediately, enabling assignment of an alternative vehicle via `Assign vehicle`.
- **In-Custody Vehicle Swapping:**
  - When an in-custody vehicle requires maintenance, breakdown replacement, or client exchange, the module executes an atomic transition via `VehicleUseService::replace()` (`POST /vehicle-rental/uses/{use}/replace`).
  - Atomically records the return of the previous vehicle at replacement time with return odometer reading, updates previous vehicle status back to `VehicleStatus::Active`, creates the new vehicle custody record linked via `replaces_use_id` / `replaced_by_use_id`, checks vehicle availability, records new vehicle handover at replacement time, and transitions new vehicle status to `VehicleStatus::Rented`.
  - In running chart matrix reports, replacements are distinguished with `For Repl. <vehicle_number>` badges matching legacy domain printouts.

### B. Driver Assignment & Reassignment Workflows
- **Agreement Level:** Customer and owner agreements capture the commercial driver arrangement (`driver_mode`: `with_driver` vs. `self_drive`) and associated hourly/overtime/night-out rate structures.
- **Trip/Operational Log Level:**
  - Physical vehicle usage is recorded per trip or daily shift via `RunningChart`.
  - Each chart records driver identity (`DriverIdentitySource::Employee` with employee lookup or `DriverIdentitySource::External` with name and reference snapshot) and driver observations.
  - On draft charts, drivers can be freely changed or reassigned by editing the draft.
  - On finalized charts, if an incorrect driver was recorded, the chart is reversed with an audited reason and a correction chart is issued with the reassigned driver, preserving full legal and operational lineage.
  - Concurrent driver conflicts are prevented via pessimistic database locking (`assertDriverAvailable`), strictly disallowing one driver from operating multiple vehicles concurrently during overlapping periods.

---

## 2. Implemented Improvements

1. **Driver Mode Context in Vehicle Custody API (`VehicleUseResource.php`):**
   - Added `driver_mode` from the underlying customer and owner agreements into `customer_agreement` and `owner_agreement` payloads in `VehicleUseResource`.
2. **Visual Contract Driver Badges:**
   - In [`VehicleUsePanel.tsx`](file:///d:/project-new/AutoERP/resources/js/modules/vehicle-rental/VehicleUsePanel.tsx) and [`VehicleUseRegisterPage.tsx`](file:///d:/project-new/AutoERP/resources/js/modules/vehicle-rental/VehicleUseRegisterPage.tsx), added human-readable badges (`With driver` vs `Self-drive`) directly alongside vehicle custody status.
3. **Driver Guidance in Running Chart Editor (`RunningChartEditor.tsx`):**
   - Automatically opens the additional observations section by default when `use.customer_agreement.driver_mode === 'with_driver'`.
   - Displays prominent contextual guidance prompting operators to record the assigned employee or external driver for with-driver agreements.

---

## 3. Verification & Quality Gates

All automated verification gates passed with zero errors:

- **Backend PHPUnit Tests:**
  - `php artisan test app/Modules/VehicleRental/Tests`: **234 passed (2,007 assertions)** in 38.87s.
- **Frontend Vitest Suites:**
  - `npx vitest run resources/js/modules/vehicle-rental`: **29 passed (29 test files, 138 tests)** in 55.71s.
- **TypeScript Typecheck:**
  - `npm run typecheck`: **0 errors**.
- **ESLint Code Quality:**
  - `npm run lint`: **0 errors, 0 warnings**.
- **Production Asset Build:**
  - `npm run build`: **Built successfully in 8.75s** (754 modules transformed, all chunks compiled and hashed).
