# Service Job Types Complete CRUD Implementation

**Date:** 2026-11-21  
**Module:** Vehicle Service (`app/Modules/VehicleService`, `resources/js/modules/vehicle-service`)

## 1. Summary of Changes
Implemented a complete, robust, and extensible CRUD (Create, Read, Update, Delete) architecture for **Service Job Types** in the Vehicle Service module, transitioning from a rigid hardcoded enum to a dynamic, tenant-scoped, and system-safeguarded relational model:

### Database & Migrations
- **Table Migration (`2026_06_12_190000_create_vehicle_service_job_types_table.php`)**:
  - Adheres strictly to the single-table-per-migration standard.
  - Columns: `id`, `row_version` (concurrency check), `tenant_id`, nullable `organization_unit_id`, `code` (string 64), `name` (string 150), `description` (string 500, nullable), `tracks_mileage` (boolean, default false), `is_active` (boolean, default true), `is_system` (boolean, default false), `sort_order` (integer, default 0), `created_by`, `updated_by`, timestamps, and `softDeletes`.
  - Constraints: Unique constraint on `[tenant_id, code]`, composite foreign keys to tenants, organization units, and users.
- **Service Jobs Migration Update (`2026_06_12_190001_create_vehicle_service_jobs_table.php`)**:
  - Added nullable `job_type_id` foreign key referencing `vehicle_service_job_types(id)` with set null on delete.
  - Added composite index on `[tenant_id, job_type_id]`.

### Backend Architecture
- **Enum (`VehicleServiceJobType.php`)**:
  - Retained backed string enum for system defaults (`full_service`, `body_wash`, `oil_change`, `wheel_alignment`, `accessories`).
  - Added `defaults()` method defining built-in names, descriptions, sort orders, and mileage tracking flags.
- **Model (`VehicleServiceJobType.php`)**:
  - Extends `TenantOwnedModel` with `HasVehicleServiceJobRowVersion` and `SoftDeletes`.
  - Defines relationships: `tenant()`, `organizationUnit()`, `serviceJobs()`, `creator()`, `updater()`.
  - Added scopes: `scopeActive()` and `scopeOrdered()`.
- **Model Integration (`VehicleServiceJob.php`)**:
  - Added `jobType()` BelongsTo relationship.
  - Casts `job_type_id` to integer.
  - Added `setTypeAttribute` mutator for seamless conversion of string codes and BackedEnum values.
- **Permissions (`VehicleServicePermission.php` & `vehicleServicePermissions.ts`)**:
  - Added `JOB_TYPES_VIEW = 'vehicle_service.job_types.view'`.
  - Added `JOB_TYPES_MANAGE = 'vehicle_service.job_types.manage'`.
- **Data Transfer & Form Requests**:
  - `VehicleServiceJobTypeData.php`: Immutable DTO with validation and normalization.
  - `ListVehicleServiceJobTypesRequest.php`: Validates query parameters (`tenant_id`, `organization_unit_id`, `search`, `is_active`, pagination).
  - `StoreVehicleServiceJobTypeRequest.php`: Validates uniqueness of `code` within tenant, required fields, and types.
  - `UpdateVehicleServiceJobTypeRequest.php`: Validates `expected_version`, uniqueness ignoring current record, and protects system type code changes.
  - Updated `StoreVehicleServiceJobRequest.php` & `VehicleServiceJobData.php`: Accepts `job_type_id`, validates dynamically against resolved job type's `tracks_mileage`.
- **Service Layer (`VehicleServiceJobTypeService.php`)**:
  - `ensureDefaults()`: Seeds system default job types for a tenant if not present.
  - `resolveForJob()`: Resolves job type by ID or fallback code, enforces active status for new assignments, and validates tenant boundary.
  - `create()`, `update()`, `delete()`: Optimistic locking with `assertExpectedVersion`, unique code validation, system type protection, and in-use checks preventing deletion when referenced by service jobs.
  - `paginate()` & `lookup()`: Cached, sorted, and filtered listings.
- **Service Layer Integration (`VehicleServiceJobService.php`)**:
  - Injected `VehicleServiceJobTypeService`.
  - Resolves job type on job creation and update; verifies odometer reading requirement based on resolved `tracks_mileage`.
  - Stores `job_type_id` and eager-loads `jobType` relation.
- **Controller & API Resources (`VehicleServiceJobTypeController.php`, `VehicleServiceJobTypeResource.php`)**:
  - Endpoints: `GET /api/v1/vehicle-service/job-types` (paginated list), `GET /api/v1/vehicle-service/job-types/lookup` (active lookup), `POST /api/v1/vehicle-service/job-types` (create), `GET /api/v1/vehicle-service/job-types/{id}` (show), `PUT /api/v1/vehicle-service/job-types/{id}` (update), `DELETE /api/v1/vehicle-service/job-types/{id}` (delete).
  - Updated `VehicleServiceJobResource.php` to include `job_type_id`, loaded `job_type` object, and dynamic `type_label`.
- **Database Seeder (`VehicleServiceSeeder.php`)**:
  - Seeds default job types across tenants upon system initialization.

### Frontend Architecture
- **Contracts (`vehicleServiceTypes.ts`)**:
  - Defined `VehicleServiceJobTypeResource` and `VehicleServiceJobTypePayload`.
  - Updated `tracksVehicleServiceMileage` helper to accept either `VehicleServiceJobTypeResource` or string code.
  - Updated `VehicleServiceJob` and `VehicleServiceJobPayload` with `job_type_id` and `job_type`.
- **API Client (`jobTypes.ts` & `vehicleServiceApi.ts`)**:
  - Added `listVehicleServiceJobTypes`, `lookupVehicleServiceJobTypes`, `getVehicleServiceJobType`, `createVehicleServiceJobType`, `updateVehicleServiceJobType`, and `deleteVehicleServiceJobType`.
- **Job Types Management Page (`VehicleServiceJobTypeListPage.tsx`)**:
  - Responsive table layout with mobile cards.
  - Human-readable badges for Mileage Tracking (Required vs Not Tracked), Type Kind (System vs Custom), and Status (Active vs Inactive).
  - Search filter and status filtering.
  - FormDrawer for creating and editing job types with live validation, system type code protection, and optimistic locking.
  - Safe delete confirmation modal with protections against deleting system types or in-use types.
  - Fast inline activation/deactivation toggles.
- **Job Form Integration (`VehicleServiceJobForm.tsx`)**:
  - Dynamically fetches active job types via `lookupVehicleServiceJobTypes()`.
  - Renders human-readable job types in `<Select>` with fallback to default options.
  - Dynamically toggles odometer and next service mileage requirements and validation hints based on selected job type's `tracks_mileage`.
  - Sends `job_type_id` and `type` code in job payloads.
- **Navigation & Routing**:
  - Registered route `/vehicle-service/job-types` with lazy loading in `resources/js/app/router.tsx`.
  - Added route entitlement with `jobTypesView` in `resources/js/app/access/commerceRouteEntitlements.ts`.
  - Added "Service Job Types" under Vehicle Service menu in `resources/js/app/navigation/navigationConfig.ts`.
  - Added "Job types" shortcut button in `VehicleServiceJobListPage.tsx` header for authorized users.

## 2. Verification
- **Backend Tests (`VehicleServiceJobTypeTest.php`)**:
  - `test_job_types_index_seeds_and_returns_system_defaults` (passed)
  - `test_job_types_lookup_returns_only_active_items` (passed)
  - `test_custom_job_type_can_be_created` (passed)
  - `test_job_type_code_uniqueness_within_tenant` (passed)
  - `test_job_type_can_be_updated_with_optimistic_locking` (passed)
  - `test_system_job_type_code_cannot_be_modified` (passed)
  - `test_system_job_type_cannot_be_deleted` (passed)
  - `test_job_type_in_use_by_service_job_cannot_be_deleted` (passed)
  - `test_unreferenced_custom_job_type_can_be_deleted` (passed)
  - `test_service_job_creation_respects_custom_job_type_mileage_requirement` (passed)
- **Full Backend Suite**:
  - Ran `php artisan test app/Modules/VehicleService`: 95 passed (684 assertions).
- **Frontend Tests (`VehicleServiceJobTypeListPage.test.tsx` & full suite)**:
  - 6 unit/integration tests in `VehicleServiceJobTypeListPage.test.tsx` (all passed).
  - Ran `npx vitest run resources/js/modules/vehicle-service`: 19 test files, 101 tests passed (0 failures).
- **Static Analysis & Linting**:
  - `npm run typecheck`: clean (0 errors).
  - `npx eslint`: clean (0 errors).
  - `php vendor/bin/pint --test`: passed.
