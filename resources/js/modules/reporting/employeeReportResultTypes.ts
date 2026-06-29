import type { ApiCollection } from '@/shared/types/api';
import type { NamedResource } from '@/shared/types/common';
import type { ReportDefinition } from './reportingTypes';

export interface EmployeeCommissionEmployee extends NamedResource {
    department?: NamedResource | null;
    designation?: NamedResource | null;
}

export interface EmployeeCommissionReportRow {
    id: string;
    commission_source: 'technician' | 'supervisor';
    employee: EmployeeCommissionEmployee;
    employee_code: string;
    employee_name: string;
    department: NamedResource | null;
    department_name: string;
    designation: NamedResource | null;
    designation_name: string;
    job: NamedResource;
    job_number: string;
    job_date: string | null;
    customer: NamedResource | null;
    customer_name: string;
    vehicle: NamedResource | null;
    vehicle_label: string;
    supervisor: NamedResource | null;
    supervisor_name: string;
    line_description: string;
    role_type: string;
    assigned_hours: string;
    rate: string;
    labour_amount: string;
    commission_base: string;
    commission_type: string;
    commission_value: string;
    commission_amount: string;
    commission_status: string;
    completed_at: string | null;
    invoice_progress: string;
    payment_progress: string;
    invoice_total: string;
    paid_total: string;
    balance_due: string;
    job_status: string;
    group_key: string;
    group_label: string;
}

export interface EmployeeCommissionSummary {
    total_entries: number;
    total_employees: number;
    total_jobs: number;
    total_hours: string;
    total_labour_value: string;
    total_commission_base: string;
    technician_commission: string;
    supervisor_commission: string;
    earned_commission: string;
    pending_commission: string;
    cancelled_commission: string;
    total_commission: string;
    average_commission_per_job: string;
    average_commission_per_employee: string;
    average_commission_per_hour: string;
}

export interface EmployeeCommissionRanking {
    employee: EmployeeCommissionEmployee | null;
    labour_value: string;
    commission_amount: string;
}

export interface EmployeeCommissionGroup {
    key: string;
    label: string;
    resource: NamedResource | EmployeeCommissionEmployee | null;
    total_jobs: number;
    total_hours: string;
    total_labour_value: string;
    total_commission: string;
}

export type EmployeeCommissionReportResult = ApiCollection<EmployeeCommissionReportRow> & {
    report: ReportDefinition;
    summary: EmployeeCommissionSummary;
    rankings: {
        top_earning_employee: EmployeeCommissionRanking | null;
        top_commission_employee: EmployeeCommissionRanking | null;
    };
    groups: EmployeeCommissionGroup[];
};
