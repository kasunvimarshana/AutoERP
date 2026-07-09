import type { PaymentLifecycleFilterParams } from './paymentLifecycleTypes';

export interface TechnicianWorkReportParams extends PaymentLifecycleFilterParams {
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
    operational_status?: string;
    billing_status?: string;
    payment_status?: string;
    role_type?: string;
    commission_type?: string;
    invoice_status?: string;
}
