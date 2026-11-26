/**
 * AutoERP Enterprise System
 *
 * @author Kasun Vimarshana <kasunvmail@gmail.com>
 *
 * Phone: +94713525253
 */

import type {
    CompOffClaimStatus,
    LeaveAccrualFrequency,
    LeaveDurationType,
    LeaveRequestStatus,
    LeaveTypeStatus,
    ShiftAssignmentType,
    ShiftBreakType,
    ShiftReallocationReason,
    ShiftStatus,
    ShiftSwapStatus,
} from './hrTypes';

export const SHIFT_STATUS_OPTIONS: ReadonlyArray<{ value: ShiftStatus; label: string }> = [
    { value: 'active', label: 'Active' },
    { value: 'inactive', label: 'Inactive' },
];

export const SHIFT_BREAK_TYPE_OPTIONS: ReadonlyArray<{ value: ShiftBreakType; label: string }> = [
    { value: 'meal', label: 'Meal Break' },
    { value: 'rest', label: 'Rest Break' },
    { value: 'prayer', label: 'Prayer Break' },
    { value: 'other', label: 'Other' },
];

export const SHIFT_ASSIGNMENT_TYPE_OPTIONS: ReadonlyArray<{ value: ShiftAssignmentType; label: string }> = [
    { value: 'specific_date', label: 'Specific Single Date' },
    { value: 'date_range', label: 'Date Range' },
    { value: 'recurring', label: 'Weekly Recurring Day' },
];

export const DAY_OF_WEEK_OPTIONS: ReadonlyArray<{ value: number; label: string }> = [
    { value: 1, label: 'Monday' },
    { value: 2, label: 'Tuesday' },
    { value: 3, label: 'Wednesday' },
    { value: 4, label: 'Thursday' },
    { value: 5, label: 'Friday' },
    { value: 6, label: 'Saturday' },
    { value: 7, label: 'Sunday' },
];

export const SHIFT_SWAP_STATUS_OPTIONS: ReadonlyArray<{ value: ShiftSwapStatus; label: string }> = [
    { value: 'pending_peer', label: 'Pending Peer' },
    { value: 'peer_accepted', label: 'Peer Accepted' },
    { value: 'peer_rejected', label: 'Peer Rejected' },
    { value: 'manager_approved', label: 'Manager Approved' },
    { value: 'manager_rejected', label: 'Manager Rejected' },
    { value: 'cancelled', label: 'Cancelled' },
];

export const SHIFT_REALLOCATION_REASON_OPTIONS: ReadonlyArray<{ value: ShiftReallocationReason; label: string }> = [
    { value: 'sick_cover', label: 'Sick Leave Cover' },
    { value: 'emergency_cover', label: 'Emergency Leave Cover' },
    { value: 'surge_demand', label: 'Surge Demand' },
    { value: 'operational_need', label: 'Operational Need' },
];

export const DEFAULT_SHIFT_COLOR = '#3B82F6';
export const DEFAULT_OVERTIME_MULTIPLIER = '2.00';

export function getDayOfWeekLabel(day?: number | null): string {
    if (!day) return '-';
    return DAY_OF_WEEK_OPTIONS.find((d) => d.value === day)?.label ?? `Day ${day}`;
}

export function getShiftReallocationReasonLabel(reason: ShiftReallocationReason | string): string {
    return SHIFT_REALLOCATION_REASON_OPTIONS.find((r) => r.value === reason)?.label ?? reason;
}

export const DEFAULT_LEAVE_TYPE_COLOR = '#10B981';

export const LEAVE_TYPE_STATUS_OPTIONS: ReadonlyArray<{ value: LeaveTypeStatus; label: string }> = [
    { value: 'active', label: 'Active' },
    { value: 'inactive', label: 'Inactive' },
];

export const LEAVE_ACCRUAL_FREQUENCY_OPTIONS: ReadonlyArray<{ value: LeaveAccrualFrequency; label: string }> = [
    { value: 'annual_upfront', label: 'Annual Upfront' },
    { value: 'monthly_prorated', label: 'Monthly Prorated' },
    { value: 'quarterly', label: 'Quarterly' },
    { value: 'no_accrual', label: 'No Accrual' },
];

export const LEAVE_DURATION_TYPE_OPTIONS: ReadonlyArray<{ value: LeaveDurationType; label: string }> = [
    { value: 'full_day', label: 'Full Day' },
    { value: 'half_day_morning', label: 'Half Day (Morning)' },
    { value: 'half_day_afternoon', label: 'Half Day (Afternoon)' },
    { value: 'custom_hours', label: 'Custom Hours' },
];

export const LEAVE_REQUEST_STATUS_OPTIONS: ReadonlyArray<{ value: LeaveRequestStatus; label: string }> = [
    { value: 'pending', label: 'Pending' },
    { value: 'approved', label: 'Approved' },
    { value: 'rejected', label: 'Rejected' },
    { value: 'cancelled', label: 'Cancelled' },
];

export const COMP_OFF_STATUS_OPTIONS: ReadonlyArray<{ value: CompOffClaimStatus; label: string }> = [
    { value: 'pending', label: 'Pending' },
    { value: 'approved', label: 'Approved' },
    { value: 'rejected', label: 'Rejected' },
    { value: 'expired', label: 'Expired' },
    { value: 'used', label: 'Used' },
];

export function getLeaveAccrualFrequencyLabel(freq: LeaveAccrualFrequency | string): string {
    return LEAVE_ACCRUAL_FREQUENCY_OPTIONS.find((f) => f.value === freq)?.label ?? freq;
}

export function getLeaveDurationTypeLabel(duration: LeaveDurationType | string): string {
    return LEAVE_DURATION_TYPE_OPTIONS.find((d) => d.value === duration)?.label ?? duration;
}
