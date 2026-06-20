import type { ApiCollection } from '@/shared/types/api';
import type { NamedResource } from '@/shared/types/common';

export type ReportFormat = 'html' | 'csv' | 'xlsx' | 'pdf' | 'print';

export interface ReportColumn {
    key: string;
    label: string;
    sortable: boolean;
    format: string;
}

export interface ReportFilter {
    key: string;
    label: string;
    type: 'text' | 'select' | 'date' | 'number' | 'boolean';
    options?: Array<{ value: string; label: string }>;
}

export interface ReportDefinition {
    key: string;
    title: string;
    group: string;
    description?: string;
    columns: ReportColumn[];
    filters: ReportFilter[];
    supports_date_range: boolean;
    default_sort: string;
    default_direction: 'asc' | 'desc';
}

export interface ReportParams {
    page?: number;
    per_page?: number;
    search?: string;
    sort?: string;
    direction?: 'asc' | 'desc';
    date_from?: string;
    date_to?: string;
    filters?: Record<string, string | number | boolean | null | undefined>;
}

export type ReportRow = Record<string, string | number | boolean | null>;
export type ReportResult = ApiCollection<ReportRow> & { report: ReportDefinition };

export interface TechnicianWorkReportParams {
    page?: number;
    per_page?: number;
    search?: string;
    sort?: string;
    direction?: 'asc' | 'desc';
    date_from?: string;
    date_to?: string;
    employee_id?: number | null;
    supervisor_id?: number | null;
    customer_id?: number | null;
    vehicle_id?: number | null;
    job_status?: string;
    role_type?: string;
    commission_type?: string;
    invoice_status?: string;
    payment_status?: string;
}

export interface TechnicianWorkReportRow {
    id: number;
    job_number: string;
    job_date: string | null;
    job_status: string | null;
    customer: NamedResource | null;
    customer_name: string;
    vehicle: NamedResource | null;
    vehicle_label: string;
    line_description: string;
    line_source_type: string | null;
    employee: NamedResource | null;
    employee_name: string;
    role_type: string;
    assigned_hours: string;
    rate: string;
    labour_amount: string;
    line_total: string;
    commission_type: string | null;
    commission_value: string;
    commission_amount: string;
    supervisor: NamedResource | null;
    supervisor_name: string;
    supervisor_commission_amount: string;
    invoice_status: string | null;
    invoice: NamedResource | null;
    payment_status: string | null;
    payment: NamedResource | null;
}

export interface TechnicianWorkReportSummary {
    total_assigned_hours: string;
    total_labour_amount: string;
    total_technician_commission: string;
    total_supervisor_commission: string;
    total_payable_commission: string;
}

export type TechnicianWorkReportResult = ApiCollection<TechnicianWorkReportRow> & {
    report: ReportDefinition;
    summary: TechnicianWorkReportSummary;
};

export type EmployeeCommissionGroupBy = 'employee' | 'department' | 'designation' | 'supervisor' | 'commission_source';

export interface EmployeeCommissionReportParams {
    page?: number;
    per_page?: number;
    search?: string;
    sort?: string;
    direction?: 'asc' | 'desc';
    group_by?: EmployeeCommissionGroupBy;
    date_from?: string;
    date_to?: string;
    employee_id?: number | null;
    department_id?: number | null;
    designation_id?: number | null;
    supervisor_id?: number | null;
    customer_id?: number | null;
    vehicle_id?: number | null;
    job_status?: string;
    invoice_status?: string;
    payment_status?: string;
    commission_type?: string;
    commission_source?: 'technician' | 'supervisor' | '';
    role_type?: string;
    commission_status?: 'pending' | 'earned' | 'cancelled' | '';
    include_cancelled?: boolean;
}

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

export type OperationalReportKind = 'purchase' | 'vehicle-service' | 'employee-incentive';

export interface OperationalReportParams {
    page?: number;
    per_page?: number;
    search?: string;
    sort?: string;
    direction?: 'asc' | 'desc';
    date_from?: string;
    date_to?: string;
    purchase_status?: string;
    job_status?: string;
    line_source_type?: string;
    incentive_source?: 'technician' | 'supervisor' | '';
    supplier_id?: number | null;
    item_id?: number | null;
    customer_id?: number | null;
    vehicle_id?: number | null;
    employee_id?: number | null;
    department_id?: number | null;
}

export type OperationalReportSummary = Record<string, string | number>;
export type OperationalReportResult = ApiCollection<ReportRow> & {
    report: ReportDefinition;
    summary: OperationalReportSummary;
};
