/**
 * AutoERP Enterprise System
 *
 * @author Kasun Vimarshana <kasunvmail@gmail.com>
 *
 * Phone: +94713525253
 */

import type { NamedResource } from '@/shared/types/common';

export type EmployeeStatus = 'active' | 'inactive' | 'on_leave' | 'suspended' | 'terminated' | 'pending_approval';
export type EmployeeAvailabilityStatus = 'available' | 'assigned' | 'on_leave' | 'unavailable' | 'suspended' | 'inactive';
export interface HrMaster extends NamedResource { tenant_id: number; organization_unit_id?: number | null; row_version?: number; description?: string | null; is_active: boolean; sort_order?: number | null; }
export interface HrDepartment extends HrMaster { parent_id?: number | null; parent?: HrDepartment | null; }
export type HrDesignation = HrMaster;
export type HrEmploymentType = HrMaster;
export type HrSkill = HrMaster;
export type HrCertification = HrMaster;
export type HrLicense = HrMaster;

export interface EmployeeSummary extends NamedResource {
    id: number; row_version: number; tenant_id: number; organization_unit_id?: number | null; employee_number: string; code?: string | null;
    display_name: string; first_name: string; middle_name?: string | null; last_name?: string | null;
    email?: string | null; phone?: string | null; mobile?: string | null; status: EmployeeStatus;
    availability_status: EmployeeAvailabilityStatus; department?: HrDepartment | null; designation?: HrDesignation | null; employment_type?: HrEmploymentType | null;
}
export interface Employee extends EmployeeSummary {
    department_id?: number | null; designation_id?: number | null; employment_type_id?: number | null; reporting_manager_id?: number | null;
    reporting_manager?: EmployeeSummary | null; joined_date?: string | null; resigned_date?: string | null; date_of_birth?: string | null;
    gender?: 'male' | 'female' | 'other' | 'not_specified' | null; default_hourly_rate: string; default_daily_rate: string;
    default_service_rate: string; notes?: string | null; metadata?: Record<string, unknown> | null; approved_at?: string | null;
}
export interface EmployeePayload {
    row_version?: number; employee_number?: string; code?: string; first_name: string; middle_name?: string; last_name?: string; display_name?: string;
    email?: string; phone?: string; mobile?: string; department_id?: number | null; designation_id?: number | null;
    employment_type_id?: number | null; reporting_manager_id?: number | null; joined_date?: string; resigned_date?: string;
    date_of_birth?: string; gender?: string; status?: EmployeeStatus; availability_status?: EmployeeAvailabilityStatus;
    default_hourly_rate?: string; default_daily_rate?: string; default_service_rate?: string; notes?: string;
}
export interface EmployeeContact { id: number; contact_name: string; relationship?: string | null; email?: string | null; phone?: string | null; mobile?: string | null; is_emergency_contact: boolean; is_primary: boolean; is_active: boolean; notes?: string | null; }
export type EmployeeContactPayload = Omit<EmployeeContact, 'id'>;
export interface EmployeeAddress { id: number; address_type: string; address_line_1: string; address_line_2?: string | null; city?: string | null; state?: string | null; postal_code?: string | null; country?: string | null; is_primary: boolean; is_active: boolean; }
export type EmployeeAddressPayload = Omit<EmployeeAddress, 'id'>;
export interface EmployeeDocument { id: number; document_type: string; document_number?: string | null; issued_date?: string | null; expiry_date?: string | null; file_path?: string | null; status: string; notes?: string | null; }
export type EmployeeDocumentPayload = Omit<EmployeeDocument, 'id'>;
export interface EmployeeSkillAssignment { id: number; skill_id: number; skill?: HrSkill; proficiency_level: string; years_of_experience: string; is_primary: boolean; }
export type EmployeeSkillPayload = Omit<EmployeeSkillAssignment, 'id' | 'skill'>;
export interface EmployeeCertificationAssignment { id: number; certification_id: number; certification?: HrCertification; certificate_number?: string | null; issued_date?: string | null; expiry_date?: string | null; status: string; }
export type EmployeeCertificationPayload = Omit<EmployeeCertificationAssignment, 'id' | 'certification'>;
export interface EmployeeLicenseAssignment { id: number; license_id: number; license?: HrLicense; license_number?: string | null; issued_date?: string | null; expiry_date?: string | null; status: string; }
export type EmployeeLicensePayload = Omit<EmployeeLicenseAssignment, 'id' | 'license'>;
export interface EmployeeRate { id: number; rate_type: string; amount: string; currency_id?: number | null; currency?: NamedResource | null; effective_from?: string | null; effective_to?: string | null; is_active: boolean; }
export type EmployeeRatePayload = Omit<EmployeeRate, 'id' | 'currency'>;
export interface EmployeeAvailability { id: number; availability_date?: string | null; availability_status: EmployeeAvailabilityStatus; source_type?: string | null; source_id?: number | null; reason?: string | null; starts_at?: string | null; ends_at?: string | null; }
export type EmployeeAvailabilityPayload = Omit<EmployeeAvailability, 'id'>;
export interface EmployeeStatusHistory { id: number; old_status?: EmployeeStatus | null; new_status: EmployeeStatus; reason?: string | null; changed_by?: number | null; changed_at: string; }
export interface EmployeeRelationsPayload { contacts: EmployeeContactPayload[]; addresses: EmployeeAddressPayload[]; documents: EmployeeDocumentPayload[]; skills: EmployeeSkillPayload[]; certifications: EmployeeCertificationPayload[]; licenses: EmployeeLicensePayload[]; rates: EmployeeRatePayload[]; availability?: EmployeeAvailabilityPayload; }
export interface EmployeeWithRelationsPayload extends EmployeeRelationsPayload { employee: EmployeePayload; }

// --- Shifts & Rostering Types ---
export type ShiftStatus = 'active' | 'inactive';
export type ShiftAssignmentType = 'recurring' | 'date_range' | 'specific_date';
export type ShiftSwapStatus = 'pending_peer' | 'peer_accepted' | 'peer_rejected' | 'manager_approved' | 'manager_rejected' | 'cancelled';
export type ShiftReallocationReason = 'sick_cover' | 'emergency_cover' | 'surge_demand' | 'operational_need';
export type ShiftBreakType = 'meal' | 'rest' | 'prayer' | 'other';

export interface HrShiftBreak {
    id: number;
    row_version: number;
    tenant_id?: number;
    organization_unit_id?: number | null;
    shift_id: number;
    name: string;
    break_type: string;
    start_time?: string | null;
    end_time?: string | null;
    duration_minutes: number;
    is_paid: boolean;
    is_mandatory: boolean;
}

export type HrShiftBreakPayload = Omit<HrShiftBreak, 'id' | 'row_version' | 'tenant_id' | 'organization_unit_id' | 'shift_id'>;

export interface HrShift {
    id: number; row_version: number; tenant_id: number; organization_unit_id?: number | null;
    code: string; name: string; color: string; start_time: string; end_time: string;
    is_overnight: boolean; grace_in_minutes: number; grace_out_minutes: number;
    half_day_threshold_minutes: number; full_day_threshold_minutes: number;
    is_break_paid: boolean; auto_deduct_break: boolean; auto_deduct_break_minutes: number;
    auto_deduct_threshold_minutes: number; is_flexible: boolean; status: ShiftStatus; notes?: string | null;
    breaks?: HrShiftBreak[];
}
export type HrShiftPayload = Omit<HrShift, 'id' | 'row_version' | 'tenant_id' | 'breaks'>;

export interface HrShiftAssignment {
    id: number; row_version: number; employee_id: number; shift_id: number;
    assignment_type: ShiftAssignmentType;
    day_of_week?: number | null; start_date?: string | null; end_date?: string | null;
    is_custom_timing: boolean; custom_start_time?: string | null; custom_end_time?: string | null;
    is_cancelled: boolean; cancellation_reason?: string | null;
    shift?: HrShift; employee?: EmployeeSummary;
}

export interface HrShiftAssignmentPayload {
    employee_id: number;
    shift_id: number;
    assignment_type: ShiftAssignmentType;
    day_of_week?: number | null;
    start_date?: string | null;
    end_date?: string | null;
    is_custom_timing?: boolean;
    custom_start_time?: string | null;
    custom_end_time?: string | null;
}

export interface HrShiftSwap {
    id: number; row_version: number; requester_employee_id: number; target_employee_id: number;
    requester_shift_id?: number | null; target_shift_id?: number | null;
    swap_date: string; target_swap_date: string; reason: string;
    status: ShiftSwapStatus;
    manager_action_notes?: string | null;
    peer_responded_at?: string | null;
    manager_actioned_at?: string | null;
    requester_employee?: EmployeeSummary; target_employee?: EmployeeSummary;
    requester_shift?: HrShift; target_shift?: HrShift;
}

export interface HrShiftReallocation {
    id: number;
    row_version: number;
    tenant_id?: number;
    organization_unit_id?: number | null;
    absent_employee_id: number;
    cover_employee_id: number;
    shift_id: number;
    reallocation_date: string;
    reason_code: ShiftReallocationReason;
    notes?: string | null;
    absent_employee?: EmployeeSummary;
    cover_employee?: EmployeeSummary;
    shift?: HrShift;
}

export interface HrShiftReallocationPayload {
    absent_employee_id: number;
    cover_employee_id: number;
    shift_id: number;
    reallocation_date: string;
    reason_code?: ShiftReallocationReason;
    notes?: string | null;
}

// --- Attendance Types ---
export type PunchType = 'in' | 'out' | 'break_out' | 'break_in' | 'meal_out' | 'meal_in';
export type PunchSource = 'web' | 'mobile' | 'biometric_device' | 'manual_entry' | 'system_resolved';
export type CaptureMethod = 'biometric' | 'rfid_card' | 'nfc_tag' | 'barcode' | 'totp_qr' | 'mobile_gps' | 'web_portal' | 'manual_admin' | 'file_upload';
export type AnomalyType = 'missing_out' | 'missing_in' | 'unpaired_break' | 'excessive_hours' | 'short_shift' | 'geofence_violation';
export type CredentialType = 'rfid_card' | 'nfc_tag' | 'barcode' | 'mobile_device_uuid';
export type CredentialStatus = 'active' | 'suspended' | 'lost' | 'revoked';
export type AttendanceStatus = 'present' | 'late' | 'half_day' | 'absent' | 'on_leave' | 'rest_day' | 'holiday' | 'unresolved' | 'missing_punch' | 'anomalous';

export interface HrAttendancePunch {
    id: number; attendance_record_id?: number | null; employee_id: number;
    attendance_date: string; punch_time: string; punch_type: PunchType;
    punch_source: PunchSource; capture_method?: CaptureMethod;
    credential_id?: number | null; accuracy_meters?: number | null;
    geofence_id?: number | null; device_id?: string | null; ip_address?: string | null;
    latitude?: string | null; longitude?: string | null; is_verified: boolean; is_ignored: boolean;
}

export interface HrAttendanceRecord {
    id: number; row_version: number; employee_id: number; attendance_date: string;
    shift_id?: number | null; first_punch_in?: string | null; last_punch_out?: string | null;
    scheduled_minutes: number; total_worked_minutes: number; total_break_minutes: number;
    unpaid_break_minutes: number; regular_minutes: number; overtime_minutes: number;
    double_time_minutes: number; late_arrival_minutes: number; early_departure_minutes: number;
    status: AttendanceStatus; lock_status: 'open' | 'locked' | 'finalized';
    is_regularized: boolean; anomaly_type?: AnomalyType | null; requires_regularization?: boolean; notes?: string | null;
    shift?: HrShift; employee?: EmployeeSummary; punches?: HrAttendancePunch[];
}

export interface HrEmployeeAttendanceCredential {
    id: number; row_version: number; employee_id: number;
    credential_type: CredentialType; credential_value: string; label?: string | null;
    status: CredentialStatus; issued_at?: string | null; expires_at?: string | null;
    revoked_at?: string | null; revocation_reason?: string | null;
}

export interface HrAttendanceGeofence {
    id: number; row_version: number; name: string; code: string;
    latitude: string | number; longitude: string | number; radius_meters: number;
    ip_whitelist?: string | null; is_active: boolean; notes?: string | null;
}

export interface HrAttendanceGeofencePayload {
    name: string;
    code: string;
    latitude: number | string;
    longitude: number | string;
    radius_meters: number;
    ip_whitelist?: string | null;
    is_active?: boolean;
    notes?: string | null;
}

export interface HrAttendanceKiosk {
    id: number; row_version: number; kiosk_code: string; name: string;
    secret_key?: string; rotation_seconds: number; geofence_id?: number | null;
    is_active: boolean; last_heartbeat_at?: string | null;
    geofence?: HrAttendanceGeofence | null;
}

export interface HrAttendanceKioskPayload {
    kiosk_code: string;
    name: string;
    rotation_seconds?: number;
    geofence_id?: number | null;
    is_active?: boolean;
}

export interface BatchImportPreview {
    total_rows: number;
    valid_rows: number;
    invalid_rows: number;
    rows: Array<{
        row_number: number;
        employee_code: string;
        employee_name?: string | null;
        employee_id?: number | null;
        date: string;
        time: string;
        punch_type: string;
        notes?: string;
        is_valid: boolean;
        errors: string[];
    }>;
    error_summary: string[];
}

export interface HrAttendanceRegularization {
    id: number; row_version: number; attendance_record_id?: number | null; employee_id: number;
    attendance_date: string; regularization_type: 'missing_in' | 'missing_out' | 'both_missing' | 'incorrect_time' | 'forgot_to_punch' | 'on_duty';
    proposed_clock_in?: string | null; proposed_clock_out?: string | null; reason_code: string;
    employee_notes: string; status: 'pending' | 'approved' | 'rejected' | 'cancelled';
    actioned_at?: string | null; action_notes?: string | null;
    employee?: EmployeeSummary; attendance_record?: HrAttendanceRecord;
}

// --- Leave Types & Policies ---
export type LeaveTypeStatus = 'active' | 'inactive';
export type LeaveAccrualFrequency = 'annual_upfront' | 'monthly_prorated' | 'quarterly' | 'no_accrual';
export type LeaveDurationType = 'full_day' | 'half_day_morning' | 'half_day_afternoon' | 'custom_hours';
export type LeaveRequestStatus = 'pending' | 'approved' | 'rejected' | 'cancelled';
export type CompOffClaimStatus = 'pending' | 'approved' | 'rejected' | 'expired' | 'used';

export interface HrLeaveType {
    id: number; row_version: number; code: string; name: string; color: string;
    is_paid: boolean; requires_attachment: boolean; attachment_mandatory_after_days: number;
    allow_half_day: boolean; allow_hourly: boolean; is_compensatory: boolean;
    sandwich_rule_applied: boolean; status: LeaveTypeStatus;
}

export interface HrLeaveTypePayload {
    code: string;
    name: string;
    color?: string;
    is_paid?: boolean;
    requires_attachment?: boolean;
    attachment_mandatory_after_days?: number;
    allow_half_day?: boolean;
    allow_hourly?: boolean;
    is_compensatory?: boolean;
    sandwich_rule_applied?: boolean;
    status?: LeaveTypeStatus;
}

export interface HrLeavePolicy {
    id: number; row_version: number; leave_type_id: number; department_id?: number | null;
    employment_type_id?: number | null; name: string; annual_allocation_days: string;
    accrual_frequency: LeaveAccrualFrequency;
    max_carry_forward_days: string; carry_forward_expiry_months: number;
    max_consecutive_days: number; probation_restricted: boolean;
    effective_from: string; effective_to?: string | null;
    leave_type?: HrLeaveType; department?: HrDepartment | null; employment_type?: HrEmploymentType | null;
}

export interface HrLeavePolicyPayload {
    leave_type_id: number;
    name: string;
    annual_allocation_days: string | number;
    effective_from: string;
    effective_to?: string | null;
    accrual_frequency?: LeaveAccrualFrequency;
    max_carry_forward_days?: string | number;
    carry_forward_expiry_months?: number;
    max_consecutive_days?: number;
    probation_restricted?: boolean;
    department_id?: number | null;
    employment_type_id?: number | null;
}

export interface HrLeaveBalance {
    id: number; row_version: number; employee_id: number; leave_type_id: number; calendar_year: number;
    opening_balance: string; accrued_days: string; carried_forward_days: string; used_days: string;
    pending_approval_days: string; expired_days: string; current_balance: string;
    leave_type?: HrLeaveType; employee?: EmployeeSummary;
}

export interface HrLeaveRequest {
    id: number; row_version: number; employee_id: number; leave_type_id: number;
    start_date: string; end_date: string; duration_type: LeaveDurationType;
    total_days: string; reason: string; attachment_document_id?: number | null;
    status: LeaveRequestStatus; approved_at?: string | null; action_notes?: string | null;
    leave_type?: HrLeaveType; employee?: EmployeeSummary;
}

export interface HrCompOffClaim {
    id: number; row_version: number; employee_id: number; worked_date: string;
    attendance_record_id?: number | null;
    claimed_days: string; reason: string; status: CompOffClaimStatus;
    expiry_date?: string | null; employee?: EmployeeSummary;
}

// --- Shift Rotations ---
export interface HrShiftRotationStep {
    id: number;
    row_version: number;
    shift_rotation_id: number;
    day_sequence: number;
    shift_id: number | null;
    shift?: HrShift | null;
    notes?: string | null;
}

export interface HrShiftRotationStepPayload {
    day_sequence: number;
    shift_id?: number | null;
    notes?: string | null;
}

export interface HrShiftRotation {
    id: number;
    row_version: number;
    code: string;
    name: string;
    description?: string | null;
    cycle_days: number;
    status: 'active' | 'inactive';
    steps?: HrShiftRotationStep[];
}

export interface HrShiftRotationPayload {
    code: string;
    name: string;
    description?: string | null;
    cycle_days: number;
    status?: 'active' | 'inactive';
}

export interface HrShiftRotationAssignment {
    id: number;
    row_version: number;
    tenant_id?: number;
    organization_unit_id?: number | null;
    employee_id: number;
    shift_rotation_id: number;
    anchor_date: string;
    end_date?: string | null;
    is_active: boolean;
    employee?: EmployeeSummary;
    rotation?: HrShiftRotation;
}

// --- Holiday Calendars & Holidays ---
export interface HrHoliday {
    id: number;
    row_version: number;
    tenant_id?: number;
    organization_unit_id?: number | null;
    calendar_id: number;
    name: string;
    holiday_date: string;
    is_mandatory: boolean;
    is_comp_off_eligible: boolean;
    overtime_multiplier: string;
    description?: string | null;
}

export interface HrHolidayPayload {
    name: string;
    holiday_date: string;
    is_mandatory?: boolean;
    is_comp_off_eligible?: boolean;
    overtime_multiplier?: string | number;
    description?: string | null;
}

export interface HrHolidayCalendar {
    id: number;
    row_version: number;
    tenant_id?: number;
    organization_unit_id?: number | null;
    code: string;
    name: string;
    calendar_year: number;
    is_default: boolean;
    holidays_count?: number;
    holidays?: HrHoliday[];
}

export interface HrHolidayCalendarPayload {
    code: string;
    name: string;
    calendar_year: number;
    is_default?: boolean;
}

// --- Biometric Hardware Integration Types ---
export type BiometricProtocol = 'zkteco_push' | 'zkteco_socket' | 'hikvision_isapi' | 'dahua_cgi' | 'generic_webhook' | 'usb_import';
export type BiometricDeviceStatus = 'active' | 'inactive' | 'offline' | 'error';
export type BiometricPunchState = 'check_in' | 'check_out' | 'break_out' | 'break_in' | 'overtime_in' | 'overtime_out' | 'auto';
export type BiometricVerifyMethod = 'fingerprint' | 'face' | 'card' | 'password' | 'iris' | 'unknown';
export type RawPunchSyncStatus = 'pending' | 'processed' | 'duplicate' | 'unmapped_user' | 'failed';

export interface HrBiometricDevice {
    id: number;
    row_version: number;
    tenant_id: number;
    organization_unit_id?: number | null;
    device_code: string;
    name: string;
    protocol: BiometricProtocol;
    ip_address?: string | null;
    port?: number | null;
    serial_number?: string | null;
    comm_key?: string | null;
    auth_token?: string | null;
    status: BiometricDeviceStatus;
    is_enabled: boolean;
    auto_map_users: boolean;
    last_ping_at?: string | null;
    last_sync_at?: string | null;
    total_punches_synced: number;
    settings?: Record<string, unknown> | null;
    device_users_count?: number;
    raw_punches_count?: number;
}

export type HrBiometricDevicePayload = Omit<HrBiometricDevice, 'id' | 'tenant_id' | 'total_punches_synced' | 'device_users_count' | 'raw_punches_count'>;

export interface HrBiometricDeviceUser {
    id: number;
    tenant_id: number;
    device_id: number;
    biometric_user_id: string;
    card_number?: string | null;
    employee_id: number;
    is_active: boolean;
    sync_status: string;
    employee?: EmployeeSummary | null;
}

export interface HrBiometricRawPunch {
    id: number;
    tenant_id: number;
    device_id: number;
    biometric_user_id: string;
    employee_id?: number | null;
    attendance_punch_id?: number | null;
    punch_time: string;
    received_at: string;
    punch_state: BiometricPunchState;
    verify_method: BiometricVerifyMethod;
    idempotency_hash: string;
    sync_status: RawPunchSyncStatus;
    error_message?: string | null;
    employee?: EmployeeSummary | null;
    attendance_punch?: HrAttendancePunch | null;
}

export interface DeviceHealthResult {
    isReachable: boolean;
    responseTimeMs?: number | null;
    firmwareVersion?: string | null;
    deviceTime?: string | null;
    clockDriftSeconds?: number | null;
    errorMessage?: string | null;
    metadata?: Record<string, unknown>;
}
