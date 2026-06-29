import type { PaymentLifecycleFilterParams } from './paymentLifecycleTypes';

export type EmployeeCommissionGroupBy =
    | 'employee'
    | 'department'
    | 'designation'
    | 'supervisor'
    | 'commission_source';

export interface EmployeeCommissionReportParams extends PaymentLifecycleFilterParams {
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
    commission_type?: string;
    commission_source?: 'technician' | 'supervisor' | '';
    role_type?: string;
    commission_status?: 'pending' | 'earned' | 'cancelled' | '';
    include_cancelled?: boolean;
}
