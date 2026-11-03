import { apiClient } from '@/shared/api/apiClient';
import type { PaginationMeta } from '@/shared/types/pagination';

export interface PaginatedResponse<T> {
    data: T[];
    meta: PaginationMeta;
}

export enum ExpenseStatus {
    Draft = 'draft',
    Submitted = 'submitted',
    Approved = 'approved',
    Allocated = 'allocated',
    Rejected = 'rejected',
    Reversed = 'reversed',
}

export enum ExpenseType {
    Fuel = 'fuel',
    Toll = 'toll',
    Maintenance = 'maintenance',
    Fine = 'fine',
    Other = 'other',
}

export enum AllocationType {
    CompanyCost = 'company_cost',
    CustomerRecovery = 'customer_recovery',
    OwnerDeduction = 'owner_deduction',
    EmployeeReimbursement = 'employee_reimbursement',
}

export interface ExpenseAllocation {
    id: number;
    sequence: number;
    allocation_type: AllocationType;
    allocation_type_label: string;
    net_amount: string;
    tax_amount: string;
    total_amount: string;
    customer?: { id: number; name: string } | null;
    supplier?: { id: number; name: string } | null;
    target_customer_agreement?: { id: number; reference: string } | null;
    target_owner_agreement?: { id: number; reference: string } | null;
}

export interface RentalExpense {
    id: number;
    row_version: number;
    expense_number: string;
    customer_agreement?: { id: number; reference: string } | null;
    vehicle_use?: { id: number; label: string } | null;
    running_chart?: { id: number; reference: string } | null;
    vehicle: {
        id: number;
        label: string;
        make?: string | null;
        model?: string | null;
    };
    supplier?: { id: number; name: string } | null;
    employee?: { id: number; name: string } | null;
    expense_type: ExpenseType;
    expense_type_label: string;
    expense_date: string;
    currency: { id: number; code: string };
    net_amount: string;
    tax_amount: string;
    gross_amount: string;
    reference_number?: string | null;
    description?: string | null;
    status: ExpenseStatus;
    submitted_at?: string | null;
    approved_at?: string | null;
    created_at?: string | null;
    allocations?: ExpenseAllocation[];
}

export interface StoreRentalExpensePayload {
    expense_number?: string;
    customer_agreement_id?: number | null;
    vehicle_use_id?: number | null;
    running_chart_id?: number | null;
    vehicle_id: number;
    supplier_id?: number | null;
    employee_id?: number | null;
    expense_type: string;
    expense_date: string;
    currency_id: number;
    net_amount: string;
    tax_group_id?: number | null;
    tax_amount?: string;
    reference_number?: string | null;
    description?: string | null;
}

export const EXPENSE_PERMISSION = {
    view: 'vehicle-rental.expenses.view',
    manage: 'vehicle-rental.expenses.manage',
};

export const EXPENSE_REGISTER_PATH = '/vehicle-rental/expenses';

export async function listRentalExpenses(
    page: number = 1,
    signal?: AbortSignal,
    filters?: Record<string, string | number>,
): Promise<PaginatedResponse<RentalExpense>> {
    const params = new URLSearchParams({ page: String(page) });
    if (filters) {
        Object.entries(filters).forEach(([key, value]) => {
            if (value !== undefined && value !== '') params.append(key, String(value));
        });
    }
    const response = await apiClient.get<PaginatedResponse<RentalExpense>>(`/api/v1/vehicle-rental/expenses?${params.toString()}`, { signal });
    return response.data;
}

export async function getRentalExpense(id: number, signal?: AbortSignal): Promise<RentalExpense> {
    const response = await apiClient.get<{ data: RentalExpense }>(`/api/v1/vehicle-rental/expenses/${id}`, { signal });
    return response.data.data;
}

export async function createRentalExpense(payload: StoreRentalExpensePayload): Promise<RentalExpense> {
    const response = await apiClient.post<{ data: RentalExpense }>('/api/v1/vehicle-rental/expenses', payload);
    return response.data.data;
}

export async function transitionRentalExpense(
    id: number,
    version: number,
    status: ExpenseStatus,
    allocations?: Array<{
        allocation_type: string;
        net_amount: string;
        tax_amount?: string;
        markup_amount?: string;
        withholding_amount?: string;
        target_customer_agreement_id?: number | null;
        target_owner_agreement_id?: number | null;
        target_vehicle_use_id?: number | null;
        customer_id?: number | null;
        supplier_id?: number | null;
    }>,
): Promise<RentalExpense> {
    const response = await apiClient.patch<{ data: RentalExpense }>(`/api/v1/vehicle-rental/expenses/${id}/transition`, {
        expected_version: version,
        status,
        allocations,
    });
    return response.data.data;
}
