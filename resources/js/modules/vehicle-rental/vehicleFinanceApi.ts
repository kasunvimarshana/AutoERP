import { apiClient } from '@/shared/api/apiClient';
import type { PaginationMeta } from '@/shared/types/pagination';

export interface PaginatedResponse<T> {
    data: T[];
    meta: PaginationMeta;
}

export enum FinanceStatus {
    Draft = 'draft',
    Active = 'active',
    Settled = 'settled',
    Defaulted = 'defaulted',
    Cancelled = 'cancelled',
}

export enum InstallmentStatus {
    Scheduled = 'scheduled',
    Due = 'due',
    Overdue = 'overdue',
    PartiallyPaid = 'partially_paid',
    Paid = 'paid',
    Waived = 'waived',
    Reversed = 'reversed',
}

export interface FinanceInstallment {
    id: number;
    row_version: number;
    finance_agreement_id: number;
    installment_number: number;
    due_date: string;
    principal_due: string;
    interest_due: string;
    fee_due: string;
    tax_due: string;
    total_due: string;
    paid_amount: string;
    balance_due: string;
    status: InstallmentStatus;
    invoice?: {
        id: number;
        invoice_number: string;
        status: string;
    } | null;
}

export interface VehicleFinanceAgreement {
    id: number;
    row_version: number;
    agreement_number: string;
    supplier: { id: number; name: string };
    vehicle: {
        id: number;
        label: string;
        make?: string | null;
        model?: string | null;
    };
    agreement_date: string;
    starts_at: string;
    matures_at: string;
    currency: { id: number; code: string };
    principal_amount: string;
    initial_deposit_amount: string;
    residual_value: string;
    interest_method: string;
    annual_interest_rate: string;
    installment_frequency: string;
    installment_count: number;
    payment_term_days: number;
    tax_group_id?: number | null;
    status: FinanceStatus;
    remarks?: string | null;
    approved_at?: string | null;
    created_at?: string | null;
    installments?: FinanceInstallment[];
}

export interface StoreFinanceAgreementPayload {
    agreement_number?: string;
    supplier_id: number;
    vehicle_id: number;
    agreement_date: string;
    starts_at: string;
    matures_at: string;
    currency_id: number;
    principal_amount: string;
    initial_deposit_amount?: string;
    residual_value?: string;
    interest_method?: string;
    annual_interest_rate: string;
    installment_frequency: string;
    installment_count: number;
    payment_term_days?: number;
    tax_group_id?: number | null;
    remarks?: string | null;
}

export const FINANCE_PERMISSION = {
    view: 'vehicle-rental.finance.view',
    manage: 'vehicle-rental.finance.manage',
};

export const FINANCE_REGISTER_PATH = '/vehicle-rental/finance';

export async function listFinanceAgreements(
    page: number = 1,
    signal?: AbortSignal,
    filters?: Record<string, string | number>,
): Promise<PaginatedResponse<VehicleFinanceAgreement>> {
    const params = new URLSearchParams({ page: String(page) });
    if (filters) {
        Object.entries(filters).forEach(([key, value]) => {
            if (value !== undefined && value !== '') params.append(key, String(value));
        });
    }
    const response = await apiClient.get<PaginatedResponse<VehicleFinanceAgreement>>(`/api/v1/vehicle-rental/finance-agreements?${params.toString()}`, { signal });
    return response.data;
}

export async function getFinanceAgreement(id: number, signal?: AbortSignal): Promise<VehicleFinanceAgreement> {
    const response = await apiClient.get<{ data: VehicleFinanceAgreement }>(`/api/v1/vehicle-rental/finance-agreements/${id}`, { signal });
    return response.data.data;
}

export async function createFinanceAgreement(payload: StoreFinanceAgreementPayload): Promise<VehicleFinanceAgreement> {
    const response = await apiClient.post<{ data: VehicleFinanceAgreement }>('/api/v1/vehicle-rental/finance-agreements', payload);
    return response.data.data;
}

export async function activateFinanceAgreement(id: number, version: number): Promise<VehicleFinanceAgreement> {
    const response = await apiClient.patch<{ data: VehicleFinanceAgreement }>(`/api/v1/vehicle-rental/finance-agreements/${id}/activate`, {
        expected_version: version,
    });
    return response.data.data;
}

export async function createInstallmentPayable(
    installmentId: number,
    version: number,
    invoiceDate: string,
): Promise<{ id: number }> {
    const response = await apiClient.post<{ data: { id: number } }>(`/api/v1/vehicle-rental/finance-agreements/installments/${installmentId}/payable`, {
        expected_version: version,
        invoice_date: invoiceDate,
    });
    return response.data.data;
}

export async function refreshFinanceDueStatuses(): Promise<{ updated: number }> {
    const response = await apiClient.patch<{ updated: number }>('/api/v1/vehicle-rental/finance-agreements/installments/refresh-due-statuses');
    return response.data;
}
