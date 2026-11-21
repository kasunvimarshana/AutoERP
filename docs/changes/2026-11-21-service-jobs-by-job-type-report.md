# Service Jobs by Job Type Report Implementation

**Date:** 2026-11-21  
**Module:** Reporting (`app/Modules/Reporting`, `resources/js/modules/reporting`), integrated with Vehicle Service (`app/Modules/VehicleService`)

## 1. Summary of Changes
Implemented a comprehensive, high-clarity **Service Jobs Report Grouped by Job Type** that enables workshop managers, service advisors, accountants, and business analysts to analyze, audit, and compare vehicle service jobs across different Job Types (e.g., Full Service, Oil Change, Body Wash, Wheel Alignment, Accessories, and custom-defined job types):

### Backend Architecture (`app/Modules/Reporting`)
- **Query & Service Layer (`ServiceJobsByJobTypeReportService.php`)**:
  - Scoped authoritatively to tenant and organization unit (`forContext`).
  - Resolves job types dynamically via `VehicleServiceJobType` relationship, with safe fallback to legacy string codes (`vehicle_service_jobs.type`) and default definitions.
  - Computes global KPI summary metrics using `DecimalMath` with scale 6 precision (`total_jobs`, `total_revenue`, `total_subtotal`, `total_discount`, `total_tax`, `total_charges`, `total_commission`, `average_job_value`, `status_counts`).
  - Computes group-level summary and comparative metrics for every Job Type group (`total_jobs`, `total_subtotal`, `total_discount`, `total_tax`, `total_charges`, `total_revenue`, `average_job_value`, `revenue_share_percentage`, `jobs_share_percentage`, and detailed job items).
  - Groups are sorted by total revenue descending, with secondary alphabetical sort.
  - Implemented `exportRows()` supporting flattened, structured rows for PDF, Excel (XLSX), CSV, and HTML/Print generation sorted by Job Type name and Job Date descending.
  - Implemented `definition()` registering `vehicle-service/jobs-by-type` with formatted columns, summarize flags, and landscape layout.
- **Request Validation (`ServiceJobsByJobTypeReportRequest.php`)**:
  - Extends `TenantScopedRequest` validating `tenant_id`, `organization_unit_id`, `job_type_id`, `job_type_code`, date filters (`date_from`, `date_to`, `date_preset`), `job_status`, `include_cancelled`, `customer_id`, `vehicle_id`, `supervisor_employee_id`, search keyword, and pagination.
- **Controller & Endpoints (`ServiceJobsByJobTypeReportController.php`)**:
  - `index()`: Enforces `ReportingAuthorizationService::REPORTS_VIEW` returning structured grouping and summary JSON data.
  - `export()`: Enforces `ReportingAuthorizationService::REPORTS_EXPORT` delegating to `ReportExport` for PDF, XLSX, CSV, and HTML/Print generation.
- **Routes & Registry Integration**:
  - Registered `api/v1/reports/vehicle-service/jobs-by-type` and `api/v1/reports/vehicle-service/jobs-by-type/export/{format}` in `app/Modules/Reporting/Routes/api.php`.
  - Registered definition in `ReportCatalog.php` under the Vehicle Service section.

### Frontend Architecture (`resources/js/modules/reporting`)
- **Contracts & Types (`serviceJobsByJobTypeReportTypes.ts`)**:
  - Defined `ServiceJobsByJobTypeJob`, `ServiceJobsByJobTypeGroup`, `ServiceJobsByJobTypeSummary`, `ServiceJobsByJobTypeParams`, `ServiceJobsByJobTypeResult`, and date preset types.
- **API Client & Export Actions (`reportingApi.ts`, `ExportActions.tsx`)**:
  - Added `runServiceJobsByJobTypeReport()`.
  - Added `ServiceJobsByJobTypeParams` to `exportReport()` and `ExportActions` parameters union.
- **Report Page (`ServiceJobsByJobTypeReportPage.tsx`)**:
  - High-clarity, task-focused user interface adhering to design principles (zero raw IDs, human-readable labels, responsive layouts).
  - Quick date preset selector buttons (`Today`, `This Week`, `This Month`, `This Year`, `All Time`).
  - Comprehensive filters: Custom date range, Job Type dropdown (populated dynamically from `lookupVehicleServiceJobTypes()`), Job Status selector, Customer autocomplete (`CustomerLookupSelect`), Vehicle autocomplete (`VehicleLookupSelect`), Search keyword input, and "Include cancelled jobs" toggle.
  - Interactive KPI summary cards at top displaying Total Jobs, Total Revenue, Average Job Value, Total Discounts, and Total Subtotal.
  - Comparative Job Type Performance Table: Displays all Job Types side by side with Jobs count, Job Share %, Subtotal, Total Discounts, Total Revenue, Revenue Share progress bar, and Average Job Value, with footer totals.
  - Detailed Grouped Sections: Collapsible sections per Job Type with group header badges (count, revenue, average, revenue share %, mileage tracking indicator), "Expand All" / "Collapse All" controls, and inner desktop table / mobile cards with job details, status badges, and "View Job" links.
- **Navigation & Routing**:
  - Registered route `/reports/vehicle-service/jobs-by-type` with lazy loading in `resources/js/app/router.tsx`.
  - Added "Jobs by Job Type" link under Reports -> Vehicle Service menu in `resources/js/app/navigation/navigationConfig.ts`.
  - Mapped report path in `resources/js/modules/reporting/pages/ReportListPage.tsx`.

## 2. Verification
- **Backend Tests (`ServiceJobsByJobTypeReportTest.php`)**:
  - `test_service_jobs_by_job_type_report_returns_accurate_groups_and_metrics` (passed)
  - `test_service_jobs_by_job_type_report_filters_by_job_type` (passed)
  - `test_service_jobs_by_job_type_report_filters_by_date_range` (passed)
  - `test_service_jobs_by_job_type_report_filters_by_customer_and_vehicle` (passed)
  - `test_service_jobs_by_job_type_report_search_matches` (passed)
  - `test_service_jobs_by_job_type_report_enforces_tenant_isolation` (passed)
  - `test_service_jobs_by_job_type_report_export_csv_and_pdf` (passed)
  - All 57 tests in `app/Modules/Reporting/Tests` passed (688 assertions).
  - Laravel Pint code style passed with 0 issues.
- **Frontend Tests (`ServiceJobsByJobTypeReportPage.test.tsx`)**:
  - `renders header, KPI metrics cards, comparison table, and grouped jobs` (passed)
  - `toggles group collapse when header is clicked` (passed)
  - `allows changing date preset and re-fetches report` (passed)
  - `submits search filter keyword` (passed)
  - `renders empty message when no service jobs found` (passed)
  - All 17 test files (32 tests) in `resources/js/modules/reporting` passed.
  - `npm run typecheck` passed with 0 errors.
  - `npx eslint` passed with 0 errors across all modified and new files.
