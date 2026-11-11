# Vehicle Rental All Invoice Listing Portrait Orientation Alignment

## Context & Motivation
In the Vehicle Rental reporting suite, the `Lessee's All Invoice Listing (Summary)` report (`pmdebinv3.frx` / `prndebinv3_s.pdf`) was being exported with hardcoded landscape orientation in `RentalReportsController::exportRentalRevenue`.

To match the legacy FoxPro printout standard (`prndebinv3_s.pdf`), the controller export orientation and Blade `@page` CSS size were updated to evaluate dynamic orientation: `portrait` when `report_type === 'invoice_listing'` and `landscape` for multi-column agreement revenue summaries.

---

## Changes Implemented

### 1. Dynamic PDF Export Orientation in Controller
- **File:** [`RentalReportsController.php`](file:///d:/project-new/AutoERP/app/Modules/VehicleRental/Http/Controllers/RentalReportsController.php)
- Updated `exportRentalRevenue` to inspect `report_type`. When `report_type === 'invoice_listing'`, sets orientation to `'portrait'`; otherwise retains `'landscape'`.

### 2. Blade Template Print & Container Width Sizing
- **File:** [`rental-revenue.blade.php`](file:///d:/project-new/AutoERP/resources/views/reports/vehicle-rental/rental-revenue.blade.php)
- Updated `@page { size: ... }` to output `A4 portrait` when `report_type === 'invoice_listing'` and `A4 landscape` for summary mode.
- Adjusted `.sheet-container` max width to `820px` for portrait and `1120px` for landscape.

---

## Verification
- Ran PHPUnit suite `php artisan test app/Modules/VehicleRental/Tests`: 234 tests passed (2,007 assertions).
- Ran TypeScript compile check: `npm run typecheck` (0 errors).
- Ran code linter: `npm run lint` (0 errors).
- Ran production build: `npm run build` (built cleanly).
