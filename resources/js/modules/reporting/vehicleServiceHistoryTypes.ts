import type { ApiCollection } from '@/shared/types/api';
import type { NamedResource } from '@/shared/types/common';
import type { ReportDefinition } from './genericReportTypes';

export interface VehicleServiceHistoryParams {
    vehicle_id?: number;
    page?: number;
    per_page?: number;
    date_from?: string;
    date_to?: string;
    job_status?: string;
    include_cancelled?: boolean;
}

export interface VehicleServiceHistoryLine {
    id: number;
    category: 'work' | 'part';
    description: string;
    quantity: string;
    uom?: string | null;
    employees: NamedResource[];
}

export interface VehicleServiceHistoryRow {
    id: string;
    live_job_id: number | null;
    source: 'current' | 'legacy';
    source_label: string;
    job_type: string;
    job_type_label: string;
    job_number: string;
    job_date: string | null;
    status: string;
    vehicle_label: string;
    customer: NamedResource | null;
    customer_name: string;
    supervisor: NamedResource | null;
    supervisor_name: string;
    technicians: NamedResource[];
    technician_names: string;
    odometer_reading: string | null;
    next_service_mileage: string | null;
    complaint: string | null;
    inspection_notes: string | null;
    diagnosis: string | null;
    recommended_work: string | null;
    work_summary: string;
    lines: VehicleServiceHistoryLine[];
    invoice_total: string;
    paid_total: string;
    balance_due: string;
}

export type VehicleServiceHistoryResult = ApiCollection<VehicleServiceHistoryRow> & {
    report: ReportDefinition;
    vehicle: {
        id: number;
        vehicle_number: string;
        registration_number: string | null;
        display_name: string;
        make: string | null;
        model: string | null;
        manufacture_year: number | null;
        odometer_reading: string;
        odometer_unit: string | null;
        status: string;
        current_owner: { code: string | null; name: string | null } | null;
    } | null;
    summary: {
        total_jobs: number;
        last_service_date: string | null;
        last_service_mileage: string | null;
        next_service_mileage: string | null;
    };
};
