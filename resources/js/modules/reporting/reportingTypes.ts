import type { ApiCollection } from '@/shared/types/api';
import type { NamedResource } from '@/shared/types/common';

export type ReportFormat = 'csv' | 'xlsx' | 'pdf' | 'print';

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
