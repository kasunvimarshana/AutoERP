import { apiClient } from '@/shared/api/apiClient';
import { endpoints } from '@/shared/api/endpoints';
import type { ApiCollection, ApiResource, ListParams } from '@/shared/types/api';

export const EXPENSE_IDEMPOTENCY_HEADER = 'Idempotency-Key';
const retainedCreateKeys = new Map<string, string>();

export interface ExpenseType {
    id: number;
    row_version: number;
    code: string;
    name: string;
    description?: string | null;
    is_active: boolean;
    sort_order: number;
}

export interface ExpensePaymentMethod {
    id: number;
    code: string;
    name: string;
    type: string;
    requires_reference: boolean;
    requires_instrument_details: boolean;
}

export interface Expense {
    id: number;
    row_version: number;
    expense_number: string;
    expense_date: string;
    amount: string;
    exchange_rate: string;
    status: string;
    expense_type: { id: number; code: string; name: string };
    organization_unit?: { id: number; code: string; name: string };
    currency?: { id: number; code: string; name: string; symbol?: string | null } | null;
    payment_method: { id: number; code: string; name: string; type: string };
    reference_number?: string | null;
    instrument_number?: string | null;
    instrument_date?: string | null;
    external_bank_name?: string | null;
    notes?: string | null;
    finance_posting_reference?: string | null;
    finance_reversal_reference?: string | null;
    reversal_date?: string | null;
    reversal_reason?: string | null;
}

export interface ExpensePayload {
    expense_type_id: number;
    payment_method_id: number;
    expense_date: string;
    amount: string;
    reference_number?: string;
    instrument_number?: string;
    instrument_date?: string;
    external_bank_name?: string;
    notes?: string;
}

export async function listExpenses(params: ListParams & {
    expense_type_id?: number;
    status?: string;
    date_from?: string;
    date_to?: string;
}, signal?: AbortSignal) {
    const response = await apiClient.get<ApiCollection<Expense>>(endpoints.expenses, { params, signal });
    return response.data;
}

export async function createExpense(payload: ExpensePayload) {
    const fingerprint = JSON.stringify(payload);
    const key = retainedCreateKeys.get(fingerprint) ?? globalThis.crypto.randomUUID();
    retainedCreateKeys.set(fingerprint, key);
    const response = await apiClient.post<ApiResource<Expense>>(endpoints.expenses, payload, {
        headers: { [EXPENSE_IDEMPOTENCY_HEADER]: key },
    });
    retainedCreateKeys.delete(fingerprint);
    return response.data.data;
}

export async function reverseExpense(id: number, expectedVersion: number, reversalDate: string, reason: string) {
    const response = await apiClient.post<ApiResource<Expense>>(`${endpoints.expenses}/${id}/reverse`, {
        expected_version: expectedVersion,
        reversal_date: reversalDate,
        reason,
    });
    return response.data.data;
}

export async function listExpenseTypes(params: ListParams & { is_active?: boolean }, signal?: AbortSignal) {
    const response = await apiClient.get<ApiCollection<ExpenseType>>(`${endpoints.expenses}/types`, { params, signal });
    return response.data;
}

export async function listExpenseTypeOptions(signal?: AbortSignal) {
    const response = await apiClient.get<ApiCollection<ExpenseType>>(`${endpoints.expenses}/type-options`, { signal });
    return response.data;
}

export async function createExpenseType(payload: Pick<ExpenseType, 'code' | 'name'> & Partial<ExpenseType>) {
    const response = await apiClient.post<ApiResource<ExpenseType>>(`${endpoints.expenses}/types`, payload);
    return response.data.data;
}

export async function updateExpenseType(id: number, payload: Pick<ExpenseType, 'row_version' | 'code' | 'name'> & Partial<ExpenseType>) {
    const response = await apiClient.put<ApiResource<ExpenseType>>(`${endpoints.expenses}/types/${id}`, payload);
    return response.data.data;
}

export async function listExpensePaymentMethods(signal?: AbortSignal) {
    const response = await apiClient.get<ApiResource<ExpensePaymentMethod[]>>(`${endpoints.expenses}/payment-methods`, { signal });
    return response.data.data;
}
