# Authentication & Report Export Hardening: `Route [login] not defined` Resolution & Direct API Navigations

**Date:** 2026-11-10  
**Modules:** `Core`, `Auth`, `VehicleRental`  
**Scope:** Resolves the `Route [login] not defined.` exception, eliminates unauthenticated direct API window/tab navigations, standardizes authenticated report blob exports, and hardens guest redirection and error handling across both backend and frontend layers.

---

## 1. Problem & Root Cause Analysis

### A. Backend Route Redirection & Exception Handling
- **Symptoms:**
  Navigating to any protected API endpoint via a browser address bar, link, or popup without authorization headers (e.g., `GET /api/v1/vehicle-rental/reports/running-chart-sheet/export/print?...`) yielded:
  ```json
  {
    "success": false,
    "message": "Route [login] not defined.",
    "error": {
      "code": "DOMAIN_RULE_FAILED",
      "type": "domain",
      "message": "Route [login] not defined."
    }
  }
  ```
- **Root Causes:**
  1. **Missing `redirectGuestsTo` configuration:** Laravel 11's default `Illuminate\Auth\Middleware\Authenticate` redirects unauthenticated requests that do not expect JSON (`$request->expectsJson() === false`) to `route('login')`. Browser-initiated top-level navigations send `Accept: text/html...`, causing Laravel to invoke `route('login')` on an API route.
  2. **Missing named `'login'` route:** The application defines a fallback route for SPA rendering in `routes/web.php`, but neither named `login` nor redirected guests safely.
  3. **Exception Handler Mapping Collision:** Symfony's `RouteNotFoundException` inherits from `InvalidArgumentException`. The global exception handler caught it as an `InvalidArgumentException`, returning an HTTP 422 domain validation failure instead of properly handling authentication or route routing errors.

### B. Frontend Unauthenticated Direct API Navigations
- **Symptoms:**
  In Vehicle Rental ("Print Sheet", "Print Owner Sheet", and 7 reporting pages), the UI was using raw `window.open(url)` or `<a href={apiExportUrl} target="_blank">` pointing directly to `/api/v1/vehicle-rental/reports/...`.
- **Root Cause:**
  Browser navigations to top-level URLs via `window.open` or `<a href="...">` do not pass custom HTTP headers (such as `Authorization: Bearer <token>`, `X-Tenant-Id`, or `X-Organization-Unit-Id`). Consequently, requests arrived unauthenticated at the backend.

---

## 2. Solutions & Implementation

### A. Backend Foundation
1. **`bootstrap/app.php` Middleware Configuration:**
   - Configured `$middleware->redirectGuestsTo()`:
     ```php
     $middleware->redirectGuestsTo(static function (Request $request): ?string {
         if ($request->is('api/*') || $request->expectsJson()) {
             return null;
         }

         return route('login');
     });
     ```
     This ensures all `/api/*` endpoints strictly return `null` for redirects regardless of `Accept` headers, reliably triggering `AuthenticationException`.
2. **`routes/web.php` Named Route Definition:**
   - Defined `Route::get('/login', ...)->name('login')` to guarantee that any web-context guest redirect resolves to the SPA login interface without throwing `RouteNotFoundException`.
3. **`bootstrap/app.php` RouteNotFoundException Handling:**
   - Added explicit handling for `Symfony\Component\Routing\Exception\RouteNotFoundException` returning 404 for API requests, avoiding misclassification as a 422 domain rule violation.

### B. Frontend Authenticated Blob Export Standard
1. **`resources/js/shared/utils/reportExport.ts` & `reportExport.test.ts`:**
   - Implemented `exportReportBlob()` utility using `apiClient.get<Blob>()`.
   - Attaches authenticated Bearer tokens and tenant headers in-memory.
   - For print/html formats, securely pipes the resulting blob URL into a pre-opened preview window (`window.location.replace(blobUrl)`), avoiding browser popup blockers.
   - For pdf/file formats, initiates an immediate authenticated download.
   - Closes the preview window and throws `toApiError` on request failure.
2. **Vehicle Rental Running Charts & Reports Integration:**
   - Updated `RunningChartsPanel.tsx`, `RunningChartRegisterPage.tsx`, and `RentalRunningChartReportPage.tsx` to use `exportRunningChartReport()` with loading feedback.
   - Updated `FleetUtilizationReportPage.tsx`, `LesseeOutstandingReportPage.tsx`, `OwnerDisbursementReportPage.tsx`, `RentalIncidentReportPage.tsx`, `RentalRevenueReportPage.tsx`, `VehicleProfitabilityReportPage.tsx`, and `VehicleRevenueLicenseReportPage.tsx` to replace raw `<a target="_blank">` links with interactive buttons using `exportRentalReport()`.

---

## 3. Verification & Quality Gates

- **Vitest Frontend Tests:**
  - `npx vitest run resources/js/modules/vehicle-rental resources/js/shared/utils/reportExport.test.ts`: **30 passed (30 files, 134 tests)**.
- **Type Checking & Linting:**
  - `npm run typecheck`: **0 errors**.
  - `npm run lint`: **0 errors**.
- **Backend PHPUnit Tests:**
  - `php artisan test app/Modules/VehicleRental/Tests`: **230 passed (1,948 assertions)**.
  - `php artisan test app/Modules/Invoice/Tests`: **41 passed (237 assertions)**.
