export type EmployeeRecord = {
    id: number;
    tenant_id: number;
    user_id: number;
    employee_code: string | null;
    org_unit_id: number | null;
    job_title: string | null;
    hire_date: string | null;
    termination_date: string | null;
    metadata: Record<string, unknown> | null;
    created_at: string;
    updated_at: string;
};

export type EmployeeListFilters = {
    tenant_id?: number;
    user_id?: number;
    org_unit_id?: number;
    employee_code?: string;
    job_title?: string;
    per_page?: number;
    page?: number;
    sort?: string;
};

export type EmployeePayload = {
    tenant_id: number;
    user?: {
        email: string;
        first_name: string;
        last_name: string;
        phone?: string | null;
        active?: boolean;
        address?: Record<string, unknown> | null;
        preferences?: Record<string, unknown> | null;
    };
    employee_code?: string | null;
    org_unit_id?: number | null;
    job_title?: string | null;
    hire_date?: string | null;
    termination_date?: string | null;
    metadata?: Record<string, unknown> | null;
};
