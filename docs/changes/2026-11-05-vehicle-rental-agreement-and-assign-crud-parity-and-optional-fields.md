# Vehicle Rental: Agreement and Assign CRUD Inconsistencies and Optional Fields Hardening

**Date:** 2026-11-05  
**Module:** `VehicleRental`  
**Scope:** Backend (`app/Modules/VehicleRental/`), Frontend (`resources/js/modules/vehicle-rental/`), Tests

## Context & Purpose
A comprehensive audit of the Agreement CRUD and Assign (Vehicle Use) CRUD lifecycles identified several subtle bugs, validation rigidities, and inconsistencies where optional fields or operations failed when values were omitted or submitted empty:
1. **Agreement `terms` Array Enforced as Present**: In `AgreementValidation::validate()`, `'terms' => ['present', ...]` rejected payloads that did not explicitly contain a `terms` object. Clients submitting pure commercial agreements with unconfigured rate terms failed validation.
2. **Empty Term Strings Converted to `0.000000`**: When term inputs were left blank or sent as empty strings, `bcadd($amount, '0', 6)` converted `""` into `"0.000000"`, changing an unconfigured term (unknown/not applicable) into an explicit zero allowance or zero rate (e.g. 0 km daily allowance, charging all distance as excess).
3. **Empty String Dates Cast as Today**: In `AgreementValidation`, when `ends_on` or `executing_on` was submitted as `""`, `$data['ends_on'] ?? null` evaluated to `""`. When persisted through Eloquent's `immutable_date` casting, Carbon parsed `""` as `now()`, turning open-ended agreements into agreements ending on today's date.
4. **Draft Update Omitted Reference Failure**: On `AgreementAction::Update`, if `reference` was omitted or cleared, validation failed with `"The reference field is required"`, rather than defaulting to the existing record reference.
5. **Auto-Generated Reference Blocked in UI**: In `AgreementEditor.tsx`, the submit button was disabled if `!reference.trim()`, even though the field hint stated *"Leave blank to automatically generate a unique contract reference"*. Operators could not submit new agreements with automatically generated references.
6. **Assign Plan Company Ownership Empty String Failure**: In `VehicleUseService::validatePlanInput()`, when `"owner_agreement_id": ""` was sent for company-owned vehicles, `Validator::make` returned `""` which caused `OwnerAgreement::findOrFail("")` to throw `ModelNotFoundException` instead of taking the company-owned vehicle path.
7. **Explicit DELETE Route & Method Coverage**: REST clients calling `DELETE` on agreements or vehicle uses received generic 405 error responses. Implementing explicit controller `destroy` methods and routes returns human-readable `MethodNotAllowedHttpException` explanations explaining why deletion is forbidden to protect legal and physical custody history.
8. **Vehicle Availability Optional End Label**: In `VehicleAvailabilityPage.tsx`, updated the expected end datetime input label to clarify that the end datetime is optional.

## Changes Made

### Backend
1. **`app/Modules/VehicleRental/Services/AgreementValidation.php`**:
   - Changed `'terms'` validation rule from `'present'` to `['sometimes', 'nullable', 'array:...']`.
   - Defaulted omitted or non-array `terms` to `[]`.
   - Normalized empty string / whitespace term values to `null`.
   - Normalized `executing_on`, `ends_on`, and `notes` empty strings / whitespace to `null` before and after validation.
   - Guaranteed that empty or null terms stay `null` and are not coerced into `"0.000000"`.
2. **`app/Modules/VehicleRental/Services/AgreementService.php`**:
   - In `change()`, preserved existing `$record->reference` when `reference` is omitted or empty on draft updates.
   - In `successor()`, normalized empty string `executing_on` and `ends_on` to `null` before validation and in `$clone`.
   - Added `delete()` method throwing `LogicException` to preserve agreement legal history.
3. **`app/Modules/VehicleRental/Http/Controllers/AgreementController.php`**:
   - Added `destroy()` method throwing `MethodNotAllowedHttpException` explaining that rental agreements cannot be deleted to preserve legal and financial audit history.
4. **`app/Modules/VehicleRental/Http/Requests/AgreementRequest.php`**:
   - Allowed `DELETE` method without requiring `expected_version` or prohibiting `status`.
5. **`app/Modules/VehicleRental/Services/VehicleUseService.php`**:
   - In `validatePlanInput()`, normalized `owner_agreement_id`, `ends_at`, and `notes` to `null` before validation and ensured empty values stay `null` afterwards.
   - In `replace()` and `transition()`, normalized `owner_agreement_id`, `ends_at`, `notes`, and odometers to `null`.
   - Added `delete()` method throwing `LogicException` to preserve physical custody history.
6. **`app/Modules/VehicleRental/Http/Controllers/VehicleUseController.php`**:
   - Added `destroy()` method throwing `MethodNotAllowedHttpException` explaining that vehicle custody uses cannot be deleted to preserve physical custody history.
7. **`app/Modules/VehicleRental/Routes/api.php`**:
   - Added `DELETE {kind}/agreements/{agreement}` and `DELETE vehicle-uses/{use}` routes.
8. **`app/Modules/VehicleRental/Tests/AgreementTest.php`**:
   - Added `test_agreement_creation_accepts_omitted_and_empty_optional_fields`.
   - Added `test_agreement_service_delete_throws_logic_exception`.
9. **`app/Modules/VehicleRental/Tests/VehicleUseTest.php`**:
   - Added `test_vehicle_use_plan_accepts_empty_string_owner_agreement_id_for_company_vehicle`.
   - Added `test_vehicle_use_service_delete_throws_logic_exception`.

### Frontend
1. **`resources/js/modules/vehicle-rental/AgreementEditor.tsx`**:
   - Updated submit button `disabled` condition to `(Boolean(record) && !reference.trim())`, allowing new agreements to be submitted with empty references for auto-generation.
2. **`resources/js/modules/vehicle-rental/VehicleAvailabilityPage.tsx`**:
   - Updated label to `"Expected end date & time (optional)"`.
3. **`resources/js/modules/vehicle-rental/AgreementEditor.test.tsx`**:
   - Added test verifying save button behavior for new agreements vs editing existing agreements with empty references.

## Verification
- **PHPUnit**: `php artisan test --filter=VehicleRental` $\rightarrow$ **198 passed (1,731 assertions)**.
- **Vitest**: `npx vitest run resources/js/modules/vehicle-rental` $\rightarrow$ **28 suites passed (116 tests)**.
- **TypeScript**: `npm run typecheck` $\rightarrow$ **0 errors**.
- **ESLint**: `npm run lint` $\rightarrow$ **0 errors, 0 warnings**.
- **Vite Build**: `npm run build` $\rightarrow$ **Clean production build in 8.86s**.
