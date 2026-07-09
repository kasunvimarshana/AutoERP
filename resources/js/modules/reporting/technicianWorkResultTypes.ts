import type { ApiCollection } from '@/shared/types/api';
import type { NamedResource } from '@/shared/types/common';
import type { ReportDefinition } from './genericReportTypes';

export interface TechnicianWorkReportRow {
    id: number;
    job_number: string;
    job_date: string | null;
    operational_status: string | null;
    billing_status: string | null;
    payment_status: string | null;
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
    payment_document_status: string | null;
    payment_posting_status: string | null;
    payment_allocation_status: string | null;
    payment_instrument_status: string | null;
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
