# Vehicle Rental: End-to-End Screenshots Audit & Dynamic Timezone Verification

**Date:** 2026-11-10  
**Modules:** `VehicleRental`, `ReferenceData`, `Shared`  
**Scope:** In-depth end-to-end audit and root cause synthesis across the three attached production operational screenshots (vehicle assignment owner lookup, future running chart validation, and customer custody boundary enforcement) along with full dynamic timezone verification.

---

## 1. Problem Statement & Root Cause Synthesis Across Screenshots

The three submitted screenshots illustrate key milestones and edge cases across the customer vehicle assignment and usage lifecycle:

### Screenshot 1 (`media_1794244640057_2de2b379.png`): Vehicle Assignment & Owner Agreement Lookup
- **Screen:** `Customer Agreements` → `Assign a vehicle to VRC-2026-00001`
- **Symptom:** Selecting vehicle `CCC3945` with planned handover `11/09/2026 12:00:00 AM` resulted in an empty Owner Agreement dropdown and validation error: `Select a valid owner agreement from the list.`
- **Root Cause:**
  1. When planned return is omitted (optional open-ended rental), backend `OwnerSourceService` previously required any covering owner agreement to extend across the entire multi-year customer agreement period (`ends_on`), failing valid owner agreements that covered the handover date.
  2. For fleet-owned vehicles, operators had not toggled the "Company-owned vehicle" checkbox, and no contextual guidance explained why zero owner agreements were returned.
- **Resolution:**
  - `OwnerSourceService.php` checks coverage against the handover date when planned return is open (`$coverageUntil = $coverageFrom`).
  - `VehicleUseEditor.tsx` automatically queries active owner supplies; if none exist on the handover date and the vehicle is not flagged as company-owned, it renders contextual guidance explaining the "Company-owned vehicle" toggle. If exactly one owner agreement covers the vehicle, it auto-selects it, and provides an "Auto-select owner agreement" button.

### Screenshot 2 (`media_1794249515714_515e11cc.png`): Future Usage End Rejection
- **Screen:** `Customer Agreements` → `Running Charts` → `Record Running Chart`
- **Symptom:** Usage start `11/09/2026 11:51:00 PM` and usage end `11/10/2026 11:58:00 PM` failed with:  
  `ends_at: Record completed usage ending after its start, not in the future.`
- **Root Cause:**
  1. Completed usage records represent historical physical trips. Entering an end timestamp 24 hours into the future (or next calendar day) violated the domain rule that completed usage cannot end in the future.
  2. Redundant, rigid client-side time checks previously evaluated user input before server tolerance could apply.
- **Resolution:**
  - `RunningChartValidation.php` enforces the business rule with a 1-minute server clock skew buffer (`addMinute()`) to tolerate real-world client-server drift.
  - Added a "Use current time" shortcut in `RunningChartEditor.tsx` that sets the exact current time in the workspace timezone.
  - Removed duplicate client-side barriers, establishing backend validation as the single authoritative source of truth.

### Screenshot 3 (`media_1794252420528_675a6875.png`): Custody Boundary Sub-Minute Precision
- **Screen:** `Customer Agreements` → `Running Charts · CCC3945` → `Record Running Chart`
- **Symptom:** Vehicle handed over at `Nov 9, 2026, 11:51 PM`. Usage start `11/09/2026 11:51:00 PM` and usage end `11/09/2026 11:59:06 PM` failed with:  
  `period: Usage must fall within actual customer custody.`
- **Root Cause:**
  1. Handover was captured with non-zero seconds (e.g. `11:51:38 PM` / `18:21:38 UTC`), but standard datetime pickers zeroed seconds to `:00` (`11:51:00 PM` / `18:21:00 UTC`).
  2. The server strictly compared `$usageStart < $handover` (`18:21:00 < 18:21:38`), rejecting the usage by 38 seconds within the identical minute.
- **Resolution:**
  - `RunningChartService::assertCoverage` converts all instants to `CarbonImmutable` UTC and aligns `$start` to `$use->handed_over_at` when usage start falls within a 1-minute sub-minute handover buffer.
  - `RunningChartEditor.tsx` displays seconds in the custody hint (`formatBusinessDateTimeWithSeconds`) and provides a "Use custody start time" button preserving full second precision.

---

## 2. Dynamic Timezone Handling Verification

1. **Workspace Timezone Single Source of Truth**:
   - Timezone configuration is dynamically resolved via `ConfigurationKey::WORKSPACE_TIMEZONE` (`localization.timezone`) in `RentalCalendar.php` and injected into the user session via `TenantAuthProfileBuilder.php`.
   - UI labels dynamically show the active workspace timezone via `operationalTimeZoneLabel()` / `businessTimeZoneLabel()`.
   - Replaced static example text in `ReferenceDataPanel.tsx` with dynamic `businessTimeZoneLabel()`.
2. **Zero Hardcoded Offsets**:
   - Client-side `businessTimestampWithOffset` calculates dynamic UTC offsets using `Intl.DateTimeFormat` across transition passes, supporting any positive, negative, or fractional offset worldwide without hardcoding `Asia/Colombo` or `+05:30`.

---

## 3. Automated Verification

- **PHPUnit Backend Tests (`Modules\VehicleRental\Tests`)**:
  - **230 passed (1,945 assertions)** with 0 failures.
- **Frontend Vitest Suites (`resources/js/modules/vehicle-rental`)**:
  - **29 passed test files (131 tests)** with 0 failures.
- **Static Analysis & Linting**:
  - `npm run typecheck`: **0 errors**.
  - `npm run lint`: **0 errors**.
