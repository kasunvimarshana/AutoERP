# Employee Commission and Vehicle Service History Reports

Date: 2026-09-14
Status: Finalized for development

## Confirmed scope

- Employee Commission is calculated and reported against HR employees assigned to labour items, including combo-item technician and supervisor assignments. Login users are not the reporting subject.
- Vehicle History contains Vehicle Service jobs only. Rental, ownership, and generic vehicle status history are outside this report.
- Employee Commission defaults to the current calendar month.
- Both reports appear as dedicated pages in the Reporting module.

## Employee Commission report

The default experience is employee-first. The primary controls are period presets, employee lookup, search, and commission status. Department, designation, supervisor, job, invoice, payment, and source filters remain available under an explicit More Filters section.

The report presents total, earned, pending, and cancelled commission, with jobs and hours as supporting metrics. Its primary table groups results by HR employee and shows jobs, hours, labour value, earned commission, pending commission, and total commission. Selecting an employee exposes the job-level commission breakdown. Job numbers link to their Vehicle Service job pages. Billing and lifecycle detail remains secondary so it does not overload the primary workflow.

The existing EmployeeCommissionReportService remains the canonical commission calculation source. No parallel commission calculation or login-user mapping will be introduced. Earned commission describes commission lifecycle eligibility; it must not be labelled as paid unless a separate payroll settlement source exists.

## Vehicle Service History report

The page is search-first and requires the user to find/select a vehicle by its human-readable registration number. Registration variants such as `ABC-1234`, `ABC 1234`, and `ABC1234` use the Vehicle module's normalized search behavior.

After selection, the page shows vehicle identity, current customer/owner context, odometer, status, total service jobs, and last-service information. The history is ordered newest first and paginated one Vehicle Service job per record. Each record shows service date, clickable job number, status, odometer, complaint/work summary, assigned technicians and supervisor, and available invoice/payment summary. Complaint, diagnosis, recommended/performed work, parts, and staff detail are presented in an expandable section to keep the page fast and readable.

Job-number links navigate to `/vehicle-service/jobs/{jobId}`. Date, job-status, and cancelled-job controls become available after vehicle selection. Related resources are returned as structured objects and raw foreign keys are never exposed to users.

The Reporting module owns the read-only report projection. Vehicle lookup stays owned by Vehicle, service history stays sourced from Vehicle Service, and invoice/payment facts stay sourced from their owning modules. The implementation must paginate, eager-load intentionally, avoid N+1 queries, preserve tenant scoping, and reuse canonical business rules rather than duplicate them.

## Verification

- Backend feature coverage for authorization, tenant isolation, normalized vehicle registration lookup, Vehicle Service-only history, job links/identifiers, pagination, and commission grouping.
- Frontend coverage for current-month defaults, progressive filters, employee drill-down, vehicle search/selection, expandable job history, and job navigation.
- Targeted backend tests, targeted frontend tests, and a production frontend build.
