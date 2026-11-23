# Comprehensive Dynamic Timezone Audit, Architecture & Standardization

## 1. Context & Objectives

A thorough audit of the entire codebase was conducted to ensure that all date and time handling consistently and dynamically utilizes the application's configured timezone across both backend and frontend layers.

Prior to this standardization:
1. Specific domain services, reports, and print formatters relied on static hardcoded timezone strings (e.g. `'Asia/Colombo'`) or fell back to server/PHP runtime clock time (`UTC`), leading to date skew for tenants in different geographical locations.
2. Form validation rules such as `'before_or_equal:today'` in Laravel form requests evaluated against the server's runtime clock (`UTC`), prematurely rejecting valid operational transactions submitted during evening hours in positive-offset timezones (e.g. UTC+05:30).
3. Certain frontend components formatted dates and times using local browser clocks (`.toLocaleString()`, `.toISOString().slice(0, 10)`), causing inconsistent display between different client machines and deviating from tenant business day boundaries.
4. Modular boundaries required clean separation: the core contract must be accessible by all modules while keeping the dependency graph strictly acyclic.

The objectives of this standardization:
1. Establish a single authoritative, dynamic timezone resolution architecture via `TimezoneResolverInterface` (`Modules\Core\Contracts`) and `TimezoneResolver` (`Modules\Configuration\Services`).
2. Provide a default fallback resolver `DefaultTimezoneResolver` (`Modules\Core\Services`) in `CoreServiceProvider`, cleanly overridden by `ConfigurationServiceProvider` when the configuration engine is booted.
3. Update `TenantScopedRequest` to provide `workspaceToday()` and `workspaceTimezone()` for context-aware request validation.
4. Eliminate all hardcoded timezones from backend domain services, reporting engines, pricing evaluators, cash receipt date policies, and print generation.
5. Standardize the frontend utilities in `businessDate.ts` and `formatDate.ts` to strictly consume the authenticated tenant session timezone (`localization.timezone`), providing helper functions `businessDateInputValue()`, `businessDateTimeInputValue()`, and `shiftBusinessDate()`.
6. Refactor all identified frontend pages and presentation layers to utilize the standardized dynamic date utilities.
7. Execute all quality gates (Pint, TypeScript, ESLint, Vitest, and PHPUnit) to guarantee 100% test pass rate with zero regressions.

---

## 2. Key Architectural Enhancements & Refactorings

### 2.1 Authoritative Backend Timezone Resolution
- **Contract:** Defined [TimezoneResolverInterface.php](file:///d:/project-new/AutoERP/app/Modules/Core/Contracts/TimezoneResolverInterface.php) with:
  - `resolve(?int $tenantId, ?int $organizationUnitId): string`
  - `now(?int $tenantId, ?int $organizationUnitId): CarbonImmutable`
  - `today(?int $tenantId, ?int $organizationUnitId): CarbonImmutable`
  - `todayDateString(?int $tenantId, ?int $organizationUnitId): string`
  - `toCivilDate(DateTimeInterface|string $instant, ?int $tenantId, ?int $organizationUnitId): string`
  - `toCivilDateTime(DateTimeInterface|string $instant, ?int $tenantId, ?int $organizationUnitId): CarbonImmutable`
- **Configuration Resolver:** Implemented [TimezoneResolver.php](file:///d:/project-new/AutoERP/app/Modules/Configuration/Services/TimezoneResolver.php) in `Modules\Configuration\Services`. Dynamically queries `ConfigurationResolverInterface` for key `localization.timezone` scoped to the current or passed tenant and organization unit. Falls back safely to `config('app.timezone')` and `'UTC'`.
- **Default Core Fallback:** Implemented [DefaultTimezoneResolver.php](file:///d:/project-new/AutoERP/app/Modules/Core/Services/DefaultTimezoneResolver.php) in `Modules\Core\Services` to satisfy low-level unit test contexts without external module dependencies.
- **Service Providers:**
  - Bound singleton `TimezoneResolverInterface` to `DefaultTimezoneResolver` in [CoreServiceProvider.php](file:///d:/project-new/AutoERP/app/Modules/Core/Providers/CoreServiceProvider.php).
  - Scoped binding overridden to `TimezoneResolver` in [ConfigurationServiceProvider.php](file:///d:/project-new/AutoERP/app/Modules/Configuration/Providers/ConfigurationServiceProvider.php).
  - Confirmed 100% acyclic dependency graph via `CoreModuleArchitectureTest`.

### 2.2 Dynamic Validation & Request Context
- **Base Request:** Updated [TenantScopedRequest.php](file:///d:/project-new/AutoERP/app/Modules/Core/Http/Requests/TenantScopedRequest.php) to expose `$this->workspaceToday()` and `$this->workspaceTimezone()`.
- **Form Requests Refactored:**
  - [StoreConsumableUsageRequest.php](file:///d:/project-new/AutoERP/app/Modules/Inventory/Http/Requests/StoreConsumableUsageRequest.php): Replaced `'before_or_equal:today'` with `'before_or_equal:'.$this->workspaceToday()`.
  - [ListFinanceRequest.php](file:///d:/project-new/AutoERP/app/Modules/Finance/Http/Requests/ListFinanceRequest.php): Replaced `'before_or_equal:today'` with `'before_or_equal:'.$this->workspaceToday()`.
  - [CustomerOutstandingReportRequest.php](file:///d:/project-new/AutoERP/app/Modules/Reporting/Http/Requests/CustomerOutstandingReportRequest.php): Resolved dynamic default `as_of_date` via `$this->workspaceToday()`.

### 2.3 Refactored Backend Domain & Reporting Services
Replaced hardcoded static timezones with `TimezoneResolverInterface`:
- **Auth:** [TenantAuthProfileBuilder.php](file:///d:/project-new/AutoERP/app/Modules/Auth/Services/TenantAuthProfileBuilder.php) resolves tenant timezone for session payload.
- **Reporting:**
  - [UpcomingServicesReportService.php](file:///d:/project-new/AutoERP/app/Modules/Reporting/Services/UpcomingServicesReportService.php) resolves today's date dynamically for summary counts, date filters, and row hydration.
  - [CashierReportService.php](file:///d:/project-new/AutoERP/app/Modules/Reporting/Services/CashierReportService.php) resolves dynamic default dates and formats printed report timestamps via `toCivilDateTime()`.
  - [DashboardSummaryService.php](file:///d:/project-new/AutoERP/app/Modules/Reporting/Services/DashboardSummaryService.php) resolves dynamic `today()` for operational metrics.
  - [GrnPayablesReportService.php](file:///d:/project-new/AutoERP/app/Modules/Reporting/Services/GrnPayablesReportService.php) computes `age_days` using the tenant's civil date.
  - [ServiceJobFeedbackReportService.php](file:///d:/project-new/AutoERP/app/Modules/Reporting/Services/ServiceJobFeedbackReportService.php) resolves dynamic `today()` in date filters.
  - [ServiceJobsByJobTypeReportService.php](file:///d:/project-new/AutoERP/app/Modules/Reporting/Services/ServiceJobsByJobTypeReportService.php) resolves dynamic `today()` in date filters.
- **Billing & Legal Documents:**
  - [InvoicePrintService.php](file:///d:/project-new/AutoERP/app/Modules/Invoice/Services/InvoicePrintService.php) formats invoice header timestamps via `toCivilDateTime()`.
  - [RentalBillingDocumentService.php](file:///d:/project-new/AutoERP/app/Modules/VehicleRental/Services/RentalBillingDocumentService.php) uses dynamic timezone resolution for documents and summaries.
  - [RentalCalendar.php](file:///d:/project-new/AutoERP/app/Modules/VehicleRental/Services/RentalCalendar.php) delegates timezone resolution to `TimezoneResolverInterface`.
- **Financial & Operational Policies:**
  - [PaymentCashReceiptDatePolicy.php](file:///d:/project-new/AutoERP/app/Modules/Payment/Services/PaymentCashReceiptDatePolicy.php) uses dynamic `todayDateString()` to enforce receipt entry rules.
  - [ExpensePostingService.php](file:///d:/project-new/AutoERP/app/Modules/Expense/Services/ExpensePostingService.php) uses dynamic civil dates.
  - [VehicleServiceInventoryFinanceService.php](file:///d:/project-new/AutoERP/app/Modules/VehicleService/Services/VehicleServiceInventoryFinanceService.php) uses dynamic civil dates for reversals.
  - [PurchaseDocumentContextService.php](file:///d:/project-new/AutoERP/app/Modules/Purchase/Services/PurchaseDocumentContextService.php) uses dynamic default purchase dates.
  - [BatchPriceService.php](file:///d:/project-new/AutoERP/app/Modules/Inventory/Services/BatchPriceService.php) & [ItemPriceResolutionService.php](file:///d:/project-new/AutoERP/app/Modules/Item/Services/ItemPriceResolutionService.php) resolve effective pricing dates dynamically.
  - [StockCardService.php](file:///d:/project-new/AutoERP/app/Modules/Inventory/Services/StockCardService.php) & [AuditQueryFactory.php](file:///d:/project-new/AutoERP/app/Modules/Audit/Services/AuditQueryFactory.php) dynamically adjust boundary dates.

### 2.4 Frontend Utilities & User Interface Standardization
- **Utilities (`businessDate.ts` & `formatDate.ts`):**
  - Added `shiftBusinessDate()` to adjust dates within the configured timezone calendar.
  - Parameterized `businessDateInputValue()` and `businessDateTimeInputValue()` to accept custom timezones while defaulting to `businessTimeZone()`.
  - Updated `formatDate.ts` to utilize `businessTimeZone()` dynamically.
  - Expanded [businessDate.test.ts](file:///d:/project-new/AutoERP/resources/js/shared/utils/businessDate.test.ts) with 12 tests covering multiple global timezones (`America/New_York`, `Asia/Tokyo`, `Europe/London`).
- **Pages & Components Updated:**
  - [PostingProfilePage.tsx](file:///d:/project-new/AutoERP/resources/js/modules/finance/pages/PostingProfilePage.tsx): Replaced `.toISOString().slice(0, 10)` with `businessDateInputValue()`.
  - [CashierReportPage.tsx](file:///d:/project-new/AutoERP/resources/js/modules/reporting/pages/CashierReportPage.tsx): Replaced date slicing with `shiftBusinessDate()` and table timestamps with `formatBusinessDateTime()`.
  - [VehicleServiceJobListPage.tsx](file:///d:/project-new/AutoERP/resources/js/modules/vehicle-service/pages/VehicleServiceJobListPage.tsx): Replaced date slicing with `shiftBusinessDate()`.
  - [EmployeeCommissionReportScreen.tsx](file:///d:/project-new/AutoERP/resources/js/modules/reporting/pages/EmployeeCommissionReportScreen.tsx): Replaced module-level static date with dynamic `createDefaultFilters()`.
  - [BudgetPage.tsx](file:///d:/project-new/AutoERP/resources/js/modules/finance/pages/BudgetPage.tsx): Replaced static date initialization with `initialBudgetPayload()`.
  - [StockCardPage.tsx](file:///d:/project-new/AutoERP/resources/js/modules/inventory/pages/StockCardPage.tsx) & [StockBalanceReportPage.tsx](file:///d:/project-new/AutoERP/resources/js/modules/reporting/pages/StockBalanceReportPage.tsx): Replaced `.toLocaleString()` with `formatBusinessDateTime()`.
  - [WhatsAppVerificationPanel.tsx](file:///d:/project-new/AutoERP/resources/js/shared/components/WhatsAppVerificationPanel.tsx): Replaced `.toLocaleString()` with `formatBusinessDateTime()`.
  - [tenantPresentation.ts](file:///d:/project-new/AutoERP/resources/js/modules/tenant/tenantPresentation.ts): Delegated `formatTenantDateTime` and `formatTenantDate` to `businessDate.ts`.
  - [AuditLogDetailPage.tsx](file:///d:/project-new/AutoERP/resources/js/modules/audit/AuditLogDetailPage.tsx) & [AuditLogListPage.tsx](file:///d:/project-new/AutoERP/resources/js/modules/audit/AuditLogListPage.tsx): Standardized on `formatBusinessDateTimeWithSeconds()`.
  - [platformAdministrationPresentation.ts](file:///d:/project-new/AutoERP/resources/js/modules/platform-administration/platformAdministrationPresentation.ts): Standardized on `formatBusinessDateTimeWithSeconds()`.
  - [tenantSubscriptionRules.ts](file:///d:/project-new/AutoERP/resources/js/modules/tenant/components/tenantSubscriptionRules.ts): Standardized on `businessDateTimeInputValue()`.
  - [DashboardPage.tsx](file:///d:/project-new/AutoERP/resources/js/modules/dashboard/DashboardPage.tsx): Replaced browser `shiftDate` with `shiftBusinessDate()`.

---

## 3. Verification & Automated Test Evidence

### 3.1 Backend Quality Gates
- **Laravel Pint (`php vendor/bin/pint --dirty --test`):** Passed with 0 violations.
- **Architectural Dependency Tests (`php artisan test --filter CoreModuleArchitectureTest`):**
  - `production module dependency graph is acyclic`: Passed (0 cyclic components).
  - All 6 architectural checks passed (66 assertions).
- **Baseline Migration Tests:**
  - `ModuleMigrationBaselineTest`: Passed (1 test, 3 assertions).
  - `HistoricalRetentionMigrationTest`: Passed (1 test, 22 assertions).
- **Targeted Unit & Feature Tests:**
  - `TimezoneResolverTest`: Passed (6 tests, 15 assertions).
  - `UpcomingServicesReportTest`: Passed (7 tests, 70 assertions).
  - `PaymentCashReceiptDatePolicyTest`: Passed (4 tests, 11 assertions).
  - `InvoicePrintServiceTest`: Passed (14 tests, 149 assertions).
  - `AuditQueryFactoryTest`: Passed (1 test, 3 assertions).
- **Full Backend Test Suite (`php artisan test`):**
  - **1,370 passed / 1,370 tests (100%)**, **14,235 assertions**, 0 failures.

### 3.2 Frontend Quality Gates
- **TypeScript Static Verification (`npx tsc --noEmit`):**
  - Passed with **0 errors** across all 922+ source files.
- **ESLint Code Quality (`npx eslint ...` on modified files):**
  - Passed with **0 warnings and 0 errors**.
- **Vitest Test Suite (`npx vitest run`):**
  - **176 test files passed (176)**.
  - **736 tests passed (736)**.
  - Duration: 300.92s.

---

## Author & Maintainer

- **Lead Architect & Developer:** Kasun Vimarshana
- **Email:** [kasunvmail@gmail.com](mailto:kasunvmail@gmail.com)
- **Phone:** [+94713525253](tel:+94713525253)
