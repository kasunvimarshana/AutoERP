# Extensible Biometric Attendance Device Integration Architecture & Implementation

**Date:** 2026-11-25  
**Author:** Kasun Vimarshana (<kasunvmail@gmail.com>, +94713525253)  
**Modules Affected:** `app/Modules/Hr`, `resources/js/modules/hr`, `resources/js/app`  

---

## 1. Context & Business Need

Modern enterprise operations require automated, reliable ingestion of physical attendance punches from heterogeneous biometric hardware (fingerprint terminals, facial recognition kiosks, RFID turnstiles) across multiple branches, warehouses, and remote facilities.

Prior to this implementation, attendance was limited to manual entry or web-based clocking. Direct integration with physical devices presented several industry-standard hardware challenges:
1. **Network Topologies & Firewalls**: On-premise devices frequently reside behind corporate NAT firewalls or cellular 4G routers where cloud servers cannot initiate inbound TCP socket connections.
2. **Clock Drift**: Device RTC hardware drifts due to failing backup batteries or lack of NTP, distorting civil attendance calculations.
3. **Hardware Interruptions & Buffer Surges**: Offline devices accumulate tens of thousands of punches in flash memory; on network reconnection, these arrive in massive concurrent bursts.
4. **Deduplication & Multi-Punching**: Employees frequently punch twice within seconds, and network retries cause identical log batches to be pushed multiple times.
5. **Heterogeneous Vendor Protocols**: Organizations utilize mixed device fleets (ZKTeco, Hikvision, Dahua, Suprema, generic IoT gateways) with incompatible wire formats.

To resolve these challenges without polluting core HR business logic, this architecture delivers an extensible, contract-based adapter pattern supporting both **Inbound Push** (Device -> AutoERP) and **Outbound Pull** (AutoERP -> Device Socket/HTTP) communication with automated cryptographic deduplication and dual-timestamp synchronization.

---

## 2. Architectural Design & Axioms Compliance

- **Axiom 1 & 5 (Understand First & Backend Authority)**:
  - `AttendanceCalculationEngine` remains the single authoritative source of truth for daily attendance records, working hours, break deductions, and overtime.
  - Raw hardware punches are converted into normalized `PunchData` DTOs with `PunchSource::BiometricDevice`.
- **Axiom 4 (Module Ownership & Clear Boundaries)**:
  - All biometric abstractions, drivers, staging tables, and webhook receivers reside strictly within `app/Modules/Hr/Biometric/`. No inter-module bleed or shadow structures exist.
- **Axiom 6 (Dual-Timestamp Architecture & Concurrency)**:
  - Raw punch staging maintains a strict separation between physical server ingestion facts (`received_at`, `created_at`) and civil operational punch times (`punch_time`).
  - Idempotent deduplication is enforced via SHA-256 fingerprint hashing over `tenant_id + device_id + biometric_user_pin + punch_time + punch_state`. Duplicate fingerprints are acknowledged to hardware to stop retries but quarantined as `duplicate` in staging.
  - Concurrency conflicts on device configuration mutations are prevented via `row_version` optimistic locking.
- **Axiom 7 (Database Architecture & Explicit Migrations)**:
  - Exactly one table per migration file.
  - No alteration patches (`Schema::table()`), dynamic loops, or runtime conditionals.
  - All relationship references from raw punch audit tables enforce `restrictOnDelete()`.
- **Axiom 9 (Zero Magic Values)**:
  - PHP Backed Enums: `BiometricProtocol`, `BiometricDeviceStatus`, `BiometricPunchState`, `BiometricVerifyMethod`, `RawPunchSyncStatus`.
  - TypeScript union types on the frontend.
- **Axiom 10 (Zero Raw Database IDs in UI)**:
  - Relational inputs use controlled selection components (`EmployeeLookupSelect`, `HrBiometricDeviceSelect`).
  - Tables format human-readable device codes, names, protocols, and employee names.
- **Axiom 12 (Shared Project Memory)**:
  - Documented here in `/docs/changes`.

---

## 3. Database Schema Migrations

1. `2026_06_12_130036_create_hr_biometric_devices_table.php`:
   - Table `hr_biometric_devices`: stores device code, name, protocol, IP, port, serial number, comm key, auth token, status (`active`, `inactive`, `offline`, `error`), enabled toggle, auto-map users toggle, last ping/sync timestamps, total punches synced counter, JSON settings, and optimistic `row_version`.
2. `2026_06_12_130037_create_hr_biometric_device_users_table.php`:
   - Table `hr_biometric_device_users`: maps device biometric user IDs (PINs) and RFID card numbers to internal `HrEmployee` records with composite unique keys.
3. `2026_06_12_130038_create_hr_biometric_raw_punches_table.php`:
   - Table `hr_biometric_raw_punches`: append-only staging and audit table recording raw physical punches, reported punch times, server received timestamps, punch direction (`check_in`, `check_out`, `break_out`, `break_in`, `auto`), verification method (`fingerprint`, `face`, `card`, `password`), unique SHA-256 idempotency hash, sync status (`pending`, `processed`, `duplicate`, `unmapped_user`, `failed`), and error logs.

---

## 4. Hardware Protocols & Adapter Implementations

- **`BiometricDeviceAdapterInterface`**: Standard contract defining `ping()`, `syncTime()`, `fetchLogs()`, `clearLogs()`, `parseIncomingPush()`, and `generatePushResponse()`.
- **`ZkTecoPushAdapter`**: Full implementation of the ZKTeco ADMS / iClock Push SDK protocol:
  - Supports `/iclock/cdata` GET handshake delivering server options (`Stamp`, `OpStamp`, `TimeZone`, `Realtime=1`, `TransInterval=1`).
  - Supports `/iclock/cdata` POST parsing tab-delimited and comma-delimited `ATTLOG` stamp streams with multi-state codes (Check-In, Check-Out, Breaks, Overtime) and verification modalities (Face, Fingerprint, RFID Card, Password).
  - Supports `/iclock/getrequest` command polling queue (time sync, clear log) and `/iclock/devicecmd` execution receipts.
- **`ZkTecoSocketAdapter`**: Pure PHP socket driver for LAN/VPN direct TCP polling over port 4370.
- **`HikvisionIsapiAdapter`**: REST client for Hikvision access control terminals querying `/ISAPI/AccessControl/AcsEvent` via Digest authentication.
- **`DahuaCgiAdapter`**: HTTP RPC client for Dahua terminals querying `recordFinder.cgi`.
- **`GenericWebhookAdapter`**: Universal JSON push receiver with HMAC-SHA256 signature verification (`X-Biometric-Signature`) and Bearer token security for modern edge IoT devices, Android kiosks, and third-party cloud platforms (Suprema, Kisi, Brivo).
- **`UsbImportAdapter`**: Offline parser for branch locations without internet connectivity, supporting direct import of `1_attlog.dat` flash drive dumps and CSV files.
- **`BiometricAdapterManager`**: Extensible driver registry and dependency injection factory.

---

## 5. Ingestion Pipeline & Auto-Mapping

- **`BiometricLogIngestionService`**:
  - Validates and deduplicates punches using SHA-256 hash.
  - Automatically matches incoming biometric PINs to `HrEmployee` records via explicit `HrBiometricDeviceUser` mappings or by employee code (e.g. PIN `101` matching `EMP-0101` or `101`).
  - Resolves punch direction: explicit state keys are honored; ambiguous or "auto" punches evaluate existing punches on that civil day to alternate between Check-In and Check-Out.
  - Calls `AttendanceCalculationEngine::recordPunch()` to recalculate daily hours, breaks, and overtime.
  - Quarantines unmapped PINs with `unmapped_user` status without dropping data.
- **Reprocessing Engine**:
  - When an administrator links an employee to a previously unmapped biometric PIN, `reprocessUnmappedPunches()` automatically converts historical quarantined punches into verified attendance records.

---

## 6. Frontend Management UI

- `resources/js/modules/hr/BiometricDeviceManagementPage.tsx`:
  - Complete control center with real-time metrics (Total Devices, Online, Offline/Errors, Total Punches Synced).
  - Protocol and status filters with search bar.
  - Hardware device table with live status badges, last sync/heartbeat timestamps, and action buttons.
  - "Add / Edit Device" modal with live protocol configuration instructions.
  - "Diagnostics & Ping" modal reporting latency, firmware version, and device clock drift.
  - "User Mappings" modal managing PIN-to-employee links with `EmployeeLookupSelect` and "Reprocess Unmapped Punches" action.
  - "Raw Logs" modal displaying the immutable audit trail of raw punches, verify modalities, and error diagnostics.
  - "USB File Upload" modal for offline flash drive imports.
- `resources/js/modules/hr/components/HrBiometricDeviceSelect.tsx`:
  - Controlled lookup dropdown formatting human-readable codes, names, and protocols with zero raw IDs exposed (**Axiom 10**).

---

## 7. Modified & Created Files Summary

| File | Status | Description |
|---|---|---|
| `app/Modules/Hr/Database/Migrations/2026_06_12_130036_create_hr_biometric_devices_table.php` | Created | Biometric devices table migration |
| `app/Modules/Hr/Database/Migrations/2026_06_12_130037_create_hr_biometric_device_users_table.php` | Created | Biometric user mappings table migration |
| `app/Modules/Hr/Database/Migrations/2026_06_12_130038_create_hr_biometric_raw_punches_table.php` | Created | Append-only raw punch audit table migration |
| `app/Modules/Hr/Enums/BiometricProtocol.php` | Created | Supported protocol enum with human-readable labels |
| `app/Modules/Hr/Enums/BiometricDeviceStatus.php` | Created | Device status enum (Active, Inactive, Offline, Error) |
| `app/Modules/Hr/Enums/BiometricPunchState.php` | Created | Punch state enum (CheckIn, CheckOut, Break, Auto) |
| `app/Modules/Hr/Enums/BiometricVerifyMethod.php` | Created | Verification method enum (Fingerprint, Face, Card, etc.) |
| `app/Modules/Hr/Enums/RawPunchSyncStatus.php` | Created | Sync status enum (Pending, Processed, Duplicate, Unmapped, Failed) |
| `app/Modules/Hr/DTOs/BiometricDeviceData.php` | Created | Strongly-typed device configuration DTO |
| `app/Modules/Hr/DTOs/RawBiometricPunchData.php` | Created | Normalized punch data DTO with SHA-256 fingerprint generator |
| `app/Modules/Hr/DTOs/DeviceHealthResult.php` | Created | Diagnostic ping result DTO |
| `app/Modules/Hr/DTOs/DeviceLogSyncResult.php` | Created | Cursor-based sync result DTO |
| `app/Modules/Hr/Models/HrBiometricDevice.php` | Created | Device model with tenant scoping and relations |
| `app/Modules/Hr/Models/HrBiometricDeviceUser.php` | Created | User mapping model |
| `app/Modules/Hr/Models/HrBiometricRawPunch.php` | Created | Raw punch audit model |
| `app/Modules/Hr/Biometric/Contracts/BiometricDeviceAdapterInterface.php` | Created | Standard biometric device adapter interface |
| `app/Modules/Hr/Biometric/Adapters/ZkTecoPushAdapter.php` | Created | ZKTeco ADMS / iClock Push SDK driver |
| `app/Modules/Hr/Biometric/Adapters/ZkTecoSocketAdapter.php` | Created | ZKTeco TCP socket 4370 driver |
| `app/Modules/Hr/Biometric/Adapters/HikvisionIsapiAdapter.php` | Created | Hikvision ISAPI REST client driver |
| `app/Modules/Hr/Biometric/Adapters/DahuaCgiAdapter.php` | Created | Dahua CGI RPC client driver |
| `app/Modules/Hr/Biometric/Adapters/GenericWebhookAdapter.php` | Created | Generic HMAC/Bearer JSON push webhook driver |
| `app/Modules/Hr/Biometric/Adapters/UsbImportAdapter.php` | Created | USB flash drive / CSV offline import driver |
| `app/Modules/Hr/Biometric/Services/BiometricAdapterManager.php` | Created | Protocol adapter registry and resolver |
| `app/Modules/Hr/Biometric/Services/BiometricLogIngestionService.php` | Created | Punch deduplication, employee resolution & ingestion service |
| `app/Modules/Hr/Biometric/Services/BiometricDeviceService.php` | Created | Device lifecycle, diagnostics & user mapping service |
| `app/Modules/Hr/Biometric/Jobs/SyncBiometricDeviceJob.php` | Created | Resilient queued sync job |
| `app/Modules/Hr/Http/Controllers/HrBiometricDeviceController.php` | Created | REST API controller for device management and logs |
| `app/Modules/Hr/Http/Controllers/HrBiometricPushReceiverController.php` | Created | Public endpoints for hardware push protocols |
| `app/Modules/Hr/Services/HrAuthorizationService.php` | Modified | Added biometric permissions to tenant catalog |
| `app/Modules/Hr/Routes/api.php` | Modified | Registered authenticated and public push routes |
| `resources/js/shared/api/endpoints.ts` | Modified | Added `hrBiometricDevices` endpoint |
| `resources/js/modules/hr/hrTypes.ts` | Modified | Added biometric TypeScript interfaces and union types |
| `resources/js/modules/hr/hrPermissions.ts` | Modified | Added biometric permissions constants |
| `resources/js/modules/hr/hrApi.ts` | Modified | Added API client functions for biometric devices and logs |
| `resources/js/modules/hr/components/HrBiometricDeviceSelect.tsx` | Created | Controlled lookup dropdown component |
| `resources/js/modules/hr/BiometricDeviceManagementPage.tsx` | Created | Full frontend management dashboard |
| `resources/js/app/router.tsx` | Modified | Registered `/hr/biometric-devices` route |
| `resources/js/app/access/hrRouteEntitlements.ts` | Modified | Added route entitlement rules for HR and Payroll routes |
| `tests/Unit/Hr/BiometricAdapterTest.php` | Created | Unit tests for all protocol adapters and HMAC validation |
| `tests/Feature/Hr/BiometricIntegrationTest.php` | Created | End-to-end integration tests for ingestion, deduplication & endpoints |

---

## 8. Verification & Quality Gate Results

| Quality Gate | Command | Result |
|---|---|---|
| **TypeScript Typecheck** | `npm run typecheck` | **0 errors** (strict mode passed) |
| **ESLint Analysis** | `npx eslint resources/js/modules/hr/...` | **0 errors** |
| **Backend Code Style (Pint)** | `php vendor/bin/pint --test app/Modules/Hr tests/Unit/Hr tests/Feature/Hr/BiometricIntegrationTest.php` | **0 violations** (100% PSR-12 compliant) |
| **Migration Baseline Integrity** | `php artisan test --filter ModuleMigrationBaselineTest` | **Passed (3 assertions)**: 1:1 table migration mapping, 0 alteration patches |
| **Historical Retention Integrity** | `php artisan test --filter HistoricalRetentionMigrationTest` | **Passed (22 assertions)**: 100% `restrictOnDelete()` compliance |
| **Frontend Route Entitlements** | `npx vitest run resources/js/app/access/routeEntitlements.test.ts` | **Passed (3 tests)** |
| **Frontend HR Suite** | `npx vitest run resources/js/modules/hr/` | **Passed (4 tests)** |
| **Backend Biometric Tests** | `php artisan test --filter Biometric` | **Passed: 12 tests (79 assertions)** |
| **Full HR Test Suite** | `php artisan test --filter Hr` | **Passed: 52 tests (754 assertions)** |

---

## Author & Maintainer

- **Lead Architect & Developer:** Kasun Vimarshana
- **Email:** [kasunvmail@gmail.com](mailto:kasunvmail@gmail.com)
- **Phone:** [+94713525253](tel:+94713525253)
- **Repository:** [kasunvmail@gmail.com / AutoERP](https://github.com/kasunvimarshana/AutoERP)
