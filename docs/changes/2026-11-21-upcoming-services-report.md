# Upcoming Services Report Implementation

**Date:** 2026-11-21  
**Module:** Reporting (`app/Modules/Reporting`, `resources/js/modules/reporting`), integrated with Vehicle Service (`app/Modules/VehicleService`)

## 1. Summary of Changes
Implemented an **Upcoming Services Report** that enables service managers, workshop advisors, fleet coordinators, and customer relationship agents to view, track, search, filter, and plan vehicle services due based on their scheduled `next_service_date`:

### Backend Architecture (`app/Modules/Reporting`)
- **Query & Service Layer (`UpcomingServicesReportService.php`)**:
  - Scoped to tenant and organization unit (`forContext`).
  - Supported deduplication via `latest_only` (default: `true`), taking the latest service job per vehicle with scheduled service and excluding superseded jobs where a newer service has already taken place.
  - Calculated aggregate KPI summary metrics (`total_upcoming`, `total_overdue`, `total_due_today`, `total_due_this_week`, `total_due_this_month`).
  - Applied date filters and preset filters (`all`, `overdue`, `due_today`, `due_this_week`, `due_this_month`, `upcoming`), along with custom `date_from` and `date_to`.
  - Filtered by `vehicle_id`, `customer_id`, `job_status`, and unified `search` keyword (matching vehicle registration number, customer name, phone number, customer code, and job number).
  - Hydrated rich relational presentation objects: vehicle details (registration number, make, model, year, odometer), customer details (display name, code, contact phone, email), last service job details, and computed relative urgency metadata (`urgency_status`, `days_diff`, `days_overdue`, `days_until_due`, `urgency_label`).
  - Provided `exportRows()` supporting export limits and formatting.
  - Implemented `definition()` registering `vehicle-service/upcoming-services` with column metadata and landscape layout.
- **Request Validation (`UpcomingServicesReportRequest.php`)**:
  - Extended `TenantScopedRequest` enforcing tenant ID, organization unit ID, valid date constraints, preset enum choices, job status validation, and search lengths.
- **Controller & Endpoints (`UpcomingServicesReportController.php`)**:
  - Added `index()` requiring `REPORTS_VIEW` permission returning JSON paginated data.
  - Added `export()` requiring `REPORTS_EXPORT` permission delegating to `ReportExport` for PDF, Excel (XLSX), CSV, and HTML/Print generation.
- **Routes & Registry Integration**:
  - Registered `api/v1/reports/vehicle-service/upcoming-services` and `api/v1/reports/vehicle-service/upcoming-services/export/{format}` in `app/Modules/Reporting/Routes/api.php`.
  - Registered definition in `ReportCatalog.php`.

### Frontend Architecture (`resources/js/modules/reporting`)
- **Contracts & Types (`upcomingServicesTypes.ts`)**:
  - Defined `UpcomingServiceRow`, `UpcomingServiceVehicle`, `UpcomingServiceCustomer`, `UpcomingServicesSummary`, `UpcomingServicesParams`, and `UpcomingServicesResult`.
- **API Client (`reportingApi.ts`, `ExportActions.tsx`)**:
  - Added `runUpcomingServicesReport()`.
  - Added `UpcomingServicesParams` to `ExportActions` and `exportReport`.
- **Report Page (`UpcomingServicesReportPage.tsx`)**:
  - Clean, high-clarity user interface adhering to design principles (zero raw IDs, human-readable labels, responsive layouts).
  - Interactive KPI summary cards at top displaying total counts (Total Scheduled, Overdue, Due Today, Next 7 Days, Next 30 Days) with one-click filtering.
  - Controlled relational search components using `VehicleLookupSelect` and `CustomerLookupSelect`.
  - Comprehensive filters: Search keyword, date range, job status selector, and superseded history toggle.
  - Actionable data table: colored urgency badges with relative time indicators (`5 days overdue`, `Due today`, `Due in 3 days`), vehicle details with mileage comparisons, customer contact phone with `tel:` link, last service job link, and quick action links ("View Job", "New Service").
  - Export menu integration (PDF, XLSX, CSV, Print).
  - Controlled pagination and empty state with reset guidance.
- **Navigation & Routing**:
  - Registered route `/reports/vehicle-service/upcoming-services` with lazy loading in `resources/js/app/router.tsx`.
  - Added "Upcoming Services" link under Reports section in `resources/js/app/navigation/navigationConfig.ts`.
  - Mapped `reportPath` in `resources/js/modules/reporting/pages/ReportListPage.tsx`.

## 2. Verification
- **Backend Tests (`UpcomingServicesReportTest.php`)**:
  - `test_upcoming_services_report_returns_accurate_structure_and_counts`
  - `test_upcoming_services_report_filters_by_preset_and_custom_dates`
  - `test_upcoming_services_report_filters_by_vehicle_and_customer`
  - `test_upcoming_services_report_deduplicates_superseded_jobs_when_latest_only_is_true`
  - `test_upcoming_services_report_search_matches_registration_and_customer_name`
  - `test_upcoming_services_report_enforces_tenant_isolation`
  - `test_upcoming_services_report_export_csv_and_pdf_succeeds`
  - All 50 tests in `app/Modules/Reporting/Tests` passed (647 assertions).
  - Laravel Pint code style passed with 0 issues.
- **Frontend Tests (`UpcomingServicesReportPage.test.tsx`)**:
  - Tested KPI cards rendering, table rows, vehicle/customer details, quick action links, preset clicking, form search submission, and empty state.
  - All 16 test files (27 tests) in `resources/js/modules/reporting` passed.
  - `npm run typecheck` passed with 0 errors.
  - `npx eslint` passed with 0 errors across all modified/new files.
