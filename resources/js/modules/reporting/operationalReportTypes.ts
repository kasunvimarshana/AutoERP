import type { ApiCollection } from '@/shared/types/api';
import type { ReportDefinition, ReportRow } from './genericReportTypes';

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
    agreement_id?: number | null;
    driver_employee_id?: number | null;
    chart_status?: string;
    assignment_status?: string;
    invoice_status?: string;
    exception_type?: string;
}

export type OperationalReportSummary = Record<string, string | number>;
export type OperationalReportResult = ApiCollection<ReportRow> & {
    report: ReportDefinition;
    summary: OperationalReportSummary;
};
