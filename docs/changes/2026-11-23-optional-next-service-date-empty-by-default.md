# Vehicle Service Optional & Empty-by-Default Next Service Date

**Date:** 2026-11-23  
**Module:** Vehicle Service (`app/Modules/VehicleService`, `resources/js/modules/vehicle-service`)  
**Author & Maintainer:** Kasun Vimarshana (`kasunvmail@gmail.com`)  

---

## 1. Context & Objectives

Previously, when creating a new Vehicle Service Job, the `Next Service Date` was automatically populated to one calendar month after the service job date (`CarbonImmutable::parse($data->jobDate)->addMonthNoOverflow()->toDateString()` in the backend, and `defaultNextServiceDate(initialJobDate)` in the frontend React form).

The requirement was to:
1. Make the `Next Service Date` field completely optional.
2. Leave it empty by default upon creation (`null` on backend, `""` on frontend).
3. Allow users to manually set a next service date or leave it blank based on workshop requirements.
4. Prevent changing the service `job_date` from auto-populating or overriding the blank `Next Service Date`.
5. Preserve the ability to update, modify, or clear existing dates on edit.
6. Clean up redundant helper abstractions (`defaultNextServiceDate`, `nextServiceDateTouched`) per Core Axiom 3 (no dead code).

---

## 2. Modified Files Summary

| File | Changes Made |
|---|---|
| [`.agents/skills/autoerp-engineering/SKILL.md`](file:///d:/project-new/AutoERP/.agents/skills/autoerp-engineering/SKILL.md) | Codified the 8 Prohibited Architectural Anti-Patterns, Database Key Naming Standard (`_fk`, `_uk`, `_ix`, `_pk`, `_ck` with 60-char ceiling), Audit Precedence Hierarchy, and Quality Gate Matrix. |
| [`app/Modules/VehicleService/Services/VehicleServiceJobService.php`](file:///d:/project-new/AutoERP/app/Modules/VehicleService/Services/VehicleServiceJobService.php) | Updated `resolveNextServiceDate()` so that omitting or passing `null` on create resolves directly to `null` instead of calculating 1 month forward. Removed unused `CarbonImmutable` import. |
| [`app/Modules/VehicleService/Tests/VehicleServiceEngineTest.php`](file:///d:/project-new/AutoERP/app/Modules/VehicleService/Tests/VehicleServiceEngineTest.php) | Renamed test to `test_next_service_date_defaults_to_null_when_omitted()` and asserted `assertNull($job->next_service_date)` and `assertNull($resource['next_service_date'])`. |
| [`resources/js/modules/vehicle-service/components/VehicleServiceJobForm.tsx`](file:///d:/project-new/AutoERP/resources/js/modules/vehicle-service/components/VehicleServiceJobForm.tsx) | Initialized `next_service_date` to `''` on create; removed auto-population logic when changing `job_date`; removed dead `defaultNextServiceDate` helper and `nextServiceDateTouched` state; updated field hint to `"Optional: leave blank or set next scheduled service date"`. |
| [`resources/js/modules/vehicle-service/components/VehicleServiceJobForm.test.tsx`](file:///d:/project-new/AutoERP/resources/js/modules/vehicle-service/components/VehicleServiceJobForm.test.tsx) | Updated component tests to assert that `Next Service Date` starts empty, remains empty on `job_date` change, submits `null` when untouched or cleared, and submits custom date when manually filled. |

---

## 3. Verification & Quality Gate Results

All gates passed with 0 errors:

1. **TypeScript Typecheck:**
   ```bash
   npm run typecheck
   ```
   *Result:* Passed with 0 errors (`tsc --noEmit` exited with code 0).

2. **Frontend Linting:**
   ```bash
   npm run lint
   ```
   *Result:* Passed with 0 errors across `resources/js/*`.

3. **Backend Code Style (Pint PSR-12):**
   ```bash
   php vendor/bin/pint --test app/Modules/VehicleService/Services/VehicleServiceJobService.php app/Modules/VehicleService/Tests/VehicleServiceEngineTest.php
   ```
   *Result:* Passed (`{"tool":"pint","result":"passed"}`).

4. **Vehicle Service Backend Test Suite:**
   ```bash
   php artisan test --filter VehicleServiceEngineTest
   ```
   *Result:* 76 passed (535 assertions).

5. **Reporting Service Integration Tests:**
   ```bash
   php artisan test app/Modules/Reporting/Tests/UpcomingServicesReportTest.php app/Modules/Reporting/Tests/ServiceJobsByJobTypeReportTest.php
   ```
   *Result:* 14 passed (111 assertions).

6. **Vehicle Service Frontend Vitest Suite:**
   ```bash
   npx vitest run resources/js/modules/vehicle-service/
   ```
   *Result:* 20 passed / 20 test files (108 tests passed).

7. **Database Migration Baseline Integrity:**
   ```bash
   php artisan test --filter ModuleMigrationBaselineTest
   ```
   *Result:* 1 passed (3 assertions).

8. **Historical Retention Integrity:**
   ```bash
   php artisan test --filter HistoricalRetentionMigrationTest
   ```
   *Result:* 1 passed (22 assertions).

---

## Author & Maintainer

- **Lead Architect & Developer:** Kasun Vimarshana
- **Email:** [kasunvmail@gmail.com](mailto:kasunvmail@gmail.com)
- **Phone:** [+94713525253](tel:+94713525253)
- **Repository:** [kasunvimarshana/AutoERP](https://github.com/kasunvimarshana/AutoERP)
