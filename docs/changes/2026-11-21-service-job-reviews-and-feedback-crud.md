# Service Job Reviews, Comments, Ratings, and Feedback CRUD Implementation

**Date:** 2026-11-21  
**Module:** Vehicle Service (`app/Modules/VehicleService`, `resources/js/modules/vehicle-service`)

## 1. Summary of Changes
Implemented complete CRUD functionality for Service Job reviews, comments, ratings, and related feedback in the `VehicleService` module. This feature allows workshop staff, technicians, service advisors, and customers to submit and track feedback with proper rating management, validation, optimistic concurrency control, and authorization.

### Backend Architecture (`app/Modules/VehicleService`)
- **Database Schema & Migrations (`2026_11_21_190001_create_vehicle_service_job_feedbacks_table.php`)**:
  - Created `vehicle_service_job_feedbacks` table adhering to single table per migration rule.
  - Columns: `id`, `row_version` (concurrency check), `tenant_id`, `organization_unit_id`, `vehicle_service_job_id`, `user_id`, `author_name`, `feedback_type`, `rating`, `title`, `comment`, `is_customer_visible`, `created_by`, `updated_by`, timestamps, and soft deletes.
  - Multi-tenant composite foreign keys linking to `tenants`, `organization_units`, `vehicle_service_jobs` (cascade delete), and `users` (restrict delete).
  - Explicit indices on `[id, tenant_id]` (unique), `[tenant_id, vehicle_service_job_id, created_at]`, and `[tenant_id, feedback_type, rating]`.
- **Enums & Permissions (`VehicleServiceFeedbackType.php`, `VehicleServicePermission.php`)**:
  - `VehicleServiceFeedbackType`: Enum supporting `review` ("Customer Review"), `internal_note` ("Internal Note"), `customer_feedback` ("General Feedback"), and `complaint` ("Customer Complaint").
  - `VehicleServicePermission`: Added `FEEDBACK_VIEW = 'vehicle_service.feedback.view'` and `FEEDBACK_MANAGE = 'vehicle_service.feedback.manage'`, automatically registered in `VehicleServiceServiceProvider`.
- **Domain Model (`VehicleServiceJobFeedback.php`, `VehicleServiceJob.php`)**:
  - Extends `TenantOwnedModel` with `HasVehicleServiceJobRowVersion` and `SoftDeletes`.
  - Added `feedbacks(): HasMany` relationship to `VehicleServiceJob`.
- **DTO & Requests (`VehicleServiceJobFeedbackData.php`, `Http/Requests/*`)**:
  - `ListVehicleServiceJobFeedbacksRequest`: Scoped query validation for category, rating, customer visibility, and search.
  - `StoreVehicleServiceJobFeedbackRequest`: Validates `feedback_type`, `rating` (1–5 integer), `title`, `comment` (required, max 5000), `is_customer_visible`, `author_name`, and `expected_version`.
  - `UpdateVehicleServiceJobFeedbackRequest`: Validates inputs and enforces required `expected_version` for conflict-aware optimistic locking.
  - `DeleteVehicleServiceJobFeedbackRequest`: Validates optional `expected_version`.
- **Service Layer (`VehicleServiceJobFeedbackService.php`)**:
  - `list()`: Filters by category, star rating, search keyword, and customer visibility.
  - `summary()`: Aggregates metrics including average rating (rounded to 2 decimal places), total feedback count, count per category, and complete 1–5 star rating breakdown distribution.
  - `create()`, `update()`, `delete()`: Atomic transactions with version validation and automatic row version bumping.
- **Controller & API Resource (`VehicleServiceJobFeedbackController.php`, `VehicleServiceJobFeedbackResource.php`)**:
  - Endpoints registered under `api/v1/vehicle-service/jobs/{job}/feedbacks`:
    - `GET jobs/{job}/feedbacks`: returns paginated/filtered feedbacks and rating summary.
    - `GET jobs/{job}/feedbacks/summary`: returns summary statistics.
    - `GET jobs/{job}/feedbacks/{feedback}`: returns single feedback resource.
    - `POST jobs/{job}/feedbacks`: creates new feedback.
    - `PUT jobs/{job}/feedbacks/{feedback}`: updates feedback with optimistic concurrency check.
    - `DELETE jobs/{job}/feedbacks/{feedback}`: soft-deletes feedback with concurrency protection.

### Frontend Architecture (`resources/js/modules/vehicle-service`)
- **Contracts & Permissions (`vehicleServiceTypes.ts`, `vehicleServicePermissions.ts`)**:
  - Defined `VehicleServiceFeedbackType`, `VehicleServiceJobFeedback`, `VehicleServiceFeedbackSummary`, `VehicleServiceFeedbackListResult`, and `VehicleServiceJobFeedbackPayload`.
  - Exported `feedbackView` and `feedbackManage` permissions.
- **API Client (`api/feedbacks.ts`, `vehicleServiceApi.ts`)**:
  - `listVehicleServiceFeedbacks()`, `getVehicleServiceFeedbackSummary()`, `createVehicleServiceFeedback()`, `updateVehicleServiceFeedback()`, `deleteVehicleServiceFeedback()`.
- **UI Components & Job Details Integration**:
  - `VehicleServiceFeedbackTab.tsx`:
    - Overall rating card with average star score, full/half star visualizer, and total review count.
    - Interactive 5-star distribution progress bars with counts and click-to-filter capability.
    - Summary category counts (Reviews, Internal Notes, General Feedback, Complaints).
    - Filter toolbar (Category selector, Star Rating selector, real-time search input, Clear Filters button).
    - Feedback cards displaying category badges, star ratings, customer visibility indicators ("Customer Visible" vs "Internal Only"), author names, timestamps, and edit/delete actions.
    - Accessible delete confirmation modal (`ConfirmDialog`).
  - `VehicleServiceFeedbackDialog.tsx`:
    - Accessible dialog (`Modal`) for creating and editing feedback.
    - Interactive 5-star rating selector with hover preview, clearable rating option, and rating labels.
    - Feedback category selector with automatic defaults for customer visibility and ratings.
    - Title, author name, and comment textarea with validation feedback.
    - "Visible to customer" toggle with explanatory description.
  - `VehicleServiceJobDetailPage.tsx`:
    - Integrated `Reviews & Feedback` tab into the service job tabs header with lazy loading.
    - Added "View feedback" quick action button.

## 2. Verification
- **Backend Tests (`VehicleServiceJobFeedbackTest.php`)**:
  - `test_can_list_feedbacks_and_summary_with_tenant_isolation` (passed)
  - `test_can_create_customer_review_with_star_rating` (passed)
  - `test_can_create_internal_note_without_rating` (passed)
  - `test_rating_validation_rules` (passed)
  - `test_can_update_feedback_with_optimistic_concurrency` (passed)
  - `test_can_delete_feedback` (passed)
  - `test_summary_endpoint_computes_accurate_rating_breakdown` (passed)
  - `test_filtering_feedbacks_by_type_and_rating` (passed)
  - All 103 tests (776 assertions) in `app/Modules/VehicleService/Tests` passed.
  - Laravel Pint code style passed with 0 issues.
- **Frontend Tests (`VehicleServiceFeedbackTab.test.tsx`)**:
  - `renders rating summary card, badges, and feedback items` (passed)
  - `filters feedback items by category when category filter changes` (passed)
  - `filters feedback items by star rating when selecting a star filter` (passed)
  - `opens create feedback dialog and submits a new review` (passed)
  - `opens edit dialog and updates existing feedback` (passed)
  - `opens delete confirm dialog and deletes feedback` (passed)
  - `renders empty state when no feedbacks exist` (passed)
  - All 20 test files (108 tests) in `resources/js/modules/vehicle-service` passed.
  - `npm run typecheck` passed with 0 errors.
  - `npx eslint` passed with 0 errors and 0 warnings.
