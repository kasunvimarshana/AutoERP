# Vehicle Rental: Automatic Agreement Reference Generation

**Date:** 2026-11-05  
**Module:** `VehicleRental`  
**Scope:** Backend (`app/Modules/VehicleRental/`), Frontend (`resources/js/modules/vehicle-rental/`), Tests

## Context & Purpose

The **Agreement Reference** (`reference`) is a mandatory, tenant-unique commercial identifier required by database constraints, financial audit trails, downstream invoice allocations (`source_document_number`), and receivables reporting.

Leaving the field empty previously resulted in:
1. Browser HTML5 form validation rejection (`"Please fill out this field."`).
2. API `422 Unprocessable Entity` validation rejection (`"The reference field is required."`).
3. Database constraint violations if bypassed.

### Architectural Evaluation of Options:

1. **Option A: Making the field optional (nullable in database and domain)**
   - **Flaw:** Violates core architectural rules (*"Always present relational data in human-readable format in UI. Never expose raw IDs to users."*). If null, agreements in vehicle assignments, running charts, billing statements, and reports become indistinguishable or default to raw database IDs. Downstream financial invoices lose their legal document reference.
   - **Verdict:** Rejected as fundamentally flawed.

2. **Option B: Automatically generating a unique sequence number when not provided**
   - **Advantage:** Preserves database `NOT NULL` and tenant-unique constraints (`vrca_reference_uk`, `vroa_reference_uk`). Guarantees every agreement has a human-readable, professional, zero-raw-ID identifier (`VRC-2026-00001`, `VRO-2026-00001`). Streamlines user experience by eliminating form-filling friction, while still allowing operators to specify custom legal contract codes whenever applicable.
   - **Verdict:** Implemented following the established enterprise sequence pattern (`GenerateSequenceNumberService`) used across `Invoice`, `Vehicle`, `Customer`, and `Expense`.

## Changes Made

### Backend

1. **`app/Modules/VehicleRental/Services/AgreementNumberService.php`**:
   - Introduced `AgreementNumberService` utilizing `GenerateSequenceNumberService` to generate tenant-scoped sequential references (`VRC-{PERIOD}-` for customer agreements, `VRO-{PERIOD}-` for owner agreements).
   - Enforced retry logic with collision detection against existing records to prevent race conditions or manual overlap.

2. **`app/Modules/VehicleRental/Services/AgreementService.php`**:
   - In `create()`, resolved `$input['reference']` via `AgreementNumberService::next()` when omitted or blank prior to domain validation.
   - In `successor()`, resolved `$input['reference']` via `AgreementNumberService::next()` when omitted or blank prior to validation.

3. **`app/Modules/VehicleRental/Services/RentalReservationService.php`**:
   - Removed ad-hoc random string generation (`'CA-'.strtoupper(bin2hex(random_bytes(4)))`) in `convertToAgreement()` in favor of the centralized `AgreementNumberService`.

4. **`app/Modules/VehicleRental/Tests/AgreementTest.php`**:
   - Added `test_agreement_reference_is_automatically_generated_when_omitted` verifying customer and owner auto-generation and custom reference preservation.
   - Added `test_successor_reference_is_automatically_generated_when_omitted` verifying auto-generation for successor drafts.

### Frontend

1. **`resources/js/modules/vehicle-rental/agreements.ts`**:
   - Made `reference?: string` optional in `AgreementPayload` and `AgreementSuccessorPayload`.

2. **`resources/js/modules/vehicle-rental/AgreementEditor.tsx`**:
   - Made the reference input optional on creation (`required={Boolean(record)}`) with contextual hint: `"Leave blank to automatically generate a unique contract reference"`.
   - Trimmed and forwarded `undefined` when blank to trigger server-side generation.

3. **`resources/js/modules/vehicle-rental/AgreementSuccessorForm.tsx`**:
   - Made the successor reference input optional with contextual auto-generation guidance.
   - Enabled submission without forcing manual reference entry.

4. **`resources/js/modules/vehicle-rental/AgreementEditor.test.tsx`**:
   - Added test verifying reference guidance and optional attribute on creation.

## Verification

- **PHPUnit**: `php artisan test --filter=VehicleRental` $\rightarrow$ **187 passed (1,656 assertions)**.
- **Vitest**: `npx vitest run resources/js/modules/vehicle-rental` $\rightarrow$ **28 suites passed (112 tests)**.
- **TypeScript**: `npm run typecheck` $\rightarrow$ **0 errors**.
- **ESLint**: `npm run lint` $\rightarrow$ **0 errors, 0 warnings**.
- **Vite Build**: `npm run build` $\rightarrow$ **Clean production build in 8.24s**.
