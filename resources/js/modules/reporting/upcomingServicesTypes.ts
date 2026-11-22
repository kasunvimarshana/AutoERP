/**
 * AutoERP Enterprise System
 *
 * @author Kasun Vimarshana <kasunvmail@gmail.com>
 *
 * Phone: +94713525253
 */

export type UpcomingUrgencyStatus = 'overdue' | 'due_today' | 'due_this_week' | 'due_this_month' | 'upcoming';

export type UpcomingStatusPreset = 'all' | 'overdue' | 'due_today' | 'due_this_week' | 'due_this_month' | 'upcoming';

export interface UpcomingServiceVehicle {
    id: number;
    registration_number: string;
    vehicle_number: string | null;
    display_name: string;
    make: string | null;
    model: string | null;
    year: number | null;
    current_odometer: string | null;
    odometer_unit: string | null;
}

export interface UpcomingServiceCustomer {
    id: number;
    code: string | null;
    name: string;
    phone: string | null;
    email: string | null;
}

export interface UpcomingServiceRow {
    id: number;
    job_number: string;
    job_date: string;
    job_type: string;
    job_type_label: string;
    job_status: string;
    job_status_label: string;
    next_service_date: string;
    days_diff: number;
    days_overdue: number;
    days_until_due: number;
    urgency_status: UpcomingUrgencyStatus;
    urgency_label: string;
    odometer_reading: string | null;
    next_service_mileage: string | null;
    vehicle: UpcomingServiceVehicle;
    registration_number: string;
    vehicle_label: string;
    customer: UpcomingServiceCustomer;
    customer_name: string;
    customer_phone: string;
}

export interface UpcomingServicesSummary {
    total_upcoming: number;
    total_overdue: number;
    total_due_today: number;
    total_due_this_week: number;
    total_due_this_month: number;
}

export interface UpcomingServicesParams {
    page?: number;
    per_page?: number;
    date_from?: string;
    date_to?: string;
    status_preset?: UpcomingStatusPreset;
    vehicle_id?: number;
    customer_id?: number;
    job_status?: string;
    search?: string;
    latest_only?: boolean;
}

export interface UpcomingServicesResult {
    data: UpcomingServiceRow[];
    summary: UpcomingServicesSummary;
    meta: {
        current_page: number;
        from: number | null;
        last_page: number;
        per_page: number;
        to: number | null;
        total: number;
    };
    links: {
        first: string | null;
        last: string | null;
        prev: string | null;
        next: string | null;
    };
    report: {
        key: string;
        title: string;
        group: string;
        description: string;
        columns: Array<{
            key: string;
            label: string;
            sortable: boolean;
            format: string;
            summarized: boolean;
        }>;
    };
}
