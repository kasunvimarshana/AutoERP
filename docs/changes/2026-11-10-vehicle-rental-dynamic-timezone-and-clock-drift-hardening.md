# Vehicle Rental: Dynamic Timezone Alignment, Clock Drift Skew Hardening & Temporal Validation Reconciliation

**Date:** 2026-11-10  
**Modules:** `VehicleRental`, `Auth`, `Configuration`, `Core`  
**Scope:** Resolves date-time validation failures across Customer Agreements and Running Charts:
1. Dynamically resolves application and tenant configured timezones without hardcoding.
2. Increases server and client clock skew tolerance to 2 minutes, preventing valid near-current timestamps from being rejected as future dates.
3. Provides "Use current time" shortcut for Usage start and clear contextual hints on Running Chart Editor, eliminating PM/AM selection errors.
4. Extensively tests vehicle replacement, running chart recording, and vehicle return across past, current, and future timestamps.

---

## 1. Problem & Root Cause Analysis

### A. Dynamic Timezone Resolution Gap
- **Symptoms:**
  The application was defaulting to `'UTC'` when `localization.timezone` had not been explicitly saved in `tenant_configuration_values`, because:
  - `config/app.php` had hardcoded `'timezone' => 'UTC'` rather than reading `env('APP_TIMEZONE', 'UTC')`.
  - `configuration.php` had `'default' => 'UTC'` for `localization.timezone` rather than reading the application configured timezone.
  - `TenantAuthProfileBuilder.php` omitted `$tenant['timezone']` when no database configuration row existed, causing the frontend auth session to receive `null` and fall back to the browser's timezone.
- **Root Cause:**
  When backend services evaluated `$this->calendar->timezone($context)`, it fell back to `'UTC'`, whereas the frontend ran in the user's browser timezone. While offsets were appended, evaluating against UTC `$now` without matching configuration defaults left subtle boundary discrepancies.

### B. Clock Drift & 12-Hour AM/PM Input Pitfalls
- **Symptoms:**
  Operators entering usage periods at current time encountered `Record completed usage ending after its start, not in the future.`.
  In real usage, entering timestamps with seconds (e.g. `10:31:03 AM` to `10:31:17 PM`) occurred because 12-hour datetime-local pickers allow accidental selection of `PM` instead of `AM`, or because sub-minute clock differences between client and server exceeded the tight 1-minute skew allowance.
- **Root Cause:**
  The server's tolerance was restricted to `addMinute()`. A client machine even 65 seconds ahead of the server immediately triggered future rejection. Furthermore, `Usage start` lacked a "Use current time" shortcut, forcing manual typing on one field while clicking "Use current time" on another.

---

## 2. Solutions & Implementation

### A. Dynamic Application & Workspace Timezone Resolution
1. **`config/app.php`:**
   - Updated `'timezone' => env('APP_TIMEZONE', 'UTC')`.
2. **`app/Modules/Configuration/Config/configuration.php`:**
   - Updated `'localization.timezone'` default to `env('APP_TIMEZONE', 'UTC')`.
3. **`app/Modules/VehicleRental/Services/RentalCalendar.php`:**
   - Hardened `timezone(AgreementContext $context)` to safely resolve tenant/organization configuration and fall back cleanly to `config('app.timezone', 'UTC')` if unconfigured or on configuration resolution error.
4. **`app/Modules/Auth/Services/TenantAuthProfileBuilder.php`:**
   - Ensured `$tenant['timezone']` and `$organizationUnit['timezone']` always populate with the resolved configuration or `config('app.timezone', 'UTC')`, guaranteeing client-side `configureBusinessTimeZone()` synchronizes with the backend.

### B. Clock Skew & Temporal Validation Hardening
1. **`app/Modules/VehicleRental/Services/RunningChartValidation.php`:**
   - Upgraded future time tolerance to `$now = $this->calendar->now($context)->addMinutes(2)`.
2. **`app/Modules/VehicleRental/Services/VehicleUseService.php`:**
   - Upgraded future time tolerance in `Handover`, `ReturnVehicle`, and `replace` to `$now = CarbonImmutable::now($tz)->addMinutes(2)`.
3. **`resources/js/modules/vehicle-rental/RunningChartEditor.tsx`:**
   - Upgraded client-side pre-submit tolerance to `nowMs = Date.now() + 120_000`.
   - Added a "Use current time" button to `Usage start` alongside `Use custody start time`.
   - Added contextual hint under `Usage end`: `Usage end must follow usage start and cannot be in the future.`.

---

## 3. Verification & Quality Gates

- **PHPUnit Backend Tests (`php artisan test app/Modules/VehicleRental/Tests`):**
  - Added past return, current return with clock skew, and future return rejection tests in `VehicleUseTest.php`.
  - Added replacement at current time with clock skew tolerance in `VehicleUseReplacementValidationTest.php`.
  - Added past usage, current usage with clock skew, and future usage tests in `RunningChartTest.php`.
  - **Result: 234 passed (1,981 assertions)**.
- **Frontend Vitest Tests (`npx vitest run resources/js/modules/vehicle-rental`):**
  - **Result: 29 passed test files (135 passed tests)**.
- **Static Analysis & Linting:**
  - `npm run typecheck`: **0 errors**.
  - `npm run lint`: **0 errors, 0 warnings**.
