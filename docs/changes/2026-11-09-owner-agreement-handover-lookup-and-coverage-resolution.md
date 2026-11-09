# Vehicle Rental: Owner Agreement Handover Selection, Lookup, and Coverage Resolution

**Date:** 2026-11-09  
**Modules:** `VehicleRental`, `Auth`, `Configuration`  
**Scope:** Customer Agreements → Assigned Vehicles & Handover: Owner agreement data-fetching, vehicle mapping, timezone alignment, and coverage period verification.

---

## 1. Problem Statement & Root Cause Analysis

Operators assigning a vehicle (e.g. `CCC3945`) to a Customer Agreement (e.g. `VRC-2026-00001`, duration 1 year from `2026-11-09` to `2027-11-09`) with an optional blank Planned Return encountered an empty Owner Agreement dropdown and an immediate validation error (`"Select a valid owner agreement from the list."`). The Auto-select Owner Agreement button also failed to populate the field.

### Identified Root Causes:
1. **Flawed Coverage Bound Assumption in `OwnerSourceService`**:
   When `ends_at` was empty (planned return left blank), `OwnerSourceService` queried with `$coverageUntil = $customerAgreement->ends_on` (`2027-11-09`). It required `ends_on >= $coverageUntil`, completely discarding any active owner agreement (such as `VRO-2026-00001`, which ends on `2027-07-09`) whose lease ended before the customer agreement ended—even though the vehicle was validly leased and active on the handover date (`2026-11-09`).
2. **Flawed End-Date Overwrite in `VehicleUseService`**:
   Similarly, when planning vehicle custody (`VehicleUseService::plan()`), if `$end` was null and `$customer->ends_on` was non-null, `$ownerEffectiveEnd` was hardcoded to `$customer->ends_on`. This forced `assertCoverage()` to reject any owner agreement expiring prior to the customer contract, preventing operators from assigning vehicles that would later be swapped, replaced, or have their owner lease renewed.
3. **Workspace Timezone Misalignment between Client & Server**:
   `TenantAuthProfileBuilder` previously did not expose `timezone` on the session's `tenant` or `organization_unit` profiles, causing the frontend `AuthProvider` to fall back to the browser's local timezone (`Asia/Colombo`, UTC+5:30), while the backend defaulted to UTC when unconfigured. Midnight handovers (`00:00:00`) in Sri Lanka translated to `18:30:00 UTC` on the previous day (`2026-11-08`), which failed `starts_on <= coverageFrom` against agreements starting on `2026-11-09`.

---

## 2. Changes Implemented

1. **`app/Modules/VehicleRental/Services/OwnerSourceService.php`**:
   - When `ends_at` is omitted / blank and `customer_agreement_id` is provided, `$coverageUntil` is set to `$coverageFrom` (the handover date).
   - Any active owner agreement for the vehicle that covers the handover date (`starts_on <= coverageFrom` and `ends_on is null or ends_on >= coverageFrom`) is returned and selectable.
2. **`app/Modules/VehicleRental/Services/VehicleUseService.php`**:
   - In `plan()`, removed the hardcoded assumption that an owner agreement must cover through `$customer->ends_on` when Planned Return is blank.
   - If `$end !== null`, the owner agreement must cover until `$end`.
   - If `$end === null` and `$customer->ends_on === null`, requires open-ended vehicle supply (`$owner->ends_on === null`).
   - If `$end === null` and `$customer->ends_on !== null`, verifies that the owner agreement is Active and covers the handover date (`starts_on <= start` and `ends_on is null or ends_on >= start`).
3. **`app/Modules/Auth/Services/TenantAuthProfileBuilder.php`**:
   - Injected `ConfigurationResolverInterface` and populated `timezone` in both `$tenant` and `$organization_unit` auth profiles using `ConfigurationKey::WORKSPACE_TIMEZONE`.
   - Synchronizes `AuthProvider` in the frontend with the exact tenant/organization commercial calendar configured on the server.
4. **`resources/js/modules/vehicle-rental/VehicleUseEditor.tsx`**:
   - Updated the assignment prompt text to clarify that when planned return is blank, vehicle assignment continues under the covering owner agreement until returned or replaced.
   - Verified that auto-selection (`useEffect` on vehicle select) and the "Auto-select owner agreement" action button reliably select the covering owner agreement.
5. **Tests**:
   - Updated `OwnerSourceTest.php` to verify owner agreements covering handover dates are returned when `ends_at` is empty, and filtered when an explicit return date is requested.
   - Updated `VehicleUseTest.php` to verify vehicle assignment succeeds with covering owner agreement when planned return is blank, and rejects when an explicit planned return exceeds the owner agreement duration.

---

## 3. Verification

- **PHPUnit Backend Tests**:
  - `Modules\VehicleRental\Tests`: **227 passed (1,922 assertions)** with 0 failures.
- **Frontend Vitest Suites**:
  - `VehicleUseEditor.test.tsx`: **9 passed (9 tests)** with 0 failures.
- **Static Analysis & Linting**:
  - `npm run typecheck`: **0 errors**.
  - `npm run lint`: **0 errors, 0 warnings**.
