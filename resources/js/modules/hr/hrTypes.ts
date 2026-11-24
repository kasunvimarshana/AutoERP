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
export interface HrMaster extends NamedResource { tenant_id: number; organization_unit_id?: number | null; description?: string | null; is_active: boolean; sort_order?: number | null; }
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
export interface HrShift {
    id: number; row_version: number; tenant_id: number; organization_unit_id?: number | null;
    code: string; name: string; color: string; start_time: string; end_time: string;
    is_overnight: boolean; grace_in_minutes: number; grace_out_minutes: number;
    half_day_threshold_minutes: number; full_day_threshold_minutes: number;
    is_break_paid: boolean; auto_deduct_break: boolean; auto_deduct_break_minutes: number;
    auto_deduct_threshold_minutes: number; is_flexible: boolean; status: ShiftStatus; notes?: string | null;
}
export type HrShiftPayload = Omit<HrShift, 'id' | 'row_version' | 'tenant_id'>;

export interface HrShiftAssignment {
    id: number; row_version: number; employee_id: number; shift_id: number;
    assignment_type: 'recurring' | 'date_range' | 'specific_date';
    day_of_week?: number | null; start_date?: string | null; end_date?: string | null;
    is_custom_timing: boolean; custom_start_time?: string | null; custom_end_time?: string | null;
    is_cancelled: boolean; cancellation_reason?: string | null;
    shift?: HrShift; employee?: EmployeeSummary;
}

export interface HrShiftSwap {
    id: number; row_version: number; requester_employee_id: number; target_employee_id: number;
    requester_shift_id?: number | null; target_shift_id?: number | null;
    swap_date: string; target_swap_date: string; reason: string;
    status: 'pending_peer' | 'peer_accepted' | 'peer_rejected' | 'manager_approved' | 'manager_rejected' | 'cancelled';
    requester_employee?: EmployeeSummary; target_employee?: EmployeeSummary;
    requester_shift?: HrShift; target_shift?: HrShift;
}

// --- Attendance Types ---
export type PunchType = 'in' | 'out' | 'break_out' | 'break_in' | 'meal_out' | 'meal_in';
export type PunchSource = 'web' | 'mobile' | 'biometric_device' | 'manual_entry' | 'system_resolved';
export type AttendanceStatus = 'present' | 'late' | 'half_day' | 'absent' | 'on_leave' | 'rest_day' | 'holiday' | 'unresolved';

export interface HrAttendancePunch {
    id: number; attendance_record_id?: number | null; employee_id: number;
    attendance_date: string; punch_time: string; punch_type: PunchType;
    punch_source: PunchSource; device_id?: string | null; ip_address?: string | null;
    latitude?: string | null; longitude?: string | null; is_verified: boolean; is_ignored: boolean;
}

export interface HrAttendanceRecord {
    id: number; row_version: number; employee_id: number; attendance_date: string;
    shift_id?: number | null; first_punch_in?: string | null; last_punch_out?: string | null;
    scheduled_minutes: number; total_worked_minutes: number; total_break_minutes: number;
    unpaid_break_minutes: number; regular_minutes: number; overtime_minutes: number;
    double_time_minutes: number; late_arrival_minutes: number; early_departure_minutes: number;
    status: AttendanceStatus; lock_status: 'open' | 'locked' | 'finalized';
    is_regularized: boolean; notes?: string | null;
    shift?: HrShift; employee?: EmployeeSummary; punches?: HrAttendancePunch[];
}

export interface HrAttendanceRegularization {
    id: number; row_version: number; attendance_record_id?: number | null; employee_id: number;
    attendance_date: string; regularization_type: 'missing_in' | 'missing_out' | 'both_missing' | 'incorrect_time' | 'forgot_to_punch' | 'on_duty';
    proposed_clock_in?: string | null; proposed_clock_out?: string | null; reason_code: string;
    employee_notes: string; status: 'pending' | 'approved' | 'rejected' | 'cancelled';
    actioned_at?: string | null; action_notes?: string | null;
    employee?: EmployeeSummary; attendance_record?: HrAttendanceRecord;
}

// --- Leave Types ---
export interface HrLeaveType {
    id: number; row_version: number; code: string; name: string; color: string;
    is_paid: boolean; requires_attachment: boolean; attachment_mandatory_after_days: number;
    allow_half_day: boolean; allow_hourly: boolean; is_compensatory: boolean;
    sandwich_rule_applied: boolean; status: 'active' | 'inactive';
}

export interface HrLeavePolicy {
    id: number; row_version: number; leave_type_id: number; department_id?: number | null;
    employment_type_id?: number | null; name: string; annual_allocation_days: string;
    accrual_frequency: 'annual_upfront' | 'monthly_prorated' | 'quarterly' | 'no_accrual';
    max_carry_forward_days: string; carry_forward_expiry_months: number;
    max_consecutive_days: number; probation_restricted: boolean;
    effective_from: string; effective_to?: string | null;
    leave_type?: HrLeaveType; department?: HrDepartment;
}

export interface HrLeaveBalance {
    id: number; row_version: number; employee_id: number; leave_type_id: number; calendar_year: number;
    opening_balance: string; accrued_days: string; carried_forward_days: string; used_days: string;
    pending_approval_days: string; expired_days: string; current_balance: string;
    leave_type?: HrLeaveType; employee?: EmployeeSummary;
}

export interface HrLeaveRequest {
    id: number; row_version: number; employee_id: number; leave_type_id: number;
    start_date: string; end_date: string; duration_type: 'full_day' | 'half_day_morning' | 'half_day_afternoon' | 'custom_hours';
    total_days: string; reason: string; attachment_document_id?: number | null;
    status: 'pending' | 'approved' | 'rejected' | 'cancelled'; approved_at?: string | null; action_notes?: string | null;
    leave_type?: HrLeaveType; employee?: EmployeeSummary;
}

export interface HrCompOffClaim {
    id: number; row_version: number; employee_id: number; worked_date: string;
    claimed_days: string; reason: string; status: 'pending' | 'approved' | 'rejected' | 'expired' | 'used';
    expiry_date?: string | null; employee?: EmployeeSummary;
}
