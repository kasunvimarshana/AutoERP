# Biometric Attendance Device Integration Architecture & Hardware Runbook

**Author:** Kasun Vimarshana (<kasunvmail@gmail.com>, +94713525253)  
**System:** AutoERP Enterprise System  
**Namespace:** `Modules\Hr\Biometric`  

---

## 1. Architectural Foundations & Supported Protocols

AutoERP provides an extensible, contract-based hardware integration layer designed to ingest attendance punches from heterogeneous biometric terminals across multiple physical branches and cloud environments.

The system decouples hardware protocol specifics from enterprise HR business logic via `BiometricDeviceAdapterInterface`. The core HR modules (`AttendanceCalculationEngine`, shifts, leaves, overtime rules) remain 100% agnostic to whether a punch originated from a ZKTeco facial scanner, a Hikvision turnstile, a mobile NFC reader, or a USB flash drive.

### 1.1 Inbound Push vs. Outbound Pull Topologies

| Feature | Push Architecture (ADMS / Webhooks) | Pull Architecture (TCP Socket / ISAPI) |
|---|---|---|
| **Initiator** | Device connects outbound to AutoERP server | AutoERP server connects inbound to device IP |
| **Firewall / NAT** | Works behind NAT firewalls, 4G LTE routers with zero router port forwarding | Requires static LAN IP, port forwarding, or site-to-site VPN |
| **Port** | Standard HTTP/HTTPS (80 / 443) | Hardware ports (e.g. TCP 4370 for ZKTeco, 80/443 for Hikvision) |
| **Scalability** | Cloud-native, handles distributed branch networks effortlessly | Best suited for on-premise local deployments or private WANs |
| **Protocols** | ZKTeco ADMS / iClock, Hikvision Event Webhook, Generic IoT JSON | ZKTeco Standalone Socket (4370), Hikvision ISAPI, Dahua CGI |

---

## 2. Supported Vendor Protocols

### 2.1 ZKTeco ADMS / iClock Push SDK
- **Handshake (`GET /iclock/cdata?SN={serial}&options=all`)**:
  Device announces its presence on boot. AutoERP responds with server operational configuration:
  ```text
  GET OPTION FROM: {SN}
  Stamp=9999
  OpStamp=0
  ErrorDelay=30
  Delay=10
  TransTimes=00:00;14:05
  TransInterval=1
  TransFlag=1111111111
  TimeZone=330
  Realtime=1
  Encrypt=0
  ```
- **Log Stream (`POST /iclock/cdata?SN={serial}&table=ATTLOG`)**:
  Device streams punches as tab-delimited or comma-delimited payloads:
  `101\t2026-11-25 08:30:00\t0\t1\t0\t0\t0`
  *(PIN \t PunchTime \t State \t VerifyType \t WorkCode \t Reserved \t Mask/Temp)*
  - States: `0`=CheckIn, `1`=CheckOut, `2`=BreakOut, `3`=BreakIn, `4`=OTIn, `5`=OTOut.
  - Verify Modes: `1`=Fingerprint, `2`=Password, `4`=RFID Card, `15`=Face.
  - AutoERP acknowledges with `OK: {count}`.
- **Command Queue (`GET /iclock/getrequest?SN={serial}`)**:
  Device polls for server instructions (e.g. `SET TIME {timestamp}`, `CLEAR LOG`, `REBOOT`).

### 2.2 ZKTeco Standalone TCP Socket (Port 4370)
- Implemented in `ZkTecoSocketAdapter` using direct PHP TCP socket frames.
- Sends binary command packets with 8-byte headers (`CMD_CONNECT` [1000], `CMD_ATTLOG_RRQ` [13], `CMD_SET_TIME` [202]).

### 2.3 Hikvision ISAPI (Intelligent Security API)
- RESTful HTTP interface querying `/ISAPI/AccessControl/AcsEvent?format=json` via Digest Authentication.
- Ingests minor event code 75 (access granted) containing `employeeNoString`, `cardNo`, and ISO timestamps.

### 2.4 Dahua CGI RPC
- HTTP GET interface for `recordFinder.cgi?action=find&name=AccessControlCardRec`.
- Parses structured key-value event streams (`records[i].UserID`, `records[i].CreateTime`, `records[i].CardNo`).

### 2.5 Generic Cloud / IoT Webhook
- Standard JSON push endpoint `/api/v1/hr/biometric/push/webhook/{deviceCode}` for Android terminals, edge IoT gateways, and modern cloud access control platforms.
- Authenticated via Bearer API Token or HMAC-SHA256 signature (`X-Biometric-Signature`).

### 2.6 USB Flash Drive / Offline Importer
- Supports importing raw flash drive dumps (`1_attlog.dat` or CSV) for offline remote sites.

---

## 3. Hardware Failures & Mitigation Engineering

### 3.1 Clock Drift Resolution
- Hardware RTC batteries degrade over time, causing devices to lose or gain minutes daily.
- Diagnostics measure real-time latency and drift between device time and server operational time.
- The system supports automated clock correction commands (`syncTime()`).
- All punches preserve dual timestamps: physical server recording time (`received_at`, `created_at`) and civil reported operational time (`punch_time`).

### 3.2 Power Outages & Buffer Surge Handling
- When local internet drops, devices store up to 100,000 punches in non-volatile flash memory.
- Upon reconnection, punches flood the server in large batches.
- The ingestion pipeline processes punches in chunked database transactions and enforces backpressure via queued background processing (`SyncBiometricDeviceJob`).

### 3.3 Cryptographic Idempotency & Deduplication
- Rapid double-punches (employees scanning twice within seconds) and network timeout retries are prevented through two independent defense layers:
  1. **Fingerprint Hash Layer**: SHA-256 hash over `tenant_id + device_id + biometric_user_pin + punch_time + punch_state`. Duplicate punches are acknowledged to hardware (avoiding endless transmission loops) but flagged as `duplicate` in staging without mutating attendance.
  2. **Sliding Window Layer**: `AttendanceCalculationEngine` filters punches of the same type within 120 seconds.

---

## 4. Hardware Configuration Runbook

### Configuring a ZKTeco Terminal (ADMS / Cloud Push)
1. On the physical machine, press **Menu** $\to$ **Comm.** $\to$ **Cloud Server / ADMS**.
2. Set **Server Address**: your AutoERP domain or public IP (e.g., `erp.yourcompany.com`).
3. Set **Server Port**: `80` (or `443` for HTTPS).
4. Set **Enable Domain Name**: `ON` (if using a domain) or `OFF` (if using IP).
5. Ensure the terminal's **Serial Number** (found under *System Info* $\to$ *Device Info*) matches the Serial Number configured in AutoERP's **Biometric Devices** page.
6. The terminal icon will change to connected (green globe or checkmark).

### Configuring a Hikvision Facial Recognition Terminal (ISAPI)
1. Open the device web management portal in a browser.
2. Navigate to **Configuration** $\to$ **Network** $\to$ **Advanced Settings** $\to$ **Integration Protocol**.
3. Enable **Hikvision-CGI / ISAPI** and set authentication to **Digest/Basic**.
4. Register the device IP and credentials in AutoERP under **Biometric Devices** with protocol `Hikvision ISAPI`.

---

## 5. Developer Guide: Adding a New Device Adapter

New hardware vendors can be added in 4 simple steps without modifying any core business logic:

1. **Create the Adapter Class**:
   Implement `BiometricDeviceAdapterInterface`:
   ```php
   namespace Modules\Hr\Biometric\Adapters;

   use Modules\Hr\Biometric\Contracts\BiometricDeviceAdapterInterface;

   final class CustomVendorAdapter implements BiometricDeviceAdapterInterface
   {
       public function ping(HrBiometricDevice $device): DeviceHealthResult { ... }
       public function syncTime(HrBiometricDevice $device, CarbonImmutable $targetTime): bool { ... }
       public function fetchLogs(HrBiometricDevice $device, ?string $cursor = null): DeviceLogSyncResult { ... }
       public function clearLogs(HrBiometricDevice $device): bool { ... }
       public function parseIncomingPush(HrBiometricDevice $device, Request $request): DeviceLogSyncResult { ... }
       public function generatePushResponse(HrBiometricDevice $device, Request $request, DeviceLogSyncResult $syncResult): Response { ... }
   }
   ```
2. **Add Protocol Case in `BiometricProtocol`**:
   Add `case CustomVendor = 'custom_vendor';` to `Modules\Hr\Enums\BiometricProtocol`.
3. **Register in `BiometricAdapterManager`**:
   Add the case match in `resolveByProtocol()` or call `$manager->registerAdapter('custom_vendor', CustomVendorAdapter::class)`.
4. **Run Quality Gates**:
   Run `php artisan test --filter Biometric` and `php vendor/bin/pint --test`.
