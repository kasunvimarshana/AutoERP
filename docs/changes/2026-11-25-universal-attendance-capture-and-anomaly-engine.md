# Universal Attendance Capture Architecture & Anomaly Detection Engine

## Context & Objectives
The enterprise system requires a comprehensive, extensible, and vendor-agnostic attendance capture and regularization ecosystem. Attendance capture must support every physical and digital capture method through a consistent adapter and service architecture:
1. **Biometric Hardware Devices**: TCP/IP, Push (ADMS), ISAPI, CGI, and USB imports for ZKTeco, Hikvision, Dahua, Suprema, and generic webhook devices.
2. **Contactless Smart Badges & Credentials (RFID / NFC / Barcode)**: Mifare, Desfire, 125kHz EM-Marin, NFC smartphone emulation, and 1D/2D barcodes with multi-credential binding per employee, credential lifecycle tracking (`active`, `suspended`, `lost`, `revoked`), and deduplication.
3. **Dynamic / Rotating QR Codes**: Stationary kiosk / tablet terminal mode displaying dynamic TOTP tokens signed with HMAC-SHA256, changing every 20 seconds to eliminate screenshot sharing, photo copying, and buddy punching.
4. **Mobile & Web Clock-In with Geofencing**: Haversine distance GPS verification against branch geofence radiuses, office IP subnet whitelisting, device hardware fingerprinting, and optional camera selfie audit attachments.
5. **Two-Phase Batch File Imports**: CSV/Excel bulk upload pipeline providing pre-validation dry runs (reporting row-level syntax, employee code mapping, and date/time errors) followed by atomic database batch commits.
6. **Automated Anomaly & Missing-Punch Detection Engine**: Automated scan detecting unclosed shifts (missing out, missing in), unpaired breaks, and excessive duration (> 16 hours), flagging records with specific anomaly types and routing them to an Anomaly Desk with one-click administrative quick resolution.
7. **Complete HR & Payroll Integration**: All capture streams normalize into `HrAttendancePunch`, trigger `AttendanceCalculationEngine` to compute worked hours, overtime, and unpaid breaks, and seamlessly propagate into `PayrollCalculationEngine` for proration and deductions.

---

## Modified & Created Files Summary

| File | Change Type | Purpose |
|---|---|---|
| `app/Modules/Hr/Enums/CaptureMethod.php` | Created | Bounded PHP enum for capture methods (`biometric`, `rfid_card`, `nfc_tag`, `barcode`, `totp_qr`, `mobile_gps`, `web_portal`, `manual_admin`, `file_upload`). |
| `app/Modules/Hr/Enums/AnomalyType.php` | Created | Bounded PHP enum for anomaly classifications (`missing_out`, `missing_in`, `unpaired_break`, `excessive_hours`, `short_shift`, `geofence_violation`). |
| `app/Modules/Hr/Enums/CredentialType.php` | Created | Bounded PHP enum for credential types (`rfid_card`, `nfc_tag`, `barcode`, `mobile_device_uuid`). |
| `app/Modules/Hr/Enums/CredentialStatus.php` | Created | Bounded PHP enum for credential lifecycle status (`active`, `suspended`, `lost`, `revoked`). |
| `app/Modules/Hr/Enums/BatchImportStatus.php` | Created | Bounded PHP enum for batch import status (`pending`, `validated`, `imported`, `failed`). |
| `app/Modules/Hr/Enums/AttendanceStatus.php` | Modified | Added `MissingPunch` and `Anomalous` status cases. |
| `app/Modules/Hr/Database/Migrations/2026_06_12_130024_create_hr_attendance_records_table.php` | Modified | Added `anomaly_type` (string 32 nullable), `requires_regularization` (bool), and indexed `hr_att_records_req_reg_ix`. |
| `app/Modules/Hr/Database/Migrations/2026_06_12_130025_create_hr_attendance_punches_table.php` | Modified | Added `capture_method`, `credential_id`, `accuracy_meters`, `device_fingerprint`, `photo_path`, `geofence_id`, and `idempotency_hash`. |
| `app/Modules/Hr/Database/Migrations/2026_06_12_130039_create_hr_attendance_geofences_table.php` | Created | Database migration for branch geofences with GPS coordinates, radiuses, and IP whitelists. |
| `app/Modules/Hr/Database/Migrations/2026_06_12_130040_create_hr_attendance_kiosks_table.php` | Created | Database migration for dynamic QR kiosks with HMAC secret keys and rotation intervals. |
| `app/Modules/Hr/Database/Migrations/2026_06_12_130041_create_hr_employee_attendance_credentials_table.php` | Created | Database migration for employee RFID cards, NFC tags, barcodes, and mobile UUIDs. |
| `app/Modules/Hr/Database/Migrations/2026_06_12_130042_create_hr_attendance_batch_imports_table.php` | Created | Database migration for tracking bulk file upload batches and errors. |
| `app/Modules/Hr/Models/HrAttendanceGeofence.php` | Created | Eloquent model for attendance geofences. |
| `app/Modules/Hr/Models/HrAttendanceKiosk.php` | Created | Eloquent model for attendance kiosk terminals. |
| `app/Modules/Hr/Models/HrEmployeeAttendanceCredential.php` | Created | Eloquent model for employee attendance credentials. |
| `app/Modules/Hr/Models/HrAttendanceBatchImport.php` | Created | Eloquent model for attendance batch imports. |
| `app/Modules/Hr/Models/HrAttendanceRecord.php` | Modified | Casts for `anomaly_type` and `requires_regularization`. |
| `app/Modules/Hr/Models/HrAttendancePunch.php` | Modified | Casts for `capture_method` and relations to credentials and geofences. |
| `app/Modules/Hr/Models/HrEmployee.php` | Modified | Added `attendanceCredentials(): HasMany` relationship. |
| `app/Modules/Hr/DTOs/PunchData.php` | Modified | Extended with capture method, credential ID, accuracy, fingerprint, and idempotency hash. |
| `app/Modules/Hr/Services/Capture/GeofenceVerificationService.php` | Created | Haversine formula distance calculation and IPv4 CIDR subnet matching. |
| `app/Modules/Hr/Services/Capture/DynamicQrSecurityService.php` | Created | TOTP rotating QR token generation with HMAC-SHA256 signature and sliding grace window validation. |
| `app/Modules/Hr/Services/Capture/AttendanceCaptureService.php` | Created | Unified capture service handling Web, Mobile GPS, RFID tap, Barcode, and Kiosk QR scans. |
| `app/Modules/Hr/Services/Capture/BatchAttendanceImportService.php` | Created | Two-phase CSV/Excel import service (dry-run pre-validation + atomic commit). |
| `app/Modules/Hr/Services/AttendanceAnomalyService.php` | Created | Anomaly scan engine for missing punches and administrative quick-resolution. |
| `app/Modules/Hr/Commands/DetectMissingPunchesCommand.php` | Created | Artisan console command `hr:detect-missing-punches`. |
| `app/Modules/Hr/Providers/HrServiceProvider.php` | Modified | Registered `DetectMissingPunchesCommand`. |
| `app/Modules/Hr/Http/Controllers/HrAttendanceCaptureController.php` | Created | Controller for web, mobile, rfid, barcode, kiosk QR token, and batch file import endpoints. |
| `app/Modules/Hr/Http/Controllers/HrAttendanceAnomalyController.php` | Created | Controller for anomaly listing, on-demand scanning, and quick resolution. |
| `app/Modules/Hr/Http/Controllers/HrAttendanceCredentialController.php` | Created | Controller for employee credential CRUD. |
| `app/Modules/Hr/Http/Controllers/HrAttendanceGeofenceController.php` | Created | Controller for geofence and kiosk CRUD. |
| `app/Modules/Hr/Routes/api.php` | Modified | Registered all capture, anomaly, credential, geofence, and kiosk API endpoints. |
| `resources/js/shared/api/endpoints.ts` | Modified | Added frontend endpoints for capture, anomalies, and kiosks. |
| `resources/js/modules/hr/hrTypes.ts` | Modified | Added TypeScript types for capture methods, anomalies, credentials, kiosks, and batch preview. |
| `resources/js/modules/hr/hrApi.ts` | Modified | Added API functions for capture, anomalies, kiosks, batch import, and credentials. |
| `resources/js/modules/hr/AttendanceManagementPage.tsx` | Modified | Enhanced with 5 tabs: Daily Records, Anomaly Desk, Regularizations, Dynamic QR Kiosk, and Batch File Import. |
| `resources/js/modules/hr/components/EmployeeCredentialTab.tsx` | Created | UI tab for viewing, issuing, and revoking RFID badges, NFC tags, barcodes, and mobile UUIDs. |
| `resources/js/modules/hr/EmployeeDetailPage.tsx` | Modified | Added `credentials` tab. |
| `app/Modules/Core/Http/Requests/TenantScopedRequest.php` | Modified | Converted from abstract to concrete class with default `rules(): array` and `userId()` alias to enable route dependency injection across controllers. |
| `tests/Feature/Hr/AttendanceCaptureAndAnomalyTest.php` | Created | Comprehensive automated feature tests for all capture methods, TOTP QR security, HTTP API endpoints, and anomaly detection. |

---

## Verification & Quality Gate Results

### 1. Automated Behavioral Tests
- `tests/Feature/Hr/AttendanceCaptureAndAnomalyTest.php`: **7 passed (64 assertions)**
  - Web punch clock records valid punch and calculates daily record.
  - Mobile GPS punch validates geofence and radius.
  - RFID smart card tap resolves active credential.
  - Dynamic rotating QR lifecycle and replay protection.
  - Batch CSV import two phase preview and commit.
  - Missing punch anomaly detection and admin quick resolution.
  - HTTP API endpoints for all capture channels, anomalies, and credentials.
- `tests/Feature/Hr/BiometricIntegrationTest.php`: **6 passed (43 assertions)**
- `tests/Unit/Hr/BiometricAdapterTest.php`: **6 passed (36 assertions)**
- `app/Modules/Hr/Tests/HrEngineTest.php`: **12 passed (157 assertions)**
- `app/Modules/Payroll/Tests/PayrollEngineTest.php`: **7 passed (73 assertions)**
- `app/Modules/Payroll/Tests/PayrollApiTest.php`: **1 passed (7 assertions)**
- `tests/Feature/Hr/EmployeeBlockerTest.php`: **4 passed (13 assertions)**
- `tests/Feature/Hr/HrPermissionBoundaryTest.php`: **4 passed (9 assertions)**
- `tests/Feature/Hr/HrRateMutationBoundaryTest.php`: **1 passed (2 assertions)**
- **Total HR & Payroll Backend Tests**: **48 passed / 777 assertions (0 failures)**

### 2. Architecture & Migration Invariant Gates
- `tests/Unit/Database/ModuleMigrationBaselineTest.php`: **1 passed (3 assertions)**
  - 1:1 table-to-migration baseline preserved.
  - 0 patch migrations (`Schema::table`).
  - Canonical timestamp sequencing verified.
  - Identifiers safely below 60-character ceiling.
- `tests/Unit/Architecture/HistoricalRetentionMigrationTest.php`: **1 passed (22 assertions)**
  - 100% `restrictOnDelete()` compliance on status history and audit tables.

### 3. Frontend Quality Gates
- **TypeScript Typecheck (`npm run typecheck`)**: **0 errors** across all strict mode files.
- **ESLint Analysis (`npx eslint`)**: **0 errors, 2 warnings** across modified HR frontend files.
- **Vitest Full Frontend Suite (`npm test`)**: **176 passed / 176 test files (739 tests passed)**.
- **Backend Code Style (`php vendor/bin/pint --test`)**: **0 PSR-12 violations** (`result: "passed"`).

---

## Author & Maintainer

- **Lead Architect & Developer:** Kasun Vimarshana
- **Email:** [kasunvmail@gmail.com](mailto:kasunvmail@gmail.com)
- **Phone:** [+94713525253](tel:+94713525253)
- **Repository:** [kasunvimarshana/AutoERP](https://github.com/kasunvimarshana/AutoERP)
