import type { NamedResource } from '@/shared/types/common';

export type DriverLicenseStatus = 'valid' | 'expiring_soon' | 'expired';

export interface DriverAssignmentSummary {
    allocation_id: number;
    allocation_number?: string;
    vehicle_id?: number;
    vehicle_number: string;
    agreement_number?: string | null;
    role: string;
    assigned_from: string;
    assigned_to: string;
    status?: string;
}

export interface DriverSummary {
    id: number;
    tenant_id: number;
    organization_unit_id: number | null;
    driver_number: string;
    employee_number: string;
    code: string | null;
    name: string;
    display_name: string;
    first_name: string;
    middle_name: string | null;
    last_name: string | null;
    email: string | null;
    phone: string | null;
    mobile: string | null;
    status: string;
    availability_status: string;
    designation: NamedResource | null;
    joined_date: string | null;
    date_of_birth: string | null;
    gender: string | null;
    default_hourly_rate: string | number;
    default_daily_rate: string | number;
    work_start_time?: string | null;
    work_end_time?: string | null;
    standard_shift_hours?: number | string | null;
    nic_passport: string | null;
    driving_license_number: string | null;
    driving_license_classes: string[];
    driving_license_expiry: string | null;
    driving_license_issued: string | null;
    license_status: DriverLicenseStatus;
    days_until_license_expiry: number | null;
    current_assignment: DriverAssignmentSummary | null;
}

export interface DriverLicenseAssignmentItem {
    id: number;
    license_id: number;
    license_name: string;
    license_number: string;
    issued_date: string | null;
    expiry_date: string | null;
    status: string;
}

export interface DriverRecentAssignmentItem {
    id: number;
    allocation_id: number;
    vehicle_number: string;
    agreement_number?: string | null;
    role: string;
    assigned_from: string;
    assigned_to: string;
    status: string;
}

export interface DriverStatusHistoryItem {
    id: number;
    old_status: string | null;
    new_status: string;
    reason: string | null;
    changed_at: string;
}

export interface DriverDetail extends DriverSummary {
    emergency_contact_name: string | null;
    emergency_contact_phone: string | null;
    emergency_contact_relationship: string | null;
    address: string | null;
    city: string | null;
    blood_group: string | null;
    notes: string | null;
    license_assignments: DriverLicenseAssignmentItem[];
    recent_assignments: DriverRecentAssignmentItem[];
    status_histories: DriverStatusHistoryItem[];
}

export interface DriverPayload {
    code?: string | null;
    employee_number?: string | null;
    first_name: string;
    middle_name?: string | null;
    last_name?: string | null;
    display_name?: string | null;
    email?: string | null;
    phone?: string | null;
    mobile?: string | null;
    date_of_birth?: string | null;
    gender?: string | null;
    joined_date?: string | null;
    default_daily_rate?: number | string | null;
    default_hourly_rate?: number | string | null;
    work_start_time?: string | null;
    work_end_time?: string | null;
    standard_shift_hours?: number | string | null;
    status?: string;
    availability_status?: string;
    notes?: string | null;
    nic_passport?: string | null;
    driving_license_number: string;
    driving_license_classes?: string[];
    driving_license_issued?: string | null;
    driving_license_expiry?: string | null;
    emergency_contact_name?: string | null;
    emergency_contact_phone?: string | null;
    emergency_contact_relationship?: string | null;
    address?: string | null;
    city?: string | null;
    blood_group?: string | null;
}

export interface DriverStatusPayload {
    status: string;
    availability_status?: string;
    reason?: string;
}

export interface DriverListParams {
    search?: string;
    status?: string;
    availability_status?: string;
    license_status?: string;
    page?: number;
    per_page?: number;
}
