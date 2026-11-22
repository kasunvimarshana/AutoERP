/**
 * AutoERP Enterprise System
 *
 * @author Kasun Vimarshana <kasunvmail@gmail.com>
 *
 * Phone: +94713525253
 */

import type { ReportDefinition } from '../reportingTypes';

export interface ServiceJobsByJobTypeCustomer {
    id: number;
    code: string;
    name: string;
    phone: string | null;
    email: string | null;
}

export interface ServiceJobsByJobTypeVehicle {
    id: number;
    registration_number: string;
    vehicle_number: string;
    display_name: string;
    make: string;
    model: string;
    year: number | null;
}

export interface ServiceJobsByJobTypeSupervisor {
    id: number;
    name: string;
    code: string;
}

export interface ServiceJobsByJobTypeJob {
    id: number;
    job_number: string;
    job_date: string;
    expected_delivery_date: string | null;
    next_service_date: string | null;
    job_type_id: number | null;
    job_type_code: string;
    job_type_name: string;
    tracks_mileage: boolean;
    status: string;
    status_label: string;
    customer: ServiceJobsByJobTypeCustomer;
    customer_name: string;
    customer_phone: string | null;
    vehicle: ServiceJobsByJobTypeVehicle;
    registration_number: string;
    vehicle_label: string;
    supervisor: ServiceJobsByJobTypeSupervisor | null;
    supervisor_name: string | null;
    odometer_reading: string | null;
    next_service_mileage: string | null;
    subtotal: string;
    discount_total: string;
    tax_total: string;
    charge_total: string;
    grand_total: string;
    commission_cost_total: string;
    net_after_commission: string;
}

export interface ServiceJobsByJobTypeGroup {
    job_type_id: number | null;
    job_type_code: string;
    job_type_name: string;
    tracks_mileage: boolean;
    total_jobs: number;
    total_subtotal: string;
    total_discount: string;
    total_tax: string;
    total_charges: string;
    total_revenue: string;
    average_job_value: string;
    revenue_share_percentage: number;
    jobs_share_percentage: number;
    jobs: ServiceJobsByJobTypeJob[];
}

export interface ServiceJobsByJobTypeSummary {
    total_jobs: number;
    total_subtotal: string;
    total_discount: string;
    total_tax: string;
    total_charges: string;
    total_revenue: string;
    total_commission: string;
    average_job_value: string;
    status_counts: Record<string, number>;
}

export type ServiceJobsByJobTypeDatePreset = 'today' | 'this_week' | 'this_month' | 'this_year' | 'all' | 'custom';

export interface ServiceJobsByJobTypeParams {
    tenant_id?: number;
    organization_unit_id?: number;
    job_type_id?: number;
    job_type_code?: string;
    date_from?: string;
    date_to?: string;
    date_preset?: ServiceJobsByJobTypeDatePreset;
    job_status?: string;
    include_cancelled?: boolean;
    customer_id?: number;
    vehicle_id?: number;
    supervisor_employee_id?: number;
    search?: string;
    page?: number;
    per_page?: number;
}

export interface ServiceJobsByJobTypeResult {
    data: ServiceJobsByJobTypeJob[];
    summary: ServiceJobsByJobTypeSummary;
    groups: ServiceJobsByJobTypeGroup[];
    meta: {
        current_page: number;
        from: number;
        last_page: number;
        per_page: number;
        to: number;
        total: number;
    };
    links: {
        first: string | null;
        last: string | null;
        prev: string | null;
        next: string | null;
    };
    report: ReportDefinition;
}
