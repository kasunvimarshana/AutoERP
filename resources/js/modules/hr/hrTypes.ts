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
    id: number; tenant_id: number; organization_unit_id?: number | null; employee_number: string; code?: string | null;
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
    employee_number?: string; code?: string; first_name: string; middle_name?: string; last_name?: string; display_name?: string;
    email?: string; phone?: string; mobile?: string; department_id?: number | null; designation_id?: number | null;
    employment_type_id?: number | null; reporting_manager_id?: number | null; joined_date?: string; resigned_date?: string;
    date_of_birth?: string; gender?: string; status?: EmployeeStatus; availability_status?: EmployeeAvailabilityStatus;
    default_hourly_rate?: string; default_daily_rate?: string; default_service_rate?: string; notes?: string;
}
export interface EmployeeContact { id: number; contact_name: string; relationship?: string | null; email?: string | null; phone?: string | null; mobile?: string | null; is_emergency_contact: boolean; is_primary: boolean; is_active: boolean; notes?: string | null; }
export type EmployeeContactPayload = Omit<EmployeeContact, 'id'>;
export interface EmployeeAddress { id: number; address_type: string; address_line_1: string; address_line_2?: string | null; city?: string | null; state?: string | null; postal_code?: string | null; country?: string | null; is_primary: boolean; is_active: boolean; }
export type EmployeeAddressPayload = Omit<EmployeeAddress, 'id'>;
export interface EmployeeDocument { id: number; document_type: string; document_number?: string | null; issued_date?: string | null; expiry_date?: string | null; status: string; notes?: string | null; }
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
