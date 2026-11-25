# AutoERP HR, Attendance, Leave, Shift & Payroll — User Guide

This guide provides step-by-step instructions for every HR, Attendance, Roster, Leave, Biometric Device, and Payroll feature implemented in AutoERP. All navigation paths, permissions, screens, and workflows below match the active application.

---

## 1. Prerequisites & Access Control

Before using HR or Payroll features:
1. Ensure your tenant has the **HR** module enabled (`hr`).
2. Select an active **Tenant** and **Organization Unit** in the workspace header.
3. Your user role must hold the appropriate granular permissions:
   - **Employees & Master Data:** `hr.employees.view`, `hr.employees.create`, `hr.employees.update`, `hr.employees.delete`, `hr.master-data.view`, `hr.master-data.manage`
   - **Shifts & Rosters:** `hr.shifts.view`, `hr.shifts.manage`
   - **Attendance:** `hr.attendance.view`, `hr.attendance.punch`, `hr.attendance.regularize`, `hr.attendance.manage`
   - **Leaves:** `hr.leaves.view`, `hr.leaves.apply`, `hr.leaves.manage`
   - **Biometric Devices:** `hr.biometric.view`, `hr.biometric.manage`, `hr.biometric.sync`
   - **Payroll:** `payroll.view`, `payroll.components.manage`, `payroll.structures.manage`, `payroll.assignments.manage`, `payroll.tax-tables.manage`, `payroll.statutory-rules.manage`, `payroll.periods.manage`, `payroll.pay-runs.calculate`, `payroll.pay-runs.approve`, `payroll.pay-runs.pay`, `payroll.pay-runs.reverse`, `payroll.payslips.view`, `payroll.adjustments.manage`, `payroll.loans.manage`

---

## 2. Master Data & Employee Management

### 2.1 Managing HR Master Data
Navigate in the sidebar under **Human Resources** to any master data view:
- **Departments** (`/hr/employees?view=departments`)
- **Designations** (`/hr/employees?view=designations`)
- **Employment Types** (`/hr/employees?view=employment-types`)
- **Skills** (`/hr/employees?view=skills`)
- **Certifications** (`/hr/employees?view=certifications`)
- **Licenses** (`/hr/employees?view=licenses`)

On each master data panel, users with `hr.master-data.manage` can create, edit, and delete master records used across employee profiles.

### 2.2 Creating & Managing Employees
1. Go to **Human Resources → Create Employee** (`/hr/employees/create`) to create a new employee profile (employee number, personal details, department, designation, employment type, supervisor, hire date).
2. Go to **Human Resources → Employees** (`/hr/employees?view=employees`) to search, filter, and open an employee's detail page (`/hr/employees/:id`) or edit page (`/hr/employees/:id/edit`).
3. Inside an **Employee Detail** page (`/hr/employees/:id`), use the tabs to manage:
   - **Contacts**, **Addresses**, **Documents**
   - **Skills**, **Certifications**, **Licenses**
   - **Rates** (append-only compensation rate history; rates are create-only to preserve audit history)
   - **Availability** & **Status History** (immutable lifecycle audit trail)
   - **Attendance Credentials** (issue, revoke, or delete **RFID**, **NFC**, **Barcode**, or **PIN** credentials for badge/card/kiosk attendance capture)

---

## 3. Shift Roster & Scheduling (`/hr/roster`)

Navigate to **Human Resources → Roster & Shifts** (`/hr/roster`).

### 3.1 Viewing the Roster Grid
- Use the **From** and **To** date pickers and click **Refresh** to view the multi-day schedule grid per employee.
- Cells display:
  - **Shift Code & Start Time** (color-coded by shift)
  - **Off** for rest days
  - **Holiday Name** (purple badge) when a Holiday Calendar entry applies

### 3.2 Assigning a Shift
1. Click **+ Assign Shift** (requires `hr.shifts.manage`).
2. Select the **Employee** and **Shift**.
3. Choose **Assignment Type**:
   - **Specific Single Date:** Assigns a one-off shift on a single date.
   - **Date Range:** Assigns a shift across `Start Date` to `End Date`.
   - **Weekly Recurring Day:** Assigns a recurring shift for a chosen day of the week.
4. Submit to update the roster. (Conflicts with approved leave or overlapping active assignments are automatically blocked.)

### 3.3 Requesting a Shift Swap
1. Click **Request Swap** (requires `hr.shifts.manage` in the roster toolbar).
2. Select the **Requester Employee**, **Target Employee**, **Swap Date**, **Target Swap Date**, and enter a **Reason**.
3. Submit the swap request. The target peer can accept/reject via the peer-action workflow, followed by manager approval/rejection.

### 3.4 Reallocating Absent Cover
1. Click **Reallocate Cover**.
2. Select the **Absent Employee**, **Cover Employee**, **Shift**, **Date**, and **Reason Code** (`sick_cover`, `emergency_cover`, `surge_demand`, `operational_need`).
3. Submit to assign temporary cover on the roster.

> **API Note for Holiday Calendars & Shift Rotations:** Holiday Calendars (`/api/v1/hr/holiday-calendars`, `/api/v1/hr/holidays`) and Shift Rotations (`/api/v1/hr/shift-rotations`, `/api/v1/hr/shift-rotation-assignments`) are managed via the HR REST API and automatically feed into the Roster Grid, Attendance Calculation Engine, and Leave Management.

---

## 4. Attendance & Multi-Method Time Tracking (`/hr/attendance`)

Navigate to **Human Resources → Attendance** (`/hr/attendance`).

### 4.1 Quick Web Clock-In / Clock-Out
- Users with `hr.attendance.punch` can click **Clock In** or **Clock Out** directly from the header actions (after selecting an employee or for their own profile).

### 4.2 Daily Attendance Records & Adjustments (`Records` Tab)
1. Filter by **Start Date**, **End Date**, and **Employee**.
2. Review daily **Clock In**, **Clock Out**, **Worked Minutes**, **Overtime Minutes**, and **Status** (`present`, `absent`, `half_day`, `late`, `on_leave`, `holiday`, `rest_day`).
3. **Request Regularization:** Click **Request Regularization** to submit a correction (`forgot_to_punch`, `missing_in`, `missing_out`, `both_missing`, `incorrect_time`, `on_duty`) with proposed times and notes.
4. **Direct Adjust (Managers):** Users with `hr.attendance.manage` can click **Adjust** on a record to set `new_status`, `new_worked_minutes`, `new_overtime_minutes`, and an `adjustment_reason` (creates an immutable `HrAttendanceAdjustment` audit record).

### 4.3 Regularization Approvals (`Regularizations` Tab)
- Managers with `hr.attendance.manage` review pending employee regularization requests and click **Approve** or **Reject**.

### 4.4 Missing Punch Anomalies (`Anomalies` Tab)
1. Click **Scan Missing Punches** in the header to scan attendance records for `missing_in`, `missing_out`, or `both_missing`.
2. In the **Anomalies** tab, click **Quick Resolve** on any flagged record, enter the missing `in_time` / `out_time` and `resolution_reason`, and submit to recalculate the daily record.

### 4.5 Dynamic Rotating QR Kiosk (`Kiosk` Tab)
1. Select an active **Attendance Kiosk** from the dropdown.
2. The terminal displays a rotating **TOTP QR Token** (refreshes every 30 seconds).
3. Employees scan the kiosk QR code from mobile/web (`POST /api/v1/hr/attendance/capture/qr-scan`) to record a replay-protected punch.

### 4.6 Two-Phase Batch Import — CSV / Excel `.xlsx` (`Import` Tab)
1. Upload a `.csv`, `.txt`, or `.xlsx` file (or paste CSV content directly).
   - Expected columns: `employee_code,date,time,punch_type,notes` (Excel 1900-epoch numeric serial dates/times are automatically normalized).
2. Click **Run Dry Run Validation** to preview every row, employee lookup match, date/time validity, and duplicate detection.
3. Once `Invalid = 0`, click **Commit Valid Batch** to atomically ingest all valid punches and recalculate daily attendance.

### 4.7 Additional Capture Channels (API / Hardware)
- **Mobile GPS:** `POST /api/v1/hr/attendance/capture/mobile` (validates coordinates against configured `HrAttendanceGeofence` radius).
- **RFID Smart Card:** `POST /api/v1/hr/attendance/capture/rfid` (`card_uid`).
- **NFC Tag:** `POST /api/v1/hr/attendance/capture/nfc` (`tag_uid`).
- **Barcode Badge:** `POST /api/v1/hr/attendance/capture/barcode` (`barcode`).
- **Unified Capture Endpoint:** `POST /api/v1/hr/attendance/capture` with `capture_method` parameter.

---

## 5. Biometric Devices & Hardware Sync (`/hr/biometric-devices`)

Navigate to **Human Resources → Biometric Devices** (`/hr/biometric-devices`).

1. **Register a Device:** Add a biometric terminal specifying `device_code`, `name`, `protocol` (`zkteco_push`, `zkteco_socket`, `hikvision_isapi`, `dahua_cgi`, `usb_import`, `generic_webhook`), IP/port or webhook config.
2. **Health Check & Sync:**
   - Click **Ping** to test connectivity.
   - Click **Sync** to pull attendance logs from the device (supports synchronous or queued async sync).
   - **Upload Log:** Upload exported USB `.dat`/`.csv` log files directly to a device.
3. **Employee Biometric Mapping:** Map each hardware `biometric_user_id` (and optional `card_number`) to an AutoERP Employee.
4. **Raw Logs & Reprocessing:** Inspect `raw-logs` (`synced`, `unmapped`, `duplicate`, `failed`). After mapping previously unmapped device user IDs, click **Reprocess** to idempotently convert staged raw punches into attendance records.

---

## 6. Leave Management (`/hr/leaves`)

Navigate to **Human Resources → Leave Management** (`/hr/leaves`).

1. **Filter & View Balances:** Select an **Employee** (or view all) to see live Leave Balance cards showing `current_balance`, `accrued_days` (quota), `used_days`, and `pending_approval_days`.
2. **Apply for Leave:**
   - Click **+ Apply for Leave** (requires `hr.leaves.apply`).
   - Choose **Employee**, **Leave Type**, **Start Date**, **End Date**, **Duration Type** (`full_day`, `half_day_morning`, `half_day_afternoon`, `custom_hours`), **Total Days**, and **Reason**.
   - Overlapping leave dates and insufficient balances (beyond policy negative limits) are automatically rejected.
3. **Approve / Reject / Cancel Leave:**
   - Managers (`hr.leaves.manage`) click **Approve** or **Reject** on pending requests.
   - Applicants (`hr.leaves.apply`) can click **Cancel** on `pending` or `approved` requests (cancelling an approved leave restores balance and triggers attendance recalculation).
4. **Claim Compensatory Off (Comp-Off):**
   - Click **Claim Comp-Off**, select the **Employee**, **Worked Date**, **Claimed Days**, and **Reason**, then submit for manager approval.

> **API Note for Leave Types & Policies:** Leave Types (`/api/v1/hr/leave-types`) and Leave Policies (`/api/v1/hr/leave-policies`) support full CRUD via the HR API.

---

## 7. Payroll Management

Navigate under **Payroll** in the sidebar:

### 7.1 Salary Structures & Components (`/payroll/structures`)
1. **Create Salary Components:** Click to add earnings or deductions (`fixed`, `percentage_of_basic`, `formula`, `attendance_based`), marking whether each component is taxable and/or statutory-applicable.
2. **Create Salary Structures:** Group ordered salary components with amounts/rates and cycle type (`monthly`, `weekly`, `biweekly`) to assign to employees.

### 7.2 Pay Runs & Execution (`/payroll/pay-runs`)
1. **Create Pay Period:** Define period `name`, `period_start`, `period_end`, `payout_date`, and `total_working_days`.
2. **Create & Calculate Pay Run:** Select a Pay Period, create a Pay Run, and click **Calculate**. The engine aggregates attendance, clips unpaid leave to period boundaries, prorates mid-period joiners/leavers, applies progressive tax brackets, statutory rules (e.g., EPF/ETF), loan installments, and approved adjustments.
3. **Approve Pay Run:** Review calculated totals and click **Approve** (`payroll.pay-runs.approve`).
4. **Finalize & Post to GL:** Click **Pay** (`payroll.pay-runs.pay`) to finalize payout and post balanced debit/credit journals to the Finance General Ledger.
5. **Reverse Pay Run:** If a correction is needed after approval/payment, click **Reverse** (`payroll.pay-runs.reverse`) with a reason to create linked reversal journal entries without mutating historical records.
6. **Retroactive Pay Arrears:** Use the **Retro Arrears** modal to calculate arrears for an employee from an `effective_date` into a target pay period.

### 7.3 Loans & Advances (`/payroll/loans`)
1. Filter loans by **Status** and **Type** (`loan` or salary `advance`).
2. **Create Loan / Advance:** Select **Employee**, **Type**, **Principal Amount**, **Interest Rate**, **Term Months**, **Start Period**, and **Notes** (displays live estimated EMI preview).
3. **Lifecycle Actions:** **Approve** → **Disburse** (with disbursement date & period) → automatic installment deduction during Pay Run calculation, or **Cancel** if not yet disbursed.

### 7.4 Employee Payslips (`/payroll/payslips`)
1. Filter payslips by **Pay Run** or select a payslip from the list.
2. View itemized **Earnings**, **Deductions**, **Statutory Contributions (EPF/ETF)**, **Tax Withholdings**, and **Net Take-Home Pay**.
3. Click **Print Payslip** to print or save as PDF.
